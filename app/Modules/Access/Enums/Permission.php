<?php

namespace App\Modules\Access\Enums;

use App\Modules\Core\Enums\Role;

/**
 * Semua permission Skoolia (format modul.aksi) — matriks VIBE_PLANNING §4.2.
 * Scope "◐ terbatas" dijaga Policy/ownership, bukan oleh permission.
 */
enum Permission: string
{
    case MasterDataManage = 'master_data.manage';
    case StudentView = 'student.view';
    case AcademicManage = 'academic.manage';
    case AttendanceManage = 'attendance.manage';
    case GradeManage = 'grade.manage';
    case GradeView = 'grade.view';
    case ReportCardReview = 'report_card.review';
    case ReportCardValidate = 'report_card.validate';
    case ReportCardApprove = 'report_card.approve';
    case PortfolioManage = 'portfolio.manage';
    case ActivityManage = 'activity.manage';
    case FinanceManage = 'finance.manage';
    case FinanceView = 'finance.view';
    case PpdbManage = 'ppdb.manage';
    case PpdbDecide = 'ppdb.decide';
    case CmsManage = 'cms.manage';
    case ChatUse = 'chat.use';
    case ChildConditionRespond = 'child_condition.respond';
    case ChildConditionSubmit = 'child_condition.submit';
    case AnnouncementManage = 'announcement.manage';
    case UserManage = 'user.manage';
    case RoleManage = 'role.manage';
    case ReportExport = 'report.export';
    case SettingManage = 'setting.manage';
    case AuditLogView = 'audit_log.view';
    case GuestBookManage = 'guest_book.manage';
    case GuestBookView = 'guest_book.view';

    public function label(): string
    {
        return match ($this) {
            self::MasterDataManage => 'Kelola master data',
            self::StudentView => 'Lihat data siswa',
            self::AcademicManage => 'Kelola akademik',
            self::AttendanceManage => 'Kelola presensi',
            self::GradeManage => 'Kelola nilai',
            self::GradeView => 'Lihat nilai',
            self::ReportCardReview => 'Review raport (wali kelas)',
            self::ReportCardValidate => 'Validasi raport',
            self::ReportCardApprove => 'Setujui raport',
            self::PortfolioManage => 'Kelola portofolio',
            self::ActivityManage => 'Kelola kegiatan',
            self::FinanceManage => 'Kelola keuangan',
            self::FinanceView => 'Lihat keuangan',
            self::PpdbManage => 'Kelola PPDB',
            self::PpdbDecide => 'Putuskan hasil PPDB',
            self::CmsManage => 'Kelola website',
            self::ChatUse => 'Gunakan chat',
            self::ChildConditionRespond => 'Tanggapi kondisi anak',
            self::ChildConditionSubmit => 'Laporkan kondisi anak',
            self::AnnouncementManage => 'Kelola pengumuman',
            self::UserManage => 'Kelola pengguna',
            self::RoleManage => 'Kelola role & permission',
            self::ReportExport => 'Ekspor laporan',
            self::SettingManage => 'Kelola pengaturan sekolah',
            self::AuditLogView => 'Lihat audit log',
            self::GuestBookManage => 'Kelola buku tamu',
            self::GuestBookView => 'Lihat buku tamu',
        };
    }

    /**
     * Kelompok modul untuk matriks checkbox (S06).
     */
    public function group(): string
    {
        return match ($this) {
            self::MasterDataManage, self::StudentView => 'Master Data',
            self::AcademicManage, self::AttendanceManage, self::GradeManage, self::GradeView => 'Akademik',
            self::ReportCardReview, self::ReportCardValidate, self::ReportCardApprove => 'Raport',
            self::PortfolioManage, self::ActivityManage => 'Portofolio & Kegiatan',
            self::FinanceManage, self::FinanceView => 'Keuangan',
            self::PpdbManage, self::PpdbDecide => 'PPDB',
            self::CmsManage => 'Website',
            self::ChatUse, self::ChildConditionRespond, self::ChildConditionSubmit, self::AnnouncementManage => 'Komunikasi',
            self::UserManage, self::RoleManage, self::SettingManage, self::AuditLogView => 'Sistem',
            self::ReportExport => 'Laporan',
            self::GuestBookManage, self::GuestBookView => 'Buku Tamu',
        };
    }

    /**
     * Role yang mendapat permission ini secara default (matriks §4.2).
     *
     * @return list<Role>
     */
    public function defaultRoles(): array
    {
        return match ($this) {
            self::MasterDataManage,
            self::ReportCardValidate,
            self::FinanceManage,
            self::PpdbManage,
            self::CmsManage,
            self::UserManage,
            self::RoleManage,
            self::SettingManage,
            self::GradeView,
            self::GuestBookManage => [Role::Operator],

            self::StudentView,
            self::ChatUse => [Role::Principal, Role::Teacher, Role::Operator, Role::Guardian],

            self::AcademicManage,
            self::AttendanceManage,
            self::PortfolioManage,
            self::ActivityManage => [Role::Teacher, Role::Operator],

            self::GradeManage,
            self::ReportCardReview,
            self::ChildConditionRespond => [Role::Teacher],

            self::ReportCardApprove,
            self::PpdbDecide,
            self::GuestBookView => [Role::Principal],

            self::FinanceView => [Role::Principal, Role::Operator, Role::Guardian],
            self::ReportExport => [Role::Principal, Role::Teacher, Role::Operator],
            self::AuditLogView,
            self::AnnouncementManage => [Role::Principal, Role::Operator],
            self::ChildConditionSubmit => [Role::Guardian],
        };
    }

    /**
     * Permission default untuk satu role.
     *
     * @return list<string>
     */
    public static function defaultsFor(Role $role): array
    {
        $names = [];

        foreach (self::cases() as $permission) {
            if (in_array($role, $permission->defaultRoles(), true)) {
                $names[] = $permission->value;
            }
        }

        return $names;
    }
}
