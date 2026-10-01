import { Head, Link, Deferred } from '@inertiajs/react';
import { CheckCircle2, CreditCard, MapPin, Package, ReceiptText, ShoppingCart, TrendingUp, Truck, Wallet } from 'lucide-react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { EmptyState, PageHeader, RowsSkeleton } from '@/components/admin';
import { AreaChart, MeterRow, StatTile } from '@/components/ui/chart';
import { useAuth } from '@/hooks/useAuth';

interface DayTotal {
  day: string;
  total: number;
  orders?: number;
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
  const today = new Date().toLocaleDateString('en-US', { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' });  const up = stats.salesDelta >= 0;
  const weekTotal = stats.weekSeries.reduce((a, d) => a + Number(d.total), 0);
  const weekOrders = stats.weekSeries.reduce((a, d) => a + Number(d.orders ?? 0), 0);
  const pmTotal = Math.max(1, stats.paymentMethods.reduce((a, p) => a + Number(p.total), 0));
  const branchTotal = Math.max(1, stats.branchPerformance.reduce((a, b) => a + Number(b.total), 0));
  const trend = stats.weekSeries.map((d) => ({ label: d.day, value: Number(d.total) }));

  const pmTones = ['brand', 'success', 'warning', 'info'] as const;

  return (
    <AppLayout title="Dashboard">
      <Head title="Dashboard" />
      <PageHeader
        title={`${greeting}${firstName ? `, ${firstName}` : ''}`}
        description={`${today}${branchName ? ` · ${branchName}` : ''}`}
        actions={
          <>
            <Link href="/pos">
              <Button className="h-9 rounded-lg">
                <ShoppingCart className="size-4" /> Open Terminal
              </Button>
            </Link>
            <Link href="/purchases/create">
              <Button variant="outline" className="h-9 rounded-lg">
                <Truck className="size-4" /> New Purchase
              </Button>
            </Link>
          </>
        }
      />

      <Deferred
        data="stats"
        fallback={
          <div className="space-y-4">
            <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
              {[0, 1, 2, 3].map((i) => (
                <div key={i} className="h-[86px] animate-pulse rounded-xl border border-slate-200 bg-slate-50" />
              ))}
            </div>
            <RowsSkeleton count={6} />
          </div>
        }
      >
        <div className="space-y-4">
          {/* KPI strip */}
          <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <StatTile
              label="Sales today"
              value={money(stats.todaySalesTotal)}
              tone="brand"
              icon={<Wallet className="size-[18px]" />}
              hint={
                <span className={up ? 'text-success-600' : 'text-error-600'}>
                    {up ? '▲' : '▼'} {Math.abs(stats.salesDelta)}% vs yesterday
                </span>
              }
            />
            <StatTile
              label="Orders today"
              value={stats.todayOrderCount}
                tone="info"
              icon={<ReceiptText className="size-[18px]" />}
              hint={`${money(stats.yesterdaySalesTotal)} yesterday`}
            />
            <StatTile
              label="Revenue · 7 days"
              value={money(weekTotal)}
              tone="success"
              icon={<TrendingUp className="size-[18px]" />}
              hint={`${weekOrders} orders this week`}
            />
            <StatTile
              label="Needs restock"
              value={stats.lowStockCount}
              tone={stats.lowStockCount > 0 ? 'warning' : 'neutral'}
              icon={<Package className="size-[18px]" />}
              hint={`${stats.totalProducts} products tracked`}
            />
          </div>

          {/* Trend + payment mix */}
          <div className="grid gap-4 lg:grid-cols-3">
            <section className="rounded-xl border border-slate-200 bg-white lg:col-span-2">
              <header className="flex flex-wrap items-end justify-between gap-3 border-b border-slate-100 px-5 py-4">
                <div>
                  <h2 className="text-sm font-bold text-slate-900">Sales trend</h2>
                  <p className="mt-0.5 text-xs text-slate-500">Daily revenue, last 7 days</p>
                </div>
                <div className="text-right">
                  <p className="font-display text-2xl font-extrabold tracking-tight text-slate-900 tabular-nums">{money(weekTotal)}</p>
                  <p className="text-[11px] font-medium text-slate-400 tabular-nums">{weekOrders} orders</p>
                </div>
              </header>
              <div className="px-5 pt-5 pb-4">
                {trend.length > 0 ? (
                    <AreaChart data={trend} height={200} formatValue={money} />
                ) : (
                    <EmptyState icon={<TrendingUp className="size-5" />} title="No sales this week" hint="Completed sales will chart here." />
                )}
              </div>
            </section>

            <section className="flex flex-col rounded-xl border border-slate-200 bg-white">
              <header className="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                <div>
                  <h2 className="text-sm font-bold text-slate-900">Payment mix</h2>
                  <p className="mt-0.5 text-xs text-slate-500">Last 7 days</p>
                </div>
                <CreditCard className="size-4 text-slate-300" />
              </header>
              {stats.paymentMethods.length === 0 ? (
                    <EmptyState icon={<CreditCard className="size-5" />} title="No payments" hint="Payments will appear here." />
                ) : (
                    <div className="divide-y divide-slate-100 py-1">
                        {stats.paymentMethods.map((p, i) => (
                            <MeterRow
                                key={p.name}
                                label={p.name}
                                value={Number(p.total)}
                                total={pmTotal}
                                amount={money(Number(p.total))}
                                tone={pmTones[i % pmTones.length]}
                            />
                        ))}
                    </div>
                )}
            </section>
          </div>

          {/* Top sellers + branch performance */}
          <div className="grid gap-4 lg:grid-cols-3">
            <section className="rounded-xl border border-slate-200 bg-white">
              <header className="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                <div>
                    <h2 className="text-sm font-bold text-slate-900">Top sellers</h2>
                    <p className="mt-0.5 text-xs text-slate-500">Last 7 days</p>
                </div>
                <Link href="/reports" className="text-xs font-semibold text-brand-600 hover:text-brand-700">
                    Reports
                </Link>
              </header>
              {stats.topProducts.length === 0 ? (
                  <EmptyState icon={<Package className="size-5" />} title="No top sellers" hint="No sales this week yet." />
              ) : (
                  <ol className="divide-y divide-slate-100">
                      {stats.topProducts.slice(0, 6).map((p, i) => (
                          <li key={p.name} className="flex items-center gap-3 px-5 py-3 transition-colors hover:bg-slate-50">
                              <span
                                  className={
                                      'flex size-6 shrink-0 items-center justify-center rounded-md text-[11px] font-bold tabular-nums ' +
                                      (i === 0 ? 'bg-brand-600 text-white' : i === 1 ? 'bg-brand-100 text-brand-700' : 'bg-slate-100 text-slate-500')
                                  }
                              >
                                  {i + 1}
                              </span>
                              <div className="min-w-0 flex-1">
                                  <p className="truncate text-[13px] font-semibold text-slate-800">{p.name}</p>
                                  <p className="text-[11px] text-slate-500 tabular-nums">{p.qty} sold</p>
                              </div>
                              <span className="shrink-0 text-[13px] font-bold text-slate-900 tabular-nums">{money(p.revenue)}</span>
                          </li>
                      ))}
                  </ol>
              )}
            </section>

            <section className="rounded-xl border border-slate-200 bg-white lg:col-span-2">
              <header className="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                <div>
                    <h2 className="text-sm font-bold text-slate-900">Recent sales</h2>
                    <p className="mt-0.5 text-xs text-slate-500">Latest completed orders</p>
                </div>
                <Link href="/sales" className="text-xs font-semibold text-brand-600 hover:text-brand-700">
                    View all
                </Link>
              </header>
              {stats.recentSales.length === 0 ? (
                  <EmptyState icon={<ReceiptText className="size-5" />} title="No sales yet" hint="Completed sales will appear here." />
              ) : (
                  <div className="overflow-x-auto">
                      <table className="w-full min-w-[520px] text-left">
                          <thead>
                              <tr className="border-b border-slate-100 text-[11px] font-semibold tracking-wide text-slate-400 uppercase">
                                  <th className="px-5 py-2.5 font-semibold">Invoice</th>
                                  <th className="px-3 py-2.5 font-semibold">Customer</th>
                                  <th className="px-3 py-2.5 text-right font-semibold">Amount</th>
                                  <th className="px-5 py-2.5 text-right font-semibold">Status</th>
                              </tr>
                          </thead>
                          <tbody className="divide-y divide-slate-100">
                              {stats.recentSales.map((s) => (
                                  <tr key={s.id} className="transition-colors hover:bg-slate-50">
                                      <td className="px-5 py-3">
                                          <Link href={`/sales/${s.id}`} className="font-mono text-[13px] font-semibold text-brand-600 hover:underline">
                                              {s.invoice_no}
                                          </Link>
                                          {s.time && <p className="text-[11px] text-slate-400">{s.time}</p>}
                                      </td>
                                      <td className="max-w-[180px] truncate px-3 py-3 text-[13px] text-slate-600">{s.customer}</td>
                                      <td className="px-3 py-3 text-right text-[13px] font-bold text-slate-900 tabular-nums">{money(s.total)}</td>
                                      <td className="px-5 py-3 text-right">
                                          {s.due > 0 ? (
                                              <span className="inline-flex rounded-full bg-warning-50 px-2 py-0.5 text-[11px] font-bold text-warning-700">
                                                  Due {money(s.due)}
                                              </span>
                                          ) : (
                                              <span className="inline-flex rounded-full bg-success-50 px-2 py-0.5 text-[11px] font-bold text-success-700">Paid</span>
                                          )}
                                      </td>
                                  </tr>
                              ))}
                          </tbody>
                      </table>
                  </div>
              )}
            </section>
          </div>

          {/* Restock + branches */}
          <div className="grid gap-4 lg:grid-cols-3">
            <section className="rounded-xl border border-slate-200 bg-white">
              <header className="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                <div>
                    <h2 className="text-sm font-bold text-slate-900">Needs restock</h2>
                    <p className="mt-0.5 text-xs text-slate-500">{stats.lowStockCount} items at or below reorder level</p>
                </div>
                <Link href="/inventory/stock" className="text-xs font-semibold text-brand-600 hover:text-brand-700">
                    Stock
                </Link>
              </header>
              {stats.lowStockItems.length === 0 ? (
                  <div className="flex flex-col items-center justify-center gap-2 px-5 py-12 text-center">
                      <span className="flex size-11 items-center justify-center rounded-xl bg-success-50 text-success-600">
                          <CheckCircle2 className="size-5" />
                      </span>
                      <p className="text-[13px] font-semibold text-slate-700">Stock levels are healthy</p>
                      <p className="text-xs text-slate-500">Nothing is below its reorder point.</p>
                  </div>
              ) : (
                  <ul className="divide-y divide-slate-100">
                      {stats.lowStockItems.map((item, i) => (
                          <li key={`${item.product}-${i}`} className="flex items-center gap-3 px-5 py-3">
                              <span
                                  className={
                                      'flex size-8 shrink-0 items-center justify-center rounded-lg ' +
                                      (item.quantity <= 0 ? 'bg-error-50 text-error-600' : 'bg-warning-50 text-warning-600')
                                  }
                              >
                                  <Package className="size-4" />
                              </span>
                              <div className="min-w-0 flex-1">
                                  <p className="truncate text-[13px] font-semibold text-slate-800">{item.product}</p>
                                  <p className="truncate text-[11px] text-slate-500">
                                      {item.warehouse}
                                      {item.unit ? ` · ${item.unit}` : ''}
                                  </p>
                              </div>
                              <span
                                  className={
                                      'shrink-0 rounded-md px-2 py-0.5 text-[11px] font-bold tabular-nums ' +
                                      (item.quantity <= 0 ? 'bg-error-50 text-error-700' : 'bg-warning-50 text-warning-700')
                                  }
                              >
                                  ×{item.quantity}
                              </span>
                          </li>
                      ))}
                  </ul>
              )}
            </section>

            <section className="flex flex-col rounded-xl border border-slate-200 bg-white lg:col-span-2">
              <header className="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                <div>
                    <h2 className="text-sm font-bold text-slate-900">Branch performance</h2>
                    <p className="mt-0.5 text-xs text-slate-500">Revenue by location, last 7 days</p>
                </div>
                <MapPin className="size-4 text-slate-300" />
              </header>
              {stats.branchPerformance.length === 0 ? (
                  <EmptyState icon={<MapPin className="size-5" />} title="No branch data" hint="Branch sales will appear here." />
              ) : (
                  <div className="divide-y divide-slate-100 py-1">
                      {stats.branchPerformance.map((b) => (
                          <MeterRow
                              key={b.name}
                              label={`${b.name} · ${b.orders} orders`}
                              value={Number(b.total)}
                              total={branchTotal}
                              amount={money(Number(b.total))}
                          />
                      ))}
                  </div>
              )}
            </section>
          </div>
        </div>
      </Deferred>
    </AppLayout>
  );
}