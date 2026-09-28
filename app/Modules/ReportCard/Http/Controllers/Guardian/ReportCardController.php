<?php

namespace App\Modules\ReportCard\Http\Controllers\Guardian;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\People\Services\GuardianChildren;
use App\Modules\ReportCard\Enums\ReportCardStatus;
use App\Modules\ReportCard\Models\ReportCard;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Raport terbit milik anak terpilih (hanya PUBLISHED).
 */
class ReportCardController extends Controller
{
    public function index(Request $request, GuardianChildren $guardianChildren): Response
    {
        /** @var User $user */
        $user = $request->user();
        $children = $guardianChildren->of($user);
        $child = $guardianChildren->selected($children, $request->string(GuardianChildren::QUERY_KEY)->value());

        $cards = $child
            ? ReportCard::query()
                ->where('student_id', $child->id)
                ->where('status', ReportCardStatus::Published)
                ->with(['classroom:id,name', 'semester.academicYear'])
                ->latest('published_at')
                ->get()
            : collect();

        return Inertia::render('ReportCard::Guardian/Index', [
            ...$guardianChildren->switcherProps($children, $child),
            'child' => $child ? ['public_id' => $child->public_id, 'name' => $child->name] : null,
            'cards' => $cards->map(fn (ReportCard $card) => [
                'public_id' => $card->public_id,
                'period' => "{$card->semester->label()} TA {$card->semester->academicYear->name}",
                'classroom' => $card->classroom->name,
                'published_at' => $card->published_at?->toIso8601String(),
            ])->values(),
        ]);
    }
}
