<div class="d-flex align-items-center justify-content-center" style="min-height: 70vh;">
    <div class="card shadow border-0 p-4" style="width: 100%; max-width: 350px;">
        <div class="text-center mb-4">
            <h1 style="font-size: 4rem;">🦥</h1>
            <h3>TimeSloth Login</h3>
        </div>
        
        <form method="POST" action="/login">
            <div class="mb-3">
                <label class="form-label">Username</label>
                <input type="text" name="username" class="form-control p-3" 
                       placeholder="Dein Name" required autocomplete="username" autofocus>
            </div>
            <div class="mb-3">
                <label class="form-label">Passwort</label>
                <input type="password" name="password" class="form-control p-3" 
                       placeholder="Geheim" required autocomplete="current-password">
            </div>
            <button type="submit" class="btn btn-primary w-100 p-3 fw-bold">Let's be lazy</button>
        </form>
    </div>
</div>