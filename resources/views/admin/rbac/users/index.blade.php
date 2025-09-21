@extends('admin.layouts.admin-layout')
@section('title', 'Manage Users')

@push('styles')
    <style>
        .user-card {
            border-left: 5px solid #28a745;
            background-color: rgba(198, 239, 206, 0.5);
            /* light green transparent */
            cursor: default;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        .user-card.active-card {
            border-left-color: #28a745;
        }

        .user-card.inactive-card {
            border-left-color: #dc3545;
            background-color: rgba(255, 200, 200, 0.3);
            /* soft red transparent */
        }

        .card-title {
            font-weight: 700;
            /* Bold user name */
        }

        .badge {
            font-size: 0.85rem;
            padding: 0.4em 0.7em;
        }
    </style>
@endpush

@section('content')
    <div class="container-fluid py-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3>Users</h3>
            @can('Create Users')
                <a href="{{ route('admin.users.create') }}" class="btn btn-success">Create User</a>
            @endcan
        </div>


        <div class="row">
            @foreach ($users as $user)
                <div class="col-md-4 mb-3">
                    <div class="card user-card {{ $user->is_active ? 'active-card' : 'inactive-card' }}">
                        <div class="card-body">
                            <h5 class="card-title d-flex align-items-center justify-content-between">
                                <span>{{ $user->name }}</span>
                                @if ($user->is_active)
                                    <span class="badge rounded-pill bg-success ms-2">Active</span>
                                @else
                                    <span class="badge rounded-pill bg-danger ms-2">Inactive</span>
                                @endif
                            </h5>

                            <p class="card-text mb-1">
                                <strong>Email:</strong> {{ $user->email }} <br>
                                <strong>Roles:</strong> {{ $user->roles->pluck('name')->join(', ') }}
                            </p>

                            @if ($user->permissions->count())
                                @can('View User Permissions')
                                    <button class="btn btn-sm btn-outline-secondary mb-2" data-bs-toggle="modal"
                                        data-bs-target="#permissionsModal-{{ $user->id }}">
                                        View Permissions ({{ $user->permissions->count() }})
                                    </button>
                                @endcan


                                <!-- Modal -->
                                <div class="modal fade" id="permissionsModal-{{ $user->id }}" tabindex="-1"
                                    aria-labelledby="permissionsModalLabel-{{ $user->id }}" aria-hidden="true">
                                    <div class="modal-dialog modal-lg modal-dialog-scrollable">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="permissionsModalLabel-{{ $user->id }}">
                                                    Permissions for {{ $user->name }}
                                                </h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                    aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                @php
                                                    $grouped = $user->permissions->groupBy(function ($perm) {
                                                        return optional($perm->group)->name ?? 'Ungrouped';
                                                    });
                                                @endphp

                                                @foreach ($grouped as $groupName => $permissions)
                                                    <div class="mb-3">
                                                        <h6 class="text-success">{{ $groupName }}</h6>
                                                        @foreach ($permissions as $permission)
                                                            <span
                                                                class="badge bg-success mb-1">{{ $permission->name }}</span>
                                                        @endforeach
                                                    </div>
                                                    <hr>
                                                @endforeach
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary"
                                                    data-bs-dismiss="modal">Close</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            <div class="mt-2">
                                @can('Edit Users')
                                    <a href="{{ route('admin.users.edit', $user->id) }}"
                                        class="btn btn-outline-success btn-sm">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                @endcan

                                @can('Delete Users')
                                    <form action="{{ route('admin.users.destroy', $user->id) }}" method="POST"
                                        class="d-inline delete-user-form">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-outline-danger btn-sm">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </form>
                                @endcan
                            </div>

                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // SweetAlert Delete Confirmation
            document.querySelectorAll('.delete-user-form').forEach(function(form) {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    Swal.fire({
                        title: 'Are you sure?',
                        text: "You won't be able to revert this!",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#28a745',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'Yes, delete it!'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            form.submit();
                        }
                    });
                });
            });
        });
    </script>
@endpush
