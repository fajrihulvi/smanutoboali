<?php

namespace App\Modules\School\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Modules\Core\Actions\UpdateSystemSettingsAction;
use App\Modules\Core\Enums\SettingKey;
use App\Modules\Core\Services\FileUrlGenerator;
use App\Modules\Core\Services\SettingsService;
use App\Modules\School\Actions\UpdateSchoolProfileAction;
use App\Modules\School\Enums\SchoolLevel;
use App\Modules\School\Http\Requests\UpdateSchoolSettingsRequest;
use App\Modules\School\Models\School;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class SchoolSettingsController extends Controller
{
    public function edit(SettingsService $settings, FileUrlGenerator $urls): Response
    {
        $school = School::current();

        return Inertia::render('School::Operator/Settings', [
            'school' => [
                'name' => $school->name,
                'npsn' => $school->npsn,
                'level' => $school->level->value,
                'address' => $school->address,
                'phone' => $school->phone,
                'email' => $school->email,
                'principal_name' => $school->principal_name,
                'principal_nip' => $school->principal_nip,
                'logo_url' => $school->logo ? $urls->url($school->logo) : null,
            ],
            'system' => [
                'timezone' => $settings->get(SettingKey::Timezone),
                'upload_max_size_kb' => $settings->uploadMaxKb(),
            ],
            'levels' => array_map(
                fn (SchoolLevel $level) => ['value' => $level->value, 'label' => $level->label()],
                SchoolLevel::cases(),
            ),
            'timezones' => collect(SettingKey::timezones())
                ->map(fn (string $label, string $value) => ['value' => $value, 'label' => $label])
                ->values(),
            'uploadHardMaxKb' => (int) config('skoolia.upload.hard_max_kb'),
        ]);
    }

    public function update(
        UpdateSchoolSettingsRequest $request,
        UpdateSchoolProfileAction $updateProfile,
        UpdateSystemSettingsAction $updateSystem,
    ): RedirectResponse {
        DB::transaction(function () use ($request, $updateProfile, $updateSystem) {
            $updateProfile->handle($request->profile(), $request->file('logo'), $request->boolean('remove_logo'));
            $updateSystem->handle($request->systemSettings());
        });

        return to_route('operator.settings.edit')->with('success', 'Pengaturan sekolah berhasil disimpan.');
    }
}
