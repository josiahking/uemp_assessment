<?php

namespace App\Services;

use App\Models\CourseRegistration;
use App\Models\Result;
use App\Models\ResultEvent;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DepartmentReviewService
{
    public function __construct(private readonly ActivityLogger $activityLogger) {}

    public function decideRegistration(CourseRegistration $registration, string $status): void
    {
        abort_unless($registration->status === 'pending', 409);
        DB::transaction(function () use ($registration, $status) {
            $registration->update(['status' => $status]);
            $this->activityLogger->record('course_registration.'.$status, $registration);
        });
    }

    public function reviewResult(Result $result, User $reviewer, string $status, ?string $reviewNote): void
    {
        abort_unless($result->status === 'submitted', 409);
        abort_if($result->submitted_by === $reviewer->id, 403);
        DB::transaction(function () use ($result, $reviewer, $status, $reviewNote) {
            $result->update(['status' => $status, 'review_note' => $reviewNote, 'reviewed_by' => $reviewer->id, 'reviewed_at' => now()]);
            ResultEvent::create([
                'result_id' => $result->id,
                'actor_id' => $reviewer->id,
                'event' => $status,
                'from_status' => 'submitted',
                'to_status' => $status,
                'metadata' => ['review_note' => $reviewNote],
            ]);
            $this->activityLogger->record('result.'.$status, $result, ['review_note' => $reviewNote]);
        });
    }
}
