import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import { Plus, RotateCcw } from 'lucide-react';
import AppLayout from '@/layouts/app-layout';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { EmptyState, PageHeader, TableHeadRow, TableShell, tdCls, thCls } from '@/components/admin';

interface Ret { id: number; return_no: string; sale?: string | null; status: string; total: number; created_at?: string }

const labelCls = 'text-[11px] font-semibold tracking-wider text-slate-500 uppercase';

export default function ReturnsIndex({ returns, sales }: { returns: { data: Ret[] }; sales: { id: number; invoice_no: string }[] }) {
    const [open, setOpen] = useState(false);
    const [saleId, setSaleId] = useState<number>(sales[0]?.id ?? 0);
    const [warehouseId, setWarehouseId] = useState('');
    const [items, setItems] = useState<{ sale_item_id: string; quantity: string }[]>([{ sale_item_id: '', quantity: '1' }]);

    function submit() {
        router.post('/sales-returns', {
            sale_id: saleId,
            warehouse_id: Number(warehouseId),
            items: items.map((i) => ({ sale_item_id: Number(i.sale_item_id), quantity: Number(i.quantity) })),
        }, { onSuccess: () => setOpen(false) });
    }

    return (
        <AppLayout title="Sales Returns">
            <Head title="Sales Returns" />
            <PageHeader
                title="Sales Returns"
                count={returns.data.length}
                description="Refunds and restocks linked to original sales."
                actions={<Button onClick={() => setOpen(true)} className="h-10 rounded-xl bg-blue-600 font-semibold hover:bg-blue-700"><Plus className="size-4" /> New return</Button>}
            />
            <TableShell>
                <TableHeadRow>
                    <th className={thCls}>Return No</th>
                    <th className={thCls}>Sale</th>
                    <th className={`${thCls} text-right`}>Total</th>
                    <th className={thCls}>Status</th>
                    <th className={thCls}>Date</th>
                </TableHeadRow>
                <tbody>
                    {returns.data.map((r) => (
                        <tr key={r.id} className="border-t border-slate-200 hover:bg-slate-50">
                            <td className={`${tdCls} font-mono font-semibold`}>{r.return_no}</td>
                            <td className={`${tdCls} font-mono text-xs text-slate-500`}>{r.sale ?? '—'}</td>
                            <td className={`${tdCls} text-right font-bold tabular-nums`}>${Number(r.total).toFixed(2)}</td>
                            <td className={tdCls}>
                                {r.status === 'completed' || r.status === 'approved'
                                    ? <Badge className="border-transparent bg-emerald-100 text-emerald-700 hover:bg-emerald-100">{r.status}</Badge>
                                    : <Badge className="border-transparent bg-amber-100 text-amber-800 hover:bg-amber-100">{r.status}</Badge>}
                            </td>
                            <td className={`${tdCls} text-slate-500`}>{r.created_at}</td>
                        </tr>
                    ))}
                    {returns.data.length === 0 && (
                        <tr><td colSpan={5} className="p-0"><EmptyState icon={<RotateCcw className="size-5" />} title="No returns found" hint="Returns restock the chosen warehouse automatically." /></td></tr>
                    )}
                </tbody>
            </TableShell>
            <Dialog open={open} onOpenChange={setOpen}>
                <DialogContent>
                    <DialogHeader><DialogTitle>New sales return</DialogTitle></DialogHeader>
                    <div>
                        <Label className={labelCls}>Sale</Label>
                        <select value={saleId} onChange={(e) => setSaleId(Number(e.target.value))} className="mt-1 h-10 w-full rounded-xl border border-slate-200 bg-white px-2 text-sm">
                            {sales.map((s) => <option key={s.id} value={s.id}>{s.invoice_no}</option>)}
                        </select>
                    </div>
                    <div>
                        <Label className={labelCls}>Warehouse ID (restock target)</Label>
                        <Input value={warehouseId} onChange={(e) => setWarehouseId(e.target.value)} placeholder="e.g. 1" inputMode="numeric" className="mt-1 h-10 rounded-xl" />
                    </div>
                    {items.map((row, idx) => (
                        <div key={idx} className="flex gap-2">
                            <Input value={row.sale_item_id} onChange={(e) => setItems((p) => p.map((r, i) => (i === idx ? { ...r, sale_item_id: e.target.value } : r)))} placeholder="Sale item ID" className="h-10 flex-1 rounded-xl" />
                            <Input value={row.quantity} onChange={(e) => setItems((p) => p.map((r, i) => (i === idx ? { ...r, quantity: e.target.value } : r)))} placeholder="Qty" className="h-10 w-24 rounded-xl tabular-nums" />
                        </div>
                    ))}
                    <Button variant="outline" onClick={() => setItems((p) => [...p, { sale_item_id: '', quantity: '1' }])} className="rounded-xl">+ Add line</Button>
                    <p className="text-xs text-slate-500">Find sale item IDs on the sale receipt page. Returns restock the chosen warehouse automatically.</p>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setOpen(false)} className="rounded-xl">Cancel</Button>
                        <Button onClick={submit} className="rounded-xl bg-blue-600 hover:bg-blue-700">Submit return</Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
