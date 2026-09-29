import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import { Plus, ShoppingBag } from 'lucide-react';
import AppLayout from '@/layouts/app-layout';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { EmptyState, PageHeader, SearchInput } from '@/components/admin';
import { Pagination } from '@/components/ui/pagination';

interface Order {
    id: number;
    po_no: string;
    supplier?: string | null;
    grand_total: number;
    status: string;
    order_date?: string | null;
    created_at?: string | null;
}

interface Props {
    orders: { data: Order[]; current_page: number; last_page: number; per_page: number; total: number };
    filters: { search: string };
}

function statusBadge(status: string) {
    if (status === 'received') return <Badge className="border-transparent bg-emerald-100 text-emerald-700 hover:bg-emerald-100">{status}</Badge>;
    if (status === 'cancelled') return <Badge className="border-transparent bg-red-100 text-red-700 hover:bg-red-100">{status}</Badge>;
    return <Badge className="border-transparent bg-amber-100 text-amber-800 hover:bg-amber-100">{status}</Badge>;
}

export default function PurchasesIndex({ orders, filters }: Props) {
    const safeOrders = orders ?? { data: [] as Order[], current_page: 1, last_page: 1, per_page: 15, total: 0 };
    const [search, setSearch] = useState(filters?.search ?? '');
    function pageParams(page?: number) {
        return { search: search || undefined, page };
    }
    function goToPage(page: number) {
        router.get('/purchases', pageParams(page), { only: ['orders'], preserveState: true, preserveScroll: true });
    }
    return (
        <AppLayout title="Purchases">
            <Head title="Purchases" />
            <PageHeader
                title="Purchases"
                count={safeOrders.total}
                description="Purchase orders from suppliers."
                actions={
                    <>
                        <SearchInput value={search} onChange={setSearch} onSubmit={() => router.get('/purchases', { search }, { preserveState: true })} placeholder="Search PO no or supplier…" />
                        <Link href="/purchases/create">
                            <Button className="h-10 rounded-xl bg-blue-600 font-semibold hover:bg-blue-700"><Plus className="size-4" /> New Purchase</Button>
                        </Link>
                    </>
                }
            />
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>PO No</TableHead>
                        <TableHead>Supplier</TableHead>
                        <TableHead className="text-right">Total</TableHead>
                        <TableHead>Status</TableHead>
                        <TableHead>Date</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    {safeOrders.data.map((o) => (
                        <TableRow key={o.id}>
                            <TableCell className="font-mono font-semibold">{o.po_no}</TableCell>
                            <TableCell className="text-slate-500">{o.supplier ?? '—'}</TableCell>
                            <TableCell className="text-right font-bold tabular-nums">${Number(o.grand_total).toFixed(2)}</TableCell>
                            <TableCell>{statusBadge(o.status)}</TableCell>
                            <TableCell className="text-slate-500">{o.order_date ?? o.created_at ?? '—'}</TableCell>
                        </TableRow>
                    ))}
                    {safeOrders.data.length === 0 && (
                        <TableRow>
                            <TableCell colSpan={5} className="p-0">
                                <EmptyState icon={<ShoppingBag className="size-5" />} title="No purchase orders found" hint="Create your first purchase order." />
                            </TableCell>
                        </TableRow>
                    )}
                </TableBody>
            </Table>
            {safeOrders.last_page > 1 && (
            <div className="mt-3 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-[0_1px_2px_rgba(15,23,42,0.05)]">
                <Pagination currentPage={safeOrders.current_page} lastPage={safeOrders.last_page} perPage={safeOrders.per_page} total={safeOrders.total} onPageChange={goToPage} showPerPageSelector={false} buildUrl={() => '/purchases'} prefetchOnly={['orders']} prefetchData={(page) => pageParams(page)} />
            </div>
            )}
        </AppLayout>
    );
}
