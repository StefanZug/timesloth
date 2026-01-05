document.addEventListener('DOMContentLoaded', () => {
    
    // 1. Faultier Spin Animation
    const sloths = document.querySelectorAll('.sloth-logo');
    sloths.forEach(sloth => {
        sloth.addEventListener('click', (e) => {
            e.preventDefault(); 
            e.stopPropagation();
            
            if(sloth.classList.contains('spin-animation')) return;
            
            sloth.classList.add('spin-animation');
            setTimeout(() => { 
                sloth.classList.remove('spin-animation'); 
            }, 1000);
        });
    });

    // 2. Bubble Theme Toggle
    const themeBtn = document.getElementById('darkModeBtn');
    const bubble = document.getElementById('theme-bubble');
    
    if(themeBtn && bubble) {
        // Icon initial setzen
        const updateIcon = () => {
            const isDark = document.documentElement.getAttribute('data-bs-theme') === 'dark';
            themeBtn.className = isDark ? 'bi bi-sun-fill theme-toggle-btn' : 'bi bi-moon-stars-fill theme-toggle-btn';
        };
        updateIcon();

        themeBtn.addEventListener('click', () => {
            // Koordinaten für den Startpunkt finden (Mitte des Buttons)
            const rect = themeBtn.getBoundingClientRect();
            const x = rect.left + rect.width / 2;
            const y = rect.top + rect.height / 2;
            
            // Ziel-Farbe und Theme bestimmen
            const isDark = document.documentElement.getAttribute('data-bs-theme') === 'dark';
            const nextTheme = isDark ? 'light' : 'dark';
            // Farben passend zu deinen CSS Variablen
            const nextColor = isDark ? '#f3f4f6' : '#0d1117'; 

            // Bubble positionieren
            bubble.style.left = x + 'px';
            bubble.style.top = y + 'px';
            bubble.style.backgroundColor = nextColor;
            
            // Explosion!
            bubble.classList.add('expand');

            // Theme switchen (nach kurzer Verzögerung für den Effekt)
            setTimeout(() => {
                document.documentElement.setAttribute('data-bs-theme', nextTheme);
                localStorage.setItem('theme', nextTheme);
                updateIcon();
                
                // Aufräumen: Bubble zurücksetzen
                setTimeout(() => {
                    bubble.classList.remove('expand');
                }, 100); 
            }, 250); 
        });
    }
});