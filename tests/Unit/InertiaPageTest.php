<?php

use App\Support\InertiaPage;

it('memetakan nama halaman modul ke Modules/<Module>/Pages', function () {
    expect(InertiaPage::sourcePath('Attendance::Teacher/Index'))
        ->toBe('resources/js/Modules/Attendance/Pages/Teacher/Index.tsx');
});

it('memetakan halaman bawaan starter kit ke folder pages', function () {
    expect(InertiaPage::sourcePath('settings/profile'))
        ->toBe('resources/js/pages/settings/profile.tsx');
});
