import { Head, Link } from '@inertiajs/react';
import { ArrowDownRight, ArrowUpRight, Package, PackageSearch, Receipt, ReceiptText, ShoppingBag, ShoppingCart, Truck, Wallet } from 'lucide-react';
import AppLayout from '@/layouts/app-layout';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { EmptyState } from '@/components/admin';

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
}

const money = (n: number) => `$${Number(n).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

export default function Dashboard({ stats }: { stats: Stats }) {
    const up = stats.salesDelta >= 0;
    const maxDay = Math.max(1, ...stats.weekSeries.map((d) => d.total));
    const cards = [
        { label: "Today's sales", value: money(stats.todaySalesTotal), sub: `${up ? '+' : ''}${stats.salesDelta}% vs yesterday`, icon: Receipt, good: up },
        { label: "Today's orders", value: String(stats.todayOrderCount), sub: `${stats.yesterdayOrderCount} yesterday`, icon: ShoppingBag, good: true },
        { label: 'Low stock alerts', value: String(stats.lowStockCount), sub: 'needs restock', icon: PackageSearch, good: stats.lowStockCount === 0 },
        { label: 'Total products', value: String(stats.totalProducts), sub: 'in catalog', icon: Package, good: true },
    ];
    return (
        <AppLayout title="Dashboard">
            <Head title="Dashboard" />
            <div className="mb-5 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h1 className="text-xl font-bold tracking-tight text-slate-900">Back Office</h1>
                    <p className="mt-1 text-sm text-slate-500">Store performance at a glance.</p>
                </div>
                <div className="flex flex-wrap gap-2">
                    <Link href="/pos">
                        <Button className="h-10 rounded-xl bg-blue-600 font-semibold hover:bg-blue-700">
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
                </div>
            </div>

            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                {cards.map((c) => (
                    <Card key={c.label} className="rounded-2xl border-slate-200 shadow-sm">
                        <CardHeader className="flex flex-row items-center justify-between pb-2">
                            <CardTitle className="text-sm font-semibold text-slate-500">{c.label}</CardTitle>
                            <span className="flex size-9 items-center justify-center rounded-xl bg-[#0A1633] text-white">
                                <c.icon className="size-4" />
                            </span>
                        </CardHeader>
                        <CardContent>
                            <div className="text-3xl font-extrabold tracking-tight tabular-nums">{c.value}</div>
                            <p className={`mt-1 flex items-center gap-1 text-xs font-medium ${c.good ? 'text-emerald-600' : 'text-red-600'}`}>
                                {c.good ? <ArrowUpRight className="size-3" /> : <ArrowDownRight className="size-3" />} {c.sub}
                            </p>
                        </CardContent>
                    </Card>
                ))}
            </div>

            <div className="mt-4 grid gap-4 lg:grid-cols-3">
                <Card className="rounded-2xl border-slate-200 shadow-sm lg:col-span-2">
                    <CardHeader className="flex flex-row items-center justify-between">
                        <CardTitle className="text-sm font-semibold">Last 7 days</CardTitle>
                        <span className="text-xs text-slate-500">revenue per day</span>
                    </CardHeader>
                    <CardContent>
                        <div className="flex h-44 items-end gap-2">
                            {stats.weekSeries.map((d) => (
                                <div key={d.day} className="flex flex-1 flex-col items-center gap-1">
                                    <span className="text-[11px] font-semibold text-slate-500 tabular-nums">{d.total > 0 ? `$${Math.round(d.total)}` : ''}</span>
                                    <div
                                        className="w-full rounded-t-md bg-blue-600 transition-all"
                                        style={{ height: `${Math.max(4, (d.total / maxDay) * 140)}px` }}
                                        title={`${d.day}: ${money(d.total)}`}
                                    />
                                    <span className="text-[11px] text-slate-500">{d.day}</span>
                                </div>
                            ))}
                        </div>
                    </CardContent>
                </Card>
                <Card className="rounded-2xl border-slate-200 shadow-sm">
                    <CardHeader>
                        <CardTitle className="text-sm font-semibold">Top sellers · 7 days</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-3">
                        {stats.topProducts.length === 0 && <EmptyState icon={<Package className="size-5" />} title="No top sellers" hint="No sales this week yet." />}
                        {stats.topProducts.map((p, i) => (
                            <div key={p.name} className="flex items-center gap-3">
                                <span className="flex size-7 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-xs font-extrabold text-slate-600 tabular-nums">{i + 1}</span>
                                <div className="min-w-0 flex-1">
                                    <p className="truncate text-sm font-semibold">{p.name}</p>
                                    <p className="text-xs text-slate-500 tabular-nums">
                                        {p.qty} sold · {money(p.revenue)}
                                    </p>
                                </div>
                            </div>
                        ))}
                    </CardContent>
                </Card>
            </div>

            <div className="mt-4 grid gap-4 lg:grid-cols-2">
                <Card className="rounded-2xl border-slate-200 shadow-sm">
                    <CardHeader className="flex flex-row items-center justify-between">
                        <CardTitle className="text-sm font-semibold">Recent sales</CardTitle>
                        <Link href="/sales" className="text-xs font-semibold text-blue-700 hover:underline">
                            View all
                        </Link>
                    </CardHeader>
                    <CardContent className="space-y-1">
                        {stats.recentSales.length === 0 && <EmptyState icon={<ReceiptText className="size-5" />} title="No sales yet" hint="Completed sales will appear here." />}
                        {stats.recentSales.map((s) => (
                            <Link key={s.id} href={`/sales/${s.id}`} className="flex items-center gap-3 rounded-xl px-3 py-2 transition-colors hover:bg-slate-50">
                                <span className="flex size-9 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-700">
                                    <Receipt className="size-4" />
                                </span>
                                <div className="min-w-0 flex-1">
                                    <p className="truncate font-mono text-sm font-semibold">{s.invoice_no}</p>
                                    <p className="text-xs text-slate-500">
                                        {s.customer} · {s.time}
                                    </p>
                                </div>
                                <div className="text-right">
                                    <p className="text-sm font-extrabold tabular-nums">{money(s.total)}</p>
                                    {s.due > 0 ? <Badge className="border-transparent bg-amber-100 text-amber-800 hover:bg-amber-100">Due {money(s.due)}</Badge> : <Badge className="border-transparent bg-emerald-100 text-emerald-700 hover:bg-emerald-100">Paid</Badge>}
                                </div>
                            </Link>
                        ))}
                    </CardContent>
                </Card>
                <Card className="rounded-2xl border-slate-200 shadow-sm">
                    <CardHeader className="flex flex-row items-center justify-between">
                        <CardTitle className="text-sm font-semibold">Needs restock</CardTitle>
                        <Link href="/stocks" className="text-xs font-semibold text-blue-700 hover:underline">
                            View all
                        </Link>
                    </CardHeader>
                    <CardContent className="space-y-2">
                        {stats.lowStockItems.length === 0 && <p className="py-6 text-center text-sm text-slate-500">Stock levels are healthy.</p>}
                        {stats.lowStockItems.map((item, i) => (
                            <div key={`${item.product}-${i}`} className="flex items-center gap-3 rounded-xl bg-amber-50 px-3 py-2">
                                <span className="flex size-9 shrink-0 items-center justify-center rounded-xl bg-amber-100 text-amber-800">
                                    <Package className="size-4" />
                                </span>
                                <div className="min-w-0 flex-1">
                                    <p className="truncate text-sm font-semibold">{item.product}</p>
                                    <p className="text-xs text-slate-500">
                                        {item.warehouse}
                                        {item.unit ? ` · ${item.unit}` : ''}
                                    </p>
                                </div>
                                <Badge className={item.quantity <= 0 ? 'border-transparent bg-red-100 text-red-700 hover:bg-red-100' : 'border-transparent bg-amber-100 text-amber-800 hover:bg-amber-100'}>
                                    ×{item.quantity}
                                </Badge>
                            </div>
                        ))}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
