<nav>
    <div class="navbar-container">
        <div class="navbar-left-side">
            <i id="menu-toggle-button" class="fa-solid fa-bars" style="color: #ffffff;"></i>
            <div class="navbar-logo-container">
                <img src="{{ asset(\App\Models\Setting::where('key', 'site_logo')->value('value') ?? 'images/pampanga-state-u-logo-small.png') }}" alt="Website Logo">
                <h1>AutoRank</h1>
            </div>
        </div>
        <div class="navbar-right-side">
            @auth
            <img src="{{ Auth::user()->avatar }}" alt="{{ auth()->user()->name }}'s profile picture">
            <h2>
                {{ auth()->user()->name }}
                <b>
                    [
                    {{-- Dynamically display the user's current role --}}
                    @php
                    $currentUserRoleForDisplay = 'Guest'; // Default fallback

                    // Re-fetch the user from the database directly for the navbar.
                    // This diagnostic step helps confirm the latest role from DB.
                    $navbarUser = \App\Models\User::with('roles')->find(Auth::id());

                    if ($navbarUser && $navbarUser->roles->isNotEmpty()) {
                    $currentUserRoleForDisplay = $navbarUser->roles->first()->name;
                    }
                    @endphp
                    {{ Str::title(str_replace('_', ' ', $currentUserRoleForDisplay)) }}
                    ]
                </b>
            </h2>
            @endauth
        </div>
    </div>
    <div id="hidden-menu">
        {{-- ADMIN Routes --}}
        @auth
        @can('manage users')
        <a href="{{ route('admin.dashboard') }}">
            <div class="hidden-menu-icon">
                <i class="fa-solid fa-check-to-slot" style="color: #ffffff;"></i>
            </div>
            <div class="hidden-menu-title">
                <p>Admin Dashboard</p>
            </div>
        </a>

        <a href="{{ route('admin.modeler') }}">
            <div class="hidden-menu-icon">
                <i class="fa-solid fa-hexagon-nodes" style="color: #ffffff;"></i>
            </div>
            <div class="hidden-menu-title">
                <p>Strategic Modeler</p>
            </div>
        </a>

        <a href="{{ route('manage-users') }}">
            <div class="hidden-menu-icon">
                <i class="fa-solid fa-users"></i>
            </div>
            <div class="hidden-menu-title">
                <p>Manage Users</p>
            </div>
        </a>
        @endcan
        @endauth

        {{-- EVALUATOR Routes --}}
        @auth
        @can('access evaluate applications page')
        <a href="{{ route('evaluator.applications.dashboard') }}">
            <div class="hidden-menu-icon">
                <i class="fa-solid fa-file-circle-check"></i>
            </div>
            <div class="hidden-menu-title">
                <p>Evaluate Applications</p>
            </div>
        </a>
        @endcan
        @endauth

        {{-- INSTRUCTOR Routes --}}
        @unlessrole('admin|evaluator')
        <a href="{{ route('profile-page') }}">
            <div class="hidden-menu-icon">
                <i class="fa-solid fa-address-book"></i>
            </div>
            <div class="hidden-menu-title">
                <p>Profile</p>
            </div>
        </a>

        <a href="{{ route('instructor.instructional-page') }}">
            <div class="hidden-menu-icon">
                <i class="fa-solid fa-clipboard-check"></i>
            </div>
            <div class="hidden-menu-title">
                <p>KRA I: Instruction</p>
            </div>
        </a>

        <a href="{{ route('instructor.research-page') }}">
            <div class="hidden-menu-icon">
                <i class="fa-solid fa-file-signature"></i>
            </div>
            <div class="hidden-menu-title">
                <p>KRA II: Research</p>
            </div>
        </a>

        <a href="{{ route('instructor.extension-page') }}">
            <div class="hidden-menu-icon">
                <i class="fa-solid fa-handshake"></i>
            </div>
            <div class="hidden-menu-title">
                <p>KRA III: Extension Services</p>
            </div>
        </a>

        <a href="{{ route('instructor.professional-development-page') }}">
            <div class="hidden-menu-icon">
                <i class="fa-solid fa-arrow-trend-up"></i>
            </div>
            <div class="hidden-menu-title">
                <p>KRA IV: Professional Development</p>
            </div>
        </a>
        @endunlessrole

        <a href="{{ route('system-settings') }}">
            <div class="hidden-menu-icon">
                <i class="fa-solid fa-gear"></i>
            </div>
            <div class="hidden-menu-title">
                <p>System Settings</p>
            </div>
        </a>

        <a href="#" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
            <div class="hidden-menu-icon">
                <i class="fa-solid fa-key"></i>
            </div>
            <div class="hidden-menu-title">
                <p>Log Out</p>
            </div>
        </a>

        <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
            @csrf
        </form>
    </div>
</nav>