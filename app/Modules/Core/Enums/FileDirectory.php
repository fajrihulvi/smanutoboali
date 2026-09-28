<?php

namespace App\Modules\Core\Enums;

/**
 * Folder penyimpanan file (SKOOLIA_SYSTEM_PLANNING §15).
 */
enum FileDirectory: string
{
    case Students = 'students';
    case Teachers = 'teachers';
    case Portfolios = 'portfolios';
    case Activities = 'activities';
    case Ppdb = 'ppdb';
    case Payments = 'payments';
    case Website = 'website';
    case Chat = 'chat';
    case ChildConditions = 'child-conditions';
    case Materials = 'materials';
    case ReportCards = 'report-cards';
    case Settings = 'settings';
    case Misc = 'misc';
}
