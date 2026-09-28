<?php

namespace App\Modules\Portfolio\Http\Requests;

use App\Modules\Core\Services\SettingsService;
use App\Modules\People\Models\Student;
use App\Modules\Portfolio\Enums\PortfolioVisibility;
use App\Modules\Portfolio\Models\Portfolio;
use App\Modules\Portfolio\Models\PortfolioCategory;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class PortfolioRequest extends FormRequest
{
    public function authorize(): bool
    {
        $portfolio = $this->route('portfolio');

        return $portfolio instanceof Portfolio
            ? ($this->user()?->can('update', $portfolio) ?? false)
            : ($this->user()?->can('manageAny', Portfolio::class) ?? false);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'category' => ['required', 'string', Rule::exists(PortfolioCategory::class, 'code')],
            'title' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:2000'],
            'date' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'visibility' => ['required', Rule::enum(PortfolioVisibility::class)],
            'students' => ['required', 'array', 'min:1', 'max:60'],
            'students.*' => ['string', 'distinct'],
            'files' => ['array', 'max:'.Portfolio::MAX_FILES],
            'files.*' => ['file', 'max:'.app(SettingsService::class)->uploadMaxKb()],
            'remove_files' => ['array'],
            'remove_files.*' => ['string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'category' => 'kategori', 'title' => 'judul', 'description' => 'deskripsi', 'date' => 'tanggal',
            'visibility' => 'status', 'students' => 'siswa', 'files' => 'file', 'files.*' => 'file',
        ];
    }

    /**
     * Semua siswa terpilih harus terlihat oleh pengguna (guru: yang ia ajar/walikan).
     *
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->has('students') || $this->user() === null) {
                    return;
                }

                if ($this->studentIds() === [] || count($this->studentIds()) !== count($this->array('students'))) {
                    $validator->errors()->add('students', 'Pilih siswa yang Anda ajar atau walikan.');
                }
            },
        ];
    }

    /**
     * @return list<int>
     */
    public function studentIds(): array
    {
        /** @var list<int> */
        return once(fn () => Student::query()
            ->visibleTo($this->user())
            ->whereIn('public_id', $this->array('students'))
            ->pluck('students.id')
            ->all());
    }

    /**
     * @return array{category_id: int, title: string, description: string|null, date: CarbonImmutable, visibility: PortfolioVisibility, student_ids: list<int>}
     */
    public function payload(): array
    {
        $description = $this->string('description')->trim()->value();

        return [
            'category_id' => (int) PortfolioCategory::query()->where('code', $this->string('category')->value())->value('id'),
            'title' => $this->string('title')->trim()->value(),
            'description' => $description !== '' ? $description : null,
            'date' => CarbonImmutable::parse($this->string('date')->value()),
            'visibility' => PortfolioVisibility::from($this->string('visibility')->value()),
            'student_ids' => $this->studentIds(),
        ];
    }

    /**
     * @return list<UploadedFile>
     */
    public function uploads(): array
    {
        return array_values(array_filter((array) $this->file('files', []), fn ($file) => $file instanceof UploadedFile));
    }

    /**
     * @return list<string>
     */
    public function removedFiles(): array
    {
        return array_values(array_map('strval', $this->array('remove_files')));
    }
}
