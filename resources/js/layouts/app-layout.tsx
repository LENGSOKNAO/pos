import { Link, router, usePage } from '@inertiajs/react';
import { LayoutDashboard, LogOut, Package, ReceiptText, RotateCcw, ShoppingCart, ShoppingBag, Users, Wallet, Warehouse, Truck, BarChart3, Settings } from 'lucide-react';
import type { ReactNode } from 'react';
import { dashboard, logout } from '@/routes';
import PosController from '@/actions/App/Http/Controllers/PosController';
import ProductController from '@/actions/App/Http/Controllers/ProductController';
import StockController from '@/actions/App/Http/Controllers/StockController';
import { cn } from '@/lib/utils';

interface AppLayoutProps {
    children: ReactNode;
    fullscreen?: boolean;
    title?: string;
}

interface AuthUser {
    username?: string;
    employee?: { first_name?: string | null; last_name?: string | null } | null;
    roles?: { name?: string }[];
}

export default function AppLayout({ children, fullscreen = false, title }: AppLayoutProps) {
    if (fullscreen) {
        return <div className="min-h-screen bg-slate-100">{children}</div>;
    }
    const { url, props } = usePage<{ auth?: { user?: AuthUser } }>();
    const user = props.auth?.user;
    const fullName = `${user?.employee?.first_name ?? ''} ${user?.employee?.last_name ?? ''}`.trim();
    const userName = fullName || user?.username || 'Cashier';
    const roleName = user?.roles?.[0]?.name ?? '';
    const today = new Date().toLocaleDateString('en-US', { weekday: 'long', month: 'short', day: 'numeric' });
    const links = [
        { label: 'Dashboard', href: dashboard.url(), icon: LayoutDashboard },
        { label: 'POS Terminal', href: PosController.index.url(), icon: ShoppingCart },
        { label: 'Products', href: ProductController.index.url(), icon: Package },
        { label: 'Stock', href: StockController.index.url(), icon: Warehouse },
        { label: 'Sales', href: '/sales', icon: ReceiptText },
        { label: 'Returns', href: '/sales-returns', icon: RotateCcw },
        { label: 'Customers', href: '/customers', icon: Users },
        { label: 'Suppliers', href: '/suppliers', icon: Truck },
        { label: 'Purchases', href: '/purchases', icon: ShoppingBag },
        { label: 'Cash', href: '/cash-sessions', icon: Wallet },
        { label: 'Reports', href: '/reports', icon: BarChart3 },
        { label: 'Users', href: '/users', icon: Users },
        { label: 'Settings', href: '/settings', icon: Settings },
    ];
    const activeLabel = [...links].reverse().find((l) => url.startsWith(l.href))?.label ?? title ?? 'Back Office';
    return (
        <div className="flex min-h-screen bg-slate-100 text-sm">
            <aside className="flex w-16 shrink-0 flex-col items-center bg-[#0A1633] py-3 lg:w-24">
                <Link href={dashboard.url()} title="SquarePOS — Dashboard" className="mb-1 flex size-10 items-center justify-center rounded-xl bg-blue-600 text-lg font-black text-white shadow-md shadow-blue-950/50 ring-1 ring-white/20">
                    $
                </Link>
                <p className="mb-3 hidden text-[10px] font-bold tracking-widest text-blue-300/70 uppercase lg:block">SquarePOS</p>
                <nav className="flex w-full flex-1 flex-col items-center gap-1 overflow-y-auto px-2">
                    {links.map((l) => {
                        const active = url.startsWith(l.href);
                        return (
                            <Link
                                key={l.label}
                                href={l.href}
                                prefetch="hover"
                                cacheFor="5m"
                                title={l.label}
                                aria-label={l.label}
                                className={cn(
                                    'relative flex w-full flex-col items-center gap-1 rounded-xl px-1 py-2 transition-colors focus-visible:ring-2 focus-visible:ring-blue-400 focus-visible:outline-none',
                                    active ? 'bg-white/10 text-white' : 'text-slate-400 hover:bg-white/5 hover:text-white',
                                )}
                            >
                                {active && <span className="absolute top-1/2 left-0 h-6 w-1 -translate-y-1/2 rounded-r-full bg-blue-500" />}
                                <l.icon className="size-5" />
                                <span className="hidden text-[10px] leading-none font-semibold lg:block">{l.label}</span>
                            </Link>
                        );
                    })}
                </nav>
                <button
                    onClick={() => router.post(logout.url())}
                    title="Logout"
                    aria-label="Logout"
                    className="mt-3 flex w-full flex-col items-center gap-1 rounded-xl px-1 py-2 text-slate-400 transition-colors hover:bg-white/5 hover:text-white"
                >
                    <LogOut className="size-5" />
                    <span className="hidden text-[10px] leading-none font-semibold lg:block">Logout</span>
                </button>
            </aside>
            <div className="flex min-w-0 flex-1 flex-col">
                <header className="sticky top-0 z-30 flex h-16 items-center gap-3 border-b border-slate-200 bg-white/90 px-4 shadow-[0_1px_2px_rgba(15,23,42,0.04)] backdrop-blur sm:px-6">
                    <div className="min-w-0 leading-tight">
                        <p className="truncate text-xs font-medium text-slate-400">SquarePOS / Back Office · {today}</p>
                        <h1 className="truncate text-xl font-bold tracking-tight text-slate-900">{title ?? activeLabel}</h1>
                    </div>
                    <div className="ml-auto flex items-center gap-2 sm:gap-3">
                        {roleName && (
                            <span className="hidden rounded-full bg-blue-50 px-2.5 py-1 text-xs font-bold text-blue-700 md:inline">
                                {roleName}
                            </span>
                        )}
                        <span className="flex items-center gap-2 rounded-full border border-slate-200 bg-slate-50 py-1 pr-3 pl-1 text-sm font-semibold text-slate-700">
                            <span className="flex size-8 items-center justify-center rounded-full bg-gradient-to-br from-blue-500 to-blue-700 text-xs font-black text-white">
                                {userName.charAt(0).toUpperCase()}
                            </span>
                            <span className="hidden max-w-32 truncate sm:inline">{userName}</span>
                        </span>
                    </div>
                </header>
                <main className="mx-auto w-full max-w-7xl flex-1 p-4 sm:p-6 lg:p-8">{children}</main>
            </div>
        </div>
    );
}
