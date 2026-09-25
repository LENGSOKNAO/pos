import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import { PackageSearch, Plus, RotateCcw } from 'lucide-react';
import AppLayout from '@/layouts/app-layout';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { EmptyState, PageHeader } from '@/components/admin';

interface Summary {
    revenue: number;
    orderCount: number;
    avgTicket: number;
    cogs: number;
    grossProfit: number;
    expensesTotal: number;
    net: number;
}

interface TopProduct {
    name: string;
    sku: string;
    total_qty: number;
    total_sales: number;
}

interface DayRow {
    day: string;
    total: number;
    orders: number;
}

const inputCls = 'h-10 rounded-xl border border-slate-200 bg-white px-2 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100';
const labelCls = 'text-[11px] font-semibold tracking-wider text-slate-500 uppercase';

export default function ReportsIndex({
    filters,
    branches,
    summary,
    topProducts,
    salesByDay,
}: {
    filters: { from: string; to: string; branch_id: number | null };
    branches: { id: number; name: string }[];
    summary: Summary;
    topProducts: TopProduct[];
    salesByDay: DayRow[];
}) {
    const [from, setFrom] = useState(filters.from);
    const [to, setTo] = useState(filters.to);
    const [branchId, setBranchId] = useState<string>(filters.branch_id ? String(filters.branch_id) : '');

    const apply = () => {
        router.get('/reports', { from, to, branch_id: branchId || undefined }, { preserveState: true });
    };

    const money = (n: number) => `$${Number(n).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
    const cards = [
        { label: 'Revenue', value: money(summary.revenue), sub: `${summary.orderCount} orders` },
        { label: 'Avg ticket', value: money(summary.avgTicket), sub: 'per order' },
        { label: 'COGS (est.)', value: money(summary.cogs), sub: 'qty × purchase price' },
        { label: 'Gross profit', value: money(summary.grossProfit), sub: 'revenue − COGS' },
        { label: 'Expenses', value: money(summary.expensesTotal), sub: 'in range' },
        { label: 'Net', value: money(summary.net), sub: 'gross − expenses' },
    ];
    const maxDay = Math.max(1, ...salesByDay.map((d) => Number(d.total)));

    return (
        <AppLayout title="Reports">
            <Head title="Reports" />
            <PageHeader title="Reports" description="Revenue, profit and top products for the selected range." />

            <Card className="rounded-2xl border-slate-200 shadow-sm">
                <CardContent className="flex flex-wrap items-end gap-3 p-5">
                    <label className={labelCls}>
                        From
                        <input type="date" value={from} onChange={(e) => setFrom(e.target.value)} className={`mt-1 ml-0 block ${inputCls}`} />
                    </label>
                    <label className={labelCls}>
                        To
                        <input type="date" value={to} onChange={(e) => setTo(e.target.value)} className={`mt-1 block ${inputCls}`} />
                    </label>
                    {branches.length > 0 && (
                        <label className={labelCls}>
                            Branch
                            <select value={branchId} onChange={(e) => setBranchId(e.target.value)} className={`mt-1 block ${inputCls}`}>
                                <option value="">All</option>
                                {branches.map((b) => (
                                    <option key={b.id} value={b.id}>{b.name}</option>
                                ))}
                            </select>
                        </label>
                    )}
                    <button onClick={apply} className="h-10 rounded-xl bg-blue-600 px-5 text-sm font-semibold text-white hover:bg-blue-700">Apply</button>
                </CardContent>
            </Card>

            <div className="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
                {cards.map((c) => (
                    <Card key={c.label} className="rounded-2xl border-slate-200 shadow-sm">
                        <CardHeader className="pb-2"><CardTitle className="text-sm font-semibold text-slate-500">{c.label}</CardTitle></CardHeader>
                        <CardContent>
                            <div className="text-2xl font-extrabold tracking-tight tabular-nums">{c.value}</div>
                            <p className="mt-1 text-xs text-slate-500">{c.sub}</p>
                        </CardContent>
                    </Card>
                ))}
            </div>

            <div className="mt-4 grid gap-4 lg:grid-cols-2">
                <Card className="rounded-2xl border-slate-200 shadow-sm">
                    <CardHeader><CardTitle className="text-sm font-semibold">Sales by day</CardTitle></CardHeader>
                    <CardContent className="space-y-2">
                        {salesByDay.length === 0 && <EmptyState icon={<PackageSearch className="size-5" />} title="No sales in range" hint="Pick a wider date range." />}
                        {salesByDay.map((d) => (
                            <div key={d.day} className="flex items-center gap-2 text-xs">
                                <span className="w-24 shrink-0 font-medium text-slate-500">{d.day}</span>
                                <div className="h-5 flex-1 overflow-hidden rounded bg-slate-100">
                                    <div className="h-5 rounded bg-blue-600" style={{ width: `${(Number(d.total) / maxDay) * 100}%` }} />
                                </div>
                                <span className="w-20 shrink-0 text-right font-bold tabular-nums">${Number(d.total).toLocaleString()}</span>
                            </div>
                        ))}
                    </CardContent>
                </Card>
                <Card className="rounded-2xl border-slate-200 shadow-sm">
                    <CardHeader><CardTitle className="text-sm font-semibold">Top 5 products</CardTitle></CardHeader>
                    <CardContent>
                        <table className="w-full text-sm">
                            <thead><tr className="text-left text-[11px] font-semibold tracking-wider text-slate-500 uppercase"><th className="h-11">Product</th><th className="h-11 text-right">Qty</th><th className="h-11 text-right">Sales</th></tr></thead>
                            <tbody>
                                {topProducts.map((p) => (
                                    <tr key={p.sku} className="border-t border-slate-200">
                                        <td className="h-11 font-semibold">{p.name} <span className="font-mono text-xs font-normal text-slate-500">({p.sku})</span></td>
                                        <td className="h-11 text-right tabular-nums">{Number(p.total_qty).toLocaleString()}</td>
                                        <td className="h-11 text-right font-bold tabular-nums">${Number(p.total_sales).toLocaleString()}</td>
                                    </tr>
                                ))}
                                {topProducts.length === 0 && <tr><td colSpan={3} className="p-0"><EmptyState icon={<RotateCcw className="size-5" />} title="No data" hint="No product sales in range." /></td></tr>}
                            </tbody>
                        </table>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
