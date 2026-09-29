<?php

namespace App\Modules\Cms\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Modules\People\Models\Teacher;
use Illuminate\View\View;

/**
 * Guru & tenaga kependidikan yang ditandai tampil di website (data dari modul People).
 */
class TeacherController extends Controller
{
    public function __invoke(): View
    {
        return view('public.teachers', [
            'teachers' => Teacher::query()->where('is_active', true)->where('show_on_website', true)
                ->with('photo')->orderBy('name')->get(),
            'seo' => ['title' => 'Guru & Tenaga Kependidikan', 'description' => 'Guru dan tenaga kependidikan sekolah kami.'],
        ]);
    }
}
