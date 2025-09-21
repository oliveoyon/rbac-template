@extends('admin.layouts.admin-layout')
@section('title', isset($user) ? 'Edit User' : 'Create User')

@push('styles')
    <style>
        #loader-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.6);
            z-index: 9999;
            display: none;
        }

        #loader {
            position: absolute;
            top: 50%;
            left: 50%;
            width: 50px;
            height: 50px;
            border: 5px solid #28a745;
            border-top: 5px solid transparent;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            transform: translate(-50%, -50%);
        }

        @keyframes spin {
            100% {
                transform: rotate(360deg);
            }
        }

        .btn-group {
            flex-wrap: wrap;
        }
    </style>
@endpush

@section('content')
    <div class="container py-4">
        <h3 class="mb-4">{{ isset($user) ? 'Edit User' : 'Create User' }}</h3>

        <form id="user-form" action="{{ isset($user) ? route('admin.users.update', $user->id) : route('admin.users.store') }}"
            method="POST">
            @csrf
            @if (isset($user))
                @method('PUT')
            @endif

            <!-- Basic Info Card -->
            <div class="card mb-4 shadow-sm" style="border-left:5px solid #28a745;">
                <div class="card-header bg-light"><strong>Basic Information</strong></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Name</label>
                            <input type="text" name="name" class="form-control"
                                value="{{ old('name', $user->name ?? '') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control"
                                value="{{ old('email', $user->email ?? '') }}" required>
                        </div>
                    </div>
                    <div class="row g-3 mt-3">
                        <div class="col-md-6">
                            <label class="form-label">Password</label>
                            <input type="password" name="password" class="form-control"
                                {{ isset($user) ? '' : 'required' }}>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Confirm Password</label>
                            <input type="password" name="password_confirmation" class="form-control"
                                {{ isset($user) ? '' : 'required' }}>
                        </div>
                    </div>
                    <div class="row g-3 mt-3">
                        <div class="col-md-6 d-flex align-items-center">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_active" id="is_active"
                                    {{ isset($user) && $user->is_active ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_active">Active</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Roles Card -->
            <div class="card mb-4 shadow-sm" style="border-left:5px solid #28a745;">
                <div class="card-header bg-light"><strong>Assign Roles</strong></div>
                <div class="card-body">
                    <div class="btn-group" role="group">
                        @foreach ($roles as $role)
                            <input type="checkbox" class="btn-check" id="role-{{ $role->id }}" name="roles[]"
                                value="{{ $role->name }}" autocomplete="off"
                                {{ isset($userRoles) && in_array($role->name, $userRoles) ? 'checked' : '' }}>
                            <label class="btn btn-outline-success me-1 mb-1"
                                for="role-{{ $role->id }}">{{ $role->name }}</label>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Permissions Card -->
            <div class="card mb-4 shadow-sm" style="border-left:5px solid #28a745;">
                <div class="card-header bg-light"><strong>Assign Permissions</strong></div>
                <div class="card-body">
                    @foreach ($permissionGroups as $group)
                        <div class="card mb-2" style="background-color: #f0f9f0;">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <strong>{{ $group->name }}</strong>
                                <div>
                                    <input type="checkbox" class="select-all" data-group="{{ $group->id }}"> Select All
                                    <span class="badge bg-secondary" id="count-{{ $group->id }}">0</span>
                                </div>
                            </div>
                            <div class="card-body">
                                @foreach ($group->permissions as $permission)
                                    @php
                                        $isDirect = in_array($permission->name, $directPermissions ?? []);
                                        $isViaRole =
                                            isset($user) && $user->hasPermissionTo($permission->name) && !$isDirect;
                                        $isChecked = $isDirect || $isViaRole;
                                    @endphp
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input permission-checkbox group-{{ $group->id }}"
                                            type="checkbox" name="permissions[]" value="{{ $permission->name }}"
                                            {{ $isChecked ? 'checked' : '' }}>
                                        <label class="form-check-label">
                                            {{ $permission->name }}
                                            @if ($isViaRole && !$isDirect)
                                                <small class="text-muted">(via role)</small>
                                            @endif
                                        </label>
                                    </div>
                                @endforeach


                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <button type="submit" id="submit-btn"
                class="btn btn-success">{{ isset($user) ? 'Update' : 'Create' }}</button>
        </form>
    </div>

    <div id="loader-overlay">
        <div id="loader"></div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {

            // Update permission count (direct only, ignore disabled)
            function updateCount(groupId) {
                const count = Array.from(document.querySelectorAll('.group-' + groupId + ':checked'))
                    .filter(cb => !cb.disabled).length;
                document.getElementById('count-' + groupId).textContent = count;
            }

            // Initialize counts
            document.querySelectorAll('.select-all').forEach(toggle => {
                const groupId = toggle.dataset.group;
                updateCount(groupId);
            });

            // Select/Unselect all permissions in a group (direct only)
            document.querySelectorAll('.select-all').forEach(function(toggle) {
                toggle.addEventListener('change', function() {
                    const groupId = this.dataset.group;
                    const checked = this.checked;
                    document.querySelectorAll('.group-' + groupId).forEach(cb => {
                        if (!cb.disabled) cb.checked = checked;
                    });
                    updateCount(groupId);
                });
            });

            // Update count when individual permission changed
            document.querySelectorAll('.permission-checkbox').forEach(cb => {
                cb.addEventListener('change', function() {
                    const classes = this.className.split(' ');
                    const groupClass = classes.find(c => c.startsWith('group-'));
                    if (groupClass) {
                        const groupId = groupClass.split('-')[1];
                        updateCount(groupId);
                    }
                });
            });

            // AJAX form submission
            const form = document.getElementById('user-form');
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                const submitBtn = document.getElementById('submit-btn');
                submitBtn.disabled = true;
                document.getElementById('loader-overlay').style.display = 'block';

                const formData = new FormData(form);

                fetch(form.action, {
                        method: form.method,
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json'
                        },
                        body: formData
                    })
                    .then(res => res.json())
                    .then(res => {
                        document.getElementById('loader-overlay').style.display = 'none';
                        submitBtn.disabled = false;
                        if (res.success) {
                            Swal.fire('Success', res.success, 'success').then(() => window.location
                                .href = "{{ route('admin.users.index') }}");
                        }
                    })
                    .catch(err => {
                        document.getElementById('loader-overlay').style.display = 'none';
                        submitBtn.disabled = false;
                        Swal.fire('Error', 'Something went wrong!', 'error');
                        console.error(err);
                    });
            });

        });
    </script>
@endpush
