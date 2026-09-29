<?php

namespace App\Modules\Cms\Http\Requests;

use App\Modules\Cms\Enums\ContentStatus;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;

/**
 * Aturan bersama konten website: judul, slug, SEO, status & jadwal terbit, gambar utama.
 */
abstract class ContentRequest extends FormRequest
{
    public const SLUG_PATTERN = '/^[a-z0-9]+(?:-[a-z0-9]+)*$/';

    abstract protected function table(): string;

    /**
     * Model yang sedang diubah (null saat membuat).
     */
    abstract protected function current(): ?Model;

    /**
     * @return array<string, mixed>
     */
    protected function contentRules(int $slugMax = 120): array
    {
        return [
            'title' => ['required', 'string', 'max:150'],
            'slug' => ['nullable', 'string', "max:{$slugMax}", 'regex:'.self::SLUG_PATTERN, Rule::unique($this->table(), 'slug')->ignore($this->current()?->getKey())],
            'content' => ['nullable', 'string', 'max:300000'],
            'excerpt' => ['nullable', 'string', 'max:300'],
            'seo_title' => ['nullable', 'string', 'max:70'],
            'seo_description' => ['nullable', 'string', 'max:160'],
            'status' => ['required', Rule::enum(ContentStatus::class)],
            'publish_at' => ['nullable', 'date'],
            'image' => ['nullable', 'file', 'max:5120'],
            'remove_image' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'slug.regex' => 'Slug hanya boleh huruf kecil, angka, dan tanda hubung.',
            'slug.unique' => 'Slug sudah dipakai konten lain.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'title' => 'judul',
            'content' => 'isi',
            'excerpt' => 'ringkasan',
            'seo_title' => 'judul SEO',
            'seo_description' => 'deskripsi SEO',
            'publish_at' => 'jadwal terbit',
            'image' => 'gambar utama',
        ];
    }

    protected function nullableString(string $key): ?string
    {
        $value = trim($this->string($key)->value());

        return $value === '' ? null : $value;
    }

    protected function nullableDate(string $key): ?CarbonImmutable
    {
        $value = $this->nullableString($key);

        return $value === null ? null : CarbonImmutable::parse($value);
    }

    protected function status(): ContentStatus
    {
        return ContentStatus::from($this->string('status')->value());
    }

    protected function upload(string $key): ?UploadedFile
    {
        $file = $this->file($key);

        return $file instanceof UploadedFile ? $file : null;
    }
}
