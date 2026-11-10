@extends('layouts.app')

@section('title', 'System activity')
@section('content')
    <div class="page-heading"><div><p class="eyebrow">University / Monitoring</p><h1>System activity</h1><p class="muted">Administrative actions and academic workflow events.</p></div></div>
    <section class="data-section">
        <form class="filter-toolbar" method="GET" action="{{ route('super-admin.activities.index') }}">
            <input name="q" type="search" value="{{ $filters['q'] ?? '' }}" placeholder="Action, record, or user" aria-label="Search activity">
            <select name="action" aria-label="Action"><option value="">All actions</option>@foreach ($actions as $action)<option value="{{ $action }}" @selected(($filters['action'] ?? '') === $action)>{{ str($action)->replace('.', ' ')->title() }}</option>@endforeach</select>
            <select name="actor_id" aria-label="Performed by"><option value="">All administrators</option>@foreach ($actors as $actor)<option value="{{ $actor->id }}" @selected((string) ($filters['actor_id'] ?? '') === (string) $actor->id)>{{ $actor->name }}</option>@endforeach</select>
            <label>From<input name="from" type="date" value="{{ $filters['from'] ?? '' }}"></label><label>To<input name="to" type="date" value="{{ $filters['to'] ?? '' }}"></label>
            <button class="button button-small" type="submit">Filter</button>
        </form>
        <div class="table-wrap"><table><thead><tr><th>Action</th><th>Performed by</th><th>Record</th><th>Details</th><th>Time</th></tr></thead><tbody>
            @forelse ($activities as $activity)<tr><td><strong>{{ str($activity->action)->replace('.', ' ')->title() }}</strong></td><td>{{ $activity->actor?->name ?? 'Former account' }}</td><td>{{ class_basename($activity->subject_type ?? '') }} #{{ $activity->subject_id ?? '—' }}</td><td>{{ collect($activity->details ?? [])->except(['password'])->map(fn ($value, $key) => $key.': '.(is_scalar($value) ? $value : json_encode($value)))->join(', ') ?: '—' }}</td><td>{{ $activity->created_at->format('Y-m-d H:i') }}</td></tr>@empty<tr><td class="empty-state" colspan="5">No activity matches these filters.</td></tr>@endforelse
        </tbody></table></div>
        <div class="pagination-wrap">{{ $activities->links() }}</div>
    </section>
@endsection