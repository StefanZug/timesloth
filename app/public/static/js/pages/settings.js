const { createApp } = Vue;

createApp({
    delimiters: ['[[', ']]'],
    data() {
        return {
            settings: Object.assign({
                percent: 100,
                sollStunden: 7.70,
                pcScroll: true,
                useNativeWheel: false
            }, window.slothData.settings || {}),
            
            passwords: { old: '', new: '' },
            
            // GAME STATE
            pwGameSolved: false,
            gameClicks: 0,
            gameMessage: '',
            gameError: false,
            lastClickedId: null,

            calc: { baseWeekly: 38.5, weekly: 38.5, daily: 7.70 },
            saveState: 'idle'
        }
    },
    watch: {
        'settings.percent'() { this.updateCalc(); }
    },
    methods: {
        updateCalc() {
            const pct = parseInt(this.settings.percent) / 100;
            this.calc.weekly = this.calc.baseWeekly * pct;
            this.calc.daily = this.calc.weekly / 5;
            this.settings.sollStunden = this.calc.daily.toFixed(2);
        },
        formatNum(n) { return n.toFixed(2).replace('.', ','); },
        
        // --- GAME LOGIC ---
        handleSlothClick(id, event) {
            // WICHTIG: Neue Klasse verwenden
            const el = event.target.closest('.sloth-game-img');
            if(!el) return; 

            const shake = () => {
                el.style.transition = 'transform 0.1s';
                el.style.transform = 'translateX(5px) rotate(5deg)';
                setTimeout(() => { el.style.transform = 'translateX(-5px) rotate(-5deg)'; }, 100);
                setTimeout(() => { el.style.transform = 'translateX(5px) rotate(5deg)'; }, 200);
                setTimeout(() => { el.style.transform = 'translateX(0) rotate(0)'; }, 300);
            };

            if (this.gameClicks === 0) {
                // Erster Klick -> Immer falsch
                this.gameClicks++;
                this.lastClickedId = id;
                this.gameError = true;
                this.gameMessage = "Das ist falsch!"; 
                shake();
            } else {
                if (id === this.lastClickedId) {
                    // Gleiches nochmal geklickt -> Falsch
                    this.gameMessage = "Nein, das ANDERE!";
                    this.gameError = true;
                    shake();
                } else {
                    // Richtig!
                    this.gameError = false;
                    this.gameMessage = "Das macht niemand. Wurde auch NIE gesagt. Wie kommst du drauf?";
                    setTimeout(() => { this.pwGameSolved = true; }, 3000);
                }
            }
        },

        async saveSettings() {
            this.saveState = 'saving';
            try {
                const payload = {
                    ...this.settings,
                    sollMoDo: this.settings.sollStunden,
                    sollFr: this.settings.sollStunden
                };
                await axios.post('/api/settings', payload);
                this.saveState = 'saved';
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
                alert("Passwort geändert! Bitte neu einloggen.");
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