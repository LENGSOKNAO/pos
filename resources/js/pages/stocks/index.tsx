import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import { Warehouse } from 'lucide-react';
import AppLayout from '@/layouts/app-layout';
import StockController from '@/actions/App/Http/Controllers/StockController';
import { Badge } from '@/components/ui/badge';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { EmptyState, PageHeader, SearchInput } from '@/components/admin';

interface StockRow {
    id: number;
    warehouse?: string | null;
    product?: string | null;
    sku?: string | null;
    unit?: string | null;
    quantity: number;
}

interface Props {
    stocks: StockRow[];
    filters?: { search: string };
}

function qtyBadge(qty: number) {
    if (qty <= 0) return <Badge className="border-transparent bg-red-100 text-red-700 hover:bg-red-100 tabular-nums">{qty}</Badge>;
    if (qty < 10) return <Badge className="border-transparent bg-amber-100 text-amber-800 hover:bg-amber-100 tabular-nums">{qty}</Badge>;
    return <Badge className="border-transparent bg-emerald-100 text-emerald-700 hover:bg-emerald-100 tabular-nums">{qty}</Badge>;
}

export default function StocksIndex({ stocks, filters }: Props) {
    const [search, setSearch] = useState(filters?.search ?? '');
    const submit = () => router.get(StockController.index.url(), { search }, { preserveState: true });
    return (
        <AppLayout title="Stock">
            <Head title="Stocks" />
            <PageHeader
                title="Stock"
                count={stocks.length}
                description="On-hand quantities by warehouse and unit."
                actions={<SearchInput value={search} onChange={setSearch} onSubmit={submit} placeholder="Search stocks…" />}
            />
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Warehouse</TableHead>
                        <TableHead>Product</TableHead>
                        <TableHead>Unit SKU</TableHead>
                        <TableHead className="text-right">Quantity</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    {stocks.map((s) => (
                        <TableRow key={s.id}>
                            <TableCell className="text-slate-500">{s.warehouse ?? '—'}</TableCell>
                            <TableCell className="font-semibold text-slate-900">{s.product ?? '—'}</TableCell>
                            <TableCell className="font-mono text-xs text-slate-500">{s.sku ?? '—'}</TableCell>
                            <TableCell className="text-right">{qtyBadge(s.quantity)}</TableCell>
                        </TableRow>
                    ))}
                    {stocks.length === 0 && (
                        <TableRow>
                            <TableCell colSpan={4} className="p-0">
                                <EmptyState icon={<Warehouse className="size-5" />} title="No stock found" hint="Try a different search term." />
                            </TableCell>
                        </TableRow>
                    )}
                </TableBody>
            </Table>
        </AppLayout>
    );
}
