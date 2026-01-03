<div id="adminApp" class="container mt-4 mb-5" v-cloak>
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold m-0"><i class="bi bi-shield-lock"></i> Admin Cockpit</h2>
        <a href="/" class="btn btn-outline-secondary border-0"><i class="bi bi-x-lg"></i></a>
    </div>

    <div class="row mb-4">
        <div class="col-12">
            <div class="widget-card bg-body-tertiary">
                <div class="widget-body d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <div>
                        <small class="text-uppercase text-muted fw-bold d-block">Datenbank Größe</small>
                        <span class="fs-4 font-monospace">[[ formatBytes(stats.db_size_bytes) ]]</span>
                    </div>
                    <div class="text-center px-3 border-start border-end">
                        <small class="text-uppercase text-muted fw-bold d-block">Einträge</small>
                        <span class="fs-4">[[ stats.count_entries ]]</span>
                    </div>
                    <div class="text-center px-3 border-end">
                        <small class="text-uppercase text-muted fw-bold d-block">Logins</small>
                        <span class="fs-4">[[ stats.count_logs ]]</span>
                    </div>
                    <div class="ms-auto">
                        <button class="btn btn-outline-secondary btn-sm" @click="cleanupLogs" :disabled="loading">
                            <i class="bi bi-stars"></i> DB Bereinigen
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        
        <div class="col-lg-7">
            <div class="widget-card h-100">
                <div class="widget-header d-flex justify-content-between align-items-center">
                    <span>🦖 User Verwaltung</span>
                    <button class="btn btn-sm btn-primary py-0" data-bs-toggle="modal" data-bs-target="#createUserModal">+</button>
                </div>
                
                <div class="accordion accordion-flush" id="usersAccordion">
                    <div class="accordion-item" v-for="u in users" :key="u.id">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" :data-bs-target="'#user-'+u.id">
                                <span class="d-flex align-items-center gap-2 w-100">
                                    <span class="avatar-circle" style="width: 28px; height: 28px; font-size: 0.8rem;">
                                        [[ u.username.charAt(0).toUpperCase() ]]
                                    </span>
                                    <span class="fw-bold">[[ u.username ]]</span>
                                    <span v-if="u.is_admin" class="badge bg-primary rounded-pill ms-2" style="font-size: 0.6rem;">ADMIN</span>
                                    
                                    <span class="ms-auto me-3 d-flex align-items-center gap-2 small text-muted">
                                        <i class="bi bi-circle-fill" :class="u.is_active ? 'text-success' : 'text-danger'" style="font-size: 0.6rem;"></i>
                                        <span class="d-none d-sm-inline">[[ u.is_active ? 'Aktiv' : 'Gesperrt' ]]</span>
                                    </span>
                                </span>
                            </button>
                        </h2>
                        <div :id="'user-'+u.id" class="accordion-collapse collapse" data-bs-parent="#usersAccordion">
                            <div class="accordion-body bg-body-tertiary">
                                
                                <div class="d-flex flex-wrap gap-2 mb-3">
                                    <div class="bg-body p-2 rounded border small flex-fill">
                                        <span class="text-muted d-block">Passwort zuletzt geändert</span>
                                        <strong>[[ formatDate(u.pw_last_changed) ]]</strong>
                                    </div>
                                    <div class="bg-body p-2 rounded border small flex-fill">
                                        <span class="text-muted d-block">Rolle</span>
                                        <strong>[[ u.is_admin ? 'Administrator' : 'Benutzer' ]]</strong>
                                    </div>
                                </div>

                                <div class="d-flex gap-2 border-top pt-3" v-if="u.id != currentUserId">
                                    <button class="btn btn-sm btn-outline-dark" @click="resetPw(u)">
                                        <i class="bi bi-key"></i> PW Reset
                                    </button>
                                    
                                    <button class="btn btn-sm" :class="u.is_active ? 'btn-outline-warning' : 'btn-outline-success'" @click="toggleActive(u)">
                                        <i class="bi" :class="u.is_active ? 'bi-pause-circle' : 'bi-play-circle'"></i>
                                        [[ u.is_active ? 'Deaktivieren' : 'Aktivieren' ]]
                                    </button>

                                    <button class="btn btn-sm btn-outline-danger ms-auto" @click="deleteUser(u)">
                                        <i class="bi bi-trash"></i> Löschen
                                    </button>
                                </div>
                                <div v-else class="text-muted small text-center fst-italic">
                                    Du kannst dich nicht selbst bearbeiten.
                                </div>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-5">
            <div class="widget-card h-100">
                <div class="widget-header">🎄 Feiertage</div>
                <div class="widget-body">
                    <form @submit.prevent="addHoliday" class="d-flex gap-2 mb-3">
                        <input type="date" class="form-control form-control-sm" v-model="newHoliday.date" required>
                        <input type="text" class="form-control form-control-sm" v-model="newHoliday.name" placeholder="Name" required>
                        <button type="submit" class="btn btn-sm btn-success">+</button>
                    </form>
                    
                    <div class="table-responsive" style="max-height: 400px;">
                        <table class="table table-sm table-striped align-middle small mb-0">
                            <tbody>
                                <tr v-for="h in holidays" :key="h.id">
                                    <td class="font-monospace">[[ h.date_str ]]</td>
                                    <td>[[ h.name ]]</td>
                                    <td class="text-end">
                                        <button class="btn btn-link text-danger p-0" @click="deleteHoliday(h.id)"><i class="bi bi-x-lg"></i></button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="createUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title">Neuen User anlegen</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label>Username</label>
                        <input type="text" class="form-control" v-model="newUser.username">
                    </div>
                    <div class="mb-3">
                        <label>Initial Passwort</label>
                        <input type="text" class="form-control" v-model="newUser.password">
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" v-model="newUser.is_admin">
                        <label class="form-check-label">Administrator Rechte</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-primary" @click="createUser">Anlegen</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const { createApp } = Vue;
createApp({
    delimiters: ['[[', ']]'],
    data() {
        return {
            currentUserId: <?= $_SESSION['user_id'] ?>,
            users: <?= json_encode($users) ?>,
            holidays: <?= json_encode($holidays) ?>,
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
                alert(`Neues Passwort für ${u.username}: \n\n${res.data.new_password}`);
                u.pw_last_changed = new Date().toISOString(); 
            } catch(e) { alert("Fehler"); }
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
</script>