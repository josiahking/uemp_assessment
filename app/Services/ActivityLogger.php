<?php

namespace App\Services;

use App\Models\SystemActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class ActivityLogger
{
    public function record(string $action, ?Model $subject = null, array $details = []): void
    {
        SystemActivity::create([
            'actor_id' => Auth::id(),
            'action' => $action,
            'subject_type' => $subject ? $subject::class : null,
            'subject_id' => $subject?->getKey(),
            'details' => $details,
        ]);
    }
}
