@extends('admin.layouts.admin-layout')

@section('title', 'Permissions')

@section('content')
    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2>Permissions</h2>
            @can('Create Permission')
                <button class="btn btn-primary" id="addPermissionBtn">
                    <i class="fas fa-plus"></i> Add Permission
                </button>
            @endcan
        </div>


        <table class="table table-striped" id="permissionsTable">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Group</th>
                    <th>Created At</th>
                    @canany(['Edit Permission', 'Delete Permission'])
                        <th>Actions</th>
                    @endcanany
                </tr>
            </thead>
            <tbody>
                @foreach ($permissions as $permission)
                    <tr id="permission-{{ $permission->id }}">
                        <td>{{ $permission->id }}</td>
                        <td class="permission-name">{{ $permission->name }}</td>
                        <td class="permission-group">{{ $permission->group->name ?? '-' }}</td>
                        <td>{{ $permission->created_at->format('Y-m-d') }}</td>
                        @canany(['Edit Permission', 'Delete Permission'])
                            <td>
                                @can('Edit Permission')
                                    <button class="btn btn-sm btn-info editBtn" data-id="{{ $permission->id }}"><i
                                            class="fas fa-edit"></i></button>
                                @endcan

                                @can('Delete Permission')
                                    <button class="btn btn-sm btn-danger deleteBtn" data-id="{{ $permission->id }}"><i
                                            class="fas fa-trash"></i></button>
                                @endcan
                            </td>
                        @endcanany

                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="permissionModal" tabindex="-1" aria-labelledby="permissionModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="permissionForm">
                    <div class="modal-header">
                        <h5 class="modal-title" id="permissionModalLabel">Add Permission</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        @csrf
                        <input type="hidden" id="permissionId">
                        <div class="mb-3">
                            <label for="permissionName" class="form-label">Permission Name</label>
                            <input type="text" class="form-control" id="permissionName" name="name" required>
                            <div class="invalid-feedback" id="nameError"></div>
                        </div>
                        <div class="mb-3">
                            <label for="groupSelect" class="form-label">Group</label>
                            <select class="form-control" id="groupSelect" name="group_id" required>
                                <option value="">-- Select Group --</option>
                                @foreach ($groups as $group)
                                    <option value="{{ $group->id }}">{{ $group->name }}</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback" id="groupError"></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary" id="savePermissionBtn">Save</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const modal = new bootstrap.Modal(document.getElementById('permissionModal'));
            const addBtn = document.getElementById('addPermissionBtn');
            const form = document.getElementById('permissionForm');
            const nameInput = document.getElementById('permissionName');
            const groupSelect = document.getElementById('groupSelect');
            const permissionIdInput = document.getElementById('permissionId');
            const nameError = document.getElementById('nameError');
            const groupError = document.getElementById('groupError');

            const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            // Add
            addBtn.addEventListener('click', () => {
                form.reset();
                permissionIdInput.value = '';
                document.getElementById('permissionModalLabel').textContent = 'Add Permission';
                nameInput.classList.remove('is-invalid');
                groupSelect.classList.remove('is-invalid');
                nameError.textContent = '';
                groupError.textContent = '';
                modal.show();
            });

            // Edit
            document.querySelectorAll('.editBtn').forEach(btn => {
                btn.addEventListener('click', () => {
                    const id = btn.dataset.id;
                    fetch(`/admin/permissions/${id}/edit`)
                        .then(res => res.json())
                        .then(data => {
                            permissionIdInput.value = data.id;
                            nameInput.value = data.name;
                            groupSelect.value = data.group_id || '';
                            document.getElementById('permissionModalLabel').textContent =
                                'Edit Permission';
                            nameInput.classList.remove('is-invalid');
                            groupSelect.classList.remove('is-invalid');
                            nameError.textContent = '';
                            groupError.textContent = '';
                            modal.show();
                        });
                });
            });

            // Save
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                nameError.textContent = '';
                groupError.textContent = '';
                nameInput.classList.remove('is-invalid');
                groupSelect.classList.remove('is-invalid');

                const id = permissionIdInput.value;
                const url = id ? `/admin/permissions/${id}` : '/admin/permissions';
                const method = id ? 'PUT' : 'POST';

                fetch(url, {
                        method: method,
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': token
                        },
                        body: JSON.stringify({
                            name: nameInput.value,
                            group_id: groupSelect.value
                        })
                    })
                    .then(async res => {
                        if (res.status === 422) {
                            const data = await res.json();
                            if (data.errors.name) {
                                nameError.textContent = data.errors.name[0];
                                nameInput.classList.add('is-invalid');
                            }
                            if (data.errors.group_id) {
                                groupError.textContent = data.errors.group_id[0];
                                groupSelect.classList.add('is-invalid');
                            }
                        } else return res.json();
                    })
                    .then(data => {
                        if (data) {
                            const rowId = `permission-${data.permission.id}`;
                            const rowHtml = `
                <tr id="${rowId}">
                    <td>${data.permission.id}</td>
                    <td class="permission-name">${data.permission.name}</td>
                    <td class="permission-group">${groupSelect.options[groupSelect.selectedIndex].text}</td>
                    <td>${data.permission.created_at.split('T')[0]}</td>
                    <td>
                        <button class="btn btn-sm btn-info editBtn" data-id="${data.permission.id}"><i class="fas fa-edit"></i></button>
                        <button class="btn btn-sm btn-danger deleteBtn" data-id="${data.permission.id}"><i class="fas fa-trash"></i></button>
                    </td>
                </tr>`;

                            if (id) {
                                document.getElementById(rowId).outerHTML = rowHtml;
                            } else {
                                document.querySelector('#permissionsTable tbody').insertAdjacentHTML(
                                    'beforeend', rowHtml);
                            }
                            modal.hide();
                            Swal.fire('Success', data.message, 'success').then(() => location.reload());
                        }
                    });
            });

            // Delete
            document.addEventListener('click', function(e) {
                if (e.target.closest('.deleteBtn')) {
                    const id = e.target.closest('.deleteBtn').dataset.id;
                    Swal.fire({
                        title: 'Are you sure?',
                        text: "This will delete the permission!",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#3085d6',
                        confirmButtonText: 'Yes, delete it!'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            fetch(`/admin/permissions/${id}`, {
                                    method: 'DELETE',
                                    headers: {
                                        'X-CSRF-TOKEN': token
                                    }
                                })
                                .then(res => res.json())
                                .then(data => {
                                    Swal.fire('Deleted!', data.message, 'success').then(() => {
                                        document.getElementById(`permission-${id}`)
                                            .remove();
                                    });
                                });
                        }
                    });
                }
            });

        });
    </script>
@endpush
