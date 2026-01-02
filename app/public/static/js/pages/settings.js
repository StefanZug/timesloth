const { createApp } = Vue;

createApp({
    delimiters: ['[[', ']]'],
    data() {
        return {
            // Daten aus der PHP-Bridge holen
            settings: Object.assign({
                percent: 100,
                sollStunden: 7.70,
                correction: 0,
                pcScroll: true,
                useNativeWheel: false
            }, window.slothData.settings || {}),
            
            passwords: {
                old: '',
                new: ''
            },
            
            calc: {
                baseWeekly: 38.5,
                weekly: 38.5,
                daily: 7.70
            },
            
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
            
            // Automatisch das Soll setzen, wenn Slider bewegt wird
            this.settings.sollStunden = this.calc.daily.toFixed(2);
        },
        
        formatNum(n) {
            return n.toFixed(2).replace('.', ',');
        },

        async saveSettings() {
            this.saveState = 'saving';
            try {
                // Legacy Support Felder für API
                const payload = {
                    ...this.settings,
                    sollMoDo: this.settings.sollStunden,
                    sollFr: this.settings.sollStunden
                };
                
                await axios.post('/api/settings', payload);
                this.saveState = 'saved';
                
                // Toast nach 2s ausblenden
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