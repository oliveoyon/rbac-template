@extends('admin.layouts.admin-layout')

@section('title', 'Roles')

@section('content')
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>Roles</h2>
        <button class="btn btn-primary" id="addRoleBtn"><i class="fas fa-plus"></i> Add Role</button>
    </div>

    <table class="table table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Role Name</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($roles as $role)
            <tr>
                <td>{{ $role->id }}</td>
                <td>{{ $role->name }}</td>
                <td>
                    <button class="btn btn-sm btn-info editBtn" data-id="{{ $role->id }}"><i class="fas fa-edit"></i></button>
                    <button class="btn btn-sm btn-danger deleteBtn" data-id="{{ $role->id }}"><i class="fas fa-trash"></i></button>
                    <button class="btn btn-sm btn-success assignBtn" data-id="{{ $role->id }}"><i class="fas fa-key"></i> Permissions</button>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

<!-- Add/Edit Role Modal -->
<div class="modal fade" id="roleModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="roleForm">
        @csrf
        <input type="hidden" id="roleId">
        <div class="modal-header">
          <h5 class="modal-title">Add Role</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
            <input type="text" id="roleName" class="form-control" placeholder="Role Name" required>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-primary">Save</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Assign Permissions Fullscreen Modal -->
<div class="modal fade" id="assignPermissionsModal" tabindex="-1">
  <div class="modal-dialog modal-fullscreen">
    <div class="modal-content">
      <form id="assignPermissionsForm">
        @csrf
        <input type="hidden" id="assignRoleId">
        <div class="modal-header">
          <h5 class="modal-title">Assign Permissions</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          @foreach($permissions->groupBy('group.name') as $groupName => $groupPermissions)
          <div class="mb-4">
              <h5>{{ $groupName ?? 'Ungrouped' }}</h5>
              <div class="row">
                  @foreach($groupPermissions as $perm)
                  <div class="col-md-3">
                      <div class="form-check">
                          <input class="form-check-input permissionCheckbox" type="checkbox" value="{{ $perm->id }}" id="perm-{{ $perm->id }}">
                          <label class="form-check-label" for="perm-{{ $perm->id }}">{{ $perm->name }}</label>
                      </div>
                  </div>
                  @endforeach
              </div>
          </div>
          @endforeach
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-primary">Save Permissions</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
const roleModal = new bootstrap.Modal(document.getElementById('roleModal'));
const assignModal = new bootstrap.Modal(document.getElementById('assignPermissionsModal'));

// Add/Edit Role
document.getElementById('addRoleBtn').addEventListener('click', ()=>{
    document.getElementById('roleForm').reset();
    document.getElementById('roleId').value = '';
    roleModal.show();
});

document.querySelectorAll('.editBtn').forEach(btn=>{
    btn.addEventListener('click', ()=>{
        const id = btn.dataset.id;
        fetch(`/admin/roles/${id}/edit`).then(r=>r.json()).then(d=>{
            document.getElementById('roleId').value = d.id;
            document.getElementById('roleName').value = d.name;
            roleModal.show();
        });
    });
});

document.getElementById('roleForm').addEventListener('submit', e=>{
    e.preventDefault();
    const id = document.getElementById('roleId').value;
    const url = id ? `/admin/roles/${id}` : '/admin/roles';
    const method = id ? 'PUT' : 'POST';
    fetch(url,{
        method:method,
        headers:{'Content-Type':'application/json','X-CSRF-TOKEN':token},
        body: JSON.stringify({name: document.getElementById('roleName').value})
    }).then(r=>r.json()).then(d=>location.reload());
});

// Delete Role
document.querySelectorAll('.deleteBtn').forEach(btn=>{
    btn.addEventListener('click', ()=>{
        const id = btn.dataset.id;
        if(confirm('Delete this role?')){
            fetch(`/admin/roles/${id}`, {method:'DELETE', headers:{'X-CSRF-TOKEN':token}})
            .then(()=>location.reload());
        }
    });
});

// Assign Permissions
document.querySelectorAll('.assignBtn').forEach(btn=>{
    btn.addEventListener('click', ()=>{
        const roleId = btn.dataset.id;
        fetch(`/admin/roles/${roleId}/permissions`).then(r=>r.json()).then(d=>{
            document.getElementById('assignRoleId').value = d.role.id;
            document.querySelectorAll('.permissionCheckbox').forEach(c=>{
                c.checked = d.rolePermissions.includes(parseInt(c.value));
            });
            assignModal.show();
        });
    });
});

document.getElementById('assignPermissionsForm').addEventListener('submit', e => {
    e.preventDefault();
    const roleId = document.getElementById('assignRoleId').value;
    const permissions = Array.from(document.querySelectorAll('.permissionCheckbox:checked'))
                             .map(c => parseInt(c.value)); // <-- fix

    fetch(`/admin/roles/${roleId}/assign-permissions`, {
        method: 'POST',
        headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': token},
        body: JSON.stringify({permissions})
    })
    .then(r => r.json())
    .then(d => {
        if(d.success){
            alert(d.message);
            assignModal.hide();
        }
    });
});

</script>
@endpush
