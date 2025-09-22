<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title')</title>
    <link href="https://fonts.maateen.me/solaiman-lipi/font.css" rel="stylesheet">

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome for Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('dashboard/css/style.css') }}">
    @stack('styles')

</head>

<body>
    <!-- Overlay for Mobile -->
    <div id="overlay"></div>

    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="logo">DigiTrack</div>

        <form action="#" method="POST" class="search-form">
            @csrf
            <input type="text" name="query" placeholder="Central ID..." required>
            <button type="submit"><i class="fas fa-search"></i></button>
        </form>

        <div class="sidebar" id="sidebar">
            <div class="logo">DigiTrack</div>
            <form action="#" method="POST" class="search-form">
                <input type="text" name="query" placeholder="Central ID..." required>
                <button type="submit"><i class="fas fa-search"></i></button>
            </form>
            <ul>
                <!-- Dashboard -->
                <li>
                    <a href="/admin/home">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                </li>

                <!-- Roles & Permissions -->
                <li class="has-submenu">
                    <a href="#"><i class="fas fa-user-shield"></i> Roles & Permissions</a>
                    <ul class="submenu">
                        <li>
                            <a href="/admin/permission-groups">
                                <i class="fas fa-layer-group"></i> Permission Groups
                            </a>
                        </li>
                        <li>
                            <a href="/admin/permissions">
                                <i class="fas fa-key"></i> Permissions
                            </a>
                        </li>
                        <li>
                            <a href="/admin/roles">
                                <i class="fas fa-user-tag"></i> Roles
                            </a>
                        </li>

                        <li>
                            <a href="/admin/users">
                                <i class="fas fa-user"></i> Users
                            </a>
                        </li>
                    </ul>
                </li>

                <!-- Logout -->
                @auth
                    <li>
                        <a href="#"
                            onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                        <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                            @csrf
                        </form>
                    </li>
                @endauth
            </ul>
        </div>


    </div>




    <div class="header">
        <button id="sidebarToggle"><i class="fas fa-bars"></i></button>

        <div class="profile-menu">
            <button class="profile-button">
                <i class="fas fa-user"></i> {{ Auth::user()->name }}
            </button>

            <div class="dropdown-menu">
                <a href="{{ route('profile.edit') }}">
                    <i class="fas fa-user"></i> My Profile
                </a>

                <a href="#" onclick="logoutUser(event)">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </div>




    <!-- Content -->
    <div class="content" id="content">

        @yield('content')
        <div id="loader-overlay">
            <div id="loader"></div>
        </div>

    </div>
    <!-- Bootstrap JS & jQuery (optional) -->
    {{-- <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script> <!-- Bootstrap Bundle --> --}}
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script> <!-- SweetAlert2 -->
    <script src="{{ asset('dashboard/js/custom.js') }}"></script> <!-- Your custom JS file -->

    @stack('scripts')

    <script>
        function logoutUser(event) {
            event.preventDefault();

            fetch("{{ route('logout') }}", {
                method: "POST",
                headers: {
                    "X-CSRF-TOKEN": "{{ csrf_token() }}",
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({})
            }).then(() => {
                window.location.href = "{{ route('login') }}";
            });
        }
    </script>

</body>

</html>
