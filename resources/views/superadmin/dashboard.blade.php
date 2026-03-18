@extends('layouts.appma')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="container-fluid">
    <h1 class="mb-4">Superadmin Dashboard</h1>

    <div class="row mb-4">
        <div class="col-12">
            <button id="addOrganizationBtn" class="btn btn-primary me-2">Add New Organization and Admin </button>
            <button id="addUserBtn" class="btn btn-success me-2">Add New User</button>
            <button id="linkUserOrgBtn" class="btn btn-info">Link User to Organization</button>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h2 class="mb-0">Organizations</h2>
                </div>
                <div class="card-body">
                    <table class="table" id="organizationsTable">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Type</th>
                                <th>Users</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Organizations will be loaded here -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h2 class="mb-0">Users</h2>
                </div>
                <div class="card-body">
                    <table class="table" id="usersTable">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Organization</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Users will be loaded here -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Organization Modal -->
<div class="modal fade" id="addOrganizationModal" tabindex="-1" aria-labelledby="addOrganizationModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="addOrganizationModalLabel">Add New Organization</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="addOrganizationForm">
            @csrf
            <div class="form-group">
                <label for="name">Organization Name*</label>
                <input type="text" class="form-control" id="name" name="name" required>
            </div>
            <div class="form-group">
                <label for="type">Organization Type*</label>
                <select class="form-control" id="type" name="type" required>
                    <option value="retail">Retail</option>
                    <option value="wholesale">Wholesale</option>
                    <option value="producer">Producer</option>
                    <option value="processor">Processor</option>
                    <option value="laboratory">Laboratory</option>
                    <option value="admin">Admin</option>
                </select>
            </div>
            <div class="form-group">
                <label for="email">Email*</label>
                <input type="email" class="form-control" id="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="phone">Phone</label>
                <input type="tel" class="form-control" id="phone" name="phone">
            </div>
            <div class="form-group">
                <label for="license_number">License Number</label>
                <input type="text" class="form-control" id="license_number" name="license_number">
            </div>
            <div class="form-group">
                <label for="business_name">Business Name*</label>
                <input type="text" class="form-control" id="business_name" name="business_name" required>
            </div>
            <div class="form-group">
                <label for="physical_address">Physical Address</label>
                <input type="text" class="form-control" id="physical_address" name="physical_address">
            </div>
            <div class="form-group">
                <label for="password">Admin Password*</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary" id="saveOrganization">Save Organization</button>
      </div>
    </div>
  </div>
</div>

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="addUserModalLabel">Add New User</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="addUserForm">
            @csrf
            <div class="mb-3">
                <label for="userName" class="form-label">Name*</label>
                <input type="text" class="form-control" id="userName" name="name" required>
            </div>
            <div class="mb-3">
                <label for="userEmail" class="form-label">Email*</label>
                <input type="email" class="form-control" id="userEmail" name="email" required>
            </div>
            <div class="mb-3">
                <label for="userPassword" class="form-label">Password*</label>
                <input type="password" class="form-control" id="userPassword" name="password" required>
            </div>
            <div class="mb-3">
                <label for="userPhone" class="form-label">Phone</label>
                <input type="tel" class="form-control" id="userPhone" name="phone">
            </div>
            <div class="mb-3">
                <label for="userAddress" class="form-label">Address</label>
                <input type="text" class="form-control" id="userAddress" name="address">
            </div>
            <div class="mb-3">
                <label for="userOrganization" class="form-label">Organization*</label>
                <select class="form-control" id="userOrganization" name="organization_id" required>
                    <!-- Organizations will be loaded here -->
                </select>
            </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary" id="saveUser">Save User</button>
      </div>
    </div>
  </div>
</div>

<!-- Link User to Organization Modal -->
<div class="modal fade" id="linkUserOrgModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Link User to Organization</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="linkUserOrgForm">
                    @csrf
                    <div class="mb-3">
                        <label for="linkUser" class="form-label">User</label>
                        <select class="form-control" id="linkUser" name="user_id" required>
                            <!-- Users will be loaded here -->
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="linkOrganization" class="form-label">Organization</label>
                        <select class="form-control" id="linkOrganization" name="organization_id" required>
                            <!-- Organizations will be loaded here -->
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="saveLinkUserOrg">Save</button>
            </div>
        </div>
    </div>
