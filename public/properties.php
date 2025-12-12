<?php
/**
 * Properties Listing Page
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/install-check.php';
require_once __DIR__ . '/../includes/auth.php';

requireInstallation();
requireLogin();

$currentUser = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Properties - <?php echo APP_NAME; ?></title>
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
        .property-card {
            transition: transform 0.2s, box-shadow 0.2s;
            border: none;
            border-radius: 12px;
        }
        .property-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 16px rgba(0,0,0,0.1);
        }
        .property-type-badge {
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
        }
        .empty-state {
            padding: 4rem 2rem;
            text-align: center;
        }
        .btn-add-property {
            border-radius: 50px;
            padding: 0.75rem 2rem;
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
                        <a class="nav-link active" href="<?php echo BASE_URL; ?>properties.php">Properties</a>
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
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0">My Properties</h2>
            <button class="btn btn-primary btn-add-property" onclick="showAddPropertyModal()">
                <i class="bi bi-plus-circle"></i> Add Property
            </button>
        </div>

        <div id="properties-container" class="row g-4">
            <div class="col-12 text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Property Modal -->
    <div class="modal fade" id="addPropertyModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Property</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addPropertyForm">
                        <div class="mb-3">
                            <label class="form-label">Property Type *</label>
                            <select class="form-select" name="property_type_id" required id="propertyTypeSelect">
                                <option value="">Select type...</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Property Name *</label>
                            <input type="text" class="form-control" name="name" required placeholder="e.g., My Home, PG Building">
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Country *</label>
                                <input type="text" class="form-control" name="country" required placeholder="e.g., India">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">City *</label>
                                <input type="text" class="form-control" name="city" required placeholder="e.g., Mumbai">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Address (Optional)</label>
                            <textarea class="form-control" name="address" rows="2" placeholder="Full address"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description (Optional)</label>
                            <textarea class="form-control" name="description" rows="2" placeholder="Brief description"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="submitAddProperty()">
                        <i class="bi bi-check-circle"></i> Add Property
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const baseUrl = '<?php echo BASE_URL; ?>';
        let properties = [];
        let propertyTypes = [];

        // Load property types
        async function loadPropertyTypes() {
            try {
                const response = await fetch(baseUrl + 'api/properties/types.php');
                const data = await response.json();
                if (data.success) {
                    propertyTypes = data.types;
                    const select = document.getElementById('propertyTypeSelect');
                    data.types.forEach(type => {
                        const option = document.createElement('option');
                        option.value = type.id;
                        option.textContent = type.name;
                        select.appendChild(option);
                    });
                }
            } catch (error) {
                console.error('Error loading property types:', error);
            }
        }

        // Load properties
        async function loadProperties() {
            try {
                const response = await fetch(baseUrl + 'api/properties/list.php');
                const data = await response.json();
                if (data.success) {
                    properties = data.properties;
                    renderProperties();
                }
            } catch (error) {
                console.error('Error loading properties:', error);
                showAlert('Failed to load properties', 'danger');
            }
        }

        function renderProperties() {
            const container = document.getElementById('properties-container');
            
            if (properties.length === 0) {
                container.innerHTML = `
                    <div class="col-12">
                        <div class="empty-state">
                            <i class="bi bi-building" style="font-size: 4rem; color: #ccc;"></i>
                            <h4 class="mt-3 text-muted">No properties yet</h4>
                            <p class="text-muted">Get started by adding your first property</p>
                            <button class="btn btn-primary btn-add-property" onclick="showAddPropertyModal()">
                                <i class="bi bi-plus-circle"></i> Add Your First Property
                            </button>
                        </div>
                    </div>
                `;
                return;
            }

            container.innerHTML = properties.map(property => `
                <div class="col-md-6 col-lg-4">
                    <div class="card property-card h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h5 class="card-title mb-0">${escapeHtml(property.name)}</h5>
                                <span class="badge bg-primary property-type-badge">${escapeHtml(property.property_type_name)}</span>
                            </div>
                            <p class="text-muted small mb-2">
                                <i class="bi bi-geo-alt"></i> ${escapeHtml(property.city)}, ${escapeHtml(property.country)}
                            </p>
                            <div class="d-flex gap-3 small text-muted mb-3">
                                <span><i class="bi bi-people"></i> ${property.total_renters || 0} Renters</span>
                                ${property.total_rooms ? `<span><i class="bi bi-door-open"></i> ${property.total_rooms} Rooms</span>` : ''}
                            </div>
                            <div class="d-grid gap-2">
                                <a href="${baseUrl}property-details.php?id=${property.id}" class="btn btn-outline-primary btn-sm">
                                    <i class="bi bi-eye"></i> View Details
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            `).join('');
        }

        function showAddPropertyModal() {
            const modal = new bootstrap.Modal(document.getElementById('addPropertyModal'));
            modal.show();
        }

        async function submitAddProperty() {
            const form = document.getElementById('addPropertyForm');
            const formData = new FormData(form);
            
            const data = {
                property_type_id: parseInt(formData.get('property_type_id')),
                name: formData.get('name'),
                country: formData.get('country'),
                city: formData.get('city'),
                address: formData.get('address'),
                description: formData.get('description')
            };

            try {
                const response = await fetch(baseUrl + 'api/properties/create.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });

                const result = await response.json();
                
                if (result.success) {
                    bootstrap.Modal.getInstance(document.getElementById('addPropertyModal')).hide();
                    form.reset();
                    showAlert('Property added successfully!', 'success');
                    loadProperties();
                } else {
                    showAlert(result.message || 'Failed to add property', 'danger');
                }
            } catch (error) {
                showAlert('Failed to add property', 'danger');
                console.error('Error:', error);
            }
        }

        function showAlert(message, type) {
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3`;
            alertDiv.style.zIndex = '9999';
            alertDiv.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            document.body.appendChild(alertDiv);
            setTimeout(() => alertDiv.remove(), 5000);
        }

        function escapeHtml(text) {
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
        loadPropertyTypes();
        loadProperties();
    </script>
</body>
</html>

