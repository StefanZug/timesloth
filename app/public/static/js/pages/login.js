document.addEventListener('DOMContentLoaded', () => {
    // Logik für den Klick-Effekt auf dem Logo
    const logo = document.getElementById('slothLogo');
    
    if(logo) {
        logo.addEventListener('click', () => {
            // Klasse hinzufügen (startet Animation via CSS)
            logo.classList.add('spin-animation');
            
            // Nach 1 Sekunde (Dauer der Animation) Klasse entfernen,
            // damit man nochmal klicken kann
            setTimeout(() => {
                logo.classList.remove('spin-animation');
            }, 1000);
        });
    }
});