<!-- Edit Organization Modal -->
<div class="modal fade" id="editOrganizationModal" tabindex="-1" aria-labelledby="editOrganizationModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="editOrganizationModalLabel">Edit Organization</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="editOrganizationForm">
            @csrf
            <input type="hidden" id="editOrgId" name="id">
            <div class="form-group">
                <label for="editOrgName">Organization Name*</label>
                <input type="text" class="form-control" id="editOrgName" name="name" required>
            </div>
            <div class="form-group">
                <label for="editOrgType">Organization Type*</label>
                <select class="form-control" id="editOrgType" name="type" required>
                    <option value="retail">Retail</option>
                    <option value="wholesale">Wholesale</option>
                    <option value="producer">Producer</option>
                    <option value="processor">Processor</option>
                    <option value="laboratory">Laboratory</option>
                    <option value="admin">Admin</option>
                </select>
            </div>
            <div class="form-group">
                <label for="editOrgEmail">Email*</label>
                <input type="email" class="form-control" id="editOrgEmail" name="email" required>
            </div>
            <div class="form-group">
                <label for="editOrgPhone">Phone</label>
                <input type="tel" class="form-control" id="editOrgPhone" name="phone">
            </div>
            <div class="form-group">
                <label for="editOrgLicenseNumber">License Number</label>
                <input type="text" class="form-control" id="editOrgLicenseNumber" name="license_number">
            </div>
            <div class="form-group">
                <label for="editOrgBusinessName">Business Name*</label>
                <input type="text" class="form-control" id="editOrgBusinessName" name="business_name" required>
            </div>
            <div class="form-group">
                <label for="editOrgPhysicalAddress">Physical Address</label>
                <input type="text" class="form-control" id="editOrgPhysicalAddress" name="physical_address">
            </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary" id="updateOrganization">Update Organization</button>
      </div>
    </div>
  </div>
</div>

<!-- Edit User Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="editUserModalLabel">Edit User</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="editUserForm">
            @csrf
            <input type="hidden" id="editUserId" name="id">
            <div class="mb-3">
                <label for="editUserName" class="form-label">Name*</label>
                <input type="text" class="form-control" id="editUserName" name="name" required>
            </div>
            <div class="mb-3">
                <label for="editUserEmail" class="form-label">Email*</label>
                <input type="email" class="form-control" id="editUserEmail" name="email" required>
            </div>
            <div class="mb-3">
                <label for="editUserPhone" class="form-label">Phone</label>
                <input type="tel" class="form-control" id="editUserPhone" name="phone">
            </div>
            <div class="mb-3">
                <label for="editUserAddress" class="form-label">Address</label>
                <input type="text" class="form-control" id="editUserAddress" name="address">
            </div>
            <div class="mb-3">
                <label for="editUserOrganization" class="form-label">Organization*</label>
                <select class="form-control" id="editUserOrganization" name="organization_id" required>
                    <!-- Organizations will be loaded here -->
                </select>
            </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary" id="updateUser">Update User</button>
      </div>
    </div>
  </div>
</div>
</div>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.3/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.min.js"></script>
<script src="{{ asset('js/app.js') }}" defer></script>

