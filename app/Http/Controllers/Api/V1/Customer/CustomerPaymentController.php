<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Models\CustomerPayment;
use App\Models\Invoice;
use Illuminate\Http\Request;

class CustomerPaymentController extends BaseApiController
{
    public function index(Request $request)
    {
        $query = CustomerPayment::with('customer', 'invoice', 'paymentMethod', 'receiver');

        if ($request->has('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        if ($request->has('invoice_id')) {
            $query->where('invoice_id', $request->invoice_id);
        }

        if ($request->has('payment_method_id')) {
            $query->where('payment_method_id', $request->payment_method_id);
        }

        if ($request->has('date_from')) {
            $query->where('payment_date', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->where('payment_date', '<=', $request->date_to);
        }

        $payments = $query->latest()->paginate($request->get('per_page', 15));

        return $this->paginated($payments);
    }

    public function store(Request $request)
    {
        $data = $this->validateRequest($request, [
            'customer_id' => 'required|exists:customers,id',
            'invoice_id' => 'nullable|exists:invoices,id',
            'amount' => 'required|numeric|min:0.01',
            'payment_method_id' => 'required|exists:payment_methods,id',
            'reference_number' => 'nullable|string',
        ]);

        $payment = CustomerPayment::create([
            'customer_id' => $data['customer_id'],
            'invoice_id' => $data['invoice_id'],
            'amount' => $data['amount'],
            'payment_method_id' => $data['payment_method_id'],
            'payment_date' => now(),
            'reference_number' => $data['reference_number'],
            'received_by' => auth()->id(),
        ]);

        // Update invoice if provided
        if ($data['invoice_id']) {
            $invoice = Invoice::find($data['invoice_id']);
            $totalPaid = $invoice->payments->sum('amount') + $data['amount'];
            $invoice->update([
                'paid_amount' => $totalPaid,
                'due_amount' => $invoice->total - $totalPaid,
                'status' => $totalPaid >= $invoice->total ? 'paid' : ($totalPaid > 0 ? 'partial_paid' : 'pending'),
            ]);
        }

        return $this->success($payment->load('customer', 'invoice', 'paymentMethod'), 'Payment recorded successfully', 201);
    }

    public function show(CustomerPayment $customerPayment)
    {
        return $this->success($customerPayment->load('customer', 'invoice', 'paymentMethod', 'receiver'));
    }
}
