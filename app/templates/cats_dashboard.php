<div id="cats-app" class="container-fluid px-3 px-xl-5 mt-3 mb-5" v-cloak>
    
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">
            <i class="bi bi-github text-warning me-2"></i>CATSloth
        </h1>
        
        <div class="btn-toolbar mb-2 mb-md-0">
            <select class="form-select me-2" v-model="selectedProjectId" @change="loadProject">
                <option :value="null" disabled>Projekt wählen...</option>
                <option v-for="p in projects" :key="p.id" :value="p.id">
                    [[ p.psp_element ]] - [[ p.customer_name ]]
                </option>
            </select>
            
            <button class="btn btn-sm btn-outline-secondary" @click="openNewProjectModal">
                <i class="bi bi-plus-lg"></i> Neues Projekt
            </button>
        </div>
    </div>

    <div v-if="currentProject" class="row">
        
        <div class="col-md-12 mb-4">
            <div class="card shadow-sm border-warning border-opacity-25">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="card-title mb-0">[[ currentProject.project_info.customer_name ]]</h5>
                        <small class="text-muted">[[ currentProject.project_info.task_name ]] ([[ currentProject.project_info.psp_element ]])</small>
                    </div>
                    <div class="text-end">
                        <span class="d-block h4 mb-0" :class="budgetColor">
                            [[ formatNumber(currentProject.budget_left) ]] h
                        </span>
                        <small class="text-muted">Verfügbar (Gesamt: [[ formatNumber(currentProject.budget_yearly) ]] h)</small>
                    </div>
                    <div>
                         <button class="btn btn-sm btn-outline-primary" @click="openAllocationModal">
                            <i class="bi bi-people"></i> Team
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-12">
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle text-center bg-white shadow-sm" style="min-width: 1200px;">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 150px;" class="text-start">Mitarbeiter</th>
                            <th style="width: 80px;">Gewicht</th>
                            <th v-for="m in 12" :key="m" style="width: 70px;">[[ monthName(m) ]]</th>
                            <th style="width: 90px;" class="bg-light">Summe</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="user in currentProject.team_stats" :key="user.user_id">
                            <td class="text-start fw-bold">
                                [[ user.username ]]
                                <i v-if="!user.is_active" class="bi bi-person-x text-danger" title="Inaktiv"></i>
                            </td>
                            <td>
                                <span class="badge bg-secondary rounded-pill">[[ user.share_weight ]]</span>
                            </td>
                            
                            <td v-for="m in 12" :key="m" :class="{'bg-light': !isEligible(user, m)}">
                                <div v-if="isEligible(user, m)">
                                    <input 
                                        type="number" 
                                        class="form-control form-control-sm text-center p-0 border-0 bg-transparent fw-bold"
                                        v-model.lazy="user.monthly_data[pad(m)].used"
                                        @change="saveBooking(user, m)"
                                        step="0.25"
                                        placeholder="-"
                                    >
                                </div>
                                <div v-else class="text-muted text-opacity-25 small">
                                    <i class="bi bi-slash-circle" style="font-size: 0.7em;"></i>
                                </div>
                            </td>

                            <td class="fw-bold bg-light">[[ formatNumber(user.used_total) ]]</td>
                        </tr>
                        
                        <tr class="table-secondary fw-bold border-top-2">
                            <td class="text-start">Gesamt</td>
                            <td>-</td>
                            <td v-for="m in 12" :key="'sum'+m">
                                [[ formatNumber(monthlySum(m)) ]]
                            </td>
                            <td>[[ formatNumber(currentProject.budget_used) ]]</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div v-else class="text-center py-5 text-muted">
        <div class="mb-3">
            <i class="bi bi-github display-1 text-secondary opacity-25"></i>
        </div>
        <h4>Wähle ein Projekt aus</h4>
        <p>Benutze das Dropdown oben rechts oder erstelle ein neues Projekt.</p>
    </div>

    </div>

<script src="/static/js/pages/cats.js"></script>