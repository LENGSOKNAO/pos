import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import { Plus, Truck } from 'lucide-react';
import AppLayout from '@/layouts/app-layout';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { EmptyState, PageHeader, SearchInput, TableHeadRow, TableShell, tdCls, thCls } from '@/components/admin';

interface Supplier { id: number; name: string; code: string; phone?: string | null; balance: number; status: string }

export default function SuppliersIndex({ suppliers, filters }: { suppliers: { data: Supplier[] }; filters: { search: string } }) {
    const [search, setSearch] = useState(filters.search ?? '');
    const [open, setOpen] = useState(false);
    const [name, setName] = useState('');
    const [phone, setPhone] = useState('');

    return (
        <AppLayout title="Suppliers">
            <Head title="Suppliers" />
            <PageHeader
                title="Suppliers"
                count={suppliers.data.length}
                description="Vendor accounts and outstanding balances."
                actions={
                    <>
                        <SearchInput value={search} onChange={setSearch} onSubmit={() => router.get('/suppliers', { search }, { preserveState: true })} placeholder="Search name or phone…" />
                        <Button onClick={() => setOpen(true)} className="h-10 rounded-xl bg-blue-600 font-semibold hover:bg-blue-700"><Plus className="size-4" /> Quick add</Button>
                    </>
                }
            />
            <TableShell>
                <TableHeadRow>
                    <th className={thCls}>Name</th>
                    <th className={thCls}>Code</th>
                    <th className={thCls}>Phone</th>
                    <th className={`${thCls} text-right`}>Balance</th>
                    <th className={thCls}>Status</th>
                </TableHeadRow>
                <tbody>
                    {suppliers.data.map((s) => (
                        <tr key={s.id} className="border-t border-slate-200 hover:bg-slate-50">
                            <td className={`${tdCls} font-semibold text-slate-900`}>{s.name}</td>
                            <td className={`${tdCls} font-mono text-xs text-slate-500`}>{s.code}</td>
                            <td className={`${tdCls} text-slate-500`}>{s.phone ?? '—'}</td>
                            <td className={`${tdCls} text-right font-bold tabular-nums ${Number(s.balance) > 0 ? 'text-red-600' : 'text-slate-900'}`}>${Number(s.balance).toFixed(2)}</td>
                            <td className={tdCls}>
                                {s.status === 'active'
                                    ? <Badge className="border-transparent bg-emerald-100 text-emerald-700 hover:bg-emerald-100">{s.status}</Badge>
                                    : <Badge className="border-transparent bg-slate-100 text-slate-600 hover:bg-slate-100">{s.status}</Badge>}
                            </td>
                        </tr>
                    ))}
                    {suppliers.data.length === 0 && (
                        <tr><td colSpan={5} className="p-0"><EmptyState icon={<Truck className="size-5" />} title="No suppliers found" hint="Add your first supplier with Quick add." /></td></tr>
                    )}
                </tbody>
            </TableShell>
            <Dialog open={open} onOpenChange={setOpen}>
                <DialogContent>
                    <DialogHeader><DialogTitle>Quick-add supplier</DialogTitle></DialogHeader>
                    <Input value={name} onChange={(e) => setName(e.target.value)} placeholder="Name" className="h-10 rounded-xl" />
                    <Input value={phone} onChange={(e) => setPhone(e.target.value)} placeholder="Phone" className="h-10 rounded-xl" />
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setOpen(false)} className="rounded-xl">Cancel</Button>
                        <Button onClick={() => router.post('/suppliers', { name, phone }, { onSuccess: () => { setOpen(false); setName(''); setPhone(''); } })} className="rounded-xl bg-blue-600 hover:bg-blue-700">Save</Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
