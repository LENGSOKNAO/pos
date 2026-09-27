<?php

namespace App\Http\Controllers\Api\V1\Sales;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Models\SalesOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class QuotationController extends BaseApiController
{
    public function index(Request $request)
    {
        $query = Quotation::with(['customer', 'branch', 'creator', 'items.product']);

        if ($request->has('company_id')) {
            $query->where('company_id', $request->company_id);
        }

        if ($request->has('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->has('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('date_from')) {
            $query->where('quotation_date', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->where('quotation_date', '<=', $request->date_to);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('quotation_number', 'like', "%{$search}%")
                    ->orWhereHas('customer', fn ($cq) => $cq->where('name', 'like', "%{$search}%"));
            });
        }

        $quotations = $query->latest()->paginate($request->get('per_page', 15));

        return $this->paginated($quotations);
    }

    public function store(Request $request)
    {
        $data = $this->validateRequest($request, [
            'company_id' => 'required|exists:companies,id',
            'branch_id' => 'required|exists:branches,id',
            'customer_id' => 'required|exists:customers,id',
            'expiry_date' => 'required|date|after_or_equal:today',
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

            $quotation = Quotation::create([
                'company_id' => $data['company_id'],
                'branch_id' => $data['branch_id'],
                'customer_id' => $data['customer_id'],
                'quotation_number' => 'QT-'.now()->format('YmdHis').'-'.rand(1000, 9999),
                'quotation_date' => now(),
                'expiry_date' => $data['expiry_date'],
                'subtotal' => $subtotal,
                'discount' => 0,
                'tax' => 0,
                'total' => $subtotal,
                'status' => 'draft',
                'created_by' => auth()->id(),
            ]);

            foreach ($data['items'] as $item) {
                $itemTotal = $item['quantity'] * $item['unit_price'];
                $itemDiscount = $item['discount'] ?? 0;
                $itemTax = $item['tax'] ?? 0;
                $itemNetTotal = $itemTotal - $itemDiscount + $itemTax;

                QuotationItem::create([
                    'quotation_id' => $quotation->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'discount' => $itemDiscount,
                    'tax' => $itemTax,
                    'total' => $itemNetTotal,
                ]);
            }

            return $this->success($quotation->load('items.product'), 'Quotation created successfully', 201);
        });
    }

    public function show(Quotation $quotation)
    {
        return $this->success($quotation->load(['customer', 'branch', 'creator', 'items.product']));
    }

    public function update(Request $request, Quotation $quotation)
    {
        if ($quotation->status !== 'draft') {
            return $this->error('Cannot update quotation in current status', 400);
        }

        $data = $this->validateRequest($request, [
            'customer_id' => 'sometimes|exists:customers,id',
            'expiry_date' => 'sometimes|date|after_or_equal:today',
            'items' => 'sometimes|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0.0001',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.discount' => 'nullable|numeric|min:0',
            'items.*.tax' => 'nullable|numeric|min:0',
        ]);

        return DB::transaction(function () use ($data, $quotation) {
            $quotation->update($data);

            if (isset($data['items'])) {
                $quotation->items()->delete();

                foreach ($data['items'] as $item) {
                    $itemTotal = $item['quantity'] * $item['unit_price'];
                    $itemDiscount = $item['discount'] ?? 0;
                    $itemTax = $item['tax'] ?? 0;
                    $itemNetTotal = $itemTotal - $itemDiscount + $itemTax;

                    QuotationItem::create([
                        'quotation_id' => $quotation->id,
                        'product_id' => $item['product_id'],
                        'quantity' => $item['quantity'],
                        'unit_price' => $item['unit_price'],
                        'discount' => $itemDiscount,
                        'tax' => $itemTax,
                        'total' => $itemNetTotal,
                    ]);
                }
            }

            return $this->success($quotation->load('items.product'), 'Quotation updated successfully');
        });
    }

    public function convert(Request $request, Quotation $quotation)
    {
        if ($quotation->status !== 'sent' && $quotation->status !== 'accepted') {
            return $this->error('Quotation cannot be converted in current status', 400);
        }

        return DB::transaction(function () use ($quotation) {
            $salesOrder = SalesOrder::create([
                'company_id' => $quotation->company_id,
                'branch_id' => $quotation->branch_id,
                'customer_id' => $quotation->customer_id,
                'employee_id' => $quotation->creator?->employee_id ?? auth()->user()->employee_id,
                'order_number' => 'SO-'.now()->format('YmdHis').'-'.rand(1000, 9999),
                'order_type' => 'quotation',
                'subtotal' => $quotation->subtotal,
                'discount' => $quotation->discount,
                'tax' => $quotation->tax,
                'total' => $quotation->total,
                'status' => 'confirmed',
            ]);

            foreach ($quotation->items as $item) {
                SalesOrderItem::create([
                    'sales_order_id' => $salesOrder->id,
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'discount' => $item->discount,
                    'tax' => $item->tax,
                    'cost_price' => $item->product->cost_price,
                    'total' => $item->total,
                ]);
            }

            $quotation->update([
                'status' => 'converted',
            ]);

            return $this->success($salesOrder->load('items.product'), 'Quotation converted to sales order successfully');
        });
    }

    public function destroy(Quotation $quotation)
    {
        if ($quotation->status !== 'draft' && $quotation->status !== 'expired') {
            return $this->error('Cannot delete quotation in current status', 400);
        }

        $quotation->delete();

        return $this->success(null, 'Quotation deleted successfully');
    }
}
