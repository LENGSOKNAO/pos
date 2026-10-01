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
  Search,
  Bell,
  MapPin,
  ChevronDown,
  ChevronsLeft,
  ChevronsRight,
  Home,
  LayoutGrid,
  type LucideIcon,
} from "lucide-react";
import { useState, useRef, useEffect } from "react";
import type { ReactNode, FormEvent } from "react";
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
  employee?: {
    first_name?: string | null;
    last_name?: string | null;
    branch?: { id?: string; name?: string } | null;
  } | null;
  roles?: { name?: string }[];
}

interface AuthProfile extends AuthUser {
  email?: string | null;
  status?: string;
}

interface NavLink {
  label: string;
  href: string;
  icon: LucideIcon;
}

interface NavSection {
  label: string;
  links: NavLink[];
}

const SECTIONS: NavSection[] = [
  {
    label: "Sell",
    links: [
      { label: "POS Terminal", href: PosController.index.url(), icon: ShoppingCart },
      { label: "Sales", href: "/sales", icon: ReceiptText },
      { label: "Returns", href: "/sales-returns", icon: RotateCcw },
    ],
  },
  {
    label: "Manage",
    links: [
      { label: "Products", href: ProductController.index.url(), icon: Package },
      { label: "Stock", href: StockController.index.url(), icon: Warehouse },
      { label: "Purchases", href: "/purchases", icon: ShoppingBag },
    ],
  },
  {
    label: "Partners",
    links: [
      { label: "Customers", href: "/customers", icon: Users },
      { label: "Suppliers", href: "/suppliers", icon: Truck },
    ],
  },
  {
    label: "Money",
    links: [
      { label: "Cash", href: "/cash-sessions", icon: Wallet },
      { label: "Reports", href: "/reports", icon: BarChart3 },
    ],
  },
  {
    label: "System",
    links: [
      { label: "Users", href: "/users", icon: Users },
      { label: "Settings", href: "/settings", icon: Settings },
    ],
  },
];

const BOTTOM_TABS: (NavLink & { key: string })[] = [
  { key: "home", label: "Home", href: dashboard.url(), icon: Home },
  { key: "pos", label: "POS", href: PosController.index.url(), icon: ShoppingCart },
  { key: "sales", label: "Sales", href: "/sales", icon: ReceiptText },
  { key: "more", label: "More", href: "/reports", icon: LayoutGrid },
];

export default function AppLayout({ children, fullscreen = false, title }: AppLayoutProps) {
  const [mobileOpen, setMobileOpen] = useState(false);
  if (fullscreen) {
    return <div className="flex h-screen flex-col bg-neutral-100">{children}</div>;
  }
  return (
    <Shell title={title} mobileOpen={mobileOpen} setMobileOpen={setMobileOpen}>
      {children}
    </Shell>
  );
}

function NavItem({
  link,
  active,
  collapsed,
  onNavigate,
}: {
  link: NavLink;
  active: boolean;
  collapsed: boolean;
  onNavigate: () => void;
}) {
  return (
    <Link
      href={link.href}
      prefetch="hover"
      cacheFor="5m"
      onClick={onNavigate}
      title={collapsed ? link.label : undefined}
      aria-current={active ? "page" : undefined}
      className={cn(
        "group relative flex items-center gap-3 rounded-xl px-3 py-2.5 text-[13px] font-medium transition-all duration-200 focus-visible:ring-2 focus-visible:ring-brand-500 focus-visible:outline-none",
        collapsed && "justify-center px-0",
        active
          ? "bg-brand-600/15 text-brand-600"
          : "text-neutral-500 hover:bg-neutral-100 hover:text-neutral-900",
      )}
    >
      <link.icon className={cn("size-[18px] shrink-0", active ? "text-brand-600" : "text-neutral-400 group-hover:text-neutral-600")} />
      {!collapsed && <span className="truncate">{link.label}</span>}
      {collapsed && (
        <span className="pointer-events-none absolute left-full ml-2 hidden rounded-md bg-neutral-900 px-2 py-1 text-xs font-medium whitespace-nowrap text-white shadow-lg group-hover:block">
          {link.label}
        </span>
      )}
    </Link>
  );
}

