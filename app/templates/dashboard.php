<style>
    /* CSS Styles von vorhin hier einfügen oder in base.php verschieben */
    /* ... (CSS hier der Übersicht halber gekürzt, ist aber identisch) ... */
    .sticky-header { top: 58px; z-index: 900; background-color: var(--bs-body-bg); padding-top: 15px; }
</style>

<div id="app" class="container mt-3 mb-5" v-cloak>
    <div class="sticky-header">
        <div class="quota-card mb-3">
             <div class="d-flex justify-content-between align-items-center mb-1">
                <span class="text-muted small fw-bold text-uppercase">Büro-Quote (40%)</span>
                <span class="text-danger fw-bold fs-5">[[ quota.needed ]] h</span>
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
                currentDateObj: new Date(),
                viewMode: localStorage.getItem('viewMode') || 'day',
                dayStatus: null, 
                blocks: [], 
                entriesCache: [],
                holidaysMap: {},
                saveState: 'idle', 
                saveTimer: null,
                // Hier laden wir die Settings aus PHP in JS
                settings: {
                    sollStunden: 7.70,
                    deductionPerDay: 3.08,
                    arztStart: 480, arztEnde: 972,
                    ...<?= $user['settings'] ?? '{}' ?> // PHP Injection!
                }
            }
        },
        // ... REST DES VUE JS CODES 1:1 KOPIEREN ...
        // Methoden, Computed, Watch etc. alles identisch.
        // Nur aufpassen: url_for gibt es nicht mehr.
        // Falls du axios calls hast: '/api/save_entry' bleibt gleich.
    }).mount('#app');
</script>