import { Head, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import AuthenticatedSessionController from '@/actions/App/Http/Controllers/Auth/AuthenticatedSessionController';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';

export default function Login() {
    const { data, setData, post, processing, errors } = useForm({
        username: '',
        password: '',
        remember: false,
    });

    function submit(e: FormEvent) {
        e.preventDefault();
        post(AuthenticatedSessionController.store.url());
    }

    return (
        <div className="relative flex min-h-screen items-center justify-center overflow-hidden bg-[#0A1633] px-4 py-10">
            <div
                className="pointer-events-none absolute inset-0 opacity-100"
                style={{ backgroundImage: 'radial-gradient(rgba(255,255,255,0.12) 1px, transparent 1px)', backgroundSize: '22px 22px' }}
            />
            <div className="pointer-events-none absolute -top-32 left-1/2 h-72 w-[36rem] -translate-x-1/2 rounded-full bg-blue-600/30 blur-3xl" />
            <Head title="Login" />
            <div className="relative w-full max-w-sm">
                <div className="mb-6 flex items-center justify-center gap-3">
                    <span className="flex size-13 items-center justify-center rounded-2xl bg-blue-600 p-3 text-2xl font-black text-white shadow-xl shadow-blue-950/40 ring-1 ring-white/20">$</span>
                    <div className="leading-tight">
                        <p className="text-xl font-bold tracking-tight text-white">SquarePOS</p>
                        <p className="text-[11px] font-semibold tracking-widest text-blue-300 uppercase">Back Office</p>
                    </div>
                </div>
                <Card className="rounded-2xl border border-slate-200 bg-white shadow-2xl">
                    <CardHeader className="pb-2">
                        <CardTitle className="text-xl font-bold tracking-tight">Welcome back</CardTitle>
                        <CardDescription>Sign in to your POS account</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={submit} className="space-y-4">
                            <div className="space-y-1.5">
                                <Label htmlFor="username">Username</Label>
                                <Input
                                    id="username"
                                    value={data.username}
                                    onChange={(e) => setData('username', e.target.value)}
                                    autoFocus
                                    autoComplete="username"
                                    placeholder="e.g. cashier01"
                                    className={cn('h-11 rounded-xl focus-visible:ring-2 focus-visible:ring-blue-600', errors.username && 'border-red-500 focus-visible:ring-red-500')}
                                />
                                {errors.username && <p className="text-xs font-medium text-red-600">{errors.username}</p>}
                            </div>
                            <div className="space-y-1.5">
                                <Label htmlFor="password">Password</Label>
                                <Input
                                    id="password"
                                    type="password"
                                    value={data.password}
                                    onChange={(e) => setData('password', e.target.value)}
                                    autoComplete="current-password"
                                    placeholder="••••••••"
                                    className={cn('h-11 rounded-xl focus-visible:ring-2 focus-visible:ring-blue-600', errors.password && 'border-red-500 focus-visible:ring-red-500')}
                                />
                                {errors.password && <p className="text-xs font-medium text-red-600">{errors.password}</p>}
                            </div>
                            <label className="flex items-center gap-2 text-sm text-slate-500">
                                <input
                                    type="checkbox"
                                    checked={data.remember}
                                    onChange={(e) => setData('remember', e.target.checked)}
                                    className="size-4 rounded accent-blue-600"
                                />
                                Remember me
                            </label>
                            <Button type="submit" disabled={processing} className="h-11 w-full rounded-xl bg-blue-600 text-sm font-bold hover:bg-blue-700">
                                {processing ? 'Signing in…' : 'Log in'}
                            </Button>
                        </form>
                    </CardContent>
                </Card>
                <p className="mt-4 text-center text-xs text-blue-200/80">Secure sign-in · Role-based access</p>
            </div>
        </div>
    );
}
