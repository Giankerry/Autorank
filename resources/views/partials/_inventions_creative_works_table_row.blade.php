{{-- KRA II: Inventions, Innovations, & Creative Works Table Row Loader --}}
<tr data-id="{{ $item->id }}">
    <td>{{ $item->id }}</td>
    <td>{{ $item->title }}</td>
    <td>{{ $item->type }}</td>
    <td>{{ $item->role }}</td>
    @if ($item->score === null)
    <td>
        <span class="to-be-evaluated-score">To Be Evaluated</span>
    </td>
    @else
    <td>{{ $item->score }}</td>
    @endif
    <td>
        <div class="action-buttons">
            <button
                class="btn btn-info view-file-btn"
                data-info-url="{{ route('instructor.research.file-info', ['id' => $item->id]) }}"
                data-filename="{{ $item->filename }}">
                View File
            </button>
            <button
                class="btn btn-danger confirm-action-btn"
                data-action-url="{{ route('instructor.research.destroy', ['research' => $item->id]) }}"
                data-modal-title="Confirm Deletion"
                data-modal-text="This will delete the record and its associated file from Google Drive. This action cannot be undone."
                data-item-title="{{ $item->title }}"
                data-confirm-button-text="Delete">
                <i class="fa-solid fa-trash" style="color: #ffffff;"></i>
            </button>
        </div>
    </td>
</tr>