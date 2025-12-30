<!DOCTYPE html>
<html lang="de" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>TimeSloth 🦥</title>
    
    <link rel="icon" href="/static/img/favicon.png">
    
    <link href="/static/css/bootstrap.css" rel="stylesheet">
    <link href="/static/css/bootstrap-icons.css" rel="stylesheet">
    
    <style>
        /* Fix für lokale Fonts ohne Internet */
        @font-face {
            font-family: "bootstrap-icons";
            src: url("/static/fonts/bootstrap-icons.woff2") format("woff2");
        }
        
        /* Sloth Theme Colors */
        :root { --sloth-primary: #5c7cfa; }
        body { background-color: var(--bs-body-bg); transition: background-color 0.3s; }
        
        .avatar-circle {
            width: 35px; height: 35px; border-radius: 50%;
            background-color: var(--sloth-primary); color: white;
            display: flex; align-items: center; justify-content: center;
            font-weight: bold; font-size: 1.1rem;
        }
        .footer-sarcasm { 
            font-size: 0.75rem; 
            color: #888; 
            text-align: center; 
            margin-top: 50px; 
            padding-bottom: 30px; 
            opacity: 0.8;
        }

        /* Input Styling */
        tbody td input[type="text"], 
        tbody td input[type="time"] {
            width: 100%; min-width: 60px;
            background-color: transparent;
            border: 1px solid var(--bs-border-color-translucent);
            border-radius: 4px; padding: 2px 5px; text-align: center;
            transition: border-color .15s ease-in-out, box-shadow .15s ease-in-out;
        }
        tbody td input[type="text"]:focus,
        tbody td input[type="time"]:focus {
            border-color: var(--sloth-primary); outline: 0;
            box-shadow: 0 0 0 0.2rem rgba(92, 124, 250, 0.25);
        }
    </style>

    <script src="/static/js/bootstrap.js"></script>
    <script src="/static/js/vue.js"></script>
    <script src="/static/js/axios.js"></script>

    <script>
        // Theme direkt beim Laden setzen, um Flackern zu vermeiden
        const theme = localStorage.getItem('theme') || 'light';
        document.documentElement.setAttribute('data-bs-theme', theme);
    </script>
</head>
<body>
    
    <?php if (isset($_SESSION['user'])): ?>
    <nav class="navbar navbar-expand bg-body-tertiary shadow-sm mb-3 border-bottom sticky-top" style="z-index: 1050;">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold d-flex align-items-center" href="/">
                <img src="/static/img/favicon.png" alt="Logo" width="30" height="30" class="me-2 rounded-circle">
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

    <div class="container-fluid p-0" style="max-width: 800px; margin: 0 auto;">
        
        <?php if (isset($_SESSION['flash_error'])): ?>
            <div class="alert alert-danger m-2 shadow-sm">
                <?= htmlspecialchars($_SESSION['flash_error']) ?>
            </div>
            <?php unset($_SESSION['flash_error']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['flash_success'])): ?>
            <div class="alert alert-success m-2 shadow-sm">
                <?= htmlspecialchars($_SESSION['flash_success']) ?>
            </div>
            <?php unset($_SESSION['flash_success']); ?>
        <?php endif; ?>

        <?= $content ?? '' ?>
        
        <div class="footer-sarcasm">
            TimeSloth Inc. &copy; <?= date('Y') ?> - Wir speichern Daten, meistens.<br>
            Keine Haftung für verpasste SAP-Einträge, Eheprobleme oder Burnout.
        </div>
    </div>

    <script>
        // Dark Mode Toggle Logik
        const themeBtn = document.getElementById('darkModeBtn');
        if(themeBtn) {
            themeBtn.addEventListener('click', () => {
                const current = document.documentElement.getAttribute('data-bs-theme');
                const next = current === 'dark' ? 'light' : 'dark';
                document.documentElement.setAttribute('data-bs-theme', next);
                localStorage.setItem('theme', next);
            });
        }
    </script>
</body>
</html>