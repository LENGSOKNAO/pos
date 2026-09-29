import { Head, Link, Deferred } from '@inertiajs/react';
import { ArrowDownRight, ArrowUpRight, CreditCard, MapPin, Package, Receipt, ReceiptText, ShoppingCart, Truck, Wallet } from 'lucide-react';
import AppLayout from '@/layouts/app-layout';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { CardsSkeleton, EmptyState, PageHeader, RowsSkeleton } from '@/components/admin';
import { useAuth } from '@/hooks/useAuth';

interface DayTotal {
    day: string;
    total: number;
}

interface RecentSale {
    id: number;
    invoice_no: string;
    customer: string;
    total: number;
    due: number;
    time?: string | null;
}

interface TopProduct {
    name: string;
    qty: number;
    revenue: number;
}

interface LowStockItem {
    product: string;
    unit?: string | null;
    warehouse?: string | null;
    quantity: number;
}

interface PaymentMethod {
    name: string;
    total: number;
}

interface BranchPerf {
    name: string;
    total: number;
    orders: number;
}

interface Stats {
    todaySalesTotal: number;
    todayOrderCount: number;
    yesterdaySalesTotal: number;
    yesterdayOrderCount: number;
    salesDelta: number;
    lowStockCount: number;
    totalProducts: number;
    weekSeries: DayTotal[];
    recentSales: RecentSale[];
    topProducts: TopProduct[];
    lowStockItems: LowStockItem[];
    paymentMethods: PaymentMethod[];
    branchPerformance: BranchPerf[];
}

