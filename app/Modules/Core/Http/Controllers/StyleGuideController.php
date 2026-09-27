<?php

namespace App\Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Halaman review visual design system (hanya APP_ENV=local).
 */
class StyleGuideController extends Controller
{
    private const ROLES = ['principal', 'teacher', 'operator', 'guardian'];

    public function __invoke(Request $request): Response
    {
        $this->previewRole($request);

        $rows = $this->dummyStudents()
            ->when($request->string('search')->isNotEmpty(), fn (Collection $rows) => $rows->filter(
                fn (array $row) => str_contains(strtolower($row['name']), strtolower($request->string('search')->value()))
            ))
            ->when($request->filled('status'), fn (Collection $rows) => $rows->where('status', $request->string('status')->value()))
            ->values();

        $page = max(1, $request->integer('page', 1));
        $paginator = new LengthAwarePaginator(
            $rows->forPage($page, 8)->values(),
            $rows->count(),
            8,
            $page,
            ['path' => $request->url(), 'query' => $request->query()],
        );

        return Inertia::render('Core::StyleGuide', [
            'students' => JsonResource::collection($paginator),
            'filters' => $request->only('search', 'status'),
        ]);
    }

    public function parent(Request $request): Response
    {
        $this->previewRole($request, 'guardian');

        return Inertia::render('Core::StyleGuideParent');
    }

    public function auth(Request $request): Response
    {
        $portal = $request->string('portal')->value();

        return Inertia::render('Core::StyleGuideAuth', [
            'portal' => in_array($portal, self::ROLES, true) ? $portal : null,
        ]);
    }

    /**
     * Tampilkan menu role tertentu (?role=teacher) dengan semua permission.
     */
    private function previewRole(Request $request, string $default = 'operator'): void
    {
        $role = $request->string('role', $default)->value();
        $role = in_array($role, self::ROLES, true) ? $role : $default;

        Inertia::share('auth', [
            'user' => ['public_id' => 'preview', 'name' => 'Pratinjau Desain', 'email' => 'preview@skoolia.test', 'avatar' => null, 'email_verified_at' => null],
            'roles' => collect(self::ROLES)->map(fn (string $code) => ['code' => $code, 'label' => ucfirst($code), 'prefix' => ''])->all(),
            'activeRole' => $role,
            'permissions' => [
                'master_data.manage', 'student.view', 'academic.manage', 'attendance.manage', 'grade.manage',
                'report_card.review', 'report_card.validate', 'report_card.approve', 'portfolio.manage',
                'activity.manage', 'finance.manage', 'finance.view', 'ppdb.manage', 'ppdb.decide', 'cms.manage',
                'chat.use', 'child_condition.respond', 'child_condition.submit', 'user.manage', 'role.manage',
                'report.export', 'setting.manage',
            ],
        ]);
        Inertia::share('notifications', ['unread' => 3]);
    }

    /**
     * @return Collection<int, array{id: string, name: string, class: string, status: string, attendance: int}>
     */
    private function dummyStudents(): Collection
    {
        $names = ['Aisyah Putri', 'Bima Pratama', 'Citra Lestari', 'Dimas Saputra', 'Eka Wulandari', 'Fajar Nugroho',
            'Gita Permata', 'Hafiz Ramadhan', 'Indah Sari', 'Joko Susilo', 'Kirana Dewi', 'Laras Ayu', 'Muhammad Rizky',
            'Nadia Salsabila', 'Oki Firmansyah', 'Putri Maharani', 'Raka Aditya', 'Salma Azzahra', 'Taufik Hidayat',
            'Umar Faruq', 'Vina Oktaviani', 'Wahyu Kurniawan', 'Yusuf Maulana', 'Zahra Khairunnisa'];
        $statuses = ['HADIR', 'HADIR', 'HADIR', 'SAKIT', 'IZIN', 'ALPA', 'TERLAMBAT'];

        return collect($names)->map(fn (string $name, int $i) => [
            'id' => 'S'.str_pad((string) ($i + 1), 4, '0', STR_PAD_LEFT),
            'name' => $name,
            'class' => ['VII-A', 'VII-B', 'VIII-A'][$i % 3],
            'status' => $statuses[$i % count($statuses)],
            'attendance' => 80 + ($i * 7) % 21,
        ]);
    }
}
