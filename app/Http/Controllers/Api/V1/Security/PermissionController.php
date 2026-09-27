<?php

namespace App\Http\Controllers\Api\V1\Security;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Models\Permission;
use Illuminate\Http\Request;

class PermissionController extends BaseApiController
{
    public function index(Request $request)
    {
        $query = Permission::query();

        if ($request->has('module')) {
            $query->where('module', $request->module);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            });
        }

        $permissions = $query->orderBy('module')->orderBy('code')->paginate($request->get('per_page', 15));

        return $this->paginated($permissions);
    }

    public function store(Request $request)
    {
        $data = $this->validateRequest($request, [
            'code' => 'required|string|unique:permissions,code|max:100',
            'name' => 'required|string|max:255',
            'module' => 'required|string|max:50',
        ]);

        $permission = Permission::create($data);

        return $this->success($permission, 'Permission created successfully', 201);
    }

    public function show(Permission $permission)
    {
        return $this->success($permission->load('roles'));
    }

    public function update(Request $request, Permission $permission)
    {
        $data = $this->validateRequest($request, [
            'code' => 'sometimes|string|unique:permissions,code,'.$permission->id.'|max:100',
            'name' => 'sometimes|string|max:255',
            'module' => 'sometimes|string|max:50',
        ]);

        $permission->update($data);

        return $this->success($permission, 'Permission updated successfully');
    }

    public function destroy(Permission $permission)
    {
        $permission->delete();

        return $this->success(null, 'Permission deleted successfully');
    }
}
