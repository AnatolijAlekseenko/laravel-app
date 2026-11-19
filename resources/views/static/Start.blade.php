
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Батальйон управління</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        /* =========================================
           1. БАЗОВЫЕ НАСТРОЙКИ
        ========================================= */

        html {
            scroll-behavior: smooth;
            min-height: 100%;
        }

        body {
            margin: 0 !important;
            padding: 0 !important;
            width: 100% !important;
            min-height: 100vh !important;
            background: #11120f !important;
            color: #f3f1eb !important;
            font-family: Arial, Helvetica, sans-serif !important;
            font-size: 16px !important;
        }

        .portal-shell,
        .portal-shell * {
            box-sizing: border-box;
        }

        .portal-shell {
            width: 100%;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            background:
                radial-gradient(
                    circle at 50% 0%,
                    rgba(246, 146, 30, .07),
                    transparent 45%
                ),
                #11120f;
            color: #f3f1eb;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 16px;
        }

        .portal-shell a {
            color: inherit;
            text-decoration: none;
        }

        /* =========================================
           2. ВЕРХНЯЯ ПАНЕЛЬ
        ========================================= */

        .portal-header {
            width: 100%;
            min-height: 100px;
            padding: 15px 35px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 25px;

            background: #191a16;
            border-bottom: 1px solid #38382f;
        }

        .portal-brand {
            display: flex;
            align-items: center;
            gap: 18px;
            min-width: 260px;
        }

        .portal-logo {
            width: 52px;
            height: 70px;
            object-fit: contain;
            flex-shrink: 0;
        }

        .portal-brand-title {
            color: #fff;
            font-size: 17px;
            font-weight: 900;
            line-height: 1.35;
            letter-spacing: 1.5px;
            text-transform: uppercase;
        }

        .portal-brand-subtitle {
            margin-top: 6px;
            color: #a19e92;
            font-size: 12px;
            letter-spacing: 1.5px;
        }

        .portal-header-center {
            color: #858276;
            font-size: 13px;
            letter-spacing: 3px;
            text-transform: uppercase;
            text-align: center;
        }

        .portal-header-right {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .portal-login {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 52px;
            padding: 0 27px;

            background: #f6921e;
            color: #17130d !important;

            font-size: 14px;
            font-weight: 900;
            letter-spacing: 1px;
            text-transform: uppercase;

            transition: background .2s, transform .2s;
        }

        .portal-login:hover {
            background: #ffad42;
            transform: translateY(-2px);
        }

        /* =========================================
           3. ОСНОВНАЯ СЕТКА
        ========================================= */

        .portal-layout {
            width: 100%;
            flex: 1;
            display: grid;
            grid-template-columns: 270px minmax(0, 1fr) 300px;
            align-items: stretch;
        }

        /* =========================================
           4. ЛЕВОЕ МЕНЮ
        ========================================= */

        .portal-sidebar {
            padding: 35px 22px;
            background: rgba(19, 20, 17, .85);
            border-right: 1px solid #38382f;
        }

        .portal-sidebar-heading {
            margin: 0 0 22px 10px;
            color: #a29e91;
            font-size: 13px;
            font-weight: 800;
            letter-spacing: 2px;
            text-transform: uppercase;
        }

        .portal-menu {
            display: flex;
            flex-direction: column;
            gap: 7px;
        }

        .portal-menu-link {
            min-height: 56px;
            display: flex;
            align-items: center;
            gap: 15px;

            padding: 10px 13px;

            border-left: 4px solid transparent;
            color: #d0cec5 !important;

            font-size: 15px;
            font-weight: 700;
            line-height: 1.3;

            transition: .2s;
        }

        .portal-menu-link:hover,
        .portal-menu-link.active {
            color: #ff9d24 !important;
            background: rgba(246, 146, 30, .10);
            border-left-color: #f6921e;
        }

        .portal-menu-icon {
            width: 27px;
            min-width: 27px;
            color: #f6921e;
            font-size: 22px;
            text-align: center;
        }

        .portal-sidebar-divider {
            height: 1px;
            margin: 30px 0;
            background: #38382f;
        }

        .portal-sidebar-note {
            padding: 10px;
            color: #a19e92;
            font-size: 14px;
            line-height: 1.8;
        }

        .portal-sidebar-note strong {
            display: block;
            margin-bottom: 10px;
            color: #f6921e;
            font-size: 13px;
            letter-spacing: 1px;
        }

        /* =========================================
           5. ЦЕНТРАЛЬНАЯ ЧАСТЬ
        ========================================= */

        .portal-content {
            width: 100%;
            min-width: 0;
            padding: 38px 42px;
        }

        .portal-page-heading {
            padding-bottom: 30px;
            margin-bottom: 32px;
            border-bottom: 1px solid #38382f;
        }

        .portal-page-title {
            margin: 0;
            color: #f4f2ec;
            font-size: clamp(28px, 3vw, 40px);
            font-weight: 900;
            line-height: 1.2;
            letter-spacing: -1px;
            text-transform: uppercase;
        }

        .portal-page-title span {
            color: #f6921e;
        }

        .portal-page-description {
            margin-top: 12px;
            color: #9a978c;
            font-size: 14px;
            letter-spacing: 2px;
            text-transform: uppercase;
        }

        /* =========================================
           6. ЗАГОЛОВКИ РАЗДЕЛОВ
        ========================================= */

        .portal-section {
            margin-bottom: 45px;
            scroll-margin-top: 25px;
        }

        .portal-section-heading {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;

            padding-bottom: 18px;
            margin-bottom: 10px;

            border-bottom: 1px solid #38382f;
        }

        .portal-section-title {
            margin: 0;
            color: #f2f0e9;
            font-size: 22px;
            font-weight: 900;
            line-height: 1.3;
            text-transform: uppercase;
        }

        .portal-section-title::before {
            content: "";
            display: inline-block;
            width: 5px;
            height: 23px;
            margin-right: 14px;
            vertical-align: -4px;
            background: #f6921e;
        }

        .portal-section-label {
            color: #8e8b80;
            font-size: 12px;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        /* =========================================
           7. НОВОСТИ
        ========================================= */

        .portal-news-list {
            display: flex;
            flex-direction: column;
        }

        .portal-news-item {
            display: flex;
            align-items: flex-start;
            gap: 23px;

            padding: 25px 12px;
            border-bottom: 1px solid #38382f;

            transition: background .2s;
        }

        .portal-news-item:hover {
            background: rgba(255,255,255,.025);
        }

        .portal-news-date {
            width: 78px;
            min-width: 78px;
            height: 78px;

            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;

            background: #22231e;
            border-bottom: 3px solid #f6921e;
        }

        .portal-news-date strong {
            color: #f6921e;
            font-size: 30px;
            line-height: 1.2;
        }

        .portal-news-date span {
            margin-top: 5px;
            color: #c2beb1;
            font-size: 11px;
            text-transform: uppercase;
        }

        .portal-news-body {
            min-width: 0;
            flex: 1;
        }

        .portal-news-category {
            margin-bottom: 9px;
            color: #f6921e;
            font-size: 12px;
            font-weight: 900;
            letter-spacing: 1.5px;
            text-transform: uppercase;
        }

        .portal-news-title {
            margin-bottom: 10px;
            color: #f5f3ec;
            font-size: 19px;
            font-weight: 800;
            line-height: 1.4;
        }

        .portal-news-text {
            color: #b0ada2;
            font-size: 15px;
            line-height: 1.8;
        }

        /* =========================================
           8. УЧЕБНЫЕ МАТЕРИАЛЫ
        ========================================= */

        .portal-materials {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px;
        }

        .portal-material-card {
            min-height: 115px;
            padding: 20px;

            display: flex;
            align-items: center;
            gap: 17px;

            background: #1c1d19;
            border: 1px solid #38382f;

            transition: .2s;
        }

        .portal-material-card:hover {
            border-color: #f6921e;
            background: #24231e;
            transform: translateY(-2px);
        }

        .portal-material-icon {
            width: 45px;
            min-width: 45px;
            color: #f6921e;
            font-size: 32px;
            text-align: center;
        }

        .portal-material-title {
            margin-bottom: 7px;
            color: #f4f2eb;
            font-size: 16px;
            font-weight: 800;
        }

        .portal-material-description {
            color: #aaa79b;
            font-size: 13px;
            line-height: 1.6;
        }

        /* =========================================
           9. ПРАВАЯ КОЛОНКА
        ========================================= */

        .portal-right {
            padding: 38px 25px;
            background: rgba(19, 20, 17, .55);
            border-left: 1px solid #38382f;
        }

        .portal-right-section {
            margin-bottom: 42px;
        }

        .portal-right-title {
            margin-bottom: 20px;
            color: #f6921e;
            font-size: 13px;
            font-weight: 900;
            letter-spacing: 1.5px;
            text-transform: uppercase;
        }

        .portal-right-link {
            display: block;
            padding: 14px 0;
            border-bottom: 1px solid #38382f;

            color: #d0cec5 !important;
            font-size: 14px;
            line-height: 1.5;

            transition: color .2s;
        }

        .portal-right-link:hover {
            color: #f6921e !important;
        }

        .portal-right-link span {
            float: right;
            color: #f6921e;
            font-size: 18px;
        }

        .portal-right-description {
            color: #b0ada2;
            font-size: 14px;
            line-height: 1.9;
        }

        .portal-status {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 14px;

            color: #f1efe8;
            font-size: 15px;
            font-weight: 700;
        }

        .portal-status-dot {
            width: 11px;
            height: 11px;
            min-width: 11px;
            border-radius: 50%;
            background: #f6921e;
            box-shadow: 0 0 10px rgba(246,146,30,.5);
        }

        /* =========================================
           10. НИЖНЯЯ ПАНЕЛЬ
        ========================================= */

        .portal-footer {
            min-height: 75px;
            width: 100%;
            padding: 20px 35px;

            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;

            background: #191a16;
            border-top: 1px solid #38382f;

            color: #a19e92;
            font-size: 12px;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .portal-footer-status {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        /* =========================================
           11. АДАПТИВНОСТЬ
        ========================================= */

        @media (max-width: 1200px) {

            .portal-layout {
                grid-template-columns: 230px minmax(0, 1fr);
            }

            .portal-right {
                grid-column: 2;
                border-left: none;
                border-top: 1px solid #38382f;

                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 25px;
            }

            .portal-content {
                padding: 30px;
            }
        }

        @media (max-width: 760px) {

            .portal-header {
                padding: 12px 18px;
                min-height: 85px;
                flex-wrap: wrap;
            }

            .portal-brand {
                min-width: 0;
                gap: 12px;
            }

            .portal-logo {
                width: 40px;
                height: 55px;
            }

            .portal-brand-title {
                font-size: 13px;
            }

            .portal-brand-subtitle,
            .portal-header-center {
                display: none;
            }

            .portal-login {
                min-height: 44px;
                padding: 0 14px;
                font-size: 11px;
            }

            .portal-layout {
                display: flex;
                flex-direction: column;
            }

            .portal-sidebar {
                padding: 20px 15px;
                border-right: none;
                border-bottom: 1px solid #38382f;
            }

            .portal-sidebar-heading {
                margin-bottom: 12px;
            }

            .portal-menu {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 5px;
            }

            .portal-menu-link {
                min-height: 48px;
                padding: 8px;
                font-size: 13px;
            }

            .portal-sidebar-divider,
            .portal-sidebar-note {
                display: none;
            }

            .portal-content {
                padding: 25px 16px;
            }

            .portal-page-title {
                font-size: 27px;
            }

            .portal-page-description {
                font-size: 11px;
                line-height: 1.6;
            }

            .portal-section-title {
                font-size: 18px;
            }

            .portal-section-label {
                display: none;
            }

            .portal-news-item {
                gap: 12px;
                padding: 20px 5px;
            }

            .portal-news-date {
                width: 58px;
                min-width: 58px;
                height: 65px;
            }

            .portal-news-date strong {
                font-size: 24px;
            }

            .portal-news-title {
                font-size: 16px;
            }

            .portal-news-text {
                font-size: 14px;
            }

            .portal-materials {
                grid-template-columns: 1fr;
            }

            .portal-right {
                display: block;
                padding: 25px 18px;
            }

            .portal-footer {
                padding: 18px;
                flex-direction: column;
                align-items: flex-start;
                font-size: 11px;
            }
        }

        @media (max-width: 380px) {

            .portal-menu-link {
                font-size: 11px;
            }

            .portal-news-text {
                font-size: 13px;
            }

            .portal-material-card {
                padding: 14px;
            }
        }

    </style>
</head>

<body>

<div class="portal-shell">

    <!-- =====================================
         ВЕРХНЯЯ ПАНЕЛЬ
    ====================================== -->

    <header class="portal-header">

        <div class="portal-brand">

            <svg
                class="portal-logo"
                viewBox="0 0 634 1027"
                fill="none"
                xmlns="http://www.w3.org/2000/svg"
                role="img"
                aria-label="Емблема"
            >
                <path
                    d="M396 234.064V0L0 396.774V631L396 234.064Z"
                    fill="#f6921e"
                />

                <path
                    d="M0 713.792V948L634 312.948V79L0 713.792Z"
                    fill="#f6921e"
                />

                <path
                    d="M237 1027L633 630.002V396L237 792.846V1027Z"
                    fill="#f6921e"
                />
            </svg>

            <div>
                <div class="portal-brand-title">
                    БАТАЛЬЙОН<br>
                    УПРАВЛІННЯ
                </div>

                <div class="portal-brand-subtitle">
                    ІНФОРМАЦІЙНИЙ ПОРТАЛ
                </div>
            </div>

        </div>

        <div class="portal-header-center">
            INFORMATION SYSTEM / UKRAINE
        </div>

        <div class="portal-header-right">

            <a href="{{ route('login') }}" class="portal-login">
                УВІЙТИ В СИСТЕМУ →
            </a>

        </div>

    </header>


    <!-- =====================================
         ОСНОВНОЕ СОДЕРЖИМОЕ
    ====================================== -->

    <div class="portal-layout">

        <!-- ЛЕВАЯ НАВИГАЦИЯ -->

        <aside class="portal-sidebar">

            <div class="portal-sidebar-heading">
                НАВІГАЦІЯ
            </div>

            <nav class="portal-menu">

                <a href="#portal-home" class="portal-menu-link active">
                    <span class="portal-menu-icon">⌂</span>
                    Головна
                </a>

                <a href="#portal-news" class="portal-menu-link">
                    <span class="portal-menu-icon">▤</span>
                    Новини
                </a>

                <a href="#portal-training" class="portal-menu-link">
                    <span class="portal-menu-icon">▣</span>
                    Навчальні матеріали
                </a>

                <a href="#portal-instructions" class="portal-menu-link">
                    <span class="portal-menu-icon">☷</span>
                    Інструкції
                </a>

                <a href="#portal-documents" class="portal-menu-link">
                    <span class="portal-menu-icon">▧</span>
                    Документація
                </a>

                <a href="#portal-contacts" class="portal-menu-link">
                    <span class="portal-menu-icon">✉</span>
                    Контакти
                </a>

            </nav>

            <div class="portal-sidebar-divider"></div>

            <div class="portal-sidebar-note">

                <strong>ІНФОРМАЦІЙНИЙ ПОРТАЛ</strong>

                Єдиний простір інформації,
                оголошень та навчальних матеріалів.

            </div>

        </aside>


        <!-- ЦЕНТРАЛЬНАЯ КОЛОНКА -->

        <main class="portal-content" id="portal-home">

            <!-- ЗАГОЛОВОК -->

            <div class="portal-page-heading">

                <h1 class="portal-page-title">
                    БАТАЛЬЙОН<br>
                    <span>УПРАВЛІННЯ</span>
                </h1>

                <div class="portal-page-description">
                    ІНФОРМАЦІЙНИЙ ПОРТАЛ ПІДРОЗДІЛУ
                </div>

            </div>


            <!-- НОВОСТИ -->

            <section class="portal-section" id="portal-news">

                <div class="portal-section-heading">

                    <h2 class="portal-section-title">
                        Новини та оголошення
                    </h2>

                    <div class="portal-section-label">
                        ІНФОРМАЦІЙНИЙ ЦЕНТР
                    </div>

                </div>

                <div class="portal-news-list">

                    <article class="portal-news-item">

                        <div class="portal-news-date">
                            <strong>01</strong>
                            <span>НОВИНА</span>
                        </div>

                        <div class="portal-news-body">

                            <div class="portal-news-category">
                                ОГОЛОШЕННЯ
                            </div>

                            <div class="portal-news-title">
                                Інформаційний розділ новин
                            </div>

                            <div class="portal-news-text">
                                У цьому розділі будуть розміщуватися
                                актуальні новини, оголошення та важлива
                                інформація підрозділу.
                            </div>

                        </div>

                    </article>


                    <article class="portal-news-item">

                        <div class="portal-news-date">
                            <strong>02</strong>
                            <span>НОВИНА</span>
                        </div>

                        <div class="portal-news-body">

                            <div class="portal-news-category">
                                ІНФОРМАЦІЯ
                            </div>

                            <div class="portal-news-title">
                                Оновлення інформаційного порталу
                            </div>

                            <div class="portal-news-text">
                                Тут можна публікувати повідомлення
                                про оновлення системи, нові матеріали
                                та зміни в документації.
                            </div>

                        </div>

                    </article>

                </div>

            </section>


            <!-- УЧЕБНЫЕ МАТЕРИАЛЫ -->

            <section class="portal-section" id="portal-training">

                <div class="portal-section-heading">

                    <h2 class="portal-section-title">
                        Матеріали для навчання
                    </h2>

                    <div class="portal-section-label">
                        БАЗА ЗНАНЬ
                    </div>

                </div>

                <div class="portal-materials">

                    <a href="#portal-instructions" class="portal-material-card">

                        <div class="portal-material-icon">▣</div>

                        <div>
                            <div class="portal-material-title">
                                Навчальні матеріали
                            </div>

                            <div class="portal-material-description">
                                Посібники та методичні матеріали.
                            </div>
                        </div>

                    </a>


                    <a href="#portal-documents" class="portal-material-card">

                        <div class="portal-material-icon">▤</div>

                        <div>
                            <div class="portal-material-title">
                                Документація
                            </div>

                            <div class="portal-material-description">
                                Документи, зразки та довідкова інформація.
                            </div>
                        </div>

                    </a>


                    <a href="#portal-instructions" class="portal-material-card">

                        <div class="portal-material-icon">☷</div>

                        <div>
                            <div class="portal-material-title">
                                Інструкції користувача
                            </div>

                            <div class="portal-material-description">
                                Порядок роботи з інформаційними ресурсами.
                            </div>
                        </div>

                    </a>


                    <a href="#portal-contacts" class="portal-material-card">

                        <div class="portal-material-icon">?</div>

                        <div>
                            <div class="portal-material-title">
                                Допомога користувачам
                            </div>

                            <div class="portal-material-description">
                                Контактна інформація та підтримка.
                            </div>
                        </div>

                    </a>

                </div>

            </section>


            <!-- ЯКОРЯ ДЛЯ РАЗДЕЛОВ -->

            <section class="portal-section" id="portal-instructions">
            </section>

            <section class="portal-section" id="portal-documents">
            </section>

            <section class="portal-section" id="portal-contacts">
            </section>

        </main>


        <!-- ПРАВАЯ КОЛОНКА -->

        <aside class="portal-right">

            <section class="portal-right-section">

                <div class="portal-right-title">
                    КОРИСНА ІНФОРМАЦІЯ
                </div>

                <a href="#portal-news" class="portal-right-link">
                    Новини та оголошення
                    <span>→</span>
                </a>

                <a href="#portal-training" class="portal-right-link">
                    Навчальні матеріали
                    <span>→</span>
                </a>

                <a href="#portal-instructions" class="portal-right-link">
                    Інструкції
                    <span>→</span>
                </a>

                <a href="#portal-documents" class="portal-right-link">
                    Документація
                    <span>→</span>
                </a>

            </section>


            <section class="portal-right-section">

                <div class="portal-right-title">
                    СТАН ПОРТАЛУ
                </div>

                <div class="portal-status">
                    <span class="portal-status-dot"></span>
                    Інформаційний портал
                </div>

                <div class="portal-right-description">
                    Публічна інформаційна сторінка.
                    Доступ до облікової системи
                    здійснюється після авторизації.
                </div>

            </section>


            <section class="portal-right-section">

                <div class="portal-right-title">
                    ПРО СИСТЕМУ
                </div>

                <div class="portal-right-description">
                    Інформаційний простір для розміщення
                    новин, оголошень, навчальних матеріалів
                    та документації.
                </div>

            </section>

        </aside>

    </div>


    <!-- =====================================
         ПОДВАЛ
    ====================================== -->

    <footer class="portal-footer">

        <div>
            БАТАЛЬЙОН УПРАВЛІННЯ © {{ date('Y') }}
        </div>

        <div class="portal-footer-status">
            <span class="portal-status-dot"></span>
            INFORMATION SYSTEM
        </div>

    </footer>

</div>

</body>
</html>
