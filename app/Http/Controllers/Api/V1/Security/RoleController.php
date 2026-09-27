<?php

namespace App\Http\Controllers\Api\V1\Security;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Models\Role;
use Illuminate\Http\Request;

class RoleController extends BaseApiController
{
    public function index(Request $request)
    {
        $query = Role::with('company', 'permissions');

        if ($request->has('company_id')) {
            $query->where('company_id', $request->company_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%");
        }

        $roles = $query->orderBy('name')->paginate($request->get('per_page', 15));

        return $this->paginated($roles);
    }

    public function store(Request $request)
    {
        $data = $this->validateRequest($request, [
            'company_id' => 'required|exists:companies,id',
            'name' => 'required|string|unique:roles,name|max:100',
            'description' => 'nullable|string',
            'status' => 'required|in:active,inactive',
            'permission_ids' => 'nullable|array',
            'permission_ids.*' => 'exists:permissions,id',
        ]);

        $permissionIds = $data['permission_ids'] ?? [];
        unset($data['permission_ids']);

        $role = Role::create($data);

        if (! empty($permissionIds)) {
            $role->permissions()->sync($permissionIds);
        }

        return $this->success($role->load('permissions'), 'Role created successfully', 201);
    }

    public function show(Role $role)
    {
        return $this->success($role->load('company', 'permissions'));
    }

    public function update(Request $request, Role $role)
    {
        $data = $this->validateRequest($request, [
            'name' => 'sometimes|string|unique:roles,name,'.$role->id.'|max:100',
            'description' => 'nullable|string',
            'status' => 'sometimes|in:active,inactive',
            'permission_ids' => 'nullable|array',
            'permission_ids.*' => 'exists:permissions,id',
        ]);

        $permissionIds = $data['permission_ids'] ?? null;
        unset($data['permission_ids']);

        $role->update($data);

        if ($permissionIds !== null) {
            $role->permissions()->sync($permissionIds);
        }

        return $this->success($role->load('permissions'), 'Role updated successfully');
    }

    public function destroy(Role $role)
    {
        $role->delete();

        return $this->success(null, 'Role deleted successfully');
    }
}
