<!doctype html>
<html lang="en">

<head>

    <meta charset="utf-8"/>
    <title>{{ config('app.name') }} - Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="Admin Panel" name="description"/>
    <meta content="Santexnik" name="author"/>
    <!-- App favicon -->
    <link rel="shortcut icon" href="assets/images/favicon.ico">

    <!-- jquery.vectormap css -->
    <link href="{{asset('assets/libs/admin-resources/jquery.vectormap/jquery-jvectormap-1.2.2.css')}}" rel="stylesheet"
          type="text/css"/>

    <!-- Bootstrap Css -->
    <!-- Icons Css -->
    <link href="{{asset('assets/css/icons.min.css')}}" rel="stylesheet" type="text/css"/>
    <!-- App Css-->
    <link href="{{asset('assets/css/app.min.css')}}" id="app-style" rel="stylesheet" type="text/css"/>
    <link href="{{ asset('assets/css/admin-crm.css') }}?v={{ @filemtime(public_path('assets/css/admin-crm.css')) }}" rel="stylesheet" type="text/css"/>
    <link href="{{asset('assets/libs/dropzone/min/dropzone.min.css')}}" id="app-style" rel="stylesheet"
          type="text/css"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.9.3/min/dropzone.min.css">
    <link href="{{asset('assets/css/bootstrap.min.css')}}" id="bootstrap-style" rel="stylesheet" type="text/css"/>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
    <link href="{{asset('assets/css/brand.css')}}?v={{ filemtime(public_path('assets/css/brand.css')) }}" rel="stylesheet" type="text/css"/>
    {{-- shadcn-style UI layer: loaded last so it wins over the theme, Bootstrap and select2. --}}
    <link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="{{ asset('assets/css/admin-ui.css') }}?v={{ @filemtime(public_path('assets/css/admin-ui.css')) }}" rel="stylesheet" type="text/css"/>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>

<body data-topbar="dark">

<!-- Begin page -->
<div id="layout-wrapper">

    <header id="page-topbar">
        <div class="navbar-header">
            <div class="d-flex">
                <!-- LOGO -->
                <div class="navbar-brand-box">
                                        @php
                      $adminMark = setting_image('favicon');
                      $adminName = setting('site_name', config('app.name'));
                    @endphp
                    {{-- Light top bar: the company icon (Settings → Favicon) and its name. --}}
                    <a href="{{route('home')}}" class="logo admin-logo" aria-label="{{ $adminName }} — dashboard">
                        @if($adminMark)<img src="{{ $adminMark }}" alt="" class="admin-logo-mark">@else<span class="admin-logo-mark admin-logo-initial" aria-hidden="true">{{ mb_strtoupper(mb_substr($adminName, 0, 1)) }}</span>@endif
                        <span class="admin-logo-text">{{ $adminName }}</span>
                    </a>
                </div>

                <button type="button" class="btn btn-sm px-3 font-size-24 header-item waves-effect"
                        id="vertical-menu-btn">
                    <i class="ri-menu-2-line align-middle"></i>
                </button>

                <!-- App Search-->
                <form class="app-search admin-search d-none d-lg-block" method="get" action="{{ route('admin.search') }}" role="search">
                    <div class="position-relative">
                        <input type="search" name="q" value="{{ request()->routeIs('admin.search') ? request('q') : '' }}" class="form-control" placeholder="Search tours, customers, bookings, leads…" aria-label="Search the admin">
                        <span class="ri-search-line"></span>
                    </div>
                </form>
            </div>

            <div class="d-flex">

                <div class="dropdown d-inline-block d-lg-none ms-2">
                    <button type="button" class="btn header-item noti-icon waves-effect"
                            id="page-header-search-dropdown"
                            data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <i class="ri-search-line"></i>
                    </button>
                    <div class="dropdown-menu dropdown-menu-lg dropdown-menu-end p-0"
                         aria-labelledby="page-header-search-dropdown">

                        <form class="p-3" method="get" action="{{ route('admin.search') }}" role="search">
                            <div class="mb-3 m-0">
                                <div class="input-group">
                                    <input type="search" name="q" class="form-control" placeholder="Search…" aria-label="Search the admin">
                                    <div class="input-group-append">
                                        <button class="btn btn-primary" type="submit"><i class="ri-search-line"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>


                <div class="dropdown d-inline-block user-dropdown">
                    @php
                      $adminUser = Auth::user();
                      $adminInitials = collect(preg_split('/\s+/', trim((string) $adminUser->name)))->filter()->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('');
                    @endphp
                    <button type="button" class="btn header-item waves-effect admin-user-btn" id="page-header-user-dropdown"
                            data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" aria-label="Account menu">
                        <span class="admin-avatar" aria-hidden="true">{{ $adminInitials ?: '?' }}</span>
                        <span class="d-none d-xl-inline-block admin-user-name">{{ $adminUser->name }}</span>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end admin-user-menu">
                        <div class="px-3 py-2">
                            <div class="fw-semibold text-body">{{ $adminUser->name }}</div>
                            <div class="text-muted small text-truncate">{{ $adminUser->email }}</div>
                            @if($adminUser->getRoleNames()->isNotEmpty())<div class="mt-1"><span class="badge bg-secondary">{{ $adminUser->getRoleNames()->implode(', ') }}</span></div>@endif
                        </div>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item" href="{{ url('/') }}" target="_blank" rel="noopener"><i class="ri-external-link-line align-middle me-1"></i> View website</a>
                        <a class="dropdown-item text-danger" href="{{route('logout')}}"><i class="ri-logout-box-r-line align-middle me-1"></i> Log out</a>
                    </div>
                </div>

            </div>
        </div>
    </header>

    <!-- ========== Left Sidebar Start ========== -->
    <div class="vertical-menu">

        <div data-simplebar class="h-100">

            <!--- Sidemenu -->
            <div id="sidebar-menu">
                @include('admin.includes.menu_search')
                <!-- Left Menu Start -->
                @include('admin.includes.menu')
            </div>
            <!-- Sidebar -->
        </div>
    </div>
    <!-- Left Sidebar End -->
