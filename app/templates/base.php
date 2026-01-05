<!DOCTYPE html>
<html lang="de" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TimeSloth</title>
    
    <link rel="icon" href="/static/img/logo.png">
    
    <link href="/static/css/bootstrap.css" rel="stylesheet">
    <link href="/static/css/bootstrap-icons.css" rel="stylesheet">
    <link href="/static/css/custom.css?v=0.1.7.0" rel="stylesheet">
    
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
    
    <div id="theme-bubble" class="theme-bubble"></div>

    <?php if (isset($_SESSION['user'])): ?>
    <nav class="navbar navbar-expand bg-body-tertiary shadow-sm mb-3 border-bottom sticky-top" style="z-index: 1050;">
        <div class="container-fluid">
            <div class="d-flex align-items-center">
                <img src="/static/img/logo.png" alt="Logo" width="42" height="42" 
                     class="me-3 rounded-circle sloth-logo" 
                     style="cursor: pointer;" title="Klick mich für neue Weisheiten!">

                <div class="d-flex flex-column justify-content-center">
                    <a class="navbar-brand fw-bold m-0 p-0 fs-5" href="/" style="line-height: 1;">TimeSloth</a>
                    <small id="header-quote" class="sloth-quote animate-fade">
                        </small>
                </div>
            </div>
            
            <div class="d-flex align-items-center gap-3">
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

    <main class="container-fluid p-0" id="main-content">
        <?php if (isset($_SESSION['flash_error'])): ?>
            <div class="alert alert-danger m-2 shadow-sm"><?= htmlspecialchars($_SESSION['flash_error']) ?></div>
            <?php unset($_SESSION['flash_error']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['flash_success'])): ?>
            <div class="alert alert-success m-2 shadow-sm"><?= htmlspecialchars($_SESSION['flash_success']) ?></div>
            <?php unset($_SESSION['flash_success']); ?>
        <?php endif; ?>

        <?= $content ?? '' ?>
        
        <footer class="app-footer mt-5 pb-4">
            <div class="d-flex flex-column align-items-center gap-3">
                
                <div class="footer-actions">
                    <i class="bi bi-moon-stars-fill theme-toggle-btn" id="darkModeBtn" title="Lichtschalter"></i>
                    <div style="width: 1px; height: 16px; background: var(--border-color);"></div>
                    <div class="d-flex align-items-center gap-2" style="font-size: 0.85rem;">
                        <span>&copy; <?= date('Y') ?> TimeSloth</span>
                        <img src="/static/img/logo.png" width="20" class="sloth-logo" style="filter: grayscale(1); opacity: 0.5;">
                    </div>
                </div>
            </div>
        </footer>
    </main>

    <script src="/static/js/pages/base.js"></script>
</body>
</html>