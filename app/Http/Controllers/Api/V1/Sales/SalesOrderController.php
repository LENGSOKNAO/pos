<?php

namespace App\Http\Controllers\Api\V1\Sales;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SalesOrderController extends BaseApiController
{
    public function index(Request $request)
    {
        $query = SalesOrder::with(['customer', 'employee', 'branch', 'items.product']);

        if ($request->has('company_id')) {
            $query->where('company_id', $request->company_id);
        }

        if ($request->has('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->has('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        if ($request->has('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('order_type')) {
            $query->where('order_type', $request->order_type);
        }

        if ($request->has('date_from')) {
            $query->where('order_date', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->where('order_date', '<=', $request->date_to);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                    ->orWhereHas('customer', fn ($cq) => $cq->where('name', 'like', "%{$search}%"));
            });
        }

        $orders = $query->latest()->paginate($request->get('per_page', 15));

        return $this->paginated($orders);
    }

    public function store(Request $request)
    {
        $data = $this->validateRequest($request, [
            'company_id' => 'required|exists:companies,id',
            'branch_id' => 'required|exists:branches,id',
            'customer_id' => 'nullable|exists:customers,id',
            'employee_id' => 'required|exists:employees,id',
            'order_type' => 'required|in:pos,online,wholesale,quotation',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0.0001',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.discount' => 'nullable|numeric|min:0',
            'items.*.tax' => 'nullable|numeric|min:0',
        ]);

        return DB::transaction(function () use ($data) {
            $subtotal = 0;
            foreach ($data['items'] as $item) {
                $itemTotal = $item['quantity'] * $item['unit_price'];
                $itemDiscount = $item['discount'] ?? 0;
                $itemTax = $item['tax'] ?? 0;
                $subtotal += $itemTotal - $itemDiscount + $itemTax;
            }

            $salesOrder = SalesOrder::create([
                'company_id' => $data['company_id'],
                'branch_id' => $data['branch_id'],
                'customer_id' => $data['customer_id'],
                'employee_id' => $data['employee_id'],
                'order_number' => 'SO-'.now()->format('YmdHis').'-'.rand(1000, 9999),
                'order_type' => $data['order_type'],
                'subtotal' => $subtotal,
                'discount' => 0,
                'tax' => 0,
                'total' => $subtotal,
                'status' => 'draft',
            ]);

            foreach ($data['items'] as $item) {
                $product = Product::findOrFail($item['product_id']);
                $itemTotal = $item['quantity'] * $item['unit_price'];
                $itemDiscount = $item['discount'] ?? 0;
                $itemTax = $item['tax'] ?? 0;
                $itemNetTotal = $itemTotal - $itemDiscount + $itemTax;

                SalesOrderItem::create([
                    'sales_order_id' => $salesOrder->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'discount' => $itemDiscount,
                    'tax' => $itemTax,
                    'cost_price' => $product->cost_price,
                    'total' => $itemNetTotal,
                ]);
            }

            return $this->success($salesOrder->load('items.product'), 'Sales order created successfully', 201);
        });
    }

    public function show(SalesOrder $salesOrder)
    {
        return $this->success($salesOrder->load(['customer', 'employee', 'branch', 'items.product', 'invoices', 'deliveryOrders']));
    }

    public function update(Request $request, SalesOrder $salesOrder)
    {
        if ($salesOrder->status !== 'draft') {
            return $this->error('Cannot update order in current status', 400);
        }

        $data = $this->validateRequest($request, [
            'customer_id' => 'nullable|exists:customers,id',
            'employee_id' => 'sometimes|exists:employees,id',
            'order_type' => 'sometimes|in:pos,online,wholesale,quotation',
            'items' => 'sometimes|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0.0001',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.discount' => 'nullable|numeric|min:0',
            'items.*.tax' => 'nullable|numeric|min:0',
        ]);

        return DB::transaction(function () use ($data, $salesOrder) {
            $salesOrder->update($data);

            if (isset($data['items'])) {
                $salesOrder->items()->delete();

                foreach ($data['items'] as $item) {
                    $product = Product::findOrFail($item['product_id']);
                    $itemTotal = $item['quantity'] * $item['unit_price'];
                    $itemDiscount = $item['discount'] ?? 0;
                    $itemTax = $item['tax'] ?? 0;
                    $itemNetTotal = $itemTotal - $itemDiscount + $itemTax;

                    SalesOrderItem::create([
                        'sales_order_id' => $salesOrder->id,
                        'product_id' => $item['product_id'],
                        'quantity' => $item['quantity'],
                        'unit_price' => $item['unit_price'],
                        'discount' => $itemDiscount,
                        'tax' => $itemTax,
                        'cost_price' => $product->cost_price,
                        'total' => $itemNetTotal,
                    ]);
                }
            }

            return $this->success($salesOrder->load('items.product'), 'Sales order updated successfully');
        });
    }

    public function destroy(SalesOrder $salesOrder)
    {
        if ($salesOrder->status !== 'draft' && $salesOrder->status !== 'held') {
            return $this->error('Cannot delete order in current status', 400);
        }

        $salesOrder->delete();

        return $this->success(null, 'Sales order deleted successfully');
    }
}
