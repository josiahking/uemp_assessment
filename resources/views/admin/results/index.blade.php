@extends('layouts.app')

@section('title', 'Results')
@section('content')
    <div class="page-heading"><div><p class="eyebrow">Academic review</p><h1>Results</h1><p class="muted">Review lecturer submissions before they are released to students.</p></div></div>
    <section class="data-section"><div class="table-toolbar"><div><h2>Result submissions</h2><p>{{ $results->where('status', 'submitted')->count() }} awaiting review</p></div></div>
        <div class="table-wrap"><table><thead><tr><th>Student</th><th>Course</th><th>CA / 40</th><th>Exam / 60</th><th>Total</th><th>Grade</th><th>Status &amp; review</th></tr></thead><tbody>
            @forelse ($results as $result)<tr><td><strong>{{ $result->registration->student->name }}</strong></td><td><strong>{{ $result->registration->course->code }}</strong><small>{{ $result->submitter?->name }}</small></td><td>{{ $result->ca_score }}</td><td>{{ $result->exam_score }}</td><td>{{ $result->total_score }}</td><td><strong>{{ $result->grade }}</strong></td><td><span class="status status-{{ $result->status }}">{{ ucfirst($result->status) }}</span>@if ($result->status === 'submitted')<form class="review-form" method="POST" action="{{ route('admin.results.review', $result) }}">@csrf @method('PATCH')<select name="status" aria-label="Decision for {{ $result->registration->course->code }}"><option value="approved">Approve</option><option value="returned">Return for correction</option><option value="rejected">Reject</option></select><textarea name="review_note" rows="2" placeholder="Required for return or rejection"></textarea><button class="button button-small" type="submit">Save decision</button></form>@elseif ($result->review_note)<small>{{ $result->review_note }}</small>@endif</td></tr>@empty<tr><td class="empty-state" colspan="7">No results have been submitted.</td></tr>@endforelse
        </tbody></table></div>
    </section>
@endsection