function SidebarBody({
  url,
  collapsed,
  onNavigate,
  userName,
  roleName,
}: {
  url: string;
  collapsed: boolean;
  onNavigate: () => void;
  userName: string;
  roleName: string;
}) {
  const dashboardHref = dashboard.url();
  const dashboardActive = url === dashboardHref || url.startsWith(dashboardHref + "?");
  return (
    <div className={cn("flex h-full flex-col bg-slate-950", collapsed ? "w-16" : "w-64")}>
      <Link
        href={dashboardHref}
        onClick={onNavigate}
        className={cn(
          "flex items-center gap-3 border-b border-white/10 px-4 py-4",
          collapsed && "justify-center px-0",
        )}
      >
        <span className="flex size-9 shrink-0 items-center justify-center rounded-xl bg-brand-600 text-base font-black text-white">
          S
        </span>
        {!collapsed && (
          <span className="leading-tight">
            <span className="block text-[15px] font-bold tracking-tight text-white">SquarePOS</span>
            <span className="block text-[11px] font-medium text-neutral-400">Back Office</span>
          </span>
        )}
      </Link>
      <nav className={cn("flex-1 space-y-4 overflow-y-auto px-3 py-4", collapsed && "px-2")}>
        <NavItem
          link={{ label: "Dashboard", href: dashboardHref, icon: LayoutDashboard }}
          active={dashboardActive}
          collapsed={collapsed}
          onNavigate={onNavigate}
        />
        {SECTIONS.map((section) => (
          <div key={section.label}>
            {!collapsed && (
              <p className="mb-1 px-3 text-[10px] font-semibold tracking-widest text-neutral-500 uppercase">
                {section.label}
              </p>
            )}
            {collapsed && <div className="mx-2 mb-1 border-t border-white/10" />}
            <div className="space-y-0.5">
              {section.links.map((l) => (
                <NavItem
                  key={l.label}
                  link={l}
                  active={url.startsWith(l.href)}
                  collapsed={collapsed}
                  onNavigate={onNavigate}
                />
              ))}
            </div>
          </div>
        ))}
      </nav>
      <div className="border-t border-white/10 p-3">
        {collapsed ? (
          <button
            onClick={() => router.post(logout.url())}
            title={`${userName} — Logout`}
            aria-label="Logout"
            className="mx-auto flex size-9 items-center justify-center rounded-full bg-brand-600 text-xs font-bold text-white transition-colors hover:bg-brand-500"
          >
            {userName.charAt(0).toUpperCase()}
          </button>
        ) : (
          <div className="flex items-center gap-2.5 rounded-xl bg-white/5 px-3 py-2.5">
            <span className="flex size-9 shrink-0 items-center justify-center rounded-full bg-brand-600 text-xs font-bold text-white">
              {userName.charAt(0).toUpperCase()}
            </span>
            <div className="min-w-0 flex-1 leading-tight">
              <p className="truncate text-sm font-semibold text-white">{userName}</p>
              <Deferred
                data="authProfile"
                fallback={<p className="h-3 w-12 animate-pulse rounded bg-white/10" />}
              >
                <p className="truncate text-xs text-neutral-400">{roleName || "Staff"}</p>
              </Deferred>
            </div>
            <button
              onClick={() => router.post(logout.url())}
              title="Logout"
              aria-label="Logout"
              className="rounded-md p-1.5 text-neutral-400 transition-colors hover:bg-white/10 hover:text-white"
            >
              <LogOut className="size-4" />
            </button>
          </div>
        )}
      </div>
    </div>
  );
}

