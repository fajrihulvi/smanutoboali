<?php

namespace App\Modules\Ppdb\Models;

use App\Models\User;
use App\Modules\People\Enums\Gender;
use App\Modules\People\Enums\Religion;
use App\Modules\Ppdb\Enums\ApplicantStatus;
use App\Modules\Ppdb\Enums\SelectionProposal;
use App\Support\Concerns\HasPublicId;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Satu pendaftaran calon siswa.
 *
 * @property int $id
 * @property string $public_id
 * @property string|null $registration_number
 * @property int $ppdb_period_id
 * @property int $ppdb_track_id
 * @property int $user_id
 * @property ApplicantStatus $status
 * @property string|null $name
 * @property string|null $nik
 * @property string|null $nisn
 * @property Gender|null $gender
 * @property string|null $birth_place
 * @property CarbonImmutable|null $birth_date
 * @property Religion|null $religion
 * @property string|null $address
 * @property string|null $previous_school
 * @property string|null $revision_note
 * @property string|null $score
 * @property string|null $selection_note
 * @property SelectionProposal|null $proposal
 * @property int|null $decided_by
 * @property CarbonImmutable|null $result_notified_at
 * @property CarbonImmutable|null $reregistration_submitted_at
 * @property CarbonImmutable|null $reregistered_at
 * @property int|null $reregistered_by
 * @property int|null $student_id
 * @property CarbonImmutable|null $submitted_at
 * @property CarbonImmutable|null $verified_at
 * @property CarbonImmutable|null $decided_at
 * @property CarbonImmutable $created_at
 * @property-read PpdbPeriod $period
 * @property-read PpdbTrack $track
 * @property-read User $user
 * @property-read Collection<int, PpdbApplicantGuardian> $guardians
 * @property-read Collection<int, PpdbDocument> $documents
 */
class PpdbApplicant extends Model
{
    use HasPublicId, SoftDeletes;

    protected $fillable = [
        'registration_number', 'ppdb_period_id', 'ppdb_track_id', 'user_id', 'status',
        'name', 'nik', 'nisn', 'gender', 'birth_place', 'birth_date', 'religion', 'address', 'previous_school',
        'revision_note', 'score', 'selection_note', 'proposal', 'student_id', 'submitted_at', 'verified_at',
        'decided_at', 'decided_by', 'result_notified_at', 'reregistration_submitted_at', 'reregistered_at', 'reregistered_by',
    ];

    protected $hidden = ['id', 'ppdb_period_id', 'ppdb_track_id', 'user_id', 'student_id', 'decided_by', 'reregistered_by'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ApplicantStatus::class,
            'gender' => Gender::class,
            'religion' => Religion::class,
            'birth_date' => 'immutable_date',
            'submitted_at' => 'immutable_datetime',
            'verified_at' => 'immutable_datetime',
            'decided_at' => 'immutable_datetime',
            'proposal' => SelectionProposal::class,
            'result_notified_at' => 'immutable_datetime',
            'reregistration_submitted_at' => 'immutable_datetime',
            'reregistered_at' => 'immutable_datetime',
        ];
    }

    /**
     * Status yang boleh dilihat pendaftar: keputusan (diterima/tidak) baru terlihat
     * setelah hasil periode diumumkan; sebelumnya tetap "Seleksi".
     */
    public function visibleStatus(): ApplicantStatus
    {
        $decided = in_array($this->status, [ApplicantStatus::Accepted, ApplicantStatus::Rejected], true);

        return $decided && $this->period->results_published_at === null ? ApplicantStatus::InSelection : $this->status;
    }

    public function resultPublished(): bool
    {
        return $this->period->results_published_at !== null && $this->decided_at !== null;
    }

    /**
     * @return BelongsTo<PpdbPeriod, $this>
     */
    public function period(): BelongsTo
    {
        return $this->belongsTo(PpdbPeriod::class, 'ppdb_period_id')->withTrashed();
    }

    /**
     * @return BelongsTo<PpdbTrack, $this>
     */
    public function track(): BelongsTo
    {
        return $this->belongsTo(PpdbTrack::class, 'ppdb_track_id')->withTrashed();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<PpdbApplicantGuardian, $this>
     */
    public function guardians(): HasMany
    {
        return $this->hasMany(PpdbApplicantGuardian::class)->orderByDesc('is_primary')->orderBy('id');
    }

    /**
     * @return HasMany<PpdbDocument, $this>
     */
    public function documents(): HasMany
    {
        return $this->hasMany(PpdbDocument::class);
    }
}
