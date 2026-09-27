<?php

namespace App\Http\Controllers\Api\V1\Notification;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends BaseApiController
{
    public function index(Request $request)
    {
        $query = Notification::with('user');

        if ($request->has('company_id')) {
            $query->where('company_id', $request->company_id);
        }

        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        } else {
            $query->where('user_id', auth()->id());
        }

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('is_read')) {
            $query->where('is_read', $request->boolean('is_read'));
        }

        if ($request->has('date_from')) {
            $query->where('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->where('created_at', '<=', $request->date_to);
        }

        $notifications = $query->latest()->paginate($request->get('per_page', 15));

        return $this->paginated($notifications);
    }

    public function store(Request $request)
    {
        $data = $this->validateRequest($request, [
            'company_id' => 'required|exists:companies,id',
            'user_id' => 'required|exists:users,id',
            'type' => 'required|string|in:low_stock,expiry,debt_due,large_refund,large_discount,cash_difference,suspicious_activity,approval_request',
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'reference_type' => 'nullable|string',
            'reference_id' => 'nullable|string',
        ]);

        $notification = Notification::create($data);

        return $this->success($notification->load('user'), 'Notification created successfully', 201);
    }

    public function show(Notification $notification)
    {
        return $this->success($notification->load('user'));
    }

    public function update(Request $request, Notification $notification)
    {
        $data = $this->validateRequest($request, [
            'is_read' => 'sometimes|boolean',
        ]);

        $notification->update($data);

        return $this->success($notification, 'Notification updated successfully');
    }

    public function markAllRead(Request $request)
    {
        $query = Notification::where('user_id', auth()->id())
            ->where('is_read', false);

        $count = $query->count();
        $query->update(['is_read' => true]);

        return $this->success(['updated' => $count], 'All notifications marked as read');
    }

    public function destroy(Notification $notification)
    {
        $notification->delete();

        return $this->success(null, 'Notification deleted successfully');
    }
}