const money = (n: number) => `$${Number(n).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

const emptyStats: Stats = {
    todaySalesTotal: 0,
    todayOrderCount: 0,
    yesterdaySalesTotal: 0,
    yesterdayOrderCount: 0,
    salesDelta: 0,
    lowStockCount: 0,
    totalProducts: 0,
    weekSeries: [],
    recentSales: [],
    topProducts: [],
    lowStockItems: [],
    paymentMethods: [],
    branchPerformance: [],
};

export default function Dashboard({ stats: rawStats }: { stats?: Stats }) {
    const stats = rawStats ?? emptyStats;
    const { user } = useAuth();
    const hour = new Date().getHours();
    const greeting = hour < 12 ? 'Good morning' : hour < 18 ? 'Good afternoon' : 'Good evening';
    const firstName = user?.employee ? `${user.employee.first_name}` : (user?.username ?? '');
    const branchName = user?.employee?.branch?.name;
    const today = new Date().toLocaleDateString('en-US', { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' });
    const up = stats.salesDelta >= 0;
    const maxDay = Math.max(1, ...stats.weekSeries.map((d) => Number(d.total)));
    const weekTotal = stats.weekSeries.reduce((a, d) => a + Number(d.total), 0);
    const pmTotal = Math.max(1, stats.paymentMethods.reduce((a, p) => a + Number(p.total), 0));

    return (
        <AppLayout title="Dashboard">
            <Head title="Dashboard" />
            <PageHeader
                title={`${greeting}${firstName ? `, ${firstName}` : ''}`}
                description={`${today}${branchName ? ` · ${branchName}` : ''}`}
                actions={
                    <>
                        <Link href="/pos">
                            <Button className="h-10 rounded-xl bg-blue-600 font-semibold text-white hover:bg-blue-700">
                                <ShoppingCart className="size-4" /> Open Terminal
                            </Button>
                        </Link>
                        <Link href="/purchases/create">
                            <Button variant="outline" className="h-10 rounded-xl border-slate-200 bg-white">
                                <Truck className="size-4" /> New Purchase
                            </Button>
                        </Link>
                        <Link href="/cash-sessions">
                            <Button variant="outline" className="h-10 rounded-xl border-slate-200 bg-white">
                                <Wallet className="size-4" /> Cash Drawer
                            </Button>
                        </Link>
                    </>
                }
            />

            <Deferred data="stats" fallback={<><CardsSkeleton /><div className="mt-4"><RowsSkeleton count={5} /></div></>}>
            <div className="grid gap-4 lg:grid-cols-3">
                <Card className="rounded-2xl border-slate-200/90 bg-white lg:col-span-2">
                    <CardHeader className="flex flex-row items-start justify-between pb-1">
                        <div>
                            <p className="text-[11px] font-bold tracking-wider text-slate-500 uppercase">Today&apos;s sales</p>
                            <p className="mt-1 text-4xl font-extrabold tracking-tight text-slate-900 tabular-nums">{money(stats.todaySalesTotal)}</p>
                            <p className={`mt-2 inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-semibold ${up ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700'}`}>
                                {up ? <ArrowUpRight className="size-3" /> : <ArrowDownRight className="size-3" />} {up ? '+' : ''}{stats.salesDelta}% vs yesterday · {stats.todayOrderCount} orders
                            </p>
                        </div>
                        <span className="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-600 tabular-nums">{money(weekTotal)} / 7d</span>
                    </CardHeader>
                    <CardContent>
                        <div className="flex h-44 items-end gap-2 px-1 pt-4">
                            {stats.weekSeries.map((d) => (
                                <div key={d.day} className="group flex h-full flex-1 flex-col items-center justify-end gap-1.5">
                                    <span className="text-[11px] font-bold text-slate-500 tabular-nums opacity-0 transition-opacity group-hover:opacity-100">{Number(d.total) > 0 ? `$${Math.round(Number(d.total))}` : ''}</span>
                                    <div
                                        className="w-full max-w-12 rounded-t-lg bg-blue-600 transition-all group-hover:bg-blue-700"
                                        style={{ height: `${Math.max(4, (Number(d.total) / maxDay) * 112)}px` }}
                                        title={`${d.day}: ${money(Number(d.total))}`}
                                    />
                                    <span className="pb-1 text-[11px] font-semibold text-slate-500">{d.day}</span>
                                </div>
                            ))}
                        </div>
                    </CardContent>
                </Card>
                <Card className="rounded-2xl border-slate-200/90 bg-white">
                    <CardHeader className="pb-1">
                        <CardTitle className="text-[15px] font-bold text-slate-900">Payment methods</CardTitle>
                        <p className="mt-0.5 text-xs text-slate-500">Last 7 days</p>
                    </CardHeader>
                    <CardContent className="space-y-3">
                        {stats.paymentMethods.length === 0 && <EmptyState icon={<CreditCard className="size-5" />} title="No payments" hint="Payments will appear here." />}
                        {stats.paymentMethods.map((p) => (
                            <div key={p.name}>
                                <div className="flex items-center justify-between text-xs">
                                    <span className="font-semibold text-slate-700">{p.name}</span>
                                    <span className="font-extrabold text-slate-900 tabular-nums">{money(Number(p.total))}</span>
                                </div>
                                <div className="mt-1.5 h-2 overflow-hidden rounded-full bg-slate-100">
                                    <div className="h-2 rounded-full bg-blue-600" style={{ width: `${Math.max(2, (Number(p.total) / pmTotal) * 100)}%` }} />
                                </div>
                            </div>
                        ))}
                    </CardContent>
                </Card>
            </div>

            <div className="mt-4 grid gap-4 lg:grid-cols-2">
                <Card className="rounded-2xl border-slate-200/90 bg-white">
                    <CardHeader className="flex flex-row items-center justify-between">
                        <div>
                            <CardTitle className="text-[15px] font-bold text-slate-900">Top products</CardTitle>
                            <p className="mt-0.5 text-xs text-slate-500">Last 7 days</p>
                        </div>
                        <Link href="/reports" className="rounded-lg bg-slate-100 px-2.5 py-1.5 text-xs font-bold text-slate-700 hover:bg-slate-200">Reports</Link>
                    </CardHeader>
                    <CardContent className="space-y-1">
                        {stats.topProducts.length === 0 && <EmptyState icon={<Package className="size-5" />} title="No top sellers" hint="No sales this week yet." />}
                        {stats.topProducts.map((p, i) => (
                            <div key={p.name} className="flex items-center gap-3 rounded-xl px-2 py-2 transition-colors hover:bg-slate-50">
                                <span className="flex size-8 shrink-0 items-center justify-center rounded-lg bg-slate-900 text-xs font-extrabold text-white tabular-nums">{i + 1}</span>
                                <div className="min-w-0 flex-1">
                                    <p className="truncate text-sm font-semibold text-slate-900">{p.name}</p>
                                    <p className="text-xs text-slate-500 tabular-nums">{p.qty} sold · {money(p.revenue)}</p>
                                </div>
                            </div>
                        ))}
                    </CardContent>
                </Card>
                <Card className="rounded-2xl border-slate-200/90 bg-white">
                    <CardHeader className="flex flex-row items-center justify-between">
                        <div>
                            <CardTitle className="text-[15px] font-bold text-slate-900">Recent sales</CardTitle>
                            <p className="mt-0.5 text-xs text-slate-500">Latest completed orders</p>
                        </div>
                        <Link href="/sales" className="rounded-lg bg-slate-100 px-2.5 py-1.5 text-xs font-bold text-slate-700 hover:bg-slate-200">View all</Link>
                    </CardHeader>
                    <CardContent className="space-y-1">
                        {stats.recentSales.length === 0 && <EmptyState icon={<ReceiptText className="size-5" />} title="No sales yet" hint="Completed sales will appear here." />}
                        {stats.recentSales.map((s) => (
                            <Link key={s.id} href={`/sales/${s.id}`} className="flex items-center gap-3 rounded-xl px-3 py-2.5 ring-1 ring-transparent transition-colors hover:bg-slate-50 hover:ring-slate-100">
                                <span className="flex size-10 shrink-0 items-center justify-center rounded-xl bg-slate-900 text-white">
                                    <Receipt className="size-4" />
                                </span>
                                <div className="min-w-0 flex-1">
                                    <p className="truncate font-mono text-sm font-bold text-slate-900">{s.invoice_no}</p>
                                    <p className="truncate text-xs text-slate-500">{s.customer} · {s.time}</p>
                                </div>
                                <div className="text-right">
                                    <p className="text-sm font-extrabold text-slate-900 tabular-nums">{money(s.total)}</p>
                                    {s.due > 0 ? <Badge className="mt-0.5 rounded-full border-transparent bg-amber-100 text-amber-800 hover:bg-amber-100">Due {money(s.due)}</Badge> : <Badge className="mt-0.5 rounded-full border-transparent bg-emerald-100 text-emerald-700 hover:bg-emerald-100">Paid</Badge>}
                                </div>
                            </Link>
                        ))}
                    </CardContent>
                </Card>
            </div>

            <div className="mt-4 grid gap-4 lg:grid-cols-2">
                <Card className="rounded-2xl border-slate-200/90 bg-white">
                    <CardHeader className="flex flex-row items-center justify-between">
                        <div>
                            <CardTitle className="text-[15px] font-bold text-slate-900">Low stock</CardTitle>
                            <p className="mt-0.5 text-xs text-slate-500">{stats.lowStockCount} items need restock</p>
                        </div>
                        <Link href="/inventory/stock" className="rounded-lg bg-slate-100 px-2.5 py-1.5 text-xs font-bold text-slate-700 hover:bg-slate-200">View all</Link>
                    </CardHeader>
                    <CardContent className="space-y-2">
                        {stats.lowStockItems.length === 0 && <p className="py-6 text-center text-sm text-slate-500">Stock levels are healthy.</p>}
                        {stats.lowStockItems.map((item, i) => (
                            <div key={`${item.product}-${i}`} className="flex items-center gap-3 rounded-xl border border-amber-200/60 bg-amber-50/60 px-3 py-2.5">
                                <span className="flex size-10 shrink-0 items-center justify-center rounded-xl bg-white text-amber-600 ring-1 ring-amber-200/70 ring-inset">
                                    <Package className="size-4" />
                                </span>
                                <div className="min-w-0 flex-1">
                                    <p className="truncate text-sm font-semibold text-slate-900">{item.product}</p>
                                    <p className="text-xs text-slate-500">{item.warehouse}{item.unit ? ` · ${item.unit}` : ''}</p>
                                </div>
                                <Badge className={item.quantity <= 0 ? 'rounded-full border-transparent bg-red-600 text-white hover:bg-red-600' : 'rounded-full border-transparent bg-amber-500 text-white hover:bg-amber-500'}>×{item.quantity}</Badge>
                            </div>
                        ))}
                    </CardContent>
                </Card>
                <Card className="rounded-2xl border-slate-200/90 bg-white">
                    <CardHeader className="pb-1">
                        <CardTitle className="text-[15px] font-bold text-slate-900">Branch performance</CardTitle>
                        <p className="mt-0.5 text-xs text-slate-500">Last 7 days</p>
                    </CardHeader>
                    <CardContent className="space-y-1">
                        {stats.branchPerformance.length === 0 && <EmptyState icon={<MapPin className="size-5" />} title="No branch data" hint="Branch sales will appear here." />}
                        {stats.branchPerformance.map((b) => (
                            <div key={b.name} className="flex items-center gap-3 rounded-xl px-2 py-2.5 transition-colors hover:bg-slate-50">
                                <span className="flex size-10 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-slate-600">
                                    <MapPin className="size-4" />
                                </span>
                                <div className="min-w-0 flex-1">
                                    <p className="truncate text-sm font-semibold text-slate-900">{b.name}</p>
                                    <p className="text-xs text-slate-500 tabular-nums">{b.orders} orders</p>
                                </div>
                                <p className="text-sm font-extrabold text-slate-900 tabular-nums">{money(Number(b.total))}</p>
                            </div>
                        ))}
                    </CardContent>
                </Card>
            </div>
            </Deferred>
        </AppLayout>
    );
}
