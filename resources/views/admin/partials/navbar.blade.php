<nav class="navbar navbar-expand navbar-light navbar-bg">
    <a class="sidebar-toggle js-sidebar-toggle">
        <i class="hamburger align-self-center"></i>
    </a>

    <div class="navbar-collapse collapse">
        <ul class="navbar-nav navbar-align">

            <li class="nav-item">
                <form method="POST" action="{{ route('logout') }}"
                      class="d-flex align-items-center m-0">
                    @csrf

                    {{-- Kullanıcı adı --}}
                    <span class="nav-link text-uppercase fw-semibold py-0 me-2 d-flex align-items-center">
                        {{ auth()->user()->name ?? 'USER' }} - {{ auth()->user()->role ?? 'ROLE' }}
                    </span>

                    {{-- Çıkış --}}
                    <button type="submit"
                            class="nav-link btn btn-link text-danger py-0 d-flex align-items-center">
                        Çıkış
                    </button>

                </form>
            </li>

        </ul>
    </div>
</nav>
