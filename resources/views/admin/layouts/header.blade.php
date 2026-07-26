<nav class="app-header navbar bg-body">
    <div class="container-fluid app-header-inner px-2 px-md-3">
        <div class="app-header-left d-flex align-items-center gap-2">
            <a class="nav-link px-2 header-icon-btn"
               data-lte-toggle="sidebar"
               href="#"
               role="button"
               aria-label="Відкрити меню">
                <i class="bi bi-list fs-4"></i>
            </a>

            <div class="header-brand d-none d-sm-flex flex-column">
                <span class="header-title">Панель керування</span>
                <span class="header-subtitle">Korporatsiia Mov</span>
            </div>
        </div>

        <div class="app-header-right d-flex align-items-center gap-2 ms-auto">
            <a href="{{ route('index') }}"
               class="nav-link header-home-btn d-none d-md-inline-flex align-items-center gap-2">
                <i class="bi bi-house-door"></i>
                <span>На сайт</span>
            </a>

            <a href="{{ route('index') }}"
               class="nav-link header-exit-btn d-inline-flex align-items-center gap-2">
                <i class="bi bi-box-arrow-right"></i>
                <span class="d-none d-lg-inline">Вийти з кабінету адміністратора</span>
                <span class="d-none d-md-inline d-lg-none">Вийти</span>
            </a>
        </div>
    </div>
</nav>
