import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import { Package } from 'lucide-react';
import AppLayout from '@/layouts/app-layout';
import ProductController from '@/actions/App/Http/Controllers/ProductController';
import { Badge } from '@/components/ui/badge';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { EmptyState, PageHeader, SearchInput } from '@/components/admin';

interface ProductRow {
    id: number;
    name: string;
    sku: string;
    category?: string | null;
    brand?: string | null;
    unit?: string | null;
    selling_price: number;
    stock_qty: number;
}

interface Props {
    products: ProductRow[];
    meta: { current_page: number; last_page: number; total: number };
    filters: { search: string };
}

function stockBadge(qty: number) {
    if (qty <= 0) return <Badge className="border-transparent bg-red-100 text-red-700 hover:bg-red-100 tabular-nums">{qty}</Badge>;
    if (qty < 10) return <Badge className="border-transparent bg-amber-100 text-amber-800 hover:bg-amber-100 tabular-nums">{qty}</Badge>;
    return <Badge className="border-transparent bg-emerald-100 text-emerald-700 hover:bg-emerald-100 tabular-nums">{qty}</Badge>;
}

export default function ProductsIndex({ products, meta, filters }: Props) {
    const [search, setSearch] = useState(filters.search ?? '');
    const submit = () => router.get(ProductController.index.url(), { search }, { preserveState: true });
    return (
        <AppLayout title="Products">
            <Head title="Products" />
            <PageHeader
                title="Products"
                count={meta.total}
                description="Catalog items, prices and stock levels."
                actions={<SearchInput value={search} onChange={setSearch} onSubmit={submit} placeholder="Search products…" />}
            />
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Name</TableHead>
                        <TableHead>Category</TableHead>
                        <TableHead>Brand</TableHead>
                        <TableHead className="text-right">Price</TableHead>
                        <TableHead className="text-right">Stock</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    {products.map((p) => (
                        <TableRow key={p.id}>
                            <TableCell>
                                <p className="font-semibold text-slate-900">{p.name}</p>
                                <p className="font-mono text-xs text-slate-500">{p.sku}</p>
                            </TableCell>
                            <TableCell className="text-slate-500">{p.category ?? '—'}</TableCell>
                            <TableCell className="text-slate-500">{p.brand ?? '—'}</TableCell>
                            <TableCell className="text-right font-bold tabular-nums">${Number(p.selling_price).toFixed(2)}</TableCell>
                            <TableCell className="text-right">{stockBadge(p.stock_qty)}</TableCell>
                        </TableRow>
                    ))}
                    {products.length === 0 && (
                        <TableRow>
                            <TableCell colSpan={5} className="p-0">
                                <EmptyState icon={<Package className="size-5" />} title="No products found" hint="Try a different search term." />
                            </TableCell>
                        </TableRow>
                    )}
                </TableBody>
            </Table>
            {meta.last_page > 1 && (
                <p className="mt-3 text-center text-xs text-slate-500 tabular-nums">Page {meta.current_page} of {meta.last_page} · {meta.total} records</p>
            )}
        </AppLayout>
    );
}
