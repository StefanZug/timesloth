<div id="app" class="container-fluid px-3 px-xl-5 mt-3 mb-5" v-cloak>

    <div class="mobile-only-switcher text-center mb-3">
        <div class="view-switcher shadow-sm">
            <button class="view-btn" :class="{active: viewMode === 'day'}" @click="viewMode = 'day'"><i class="bi bi-calendar-day"></i> Tag</button>
            <button class="view-btn" :class="{active: viewMode === 'month'}" @click="viewMode = 'month'"><i class="bi bi-table"></i> Monat</button>
        </div>
    </div>

    <div class="row g-4">
        
        <div class="col-12 col-lg-3 order-1 order-lg-1 sticky-column" v-show="isDesktop || viewMode === 'day'">
            <?php include __DIR__ . '/partials/dashboard/day_view.php'; ?>
        </div>

        <div class="col-12 col-lg-6 order-1 order-lg-2" v-show="isDesktop || viewMode === 'month'">
            <?php include __DIR__ . '/partials/dashboard/month_table.php'; ?>
        </div>

        <div class="col-12 col-lg-3 order-2 order-lg-3 sticky-column">
            <?php include __DIR__ . '/partials/dashboard/stats_sidebar.php'; ?>
        </div>
    </div>

    <div class="save-indicator-container">
        <transition name="fade">
            <div v-if="saveState === 'saved'" class="bg-success text-white rounded-circle shadow d-flex align-items-center justify-content-center save-blob"><i class="bi bi-check-lg fs-5"></i></div>
        </transition>
        <div v-if="saveState === 'saving'" class="bg-warning text-white rounded-circle shadow d-flex align-items-center justify-content-center save-blob"><div class="spinner-border spinner-border-sm"></div></div>
    </div>

    <?php include __DIR__ . '/partials/dashboard/modals.php'; ?>

</div>

<script>
    window.slothData = {
        settings: <?= $user['settings'] ?: '{}' ?>
    };
</script>
<script src="/static/js/core/TimeLogic.js"></script>
<script src="/static/js/pages/dashboard.js"></script>