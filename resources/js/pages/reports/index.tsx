import { Head, router, Deferred } from '@inertiajs/react';
import { useState } from 'react';
import { Banknote, CalendarDays, ChartColumn, CircleDollarSign, PackageSearch, Receipt, RotateCcw, TrendingDown, TrendingUp, Wallet } from 'lucide-react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { CardsSkeleton, EmptyState, PageHeader, RowsSkeleton } from '@/components/admin';

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

const inputCls = 'h-10 rounded-xl border border-slate-200 bg-white px-2.5 text-sm shadow-[0_1px_2px_rgba(15,23,42,0.04)] outline-none focus:border-slate-900 focus:ring-2 focus:ring-slate-900/10';
const labelCls = 'text-[11px] font-bold tracking-wider text-slate-500 uppercase';

export default function ReportsIndex({
    filters,
    branches,
    summary,
    topProducts,
    salesByDay,
}: {
    filters?: { from: string; to: string; branch_id: number | null };
    branches?: { id: number; name: string }[];
    summary?: Summary;
    topProducts?: TopProduct[];
    salesByDay?: DayRow[];
}) {
    const safeFilters = filters ?? { from: '', to: '', branch_id: null };
    const safeBranches = Array.isArray(branches) ? branches : [];
    const safeSummary: Summary = summary ?? { revenue: 0, orderCount: 0, avgTicket: 0, cogs: 0, grossProfit: 0, expensesTotal: 0, net: 0 };
    const safeTopProducts = Array.isArray(topProducts) ? topProducts : [];
    const safeSalesByDay = Array.isArray(salesByDay) ? salesByDay : [];
    const [from, setFrom] = useState(safeFilters.from);
    const [to, setTo] = useState(safeFilters.to);
    const [branchId, setBranchId] = useState<string>(safeFilters.branch_id ? String(safeFilters.branch_id) : '');

    const apply = () => {
        router.get('/reports', { from, to, branch_id: branchId || undefined }, { preserveState: true });
    };

    const money = (n: number) => `$${Number(n).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
    const cards = [
        { label: 'Revenue', value: money(safeSummary.revenue), sub: `${safeSummary.orderCount} orders`, icon: Banknote, accent: 'bg-emerald-50 text-emerald-600 ring-emerald-600/10' },
        { label: 'Avg ticket', value: money(safeSummary.avgTicket), sub: 'per order', icon: Receipt, accent: 'bg-blue-50 text-blue-600 ring-blue-600/10' },
        { label: 'COGS (est.)', value: money(safeSummary.cogs), sub: 'qty × purchase price', icon: TrendingDown, accent: 'bg-amber-50 text-amber-600 ring-amber-600/10' },
        { label: 'Gross profit', value: money(safeSummary.grossProfit), sub: 'revenue − COGS', icon: TrendingUp, accent: 'bg-violet-50 text-violet-600 ring-violet-600/10' },
        { label: 'Expenses', value: money(safeSummary.expensesTotal), sub: 'in range', icon: Wallet, accent: 'bg-red-50 text-red-600 ring-red-600/10' },
        { label: 'Net', value: money(safeSummary.net), sub: 'gross − expenses', icon: CircleDollarSign, accent: 'bg-slate-900 text-white ring-slate-900' },
    ];
    const maxDay = Math.max(1, ...safeSalesByDay.map((d) => Number(d.total)));

    return (
        <AppLayout title="Reports">
            <Head title="Reports" />
            <PageHeader
                title="Reports"
                description="Revenue, profit and top products for the selected range."
                actions={
                    <Button onClick={apply} className="h-10 rounded-xl bg-slate-900 font-semibold text-white hover:bg-slate-800">
                        <CalendarDays className="size-4" /> Apply filters
                    </Button>
                }
            />

            <Card className="rounded-2xl border-slate-200/90 bg-white shadow-[0_1px_2px_rgba(15,23,42,0.05)]">
                <CardContent className="flex flex-wrap items-end gap-3 p-5">
                    <label className={labelCls}>
                        From
                        <input type="date" value={from} onChange={(e) => setFrom(e.target.value)} className={`mt-1.5 ml-0 block ${inputCls}`} />
                    </label>
                    <label className={labelCls}>
                        To
                        <input type="date" value={to} onChange={(e) => setTo(e.target.value)} className={`mt-1.5 block ${inputCls}`} />
                    </label>
                    {safeBranches.length > 0 && (
                        <label className={labelCls}>
                            Branch
                            <select value={branchId} onChange={(e) => setBranchId(e.target.value)} className={`mt-1.5 block ${inputCls}`}>
                                <option value="">All</option>
                                {safeBranches.map((b) => (
                                    <option key={b.id} value={b.id}>{b.name}</option>
                                ))}
                            </select>
                        </label>
                    )}
                    <button onClick={apply} className="h-10 rounded-xl bg-slate-900 px-5 text-sm font-semibold text-white hover:bg-slate-800">Apply</button>
                </CardContent>
            </Card>

            <Deferred data={['summary', 'topProducts', 'salesByDay']} fallback={<><CardsSkeleton count={6} /><div className="mt-4"><RowsSkeleton count={5} /></div></>}>
            <div className="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
                {cards.map((c) => (
                    <Card key={c.label} className="rounded-2xl border-slate-200/90 bg-white shadow-[0_1px_2px_rgba(15,23,42,0.05)]">
                        <CardHeader className="flex flex-row items-center justify-between pb-1">
                            <CardTitle className="text-[13px] font-semibold text-slate-500">{c.label}</CardTitle>
                            <span className={`flex size-10 items-center justify-center rounded-xl ring-1 ring-inset ${c.accent}`}>
                                <c.icon className="size-[18px]" />
                            </span>
                        </CardHeader>
                        <CardContent>
                            <div className="text-[26px] leading-8 font-extrabold tracking-tight text-slate-900 tabular-nums">{c.value}</div>
                            <p className="mt-1 text-xs font-medium text-slate-500">{c.sub}</p>
                        </CardContent>
                    </Card>
                ))}
            </div>

            <div className="mt-4 grid gap-4 lg:grid-cols-2">
                <Card className="rounded-2xl border-slate-200/90 bg-white shadow-[0_1px_2px_rgba(15,23,42,0.05)]">
                    <CardHeader className="flex flex-row items-center justify-between">
                        <div>
                            <CardTitle className="text-[15px] font-bold text-slate-900">Sales by day</CardTitle>
                            <p className="mt-0.5 text-xs text-slate-500">Daily revenue in range</p>
                        </div>
                        <span className="flex size-10 items-center justify-center rounded-xl bg-slate-100 text-slate-600"><ChartColumn className="size-[18px]" /></span>
                    </CardHeader>
                    <CardContent className="space-y-2.5">
                        {safeSalesByDay.length === 0 && <EmptyState icon={<PackageSearch className="size-5" />} title="No sales in range" hint="Pick a wider date range." />}
                        {safeSalesByDay.map((d) => (
                            <div key={d.day} className="flex items-center gap-3 text-xs">
                                <span className="w-24 shrink-0 font-semibold text-slate-500">{d.day}</span>
                                <div className="h-6 flex-1 overflow-hidden rounded-lg bg-slate-100 ring-1 ring-slate-200/50 ring-inset">
                                    <div className="h-6 rounded-lg bg-slate-900" style={{ width: `${Math.max(2, (Number(d.total) / maxDay) * 100)}%` }} />
                                </div>
                                <span className="w-24 shrink-0 text-right font-extrabold text-slate-900 tabular-nums">${Number(d.total).toLocaleString()} <span className="font-medium text-slate-400">· {d.orders}</span></span>
                            </div>
                        ))}
                    </CardContent>
                </Card>
                <Card className="rounded-2xl border-slate-200/90 bg-white shadow-[0_1px_2px_rgba(15,23,42,0.05)]">
                    <CardHeader>
                        <CardTitle className="text-[15px] font-bold text-slate-900">Top 5 products</CardTitle>
                        <p className="mt-0.5 text-xs text-slate-500">Best sellers in range</p>
                    </CardHeader>
                    <CardContent className="px-2 pb-2">
                        <table className="w-full text-sm">
                            <thead><tr className="text-left text-[11px] font-bold tracking-wider text-slate-500 uppercase"><th className="h-10 px-4">Product</th><th className="h-10 px-4 text-right">Qty</th><th className="h-10 px-4 text-right">Sales</th></tr></thead>
                            <tbody>
                                {safeTopProducts.map((p, i) => (
                                    <tr key={p.sku} className="border-t border-slate-100 transition-colors first:border-0 hover:bg-slate-50">
                                        <td className="h-12 px-4">
                                            <div className="flex items-center gap-2.5">
                                                <span className="flex size-7 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-xs font-extrabold text-slate-600 tabular-nums">{i + 1}</span>
                                                <div className="min-w-0">
                                                    <p className="truncate font-semibold text-slate-900">{p.name}</p>
                                                    <p className="font-mono text-xs text-slate-400">{p.sku}</p>
                                                </div>
                                            </div>
                                        </td>
                                        <td className="h-12 px-4 text-right tabular-nums">{Number(p.total_qty).toLocaleString()}</td>
                                        <td className="h-12 px-4 text-right font-extrabold text-slate-900 tabular-nums">${Number(p.total_sales).toLocaleString()}</td>
                                    </tr>
                                ))}
                                {safeTopProducts.length === 0 && <tr><td colSpan={3} className="p-0"><EmptyState icon={<RotateCcw className="size-5" />} title="No data" hint="No product sales in range." /></td></tr>}
                            </tbody>
                        </table>
                    </CardContent>
                </Card>
            </div>
            </Deferred>
        </AppLayout>
    );
}
