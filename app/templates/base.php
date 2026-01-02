<!DOCTYPE html>
<html lang="de" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>TimeSloth</title>
    
    <link rel="icon" href="/static/img/logo.png">
    
    <link href="/static/css/bootstrap.css" rel="stylesheet">
    <link href="/static/css/bootstrap-icons.css" rel="stylesheet">
    <link href="/static/css/custom.css?v=0.1.4.3" rel="stylesheet">
    
    <script src="/static/js/bootstrap.js"></script>
    <script src="/static/js/vue.js"></script>
    <script src="/static/js/axios.js"></script>

    <script>
    let theme = localStorage.getItem('theme');
    if (!theme) {
        theme = (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) ? 'dark' : 'light';
    }
    document.documentElement.setAttribute('data-bs-theme', theme);
    </script>
</head>
<body>
    
    <?php if (isset($_SESSION['user'])): ?>
    <nav class="navbar navbar-expand bg-body-tertiary shadow-sm mb-3 border-bottom sticky-top" style="z-index: 1050;">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold d-flex align-items-center" href="/">
                <img src="/static/img/logo.png" alt="Logo" width="30" height="30" class="me-2 rounded-circle sloth-logo" style="cursor: pointer;">
                TimeSloth
            </a>
            
            <div class="d-flex align-items-center gap-3">
                <i class="bi bi-moon-stars-fill" id="darkModeBtn" style="cursor: pointer;"></i>
                
                <div class="dropdown">
                    <div class="avatar-circle" data-bs-toggle="dropdown" style="cursor: pointer;">
                        <?= strtoupper(substr($_SESSION['user']['username'], 0, 1)) ?>
                    </div>
                    <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                        <li><h6 class="dropdown-header">Hallo <?= htmlspecialchars($_SESSION['user']['username']) ?></h6></li>
                        <li><a class="dropdown-item" href="/settings">⚙️ Einstellungen</a></li>
                        <?php if (!empty($_SESSION['user']['is_admin'])): ?>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="/admin">🛡️ Admin Panel</a></li>
                        <?php endif; ?>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="/logout">Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>
    <?php endif; ?>

    <div class="container-fluid p-0" id="main-content">
        <?php if (isset($_SESSION['flash_error'])): ?>
            <div class="alert alert-danger m-2 shadow-sm"><?= htmlspecialchars($_SESSION['flash_error']) ?></div>
            <?php unset($_SESSION['flash_error']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['flash_success'])): ?>
            <div class="alert alert-success m-2 shadow-sm"><?= htmlspecialchars($_SESSION['flash_success']) ?></div>
            <?php unset($_SESSION['flash_success']); ?>
        <?php endif; ?>

        <?= $content ?? '' ?>
        
        <div class="app-footer">
            <div class="mb-2 d-flex align-items-center justify-content-center gap-2">
                <span>&copy; <?= date('Y') ?> • <span class="fw-bold">TimeSloth</span></span>
                <img src="/static/img/logo.png" alt="Logo" width="24" height="24" 
                     class="sloth-logo"
                     style="filter: grayscale(1); opacity: 0.7; transition: all 0.3s; cursor: pointer;">
            </div>
            
            <div class="mb-2">
                <?php 
                $quotes = [
                    "Zeit ist Geld, aber Faulheit ist unbezahlbar.",
                    "Wir zählen die Stunden, damit du es nicht musst.",
                    "Keine Haftung bei versehentlicher Produktivität.",
                    "Programmiert mit ❤️ und viel Koffein.",
                    "Heute schon nichts getan? Wir verurteilen dich nicht.",
                    "Wenn du das hier liest, arbeitest du gerade nicht. 👀",
                    "Lade Arbeitsmoral... Fehler 404.",
                    "Schneller arbeiten bringt auch nicht mehr Feierabend.",
                    "SAP glaubt dir. Wir auch. Meistens.",
                    "Wir tracken deine Zeit, nicht deine Motivation.",
                    "TimeSloth: Weil 'Ich hab vergessen zu buchen' keine Ausrede mehr ist.",
                    "Deine Büro-Quote weint leise im Hintergrund.",
                    "Zuhause ist es am schönsten, aber SAP will dich im Büro sehen.",
                    "Work-Life-Balance? Wir bevorzugen Life-Life-Balance.",
                    "Wir unterstützen proaktives Nichtstun.",
                    "Wir machens, weils SAP nicht kann."
                ];
                echo $quotes[array_rand($quotes)]; 
                ?>
            </div>
        </div>
    </div>

    <script>
        // Dark Mode Toggle
        const themeBtn = document.getElementById('darkModeBtn');
        if(themeBtn) {
            themeBtn.addEventListener('click', () => {
                const current = document.documentElement.getAttribute('data-bs-theme');
                const next = current === 'dark' ? 'light' : 'dark';
                document.documentElement.setAttribute('data-bs-theme', next);
                localStorage.setItem('theme', next);
            });
        }

        // SLOTH SPIN LOGIC (Global für alle Logos)
        document.addEventListener('DOMContentLoaded', () => {
            // Finde ALLE Elemente mit der Klasse .sloth-logo
            const sloths = document.querySelectorAll('.sloth-logo');
            
            sloths.forEach(sloth => {
                sloth.addEventListener('click', () => {
                    // Verhindern, dass Animation neu startet während sie läuft
                    if(sloth.classList.contains('spin-animation')) return;
                    
                    sloth.classList.add('spin-animation');
                    setTimeout(() => {
                        sloth.classList.remove('spin-animation');
                    }, 1000);
                });
            });
        });
    </script>
</body>
</html>