<script>
$(document).ready(function() {
    console.log('Document ready');

    loadOrganizations();
    loadUsers();

    // Add Organization and Admin
    $('#saveOrganization').on('click', function() {
        console.log('Save Organization clicked');
        const formData = $('#addOrganizationForm').serialize();
        $.ajax({
            url: '/superadmin/organization',
            type: 'POST',
            data: formData,
            success: function(response) {
                console.log('Organization created successfully', response);
                $('#addOrganizationModal').modal('hide');
                loadOrganizations();
                alert('Organization and admin created successfully!');
            },
            error: function(xhr) {
                console.error('Error creating organization', xhr);
                if (xhr.status === 422) {
                    let errors = xhr.responseJSON.errors;
                    let errorMessage = '';
                    for (let field in errors) {
                        errorMessage += errors[field].join('\n') + '\n';
                    }
                    alert('Validation Error:\n' + errorMessage);
                } else {
                    alert('An error occurred. Please try again.');
                }
            }
        });
    });

    // Add User
    $('#saveUser').on('click', function() {
        console.log('Save User clicked');
        const formData = $('#addUserForm').serialize();
        $.ajax({
            url: '/superadmin/user',
            type: 'POST',
            data: formData,
            success: function(response) {
                console.log('User created successfully', response);
                $('#addUserModal').modal('hide');
                loadUsers();
                alert('User created successfully!');
            },
            error: function(xhr) {
                console.error('Error creating user', xhr);
                if (xhr.status === 422) {
                    let errors = xhr.responseJSON.errors;
                    let errorMessage = '';
                    for (let field in errors) {
                        errorMessage += errors[field].join('\n') + '\n';
                    }
                    alert('Validation Error:\n' + errorMessage);
                } else {
                    alert('An error occurred. Please try again.');
                }
            }
        });
    });

    // Load organizations for user creation
    $('#addUserBtn').on('click', function() {
        console.log('Add User button clicked');
        $.ajax({
            url: '/superadmin/organizations',
            type: 'GET',
            success: function(orgs) {
                console.log('Organizations loaded', orgs);
                const orgSelect = $('#userOrganization');
                orgSelect.empty();
                orgs.forEach(org => {
                    orgSelect.append(`<option value="${org.id}">${org.name} (${org.type})</option>`);
                });
                var myModal = new bootstrap.Modal(document.getElementById('addUserModal'));
                myModal.show();
            },
            error: function(xhr, status, error) {
                console.error('Error loading organizations:', error);
                alert('Failed to load organizations. Please try again.');
            }
        });
    });

    // Ensure CSRF token is set for all AJAX requests
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    $('#addOrganizationBtn').on('click', function() {
        $('#addOrganizationModal').modal('show');
    });

    $('#addUserBtn').on('click', function() {
        $('#addUserModal').modal('show');
    });

    $('#linkUserOrgBtn').on('click', function() {
        loadUsersAndOrgsForLinking();
    });

    function loadOrganizations() {
        $.ajax({
            url: '/superadmin/organizations',
            type: 'GET',
            success: function(organizations) {
                const tableBody = $('#organizationsTable tbody');
                tableBody.empty();
                organizations.forEach(org => {
                    tableBody.append(`
                        <tr>
                            <td>${org.name}</td>
                            <td>${org.type}</td>
                            <td>${org.users ? org.users.length : 0}</td>
                            <td>
                                <button class="btn btn-sm btn-info view-org" data-id="${org.id}">View</button>
                                <button class="btn btn-sm btn-warning edit-org" data-id="${org.id}">Edit</button>
                            </td>
                        </tr>
                    `);
                });
            },
            error: function(xhr, status, error) {
                console.error('Error loading organizations:', error);
            }
        });
    }

   function loadUsers() {
    $.ajax({
        url: '/superadmin/superadmin/users',
        type: 'GET',
        success: function(users) {
            console.log('Users loaded:', users);
            const tableBody = $('#usersTable tbody');
            tableBody.empty();
            users.forEach(user => {
                console.log('Processing user:', user);
                tableBody.append(`
                    <tr>
                        <td>${user.name || 'N/A'}</td>
                        <td>${user.email || 'N/A'}</td>
                        <td>${user.role ? user.role.name : 'N/A'}</td>
                        <td>${user.primary_organization || 'Not Linked'}</td>
                        <td>
                            <button class="btn btn-sm btn-info view-user" data-id="${user.id}">View</button>
                            <button class="btn btn-sm btn-warning edit-user" data-id="${user.id}">Edit</button>
                        </td>
                    </tr>
                `);
            });
        },
        error: function(xhr, status, error) {
            console.error('Error loading users:', error);
            console.error('Status:', status);
            console.error('Response:', xhr.responseText);
        }
    });
}
    function loadUsersAndOrgsForLinking() {
        $.ajax({
            url: '/superadmin/superadmin/users',
            type: 'GET',
            success: function(users) {
                const userSelect = $('#linkUser');
                userSelect.empty();
                users.forEach(user => {
                    userSelect.append(`<option value="${user.id}">${user.name} (${user.email})</option>`);
                });

                $.ajax({
                    url: '/superadmin/organizations',
                    type: 'GET',
                    success: function(orgs) {
                        const orgSelect = $('#linkOrganization');
                        orgSelect.empty();
                        orgs.forEach(org => {
                            orgSelect.append(`<option value="${org.id}">${org.name} (${org.type})</option>`);
                        });

                        // Show the modal after populating both dropdowns
                        $('#linkUserOrgModal').modal('show');
                    },
                    error: function(xhr, status, error) {
                        console.error('Error loading organizations:', error);
                        alert('Failed to load organizations. Please try again.');
                    }
                });
            },
            error: function(xhr, status, error) {
                console.error('Error loading users:', error);
                alert('Failed to load users. Please try again.');
            }
        });
    }
$('#saveLinkUserOrg').on('click', function() {
    const formData = $('#linkUserOrgForm').serialize();
    $.ajax({
        url: '/superadmin/link-user-org',
        type: 'POST',
        data: formData,
        success: function(response) {
            console.log('User linked to organization successfully', response);
            $('#linkUserOrgModal').modal('hide');
            loadUsers(); // Reload the users table to reflect the changes
            alert('User linked to organization successfully!');
        },
        error: function(xhr) {
            console.error('Error linking user to organization', xhr);
            if (xhr.status === 422) {
                let errors = xhr.responseJSON.errors;
                let errorMessage = '';
                for (let field in errors) {
                    errorMessage += errors[field].join('\n') + '\n';
                }
                alert('Validation Error:\n' + errorMessage);
            } else {
                alert('An error occurred. Please try again.');
            }
        }
    });
});
// Edit Organization
// Edit Organization
$(document).on('click', '.edit-org', function() {
    const orgId = $(this).data('id');
    $.ajax({
        url: `/superadmin/organization/${orgId}`,
        type: 'GET',
        dataType: 'json',
        success: function(org) {
            console.log('Organization data received:', org);
            // Populate the form fields with org data
            $('#editOrgId').val(org.id);
            $('#editOrgName').val(org.name);
            $('#editOrgType').val(org.type);
            $('#editOrgEmail').val(org.email);
            $('#editOrgPhone').val(org.phone);
            $('#editOrgLicenseNumber').val(org.license_number);
            $('#editOrgBusinessName').val(org.business_name);
            $('#editOrgPhysicalAddress').val(org.physical_address);
            
            $('#editOrganizationModal').modal('show');
        },
        error: function(xhr, status, error) {
            console.error('Error fetching organization details:', error);
            console.error('Status:', status);
            console.error('Response:', xhr.responseText);
            alert('Failed to load organization details. Please check the console for more information.');
        }
    });
});
    


$('#updateOrganization').on('click', function() {
    const formData = $('#editOrganizationForm').serialize();
    const orgId = $('#editOrgId').val();
    $.ajax({
        url: `/superadmin/organization/${orgId}`,
        type: 'PUT',
        data: formData,
        success: function(response) {
            console.log('Organization updated successfully', response);
            $('#editOrganizationModal').modal('hide');
            loadOrganizations();
            alert('Organization updated successfully!');
        },
        error: function(xhr) {
            console.error('Error updating organization', xhr);
            alert('An error occurred. Please try again.');
        }
    });
});

// Edit User
$(document).on('click', '.edit-user', function() {
    const userId = $(this).data('id');
    $.ajax({
        url: `/superadmin/user/${userId}`,
        type: 'GET',
        success: function(user) {
            $('#editUserId').val(user.id);
            $('#editUserName').val(user.name);
            $('#editUserEmail').val(user.email);
            $('#editUserPhone').val(user.phone);
            $('#editUserAddress').val(user.address);
            
            // Load organizations for the dropdown
            $.ajax({
                url: '/superadmin/organizations',
                type: 'GET',
                success: function(orgs) {
                    const orgSelect = $('#editUserOrganization');
                    orgSelect.empty();
                    orgs.forEach(org => {
                        orgSelect.append(`<option value="${org.id}">${org.name} (${org.type})</option>`);
                    });
                    orgSelect.val(user.organization_id);
                    $('#editUserModal').modal('show');
                },
                error: function(xhr) {
                    console.error('Error loading organizations', xhr);
                    alert('Failed to load organizations. Please try again.');
                }
            });
        },
        error: function(xhr) {
            console.error('Error fetching user details', xhr);
            alert('Failed to load user details. Please try again.');
        }
    });
});

$('#updateUser').on('click', function() {
    const formData = $('#editUserForm').serialize();
    const userId = $('#editUserId').val();
    $.ajax({
        url: `/superadmin/user/${userId}`,
        type: 'PUT',
        data: formData,
        success: function(response) {
            console.log('User updated successfully', response);
            $('#editUserModal').modal('hide');
            loadUsers();
            alert('User updated successfully!');
        },
        error: function(xhr) {
            console.error('Error updating user', xhr);
            alert('An error occurred. Please try again.');
        }
    });
});
});
</script>
@endsection