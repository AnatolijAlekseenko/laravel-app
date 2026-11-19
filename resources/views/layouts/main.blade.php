
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>@yield('header-title', 'Система обліку майна')</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        :root {
            --orange: #ff8c00;
            --orange-hover: #ffa726;
            --black: #171717;
            --dark: #202020;
            --dark-light: #292929;
            --border: #3b3b3b;
            --text: #eeeeee;
            --text-muted: #aaaaaa;
            --danger: #a83232;
        }

        * {
            box-sizing: border-box;
        }

        html, body {
            margin: 0;
            min-height: 100%;
            background: var(--black);
            color: var(--text);
            font-family: Arial, sans-serif;
        }

        body {
            min-height: 100vh;
        }

        /* =====================================
           ОСНОВНОЙ КОНТЕЙНЕР
        ===================================== */

        .app-container {
            display: flex;
            min-height: 100vh;
            width: 100%;
        }

        /* =====================================
           БОКОВОЕ МЕНЮ
        ===================================== */

        .sidebar {
            width: 270px;
            min-width: 270px;
            height: 100vh;
            position: sticky;
            top: 0;
            flex-shrink: 0;
            overflow-y: auto;
            padding: 22px 16px;

            background: var(--dark);
            border-right: 2px solid var(--orange);

            box-shadow: 4px 0 15px rgba(0,0,0,.30);

            transition:
                width .3s ease,
                min-width .3s ease,
                padding .3s ease;
        }

        .sidebar-header {
            padding: 5px 8px 18px;
            margin-bottom: 20px;
            border-bottom: 2px solid var(--orange);
        }

        .sidebar-header h2 {
            margin: 0;
            color: var(--orange);
            font-size: 19px;
        }

        .sidebar-menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .sidebar-menu li {
            margin-bottom: 8px;
        }

        .sidebar-menu a {
            display: flex;
            align-items: center;
            gap: 11px;

            padding: 12px 13px;

            background: var(--black);
            color: var(--text);

            border: 1px solid var(--border);
            border-left: 4px solid var(--orange);
            border-radius: 6px;

            font-size: 14px;
            font-weight: 600;
            text-decoration: none;

            transition: .2s;
        }

        .sidebar-menu a:hover,
        .sidebar-menu a.active {
            background: #3a2b19;
            color: var(--orange-hover);
            border-color: var(--orange);
        }

        .menu-icon {
            width: 22px;
            text-align: center;
            font-size: 17px;
        }

        .menu-section {
            margin: 22px 8px 9px;
            color: var(--text-muted);
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .submenu {
            padding-left: 14px;
            margin-top: 7px;
        }

        .submenu a {
            padding: 9px 10px;
            font-size: 13px;
            border-left-width: 2px;
        }

        /* =====================================
           СВЕРНУТОЕ МЕНЮ
        ===================================== */

        .app-container.sidebar-collapsed .sidebar {
            width: 0;
            min-width: 0;
            padding-left: 0;
            padding-right: 0;
            border-right: none;
            overflow: hidden;
        }

        /* =====================================
           ПРАВАЯ РАБОЧАЯ ОБЛАСТЬ
        ===================================== */

        .main-content {
            flex: 1;
            min-width: 0;
            padding: 20px 25px;
            background: var(--black);
        }

        /* =====================================
           ВЕРХНЯЯ ПАНЕЛЬ
        ===================================== */

        .top-toolbar {
            display: flex;
            align-items: center;
            gap: 15px;

            min-height: 58px;
            padding: 9px 15px;
            margin-bottom: 22px;

            background: var(--dark);
            border: 1px solid var(--border);
            border-radius: 8px;

            box-shadow: 0 3px 12px rgba(0,0,0,.20);
        }

        .sidebar-toggle {
            display: flex;
            align-items: center;
            justify-content: center;

            width: 40px;
            height: 38px;
            padding: 0;

            background: var(--dark-light);
            color: var(--orange);

            border: 1px solid var(--orange);
            border-radius: 6px;

            font-size: 22px;
            cursor: pointer;
        }

        .sidebar-toggle:hover {
            background: var(--orange);
            color: #111;
        }

        .toolbar-title {
            color: var(--text);
            font-size: 16px;
            font-weight: bold;
        }

        .toolbar-right {
            display: flex;
            align-items: center;
            gap: 13px;
            margin-left: auto;
        }

        .user-info {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 3px;
        }

        .user-login {
            color: var(--orange);
            font-size: 14px;
            font-weight: bold;
        }

        .user-role {
            color: var(--text-muted);
            font-size: 11px;
        }

        .logout-button {
            background: var(--danger) !important;
            color: white !important;
            border: 1px solid #c94b4b !important;
            border-radius: 6px;
            padding: 9px 16px;
            cursor: pointer;
        }

        .logout-button:hover {
            background: #c03939 !important;
        }

        /* =====================================
           ЗАГОЛОВКИ
        ===================================== */

        h1, h2, h3, h4 {
            color: var(--orange);
        }

        h1 {
            font-size: 25px;
            font-weight: 700;
            padding-bottom: 12px;
            border-bottom: 2px solid var(--orange);
            margin: 0 0 20px;
        }

        h2 {
            font-size: 21px;
        }

        h3 {
            font-size: 18px;
        }

        p {
            color: var(--text);
        }

        /* =====================================
           ССЫЛКИ
        ===================================== */

        a {
            color: var(--orange);
            text-decoration: none;
        }

        a:hover {
            color: var(--orange-hover);
        }

        /* =====================================
           КНОПКИ
        ===================================== */

        button,
        input[type="submit"],
        .btn {
            background: var(--orange);
            color: #111;
            border: 1px solid var(--orange);
            border-radius: 6px;
            padding: 9px 16px;
            font-weight: 700;
            cursor: pointer;
            transition: .2s;
        }

        button:hover,
        input[type="submit"]:hover,
        .btn:hover {
            background: var(--orange-hover);
            border-color: var(--orange-hover);
        }

        button.sidebar-toggle {
            background: var(--dark-light);
            color: var(--orange);
            padding: 0;
        }

        button.sidebar-toggle:hover {
            background: var(--orange);
            color: #111;
        }

        /* =====================================
           ПОЛЯ ВВОДА
        ===================================== */

        input,
        select,
        textarea {
            background: var(--dark-light);
            color: white;
            border: 1px solid #555;
            border-radius: 5px;
            padding: 8px 10px;
            max-width: 100%;
        }

        input:focus,
        select:focus,
        textarea:focus {
            outline: none;
            border-color: var(--orange);
            box-shadow: 0 0 0 2px rgba(255,140,0,.2);
        }

        /* =====================================
           ТАБЛИЦЫ
        ===================================== */

        .table-container {
            width: 100%;
            overflow-x: auto;
            overflow-y: auto;
            max-height: calc(100vh - 190px);

            border: 1px solid var(--border);
            border-radius: 7px;
        }

        table {
            border-collapse: collapse;
            width: 100%;
            background: var(--dark);
            color: var(--text);
        }

        th {
            position: sticky;
            top: 0;
            z-index: 2;

            background: #101010;
            color: var(--orange);
            font-weight: 700;
            border: 1px solid var(--border);
            padding: 10px;
            white-space: nowrap;
        }

        td {
            border: 1px solid var(--border);
            padding: 8px 10px;
        }

        tr:nth-child(even) {
            background: #252525;
        }

        tr:hover {
            background: #383025;
        }

        /* =====================================
           РАЗДЕЛИТЕЛИ
        ===================================== */

        hr {
            border: 0;
            border-top: 1px solid var(--orange);
        }

        /* =====================================
           СООБЩЕНИЯ
        ===================================== */

        .alert {
            padding: 12px 16px;
            margin-bottom: 15px;
            border-radius: 6px;
        }

        .alert-success {
            background: #173d25;
            border: 1px solid #287b42;
            color: #b8f0c5;
        }

        .alert-danger {
            background: #461e1e;
            border: 1px solid #a83232;
            color: #ffc1c1;
        }

        /* =====================================
           ПОЛОСА ПРОКРУТКИ
        ===================================== */

        ::-webkit-scrollbar {
            width: 9px;
            height: 9px;
        }

        ::-webkit-scrollbar-track {
            background: #171717;
        }

        ::-webkit-scrollbar-thumb {
            background: #555;
            border-radius: 5px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: var(--orange);
        }

        /* =====================================
           МОБИЛЬНАЯ ВЕРСИЯ
        ===================================== */

        @media (max-width: 800px) {

            .app-container {
                flex-direction: column;
            }

            .sidebar {
                position: relative;
                width: 100%;
                min-width: 0;
                height: auto;
                max-height: 60vh;

                border-right: none;
                border-bottom: 2px solid var(--orange);
            }

            .app-container.sidebar-collapsed .sidebar {
                width: 100%;
                min-width: 0;
                height: 0;
                max-height: 0;
                padding-top: 0;
                padding-bottom: 0;
                border-bottom: none;
            }

            .main-content {
                width: 100%;
                padding: 12px;
            }

            .top-toolbar {
                flex-wrap: wrap;
                gap: 9px;
                padding: 9px;
            }

            .toolbar-title {
                font-size: 13px;
            }

            .toolbar-right {
                width: 100%;
                justify-content: space-between;
            }

            .user-info {
                align-items: flex-start;
            }

            .table-container {
                max-height: calc(100vh - 160px);
            }
        }
    </style>
</head>

<body>

<div class="app-container" id="appContainer">

    <!-- =================================
         БОКОВОЕ МЕНЮ
    ================================== -->

    <aside class="sidebar" id="sidebar">

        <div class="sidebar-header">
            <h2>Меню системи</h2>
        </div>

        <ul class="sidebar-menu">

            <li>
                <a href="{{ route('home') }}"
                   class="{{ request()->routeIs('home') ? 'active' : '' }}">
                    <span class="menu-icon">⌂</span>
                    Головна
                </a>
            </li>

            <li>
                <a href="{{ route('mayno') }}"
                   class="{{ request()->routeIs('mayno*') && !request()->routeIs('maynoSpisane*') ? 'active' : '' }}">
                    <span class="menu-icon">▤</span>
                    Активне майно
                </a>
            </li>

            <li>
                <a href="{{ route('maynoSpisane') }}"
                   class="{{ request()->routeIs('maynoSpisane*') ? 'active' : '' }}">
                    <span class="menu-icon">▧</span>
                    Списане майно
                </a>
            </li>

            @auth
                @if (Auth::user()->Role === 'admin')

                    <li class="menu-section">
                        Адміністрування
                    </li>

                    <li>
                        <a href="{{ route('users.index') }}"
                           class="{{ request()->routeIs('users.index', 'users.edit') ? 'active' : '' }}">
                            <span class="menu-icon">♙</span>
                            Список користувачів
                        </a>
                    </li>

                    <li>
                        <a href="{{ route('users.create') }}"
                           class="{{ request()->routeIs('users.create') ? 'active' : '' }}">
                            <span class="menu-icon">＋</span>
                            Створити користувача
                        </a>
                    </li>

                @endif
            @endauth

            <li class="menu-section">
                Інформація
            </li>

            <li>
                <a href="{{ route('about') }}"
                   class="{{ request()->routeIs('about') ? 'active' : '' }}">
                    <span class="menu-icon">ⓘ</span>
                    Про систему
                </a>
            </li>

        </ul>

    </aside>


    <!-- =================================
         ОСНОВНАЯ РАБОЧАЯ ОБЛАСТЬ
    ================================== -->

    <main class="main-content">

        <!-- ВЕРХНЯЯ ПАНЕЛЬ -->

        <div class="top-toolbar">

            <button
                type="button"
                class="sidebar-toggle"
                id="sidebarToggle"
                title="Скрыть или показать меню"
                aria-label="Скрыть или показать меню"
                aria-expanded="true"
                aria-controls="sidebar">

                ☰

            </button>

            <span class="toolbar-title">
                Система обліку майна
            </span>

            <div class="toolbar-right">

                @auth

                    <div class="user-info">

                        <span class="user-login">
                            {{ Auth::user()->Login }}
                        </span>

                        <span class="user-role">
                            @switch(Auth::user()->Role)
                                @case('admin')
                                    Адміністратор
                                    @break

                                @case('editor')
                                    Редактор
                                    @break

                                @default
                                    Перегляд
                            @endswitch
                        </span>

                    </div>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf

                        <button type="submit" class="logout-button">
                            Вийти
                        </button>
                    </form>

                @endauth

            </div>

        </div>


        <!-- СООБЩЕНИЯ -->

        @if (session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger">
                {{ session('error') }}
            </div>
        @endif


        <!-- СОДЕРЖИМОЕ СТРАНИЦ -->

        @yield('content')

    </main>

</div>


<!-- =================================
     УПРАВЛЕНИЕ БОКОВЫМ МЕНЮ
================================== -->

<script>
document.addEventListener('DOMContentLoaded', function () {

    const appContainer = document.getElementById('appContainer');
    const toggleButton = document.getElementById('sidebarToggle');

    if (!appContainer || !toggleButton) {
        return;
    }

    const savedState = localStorage.getItem('sidebarCollapsed');

    // По умолчанию меню свернуто
    if (savedState === null || savedState === 'true') {
        appContainer.classList.add('sidebar-collapsed');
    } else {
        appContainer.classList.remove('sidebar-collapsed');
    }

    function updateButton() {

        const isCollapsed =
            appContainer.classList.contains('sidebar-collapsed');

        toggleButton.setAttribute(
            'aria-expanded',
            (!isCollapsed).toString()
        );

        toggleButton.title = isCollapsed
            ? 'Показати меню'
            : 'Сховати меню';
    }

    updateButton();

    toggleButton.addEventListener('click', function () {

        appContainer.classList.toggle('sidebar-collapsed');

        const isCollapsed =
            appContainer.classList.contains('sidebar-collapsed');

        localStorage.setItem(
            'sidebarCollapsed',
            isCollapsed.toString()
        );

        updateButton();
    });

});
</script>

</body>
</html>
