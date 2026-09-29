import { Head, Deferred, router } from '@inertiajs/react';
import { AlertTriangle, Bell, CheckCheck, Info, Receipt, Truck } from 'lucide-react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { EmptyState, PageHeader, RowsSkeleton } from '@/components/admin';

interface Item {
    id: number;
    type: string;
    title: string;
    message: string;
    is_read: boolean;
    created_at: string;
}

function iconFor(type: string) {
    const t = type.toLowerCase();
    if (t.includes('stock') || t.includes('inventory') || t.includes('low')) return AlertTriangle;
    if (t.includes('sale') || t.includes('order') || t.includes('payment') || t.includes('invoice')) return Receipt;
    if (t.includes('purchase') || t.includes('supplier') || t.includes('delivery')) return Truck;
    return Info;
}

function relativeTime(iso: string) {
    const then = new Date(iso).getTime();
    if (Number.isNaN(then)) return '';
    const s = Math.max(0, Math.floor((Date.now() - then) / 1000));
    if (s < 60) return 'just now';
    const m = Math.floor(s / 60);
    if (m < 60) return `${m}m ago`;
    const h = Math.floor(m / 60);
    if (h < 24) return `${h}h ago`;
    const d = Math.floor(h / 24);
    if (d < 30) return `${d}d ago`;
    return new Date(iso).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
}

export default function NotificationsIndex({ items, unreadCount }: { items?: Item[]; unreadCount?: number }) {
    const safeItems = Array.isArray(items) ? items : [];
    const unread = unreadCount ?? safeItems.filter((i) => !i.is_read).length;
    const markAll = () => {
        router.post('/notifications/read-all', {}, { preserveScroll: true });
    };

    return (
        <AppLayout title="Notifications">
            <Head title="Notifications" />
            <PageHeader
                title="Notifications"
                description={unread > 0 ? `${unread} unread` : 'You are all caught up.'}
                count={unread > 0 ? unread : undefined}
                actions={
                    unread > 0 ? (
                        <Button onClick={markAll} variant="outline" className="h-10 rounded-xl border-slate-200 bg-white">
                            <CheckCheck className="size-4" /> Mark all read
                        </Button>
                    ) : undefined
                }
            />

            <Deferred data="items" fallback={<RowsSkeleton count={6} />}>
                <div className="overflow-hidden rounded-2xl border border-slate-200/90 bg-white">
                    {safeItems.length === 0 && <EmptyState icon={<Bell className="size-5" />} title="No notifications" hint="New alerts will appear here." />}
                    {safeItems.map((n) => {
                        const Icon = iconFor(n.type);
                        return (
                            <div key={n.id} className={`flex items-start gap-3 border-b border-slate-100 px-4 py-3.5 last:border-0 ${n.is_read ? '' : 'bg-blue-50/40'}`}>
                                {!n.is_read && <span className="mt-1.5 size-2 shrink-0 rounded-full bg-blue-600" />}
                                <span className="flex size-10 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-slate-600">
                                    <Icon className="size-4" />
                                </span>
                                <div className="min-w-0 flex-1">
                                    <div className="flex items-center justify-between gap-3">
                                        <p className={`truncate text-sm ${n.is_read ? 'font-semibold text-slate-700' : 'font-bold text-slate-900'}`}>{n.title}</p>
                                        <span className="shrink-0 text-xs text-slate-400">{relativeTime(n.created_at)}</span>
                                    </div>
                                    <p className="mt-0.5 line-clamp-2 text-sm text-slate-500">{n.message}</p>
                                </div>
                            </div>
                        );
                    })}
                </div>
            </Deferred>
        </AppLayout>
    );
}
