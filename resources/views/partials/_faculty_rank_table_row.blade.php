<tr id="user-row-{{ $user->id }}">
    <td>{{ $user->id }}</td>
    <td>{{ $user->name }}</td>
    <td>{{ $user->email }}</td>
    <td id="rank-{{ $user->id }}">
        {{ $user->faculty_rank ?? 'N/A' }}
    </td>
    <td>
        {{ $user->rank_assigned_at 
            ? \Carbon\Carbon::parse($user->rank_assigned_at)->timezone('Asia/Manila')->format('m/d/y H:i') 
            : 'N/A' }}
    </td>
    <td id="rank-assigned-by-{{ $user->id }}">
        {{ $user->rank_assigned_by ?? 'N/A' }}
    </td>
    <td>
        <div class="action-container">
            <button class="update-faculty-rank-btn"
                data-user-id="{{ $user->id }}"
                data-user-name="{{ $user->name }}"
                data-current-rank="{{ $user->faculty_rank ?? '' }}">
                Update Rank
            </button>
            <a href="{{ route('user.profile', $user->id) }}">
                <button>View Profile</button>
            </a>
        </div>
    </td>
</tr>
