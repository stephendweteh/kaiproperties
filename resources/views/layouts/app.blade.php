<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @php
        $pwaSiteName = \App\Models\Setting::valueFor('site_name', 'Kai Properties');
        $pwaLogoPath = \App\Models\Setting::valueFor('logo_path');
        $pwaIcon = $pwaLogoPath ? asset('storage/'.$pwaLogoPath) : asset('favicon.ico');
    @endphp
    <title>{{ $title ?? 'Kai Properties - Maintenance' }}</title>
    <meta name="application-name" content="{{ $pwaSiteName }}">
    <meta name="apple-mobile-web-app-title" content="{{ $pwaSiteName }}">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="theme-color" content="#0c1f3f">
    <link rel="manifest" href="{{ route('pwa.manifest') }}">
    <link id="app-favicon" rel="icon" href="{{ $pwaIcon }}">
    <link rel="apple-touch-icon" href="{{ $pwaIcon }}">
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

                @if(auth()->user()->hasRole([
                    \App\Models\User::ROLE_ADMIN,
                    \App\Models\User::ROLE_OPERATIONS_MANAGER,
                ]))
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
                    @if(auth()->user()->hasRole(\App\Models\User::ROLE_ADMIN))
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
            <div class="mobile-app-header" aria-label="Mobile app header">
                <div class="mobile-app-title-wrap">
                    <div class="mobile-app-title">{{ $siteName }}</div>
                    <div class="mobile-app-subtitle">{{ $title ?? 'Dashboard' }}</div>
                </div>
                <div class="mobile-header-actions">
                    <button type="button" class="mobile-install-btn" data-install-app hidden>Install App</button>
                    <div class="mobile-notification-slot" aria-hidden="true">
                        <svg class="mobile-bell-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M15 17h5l-1.4-1.4a2 2 0 0 1-.6-1.4V11a6 6 0 1 0-12 0v3.2a2 2 0 0 1-.6 1.4L4 17h5"></path>
                            <path d="M10 17a2 2 0 0 0 4 0"></path>
                        </svg>
                        <span class="mobile-badge-dot"></span>
                    </div>
                    <form method="POST" action="{{ route('logout') }}" class="mobile-logout-form">
                        @csrf
                        <button type="submit" class="mobile-logout-btn" aria-label="Logout">
                            <svg class="mobile-logout-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path d="M15 17l5-5-5-5"></path>
                                <path d="M20 12H9"></path>
                                <path d="M12 20H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h7"></path>
                            </svg>
                        </button>
                    </form>
                </div>
            </div>

            <div class="top-user-bar">
                <button type="button" class="top-install-btn" data-install-app hidden>Install App</button>
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
    <main class="{{ request()->routeIs('login', 'signup.show') ? 'page-content login-page-content' : 'container page-content' }}">
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

<div class="pwa-loading-overlay" data-pwa-loading style="display:none;">
    <div class="pwa-loading-card" role="status" aria-live="polite" aria-label="Loading">
        <img src="{{ $pwaIcon }}" alt="{{ $pwaSiteName }}" class="pwa-loading-logo">
        <div class="pwa-loading-text">Please Wait...</div>
    </div>
</div>

<script>
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', function () {
            navigator.serviceWorker.register('{{ asset('sw.js') }}').catch(function () {
                // Service worker registration can fail silently without blocking app usage.
            });
        });
    }

    (function () {
        let deferredInstallPrompt = null;
        const installButtons = Array.from(document.querySelectorAll('[data-install-app]'));

        if (installButtons.length === 0) {
            return;
        }

        const setInstallButtonsVisible = function (isVisible) {
            installButtons.forEach(function (button) {
                button.hidden = !isVisible;
            });
        };

        window.addEventListener('beforeinstallprompt', function (event) {
            event.preventDefault();
            deferredInstallPrompt = event;
            setInstallButtonsVisible(true);
        });

        installButtons.forEach(function (button) {
            button.addEventListener('click', async function () {
                if (!deferredInstallPrompt) {
                    return;
                }

                deferredInstallPrompt.prompt();
                await deferredInstallPrompt.userChoice;
                deferredInstallPrompt = null;
                setInstallButtonsVisible(false);
            });
        });

        window.addEventListener('appinstalled', function () {
            deferredInstallPrompt = null;
            setInstallButtonsVisible(false);
        });
    })();

    (function () {
        const overlay = document.querySelector('[data-pwa-loading]');

        if (!overlay) {
            return;
        }

        let overlayTimer = null;

        const hideOverlay = function () {
            overlay.classList.remove('is-active');
            overlay.style.display = 'none';

            if (overlayTimer) {
                window.clearTimeout(overlayTimer);
                overlayTimer = null;
            }
        };

        const showOverlay = function () {
            overlay.classList.add('is-active');
            overlay.style.display = 'flex';

            if (overlayTimer) {
                window.clearTimeout(overlayTimer);
            }

            overlayTimer = window.setTimeout(hideOverlay, 8000);
        };

        document.addEventListener('submit', function (event) {
            if (event.target instanceof HTMLFormElement) {
                showOverlay();
            }
        }, true);

        document.addEventListener('click', function (event) {
            const target = event.target;
            const element = target instanceof Element ? target : target?.parentElement;

            if (!(element instanceof Element)) {
                return;
            }

            const link = element.closest('a[href]');

            if (!link) {
                return;
            }

            if (event.defaultPrevented || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
                return;
            }

            if (typeof event.button === 'number' && event.button !== 0) {
                return;
            }

            if (link.hasAttribute('download') || link.getAttribute('target') === '_blank') {
                return;
            }

            const href = link.getAttribute('href') || '';

            if (href.startsWith('#') || href.startsWith('javascript:')) {
                return;
            }

            const url = new URL(link.href, window.location.href);

            if (url.origin !== window.location.origin) {
                return;
            }

            showOverlay();
        }, true);

        window.addEventListener('beforeunload', showOverlay);
        document.addEventListener('DOMContentLoaded', hideOverlay);
        window.addEventListener('load', hideOverlay);
        window.addEventListener('pageshow', hideOverlay);

        document.addEventListener('readystatechange', function () {
            if (document.readyState === 'complete') {
                hideOverlay();
            }
        });
    })();

</script>
</body>
</html>
