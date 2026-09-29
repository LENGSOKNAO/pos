<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\CashRegister;
use App\Models\CashSession;
use App\Models\Company;
use App\Models\Customer;
use App\Models\CustomerGroup;
use App\Models\Employee;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Role;
use App\Models\SalesReturn;
use App\Models\Stock;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class PageController extends Controller
{
    public function customers(Request $request): Response
    {
        return Inertia::render('customers/index', [
            'customers' => Inertia::defer(fn () => Customer::with('customerGroup:id,name')
                ->orderBy('name')
                ->paginate(15)
                ->through(fn (Customer $c): array => [
                    'id' => $c->id,
                    'company_id' => $c->company_id,
                    'customer_code' => $c->customer_code,
                    'name' => $c->name,
                    'phone' => $c->phone,
                    'email' => $c->email,
                    'address' => $c->address,
                    'customer_group_id' => $c->customer_group_id,
                    'customer_group' => $c->customerGroup ? ['id' => $c->customerGroup->id, 'name' => $c->customerGroup->name] : null,
                    'credit_limit' => (float) $c->credit_limit,
                    'credit_days' => $c->credit_days,
                    'loyalty_points' => (float) $c->loyalty_points,
                    'status' => $c->status,
                ])),
            'groups' => CustomerGroup::where('status', 'active')->orderBy('name')->get(['id', 'name']),
            'companies' => Company::where('status', 'active')->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function suppliers(Request $request): Response
    {
        $search = $request->get('search', '');

        return Inertia::render('suppliers/index', [
            'suppliers' => Inertia::defer(fn () => Supplier::query()
                ->when($search, fn ($q) => $q->where(fn ($w) => $w->where('name', 'like', "%{$search}%")->orWhere('phone', 'like', "%{$search}%")))
                ->orderBy('name')
                ->paginate(15)
                ->through(fn (Supplier $s): array => [
                    'id' => $s->id,
                    'name' => $s->name,
                    'code' => $s->supplier_code,
                    'phone' => $s->phone,
                    'balance' => 0,
                    'status' => $s->status,
                ])),
            'filters' => ['search' => $search],
        ]);
    }

    public function storeSupplier(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:50',
        ]);

        Supplier::create([
            'company_id' => $request->user()->employee?->company_id,
            'supplier_code' => 'SUP-'.now()->format('YmdHis').'-'.rand(100, 999),
            'name' => $data['name'],
            'phone' => $data['phone'] ?? null,
            'status' => 'active',
        ]);

        return redirect()->route('suppliers.index')->with('success', 'Supplier created successfully');
    }

    public function sales(Request $request): Response
    {
        return Inertia::render('sales/index', [
            'sales' => Inertia::defer(fn () => Invoice::with('customer:id,name')
                ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
                ->when($request->filled('from'), fn ($q) => $q->whereDate('invoice_date', '>=', $request->from))
                ->when($request->filled('to'), fn ($q) => $q->whereDate('invoice_date', '<=', $request->to))
                ->orderByDesc('invoice_date')
                ->orderByDesc('id')
                ->paginate(15)
                ->through(fn (Invoice $i): array => [
                    'id' => $i->id,
                    'invoice_no' => $i->invoice_number,
                    'customer' => $i->customer?->name,
                    'status' => $i->status,
                    'grand_total' => (float) $i->total,
                    'paid_amount' => (float) $i->paid_amount,
                    'balance' => max(0, (float) $i->total - (float) $i->paid_amount),
                    'created_at' => $i->created_at?->format('Y-m-d H:i'),
                ])),
            'filters' => [
                'status' => $request->get('status', ''),
                'from' => $request->get('from', ''),
                'to' => $request->get('to', ''),
            ],
        ]);
    }

    public function saleShow(Invoice $invoice): Response
    {
        $invoice->load(['customer:id,name,phone', 'items', 'payments.paymentMethod:id,name']);

        return Inertia::render('sales/show', [
            'sale' => [
                'id' => $invoice->id,
                'invoice_no' => $invoice->invoice_number,
                'status' => $invoice->status,
                'subtotal' => (float) $invoice->subtotal,
                'tax_amount' => (float) $invoice->tax,
                'discount_amount' => (float) $invoice->discount,
                'grand_total' => (float) $invoice->total,
                'paid_amount' => (float) $invoice->paid_amount,
                'customer' => $invoice->customer ? ['name' => $invoice->customer->name, 'phone' => $invoice->customer->phone] : null,
                'created_at' => $invoice->created_at?->format('Y-m-d H:i'),
                'items' => $invoice->items->map(fn (InvoiceItem $item): array => [
                    'id' => $item->id,
                    'quantity' => (float) $item->quantity,
                    'unit_price' => (float) $item->unit_price,
                    'line_total' => (float) $item->total,
                ])->values()->all(),
                'payments' => $invoice->payments->map(fn ($p): array => [
                    'id' => $p->id,
                    'method' => $p->paymentMethod?->name,
                    'amount' => (float) $p->amount,
                ])->values()->all(),
            ],
        ]);
    }

    public function salesReturns(): Response
    {
        return Inertia::render('sales-returns/index', [
            'returns' => Inertia::defer(fn () => SalesReturn::with('invoice:id,invoice_number')
                ->orderByDesc('id')
                ->paginate(15)
                ->through(fn (SalesReturn $r): array => [
                    'id' => $r->id,
                    'return_no' => $r->return_number,
                    'sale' => $r->invoice?->invoice_number,
                    'status' => $r->status,
                    'total' => (float) $r->refund_amount,
                    'created_at' => $r->created_at?->format('Y-m-d H:i'),
                ])),
            'sales' => Invoice::orderByDesc('id')->limit(50)->get(['id', 'invoice_number'])->map(fn (Invoice $i): array => [
                'id' => $i->id,
                'invoice_no' => $i->invoice_number,
            ]),
        ]);
    }

    public function storeSalesReturn(Request $request)
    {
        $data = $request->validate([
            'sale_id' => 'required|exists:invoices,id',
            'warehouse_id' => 'nullable',
            'items' => 'required|array|min:1',
            'items.*.sale_item_id' => 'required',
            'items.*.quantity' => 'required|numeric|min:0.0001',
        ]);

        $refund = 0;
        foreach ($data['items'] as $row) {
            $item = InvoiceItem::find($row['sale_item_id']);
            if ($item) {
                $refund += (float) $item->total * ((float) $row['quantity'] / max(1, (float) $item->quantity));
            }
        }

        SalesReturn::create([
            'invoice_id' => $data['sale_id'],
            'customer_id' => Invoice::find($data['sale_id'])?->customer_id,
            'branch_id' => $request->user()->employee?->branch_id,
            'return_number' => 'RET-'.now()->format('YmdHis').'-'.rand(100, 999),
            'reason' => 'Return from back office',
            'refund_amount' => $refund,
            'status' => 'pending',
            'created_by' => $request->user()->id,
        ]);

        return redirect()->route('sales.returns.index')->with('success', 'Return submitted successfully');
    }

    public function stocks(Request $request): Response
    {
        $search = $request->get('search', '');

        return Inertia::render('stocks/index', [
            'stocks' => Inertia::defer(fn () => Stock::with(['product:id,name,sku,unit_id', 'product.unit:id,symbol', 'warehouse:id,name'])
                ->when($search, fn ($q) => $q->whereHas('product', fn ($w) => $w->where('name', 'like', "%{$search}%")->orWhere('sku', 'like', "%{$search}%")))
                ->orderByDesc('quantity')
                ->limit(100)
                ->get()
                ->map(fn (Stock $s): array => [
                    'id' => $s->id,
                    'warehouse' => $s->warehouse?->name,
                    'product' => $s->product?->name,
                    'sku' => $s->product?->sku,
                    'unit' => $s->product?->unit?->symbol,
                    'quantity' => (float) $s->quantity,
                ])->values()->all()),
            'filters' => ['search' => $search],
        ]);
    }

    public function purchases(Request $request): Response
    {
        $search = $request->get('search', '');

        return Inertia::render('purchases/index', [
            'orders' => Inertia::defer(fn () => PurchaseOrder::with('supplier:id,name')
                ->when($search, fn ($q) => $q->where('order_number', 'like', "%{$search}%"))
                ->orderByDesc('id')
                ->paginate(15)
                ->through(fn (PurchaseOrder $o): array => [
                    'id' => $o->id,
                    'po_no' => $o->order_number,
                    'supplier' => $o->supplier?->name,
                    'grand_total' => (float) $o->total,
                    'status' => $o->status,
                    'order_date' => $o->order_date?->format('Y-m-d'),
                    'created_at' => $o->created_at?->format('Y-m-d H:i'),
                ])),
            'filters' => ['search' => $search],
        ]);
    }

    public function purchaseCreate(): Response
    {
        return Inertia::render('purchases/create', [
            'suppliers' => Supplier::where('status', 'active')->orderBy('name')->get(['id', 'name']),
            'warehouses' => Warehouse::orderBy('name')->get(['id', 'name']),
            'products' => Inertia::defer(fn (): array => Product::where('status', 'active')->orderBy('name')->limit(200)->get(['id', 'name', 'sku', 'unit_id'])->map(fn (Product $p): array => [
                'id' => $p->id,
                'name' => $p->name,
                'sku' => $p->sku,
                'units' => [['id' => $p->unit_id ?? $p->id]],
            ])->all()),
        ]);
    }

    public function storePurchase(Request $request)
    {
        $data = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'items' => 'required|array|min:1',
            'items.*.product_unit_id' => 'required',
            'items.*.quantity' => 'required|numeric|min:0.0001',
            'items.*.unit_cost' => 'required|numeric|min:0',
        ]);

        $employee = $request->user()->employee;

        $order = DB::transaction(function () use ($data, $employee) {
            $subtotal = 0;
            foreach ($data['items'] as $row) {
                $subtotal += (float) $row['quantity'] * (float) $row['unit_cost'];
            }

            $order = PurchaseOrder::create([
                'company_id' => $employee?->company_id,
                'branch_id' => $employee?->branch_id,
                'warehouse_id' => $data['warehouse_id'],
                'supplier_id' => $data['supplier_id'],
                'order_number' => 'PO-'.now()->format('YmdHis').'-'.rand(100, 999),
                'order_date' => now(),
                'subtotal' => $subtotal,
                'discount' => 0,
                'tax' => 0,
                'total' => $subtotal,
                'status' => 'pending',
                'created_by' => $employee?->id,
            ]);

            foreach ($data['items'] as $row) {
                $product = Product::where('unit_id', $row['product_unit_id'])->first()
                    ?? Product::find($row['product_unit_id']);
                if (! $product) {
                    continue;
                }
                $qty = (float) $row['quantity'];
                $cost = (float) $row['unit_cost'];
                PurchaseOrderItem::create([
                    'purchase_order_id' => $order->id,
                    'product_id' => $product->id,
                    'quantity' => $qty,
                    'received_quantity' => 0,
                    'unit_cost' => $cost,
                    'discount' => 0,
                    'tax' => 0,
                    'total' => $qty * $cost,
                ]);
            }

            return $order;
        });

        return redirect()->route('purchases.index')->with('success', "Purchase {$order->order_number} created successfully");
    }

    public function cashSessions(): Response
    {
        return Inertia::render('cash-sessions/index', [
            'sessions' => Inertia::defer(fn () => CashSession::with('register:id,name,terminal_number')
                ->orderByDesc('id')
                ->paginate(15)
                ->through(fn (CashSession $s): array => [
                    'id' => $s->id,
                    'register' => $s->register?->name ?? '—',
                    'opening_balance' => (float) $s->opening_cash,
                    'closing_balance' => $s->closing_cash === null ? null : (float) $s->closing_cash,
                    'expected' => (float) ($s->expected_cash ?? $s->opening_cash),
                    'status' => $s->status,
                    'opened_at' => $s->opened_at?->format('Y-m-d H:i'),
                    'closed_at' => $s->closed_at?->format('Y-m-d H:i'),
                ])),
            'registers' => CashRegister::where('status', 'active')->orderBy('name')->get(['id', 'name', 'terminal_number'])->map(fn (CashRegister $r): array => [
                'id' => $r->id,
                'name' => $r->name,
                'code' => $r->terminal_number,
            ]),
        ]);
    }

    public function openCashSession(Request $request)
    {
        $data = $request->validate([
            'cash_register_id' => 'required|exists:cash_registers,id',
            'opening_balance' => 'required|numeric|min:0',
        ]);

        $exists = CashSession::where('register_id', $data['cash_register_id'])->where('status', 'open')->first();
        if ($exists) {
            return back()->withErrors(['cash_register_id' => 'Register already has an open session.']);
        }

        CashSession::create([
            'register_id' => $data['cash_register_id'],
            'employee_id' => $request->user()->employee?->id,
            'opening_cash' => $data['opening_balance'],
            'status' => 'open',
            'opened_at' => now(),
        ]);

        return redirect()->route('cash-sessions.index')->with('success', 'Cash session opened successfully');
    }

    public function closeCashSession(Request $request, CashSession $cashSession)
    {
        $data = $request->validate([
            'closing_balance' => 'required|numeric|min:0',
        ]);

        $cashSession->update([
            'closing_cash' => $data['closing_balance'],
            'expected_cash' => $cashSession->expected_cash ?? $cashSession->opening_cash,
            'difference' => (float) $data['closing_balance'] - (float) ($cashSession->expected_cash ?? $cashSession->opening_cash),
            'status' => 'closed',
            'closed_at' => now(),
        ]);

        return redirect()->route('cash-sessions.index')->with('success', 'Cash session closed successfully');
    }

    public function users(): Response
    {
        return Inertia::render('users/index', [
            'users' => Inertia::defer(fn (): array => User::with(['employee.branch:id,name', 'roles:id,name'])
                ->orderBy('username')
                ->get()
                ->map(function (User $u): array {
                    $name = trim(($u->employee?->first_name ?? '').' '.($u->employee?->last_name ?? '')) ?: $u->username;

                    return [
                        'id' => $u->id,
                        'name' => $name,
                        'username' => $u->username,
                        'email' => $u->email,
                        'status' => $u->status,
                        'roles' => $u->roles->map(fn ($r): array => ['id' => $r->id, 'name' => $r->name])->all(),
                        'branches' => $u->employee?->branch ? [['id' => $u->employee->branch->id, 'name' => $u->employee->branch->name]] : [],
                    ];
                })->values()->all()),
            'roles' => Role::orderBy('name')->get(['id', 'name']),
            'branches' => Branch::where('status', 'active')->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function storeUser(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:100|unique:users,username',
            'email' => 'nullable|email|max:255',
            'password' => 'required|string|min:8',
            'role_id' => 'nullable|exists:roles,id',
            'branch_id' => 'nullable|exists:branches,id',
        ]);

        $parts = preg_split('/\s+/', trim($data['name']), 2);

        $employee = Employee::create([
            'company_id' => $request->user()->employee?->company_id,
            'branch_id' => $data['branch_id'] ?? $request->user()->employee?->branch_id,
            'employee_code' => 'EMP-'.now()->format('YmdHis').'-'.rand(100, 999),
            'first_name' => $parts[0],
            'last_name' => $parts[1] ?? '',
            'position' => 'Staff',
            'status' => 'active',
        ]);

        $user = User::create([
            'username' => $data['username'],
            'email' => $data['email'] ?? null,
            'password_hash' => Hash::make($data['password']),
            'employee_id' => $employee->id,
            'status' => 'active',
        ]);

        if (! empty($data['role_id'])) {
            $user->roles()->attach($data['role_id']);
        }

        return redirect()->route('users.index')->with('success', 'User created successfully');
    }

    public function reports(Request $request): Response
    {
        $from = $request->get('from', now()->subDays(30)->toDateString());
        $to = $request->get('to', now()->toDateString());
        $branchId = $request->get('branch_id');

        return Inertia::render('reports/index', [
            'filters' => ['from' => $from, 'to' => $to, 'branch_id' => $branchId],
            'branches' => Branch::where('status', 'active')->orderBy('name')->get(['id', 'name']),
            'summary' => Inertia::defer(function () use ($from, $to, $branchId): array {
                $invoices = Invoice::query()
                    ->where('status', 'paid')
                    ->whereDate('invoice_date', '>=', $from)
                    ->whereDate('invoice_date', '<=', $to)
                    ->when($branchId, fn ($q) => $q->where('branch_id', $branchId));

                $revenue = (float) (clone $invoices)->sum('total');
                $orderCount = (clone $invoices)->count();
                $ids = (clone $invoices)->pluck('id');

                $cogs = (float) InvoiceItem::whereIn('invoice_id', $ids)->selectRaw('COALESCE(SUM(quantity * cost_price), 0) as c')->value('c');
                $expensesTotal = (float) Expense::whereDate('expense_date', '>=', $from)
                    ->whereDate('expense_date', '<=', $to)
                    ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
                    ->sum('amount');

                $grossProfit = $revenue - $cogs;

                return [
                    'revenue' => $revenue,
                    'orderCount' => $orderCount,
                    'avgTicket' => $orderCount > 0 ? $revenue / $orderCount : 0,
                    'cogs' => $cogs,
                    'grossProfit' => $grossProfit,
                    'expensesTotal' => $expensesTotal,
                    'net' => $grossProfit - $expensesTotal,
                ];
            }),
            'topProducts' => Inertia::defer(fn () => DB::table('invoice_items')
                ->join('invoices', 'invoices.id', '=', 'invoice_items.invoice_id')
                ->join('products', 'products.id', '=', 'invoice_items.product_id')
                ->where('invoices.status', 'paid')
                ->whereDate('invoices.invoice_date', '>=', $from)
                ->whereDate('invoices.invoice_date', '<=', $to)
                ->when($branchId, fn ($q) => $q->where('invoices.branch_id', $branchId))
                ->groupBy('products.id', 'products.name', 'products.sku')
                ->selectRaw('products.name as name, products.sku as sku, SUM(invoice_items.quantity) as total_qty, SUM(invoice_items.total) as total_sales')
                ->orderByDesc('total_qty')
                ->limit(5)
                ->get()
                ->map(fn ($row): array => [
                    'name' => $row->name,
                    'sku' => $row->sku,
                    'total_qty' => (float) $row->total_qty,
                    'total_sales' => (float) $row->total_sales,
                ])->all()),
            'salesByDay' => Inertia::defer(fn () => Invoice::query()
                ->where('status', 'paid')
                ->whereDate('invoice_date', '>=', $from)
                ->whereDate('invoice_date', '<=', $to)
                ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
                ->groupBy('invoice_date')
                ->selectRaw('invoice_date as day, SUM(total) as total, COUNT(*) as orders')
                ->orderBy('invoice_date')
                ->get()
                ->map(fn (Invoice $i): array => [
                    'day' => $i->invoice_date?->format('Y-m-d') ?? (string) $i->getAttribute('day'),
                    'total' => (float) $i->getAttribute('total'),
                    'orders' => (int) $i->getAttribute('orders'),
                ])->all()),
        ]);
    }

    public function notifications(Request $request): Response
    {
        return Inertia::render('notifications/index', [
            'items' => Inertia::defer(fn (): array => Notification::query()
                ->where(function ($q) use ($request) {
                    $q->where('user_id', $request->user()->id)->orWhereNull('user_id');
                })
                ->orderByDesc('created_at')
                ->limit(50)
                ->get()
                ->map(fn (Notification $n): array => [
                    'id' => $n->id,
                    'type' => $n->type,
                    'title' => $n->title,
                    'message' => $n->message,
                    'is_read' => (bool) $n->is_read,
                    'created_at' => $n->created_at?->format('Y-m-d H:i'),
                ])->all()),
            'unreadCount' => Notification::where(function ($q) use ($request) {
                $q->where('user_id', $request->user()->id)->orWhereNull('user_id');
            })->where('is_read', false)->count(),
        ]);
    }

    public function markNotificationsRead(Request $request)
    {
        Notification::where(function ($q) use ($request) {
            $q->where('user_id', $request->user()->id)->orWhereNull('user_id');
        })->where('is_read', false)->update(['is_read' => true]);

        return back()->with('success', 'All notifications marked as read.');
    }

    public function settings(): Response
    {
        $company = Company::orderBy('name')->first();

        return Inertia::render('settings/index', [
            'settings' => [
                'company' => [
                    'name' => $company?->name ?? config('app.name'),
                    'legal_name' => $company?->legal_name ?? '',
                    'phone' => $company?->phone ?? '',
                    'email' => $company?->email ?? '',
                    'address' => $company?->address ?? '',
                    'tax_number' => $company?->tax_number ?? '',
                    'currency' => $company?->currency ?? 'USD',
                    'timezone' => $company?->timezone ?? config('app.timezone'),
                ],
                'pos' => [
                    'auto_print_receipt' => config('pos.auto_print_receipt', true),
                    'show_customer_display' => config('pos.show_customer_display', false),
                    'allow_hold_orders' => config('pos.allow_hold_orders', true),
                    'allow_price_override' => config('pos.allow_price_override', false),
                    'default_payment_method' => config('pos.default_payment_method', 'cash'),
                ],
                'invoice' => [
                    'prefix' => config('invoice.prefix', 'INV-'),
                    'show_tax_breakdown' => config('invoice.show_tax_breakdown', true),
                    'footer_text' => config('invoice.footer_text', 'Thank you for your business!'),
                ],
                'tax' => [
                    'default_rate' => config('tax.default_rate', 0),
                    'tax_inclusive' => config('tax.tax_inclusive', false),
                ],
                'payment' => [
                    'allow_split_payments' => config('payment.allow_split_payments', true),
                    'allow_partial_payments' => config('payment.allow_partial_payments', true),
                ],
                'notifications' => [
                    'low_stock_threshold' => config('notifications.low_stock_threshold', 10),
                    'expiry_alert_days' => config('notifications.expiry_alert_days', 30),
                ],
            ],
        ]);
    }
}
