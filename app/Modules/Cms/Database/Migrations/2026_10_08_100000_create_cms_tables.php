<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('website_pages', function (Blueprint $table) {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->string('type', 12);
            $table->string('slug', 120)->unique();
            $table->string('title', 150);
            $table->longText('content')->nullable();
            $table->string('excerpt', 300)->nullable();
            $table->foreignId('featured_image_id')->nullable()->constrained('files')->nullOnDelete();
            $table->string('seo_title', 70)->nullable();
            $table->string('seo_description', 170)->nullable();
            $table->string('status', 10)->default('DRAFT');
            $table->timestamp('publish_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->boolean('show_in_menu')->default(true);
            $table->unsignedSmallInteger('position')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('website_categories', function (Blueprint $table) {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->string('name', 60);
            $table->string('slug', 80)->unique();
            $table->timestamps();
        });

        Schema::create('website_posts', function (Blueprint $table) {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->string('type', 10);
            $table->foreignId('category_id')->nullable()->constrained('website_categories')->nullOnDelete();
            $table->string('slug', 160)->unique();
            $table->string('title', 150);
            $table->string('excerpt', 300)->nullable();
            $table->longText('content')->nullable();
            $table->foreignId('featured_image_id')->nullable()->constrained('files')->nullOnDelete();
            $table->string('seo_title', 70)->nullable();
            $table->string('seo_description', 170)->nullable();
            $table->string('status', 10)->default('DRAFT');
            $table->timestamp('publish_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->boolean('is_featured')->default(false);
            // Agenda.
            $table->dateTime('event_starts_at')->nullable();
            $table->dateTime('event_ends_at')->nullable();
            $table->string('event_location', 150)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['type', 'status', 'publish_at']);
        });

        Schema::create('website_galleries', function (Blueprint $table) {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->string('slug', 160)->unique();
            $table->string('title', 150);
            $table->text('description')->nullable();
            $table->date('date')->nullable();
            $table->string('status', 10)->default('DRAFT');
            $table->timestamp('publish_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('website_gallery_photos', function (Blueprint $table) {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('website_gallery_id')->constrained()->cascadeOnDelete();
            $table->foreignId('file_id')->constrained('files')->cascadeOnDelete();
            $table->string('caption', 200)->nullable();
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('website_banners', function (Blueprint $table) {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->string('title', 120);
            $table->string('subtitle', 200)->nullable();
            $table->foreignId('image_file_id')->nullable()->constrained('files')->nullOnDelete();
            $table->string('link_url', 255)->nullable();
            $table->string('link_label', 40)->nullable();
            $table->unsignedSmallInteger('position')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();
        });

        Schema::create('website_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 60)->unique();
            $table->json('value')->nullable();
            $table->timestamps();
        });

        Schema::table('teachers', function (Blueprint $table) {
            $table->boolean('show_on_website')->default(false)->after('is_active');
            $table->string('website_title', 100)->nullable()->after('show_on_website');
        });

        // Halaman sistem (slug tetap untuk menu website).
        $now = now();
        $pages = [
            ['PROFIL', 'profil', 'Profil Sekolah'],
            ['VISI_MISI', 'visi-misi', 'Visi & Misi'],
            ['SAMBUTAN', 'sambutan', 'Sambutan Kepala Sekolah'],
            ['STRUKTUR', 'struktur-organisasi', 'Struktur Organisasi'],
            ['FASILITAS', 'fasilitas', 'Fasilitas'],
            ['KONTAK', 'kontak', 'Kontak'],
        ];

        foreach ($pages as $i => [$type, $slug, $title]) {
            DB::table('website_pages')->insert([
                'public_id' => (string) Str::ulid(), 'type' => $type, 'slug' => $slug, 'title' => $title,
                'status' => 'DRAFT', 'show_in_menu' => true, 'position' => $i + 1, 'created_at' => $now, 'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('teachers', fn (Blueprint $table) => $table->dropColumn(['show_on_website', 'website_title']));
        Schema::dropIfExists('website_settings');
        Schema::dropIfExists('website_banners');
        Schema::dropIfExists('website_gallery_photos');
        Schema::dropIfExists('website_galleries');
        Schema::dropIfExists('website_posts');
        Schema::dropIfExists('website_categories');
        Schema::dropIfExists('website_pages');
    }
};
