@extends('admin.layouts.admin-layout')

@section('title', 'Permission Groups')

@section('content')
    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2>Permission Groups</h2>
            @can('Create Permission Group')
                <button class="btn btn-primary" id="addGroupBtn">
                    <i class="fas fa-plus"></i> Add Group
                </button>
            @endcan
        </div>


        <table class="table table-striped" id="groupsTable">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Created At</th>
                    @canany(['Edit Permission Group', 'Delete Permission Group', 'Create Permission Group'])
                        <th>Actions</th>
                    @endcanany

                </tr>
            </thead>
            <tbody>
                @foreach ($groups as $group)
                    <tr id="group-{{ $group->id }}">
                        <td>{{ $group->id }}</td>
                        <td class="group-name">{{ $group->name }}</td>
                        <td>{{ $group->created_at->format('Y-m-d') }}</td>
                        @canany(['Edit Permission Group', 'Delete Permission Group'])
                            <td>
                                @can('Edit Permission Group')
                                    <button class="btn btn-sm btn-info editBtn" data-id="{{ $group->id }}">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                @endcan

                                @can('Delete Permission Group')
                                    <button class="btn btn-sm btn-danger deleteBtn" data-id="{{ $group->id }}">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                @endcan
                            </td>
                        @endcanany

                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- Modal (used for both Add and Edit) -->
    <div class="modal fade" id="groupModal" tabindex="-1" aria-labelledby="groupModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="groupForm">
                    <div class="modal-header">
                        <h5 class="modal-title" id="groupModalLabel">Add Group</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        @csrf
                        <input type="hidden" id="groupId">
                        <div class="mb-3">
                            <label for="groupName" class="form-label">Group Name</label>
                            <input type="text" class="form-control" id="groupName" name="name" required>
                            <div class="invalid-feedback" id="nameError"></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary" id="saveGroupBtn">Save</button>
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
            const modal = new bootstrap.Modal(document.getElementById('groupModal'));
            const addBtn = document.getElementById('addGroupBtn');
            const form = document.getElementById('groupForm');
            const nameInput = document.getElementById('groupName');
            const groupIdInput = document.getElementById('groupId');
            const nameError = document.getElementById('nameError');

            // CSRF token
            const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            // Open modal for Add
            addBtn.addEventListener('click', () => {
                form.reset();
                groupIdInput.value = '';
                document.getElementById('groupModalLabel').textContent = 'Add Group';
                nameError.textContent = '';
                nameInput.classList.remove('is-invalid');
                modal.show();
            });

            // Open modal for Edit
            document.querySelectorAll('.editBtn').forEach(btn => {
                btn.addEventListener('click', () => {
                    const id = btn.dataset.id;
                    fetch(`/admin/permission-groups/${id}/edit`)
                        .then(res => res.json())
                        .then(data => {
                            groupIdInput.value = data.id;
                            nameInput.value = data.name;
                            document.getElementById('groupModalLabel').textContent =
                                'Edit Group';
                            nameError.textContent = '';
                            nameInput.classList.remove('is-invalid');
                            modal.show();
                        });
                });
            });

            // Save group (create or update)
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                nameError.textContent = '';
                nameInput.classList.remove('is-invalid');

                const id = groupIdInput.value;
                const url = id ? `/admin/permission-groups/${id}` : '/admin/permission-groups';
                const method = id ? 'PUT' : 'POST';

                fetch(url, {
                        method: method,
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': token
                        },
                        body: JSON.stringify({
                            name: nameInput.value
                        })
                    })
                    .then(async res => {
                        if (res.status === 422) {
                            const data = await res.json();
                            nameError.textContent = data.errors.name ? data.errors.name[0] : '';
                            nameInput.classList.add('is-invalid');
                        } else {
                            return res.json();
                        }
                    })
                    .then(data => {
                        if (data) {
                            const rowId = `group-${data.group.id}`;
                            const rowHtml = `
                <tr id="${rowId}">
                    <td>${data.group.id}</td>
                    <td class="group-name">${data.group.name}</td>
                    <td>${data.group.created_at.split('T')[0]}</td>
                    <td>
                        <button class="btn btn-sm btn-info editBtn" data-id="${data.group.id}"><i class="fas fa-edit"></i></button>
                        <button class="btn btn-sm btn-danger deleteBtn" data-id="${data.group.id}"><i class="fas fa-trash"></i></button>
                    </td>
                </tr>`;

                            if (id) {
                                // update existing row
                                document.getElementById(rowId).outerHTML = rowHtml;
                            } else {
                                // add new row
                                document.querySelector('#groupsTable tbody').insertAdjacentHTML(
                                    'beforeend', rowHtml);
                            }
                            modal.hide();
                            Swal.fire('Success', data.message, 'success').then(() => location.reload());
                        }
                    });
            });

            // Delete group
            document.addEventListener('click', function(e) {
                if (e.target.closest('.deleteBtn')) {
                    const id = e.target.closest('.deleteBtn').dataset.id;
                    Swal.fire({
                        title: 'Are you sure?',
                        text: "This will delete the group!",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#3085d6',
                        confirmButtonText: 'Yes, delete it!'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            fetch(`/admin/permission-groups/${id}`, {
                                    method: 'DELETE',
                                    headers: {
                                        'X-CSRF-TOKEN': token
                                    }
                                })
                                .then(res => res.json())
                                .then(data => {
                                    Swal.fire('Deleted!', data.message, 'success').then(() => {
                                        document.getElementById(`group-${id}`).remove();
                                    });
                                });
                        }
                    });
                }
            });
        });
    </script>
@endpush
