<?php

use App\Models\User;
use App\Modules\Access\Services\UserSessionTerminator;
use App\Modules\Cms\Enums\ContentStatus;
use App\Modules\Cms\Enums\PostType;
use App\Modules\Cms\Models\WebsitePost;
use App\Modules\Core\Enums\Role;
use App\Modules\Report\Exports\ReportSheet;
use App\Modules\Report\Reports\ReportData;
use App\Support\Excel\SafeValueBinder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Maatwebsite\Excel\Excel as ExcelWriter;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;

describe('security headers', function () {
    it('mengirim CSP bernonce dan header keamanan dasar', function () {
        $response = $this->get('/login')->assertOk();

        $csp = (string) $response->headers->get('Content-Security-Policy');
        expect($csp)
            ->toContain("script-src 'self' 'nonce-")
            ->not->toContain("script-src 'self' 'unsafe-inline'")
            ->toContain("object-src 'none'")
            ->toContain("frame-ancestors 'self'")
            ->toContain('https://*.midtrans.com')
            ->toContain('wss://*.pusher.com');

        $response->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeaderMissing('Strict-Transport-Security');
        expect($response->headers->get('Permissions-Policy'))->toContain('geolocation=(self)')->toContain('camera=()');
    });

    it('nonce di halaman sama dengan nonce di header, termasuk dari cache halaman publik', function () {
        WebsitePost::query()->create(['type' => PostType::News, 'slug' => 'a', 'title' => 'A', 'status' => ContentStatus::Published, 'published_at' => now()]);

        foreach (['MISS', 'HIT'] as $expected) {
            $response = $this->get('/berita')->assertHeader('X-Page-Cache', $expected);
            preg_match("/'nonce-([^']+)'/", (string) $response->headers->get('Content-Security-Policy'), $m);

            expect($m[1] ?? null)->not->toBeNull()
                ->and($response->getContent())->toContain('nonce="'.$m[1].'"')
                ->not->toContain('__SKOOLIA_CSP_NONCE__');
        }
    });

    it('mode report-only bisa diaktifkan lewat konfigurasi', function () {
        config(['skoolia.security.csp_report_only' => true]);

        $this->get('/login')
            ->assertHeaderMissing('Content-Security-Policy')
            ->assertHeader('Content-Security-Policy-Report-Only');
    });

    it('HSTS hanya dikirim di produksi lewat HTTPS', function () {
        app()->detectEnvironment(fn () => 'production');

        $this->get('https://skoolia.test/login')->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        $this->get('http://skoolia.test/login')->assertHeaderMissing('Strict-Transport-Security');
    });
});

describe('session', function () {
    beforeEach(function () {
        config(['session.driver' => 'database']);
    });

    function fakeSession(User $user, string $id): void
    {
        DB::table('sessions')->insert(['id' => $id, 'user_id' => $user->id, 'ip_address' => '10.0.0.1', 'user_agent' => 'HP lama', 'payload' => base64_encode(serialize([])), 'last_activity' => now()->timestamp]);
    }

    it('ganti password sendiri mengeluarkan sesi di perangkat lain', function () {
        $user = $this->actingAsRole(Role::Teacher, User::factory()->withRole(Role::Teacher)->create(['password' => 'password-lama-123']));
        fakeSession($user, 'sesi-hp-lama');
        $rememberBefore = $user->remember_token;

        $this->put('/settings/password', [
            'current_password' => 'password-lama-123',
            'password' => 'Password-Baru-2026!',
            'password_confirmation' => 'Password-Baru-2026!',
        ])->assertSessionHasNoErrors();

        expect(DB::table('sessions')->where('id', 'sesi-hp-lama')->exists())->toBeFalse()
            ->and($user->fresh()->remember_token)->not->toBe($rememberBefore)
            ->and(Hash::check('Password-Baru-2026!', $user->fresh()->password))->toBeTrue();

        // Sesi yang sedang dipakai tetap login.
        $this->get('/guru/dashboard')->assertOk();
    });

    it('reset password lewat email mengeluarkan semua sesi', function () {
        $user = User::factory()->withRole(Role::Guardian)->create(['email' => 'ortu@contoh.test']);
        fakeSession($user, 'sesi-dicuri');
        $token = Password::createToken($user);

        $this->post('/reset-password', [
            'token' => $token,
            'email' => 'ortu@contoh.test',
            'password' => 'Password-Baru-2026!',
            'password_confirmation' => 'Password-Baru-2026!',
        ])->assertSessionHasNoErrors();

        expect(DB::table('sessions')->where('user_id', $user->id)->exists())->toBeFalse();
    });

    it('terminator bisa mempertahankan satu sesi', function () {
        $user = User::factory()->create();
        fakeSession($user, 'sesi-1');
        fakeSession($user, 'sesi-2');

        app(UserSessionTerminator::class)->terminate($user, 'sesi-2');

        expect(DB::table('sessions')->where('user_id', $user->id)->pluck('id')->all())->toBe(['sesi-2']);
    });

    it('cookie sesi aman di produksi, http_only dan same_site lax', function () {
        expect(config('session.http_only'))->toBeTrue()
            ->and(config('session.same_site'))->toBe('lax')
            ->and(config('session.lifetime'))->toBeLessThanOrEqual(120);

        // Default `secure` mengikuti APP_ENV=production (lihat config/session.php).
        expect(file_get_contents(config_path('session.php')))->toContain("env('SESSION_SECURE_COOKIE', env('APP_ENV') === 'production')");
    });
});

describe('ekspor Excel', function () {
    it('teks berawalan = + - @ disimpan sebagai teks, bukan formula', function () {
        $data = new ReportData('Uji', [], ['Nama', 'Catatan', 'Nilai'], [
            ['=HYPERLINK("https://jahat.test","klik")', '+1+1', 90],
            ['@SUM(A1)', '-2+3', 75],
        ]);

        $path = tempnam(sys_get_temp_dir(), 'xls').'.xlsx';
        file_put_contents($path, Excel::raw(new ReportSheet($data, 'Sekolah'), ExcelWriter::XLSX));
        SafeValueBinder::reset(); // binder global: baca ulang dengan binder bawaan
        $sheet = IOFactory::load($path)->getActiveSheet();
        @unlink($path);

        $row = 0; // cari baris data pertama (setelah judul, nama sekolah, dan header)
        foreach (range(1, 10) as $r) {
            if ((string) $sheet->getCell("C{$r}")->getValue() === '90') {
                $row = $r;
                break;
            }
        }

        expect($row)->toBeGreaterThan(0)
            ->and($sheet->getCell("A{$row}")->getDataType())->toBe(DataType::TYPE_STRING)
            ->and($sheet->getCell("A{$row}")->getValue())->toBe('=HYPERLINK("https://jahat.test","klik")')
            ->and($sheet->getCell("B{$row}")->getDataType())->toBe(DataType::TYPE_STRING)
            ->and($sheet->getCell('A'.($row + 1))->getDataType())->toBe(DataType::TYPE_STRING)
            ->and($sheet->getCell("C{$row}")->getDataType())->toBe(DataType::TYPE_NUMERIC);
    });
});

it('folder upload dilindungi .htaccess', function () {
    expect(file_get_contents(storage_path('app/public/.htaccess')))->toContain('Require all denied')->toContain('php_flag engine off')
        ->and(file_get_contents(storage_path('app/private/.htaccess')))->toContain('Require all denied')
        ->and(file_get_contents(storage_path('.htaccess')))->toContain('Require all denied');
});
