const { createApp } = Vue;

createApp({
    delimiters: ['[[', ']]'], // WICHTIG für PHP Templates
    data() {
        return {
            projects: [],
            allUsers: [], // Für das Dropdown im Modal
            selectedProjectId: null,
            currentProject: null,
            year: new Date().getFullYear(),
            loading: false,
            
            // Models für Modals
            newProject: {
                psp_element: '',
                customer_name: '',
                task_name: '',
                subtask: '',
                info: '',
                start_date: new Date().toISOString().slice(0, 10),
                end_date: new Date(new Date().getFullYear(), 11, 31).toISOString().slice(0, 10), // Ende des Jahres
                yearly_budget_hours: 0
            },
            newAllocation: {
                user_id: null,
                share_weight: 1.0,
                joined_at: new Date().toISOString().slice(0, 10)
            },
            
            // Bootstrap Modal Instanzen
            projectModalInstance: null,
            allocationModalInstance: null
        }
    },
    computed: {
        budgetColor() {
            if (!this.currentProject) return '';
            const left = this.currentProject.budget_left;
            if (left < 0) return 'text-danger';
            if (left < 20) return 'text-warning';
            return 'text-success';
        }
    },
    mounted() {
        this.loadProjects();
        this.loadAllUsers(); // Laden wir im Hintergrund für das Modal
        
        // Modal Instanzen initialisieren
        this.projectModalInstance = new bootstrap.Modal(document.getElementById('projectModal'));
        this.allocationModalInstance = new bootstrap.Modal(document.getElementById('allocationModal'));
    },
    methods: {
        async loadProjects() {
            try {
                const res = await axios.get('/api/cats/projects');
                this.projects = res.data;
            } catch (e) {
                console.error("Fehler beim Laden der Projekte", e);
            }
        },

        async loadAllUsers() {
            try {
                // HIER ist die Zeile, die angepasst werden muss:
                const res = await axios.get('/api/cats/users'); 
                
                this.allUsers = res.data;
            } catch (e) {
                console.warn("Konnte User-Liste nicht laden.");
            }
        },

        async loadProject() {
            if (!this.selectedProjectId) return;
            this.loading = true;
            try {
                const res = await axios.get(`/api/cats/project/${this.selectedProjectId}?year=${this.year}`);
                this.currentProject = res.data;
            } catch (e) {
                alert("Fehler beim Laden: " + (e.response?.data?.error || e.message));
            } finally {
                this.loading = false;
            }
        },

        isEligible(user, monthInt) {
            const m = this.pad(monthInt);
            return user.monthly_data[m] && user.monthly_data[m].is_eligible; 
        },

        async saveBooking(user, monthInt) {
            const mStr = `${this.year}-${this.pad(monthInt)}`;
            const hours = parseFloat(user.monthly_data[this.pad(monthInt)].used) || 0;

            try {
                await axios.post('/api/cats/booking', {
                    project_id: this.selectedProjectId,
                    user_id: user.user_id,
                    month: mStr,
                    hours: hours
                });
                this.recalcTotals(); 
            } catch (e) {
                alert("Fehler beim Speichern: " + e.message);
            }
        },

        // --- PROJEKT ERSTELLEN ---
        openNewProjectModal() {
            this.projectModalInstance.show();
        },
        async createProject() {
            this.loading = true;
            try {
                const res = await axios.post('/api/cats/project', this.newProject);
                this.projectModalInstance.hide();
                // Reset Form
                this.newProject.psp_element = '';
                this.newProject.customer_name = '';
                // Reload & Select
                await this.loadProjects();
                this.selectedProjectId = res.data.id;
                this.loadProject();
            } catch (e) {
                alert("Fehler: " + e.message);
            } finally {
                this.loading = false;
            }
        },

        // --- TEAM MANAGEMENT ---
        openAllocationModal() {
            // User-Liste laden falls noch nicht geschehen
            if(this.allUsers.length === 0) this.loadAllUsers();
            this.allocationModalInstance.show();
        },
        async addAllocation() {
            if (!this.newAllocation.user_id) return;
            try {
                await axios.post('/api/cats/allocation', {
                    project_id: this.selectedProjectId,
                    ...this.newAllocation
                });
                // Reload Project Data (um die Tabelle im Hintergrund zu aktualisieren)
                await this.loadProject();
                // Reset Inputs (aber Datum behalten)
                this.newAllocation.user_id = null;
                this.newAllocation.share_weight = 1.0;
            } catch (e) {
                alert("Fehler beim Hinzufügen: " + e.message);
            }
        },
        async updateAllocation(member) {
            try {
                await axios.post('/api/cats/allocation', {
                    project_id: this.selectedProjectId,
                    user_id: member.user_id,
                    share_weight: member.share_weight,
                    joined_at: member.joined_at,
                    left_at: member.left_at
                });
                // Kein Full Reload nötig, UI ist schon aktuell durch v-model
                this.recalcTotals(); // Trigger Neuberechnung Budget
            } catch (e) {
                alert("Fehler beim Update: " + e.message);
            }
        },
        async removeAllocation(member) {
            if(!confirm(`Soll ${member.username} wirklich aus dem Projekt entfernt werden?`)) return;
            try {
                await axios.delete('/api/cats/allocation', {
                    data: { // Axios DELETE body workaround
                        project_id: this.selectedProjectId,
                        user_id: member.user_id
                    }
                });
                await this.loadProject();
            } catch (e) {
                alert("Fehler beim Entfernen: " + e.message);
            }
        },

        // --- HELPER ---
        pad(n) { return n.toString().padStart(2, '0'); },
        formatNumber(val) { return val ? parseFloat(val).toFixed(2) : '0'; },
        monthName(m) {
            const date = new Date(2000, m - 1, 1);
            return date.toLocaleString('de-DE', { month: 'short' });
        },
        monthlySum(m) {
            if (!this.currentProject) return 0;
            const mKey = this.pad(m);
            return this.currentProject.team_stats.reduce((sum, user) => {
                return sum + (parseFloat(user.monthly_data[mKey]?.used) || 0);
            }, 0);
        },
        recalcTotals() {
            let totalUsed = 0;
            this.currentProject.team_stats.forEach(user => {
                let userSum = 0;
                Object.values(user.monthly_data).forEach(d => userSum += (parseFloat(d.used) || 0));
                user.used_total = userSum;
                totalUsed += userSum;
            });
            this.currentProject.budget_used = totalUsed;
            this.currentProject.budget_left = this.currentProject.budget_yearly - totalUsed;
        }
    }
});