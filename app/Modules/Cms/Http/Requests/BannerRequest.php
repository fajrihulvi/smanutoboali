<?php

namespace App\Modules\Cms\Http\Requests;

use App\Modules\Cms\Models\WebsiteBanner;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;

class BannerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage', WebsiteBanner::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:120'],
            'subtitle' => ['nullable', 'string', 'max:250'],
            'link_url' => ['nullable', 'string', 'max:255', 'regex:/^(https?:\/\/|\/)/'],
            'link_label' => ['nullable', 'string', 'max:40'],
            'position' => ['nullable', 'integer', 'min:0', 'max:999'],
            'is_active' => ['boolean'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'image' => [$this->route('banner') ? 'nullable' : 'required', 'file', 'max:5120'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ['link_url.regex' => 'Tautan harus diawali http://, https://, atau / (halaman di website ini).'];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'title' => 'judul',
            'subtitle' => 'subjudul',
            'link_url' => 'tautan',
            'link_label' => 'teks tombol',
            'position' => 'urutan',
            'starts_at' => 'mulai tayang',
            'ends_at' => 'selesai tayang',
            'image' => 'gambar',
        ];
    }

    /**
     * @return array{title: string, subtitle: string|null, link_url: string|null, link_label: string|null, position: int, is_active: bool, starts_at: CarbonImmutable|null, ends_at: CarbonImmutable|null, image: UploadedFile|null}
     */
    public function payload(): array
    {
        $text = fn (string $key) => trim($this->string($key)->value()) ?: null;
        $date = fn (string $key) => ($v = $text($key)) ? CarbonImmutable::parse($v) : null;
        $image = $this->file('image');

        return [
            'title' => trim($this->string('title')->value()),
            'subtitle' => $text('subtitle'),
            'link_url' => $text('link_url'),
            'link_label' => $text('link_label'),
            'position' => $this->integer('position'),
            'is_active' => $this->boolean('is_active'),
            'starts_at' => $date('starts_at'),
            'ends_at' => $date('ends_at'),
            'image' => $image instanceof UploadedFile ? $image : null,
        ];
    }
}
