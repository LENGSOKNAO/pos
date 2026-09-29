import { Link, router, usePage, Deferred } from "@inertiajs/react";
import {
    LayoutDashboard,
    LogOut,
    Menu,
    Package,
    ReceiptText,
    RotateCcw,
    ShoppingCart,
    ShoppingBag,
    Users,
    Wallet,
    Warehouse,
    Truck,
    BarChart3,
    Settings,
    X,
} from "lucide-react";
import { useState } from "react";
import type { ReactNode } from "react";
import { dashboard, logout } from "@/routes";
import PosController from "@/actions/App/Http/Controllers/PosController";
import ProductController from "@/actions/App/Http/Controllers/ProductController";
import StockController from "@/actions/App/Http/Controllers/StockController";
import { cn } from "@/lib/utils";

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

interface AuthProfile extends AuthUser {
    email?: string | null;
    status?: string;
}

export default function AppLayout({
    children,
    fullscreen = false,
    title,
}: AppLayoutProps) {
    const [mobileOpen, setMobileOpen] = useState(false);
    if (fullscreen) {
        return <div className="min-h-screen bg-slate-100">{children}</div>;
    }
    return <Shell title={title} mobileOpen={mobileOpen} setMobileOpen={setMobileOpen}>{children}</Shell>;
}

