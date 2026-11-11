<?php

namespace App\Services;

use App\Models\CourseRegistration;
use App\Models\Result;
use App\Models\ResultEvent;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class LecturerResultSubmission
{
    public function __construct(private readonly AcademicCalculator $calculator, private readonly ActivityLogger $activityLogger) {}

    public function submit(CourseRegistration $registration, User $lecturer, array $scores): Result
    {
        $this->assertSubmittable($registration);
        $total = round((float) $scores['ca_score'] + (float) $scores['exam_score'], 2);
        $result = $registration->result ?? new Result(['course_registration_id' => $registration->id]);
        $previousStatus = $result->status;

        return DB::transaction(function () use ($result, $previousStatus, $scores, $lecturer, $total) {
            $result->fill([
                'submitted_by' => $lecturer->id,
                'ca_score' => $scores['ca_score'],
                'exam_score' => $scores['exam_score'],
                'total_score' => $total,
                'grade' => $this->calculator->gradeFor($total),
                'status' => 'submitted',
                'review_note' => null,
                'submitted_at' => now(),
                'reviewed_by' => null,
                'reviewed_at' => null,
            ])->save();
            ResultEvent::create([
                'result_id' => $result->id,
                'actor_id' => $lecturer->id,
                'event' => 'submitted',
                'from_status' => $previousStatus,
                'to_status' => 'submitted',
                'metadata' => ['ca_score' => $scores['ca_score'], 'exam_score' => $scores['exam_score']],
            ]);
            $this->activityLogger->record('result.submitted', $result, ['ca_score' => $scores['ca_score'], 'exam_score' => $scores['exam_score'], 'total_score' => $total]);

            return $result;
        });
    }

    private function assertSubmittable(CourseRegistration $registration): void
    {
        $result = $registration->result;

        abort_if($result && ! in_array($result->status, ['draft', 'returned'], true), 409);
    }
}
