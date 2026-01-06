document.addEventListener('DOMContentLoaded', () => {
    
    const quotes = [
        "Zeit ist Geld, aber Faulheit ist unbezahlbar.",
        "Wir zählen die Stunden, damit du es nicht musst.",
        "Programmiert mit ❤️ und viel 🍺.",
        "Heute schon nichts getan? Wir verurteilen dich nicht.",
        "Lade Arbeitsmoral... ERROR 404.",
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
            
            // FIX: Timeout auf 3000ms erhöht (passend zur 3s CSS Animation)
            setTimeout(() => { 
                sloth.classList.remove('spin-animation'); 
            }, 3000);
        });
    });

    // 2. Sleepy Blink Theme Toggle
    const themeBtn = document.getElementById('darkModeBtn');
    const lidsContainer = document.getElementById('sloth-lids');
    
    if(themeBtn && lidsContainer) {
        // Icon initial setzen
        const updateIcon = () => {
            const isDark = document.documentElement.getAttribute('data-bs-theme') === 'dark';
            themeBtn.className = isDark ? 'bi bi-sun-fill theme-toggle-btn' : 'bi bi-moon-stars-fill theme-toggle-btn';
        };
        updateIcon();

        themeBtn.addEventListener('click', () => {
            // 1. Augen zu! (Klasse hinzufügen)
            lidsContainer.classList.add('eyes-closed');
            
            // 2. Warten bis Augen zu sind (400ms Animation)
            setTimeout(() => {
                // Jetzt im Dunkeln (hinter den Lidern) das Theme wechseln
                const isDark = document.documentElement.getAttribute('data-bs-theme') === 'dark';
                const nextTheme = isDark ? 'light' : 'dark';
                
                document.documentElement.setAttribute('data-bs-theme', nextTheme);
                localStorage.setItem('theme', nextTheme);
                updateIcon();

                // 3. Kurz warten und Augen wieder auf!
                setTimeout(() => {
                    lidsContainer.classList.remove('eyes-closed');
                }, 150); // Kurze "Schlaf"-Pause

            }, 400); 
        });
    }
});