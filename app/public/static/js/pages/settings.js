const { createApp } = Vue;

createApp({
    delimiters: ['[[', ']]'],
    data() {
        return {
            // Settings aus der PHP-Bridge laden
            settings: Object.assign({
                percent: 100,
                sollStunden: 7.70,
                // correction: 0, // Legacy Support, wird aber im UI nicht mehr gezeigt
                pcScroll: true,
                useNativeWheel: false
            }, window.slothData.settings || {}),
            
            passwords: { old: '', new: '' },
            
            // --- FAULTIER GAME STATE ---
            pwGameSolved: false,
            gameClicks: 0,
            gameMessage: '',
            gameError: false,
            lastClickedId: null,
            // ---------------------------

            calc: { baseWeekly: 38.5, weekly: 38.5, daily: 7.70 },
            
            saveState: 'idle' // idle, saving, saved, error
        }
    },
    watch: {
        'settings.percent'() {
            this.updateCalc();
        }
    },
    methods: {
        updateCalc() {
            const pct = parseInt(this.settings.percent) / 100;
            this.calc.weekly = this.calc.baseWeekly * pct;
            this.calc.daily = this.calc.weekly / 5;
            
            // Automatisch das Soll setzen
            this.settings.sollStunden = this.calc.daily.toFixed(2);
        },
        
        formatNum(n) {
            return n.toFixed(2).replace('.', ',');
        },
        
        // --- FAULTIER SPIEL LOGIK ---
        handleSlothClick(id, event) {
            const el = event.target.closest('.sloth-logo'); // Sicherstellen dass wir das Bild haben
            
            // Wackel-Animation Helper
            const shake = () => {
                el.style.transition = 'transform 0.1s';
                el.style.transform = 'translateX(5px) rotate(5deg)';
                setTimeout(() => { el.style.transform = 'translateX(-5px) rotate(-5deg)'; }, 100);
                setTimeout(() => { el.style.transform = 'translateX(5px) rotate(5deg)'; }, 200);
                setTimeout(() => { el.style.transform = 'translateX(0) rotate(0)'; }, 300);
            };

            if (this.gameClicks === 0) {
                // ERSTER KLICK: IMMER FALSCH
                this.gameClicks++;
                this.lastClickedId = id;
                this.gameError = true;
                this.gameMessage = "Das ist falsch!"; 
                shake();
            } else {
                // ZWEITER (oder weiterer) KLICK
                if (id === this.lastClickedId) {
                    // User hat nochmal das gleiche (falsche) geklickt
                    this.gameMessage = "Nein, das ANDERE!";
                    this.gameError = true;
                    shake();
                } else {
                    // User hat das andere geklickt -> RICHTIG
                    this.gameError = false;
                    this.gameMessage = "Das macht niemand. Wurde auch NIE gesagt. Wie kommst du drauf?";
                    
                    // Kurze Pause zum Lesen, dann Formular zeigen
                    setTimeout(() => {
                        this.pwGameSolved = true;
                    }, 3000); // 3 Sekunden Zeit den Text zu genießen
                }
            }
        },
        // -----------------------------

        async saveSettings() {
            this.saveState = 'saving';
            try {
                // Payload senden
                const payload = {
                    ...this.settings,
                    sollMoDo: this.settings.sollStunden, // Legacy Support
                    sollFr: this.settings.sollStunden
                };
                
                await axios.post('/api/settings', payload);
                this.saveState = 'saved';
                
                // State zurücksetzen
                setTimeout(() => { this.saveState = 'idle'; }, 2000);
            } catch (e) {
                console.error(e);
                this.saveState = 'error';
                alert("Fehler beim Speichern!");
            }
        },

        async changePassword() {
            try {
                await axios.post('/change_password', {
                    old_password: this.passwords.old,
                    new_password: this.passwords.new
                });
                alert("Passwort erfolgreich geändert! Bitte neu einloggen.");
                this.passwords.old = '';
                this.passwords.new = '';
                window.location.href = '/logout';
            } catch (e) {
                alert("Fehler: " + (e.response?.data?.error || "Unbekannt"));
            }
        }
    },
    mounted() {
        this.updateCalc();
    }
}).mount('#settingsApp');