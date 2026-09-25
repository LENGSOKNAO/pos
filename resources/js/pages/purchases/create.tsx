import { Head, router } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import { Trash2 } from 'lucide-react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { PageHeader } from '@/components/admin';

interface UnitRef {
    id: number;
}
interface Product {
    id: number;
    name: string;
    sku: string;
    units: UnitRef[];
}
interface Props {
    suppliers: { id: number; name: string }[];
    warehouses: { id: number; name: string }[];
    products: Product[];
}

interface Row {
    product_unit_id: number;
    quantity: string;
    unit_cost: string;
}

const selectCls = 'mt-1 h-10 w-full rounded-xl border border-slate-200 bg-white px-2 text-sm font-medium outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100';
const labelCls = 'text-[11px] font-semibold tracking-wider text-slate-500 uppercase';

export default function PurchaseCreate({ suppliers, warehouses, products }: Props) {
    const [supplierId, setSupplierId] = useState<number>(suppliers[0]?.id ?? 0);
    const [warehouseId, setWarehouseId] = useState<number>(warehouses[0]?.id ?? 0);
    const [rows, setRows] = useState<Row[]>([{ product_unit_id: 0, quantity: '1', unit_cost: '0' }]);
    const [processing, setProcessing] = useState(false);

    const unitOptions = useMemo(() => {
        const opts: { id: number; label: string }[] = [];
        products.forEach((p) =>
            p.units.forEach((u) => opts.push({ id: u.id, label: `${p.name} (${p.sku}) #${u.id}` })),
        );
        return opts;
    }, [products]);

    const total = rows.reduce((s, r) => s + (Number(r.quantity) || 0) * (Number(r.unit_cost) || 0), 0);

    function setRow(i: number, patch: Partial<Row>) {
        setRows((prev) => prev.map((r, idx) => (idx === i ? { ...r, ...patch } : r)));
    }

    function submit() {
        setProcessing(true);
        router.post(
            '/purchases',
            {
                supplier_id: supplierId,
                warehouse_id: warehouseId,
                items: rows.map((r) => ({
                    product_unit_id: Number(r.product_unit_id),
                    quantity: Number(r.quantity),
                    unit_cost: Number(r.unit_cost),
                })),
            },
            { onFinish: () => setProcessing(false) },
        );
    }

    return (
        <AppLayout title="New Purchase">
            <Head title="New Purchase" />
            <PageHeader title="New Purchase" description="Receive stock from a supplier into a warehouse." />
            <Card className="space-y-4 rounded-2xl border-slate-200 p-5 shadow-sm sm:p-6">
                <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <label className={labelCls}>Supplier</label>
                        <select value={supplierId} onChange={(e) => setSupplierId(Number(e.target.value))} className={selectCls}>
                            {suppliers.map((s) => (
                                <option key={s.id} value={s.id}>{s.name}</option>
                            ))}
                        </select>
                    </div>
                    <div>
                        <label className={labelCls}>Warehouse</label>
                        <select value={warehouseId} onChange={(e) => setWarehouseId(Number(e.target.value))} className={selectCls}>
                            {warehouses.map((w) => (
                                <option key={w.id} value={w.id}>{w.name}</option>
                            ))}
                        </select>
                    </div>
                </div>
                <div>
                    <p className="mb-2 text-sm font-semibold text-slate-900">Line items</p>
                    <div className="space-y-2">
                        {rows.map((r, i) => (
                            <div key={i} className="grid grid-cols-1 gap-2 rounded-xl border border-slate-200 bg-slate-50/50 p-3 md:grid-cols-[1fr_120px_140px_auto]">
                                <select value={r.product_unit_id} onChange={(e) => setRow(i, { product_unit_id: Number(e.target.value) })} className="h-10 rounded-xl border border-slate-200 bg-white px-2 text-sm">
                                    <option value={0}>Select product unit…</option>
                                    {unitOptions.map((o) => (
                                        <option key={o.id} value={o.id}>{o.label}</option>
                                    ))}
                                </select>
                                <Input value={r.quantity} onChange={(e) => setRow(i, { quantity: e.target.value })} inputMode="decimal" placeholder="Qty" className="h-10 rounded-xl tabular-nums" />
                                <Input value={r.unit_cost} onChange={(e) => setRow(i, { unit_cost: e.target.value })} inputMode="decimal" placeholder="Unit cost" className="h-10 rounded-xl tabular-nums" />
                                <Button variant="outline" onClick={() => setRows((prev) => prev.filter((_, idx) => idx !== i))} disabled={rows.length === 1} className="h-10 rounded-xl">
                                    <Trash2 className="size-4" />
                                </Button>
                            </div>
                        ))}
                    </div>
                </div>
                <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <Button variant="outline" onClick={() => setRows((prev) => [...prev, { product_unit_id: 0, quantity: '1', unit_cost: '0' }])} className="h-10 rounded-xl border-slate-200">
                        + Add row
                    </Button>
                    <p className="text-lg font-extrabold tabular-nums">Total: ${total.toFixed(2)}</p>
                </div>
                <Button onClick={submit} disabled={processing || !supplierId || !warehouseId} className="h-12 w-full rounded-xl bg-blue-600 text-base font-bold hover:bg-blue-700">
                    {processing ? 'Saving…' : `Save Purchase — $${total.toFixed(2)}`}
                </Button>
            </Card>
        </AppLayout>
    );
}
