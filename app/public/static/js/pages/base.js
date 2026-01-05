document.addEventListener('DOMContentLoaded', () => {
    
    const quotes = [
        "Zeit ist Geld, aber Faulheit ist unbezahlbar.",
        "Wir zählen die Stunden, damit du es nicht musst.",
        "Programmiert mit ❤️ und viel 🍺.",
        "Heute schon nichts getan? Wir verurteilen dich nicht.",
        "Lade Arbeitsmoral... Fehler 404.",
        "Schneller arbeiten bringt auch nicht mehr Feierabend.",
        "Wir tracken deine Zeit, nicht deine Motivation.",
        "Wir machens, weils SAP nicht kann.",
        "Arbeitszeit ist die neue Währung der Faulen.",
        "Timemanagement für fortschrittliche Faultiere."
    ];

    // Hilfsfunktion: Zufälligen Spruch setzen
    const updateQuotes = () => {
        const quote = quotes[Math.floor(Math.random() * quotes.length)];
        
        // Versuche beide Container zu finden (Login oder Dashboard)
        const els = [document.getElementById('header-quote'), document.getElementById('login-quote')];
        els.forEach(el => {
            if (el) {
                // Kurzer Fade-Effekt beim Textwechsel
                el.style.opacity = 0;
                setTimeout(() => {
                    el.textContent = quote;
                    el.style.opacity = 1;
                }, 200);
            }
        });
    };

    // Initial einen Spruch setzen
    updateQuotes();

    // 1. Faultier Spin Animation & Quote Update
    const sloths = document.querySelectorAll('.sloth-logo');
    sloths.forEach(sloth => {
        sloth.addEventListener('click', (e) => {
            e.preventDefault(); 
            e.stopPropagation();
            
            // Neuen Spruch holen
            updateQuotes();

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
            const rect = themeBtn.getBoundingClientRect();
            const x = rect.left + rect.width / 2;
            const y = rect.top + rect.height / 2;
            
            const isDark = document.documentElement.getAttribute('data-bs-theme') === 'dark';
            const nextTheme = isDark ? 'light' : 'dark';
            const nextColor = isDark ? '#f3f4f6' : '#0d1117'; 

            bubble.style.left = x + 'px';
            bubble.style.top = y + 'px';
            bubble.style.backgroundColor = nextColor;
            
            bubble.classList.add('expand');

            setTimeout(() => {
                document.documentElement.setAttribute('data-bs-theme', nextTheme);
                localStorage.setItem('theme', nextTheme);
                updateIcon();
                
                setTimeout(() => {
                    bubble.classList.remove('expand');
                }, 100); 
            }, 250); 
        });
    }
});