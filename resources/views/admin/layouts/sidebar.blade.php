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
                @php
                    $newTrialRequestsCount = auth()->user()?->isAdmin()
                        ? \App\Models\TrialLessonRequest::query()->new()->count()
                        : 0;
                @endphp
                <li class="nav-header">Кабінет вчителя</li>
                <li class="nav-item">
                    <a href="{{ route('admin.teacher.my_students') }}" class="nav-link">
                        <i class="nav-icon bi bi-calendar-check"></i>
                        <p>Мої студенти</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.teacher.my_groups') }}" class="nav-link">
                        <i class="nav-icon bi bi-calendar-check"></i>
                        <p>Мої групи</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.calendar.index') }}" class="nav-link">
                        <i class="nav-icon bi bi-calendar-check"></i>
                        <p>Розклад занять</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.teacher_income.index') }}" class="nav-link">
                        <i class="nav-icon bi bi-calendar-check"></i>
                        <p>Мої розрахунки</p>
                    </a>
                </li>
            @if (auth()->user()->role === 'admin')
                <li class="nav-header">CRM</li>
                <li class="nav-item">
                    <a href="{{ route('admin.index') }}" class="nav-link {{ request()->routeIs('admin.index') ? 'active' : '' }}">
                        <i class="nav-icon bi bi-bell"></i>
                        <p>
                            Заявки
                            @if($newTrialRequestsCount > 0)
                                <span class="badge text-bg-danger ms-2">{{ $newTrialRequestsCount }}</span>
                            @endif
                        </p>
                    </a>
                </li>

                <li class="nav-header">Управління заняттями</li>
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

                <li class="nav-header">Інформація про уроки</li>
                    <li class="nav-item">
                        <a href="{{route('admin.calendar_teachers.teachers.index')}}" class="nav-link">
                            <i class="nav-icon bi bi-calendar-check"></i>
                            <p>Розклад занять</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{route('admin.history_actions.index')}}" class="nav-link">
                            <i class="nav-icon bi bi-calendar-check"></i>
                            <p>Історія дій</p>
                        </a>
                    </li>
                <li class="nav-item">
                    <a href="{{route('admin.information.index')}}" class="nav-link">
                        <i class="nav-icon bi bi-person-lines-fill"></i>
                        <p>Проведені уроки</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.teachers.index') }}" class="nav-link">
                        <i class="nav-icon bi bi-people"></i>
                        <p>Викладачі</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{route('admin.students.index')}}" class="nav-link">
                        <i class="nav-icon bi bi-person-lines-fill"></i>
                        <p>Учні</p>
                    </a>
                </li>
                    <li class="nav-item">
                        <a href="{{route('admin.groups.index')}}" class="nav-link">
                            <i class="nav-icon bi bi-person-lines-fill"></i>
                            <p>Групи</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{route('admin.subscription-templates.index')}}" class="nav-link">
                            <i class="nav-icon bi bi-person-lines-fill"></i>
                            <p>Абонементи</p>
                        </a>
                    </li>
                <li class="nav-item">
                    <a href="{{route('admin.data.index')}}" class="nav-link">
                        <i class="nav-icon bi bi-person-lines-fill"></i>
                        <p>Дані</p>
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
                    <li class="nav-item {{ request()->routeIs('admin.testing.*') ? 'menu-open' : '' }}">
                        <a href="#" class="nav-link {{ request()->routeIs('admin.testing.*') ? 'active' : '' }}">
                            <i class="nav-icon bi bi-ui-checks"></i>
                            <p>
                                Тестування
                                <i class="nav-arrow bi bi-chevron-down"></i>
                            </p>
                        </a>

                        <ul class="nav nav-treeview">
                            <li class="nav-item">
                                <a href="{{ route('admin.testing.tests.index') }}"
                                   class="nav-link {{ request()->routeIs('admin.testing.tests.*') ? 'active' : '' }}">
                                    <i class="nav-icon bi bi-list"></i>
                                    <p>Тести</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('admin.school-rules.index') }}"
                                   class="nav-link {{ request()->routeIs('admin.school-rules.*') ? 'active' : '' }}">
                                    <i class="nav-icon bi bi-list-check"></i>
                                    <p>Правила школи</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('admin.testing.sessions.index') }}"
                                   class="nav-link {{ request()->routeIs('admin.testing.sessions.*') ? 'active' : '' }}">
                                    <i class="nav-icon bi bi-bar-chart"></i>
                                    <p>Сесії</p>
                                </a>
                            </li>
                        </ul>
                    </li>
                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <i class="nav-icon bi bi-telephone"></i>
                        <p>Контактні дані</p>
                    </a>
                </li>
                @endif


            </ul>
        </nav>
    </div>
    <!--end::Sidebar Wrapper-->
</aside>
<!--end::Sidebar-->
