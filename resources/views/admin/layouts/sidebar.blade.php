@php
    $user = auth()->user();
    $isAdmin = $user?->isAdmin();
    $newTrialRequestsCount = $isAdmin
        ? \App\Models\TrialLessonRequest::query()->new()->count()
        : 0;
@endphp

<aside class="app-sidebar shadow" data-bs-theme="dark">
    <div class="sidebar-brand">
        <a href="{{ route('admin.index') }}" class="brand-link">
            <img src="{{ asset('images/logo.png') }}" alt="Корпорація мов" class="brand-image">
            <span class="brand-text">
                <span class="brand-name">Корпорація мов</span>
                <span class="brand-note">{{ $isAdmin ? 'Admin CRM' : 'Кабінет викладача' }}</span>
            </span>
        </a>
    </div>

    <div class="sidebar-wrapper">
        <nav class="mt-2">
            <ul class="nav sidebar-menu flex-column" data-lte-toggle="treeview" role="menu" data-accordion="false">
                <li class="nav-header sidebar-section-title">Кабінет викладача</li>

                <li class="nav-item">
                    <a href="{{ route('admin.teacher.my_students') }}"
                       class="nav-link {{ request()->routeIs('admin.teacher.my_students') ? 'active' : '' }}">
                        <i class="nav-icon bi bi-person-check"></i>
                        <p>Мої студенти</p>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="{{ route('admin.teacher.my_groups') }}"
                       class="nav-link {{ request()->routeIs('admin.teacher.my_groups') ? 'active' : '' }}">
                        <i class="nav-icon bi bi-people"></i>
                        <p>Мої групи</p>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="{{ route('admin.calendar.index') }}"
                       class="nav-link {{ request()->routeIs('admin.calendar.*') ? 'active' : '' }}">
                        <i class="nav-icon bi bi-calendar-week"></i>
                        <p>Мій розклад</p>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="{{ route('admin.teacher_income.index') }}"
                       class="nav-link {{ request()->routeIs('admin.teacher_income.*') ? 'active' : '' }}">
                        <i class="nav-icon bi bi-cash-stack"></i>
                        <p>Мої розрахунки</p>
                    </a>
                </li>

                @if($isAdmin)
                    <li class="nav-header sidebar-section-title">CRM</li>

                    <li class="nav-item">
                        <a href="{{ route('admin.index') }}"
                           class="nav-link {{ request()->routeIs('admin.index') ? 'active' : '' }}">
                            <i class="nav-icon bi bi-bell"></i>
                            <p>
                                Заявки
                                @if($newTrialRequestsCount > 0)
                                    <span class="badge text-bg-danger ms-2">{{ $newTrialRequestsCount }}</span>
                                @endif
                            </p>
                        </a>
                    </li>

                    <li class="nav-header sidebar-section-title">Навчання</li>

                    <li class="nav-item">
                        <a href="{{ route('admin.course.index') }}"
                           class="nav-link {{ request()->routeIs('admin.course.*') ? 'active' : '' }}">
                            <i class="nav-icon bi bi-journal-bookmark"></i>
                            <p>Курси</p>
                        </a>
                    </li>

                    <li class="nav-item {{ request()->routeIs('admin.testing.*') ? 'menu-open' : '' }}">
                        <a href="#"
                           class="nav-link {{ request()->routeIs('admin.testing.*') ? 'active' : '' }}">
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
                                <a href="{{ route('admin.testing.sessions.index') }}"
                                   class="nav-link {{ request()->routeIs('admin.testing.sessions.*') ? 'active' : '' }}">
                                    <i class="nav-icon bi bi-bar-chart"></i>
                                    <p>Сесії</p>
                                </a>
                            </li>
                        </ul>
                    </li>

                    <li class="nav-header sidebar-section-title">Розклад і школа</li>

                    <li class="nav-item">
                        <a href="{{ route('admin.calendar_teachers.teachers.index') }}"
                           class="nav-link {{ request()->routeIs('admin.calendar_teachers.*') ? 'active' : '' }}">
                            <i class="nav-icon bi bi-calendar-range"></i>
                            <p>Розклад викладачів</p>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a href="{{ route('admin.history_actions.index') }}"
                           class="nav-link {{ request()->routeIs('admin.history_actions.*') ? 'active' : '' }}">
                            <i class="nav-icon bi bi-clock-history"></i>
                            <p>Історія дій</p>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a href="{{ route('admin.information.index') }}"
                           class="nav-link {{ request()->routeIs('admin.information.*') ? 'active' : '' }}">
                            <i class="nav-icon bi bi-check2-square"></i>
                            <p>Проведені уроки</p>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a href="{{ route('admin.teachers.index') }}"
                           class="nav-link {{ request()->routeIs('admin.teachers.*') ? 'active' : '' }}">
                            <i class="nav-icon bi bi-person-workspace"></i>
                            <p>Викладачі</p>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a href="{{ route('admin.students.index') }}"
                           class="nav-link {{ request()->routeIs('admin.students.*') ? 'active' : '' }}">
                            <i class="nav-icon bi bi-mortarboard"></i>
                            <p>Учні</p>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a href="{{ route('admin.groups.index') }}"
                           class="nav-link {{ request()->routeIs('admin.groups.*') ? 'active' : '' }}">
                            <i class="nav-icon bi bi-people-fill"></i>
                            <p>Групи</p>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a href="{{ route('admin.subscription-templates.index') }}"
                           class="nav-link {{ request()->routeIs('admin.subscription-templates.*') ? 'active' : '' }}">
                            <i class="nav-icon bi bi-card-checklist"></i>
                            <p>Абонементи</p>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a href="{{ route('admin.data.index') }}"
                           class="nav-link {{ request()->routeIs('admin.data.*') ? 'active' : '' }}">
                            <i class="nav-icon bi bi-database"></i>
                            <p>Дані</p>
                        </a>
                    </li>

                    <li class="nav-header sidebar-section-title">Сайт</li>

                    <li class="nav-item">
                        <a href="{{ route('admin.event.index') }}"
                           class="nav-link {{ request()->routeIs('admin.event.*') ? 'active' : '' }}">
                            <i class="nav-icon bi bi-calendar-event"></i>
                            <p>Події</p>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a href="{{ route('admin.post.index') }}"
                           class="nav-link {{ request()->routeIs('admin.post.*') ? 'active' : '' }}">
                            <i class="nav-icon bi bi-newspaper"></i>
                            <p>Пости</p>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a href="{{ route('admin.social-publishing.index') }}"
                           class="nav-link {{ request()->routeIs('admin.social-publishing.*') ? 'active' : '' }}">
                            <i class="nav-icon bi bi-share"></i>
                            <p>Соцмережі</p>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a href="{{ route('admin.photos.index') }}"
                           class="nav-link {{ request()->routeIs('admin.photos.*') ? 'active' : '' }}">
                            <i class="nav-icon bi bi-images"></i>
                            <p>Фото</p>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a href="{{ route('admin.school-rules.index') }}"
                           class="nav-link {{ request()->routeIs('admin.school-rules.*') ? 'active' : '' }}">
                            <i class="nav-icon bi bi-list-check"></i>
                            <p>Правила школи</p>
                        </a>
                    </li>
                @endif
            </ul>
        </nav>
    </div>
</aside>
