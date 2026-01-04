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

            {{-- AFFILIATE PARTNERS --}}
            <li class="sidebar-item {{ request()->routeIs('affiliate-partners.*') ? 'active' : '' }}">
                <a class="sidebar-link" href="{{ route('affiliate-partners.index') }}">
                    <i class="align-middle" data-feather="link"></i>
                    <span class="align-middle ms-2">Affiliate Partners</span>
                </a>
            </li>

            {{-- COUNTRIES --}}
            <li class="sidebar-item {{ request()->routeIs('countries.*') ? 'active' : '' }}">
                <a class="sidebar-link" href="{{ route('countries.index') }}">
                    <i class="align-middle" data-feather="flag"></i>
                    <span class="align-middle ms-2">Ülkeler</span>
                </a>
            </li>
            {{-- CITIES --}}
            <li class="sidebar-item {{ request()->routeIs('cities.*') ? 'active' : '' }}">
                <a class="sidebar-link" href="{{ route('cities.index') }}">
                    <i class="align-middle" data-feather="map-pin"></i>
                    <span class="align-middle ms-2">Şehirler</span>
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
