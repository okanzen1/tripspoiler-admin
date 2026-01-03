<nav id="sidebar" class="sidebar js-sidebar">
    <div class="sidebar-content js-simplebar">

        <a class="sidebar-brand" href="{{ route('admin.dashboard') }}">
            <span class="align-middle">TripSpoiler Admin</span>
        </a>

        <ul class="sidebar-nav">

            {{-- DASHBOARD --}}
            <li class="sidebar-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                <a class="sidebar-link" href="{{ route('admin.dashboard') }}">
                    <i class="align-middle" data-feather="home"></i>
                    <span class="align-middle ms-2">Dashboard</span>
                </a>
            </li>

            {{-- USERS --}}
            <li class="sidebar-item {{ request()->routeIs('users.*') ? 'active' : '' }}">
                <a class="sidebar-link" href="{{ route('users.index') }}">
                    <i class="align-middle" data-feather="users"></i>
                    <span class="align-middle ms-2">Kullanıcılar</span>
                </a>
            </li>

        </ul>
    </div>
</nav>
