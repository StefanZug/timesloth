<div class="d-flex flex-column align-items-center justify-content-center flex-grow-1" style="min-height: 70vh;">
    
    <div class="text-center mb-4 animate-fade">
        <img src="/static/img/logo.png" alt="TimeSloth" 
             style="height: 140px; width: auto; cursor: pointer;" 
             class="mb-2 sloth-logo" title="Klick mich!">
        
        <h1 class="fw-bold mb-0" style="letter-spacing: -1px;">TimeSloth</h1>
        <div class="text-muted small">Effizient faul sein.</div>
    </div>

    <div class="widget-card p-4 shadow-sm animate-fade" style="width: 100%; max-width: 360px; animation-delay: 0.1s;">
        <form method="POST" action="/login">
            <div class="form-floating mb-3">
                <input type="text" name="username" class="form-control" id="floatingInput" 
                       placeholder="Dein Name" required autocomplete="username" autofocus>
                <label for="floatingInput">Username</label>
            </div>
            <div class="form-floating mb-4">
                <input type="password" name="password" class="form-control" id="floatingPassword" 
                       placeholder="Geheim" required autocomplete="current-password">
                <label for="floatingPassword">Passwort</label>
            </div>
            <button type="submit" class="btn btn-primary w-100 py-2 fw-bold d-flex align-items-center justify-content-center gap-2">
                Let's be lazy <i class="bi bi-arrow-right"></i>
            </button>
        </form>
    </div>

</div>

<script src="/static/js/pages/login.js"></script>