<tr data-id="{{ $item->id }}">
    <td>{{ $item->id }}</td>
    <td>{{ $item->title }}</td>
    <td>{{ $item->type }}</td>
    <td>{{ $item->organizer }}</td>
    <td @if(($item->score ?? 'TBE') === 'TBE') style="color: gray;" title="To be evaluated" @endif>
    {{ $item->score ?? 'TBE' }}
    </td>
    <td>
        <div class="action-buttons">
            <button
                class="btn btn-info view-file-btn"
                data-info-url="{{ route('instructor.professional-development.file-info', ['id' => $item->id]) }}"
                data-filename="{{ $item->filename }}">
                View File
            </button>
            <button
                class="btn btn-danger confirm-action-btn"
                data-action-url="{{ route('instructor.professional-development.destroy', ['professionalDevelopment' => $item->id]) }}"
                data-modal-title="Confirm Deletion"
                data-modal-text="This will delete the record and its associated file. This action cannot be undone."
                data-item-title="{{ $item->title }}"
                data-confirm-button-text="Delete">
                <i class="fa-solid fa-trash"></i>
            </button>
        </div>
    </td>
</tr>