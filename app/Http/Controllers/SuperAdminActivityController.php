<?php

namespace App\Http\Controllers;

use App\Models\SystemActivity;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SuperAdminActivityController extends Controller
{
    public function activities(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'action' => ['nullable', 'string', 'max:100'],
            'actor_id' => ['nullable', 'integer', 'exists:users,id'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);
        $query = SystemActivity::with('actor')->latest('created_at');

        if (! empty($filters['q'])) {
            $search = mb_strtolower($filters['q']);
            $query->where(fn ($activities) => $activities
                ->whereRaw('LOWER(action) LIKE ?', ['%'.$search.'%'])
                ->orWhereRaw('LOWER(subject_type) LIKE ?', ['%'.$search.'%'])
                ->orWhereHas('actor', fn ($actors) => $actors->whereRaw('LOWER(name) LIKE ?', ['%'.$search.'%'])));
        }
        if (! empty($filters['action'])) {
            $query->where('action', $filters['action']);
        }
        if (! empty($filters['actor_id'])) {
            $query->where('actor_id', $filters['actor_id']);
        }
        if (! empty($filters['from'])) {
            $query->whereDate('created_at', '>=', $filters['from']);
        }
        if (! empty($filters['to'])) {
            $query->whereDate('created_at', '<=', $filters['to']);
        }

        return view('super-admin.activities', [
            'activities' => $query->paginate(20)->withQueryString(),
            'filters' => $filters,
            'actions' => SystemActivity::query()->distinct()->orderBy('action')->pluck('action'),
            'actors' => User::whereIn('id', SystemActivity::query()->select('actor_id')->whereNotNull('actor_id')->distinct())
                ->orderBy('name')->get(['id', 'name']),
        ]);
    }
}