function Shell({
    children,
    title,
    mobileOpen,
    setMobileOpen,
}: AppLayoutProps & { mobileOpen: boolean; setMobileOpen: (v: boolean) => void }) {
    const { url, props } = usePage<{
        auth?: { user?: AuthUser };
        authProfile?: AuthProfile;
    }>();
    const identity = props.auth?.user;
    const profile = props.authProfile;
    const fullName =
        `${profile?.employee?.first_name ?? ""} ${profile?.employee?.last_name ?? ""}`.trim();
    const userName = fullName || identity?.username || "Cashier";
    const roleName = profile?.roles?.[0]?.name ?? "";
    const today = new Date().toLocaleDateString("en-US", {
        weekday: "long",
        month: "short",
        day: "numeric",
    });
    const links = [
        { label: "Dashboard", href: dashboard.url(), icon: LayoutDashboard },
        {
            label: "POS Terminal",
            href: PosController.index.url(),
            icon: ShoppingCart,
        },
        {
            label: "Products",
            href: ProductController.index.url(),
            icon: Package,
        },
        { label: "Stock", href: StockController.index.url(), icon: Warehouse },
        { label: "Sales", href: "/sales", icon: ReceiptText },
        { label: "Returns", href: "/sales-returns", icon: RotateCcw },
        { label: "Customers", href: "/customers", icon: Users },
        { label: "Suppliers", href: "/suppliers", icon: Truck },
        { label: "Purchases", href: "/purchases", icon: ShoppingBag },
        { label: "Cash", href: "/cash-sessions", icon: Wallet },
        { label: "Reports", href: "/reports", icon: BarChart3 },
        { label: "Users", href: "/users", icon: Users },
        { label: "Settings", href: "/settings", icon: Settings },
    ];
    const activeLabel =
        [...links].reverse().find((l) => url.startsWith(l.href))?.label ??
        title ??
        "Back Office";

    const sidebar = (
        <div className="flex h-full w-60 flex-col bg-white">
            <Link href={dashboard.url()} className="flex items-center gap-2.5 border-b border-slate-100 px-5 py-4">
                <span className="flex size-9 items-center justify-center rounded-lg bg-blue-600 text-base font-black text-white">
                    S
                </span>
                <span className="leading-tight">
                    <span className="block text-[15px] font-bold tracking-tight text-slate-900">SquarePOS</span>
                    <span className="block text-[11px] font-medium text-slate-400">Back Office</span>
                </span>
            </Link>
            <nav className="flex-1 space-y-0.5 overflow-y-auto px-3 py-4">
                {links.map((l) => {
                    const active = url.startsWith(l.href);
                    return (
                        <Link
                            key={l.label}
                            href={l.href}
                            prefetch="hover"
                            onClick={() => setMobileOpen(false)}
                            className={cn(
                                "flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors focus-visible:ring-2 focus-visible:ring-blue-600 focus-visible:outline-none",
                                active
                                    ? "bg-blue-600 text-white shadow-sm"
                                    : "text-slate-600 hover:bg-slate-100 hover:text-slate-900",
                            )}
                        >
                            <l.icon className="size-4.5 shrink-0" />
                            {l.label}
                        </Link>
                    );
                })}
            </nav>
            <div className="border-t border-slate-100 p-3">
                <div className="flex items-center gap-2.5 rounded-lg bg-slate-50 px-3 py-2.5">
                    <span className="flex size-9 shrink-0 items-center justify-center rounded-full bg-blue-600 text-xs font-bold text-white">
                        {userName.charAt(0).toUpperCase()}
                    </span>
                    <div className="min-w-0 flex-1 leading-tight">
                        <p className="truncate text-sm font-semibold text-slate-900">{userName}</p>
                        <Deferred data="authProfile" fallback={<p className="h-3 w-12 animate-pulse rounded bg-slate-200" />}>
                            <p className="truncate text-xs text-slate-500">{roleName || "Staff"}</p>
                        </Deferred>
                    </div>
                    <button
                        onClick={() => router.post(logout.url())}
                        title="Logout"
                        aria-label="Logout"
                        className="rounded-md p-1.5 text-slate-400 transition-colors hover:bg-slate-200 hover:text-slate-700"
                    >
                        <LogOut className="size-4" />
                    </button>
                </div>
            </div>
        </div>
    );

    return (
        <div className="flex min-h-screen bg-slate-100 text-sm">
            <aside className="sticky top-0 hidden h-screen w-60 shrink-0 border-r border-slate-200 md:block">
                {sidebar}
            </aside>

            {mobileOpen && (
                <div className="fixed inset-0 z-50 md:hidden">
                    <div className="absolute inset-0 bg-slate-900/40" onClick={() => setMobileOpen(false)} />
                    <div className="absolute inset-y-0 left-0 shadow-xl">
                        <div className="relative h-full">
                            {sidebar}
                            <button
                                onClick={() => setMobileOpen(false)}
                                aria-label="Close menu"
                                className="absolute top-4 right-3 rounded-md p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-700"
                            >
                                <X className="size-5" />
                            </button>
                        </div>
                    </div>
                </div>
            )}

            <div className="flex min-w-0 flex-1 flex-col">
                <header className="sticky top-0 z-30 flex h-16 items-center gap-3 border-b border-slate-200 bg-white px-4 sm:px-6">
                    <button
                        onClick={() => setMobileOpen(true)}
                        aria-label="Open menu"
                        className="rounded-md p-2 text-slate-500 hover:bg-slate-100 hover:text-slate-800 md:hidden"
                    >
                        <Menu className="size-5" />
                    </button>
                    <div className="min-w-0 leading-tight">
                        <p className="truncate text-xs font-medium text-slate-400">
                            SquarePOS · {today}
                        </p>
                        <h1 className="truncate text-lg font-bold tracking-tight text-slate-900">
                            {title ?? activeLabel}
                        </h1>
                    </div>
                    <div className="ml-auto flex items-center gap-2 sm:gap-3">
                        <Deferred data="authProfile" fallback={<span className="hidden h-6 w-16 animate-pulse rounded-full bg-slate-100 md:inline-block" />}>
                            {roleName && (
                                <span className="hidden rounded-full bg-blue-50 px-2.5 py-1 text-xs font-semibold text-blue-700 md:inline">
                                    {roleName}
                                </span>
                            )}
                        </Deferred>
                        <span className="flex size-9 items-center justify-center rounded-full bg-blue-600 text-xs font-bold text-white">
                            {userName.charAt(0).toUpperCase()}
                        </span>
                    </div>
                </header>
                <main className="mx-auto w-full max-w-7xl flex-1 p-4 sm:p-6 lg:p-8">
                    {children}
                </main>
            </div>
        </div>
    );
}
