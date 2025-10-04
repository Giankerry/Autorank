{{-- Evaluation Process: Applications Row Loader --}}
<tr data-id="{{ $application->id }}">
    <td>{{ $application->id }}</td>
    <td>{{ $application->user->name ?? 'User Not Found' }}</td>
    <td>{{ $application->user->faculty_rank ?? 'N/A' }}</td>
    <td>{{ $application->created_at->format('F d, Y') }}</td>
    <td>
        <span class="status-badge status-{{ str_replace(' ', '-', $application->status) }}">
            {{ ucwords($application->status) }}
        </span>
    </td>
    <td>
        <div class="action-buttons">
            @if ($application->status === 'pending evaluation')
            <a href="{{ route('evaluator.application.details', ['application' => $application->id]) }}" class="btn btn-primary">
                <button>Evaluate</button>
            </a>
            @else
            <a href="{{ route('evaluator.application.details', ['application' => $application->id]) }}" class="btn btn-primary">
                <button>View</button>
            </a>
            @endif
        </div>
    </td>
</tr>
