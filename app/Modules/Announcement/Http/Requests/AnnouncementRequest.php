<?php

namespace App\Modules\Announcement\Http\Requests;

use App\Modules\Academic\Models\Classroom;
use App\Modules\Announcement\Enums\AnnouncementAudience;
use App\Modules\Announcement\Models\Announcement;
use App\Modules\Announcement\Services\AnnouncementAudienceResolver;
use App\Modules\Core\Enums\Role;
use App\Support\ActivePeriod;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;

class AnnouncementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage', Announcement::class) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $readerRoles = array_map(fn (Role $r) => $r->value, AnnouncementAudienceResolver::READER_ROLES);

        return [
            'title' => ['required', 'string', 'max:150'],
            'body' => ['required', 'string', 'max:10000'],
            'audience' => ['required', Rule::enum(AnnouncementAudience::class)],
            'roles' => ['array', Rule::requiredIf($this->input('audience') === AnnouncementAudience::Roles->value)],
            'roles.*' => ['string', 'distinct', Rule::in($readerRoles)],
            'classrooms' => ['array', Rule::requiredIf($this->input('audience') === AnnouncementAudience::Classrooms->value)],
            'classrooms.*' => ['string', 'distinct'],
            'pinned' => ['boolean'],
            'publish' => ['required', Rule::in(['draft', 'now', 'schedule'])],
            'publish_at' => ['nullable', 'required_if:publish,schedule', 'date', 'after:now'],
            'files' => ['nullable', 'array'],
            'files.*' => ['file', 'max:10240'],
            'remove_files' => ['nullable', 'array'],
            'remove_files.*' => ['string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'roles.required' => 'Pilih minimal satu peran.',
            'classrooms.required' => 'Pilih minimal satu rombel.',
            'publish_at.required_if' => 'Isi waktu tayang.',
            'publish_at.after' => 'Waktu tayang harus di masa depan.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'title' => 'judul',
            'body' => 'isi pengumuman',
            'audience' => 'target',
            'roles' => 'peran',
            'classrooms' => 'rombel',
            'publish_at' => 'waktu tayang',
            'files' => 'lampiran',
            'files.*' => 'lampiran',
        ];
    }

    /**
     * @return array{title: string, body: string, audience: AnnouncementAudience, roles: list<string>, classroom_ids: list<int>, pinned: bool, publish_at: CarbonImmutable|null}
     */
    public function payload(?Announcement $existing = null): array
    {
        $publish = $this->string('publish')->value();
        $publishAt = match ($publish) {
            'now' => $existing?->isPublished() ? $existing->publish_at : CarbonImmutable::now(),
            'schedule' => CarbonImmutable::parse($this->string('publish_at')->value()),
            default => null,
        };

        /** @var list<string> $roles */
        $roles = array_values(array_map('strval', (array) $this->input('roles', [])));
        $classroomIds = Classroom::query()
            ->where('academic_year_id', app(ActivePeriod::class)->academicYearId() ?? 0)
            ->whereIn('public_id', (array) $this->input('classrooms', []))
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        return [
            'title' => trim($this->string('title')->value()),
            'body' => trim($this->string('body')->value()),
            'audience' => AnnouncementAudience::from($this->string('audience')->value()),
            'roles' => $roles,
            'classroom_ids' => $classroomIds,
            'pinned' => $this->boolean('pinned'),
            'publish_at' => $publishAt,
        ];
    }

    /**
     * @return list<UploadedFile>
     */
    public function uploads(): array
    {
        $files = $this->file('files');

        return is_array($files) ? array_values($files) : [];
    }

    /**
     * @return list<string>
     */
    public function removeFiles(): array
    {
        return array_values(array_map('strval', (array) $this->input('remove_files', [])));
    }
}
