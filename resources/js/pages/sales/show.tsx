import { Head, Link } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';

interface Props {
    sale: {
        id: number; invoice_no: string; status: string; subtotal: number; tax_amount: number;
        discount_amount: number; grand_total: number; paid_amount: number;
        customer?: { name: string; phone?: string | null } | null; created_at?: string;
        items: { id: number; quantity: number; unit_price: number; line_total: number }[];
        payments: { id: number; method?: string | null; amount: number }[];
    };
}

export default function SaleShow({ sale }: Props) {
    const balance = Number(sale.grand_total) - Number(sale.paid_amount);
    return (
        <AppLayout title={`Receipt ${sale.invoice_no}`}>
            <Head title={`Receipt ${sale.invoice_no}`} />
            <style>{`@media print { body * { visibility: hidden; } #sale-receipt, #sale-receipt * { visibility: visible; } #sale-receipt { position: absolute; left: 0; top: 0; width: 100%; } }`}</style>
            <div className="mb-5 flex items-center justify-between">
                <Link href="/sales" className="text-sm font-semibold text-slate-500 hover:text-slate-900">← Back to sales</Link>
                <Button onClick={() => window.print()} className="h-10 rounded-xl bg-blue-600 font-semibold hover:bg-blue-700">Print receipt</Button>
            </div>
            <Card id="sale-receipt" className="mx-auto max-w-md rounded-2xl border-slate-200 p-6 font-mono text-sm shadow-sm">
                <div className="text-center">
                    <p className="font-sans text-lg font-black tracking-tight">SquarePOS</p>
                    <p className="mt-1 text-xs text-slate-500">{sale.invoice_no} · {sale.created_at}</p>
                    <p className="text-xs text-slate-500">Cashier copy — keep for records</p>
                    {sale.customer && <p className="text-xs text-slate-500">Customer: {sale.customer.name}</p>}
                    <div className="mt-2 flex justify-center">
                        {balance <= 0.009
                            ? <Badge className="border-transparent bg-emerald-100 font-sans text-emerald-700 hover:bg-emerald-100">{sale.status} · Paid</Badge>
                            : <Badge className="border-transparent bg-red-100 font-sans text-red-700 hover:bg-red-100">Due ${balance.toFixed(2)}</Badge>}
                    </div>
                </div>
                <div className="my-4 border-t border-dashed border-slate-300" />
                {sale.items.map((i) => (
                    <div key={i.id} className="flex justify-between py-1 tabular-nums">
                        <span>×{i.quantity} @ ${Number(i.unit_price).toFixed(2)}</span>
                        <span className="font-bold">${Number(i.line_total).toFixed(2)}</span>
                    </div>
                ))}
                <div className="my-4 border-t border-dashed border-slate-300" />
                <div className="space-y-1 tabular-nums">
                    <div className="flex justify-between"><span>Subtotal</span><span>${Number(sale.subtotal).toFixed(2)}</span></div>
                    <div className="flex justify-between"><span>Discount</span><span>-${Number(sale.discount_amount).toFixed(2)}</span></div>
                    <div className="flex justify-between"><span>Tax</span><span>${Number(sale.tax_amount).toFixed(2)}</span></div>
                    <div className="flex justify-between text-base font-black"><span>TOTAL</span><span>${Number(sale.grand_total).toFixed(2)}</span></div>
                    <div className="flex justify-between"><span>Paid</span><span>${Number(sale.paid_amount).toFixed(2)}</span></div>
                    <div className="flex justify-between"><span>Balance</span><span>${balance.toFixed(2)}</span></div>
                </div>
                <div className="my-4 border-t border-dashed border-slate-300" />
                <p className="text-xs text-slate-500">Payments: {sale.payments.map((p) => `${p.method ?? '—'} $${Number(p.amount).toFixed(2)}`).join(', ') || '—'}</p>
                <p className="mt-4 text-center font-sans text-xs text-slate-400">*** Thank you for shopping! ***</p>
            </Card>
        </AppLayout>
    );
}
