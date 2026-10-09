$(document).ready(function() {
    const fallbackRoot = (function() {
        const path = window.location.pathname || '';
        const lower = path.toLowerCase();
        const markers = ['/login/', '/login', '/register/', '/register', '/admin/', '/admin', '/index.html'];
        let cutIndex = -1;

        markers.forEach(function(marker) {
            const idx = lower.indexOf(marker);
            if (idx !== -1 && (cutIndex === -1 || idx < cutIndex)) {
                cutIndex = idx;
            }
        });

        return (cutIndex >= 0 ? path.slice(0, cutIndex) : path.replace(/\/[^\/]*$/, '')).replace(/\/+$/, '');
    })();

    const PATHS = window.APP_PATHS || {
        LOGIN_URL: (fallbackRoot ? fallbackRoot : '') + '/login',
        API_BASE: (fallbackRoot ? fallbackRoot : '') + '/api'
    };
    const API_BASE = PATHS.API_BASE;
    const token = localStorage.getItem('admin_token');

    if (!token) {
        window.location.href = PATHS.LOGIN_URL;
        return;
    }

    function loadRoles(page = 1, search = '') {
        $.ajax({
            url: `${API_BASE}/roles?page=${page}&limit=10&search=${encodeURIComponent(search)}`,
            method: 'GET',
            headers: {
                'Authorization': `Bearer ${token}`
            },
            success: function(response) {
                if (response.success && response.data) {
                    renderRoles(response.data.data);
                    renderRolesPagination(response.data.pagination);
                }
            },
            error: function(xhr) {
                if (xhr.status === 401) {
                    localStorage.removeItem('admin_token');
                    window.location.href = PATHS.LOGIN_URL;
                }
            }
        });
    }

    function loadPermissions(page = 1, search = '') {
        $.ajax({
            url: `${API_BASE}/permissions?page=${page}&limit=10&search=${encodeURIComponent(search)}`,
            method: 'GET',
            headers: {
                'Authorization': `Bearer ${token}`
            },
            success: function(response) {
                if (response.success && response.data) {
                    renderPermissions(response.data.data);
                    renderPermissionsPagination(response.data.pagination);
                }
            },
            error: function(xhr) {
                if (xhr.status === 401) {
                    localStorage.removeItem('admin_token');
                    window.location.href = PATHS.LOGIN_URL;
                }
            }
        });
    }

    function loadAdminUsers(page = 1, search = '') {
        $.ajax({
            url: `${API_BASE}/admin/users?page=${page}&limit=10&search=${encodeURIComponent(search)}`,
            method: 'GET',
            headers: {
                'Authorization': `Bearer ${token}`
            },
            success: function(response) {
                if (response.success && response.data) {
                    renderAdminUsers(response.data.data);
                    renderAdminUsersPagination(response.data.pagination);
                }
            },
            error: function(xhr) {
                if (xhr.status === 401) {
                    localStorage.removeItem('admin_token');
                    window.location.href = PATHS.LOGIN_URL;
                }
            }
        });
    }

    function renderRoles(roles) {
        const tbody = $('#rolesTableBody');
        tbody.empty();

        if (roles.length === 0) {
            tbody.append('<tr><td colspan="3" class="text-center">No roles found</td></tr>');
            return;
        }

        roles.forEach(role => {
            const row = `
                <tr>
                    <td>${role.id}</td>
                    <td>${role.name || ''}</td>
                    <td>${role.created_at || ''}</td>
                </tr>
            `;
            tbody.append(row);
        });
    }

    function renderPermissions(permissions) {
        const tbody = $('#permissionsTableBody');
        tbody.empty();

        if (permissions.length === 0) {
            tbody.append('<tr><td colspan="3" class="text-center">No permissions found</td></tr>');
            return;
        }

        permissions.forEach(permission => {
            const row = `
                <tr>
                    <td>${permission.id}</td>
                    <td>${permission.name || ''}</td>
                    <td>${permission.created_at || ''}</td>
                </tr>
            `;
            tbody.append(row);
        });
    }

    function renderAdminUsers(users) {
        const tbody = $('#adminUsersTableBody');
        tbody.empty();

        if (users.length === 0) {
            tbody.append('<tr><td colspan="5" class="text-center">No admin users found</td></tr>');
            return;
        }

        users.forEach(user => {
            const rolesStr = Array.isArray(user.roles) ? user.roles.join(', ') : '';
            const row = `
                <tr>
                    <td>${user.id}</td>
                    <td>${user.name || ''}</td>
                    <td>${user.mobile || ''}</td>
                    <td>${rolesStr}</td>
                    <td>${user.created_at || ''}</td>
                </tr>
            `;
            tbody.append(row);
        });
    }

    function renderRolesPagination(pagination) {
        const paginationDiv = $('#rolesPagination');
        paginationDiv.empty();

        if (pagination.pages <= 1) return;

        let html = '<nav><ul class="pagination">';
        for (let i = 1; i <= pagination.pages; i++) {
            html += `<li class="page-item ${i === pagination.page ? 'active' : ''}">
                        <a class="page-link roles-page" href="#" data-page="${i}">${i}</a>
                     </li>`;
        }
        html += '</ul></nav>';
        paginationDiv.html(html);
    }

    function renderPermissionsPagination(pagination) {
        const paginationDiv = $('#permissionsPagination');
        paginationDiv.empty();

        if (pagination.pages <= 1) return;

        let html = '<nav><ul class="pagination">';
        for (let i = 1; i <= pagination.pages; i++) {
            html += `<li class="page-item ${i === pagination.page ? 'active' : ''}">
                        <a class="page-link permissions-page" href="#" data-page="${i}">${i}</a>
                     </li>`;
        }
        html += '</ul></nav>';
        paginationDiv.html(html);
    }

    function renderAdminUsersPagination(pagination) {
        const paginationDiv = $('#adminUsersPagination');
        paginationDiv.empty();

        if (pagination.pages <= 1) return;

        let html = '<nav><ul class="pagination">';
        for (let i = 1; i <= pagination.pages; i++) {
            html += `<li class="page-item ${i === pagination.page ? 'active' : ''}">
                        <a class="page-link adminusers-page" href="#" data-page="${i}">${i}</a>
                     </li>`;
        }
        html += '</ul></nav>';
        paginationDiv.html(html);
    }

    $(document).on('click', '.roles-page', function(e) {
        e.preventDefault();
        const page = $(this).data('page');
        const search = $('#searchRoles').val();
        loadRoles(page, search);
    });

    $(document).on('click', '.permissions-page', function(e) {
        e.preventDefault();
        const page = $(this).data('page');
        const search = $('#searchPermissions').val();
        loadPermissions(page, search);
    });

    $(document).on('click', '.adminusers-page', function(e) {
        e.preventDefault();
        const page = $(this).data('page');
        const search = $('#searchAdminUsers').val();
        loadAdminUsers(page, search);
    });

    $('#searchRoles').on('keyup', function() {
        const search = $(this).val();
        loadRoles(1, search);
    });

    $('#searchPermissions').on('keyup', function() {
        const search = $(this).val();
        loadPermissions(1, search);
    });

    $('#searchAdminUsers').on('keyup', function() {
        const search = $(this).val();
        loadAdminUsers(1, search);
    });

    $('#createRoleForm').on('submit', function(e) {
        e.preventDefault();
        const name = $('#roleName').val();

        $.ajax({
            url: `${API_BASE}/roles`,
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/json'
            },
            data: JSON.stringify({ name: name }),
            success: function(response) {
                if (response.success) {
                    alert('Role created successfully');
                    $('#roleName').val('');
                    $('#createRoleModal').modal('hide');
                    loadRoles(1);
                }
            },
            error: function() {
                alert('Failed to create role');
            }
        });
    });

    $('#createPermissionForm').on('submit', function(e) {
        e.preventDefault();
        const name = $('#permissionName').val();

        $.ajax({
            url: `${API_BASE}/permissions`,
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/json'
            },
            data: JSON.stringify({ name: name }),
            success: function(response) {
                if (response.success) {
                    alert('Permission created successfully');
                    $('#permissionName').val('');
                    $('#createPermissionModal').modal('hide');
                    loadPermissions(1);
                }
            },
            error: function() {
                alert('Failed to create permission');
            }
        });
    });

    $('#createAdminUserForm').on('submit', function(e) {
        e.preventDefault();
        const mobile = $('#adminMobile').val();
        const name = $('#adminName').val();
        const password = $('#adminPassword').val();
        const roleId = $('#adminRole').val();

        $.ajax({
            url: `${API_BASE}/admin/users`,
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/json'
            },
            data: JSON.stringify({
                mobile: mobile,
                name: name,
                password: password,
                role_id: roleId
            }),
            success: function(response) {
                if (response.success) {
                    alert('Admin user created successfully');
                    $('#createAdminUserForm')[0].reset();
                    $('#createAdminUserModal').modal('hide');
                    loadAdminUsers(1);
                }
            },
            error: function() {
                alert('Failed to create admin user');
            }
        });
    });

    if ($('#rolesTableBody').length) {
        loadRoles(1);
    }

    if ($('#permissionsTableBody').length) {
        loadPermissions(1);
    }

    if ($('#adminUsersTableBody').length) {
        loadAdminUsers(1);
    }
});
