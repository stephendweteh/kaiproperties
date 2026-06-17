<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Kai Properties - Maintenance' }}</title>
    <link rel="stylesheet" href="{{ asset('css/site.css') }}">
</head>
<body>
@auth
    @php
        $siteName = \App\Models\Setting::valueFor('site_name', 'Kai Properties');
        $siteLogoPath = \App\Models\Setting::valueFor('logo_path');
    @endphp
    <div class="app-shell">
        <aside class="sidebar">
            <div class="sidebar-brand">
                @if($siteLogoPath)
                    <img class="sidebar-logo" src="{{ asset('storage/'.$siteLogoPath) }}" alt="{{ $siteName }} logo">
                @endif
                <h1 class="sidebar-title">{{ $siteName }}</h1>
                <p class="sidebar-subtitle">Maintenance System</p>
            </div>

            <nav class="side-nav">
                <a class="{{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                    <span class="nav-link-inner">
                        <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path d="M3 11l9-8 9 8"></path>
                            <path d="M5 10v10h14V10"></path>
                        </svg>
                        <span>Dashboard</span>
                    </span>
                </a>
                <a class="{{ request()->routeIs('tickets.index') ? 'active' : '' }}" href="{{ route('tickets.index') }}">
                    <span class="nav-link-inner">
                        <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path d="M4 8h16v12H4z"></path>
                            <path d="M8 8V6h8v2"></path>
                            <path d="M8 12h8"></path>
                        </svg>
                        <span>Tickets</span>
                    </span>
                </a>
                @if(auth()->user()->hasRole([
                    \App\Models\User::ROLE_ADMIN,
                    \App\Models\User::ROLE_OPERATIONS_MANAGER,
                    \App\Models\User::ROLE_TENANT,
                ]))
                    <a class="{{ request()->routeIs('tickets.create') ? 'active' : '' }}" href="{{ route('tickets.create') }}">
                        <span class="nav-link-inner">
                            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path d="M12 5v14"></path>
                                <path d="M5 12h14"></path>
                                <circle cx="12" cy="12" r="9"></circle>
                            </svg>
                            <span>Log Ticket</span>
                        </span>
                    </a>
                @endif

                @if(auth()->user()->role === 'admin')
                    <a class="{{ request()->routeIs('admin.properties.*') ? 'active' : '' }}" href="{{ route('admin.properties.index') }}">
                        <span class="nav-link-inner">
                            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path d="M3 10l9-7 9 7"></path>
                                <path d="M5 10v10h14V10"></path>
                                <path d="M9 20v-5h6v5"></path>
                            </svg>
                            <span>Properties</span>
                        </span>
                    </a>
                    <a class="{{ request()->routeIs('admin.categories.*') ? 'active' : '' }}" href="{{ route('admin.categories.index') }}">
                        <span class="nav-link-inner">
                            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path d="M4 4h7v7H4z"></path>
                                <path d="M13 4h7v7h-7z"></path>
                                <path d="M4 13h7v7H4z"></path>
                                <path d="M13 13h7v7h-7z"></path>
                            </svg>
                            <span>Categories</span>
                        </span>
                    </a>
                    <a class="{{ request()->routeIs('admin.users.*') ? 'active' : '' }}" href="{{ route('admin.users.index') }}">
                        <span class="nav-link-inner">
                            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <circle cx="8" cy="9" r="3"></circle>
                                <circle cx="17" cy="9" r="3"></circle>
                                <path d="M2 20c0-3 2.5-5 6-5s6 2 6 5"></path>
                                <path d="M12 20c.2-2.4 2.2-4 5-4 3 0 5 1.8 5 4"></path>
                            </svg>
                            <span>Users</span>
                        </span>
                    </a>
                    <a class="{{ request()->routeIs('admin.settings.*') ? 'active' : '' }}" href="{{ route('admin.settings.edit') }}">
                        <span class="nav-link-inner">
                            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <circle cx="12" cy="12" r="3"></circle>
                                <path d="M19.4 15a1.7 1.7 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06A1.7 1.7 0 0 0 15 19.4a1.7 1.7 0 0 0-1 .6 1.7 1.7 0 0 0-.4 1V21a2 2 0 1 1-4 0v-.1a1.7 1.7 0 0 0-.4-1 1.7 1.7 0 0 0-1-.6 1.7 1.7 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.7 1.7 0 0 0 4.6 15a1.7 1.7 0 0 0-.6-1 1.7 1.7 0 0 0-1-.4H3a2 2 0 1 1 0-4h.1a1.7 1.7 0 0 0 1-.4 1.7 1.7 0 0 0 .6-1 1.7 1.7 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.7 1.7 0 0 0 9 4.6c.38 0 .74-.14 1-.4.26-.26.4-.62.4-1V3a2 2 0 1 1 4 0v.1c0 .38.14.74.4 1 .26.26.62.4 1 .4a1.7 1.7 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06A1.7 1.7 0 0 0 19.4 9c0 .38.14.74.4 1 .26.26.62.4 1 .4H21a2 2 0 1 1 0 4h-.1a1.7 1.7 0 0 0-1 .4 1.7 1.7 0 0 0-.5.6z"></path>
                            </svg>
                            <span>Settings</span>
                        </span>
                    </a>
                @endif
            </nav>

            <div class="sidebar-footer">
                <div class="muted">{{ auth()->user()->name }}</div>
                <div class="muted" style="font-size: 0.84rem;">{{ auth()->user()->role === \App\Models\User::ROLE_ADMIN ? 'Super Admin' : str(auth()->user()->role)->replace('_', ' ')->title() }}</div>
                <a class="{{ request()->routeIs('profile.*') ? 'active' : '' }}" href="{{ route('profile.edit') }}" style="display:inline-block; margin-top: 0.7rem; text-decoration:none; color:#d7f6ef; background:rgba(255,255,255,0.1); padding:0.48rem 0.8rem; border-radius:8px;">
                    <span class="nav-link-inner">
                        <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <circle cx="12" cy="8" r="4"></circle>
                            <path d="M4 20c1.8-4 5-6 8-6s6.2 2 8 6"></path>
                        </svg>
                        <span>My Profile</span>
                    </span>
                </a>
                <form method="POST" action="{{ route('logout') }}" style="margin-top: 0.7rem;">
                    @csrf
                    <button type="submit" class="link-btn">
                        <span class="nav-link-inner">
                            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path d="M15 17l5-5-5-5"></path>
                                <path d="M20 12H9"></path>
                                <path d="M12 20H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h7"></path>
                            </svg>
                            <span>Logout</span>
                        </span>
                    </button>
                </form>
            </div>
        </aside>

        <main class="page-content page-content-shell">
            <div class="top-user-bar">
                <a class="top-user-chip" href="{{ route('profile.edit') }}" title="View profile">
                    @if(auth()->user()->profile_photo_path)
                        <img src="{{ asset('storage/'.auth()->user()->profile_photo_path) }}" alt="{{ auth()->user()->name }} profile photo" class="top-user-photo">
                    @else
                        <span class="top-user-photo top-user-initial">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                    @endif
                    <span class="top-user-name">{{ auth()->user()->name }}</span>
                </a>
            </div>

            @if(session('success'))
                <div class="alert success">{{ session('success') }}</div>
            @endif

            @if(session('error'))
                <div class="alert error">{{ session('error') }}</div>
            @endif

            @if($errors->any())
                <div class="alert error">
                    <ul>
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @yield('content')
        </main>
    </div>
@else
    <main class="{{ request()->routeIs('login') ? 'page-content login-page-content' : 'container page-content' }}">
        @if(session('success'))
            <div class="alert success">{{ session('success') }}</div>
        @endif

        @if(session('error'))
            <div class="alert error">{{ session('error') }}</div>
        @endif

        @if($errors->any())
            <div class="alert error">
                <ul>
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @yield('content')
    </main>
@endauth
</body>
</html>
