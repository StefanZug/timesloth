<div class="container mt-4">
    <div class="row">
        <div class="col-md-6 mb-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <h4 class="card-title mb-3">🦖 User Verwaltung</h4>
                    
                    <form id="createUserForm" class="row g-2 align-items-center form-box-admin">
                        <div class="col-6">
                            <input type="text" class="form-control" id="newUsername" placeholder="Username" required>
                        </div>
                        <div class="col-6">
                            <input type="password" class="form-control" id="newPassword" placeholder="Passwort" required>
                        </div>
                        <div class="col-12 d-flex justify-content-between align-items-center mt-2">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="isAdmin">
                                <label class="form-check-label" for="isAdmin">Admin?</label>
                            </div>
                            <button type="submit" class="btn btn-sm btn-primary">Anlegen</button>
                        </div>
                    </form>
    
                    <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                        <table class="table table-sm align-middle">
                            <thead><tr><th>User</th><th>Rolle</th><th>Action</th></tr></thead>
                            <tbody>
                                <?php foreach($users as $u): ?>
                                <tr>
                                    <td><?= htmlspecialchars($u['username']) ?></td>
                                    <td><?= $u['is_admin'] ? 'Admin' : 'User' ?></td>
                                    <td>
                                        <?php if($u['id'] != $user['id']): ?>
                                        <button class="btn btn-xs btn-outline-danger" onclick="deleteUser(<?= $u['id'] ?>, '<?= htmlspecialchars($u['username']) ?>')">x</button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
                                        
        <div class="col-md-6 mb-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <h4 class="card-title mb-3">🎄 Feiertage</h4>
                                        
                    <form id="createHolidayForm" class="row g-2 align-items-center form-box-admin">
                        <div class="col-5">
                            <input type="date" class="form-control" id="holDate" required>
                        </div>
                        <div class="col-5">
                            <input type="text" class="form-control" id="holName" placeholder="Name (z.B. Weihnachten)" required>
                        </div>
                        <div class="col-2">
                            <button type="submit" class="btn btn-success w-100">+</button>
                        </div>
                    </form>
                                        
                    <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                        <table class="table table-sm align-middle table-striped">
                            <thead><tr><th>Datum</th><th>Name</th><th></th></tr></thead>
                            <tbody>
                                <?php foreach($holidays as $h): ?>
                                <tr>
                                    <td><?= htmlspecialchars($h['date_str']) ?></td>
                                    <td><?= htmlspecialchars($h['name']) ?></td>
                                    <td class="text-end">
                                        <button class="btn btn-xs btn-outline-danger" onclick="deleteHoliday(<?= $h['id'] ?>)">x</button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    // --- USER LOGIK ---
    document.getElementById('createUserForm').addEventListener('submit', function(e) {
        e.preventDefault();
        axios.post('/admin/create_user', {
            username: document.getElementById('newUsername').value,
            password: document.getElementById('newPassword').value,
            is_admin: document.getElementById('isAdmin').checked
        }).then(() => location.reload()).catch(err => alert(err.response.data.error || "Fehler"));
    });

    function deleteUser(id, name) {
        if(confirm(`User ${name} löschen?`)) {
            axios.post(`/admin/delete_user/${id}`).then(() => location.reload());
        }
    }

    // --- FEIERTAGE LOGIK ---
    document.getElementById('createHolidayForm').addEventListener('submit', function(e) {
        e.preventDefault();
        axios.post('/admin/holiday', {
            date: document.getElementById('holDate').value,
            name: document.getElementById('holName').value
        }).then(() => location.reload()).catch(err => alert(err.response.data.error || "Fehler"));
    });

    function deleteHoliday(id) {
        if(confirm("Feiertag wirklich löschen?")) {
            axios.delete(`/admin/holiday/${id}`).then(() => location.reload());
        }
    }
</script>