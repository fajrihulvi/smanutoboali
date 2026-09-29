<?php

namespace App\Modules\Cms\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Modules\Cms\Actions\SaveWebsiteSettingsAction;
use App\Modules\Cms\Models\WebsitePage;
use App\Modules\Cms\Services\WebsiteSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SettingController extends Controller
{
    public function edit(WebsiteSettings $settings): Response
    {
        $this->authorize('manage', WebsitePage::class);

        return Inertia::render('Cms::Operator/Settings', ['settings' => $settings->all()]);
    }

    public function update(Request $request, SaveWebsiteSettingsAction $save): RedirectResponse
    {
        $this->authorize('manage', WebsitePage::class);

        $url = ['nullable', 'string', 'max:255', 'url:http,https'];
        $data = $request->validate([
            'tagline' => ['nullable', 'string', 'max:150'],
            'about' => ['nullable', 'string', 'max:500'],
            'address' => ['nullable', 'string', 'max:300'],
            'phone' => ['nullable', 'string', 'max:30'],
            'whatsapp' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+\s-]+$/'],
            'email' => ['nullable', 'email', 'max:120'],
            'opening_hours' => ['nullable', 'string', 'max:150'],
            'map_embed_url' => ['nullable', 'string', 'max:1000', 'url:https', 'starts_with:https://www.google.com/maps/embed,https://maps.google.com/'],
            'facebook' => $url,
            'instagram' => $url,
            'youtube' => $url,
            'tiktok' => $url,
            'ppdb_cta_title' => ['nullable', 'string', 'max:100'],
            'ppdb_cta_text' => ['nullable', 'string', 'max:300'],
            'footer_text' => ['nullable', 'string', 'max:300'],
        ], [
            'map_embed_url.starts_with' => 'Gunakan tautan "Sematkan peta" dari Google Maps (diawali https://www.google.com/maps/embed).',
            'whatsapp.regex' => 'Nomor WhatsApp hanya berisi angka.',
        ], [
            'about' => 'deskripsi singkat',
            'address' => 'alamat',
            'phone' => 'telepon',
            'opening_hours' => 'jam layanan',
            'map_embed_url' => 'tautan peta',
            'ppdb_cta_title' => 'judul ajakan PPDB',
            'ppdb_cta_text' => 'teks ajakan PPDB',
            'footer_text' => 'teks footer',
        ]);

        /** @var array<string, string|null> $data */
        $save->handle($data);

        return back()->with('success', 'Pengaturan website disimpan.');
    }
}
