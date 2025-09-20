@extends('admin.layouts.admin-layout')

@section('title','Users')

@section('content')
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>Users</h2>
        <button class="btn btn-primary" id="addUserBtn"><i class="fas fa-plus"></i> Add User</button>
    </div>

    <table class="table table-striped" id="usersTable">
        <thead>
            <tr>
                <th>ID</th><th>Name</th><th>Email</th><th>Roles</th><th>Permissions</th><th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($users as $user)
            <tr id="user-{{ $user->id }}">
                <td>{{ $user->id }}</td>
                <td>{{ $user->name }}</td>
                <td>{{ $user->email }}</td>
                <td>{{ $user->roles->pluck('name')->join(', ') }}</td>
                <td>{{ $user->permissions->pluck('name')->join(', ') }}</td>
                <td>
                    <button class="btn btn-sm btn-info editBtn" data-id="{{ $user->id }}"><i class="fas fa-edit"></i></button>
                    <button class="btn btn-sm btn-danger deleteBtn" data-id="{{ $user->id }}"><i class="fas fa-trash"></i></button>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

<!-- User Modal -->
<div class="modal fade" id="userModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form id="userForm">
        <div class="modal-header">
          <h5 class="modal-title" id="userModalLabel">Add User</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          @csrf
          <input type="hidden" id="userId">
          <div class="row">
            <div class="col-md-6 mb-3">
              <label>Name</label>
              <input type="text" class="form-control" id="userName" name="name" required>
            </div>
            <div class="col-md-6 mb-3">
              <label>Email</label>
              <input type="email" class="form-control" id="userEmail" name="email" required>
            </div>
            <div class="col-md-6 mb-3">
              <label>Password</label>
              <input type="password" class="form-control" id="userPassword" name="password">
            </div>
          </div>
          <div class="mb-3">
            <label>Roles</label>
            <div class="d-flex flex-wrap">
              @foreach($roles as $role)
                <div class="form-check me-3">
                  <input class="form-check-input roleCheckbox" type="checkbox" value="{{ $role->name }}" id="role{{ $role->id }}">
                  <label class="form-check-label" for="role{{ $role->id }}">{{ $role->name }}</label>
                </div>
              @endforeach
            </div>
          </div>
          <div class="mb-3">
            <label>Direct Permissions</label>
            @foreach($permissions->groupBy('group.name') as $groupName => $perms)
              <h6 class="mt-2">{{ $groupName ?? 'Ungrouped' }}</h6>
              <div class="d-flex flex-wrap">
                @foreach($perms as $perm)
                  <div class="form-check me-3">
                    <input class="form-check-input permCheckbox" type="checkbox" value="{{ $perm->name }}" id="perm{{ $perm->id }}">
                    <label class="form-check-label" for="perm{{ $perm->id }}">{{ $perm->name }}</label>
                  </div>
                @endforeach
              </div>
            @endforeach
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-primary" id="saveUserBtn">Save</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function(){
    const modal = new bootstrap.Modal(document.getElementById('userModal'));
    const form = document.getElementById('userForm');
    const token = document.querySelector('meta[name="csrf-token"]').content;

    const userIdInput = document.getElementById('userId');
    const nameInput = document.getElementById('userName');
    const emailInput = document.getElementById('userEmail');
    const passwordInput = document.getElementById('userPassword');

    document.getElementById('addUserBtn').addEventListener('click', ()=>{
        form.reset();
        userIdInput.value='';
        document.querySelectorAll('.roleCheckbox,.permCheckbox').forEach(c=>c.checked=false);
        document.getElementById('userModalLabel').textContent='Add User';
        modal.show();
    });

    document.querySelectorAll('.editBtn').forEach(btn=>{
        btn.addEventListener('click', ()=>{
            fetch(`/admin/users/${btn.dataset.id}/edit`)
            .then(res=>res.json())
            .then(data=>{
                userIdInput.value=data.user.id;
                nameInput.value=data.user.name;
                emailInput.value=data.user.email;
                passwordInput.value='';
                document.querySelectorAll('.roleCheckbox').forEach(c=>c.checked=data.user_roles.includes(c.value));
                document.querySelectorAll('.permCheckbox').forEach(c=>c.checked=data.user_perms.includes(c.value));
                document.getElementById('userModalLabel').textContent='Edit User';
                modal.show();
            });
        });
    });

    form.addEventListener('submit', function(e){
        e.preventDefault();
        const id=userIdInput.value;
        const url=id?`/admin/users/${id}`:'/admin/users';
        const method=id?'PUT':'POST';
        const payload={
            name:nameInput.value,
            email:emailInput.value,
            password:passwordInput.value,
            roles:Array.from(document.querySelectorAll('.roleCheckbox:checked')).map(c=>c.value),
            permissions:Array.from(document.querySelectorAll('.permCheckbox:checked')).map(c=>c.value)
        };
        fetch(url,{method:method,headers:{'Content-Type':'application/json','X-CSRF-TOKEN':token},body:JSON.stringify(payload)})
        .then(async res=>{
            if(res.status===422){ let data=await res.json(); Swal.fire('Validation error',JSON.stringify(data.errors),'error'); }
            else return res.json();
        })
        .then(data=>{
            if(data){
                Swal.fire('Success', data.message,'success').then(()=>location.reload());
            }
        });
    });

    document.addEventListener('click',function(e){
        if(e.target.closest('.deleteBtn')){
            let id=e.target.closest('.deleteBtn').dataset.id;
            Swal.fire({title:'Delete?',text:'This will remove user.',icon:'warning',showCancelButton:true})
            .then(result=>{
                if(result.isConfirmed){
                    fetch(`/admin/users/${id}`,{method:'DELETE',headers:{'X-CSRF-TOKEN':token}})
                    .then(res=>res.json())
                    .then(data=>{
                        Swal.fire('Deleted!',data.message,'success').then(()=>location.reload());
                    });
                }
            });
        }
    });
});
</script>
@endpush
