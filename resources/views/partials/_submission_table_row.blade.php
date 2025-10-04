{{-- Evaluation Process: Applicants' Submissions Row Loader --}}
<tr data-id="{{ $item->id }}">
    <td>{{ $item->id }}</td>
    <td>{{ $item->title ?? 'N/A' }}</td>
    <td>
        {{ ucwords(str_replace('-', ' ', $item->criterion)) }}
    </td>
    <td>{{ $item->created_at->format('F d, Y') }}</td>
    <td class="score-cell" id="score-cell-{{ $item->id }}">
        @if($item->score === null)
            <button class="btn btn-primary btn-sm set-score-btn"
                    data-submission-id="{{ $item->id }}"
                    data-kra-slug="{{ $kra_slug }}"
                    data-submission-title="{{ $item->title ?? 'this submission' }}">
                Set Score
            </button>
        @else
            <div class="score-display">
                <span class="score-value">{{ number_format($item->score, 2) }}</span>
                <span class="badge badge-scored">[ <i>Scored</i> ]</span>
            </div>
        @endif
    </td>
    <td>
    <div class="action-buttons">
        <button
            type="button"
            class="btn btn-info btn-sm view-file-btn"
            data-info-url="{{ route('evaluator.submission.files', ['kra_slug' => $kra_slug, 'submission_id' => $item->id]) }}">
            View File
        </button>
    </div>
</td>
</tr>