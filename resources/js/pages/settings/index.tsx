import { Head, useForm } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { PageHeader } from '@/components/admin';

interface SettingRow {
    id: number;
    branch_id: number | null;
    key: string;
    value: string | null;
}

const EDITABLE_KEYS = ['shop_name', 'receipt_footer', 'tax_rate', 'low_stock_threshold'];

const LABELS: Record<string, string> = {
    shop_name: 'Shop name',
    receipt_footer: 'Receipt footer',
    tax_rate: 'Tax rate',
    low_stock_threshold: 'Low stock threshold',
};

export default function SettingsIndex({
    settings,
    branches,
    defaults,
}: {
    settings: SettingRow[];
    branches: { id: number; name: string }[];
    defaults: Record<string, string>;
}) {
    const lookup = (key: string) => settings.find((s) => s.branch_id === null && s.key === key)?.value ?? defaults[key] ?? '';
    const { data, setData, post, processing } = useForm({
        settings: EDITABLE_KEYS.map((key) => ({ key, value: lookup(key), branch_id: null as number | null })),
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/settings');
    };

    const overrides = settings.filter((s) => s.branch_id !== null);

    return (
        <AppLayout title="Settings">
            <Head title="Settings" />
            <PageHeader title="Settings" description="Global shop settings applied to every branch." />
            <Card className="max-w-2xl rounded-2xl border-slate-200 shadow-sm">
                <CardHeader><CardTitle className="text-sm font-semibold">General</CardTitle></CardHeader>
                <CardContent>
                    <form onSubmit={submit} className="space-y-3">
                        {data.settings.map((row, i) => (
                            <div key={row.key}>
                                <Label htmlFor={`s-${row.key}`}>{LABELS[row.key] ?? row.key}</Label>
                                <Input
                                    id={`s-${row.key}`}
                                    value={row.value ?? ''}
                                    onChange={(e) => setData('settings', data.settings.map((r, j) => (j === i ? { ...r, value: e.target.value } : r)))}
                                    className="mt-1 h-10 rounded-xl"
                                />
                            </div>
                        ))}
                        <Button disabled={processing} className="h-10 rounded-xl bg-blue-600 font-semibold hover:bg-blue-700">
                            Save settings
                        </Button>
                    </form>
                </CardContent>
            </Card>
            {branches.length > 0 && (
                <Card className="mt-4 max-w-2xl rounded-2xl border-slate-200 shadow-sm">
                    <CardHeader><CardTitle className="text-sm font-semibold">Branch overrides</CardTitle></CardHeader>
                    <CardContent className="text-sm">
                        {overrides.length === 0 && <p className="py-4 text-center text-slate-500">No branch-specific settings.</p>}
                        {overrides.map((s) => (
                            <div key={s.id} className="flex items-center justify-between gap-3 border-t border-slate-200 py-2.5 first:border-0 first:pt-0">
                                <span className="font-semibold">{s.key} <span className="font-normal text-slate-500">({branches.find((b) => b.id === s.branch_id)?.name})</span></span>
                                <span className="text-slate-500">{s.value}</span>
                            </div>
                        ))}
                    </CardContent>
                </Card>
            )}
        </AppLayout>
    );
}
