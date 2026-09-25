import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import { ReceiptText } from 'lucide-react';
import AppLayout from '@/layouts/app-layout';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { EmptyState, PageHeader, TableHeadRow, TableShell, tdCls, thCls } from '@/components/admin';

interface Sale {
    id: number;
    invoice_no: string;
    customer?: string | null;
    status: string;
    grand_total: number;
    paid_amount: number;
    balance: number;
    created_at?: string;
}

export default function SalesIndex({ sales, filters }: { sales: { data: Sale[]; current_page: number; last_page: number }; filters: { status?: string; from?: string; to?: string } }) {
    const [status, setStatus] = useState(filters.status ?? '');
    const [from, setFrom] = useState(filters.from ?? '');
    const [to, setTo] = useState(filters.to ?? '');

    function apply() {
        router.get('/sales', { status: status || undefined, from: from || undefined, to: to || undefined }, { preserveState: true });
    }

    return (
        <AppLayout title="Sales History">
            <Head title="Sales History" />
            <PageHeader title="Sales History" count={sales.data.length} description="Completed and pending sales with payment status." />
            <Card className="mb-4 flex flex-wrap items-end gap-2 rounded-2xl border-slate-200 p-4 shadow-sm">
                <div>
                    <label className="mb-1 block text-[11px] font-semibold tracking-wider text-slate-500 uppercase">Status</label>
                    <select value={status} onChange={(e) => setStatus(e.target.value)} className="h-10 rounded-xl border border-slate-200 bg-white px-2 text-sm">
                        <option value="">All statuses</option>
                        <option value="completed">Completed</option>
                        <option value="pending">Pending</option>
                        <option value="refunded">Refunded</option>
                    </select>
                </div>
                <div>
                    <label className="mb-1 block text-[11px] font-semibold tracking-wider text-slate-500 uppercase">From</label>
                    <Input type="date" value={from} onChange={(e) => setFrom(e.target.value)} className="h-10 w-40 rounded-xl" />
                </div>
                <div>
                    <label className="mb-1 block text-[11px] font-semibold tracking-wider text-slate-500 uppercase">To</label>
                    <Input type="date" value={to} onChange={(e) => setTo(e.target.value)} className="h-10 w-40 rounded-xl" />
                </div>
                <Button onClick={apply} className="h-10 rounded-xl bg-blue-600 font-semibold hover:bg-blue-700">Filter</Button>
            </Card>
            <TableShell>
                <TableHeadRow>
                    <th className={thCls}>Invoice</th>
                    <th className={thCls}>Customer</th>
                    <th className={`${thCls} text-right`}>Total</th>
                    <th className={`${thCls} text-right`}>Paid</th>
                    <th className={thCls}>Status</th>
                    <th className={thCls}>Date</th>
                </TableHeadRow>
                <tbody>
                    {sales.data.map((s) => (
                        <tr key={s.id} className="border-t border-slate-200 hover:bg-slate-50">
                            <td className={tdCls}><Link href={`/sales/${s.id}`} className="font-mono font-semibold text-blue-700 hover:underline">{s.invoice_no}</Link></td>
                            <td className={`${tdCls} text-slate-500`}>{s.customer ?? '—'}</td>
                            <td className={`${tdCls} text-right font-bold tabular-nums`}>${Number(s.grand_total).toFixed(2)}</td>
                            <td className={`${tdCls} text-right tabular-nums`}>${Number(s.paid_amount).toFixed(2)}</td>
                            <td className={tdCls}>
                                {s.balance <= 0.009
                                    ? <Badge className="border-transparent bg-emerald-100 text-emerald-700 hover:bg-emerald-100">Paid</Badge>
                                    : <Badge className="border-transparent bg-red-100 text-red-700 hover:bg-red-100 tabular-nums">Due ${s.balance.toFixed(2)}</Badge>}
                            </td>
                            <td className={`${tdCls} text-slate-500`}>{s.created_at}</td>
                        </tr>
                    ))}
                    {sales.data.length === 0 && (
                        <tr><td colSpan={6} className="p-0"><EmptyState icon={<ReceiptText className="size-5" />} title="No sales found" hint="Adjust the filters or date range." /></td></tr>
                    )}
                </tbody>
            </TableShell>
            {sales.last_page > 1 && (
                <p className="mt-3 text-center text-xs text-slate-500 tabular-nums">Page {sales.current_page} of {sales.last_page}</p>
            )}
        </AppLayout>
    );
}
