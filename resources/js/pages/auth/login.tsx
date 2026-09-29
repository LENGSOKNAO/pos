import { Head, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import AuthenticatedSessionController from '@/actions/App/Http/Controllers/Auth/AuthenticatedSessionController';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { ShoppingCart, ReceiptText, BarChart3 } from 'lucide-react';
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
        <div className="flex min-h-screen bg-white">
            <Head title="Login" />
            <div className="hidden w-1/2 flex-col justify-between bg-[#0B1B3A] p-10 lg:flex">
                <div className="flex items-center gap-2.5">
                    <span className="flex size-9 items-center justify-center rounded-lg bg-blue-600 text-base font-black text-white">S</span>
                    <span className="text-lg font-bold tracking-tight text-white">SquarePOS</span>
                </div>
                <div>
                    <h2 className="max-w-md text-3xl font-bold tracking-tight text-white">
                        Point of sale, minus the headache.
                    </h2>
                    <p className="mt-3 max-w-md text-sm leading-relaxed text-slate-300">
                        Ring up sales, track stock, and see how the day went — all from one back office.
                    </p>
                    <ul className="mt-8 space-y-4">
                        {[
                            { icon: ShoppingCart, text: 'Fast checkout terminal for cashiers' },
                            { icon: ReceiptText, text: 'Sales, returns, and receipts in one place' },
                            { icon: BarChart3, text: 'Daily reports on products and cash' },
                        ].map((f) => (
                            <li key={f.text} className="flex items-center gap-3 text-sm text-slate-200">
                                <span className="flex size-9 items-center justify-center rounded-lg bg-white/10">
                                    <f.icon className="size-4" />
                                </span>
                                {f.text}
                            </li>
                        ))}
                    </ul>
                </div>
                <p className="text-xs text-slate-400">Secure sign-in · Role-based access</p>
            </div>

            <div className="flex flex-1 items-center justify-center bg-slate-50 px-4 py-10">
                <div className="w-full max-w-sm">
                    <div className="mb-6 flex items-center gap-2.5 lg:hidden">
                        <span className="flex size-9 items-center justify-center rounded-xl bg-blue-600 text-base font-black text-white">S</span>
                        <span className="text-lg font-bold tracking-tight text-slate-900">SquarePOS</span>
                    </div>
                    <Card className="rounded-2xl border border-slate-200 bg-white shadow-sm">
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
                                        placeholder="Enter your password"
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
                </div>
            </div>
        </div>
    );
}
