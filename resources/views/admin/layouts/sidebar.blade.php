<!--begin::Sidebar-->
<aside class="app-sidebar bg-body-secondary shadow" data-bs-theme="dark">
    <!--begin::Sidebar Brand-->
    <div class="sidebar-brand">
        <a href="{{ route('admin.index') }}" class="brand-link">
            <img src="{{ asset('dist/assets/img/AdminLTELogo.png') }}" alt="AdminLTE Logo" class="brand-image opacity-75 shadow" />
            <span class="brand-text fw-light">Корпорація мов</span>
        </a>
    </div>
    <!--end::Sidebar Brand-->

    <!--begin::Sidebar Wrapper-->
    <div class="sidebar-wrapper">
        <nav class="mt-2">
            <ul class="nav sidebar-menu flex-column" data-lte-toggle="treeview" role="menu" data-accordion="false">

                <li class="nav-header">Управління заняттями</li>
                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <i class="nav-icon bi bi-calendar-check"></i>
                        <p>Розклад занять</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{route('admin.course.index')}}" class="nav-link">
                        <i class="nav-icon bi bi-journal-bookmark"></i>
                        <p>Курси</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <i class="nav-icon bi bi-film"></i>
                        <p>Відеоматеріали</p>
                    </a>
                </li>

                <li class="nav-header">Управління студентами</li>
                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <i class="nav-icon bi bi-people"></i>
                        <p>Студенти</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <i class="nav-icon bi bi-person-lines-fill"></i>
                        <p>Учні сайту</p>
                    </a>
                </li>

                <li class="nav-header">Управління сторінкою</li>
                <li class="nav-item">
                    <a href="{{route('admin.event.index')}}" class="nav-link">
                        <i class="nav-icon bi bi-calendar-event"></i>
                        <p>Події</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{route('admin.post.index')}}" class="nav-link">
                        <i class="nav-icon bi bi-newspaper"></i>
                        <p>Пости</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{route('admin.photos.index')}}" class="nav-link">
                        <i class="nav-icon bi bi-image"></i>
                        <p>Фото</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <i class="nav-icon bi bi-puzzle"></i>
                        <p>Тестування</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <i class="nav-icon bi bi-telephone"></i>
                        <p>Контактні дані</p>
                    </a>
                </li>

                <li class="nav-header">Управління викладачами</li>
                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <i class="nav-icon bi bi-calendar-week"></i>
                        <p>Графік викладачів</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <i class="nav-icon bi bi-person-badge"></i>
                        <p>Список викладачів</p>
                    </a>
                </li>

            </ul>
        </nav>
    </div>
    <!--end::Sidebar Wrapper-->
</aside>
<!--end::Sidebar-->