function Header({
  onMenu,
  userName,
  roleName,
  branchName,
}: {
  onMenu: () => void;
  userName: string;
  roleName: string;
  branchName: string | null;
}) {
  const [q, setQ] = useState("");
  const [menuOpen, setMenuOpen] = useState(false);
  const menuRef = useRef<HTMLDivElement>(null);

  useEffect(() => {
    if (!menuOpen) {
      return;
    }
    const close = (e: MouseEvent) => {
      if (menuRef.current && !menuRef.current.contains(e.target as Node)) {
        setMenuOpen(false);
      }
    };
    document.addEventListener("mousedown", close);
    return () => document.removeEventListener("mousedown", close);
  }, [menuOpen]);

  const submit = (e: FormEvent) => {
    e.preventDefault();
    router.get(ProductController.index.url(), { search: q }, { preserveState: true });
  };

  return (
    <header className="sticky top-0 z-30 flex h-16 items-center gap-3 border-b border-neutral-200 bg-white px-4 sm:px-6">
      <button
        onClick={onMenu}
        aria-label="Open menu"
        className="rounded-lg p-2 text-neutral-500 hover:bg-neutral-100 hover:text-neutral-800 md:hidden"
      >
        <Menu className="size-5" />
      </button>
      <form onSubmit={submit} className="relative hidden w-full max-w-sm sm:block">
        <Search className="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-neutral-400" />
        <input
          value={q}
          onChange={(e) => setQ(e.target.value)}
          placeholder="Search products…"
          className="h-9 w-full rounded-xl border border-neutral-200 bg-neutral-50 pr-3 pl-9 text-sm outline-none placeholder:text-neutral-400 focus:border-brand-600 focus:bg-white focus:ring-2 focus:ring-brand-600/10"
        />
      </form>
      <div className="ml-auto flex items-center gap-2">
        <Deferred
          data="authProfile"
          fallback={<span className="hidden h-7 w-24 animate-pulse rounded-full bg-neutral-100 md:inline-block" />}
        >
          {branchName ? (
            <span className="hidden items-center gap-1.5 rounded-full border border-neutral-200 bg-neutral-50 px-2.5 py-1 text-xs font-medium text-neutral-600 md:inline-flex">
              <MapPin className="size-3.5 text-neutral-400" />
              {branchName}
            </span>
          ) : null}
        </Deferred>
        <Link
          href="/notifications"
          prefetch="hover"
          cacheFor="5m"
          aria-label="Notifications"
          className="relative rounded-lg p-2 text-neutral-500 transition-colors hover:bg-neutral-100 hover:text-neutral-800"
        >
          <Bell className="size-5" />
        </Link>
        <div className="relative" ref={menuRef}>
          <button
            onClick={() => setMenuOpen((v) => !v)}
            aria-label="Account menu"
            aria-expanded={menuOpen}
            className="flex items-center gap-1.5 rounded-xl p-1 transition-colors hover:bg-neutral-100"
          >
            <span className="flex size-9 items-center justify-center rounded-full bg-brand-600 text-xs font-bold text-white">
              {userName.charAt(0).toUpperCase()}
            </span>
            <ChevronDown className="hidden size-4 text-neutral-400 sm:block" />
          </button>
          {menuOpen && (
            <div className="absolute right-0 mt-2 w-52 overflow-hidden rounded-xl border border-neutral-200 bg-white py-1 shadow-lg">
              <div className="px-4 py-2.5">
                <p className="truncate text-sm font-semibold text-neutral-900">{userName}</p>
                <Deferred
                  data="authProfile"
                  fallback={<p className="h-3 w-16 animate-pulse rounded bg-neutral-100" />}
                >
                  <p className="truncate text-xs text-neutral-500">{roleName || "Staff"}</p>
                </Deferred>
              </div>
              <div className="border-t border-neutral-100" />
              <button
                onClick={() => router.post(logout.url())}
                className="flex w-full items-center gap-2 px-4 py-2 text-left text-sm font-medium text-neutral-700 transition-colors hover:bg-neutral-50"
              >
                <LogOut className="size-4 text-neutral-400" />
                Logout
              </button>
            </div>
          )}
        </div>
      </div>
    </header>
  );
}

