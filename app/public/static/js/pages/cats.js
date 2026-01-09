const { createApp } = Vue;

createApp({
    delimiters: ['[[', ']]'], // WICHTIG für PHP Templates
    data() {
        return {
            projects: [],
            allUsers: [], 
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
                end_date: new Date(new Date().getFullYear(), 11, 31).toISOString().slice(0, 10),
                yearly_budget_hours: 0
            },
            newAllocation: {
                user_id: null,
                share_weight: 1.0,
                joined_at: new Date().toISOString().slice(0, 10),
                left_at: null
            },
            
            // UI State
            isEditingAllocation: false, // Damit wir wissen, ob wir editieren oder neu anlegen
            
            // Bootstrap Modal Instanzen
            projectModalInstance: null,
            allocationModalInstance: null
        }
    },
    computed: {
        budgetColor() {
            if (!this.currentProject) return '';
            const left = this.currentProject.budget_left;
            if (left < 0) return 'text-danger'; // oder 'cats-budget-crit' aus custom.css
            if (left < 20) return 'text-warning';
            return 'text-success';
        }
    },
    mounted() {
        this.loadProjects();
        this.loadAllUsers();
        
        // Modal Instanzen initialisieren (Safety Check, falls HTML noch lädt)
        const pModalEl = document.getElementById('projectModal');
        const aModalEl = document.getElementById('allocationModal');
        
        if (pModalEl) this.projectModalInstance = new bootstrap.Modal(pModalEl);
        if (aModalEl) this.allocationModalInstance = new bootstrap.Modal(aModalEl);
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
                // Holt simple User-Liste (ID, Username) für Dropdowns
                const res = await axios.get('/api/cats/users'); 
                this.allUsers = res.data;
            } catch (e) {
                console.warn("Konnte User-Liste nicht laden.", e);
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

        // --- BUCHUNGEN ---
        isEligible(user, monthInt) {
            const m = this.pad(monthInt);
            return user.monthly_data[m] && user.monthly_data[m].is_eligible; 
        },

        async saveBooking(user, monthInt) {
            const mStr = `${this.year}-${this.pad(monthInt)}`;
            // Falls Input leer/invalid ist, nehmen wir 0
            let val = user.monthly_data[this.pad(monthInt)].used;
            const hours = val === '' ? 0 : parseFloat(val);

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

        // --- PROJEKT STEUERUNG ---
        openNewProjectModal() {
            this.projectModalInstance.show();
        },
        async createProject() {
            this.loading = true;
            try {
                const res = await axios.post('/api/cats/project', this.newProject);
                this.projectModalInstance.hide();
                
                // Form Reset
                this.newProject.psp_element = '';
                this.newProject.customer_name = '';
                // ... (weitere Felder bei Bedarf resetten)

                await this.loadProjects();
                this.selectedProjectId = res.data.id;
                this.loadProject();
            } catch (e) {
                alert("Fehler: " + e.message);
            } finally {
                this.loading = false;
            }
        },
        
        async deleteProject() {
            if (!this.currentProject) return;
            const confirmMsg = `ACHTUNG: Soll das Projekt "${this.currentProject.project_info.customer_name}" wirklich gelöscht werden?\n\nAlle Zuweisungen und gebuchten Stunden werden unwiderruflich entfernt!`;
            
            if (!confirm(confirmMsg)) return;
            
            this.loading = true;
            try {
                await axios.delete(`/api/cats/project/${this.selectedProjectId}`);
                this.currentProject = null;
                this.selectedProjectId = null;
                await this.loadProjects();
            } catch (e) {
                alert("Fehler beim Löschen: " + e.message);
            } finally {
                this.loading = false;
            }
        },

        // --- TEAM / ALLOCATION ---
        openAllocationModal() {
            // Reset für "Neu"
            this.isEditingAllocation = false;
            this.newAllocation = {
                user_id: null,
                share_weight: 1.0,
                joined_at: this.currentProject?.project_info?.start_date || new Date().toISOString().slice(0, 10),
                left_at: null
            };
            this.allocationModalInstance.show();
        },
        
        openEditAllocation(user) {
            // Modus "Bearbeiten"
            this.isEditingAllocation = true;
            this.newAllocation = {
                user_id: user.user_id,
                share_weight: user.share_weight,
                joined_at: user.joined_at,
                left_at: user.left_at
            };
            this.allocationModalInstance.show();
        },

        async addAllocation() {
            if (!this.newAllocation.user_id) return;
            try {
                // Upsert Logik im Backend (Insert or Update)
                await axios.post('/api/cats/allocation', {
                    project_id: this.selectedProjectId,
                    ...this.newAllocation
                });
                
                this.allocationModalInstance.hide();
                await this.loadProject(); // Reload nötig für Neuberechnung der Tabelle
            } catch (e) {
                alert("Fehler beim Speichern: " + e.message);
            }
        },

        async removeAllocation(user) {
            if(!confirm(`Soll ${user.username} wirklich aus dem Projekt entfernt werden?`)) return;
            try {
                await axios.delete('/api/cats/allocation', {
                    data: { 
                        project_id: this.selectedProjectId,
                        user_id: user.user_id
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
            // Client-Side Recalculation für flüssiges UI beim Tippen
            let totalUsed = 0;
            this.currentProject.team_stats.forEach(user => {
                let userSum = 0;
                Object.values(user.monthly_data).forEach(d => userSum += (parseFloat(d.used) || 0));
                user.used_total = userSum;
                totalUsed += userSum;
            });
            this.currentProject.budget_used = totalUsed;
            this.currentProject.budget_left = this.currentProject.project_info.yearly_budget_hours - totalUsed;
        }
    }
});