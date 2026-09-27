<?php

namespace App\Http\Controllers\Api\V1\Security;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends BaseApiController
{
    public function index(Request $request)
    {
        $query = User::with('employee', 'roles');

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('username', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhereHas('employee', fn ($eq) => $eq->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%"));
            });
        }

        $users = $query->latest()->paginate($request->get('per_page', 15));

        return $this->paginated($users);
    }

    public function store(Request $request)
    {
        $data = $this->validateRequest($request, [
            'employee_id' => 'required|exists:employees,id|unique:users,employee_id',
            'username' => 'required|string|unique:users,username|max:100',
            'email' => 'required|email|unique:users,email|max:255',
            'password' => 'required|string|min:8',
            'status' => 'required|in:active,inactive',
        ]);

        $user = User::create([
            'employee_id' => $data['employee_id'],
            'username' => $data['username'],
            'email' => $data['email'],
            'password_hash' => Hash::make($data['password']),
            'status' => $data['status'],
        ]);

        return $this->success($user->load('employee', 'roles'), 'User created successfully', 201);
    }

    public function show(User $user)
    {
        return $this->success($user->load('employee', 'roles.permissions'));
    }

    public function update(Request $request, User $user)
    {
        $data = $this->validateRequest($request, [
            'username' => 'sometimes|string|unique:users,username,'.$user->id.'|max:100',
            'email' => 'sometimes|email|unique:users,email,'.$user->id.'|max:255',
            'status' => 'sometimes|in:active,inactive',
        ]);

        $user->update($data);

        return $this->success($user->load('employee', 'roles'), 'User updated successfully');
    }

    public function resetPassword(Request $request, User $user)
    {
        $data = $this->validateRequest($request, [
            'password' => 'required|string|min:8',
        ]);

        $user->update([
            'password_hash' => Hash::make($data['password']),
        ]);

        return $this->success(null, 'Password reset successfully');
    }

    public function destroy(User $user)
    {
        $user->delete();

        return $this->success(null, 'User deleted successfully');
    }
}
