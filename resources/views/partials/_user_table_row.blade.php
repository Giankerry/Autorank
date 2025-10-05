<tr id="user-row-{{ $user->id }}">
    <td>{{ $user->id }}</td>
    <td>{{ $user->name }}</td>
    <td>{{ $user->email }}</td>
    <td id="roles-{{ $user->id }}">
        @include('partials._roles_badge', ['user' => $user])
    </td>
    <td id="assigned-at-{{ $user->id }}">
        @if ($user->role_assigned_at)
        {{ $user->role_assigned_at->timezone('Asia/Manila')->format('m/d/y H:i') }}
        @else
        N/A
        @endif
    </td>
    <td id="assigned-by-{{ $user->id }}">
        {{ $user->role_assigned_by ?? 'N/A' }}
    </td>
    <td>
        <div class="action-container">
            <button class="update-role-btn"
                data-user-id="{{ $user->id }}"
                data-user-name="{{ $user->name }}"
                data-user-roles="{{ json_encode($user->roles->pluck('id')->toArray()) }}"
                data-current-role-name="{{ Str::title(str_replace('_', ' ', $user->roles->first()->name ?? 'N/A')) }}">
                Update Role
            </button>
        </div>
    </td>
</tr>