<?php
/**
 * Property Details Page
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/install-check.php';
require_once __DIR__ . '/../includes/auth.php';

requireInstallation();
requireLogin();

$propertyId = $_GET['id'] ?? null;
if (!$propertyId) {
    header('Location: ' . BASE_URL . 'properties.php');
    exit;
}

$currentUser = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Property Details - <?php echo APP_NAME; ?></title>
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
        .property-header {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        .section-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        .room-card, .floor-card {
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
        }
        .btn-floating {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            z-index: 1000;
        }
        @media (max-width: 768px) {
            .btn-floating {
                bottom: 1rem;
                right: 1rem;
                width: 50px;
                height: 50px;
            }
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
                        <a class="nav-link" href="<?php echo BASE_URL; ?>properties.php">Properties</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo BASE_URL; ?>renters.php">Renters</a>
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
        <div id="loading" class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>

        <div id="property-content" style="display: none;">
            <!-- Property Header -->
            <div class="property-header">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <h2 id="property-name" class="mb-2"></h2>
                        <p class="text-muted mb-0" id="property-location"></p>
                    </div>
                    <span class="badge bg-primary" id="property-type-badge"></span>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <button class="btn btn-outline-primary btn-sm" onclick="editPropertyDetails()">
                        <i class="bi bi-pencil"></i> Edit Details
                    </button>
                    <a href="<?php echo BASE_URL; ?>renters.php?property_id=<?php echo $propertyId; ?>" class="btn btn-outline-success btn-sm">
                        <i class="bi bi-people"></i> View Renters
                    </a>
                </div>
            </div>

            <!-- Property Details Section -->
            <div class="section-card">
                <h5 class="mb-3"><i class="bi bi-info-circle"></i> Property Information</h5>
                <div id="property-details-content"></div>
            </div>

            <!-- Floors Section (if property has floors) -->
            <div id="floors-section" class="section-card" style="display: none;">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0"><i class="bi bi-layers"></i> Floors</h5>
                    <button class="btn btn-sm btn-primary" onclick="showAddFloorModal()">
                        <i class="bi bi-plus"></i> Add Floor
                    </button>
                </div>
                <div id="floors-list"></div>
            </div>

            <!-- Rooms Section (if property has rooms) -->
            <div id="rooms-section" class="section-card" style="display: none;">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0"><i class="bi bi-door-open"></i> Rooms</h5>
                    <button class="btn btn-sm btn-primary" onclick="showAddRoomModal()">
                        <i class="bi bi-plus"></i> Add Room
                    </button>
                </div>
                <div id="rooms-list"></div>
            </div>
        </div>
    </div>

    <!-- Floating Action Button -->
    <button class="btn btn-primary btn-floating d-md-none" onclick="scrollToTop()" id="scrollTopBtn" style="display: none;">
        <i class="bi bi-arrow-up"></i>
    </button>

    <!-- Add Floor Modal -->
    <div class="modal fade" id="floorModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="floorModalTitle">Add Floor</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="floorForm">
                        <input type="hidden" name="id" id="floorId">
                        <input type="hidden" name="property_id" value="<?php echo $propertyId; ?>">
                        <div class="mb-3">
                            <label class="form-label">Floor Number *</label>
                            <input type="number" class="form-control" name="floor_number" required min="0">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Floor Name (Optional)</label>
                            <input type="text" class="form-control" name="name" placeholder="e.g., Ground Floor, First Floor">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description (Optional)</label>
                            <textarea class="form-control" name="description" rows="2"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="saveFloor()">Save</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Room Modal -->
    <div class="modal fade" id="roomModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="roomModalTitle">Add Room</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="roomForm">
                        <input type="hidden" name="id" id="roomId">
                        <input type="hidden" name="property_id" value="<?php echo $propertyId; ?>">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Room Number *</label>
                                <input type="text" class="form-control" name="room_number" required placeholder="e.g., 101, A1">
                            </div>
                            <div class="col-md-6 mb-3" id="roomFloorField">
                                <label class="form-label">Floor</label>
                                <select class="form-select" name="floor_id" id="roomFloorSelect">
                                    <option value="">Select floor...</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Room Name (Optional)</label>
                            <input type="text" class="form-control" name="name" placeholder="e.g., Deluxe Room">
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Capacity *</label>
                                <input type="number" class="form-control" name="capacity" required min="1" value="1">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Rental Amount</label>
                                <div class="input-group">
                                    <span class="input-group-text">₹</span>
                                    <input type="number" class="form-control" name="rental_amount" min="0" step="0.01" value="0">
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status">
                                <option value="available">Available</option>
                                <option value="occupied">Occupied</option>
                                <option value="maintenance">Maintenance</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description (Optional)</label>
                            <textarea class="form-control" name="description" rows="2"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="saveRoom()">Save</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const baseUrl = '<?php echo BASE_URL; ?>';
        const propertyId = <?php echo $propertyId; ?>;
        let property = null;
        let floors = [];
        let rooms = [];

        async function loadProperty() {
            try {
                const response = await fetch(`${baseUrl}api/properties/get.php?id=${propertyId}`);
                const data = await response.json();
                if (data.success) {
                    property = data.property;
                    floors = property.floors || [];
                    rooms = property.rooms || [];
                    renderProperty();
                } else {
                    showAlert('Property not found', 'danger');
                    setTimeout(() => window.location.href = baseUrl + 'properties.php', 2000);
                }
            } catch (error) {
                console.error('Error loading property:', error);
                showAlert('Failed to load property', 'danger');
            }
        }

        function renderProperty() {
            document.getElementById('loading').style.display = 'none';
            document.getElementById('property-content').style.display = 'block';

            document.getElementById('property-name').textContent = property.name;
            document.getElementById('property-location').textContent = `${property.city}, ${property.country}`;
            document.getElementById('property-type-badge').textContent = property.property_type_name;

            // Render property details
            let detailsHtml = `
                <div class="row">
                    <div class="col-md-6 mb-2"><strong>Address:</strong> ${property.address || 'Not specified'}</div>
                    <div class="col-md-6 mb-2"><strong>Status:</strong> <span class="badge bg-${property.status === 'active' ? 'success' : 'secondary'}">${property.status}</span></div>
                </div>
            `;
            if (property.description) {
                detailsHtml += `<p class="mt-2">${escapeHtml(property.description)}</p>`;
            }
            document.getElementById('property-details-content').innerHTML = detailsHtml;

            // Show floors section if property has floors
            if (property.has_floors) {
                document.getElementById('floors-section').style.display = 'block';
                renderFloors();
            }

            // Show rooms section if property has rooms
            if (property.has_rooms) {
                document.getElementById('rooms-section').style.display = 'block';
                renderRooms();
                loadFloorsForRooms();
            }
        }

        function renderFloors() {
            const container = document.getElementById('floors-list');
            if (floors.length === 0) {
                container.innerHTML = '<p class="text-muted">No floors added yet. Click "Add Floor" to get started.</p>';
                return;
            }
            container.innerHTML = floors.map(floor => `
                <div class="floor-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-1">Floor ${floor.floor_number}${floor.name ? ` - ${escapeHtml(floor.name)}` : ''}</h6>
                            ${floor.description ? `<p class="text-muted small mb-0">${escapeHtml(floor.description)}</p>` : ''}
                        </div>
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-outline-primary" onclick="editFloor(${floor.id})">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button class="btn btn-outline-danger" onclick="deleteFloor(${floor.id})">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            `).join('');
        }

        function renderRooms() {
            const container = document.getElementById('rooms-list');
            if (rooms.length === 0) {
                container.innerHTML = '<p class="text-muted">No rooms added yet. Click "Add Room" to get started.</p>';
                return;
            }
            container.innerHTML = rooms.map(room => `
                <div class="room-card">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <h6 class="mb-1">
                                Room ${escapeHtml(room.room_number)}
                                ${room.name ? ` - ${escapeHtml(room.name)}` : ''}
                                ${room.floor_name ? ` <small class="text-muted">(Floor ${room.floor_number})</small>` : ''}
                            </h6>
                            <div class="d-flex gap-3 small text-muted mb-2">
                                <span><i class="bi bi-people"></i> ${room.current_occupancy}/${room.capacity} Occupied</span>
                                <span><i class="bi bi-currency-rupee"></i> ₹${room.rental_amount}</span>
                                <span class="badge bg-${room.status === 'available' ? 'success' : room.status === 'occupied' ? 'warning' : 'secondary'}">${room.status}</span>
                            </div>
                            ${room.description ? `<p class="text-muted small mb-0">${escapeHtml(room.description)}</p>` : ''}
                        </div>
                        <div class="btn-group btn-group-sm">
                            <a href="${baseUrl}room-details.php?room_id=${room.id}" class="btn btn-outline-primary">
                                <i class="bi bi-eye"></i>
                            </a>
                            <button class="btn btn-outline-primary" onclick="editRoom(${room.id})">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button class="btn btn-outline-danger" onclick="deleteRoom(${room.id})">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            `).join('');
        }

        async function loadFloorsForRooms() {
            try {
                const response = await fetch(`${baseUrl}api/properties/floors.php?property_id=${propertyId}`);
                const data = await response.json();
                if (data.success) {
                    const select = document.getElementById('roomFloorSelect');
                    select.innerHTML = '<option value="">Select floor...</option>';
                    data.floors.forEach(floor => {
                        const option = document.createElement('option');
                        option.value = floor.id;
                        option.textContent = `Floor ${floor.floor_number}${floor.name ? ` - ${floor.name}` : ''}`;
                        select.appendChild(option);
                    });
                }
            } catch (error) {
                console.error('Error loading floors:', error);
            }
        }

        function showAddFloorModal() {
            document.getElementById('floorModalTitle').textContent = 'Add Floor';
            document.getElementById('floorForm').reset();
            document.getElementById('floorId').value = '';
            new bootstrap.Modal(document.getElementById('floorModal')).show();
        }

        function editFloor(floorId) {
            const floor = floors.find(f => f.id == floorId);
            if (!floor) return;
            
            document.getElementById('floorModalTitle').textContent = 'Edit Floor';
            document.getElementById('floorId').value = floor.id;
            document.querySelector('#floorForm [name="floor_number"]').value = floor.floor_number;
            document.querySelector('#floorForm [name="name"]').value = floor.name || '';
            document.querySelector('#floorForm [name="description"]').value = floor.description || '';
            new bootstrap.Modal(document.getElementById('floorModal')).show();
        }

        async function saveFloor() {
            const form = document.getElementById('floorForm');
            const formData = new FormData(form);
            const data = Object.fromEntries(formData);
            data.floor_number = parseInt(data.floor_number);

            try {
                const response = await fetch(baseUrl + 'api/properties/floors.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });

                const result = await response.json();
                if (result.success) {
                    bootstrap.Modal.getInstance(document.getElementById('floorModal')).hide();
                    showAlert('Floor saved successfully', 'success');
                    loadProperty();
                } else {
                    showAlert(result.message || 'Failed to save floor', 'danger');
                }
            } catch (error) {
                showAlert('Failed to save floor', 'danger');
                console.error('Error:', error);
            }
        }

        async function deleteFloor(floorId) {
            if (!confirm('Are you sure you want to delete this floor? This will also delete all rooms on this floor.')) return;

            try {
                const response = await fetch(`${baseUrl}api/properties/floors.php?id=${floorId}`, {
                    method: 'DELETE'
                });

                const result = await response.json();
                if (result.success) {
                    showAlert('Floor deleted successfully', 'success');
                    loadProperty();
                } else {
                    showAlert(result.message || 'Failed to delete floor', 'danger');
                }
            } catch (error) {
                showAlert('Failed to delete floor', 'danger');
                console.error('Error:', error);
            }
        }

        function showAddRoomModal() {
            document.getElementById('roomModalTitle').textContent = 'Add Room';
            document.getElementById('roomForm').reset();
            document.getElementById('roomId').value = '';
            loadFloorsForRooms();
            new bootstrap.Modal(document.getElementById('roomModal')).show();
        }

        function editRoom(roomId) {
            const room = rooms.find(r => r.id == roomId);
            if (!room) return;
            
            document.getElementById('roomModalTitle').textContent = 'Edit Room';
            document.getElementById('roomId').value = room.id;
            document.querySelector('#roomForm [name="room_number"]').value = room.room_number;
            document.querySelector('#roomForm [name="name"]').value = room.name || '';
            document.querySelector('#roomForm [name="capacity"]').value = room.capacity;
            document.querySelector('#roomForm [name="rental_amount"]').value = room.rental_amount;
            document.querySelector('#roomForm [name="status"]').value = room.status;
            document.querySelector('#roomForm [name="description"]').value = room.description || '';
            loadFloorsForRooms().then(() => {
                document.querySelector('#roomForm [name="floor_id"]').value = room.floor_id || '';
            });
            new bootstrap.Modal(document.getElementById('roomModal')).show();
        }

        async function saveRoom() {
            const form = document.getElementById('roomForm');
            const formData = new FormData(form);
            const data = Object.fromEntries(formData);
            data.capacity = parseInt(data.capacity);
            data.rental_amount = parseFloat(data.rental_amount) || 0;
            if (!data.floor_id) data.floor_id = null;

            try {
                const response = await fetch(baseUrl + 'api/properties/rooms.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });

                const result = await response.json();
                if (result.success) {
                    bootstrap.Modal.getInstance(document.getElementById('roomModal')).hide();
                    showAlert('Room saved successfully', 'success');
                    loadProperty();
                } else {
                    showAlert(result.message || 'Failed to save room', 'danger');
                }
            } catch (error) {
                showAlert('Failed to save room', 'danger');
                console.error('Error:', error);
            }
        }

        async function deleteRoom(roomId) {
            if (!confirm('Are you sure you want to delete this room?')) return;

            try {
                const response = await fetch(`${baseUrl}api/properties/rooms.php?id=${roomId}`, {
                    method: 'DELETE'
                });

                const result = await response.json();
                if (result.success) {
                    showAlert('Room deleted successfully', 'success');
                    loadProperty();
                } else {
                    showAlert(result.message || 'Failed to delete room', 'danger');
                }
            } catch (error) {
                showAlert('Failed to delete room', 'danger');
                console.error('Error:', error);
            }
        }

        function editPropertyDetails() {
            // TODO: Implement property details editing
            showAlert('Property editing feature coming soon', 'info');
        }

        function scrollToTop() {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        window.addEventListener('scroll', () => {
            const btn = document.getElementById('scrollTopBtn');
            if (window.scrollY > 300) {
                btn.style.display = 'block';
            } else {
                btn.style.display = 'none';
            }
        });

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

        loadProperty();
    </script>
</body>
</html>