function Shell({
  children,
  mobileOpen,
  setMobileOpen,
}: AppLayoutProps & { mobileOpen: boolean; setMobileOpen: (v: boolean) => void }) {
  const { url, props } = usePage<{
    auth?: { user?: AuthUser };
    authProfile?: AuthProfile;
  }>();
  const [collapsed, setCollapsed] = useState(false);
  const identity = props.auth?.user;
  const profile = props.authProfile;
  const fullName = `${profile?.employee?.first_name ?? ""} ${profile?.employee?.last_name ?? ""}`.trim();
  const userName = fullName || identity?.username || "Cashier";
  const roleName = profile?.roles?.[0]?.name ?? "";
  const branchName = profile?.employee?.branch?.name ?? identity?.employee?.branch?.name ?? null;

  return (
    <div className="flex min-h-screen bg-neutral-100 text-sm">
      <aside
        className={cn(
          "sticky top-0 hidden h-screen shrink-0 transition-[width] duration-200 md:block",
          collapsed ? "w-16" : "w-64",
        )}
      >
        <SidebarBody
          url={url}
          collapsed={collapsed}
          onNavigate={() => {}}
          userName={userName}
          roleName={roleName}
        />
        <button
          onClick={() => setCollapsed((v) => !v)}
          aria-label={collapsed ? "Expand sidebar" : "Collapse sidebar"}
          className="absolute -right-3 top-16 hidden rounded-full border border-neutral-200 bg-white p-1 text-neutral-500 shadow-sm transition-colors hover:text-neutral-900 md:block"
        >
          {collapsed ? <ChevronsRight className="size-3.5" /> : <ChevronsLeft className="size-3.5" />}
        </button>
      </aside>

      {mobileOpen && (
        <div className="fixed inset-0 z-50 md:hidden">
          <div className="absolute inset-0 bg-slate-900/40" onClick={() => setMobileOpen(false)} />
          <div className="absolute inset-y-0 left-0 shadow-xl">
            <div className="relative h-full">
              <SidebarBody
                url={url}
                collapsed={false}
                onNavigate={() => setMobileOpen(false)}
                userName={userName}
                roleName={roleName}
              />
              <button
                onClick={() => setMobileOpen(false)}
                aria-label="Close menu"
                className="absolute top-4 right-3 rounded-md p-1.5 text-neutral-400 hover:bg-white/10 hover:text-white"
              >
                <X className="size-5" />
              </button>
            </div>
          </div>
        </div>
      )}

      <div className="flex min-w-0 flex-1 flex-col pb-16 md:pb-0">
        <Header
          onMenu={() => setMobileOpen(true)}
          userName={userName}
          roleName={roleName}
          branchName={branchName}
        />
        <main className="mx-auto w-full max-w-7xl flex-1 p-4 sm:p-6 lg:p-8">{children}</main>

        <nav className="fixed inset-x-0 bottom-0 z-30 border-t border-neutral-200 bg-white md:hidden">
          <div className="grid grid-cols-4">
            {BOTTOM_TABS.map((t) => {
              const active =
                t.key === "more" ? false : url.startsWith(t.href);
              const content = (
                <>
                  <t.icon className={cn("size-5", active ? "text-brand-600" : "text-neutral-400")} />
                  <span
                    className={cn(
                      "text-[11px] font-medium",
                      active ? "text-brand-600" : "text-neutral-500",
                    )}
                  >
                    {t.label}
                  </span>
                </>
              );
              const cls =
                "flex flex-col items-center gap-0.5 py-2 transition-colors";
              if (t.key === "more") {
                return (
                  <button key={t.key} onClick={() => setMobileOpen(true)} className={cls} aria-label="More">
                    {content}
                  </button>
                );
              }
              return (
                <Link
                  key={t.key}
                  href={t.href}
                  prefetch="hover"
                  cacheFor="5m"
                  className={cls}
                >
                  {content}
                </Link>
              );
            })}
          </div>
        </nav>
      </div>
    </div>
  );
}