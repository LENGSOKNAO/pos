import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import { Plus, Users } from 'lucide-react';
import AppLayout from '@/layouts/app-layout';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { EmptyState, PageHeader, SearchInput, TableHeadRow, TableShell, tdCls, thCls } from '@/components/admin';

interface Customer { id: number; name: string; code: string; phone?: string | null; group?: string | null; balance: number; status: string }

export default function CustomersIndex({ customers, groups, filters }: { customers: { data: Customer[] }; groups: { id: number; name: string }[]; filters: { search: string } }) {
    const [search, setSearch] = useState(filters.search ?? '');
    const [open, setOpen] = useState(false);
    const [name, setName] = useState('');
    const [phone, setPhone] = useState('');

    void groups;

    return (
        <AppLayout title="Customers">
            <Head title="Customers" />
            <PageHeader
                title="Customers"
                count={customers.data.length}
                description="Store credit balances and contact details."
                actions={
                    <>
                        <SearchInput value={search} onChange={setSearch} onSubmit={() => router.get('/customers', { search }, { preserveState: true })} placeholder="Search name or phone…" />
                        <Button onClick={() => setOpen(true)} className="h-10 rounded-xl bg-blue-600 font-semibold hover:bg-blue-700"><Plus className="size-4" /> Quick add</Button>
                    </>
                }
            />
            <TableShell>
                <TableHeadRow>
                    <th className={thCls}>Name</th>
                    <th className={thCls}>Code</th>
                    <th className={thCls}>Phone</th>
                    <th className={thCls}>Group</th>
                    <th className={`${thCls} text-right`}>Balance</th>
                </TableHeadRow>
                <tbody>
                    {customers.data.map((c) => (
                        <tr key={c.id} className="border-t border-slate-200 hover:bg-slate-50">
                            <td className={`${tdCls} font-semibold text-slate-900`}>{c.name}</td>
                            <td className={`${tdCls} font-mono text-xs text-slate-500`}>{c.code}</td>
                            <td className={`${tdCls} text-slate-500`}>{c.phone ?? '—'}</td>
                            <td className={tdCls}>{c.group ? <Badge className="border-transparent bg-slate-100 text-slate-700 hover:bg-slate-100">{c.group}</Badge> : <span className="text-slate-400">—</span>}</td>
                            <td className={`${tdCls} text-right font-bold tabular-nums ${Number(c.balance) > 0 ? 'text-red-600' : 'text-slate-900'}`}>${Number(c.balance).toFixed(2)}</td>
                        </tr>
                    ))}
                    {customers.data.length === 0 && (
                        <tr><td colSpan={5} className="p-0"><EmptyState icon={<Users className="size-5" />} title="No customers found" hint="Add your first customer with Quick add." /></td></tr>
                    )}
                </tbody>
            </TableShell>
            <Dialog open={open} onOpenChange={setOpen}>
                <DialogContent>
                    <DialogHeader><DialogTitle>Quick-add customer</DialogTitle></DialogHeader>
                    <Input value={name} onChange={(e) => setName(e.target.value)} placeholder="Name" className="h-10 rounded-xl" />
                    <Input value={phone} onChange={(e) => setPhone(e.target.value)} placeholder="Phone" className="h-10 rounded-xl" />
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setOpen(false)} className="rounded-xl">Cancel</Button>
                        <Button onClick={() => router.post('/customers', { name, phone }, { onSuccess: () => { setOpen(false); setName(''); setPhone(''); } })} className="rounded-xl bg-blue-600 hover:bg-blue-700">Save</Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
