<?php
/**
 * Renters Dashboard Page
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/install-check.php';
require_once __DIR__ . '/../includes/auth.php';

requireInstallation();
requireLogin();

$currentUser = getCurrentUser();
$propertyId = $_GET['property_id'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Renters - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: #f8f9fa;
        }
        .navbar {
            background: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .renter-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .renter-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .status-badge {
            font-size: 0.75rem;
        }
        .filter-section {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light mb-4">
        <div class="container">
            <a class="navbar-brand fw-bold" href="<?php echo BASE_URL; ?>">
                <i class="bi bi-building"></i> <?php echo APP_NAME; ?>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo BASE_URL; ?>dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo BASE_URL; ?>properties.php">Properties</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="<?php echo BASE_URL; ?>renters.php">Renters</a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($currentUser['first_name']); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="#" onclick="logout()">Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0">Renters</h2>
        </div>

        <!-- Filter Section -->
        <div class="filter-section">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Filter by Property</label>
                    <select class="form-select" id="propertyFilter" onchange="filterRenters()">
                        <option value="">All Properties</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Filter by Status</label>
                    <select class="form-select" id="statusFilter" onchange="filterRenters()">
                        <option value="">All Status</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                        <option value="moved_out">Moved Out</option>
                    </select>
                </div>
            </div>
        </div>

        <div id="renters-container">
            <div class="text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Renter Modal -->
    <div class="modal fade" id="addRenterModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Renter</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addRenterForm">
                        <div class="mb-3">
                            <label class="form-label">Property *</label>
                            <select class="form-select" name="property_id" required id="renterPropertySelect">
                                <option value="">Select property...</option>
                            </select>
                        </div>
                        <div class="mb-3" id="renterRoomField" style="display: none;">
                            <label class="form-label">Room (Optional)</label>
                            <select class="form-select" name="room_id" id="renterRoomSelect">
                                <option value="">Select room...</option>
                            </select>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Name *</label>
                                <input type="text" class="form-control" name="name" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Mobile *</label>
                                <input type="tel" class="form-control" name="mobile" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email (Optional)</label>
                            <input type="email" class="form-control" name="email">
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Rental Amount</label>
                                <div class="input-group">
                                    <span class="input-group-text">₹</span>
                                    <input type="number" class="form-control" name="rental_amount" min="0" step="0.01" value="0">
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Rental Start Date</label>
                                <input type="date" class="form-control" name="rental_start_date">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Notes (Optional)</label>
                            <textarea class="form-control" name="notes" rows="2"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="submitAddRenter()">Add Renter</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Notification Modal -->
    <div class="modal fade" id="notificationModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Send Notification</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="notificationForm">
                        <input type="hidden" name="renter_id" id="notificationRenterId">
                        <div class="mb-3">
                            <label class="form-label">Title *</label>
                            <input type="text" class="form-control" name="title" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Message *</label>
                            <textarea class="form-control" name="message" rows="4" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Type</label>
                            <select class="form-select" name="type">
                                <option value="info">Info</option>
                                <option value="warning">Warning</option>
                                <option value="important">Important</option>
                                <option value="payment">Payment</option>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="sendNotification()">Send Notification</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const baseUrl = '<?php echo BASE_URL; ?>';
        let renters = [];
        let properties = [];
        let filteredRenters = [];

        async function loadProperties() {
            try {
                const response = await fetch(baseUrl + 'api/properties/list.php');
                const data = await response.json();
                if (data.success) {
                    properties = data.properties;
                    
                    // Populate property filter
                    const filterSelect = document.getElementById('propertyFilter');
                    const renterSelect = document.getElementById('renterPropertySelect');
                    
                    data.properties.forEach(prop => {
                        const option1 = document.createElement('option');
                        option1.value = prop.id;
                        option1.textContent = prop.name;
                        if (prop.id == <?php echo $propertyId ?: 'null'; ?>) {
                            option1.selected = true;
                        }
                        filterSelect.appendChild(option1);
                        
                        const option2 = document.createElement('option');
                        option2.value = prop.id;
                        option2.textContent = prop.name;
                        renterSelect.appendChild(option2);
                    });
                }
            } catch (error) {
                console.error('Error loading properties:', error);
            }
        }

        async function loadRenters() {
            try {
                const propertyId = document.getElementById('propertyFilter').value;
                const url = propertyId 
                    ? `${baseUrl}api/renters/list.php?property_id=${propertyId}`
                    : `${baseUrl}api/renters/list.php`;
                    
                const response = await fetch(url);
                const data = await response.json();
                if (data.success) {
                    renters = data.renters;
                    filteredRenters = renters;
                    filterRenters();
                }
            } catch (error) {
                console.error('Error loading renters:', error);
                showAlert('Failed to load renters', 'danger');
            }
        }

        function filterRenters() {
            const statusFilter = document.getElementById('statusFilter').value;
            filteredRenters = renters.filter(renter => {
                if (statusFilter && renter.status !== statusFilter) return false;
                return true;
            });
            renderRenters();
        }

        function renderRenters() {
            const container = document.getElementById('renters-container');
            
            if (filteredRenters.length === 0) {
                container.innerHTML = `
                    <div class="text-center py-5">
                        <i class="bi bi-people" style="font-size: 4rem; color: #ccc;"></i>
                        <h4 class="mt-3 text-muted">No renters found</h4>
                        <p class="text-muted">Add your first renter to get started</p>
                        <button class="btn btn-primary" onclick="showAddRenterModal()">
                            <i class="bi bi-person-plus"></i> Add Renter
                        </button>
                    </div>
                `;
                return;
            }

            container.innerHTML = filteredRenters.map(renter => `
                <div class="renter-card">
                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                        <div class="flex-grow-1">
                            <h5 class="mb-1">${escapeHtml(renter.name)}</h5>
                            <div class="d-flex flex-wrap gap-3 small text-muted mb-2">
                                <span><i class="bi bi-telephone"></i> ${escapeHtml(renter.mobile)}</span>
                                ${renter.email ? `<span><i class="bi bi-envelope"></i> ${escapeHtml(renter.email)}</span>` : ''}
                                ${renter.property_name ? `<span><i class="bi bi-building"></i> ${escapeHtml(renter.property_name)}</span>` : ''}
                                ${renter.room_number ? `<span><i class="bi bi-door-open"></i> Room ${escapeHtml(renter.room_number)}</span>` : ''}
                            </div>
                            <div class="d-flex gap-2 flex-wrap">
                                <span class="badge bg-${renter.status === 'active' ? 'success' : renter.status === 'moved_out' ? 'secondary' : 'warning'} status-badge">${renter.status}</span>
                                ${renter.has_app_account ? '<span class="badge bg-info status-badge"><i class="bi bi-check-circle"></i> Has App</span>' : '<span class="badge bg-secondary status-badge">No App</span>'}
                                ${renter.rental_amount > 0 ? `<span class="badge bg-primary status-badge"><i class="bi bi-currency-rupee"></i> ₹${renter.rental_amount}</span>` : ''}
                            </div>
                        </div>
                        <div class="btn-group-vertical btn-group-sm">
                            ${!renter.has_app_account ? `<button class="btn btn-outline-primary" onclick="inviteRenter(${renter.id})" title="Invite to App">
                                <i class="bi bi-envelope-paper"></i> Invite
                            </button>` : ''}
                            <button class="btn btn-outline-info" onclick="showNotificationModal(${renter.id})" title="Send Notification">
                                <i class="bi bi-bell"></i> Notify
                            </button>
                            <button class="btn btn-outline-secondary" onclick="viewRenterDetails(${renter.id})" title="View Details">
                                <i class="bi bi-eye"></i> View
                            </button>
                        </div>
                    </div>
                </div>
            `).join('');
        }

        function showAddRenterModal() {
            document.getElementById('addRenterForm').reset();
            document.getElementById('renterRoomField').style.display = 'none';
            new bootstrap.Modal(document.getElementById('addRenterModal')).show();
        }

        document.getElementById('renterPropertySelect').addEventListener('change', async function() {
            const propertyId = this.value;
            const roomField = document.getElementById('renterRoomField');
            const roomSelect = document.getElementById('renterRoomSelect');
            
            if (!propertyId) {
                roomField.style.display = 'none';
                return;
            }
            
            // Check if property has rooms
            const property = properties.find(p => p.id == propertyId);
            if (property && property.total_rooms > 0) {
                roomField.style.display = 'block';
                // Load rooms for this property
                try {
                    const response = await fetch(`${baseUrl}api/properties/rooms.php?property_id=${propertyId}`);
                    const data = await response.json();
                    if (data.success) {
                        roomSelect.innerHTML = '<option value="">Select room...</option>';
                        data.rooms.forEach(room => {
                            const option = document.createElement('option');
                            option.value = room.id;
                            option.textContent = `Room ${room.room_number}${room.name ? ` - ${room.name}` : ''}`;
                            roomSelect.appendChild(option);
                        });
                    }
                } catch (error) {
                    console.error('Error loading rooms:', error);
                }
            } else {
                roomField.style.display = 'none';
            }
        });

        async function submitAddRenter() {
            const form = document.getElementById('addRenterForm');
            const formData = new FormData(form);
            const data = Object.fromEntries(formData);
            data.rental_amount = parseFloat(data.rental_amount) || 0;
            if (!data.room_id) data.room_id = null;

            try {
                const response = await fetch(baseUrl + 'api/renters/create.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });

                const result = await response.json();
                if (result.success) {
                    bootstrap.Modal.getInstance(document.getElementById('addRenterModal')).hide();
                    showAlert('Renter added successfully', 'success');
                    loadRenters();
                } else {
                    showAlert(result.message || 'Failed to add renter', 'danger');
                }
            } catch (error) {
                showAlert('Failed to add renter', 'danger');
                console.error('Error:', error);
            }
        }

        async function inviteRenter(renterId) {
            if (!confirm('Send invitation to this renter to join the app?')) return;

            try {
                const response = await fetch(baseUrl + 'api/renters/invite.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ renter_id: renterId })
                });

                const result = await response.json();
                if (result.success) {
                    showAlert('Invitation sent successfully', 'success');
                    loadRenters();
                } else {
                    showAlert(result.message || 'Failed to send invitation', 'danger');
                }
            } catch (error) {
                showAlert('Failed to send invitation', 'danger');
                console.error('Error:', error);
            }
        }

        function showNotificationModal(renterId) {
            document.getElementById('notificationRenterId').value = renterId;
            document.getElementById('notificationForm').reset();
            new bootstrap.Modal(document.getElementById('notificationModal')).show();
        }

        async function sendNotification() {
            const form = document.getElementById('notificationForm');
            const formData = new FormData(form);
            const data = Object.fromEntries(formData);

            try {
                const response = await fetch(baseUrl + 'api/renters/notify.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });

                const result = await response.json();
                if (result.success) {
                    bootstrap.Modal.getInstance(document.getElementById('notificationModal')).hide();
                    showAlert('Notification sent successfully', 'success');
                } else {
                    showAlert(result.message || 'Failed to send notification', 'danger');
                }
            } catch (error) {
                showAlert('Failed to send notification', 'danger');
                console.error('Error:', error);
            }
        }

        function viewRenterDetails(renterId) {
            // TODO: Implement renter details view
            showAlert('Renter details view coming soon', 'info');
        }

        function showAlert(message, type) {
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3`;
            alertDiv.style.zIndex = '9999';
            alertDiv.innerHTML = `${message}<button type="button" class="btn-close" data-bs-dismiss="alert"></button>`;
            document.body.appendChild(alertDiv);
            setTimeout(() => alertDiv.remove(), 5000);
        }

        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        async function logout() {
            try {
                const response = await fetch(baseUrl + 'api/auth/logout.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' }
                });
                const data = await response.json();
                if (data.success) {
                    window.location.href = baseUrl;
                }
            } catch (error) {
                console.error('Logout error:', error);
            }
        }

        // Initialize
        loadProperties();
        loadRenters();
    </script>
</body>
</html>

