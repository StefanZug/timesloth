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
            
            newUser: { username: '', password: '', is_admin: false },
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

        async fetchStats() { /* ... */ },

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
        }
    },
    mounted() {
        this.fetchStats();
    }
}).mount('#adminApp');