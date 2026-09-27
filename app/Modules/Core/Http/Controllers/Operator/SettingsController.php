<?php

namespace App\Modules\Core\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Modules\Core\Actions\UpdateSchoolSettingsAction;
use App\Modules\Core\Enums\SettingKey;
use App\Modules\Core\Http\Requests\UpdateSchoolSettingsRequest;
use App\Modules\Core\Services\SchoolProfile;
use App\Modules\Core\Services\SettingsService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    public function edit(SettingsService $settings, SchoolProfile $school): Response
    {
        return Inertia::render('Core::Operator/Settings', [
            'settings' => [
                'school_name' => $settings->get(SettingKey::SchoolName),
                'school_address' => $settings->get(SettingKey::SchoolAddress),
                'school_phone' => $settings->get(SettingKey::SchoolPhone),
                'school_email' => $settings->get(SettingKey::SchoolEmail),
                'timezone' => $settings->get(SettingKey::Timezone),
                'upload_max_size_kb' => $settings->uploadMaxKb(),
                'logo_url' => $school->logoUrl(),
            ],
            'timezones' => collect(SettingKey::timezones())
                ->map(fn (string $label, string $value) => ['value' => $value, 'label' => $label])
                ->values(),
            'uploadHardMaxKb' => (int) config('skoolia.upload.hard_max_kb'),
        ]);
    }

    public function update(UpdateSchoolSettingsRequest $request, UpdateSchoolSettingsAction $update): RedirectResponse
    {
        $update->handle(
            $request->settings(),
            $request->file('logo'),
            $request->boolean('remove_logo'),
        );

        return to_route('operator.settings.edit')->with('success', 'Pengaturan sekolah berhasil disimpan.');
    }
}
