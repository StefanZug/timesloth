const { createApp } = Vue;

createApp({
    delimiters: ['[[', ']]'],
    data() {
        return {
            // Daten aus der PHP-Bridge holen
            currentUserId: window.slothData.currentUserId,
            users: window.slothData.users || [],
            holidays: window.slothData.holidays || [],
            
            stats: { db_size_bytes: 0, count_entries: 0, count_logs: 0 },
            loading: false,
            
            newUser: { username: '', password: '', is_admin: false, is_cats_user: false },
            newHoliday: { date: '', name: '' }
        }
    },
    methods: {
        formatDate(str) {
            if(!str) return 'Nie';
            return new Date(str).toLocaleDateString('de-DE');
        },
        formatBytes(bytes, decimals = 2) {
            if (!+bytes) return '0 B';
            const k = 1024;
            const sizes = ['B', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return `${parseFloat((bytes / Math.pow(k, i)).toFixed(decimals))} ${sizes[i]}`;
        },
        
        async fetchStats() {
            try {
                const res = await axios.get('/admin/stats');
                this.stats = res.data;
            } catch(e) { console.error(e); }
        },

        async cleanupLogs() {
            if(!confirm("Alte Logs und Datenbank bereinigen?")) return;
            this.loading = true;
            try {
                await axios.post('/admin/cleanup');
                await this.fetchStats();
                alert("Datenbank bereinigt!");
            } catch(e) { alert("Fehler!"); }
            this.loading = false;
        },

        async createUser() {
            try {
                await axios.post('/admin/create_user', this.newUser);
                location.reload();
            } catch(e) { alert(e.response?.data?.error || "Fehler"); }
        },
        async deleteUser(u) {
            if(!confirm(`User ${u.username} wirklich löschen?`)) return;
            try {
                await axios.post(`/admin/delete_user/${u.id}`);
                location.reload();
            } catch(e) { alert("Fehler"); }
        },
        async toggleActive(u) {
            try {
                await axios.post(`/admin/toggle_active/${u.id}`);
                u.is_active = !u.is_active; 
            } catch(e) { alert("Fehler"); }
        },

        async resetPw(u) {
            if(!confirm(`Passwort für ${u.username} zurücksetzen?`)) return;
            try {
                const res = await axios.post(`/admin/reset_password/${u.id}`);
                // Speichert das neue PW direkt am User-Objekt -> Löst Anzeige aus
                u.temp_password = res.data.new_password;
                u.pw_last_changed = new Date().toISOString(); 
            } catch(e) { alert("Fehler"); }
        },

        async fetchUserLogs(user) {
            if(user.logs) return;
            user.loadingLogs = true;
            try {
                const res = await axios.get(`/admin/user_logs/${user.id}`);
                user.logs = res.data;
            } catch(e) { console.error(e); }
            user.loadingLogs = false;
        },

        async copyPw(text, event) {
            try {
                await navigator.clipboard.writeText(text);
                const btn = event.target.closest('button');
                const originalHtml = btn.innerHTML;
                
                // Feedback geben
                btn.innerHTML = '<i class="bi bi-check-lg"></i> Kopiert!';
                btn.classList.remove('btn-light', 'text-success');
                btn.classList.add('btn-success', 'text-white');
                
                setTimeout(() => {
                    btn.innerHTML = originalHtml;
                    btn.classList.add('btn-light', 'text-success');
                    btn.classList.remove('btn-success', 'text-white');
                }, 1500);
            } catch(err) {
                prompt("Konnte nicht kopieren. Hier markieren:", text);
            }
        },

        async addHoliday() {
            try {
                await axios.post('/admin/holiday', this.newHoliday);
                location.reload();
            } catch(e) { alert("Datum existiert schon!"); }
        },
        async deleteHoliday(id) {
            if(!confirm("Löschen?")) return;
            try {
                await axios.delete(`/admin/holiday/${id}`);
                location.reload();
            } catch(e) { alert("Fehler"); }
        },

        // NEU: Methode für den Switch im Accordion
        async toggleCats(u) {
            try {
                // Wir senden den Request
                await axios.post(`/admin/toggle_cats/${u.id}`);
                // UI aktualisieren (invertieren), damit der Switch Feedback gibt
                u.is_cats_user = !u.is_cats_user;
            } catch(e) { 
                alert("Fehler beim Speichern der Berechtigung");
                // Bei Fehler Switch zurücksetzen (optional, aber sauber)
                location.reload();
            }
        },

        async toggleAdmin(u) {
            // Optimistisches UI-Update verhindern, wenn man es selbst ist
            // (Falls du die User-ID irgendwo im JS hast, hier prüfen)
            
            if (!confirm(`Soll der User ${u.username} wirklich ${u.is_admin ? 'Rechte verlieren' : 'Admin werden'}?`)) {
                // Checkbox zurücksetzen durch Neuladen oder manuell
                location.reload(); 
                return;
            }

            try {
                await axios.post(`/admin/toggle_admin/${u.id}`);
                // Erfolg: Status im lokalen Objekt umkehren
                u.is_admin = !u.is_admin; 
            } catch(e) { 
                // Fehler anzeigen (z.B. "Du kannst dir selbst nicht die Rechte nehmen")
                alert(e.response?.data?.error || "Fehler beim Speichern");
                location.reload(); // Zustand zurücksetzen
            }
        }
    },
    mounted() {
        this.fetchStats();
    }
}).mount('#adminApp');