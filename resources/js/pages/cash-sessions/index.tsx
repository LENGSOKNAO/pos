import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import { Plus, Wallet } from 'lucide-react';
import AppLayout from '@/layouts/app-layout';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { EmptyState, PageHeader } from '@/components/admin';

interface Session {
    id: number;
    register: string;
    opening_balance: number;
    closing_balance: number | null;
    expected: number;
    status: string;
    opened_at?: string | null;
    closed_at?: string | null;
}

interface Props {
    sessions: { data: Session[]; total: number };
    registers: { id: number; name: string; code: string }[];
}

const labelCls = 'text-[11px] font-semibold tracking-wider text-slate-500 uppercase';

export default function CashSessionsIndex({ sessions, registers }: Props) {
    const [openDlg, setOpenDlg] = useState(false);
    const [registerId, setRegisterId] = useState(registers[0]?.id ?? 0);
    const [opening, setOpening] = useState('0');
    const [closeId, setCloseId] = useState<number | null>(null);
    const [closing, setClosing] = useState('0');

    return (
        <AppLayout title="Cash Sessions">
            <Head title="Cash Sessions" />
            <PageHeader
                title="Cash Sessions"
                count={sessions.total}
                description="Open and close cash drawers per register."
                actions={<Button className="h-10 rounded-xl bg-blue-600 font-semibold hover:bg-blue-700" onClick={() => setOpenDlg(true)}><Plus className="size-4" /> Open Session</Button>}
            />
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>#</TableHead>
                        <TableHead>Register</TableHead>
                        <TableHead className="text-right">Opening</TableHead>
                        <TableHead className="text-right">Expected</TableHead>
                        <TableHead className="text-right">Closing</TableHead>
                        <TableHead>Status</TableHead>
                        <TableHead><span className="sr-only">Actions</span></TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    {sessions.data.map((s) => (
                        <TableRow key={s.id}>
                            <TableCell className="font-mono text-xs">#{s.id}</TableCell>
                            <TableCell className="font-semibold">{s.register}</TableCell>
                            <TableCell className="text-right tabular-nums">${Number(s.opening_balance).toFixed(2)}</TableCell>
                            <TableCell className="text-right tabular-nums">${Number(s.expected).toFixed(2)}</TableCell>
                            <TableCell className="text-right tabular-nums">{s.closing_balance === null ? <span className="text-slate-400">—</span> : `$${Number(s.closing_balance).toFixed(2)}`}</TableCell>
                            <TableCell>
                                {s.status === 'open'
                                    ? <Badge className="border-transparent bg-emerald-100 text-emerald-700 hover:bg-emerald-100">{s.status}</Badge>
                                    : <Badge className="border-transparent bg-slate-100 text-slate-600 hover:bg-slate-100">{s.status}</Badge>}
                            </TableCell>
                            <TableCell className="text-right">
                                {s.status === 'open' && (
                                    <Button size="sm" variant="outline" className="h-9 rounded-xl" onClick={() => { setCloseId(s.id); setClosing(String(s.expected)); }}>
                                        Close
                                    </Button>
                                )}
                            </TableCell>
                        </TableRow>
                    ))}
                    {sessions.data.length === 0 && (
                        <TableRow>
                            <TableCell colSpan={7} className="p-0">
                                <EmptyState icon={<Wallet className="size-5" />} title="No sessions yet" hint="Open the first cash session for today." />
                            </TableCell>
                        </TableRow>
                    )}
                </TableBody>
            </Table>

            <Dialog open={openDlg} onOpenChange={setOpenDlg}>
                <DialogContent>
                    <DialogHeader><DialogTitle>Open cash session</DialogTitle></DialogHeader>
                    <div>
                        <Label className={labelCls}>Register</Label>
                        <select value={registerId} onChange={(e) => setRegisterId(Number(e.target.value))} className="mt-1 h-10 w-full rounded-xl border border-slate-200 bg-white px-2 text-sm">
                            {registers.map((r) => (
                                <option key={r.id} value={r.id}>{r.name} ({r.code})</option>
                            ))}
                        </select>
                    </div>
                    <div>
                        <Label className={labelCls}>Opening cash</Label>
                        <Input value={opening} onChange={(e) => setOpening(e.target.value)} inputMode="decimal" className="mt-1 h-10 rounded-xl tabular-nums" />
                    </div>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setOpenDlg(false)} className="rounded-xl">Cancel</Button>
                        <Button className="rounded-xl bg-blue-600 hover:bg-blue-700" onClick={() => { router.post('/cash-sessions/open', { cash_register_id: registerId, opening_balance: Number(opening) }, { onSuccess: () => setOpenDlg(false) }); }}>
                            Open
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <Dialog open={closeId !== null} onOpenChange={(v) => !v && setCloseId(null)}>
                <DialogContent>
                    <DialogHeader><DialogTitle>Close session #{closeId}</DialogTitle></DialogHeader>
                    <div>
                        <Label className={labelCls}>Closing cash (counted)</Label>
                        <Input value={closing} onChange={(e) => setClosing(e.target.value)} inputMode="decimal" className="mt-1 h-10 rounded-xl tabular-nums" />
                    </div>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setCloseId(null)} className="rounded-xl">Cancel</Button>
                        <Button className="rounded-xl bg-blue-600 hover:bg-blue-700" onClick={() => { if (closeId) router.post(`/cash-sessions/${closeId}/close`, { closing_balance: Number(closing) }, { onSuccess: () => setCloseId(null) }); }}>
                            Close session
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
