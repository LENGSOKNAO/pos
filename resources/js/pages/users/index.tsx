import { Head, useForm } from '@inertiajs/react';
import { useState } from 'react';
import { Plus, Users } from 'lucide-react';
import AppLayout from '@/layouts/app-layout';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { EmptyState, PageHeader } from '@/components/admin';

interface UserRow {
    id: number;
    name: string;
    username: string;
    email: string;
    status: string;
    roles: { id: number; name: string }[];
    branches: { id: number; name: string }[];
}

const fieldCls = 'mt-1 h-10 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm font-normal outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100';

export default function UsersIndex({ users, roles, branches }: { users: UserRow[]; roles: { id: number; name: string }[]; branches: { id: number; name: string }[] }) {
    const [open, setOpen] = useState(false);
    const { data, setData, post, processing, errors, reset } = useForm({ name: '', username: '', email: '', password: '', role_id: '', branch_id: '' });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/users', { onSuccess: () => { reset(); setOpen(false); } });
    };

    return (
        <AppLayout title="Users">
            <Head title="Users" />
            <PageHeader
                title="Users"
                count={users.length}
                description="Manage staff accounts, roles and branches."
                actions={<Button onClick={() => setOpen(true)} className="h-10 rounded-xl bg-blue-600 font-semibold hover:bg-blue-700"><Plus className="size-4" /> New user</Button>}
            />
            <Card className="overflow-hidden rounded-2xl border-slate-200 p-0 shadow-sm">
                <CardHeader className="border-b border-slate-200 px-5"><CardTitle className="text-sm font-semibold">All users ({users.length})</CardTitle></CardHeader>
                <CardContent className="p-0">
                    <table className="w-full text-sm">
                        <thead className="bg-slate-50"><tr className="text-left text-[11px] font-semibold tracking-wider text-slate-500 uppercase"><th className="h-11 px-4">Name</th><th className="h-11 px-4">Username</th><th className="h-11 px-4">Roles</th><th className="h-11 px-4">Branches</th><th className="h-11 px-4">Status</th></tr></thead>
                        <tbody>
                            {users.map((u) => (
                                <tr key={u.id} className="border-t border-slate-200 hover:bg-slate-50">
                                    <td className="h-11 px-4 font-semibold">{u.name}<div className="text-xs font-normal text-slate-500">{u.email}</div></td>
                                    <td className="h-11 px-4 font-mono text-xs text-slate-500">{u.username}</td>
                                    <td className="h-11 px-4 text-slate-500">{u.roles.map((r) => r.name).join(', ') || '—'}</td>
                                    <td className="h-11 px-4 text-slate-500">{u.branches.map((b) => b.name).join(', ') || '—'}</td>
                                    <td className="h-11 px-4">
                                        {u.status === 'active'
                                            ? <Badge className="border-transparent bg-emerald-100 text-emerald-700 hover:bg-emerald-100">{u.status}</Badge>
                                            : <Badge className="border-transparent bg-slate-100 text-slate-600 hover:bg-slate-100">{u.status}</Badge>}
                                    </td>
                                </tr>
                            ))}
                            {users.length === 0 && (
                                <tr><td colSpan={5} className="p-0"><EmptyState icon={<Users className="size-5" />} title="No users found" hint="Create the first staff account." /></td></tr>
                            )}
                        </tbody>
                    </table>
                </CardContent>
            </Card>
            <Dialog open={open} onOpenChange={setOpen}>
                <DialogContent>
                    <DialogHeader><DialogTitle>Create user</DialogTitle></DialogHeader>
                    <form onSubmit={submit} className="space-y-3">
                        {([['name', 'Full name', 'text'], ['username', 'Username', 'text'], ['email', 'Email', 'email'], ['password', 'Password', 'password']] as const).map(([k, label, type]) => (
                            <div key={k}>
                                <Label htmlFor={`u-${k}`}>{label}</Label>
                                <Input
                                    id={`u-${k}`}
                                    type={type}
                                    value={data[k]}
                                    onChange={(e) => setData(k, e.target.value)}
                                    className="mt-1 h-10 rounded-xl"
                                />
                                {(errors as Record<string, string | undefined>)[k] && <p className="mt-1 text-xs font-medium text-red-600">{(errors as Record<string, string | undefined>)[k]}</p>}
                            </div>
                        ))}
                        <div>
                            <Label htmlFor="u-role">Role</Label>
                            <select id="u-role" value={data.role_id} onChange={(e) => setData('role_id', e.target.value)} className={fieldCls}>
                                <option value="">Select role</option>
                                {roles.map((r) => <option key={r.id} value={r.id}>{r.name}</option>)}
                            </select>
                            {errors.role_id && <p className="mt-1 text-xs font-medium text-red-600">{errors.role_id}</p>}
                        </div>
                        <div>
                            <Label htmlFor="u-branch">Branch</Label>
                            <select id="u-branch" value={data.branch_id} onChange={(e) => setData('branch_id', e.target.value)} className={fieldCls}>
                                <option value="">None</option>
                                {branches.map((b) => <option key={b.id} value={b.id}>{b.name}</option>)}
                            </select>
                        </div>
                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setOpen(false)} className="rounded-xl">Cancel</Button>
                            <Button disabled={processing} className="rounded-xl bg-blue-600 hover:bg-blue-700">Create</Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
