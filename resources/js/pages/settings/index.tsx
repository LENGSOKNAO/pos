import { Head } from '@inertiajs/react';
import { useState } from 'react';
import { Link, router, useForm } from '@inertiajs/react';
import { Save, Building, ShoppingCart, ReceiptText, Wallet, CreditCard, Bell, Globe, Shield, Palette } from 'lucide-react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select } from '@/components/ui/select';
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card';
import { Tabs, TabsList, TabsTrigger, TabsContent } from '@/components/ui/tabs';
import { FormField, FormSection, FormActions } from '@/components/ui/form';
import { useAuth } from '@/hooks/useAuth';

interface SettingsFormData {
  company: { name: string; legal_name: string; phone: string; email: string; address: string; tax_number: string; currency: string; timezone: string };
  pos: { auto_print_receipt: boolean; show_customer_display: boolean; allow_hold_orders: boolean; allow_price_override: boolean; default_payment_method: string };
  invoice: { prefix: string; show_tax_breakdown: boolean; footer_text: string };
  tax: { default_rate: number; tax_inclusive: boolean };
  payment: { allow_split_payments: boolean; allow_partial_payments: boolean };
  notifications: { low_stock_threshold: number; expiry_alert_days: number };
}

export default function SettingsIndex({ settings }: { settings?: any }) {
  const safeSettings: any = settings ?? {};
  const { hasPermission } = useAuth();
  const [activeTab, setActiveTab] = useState('company');

  const form = useForm<SettingsFormData>({
    company: {
      name: safeSettings.company?.name || '',
      legal_name: safeSettings.company?.legal_name || '',
      phone: safeSettings.company?.phone || '',
      email: safeSettings.company?.email || '',
      address: safeSettings.company?.address || '',
      tax_number: safeSettings.company?.tax_number || '',
      currency: safeSettings.company?.currency || 'USD',
      timezone: safeSettings.company?.timezone || 'UTC',
    },
    pos: {
      auto_print_receipt: safeSettings.pos?.auto_print_receipt ?? true,
      show_customer_display: safeSettings.pos?.show_customer_display ?? false,
      allow_hold_orders: safeSettings.pos?.allow_hold_orders ?? true,
      allow_price_override: safeSettings.pos?.allow_price_override ?? false,
      default_payment_method: safeSettings.pos?.default_payment_method || 'cash',
    },
    invoice: {
      prefix: safeSettings.invoice?.prefix || 'INV-',
      show_tax_breakdown: safeSettings.invoice?.show_tax_breakdown ?? true,
      footer_text: safeSettings.invoice?.footer_text || 'Thank you for your business!',
    },
    tax: {
      default_rate: safeSettings.tax?.default_rate || 0,
      tax_inclusive: safeSettings.tax?.tax_inclusive ?? false,
    },
    payment: {
      allow_split_payments: safeSettings.payment?.allow_split_payments ?? true,
      allow_partial_payments: safeSettings.payment?.allow_partial_payments ?? true,
    },
    notifications: {
      low_stock_threshold: safeSettings.notifications?.low_stock_threshold || 10,
      expiry_alert_days: safeSettings.notifications?.expiry_alert_days || 30,
    },
  });

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    router.put('/api/v1/settings', form.data, {
      onSuccess: () => alert('Settings saved successfully!'),
    });
  };

  const tabs = [
    { id: 'company', label: 'Company', icon: Building },
    { id: 'pos', label: 'POS', icon: ShoppingCart },
    { id: 'invoice', label: 'Invoice', icon: ReceiptText },
    { id: 'tax', label: 'Tax', icon: Wallet },
    { id: 'payment', label: 'Payment', icon: CreditCard },
    { id: 'notifications', label: 'Notifications', icon: Bell },
  ];

  return (
    <AppLayout title="Settings">
      <Head title="Settings" />
      
      <div className="mb-5 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
          <h1 className="text-xl font-bold tracking-tight text-slate-900">Settings</h1>
          <p className="mt-1 text-sm text-slate-500">Configure your system settings</p>
        </div>
      </div>

      <form onSubmit={e => { e.preventDefault(); router.put('/api/v1/settings', form.data, { onSuccess: () => alert('Settings saved successfully!') }); }} className="space-y-6">
        <Tabs value={activeTab} onValueChange={setActiveTab} className="w-full">
          <TabsList className="grid w-full grid-cols-3 lg:grid-cols-6">
            {tabs.map(tab => (
              <TabsTrigger key={tab.id} value={tab.id}>
                <tab.icon className="size-4 mr-2" />
                {tab.label}
              </TabsTrigger>
            ))}
          </TabsList>

          {/* Company Settings */}
          <TabsContent value="company" forceMount>
            <Card>
              <CardHeader><CardTitle>Company Information</CardTitle></CardHeader>
              <CardContent className="space-y-4">
                <FormSection title="Basic Information">
                  <div className="grid gap-4 sm:grid-cols-2">
                    <FormField label="Company Name" required><Input value={form.data.company.name} onChange={e => form.setData('company.name', e.target.value)} /></FormField>
                    <FormField label="Legal Name"><Input value={form.data.company.legal_name} onChange={e => form.setData('company.legal_name', e.target.value)} /></FormField>
                    <FormField label="Phone"><Input value={form.data.company.phone} onChange={e => form.setData('company.phone', e.target.value)} type="tel" /></FormField>
                    <FormField label="Email"><Input value={form.data.company.email} onChange={e => form.setData('company.email', e.target.value)} type="email" /></FormField>
                    <FormField label="Address"><textarea value={form.data.company.address} onChange={e => form.setData('company.address', e.target.value)} rows={3} className="w-full rounded-md border border-slate-200 bg-white px-3 py-2 text-sm" /></FormField>
                    <FormField label="Tax Number"><Input value={form.data.company.tax_number} onChange={e => form.setData('company.tax_number', e.target.value)} /></FormField>
                    <FormField label="Currency"><Select value={form.data.company.currency} onChange={e => form.setData('company.currency', e.target.value)}><option value="USD">USD</option><option value="EUR">EUR</option><option value="GBP">GBP</option></Select></FormField>
                    <FormField label="Timezone"><Select value={form.data.company.timezone} onChange={e => form.setData('company.timezone', e.target.value)}><option value="UTC">UTC</option><option value="America/New_York">Eastern Time</option><option value="America/Chicago">Central Time</option><option value="America/Denver">Mountain Time</option><option value="America/Los_Angeles">Pacific Time</option></Select></FormField>
                  </div>
                </FormSection>
              </CardContent>
            </Card>
          </TabsContent>

          {/* POS Settings */}
          <TabsContent value="pos" forceMount>
            <Card>
              <CardHeader><CardTitle>POS Settings</CardTitle></CardHeader>
              <CardContent className="space-y-4">
                <FormSection title="POS Behavior">
                  <div className="grid gap-4 sm:grid-cols-2">
                    <FormField label="Auto Print Receipt"><label className="flex items-center gap-2 cursor-pointer"><input type="checkbox" checked={form.data.pos.auto_print_receipt} onChange={e => form.setData('pos.auto_print_receipt', e.target.checked)} className="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500" /><span className="text-sm">Automatically print receipt after sale</span></label></FormField>
                    <FormField label="Show Customer Display"><label className="flex items-center gap-2 cursor-pointer"><input type="checkbox" checked={form.data.pos.show_customer_display} onChange={e => form.setData('pos.show_customer_display', e.target.checked)} className="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500" /><span className="text-sm">Show customer-facing display</span></label></FormField>
                    <FormField label="Allow Hold Orders"><label className="flex items-center gap-2 cursor-pointer"><input type="checkbox" checked={form.data.pos.allow_hold_orders} onChange={e => form.setData('pos.allow_hold_orders', e.target.checked)} className="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500" /><span className="text-sm">Allow cashiers to hold orders</span></label></FormField>
                    <FormField label="Allow Price Override"><label className="flex items-center gap-2 cursor-pointer"><input type="checkbox" checked={form.data.pos.allow_price_override} onChange={e => form.setData('pos.allow_price_override', e.target.checked)} className="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500" /><span className="text-sm">Allow cashiers to override prices (requires permission)</span></label></FormField>
                    <FormField label="Default Payment Method"><Select value={form.data.pos.default_payment_method} onChange={e => form.setData('pos.default_payment_method', e.target.value)}><option value="cash">Cash</option><option value="card">Credit Card</option><option value="bank_transfer">Bank Transfer</option></Select></FormField>
                  </div>
                </FormSection>
              </CardContent>
            </Card>
          </TabsContent>

          {/* Invoice Settings */}
          <TabsContent value="invoice" forceMount>
            <Card>
              <CardHeader><CardTitle>Invoice Settings</CardTitle></CardHeader>
              <CardContent className="space-y-4">
                <FormSection title="Invoice Format">
                  <div className="grid gap-4 sm:grid-cols-2">
                    <FormField label="Invoice Prefix"><Input value={form.data.invoice.prefix} onChange={e => form.setData('invoice.prefix', e.target.value)} /></FormField>
                    <FormField label="Show Tax Breakdown"><label className="flex items-center gap-2 cursor-pointer"><input type="checkbox" checked={form.data.invoice.show_tax_breakdown} onChange={e => form.setData('invoice.show_tax_breakdown', e.target.checked)} className="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500" /><span className="text-sm">Show detailed tax breakdown on invoice</span></label></FormField>
                    <FormField label="Footer Text"><textarea value={form.data.invoice.footer_text} onChange={e => form.setData('invoice.footer_text', e.target.value)} rows={3} className="w-full rounded-md border border-slate-200 bg-white px-3 py-2 text-sm" /></FormField>
                  </div>
                </FormSection>
              </CardContent>
            </Card>
          </TabsContent>

          {/* Tax Settings */}
          <TabsContent value="tax" forceMount>
            <Card>
              <CardHeader><CardTitle>Tax Settings</CardTitle></CardHeader>
              <CardContent className="space-y-4">
                <FormSection title="Tax Configuration">
                  <div className="grid gap-4 sm:grid-cols-2">
                    <FormField label="Default Tax Rate (%)"><Input type="number" step="0.01" value={form.data.tax.default_rate} onChange={e => form.setData('tax.default_rate', parseFloat(e.target.value) || 0)} /></FormField>
                    <FormField label="Tax Inclusive Pricing"><label className="flex items-center gap-2 cursor-pointer"><input type="checkbox" checked={form.data.tax.tax_inclusive} onChange={e => form.setData('tax.tax_inclusive', e.target.checked)} className="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500" /><span className="text-sm">Prices include tax by default</span></label></FormField>
                  </div>
                </FormSection>
              </CardContent>
            </Card>
          </TabsContent>

          {/* Payment Settings */}
          <TabsContent value="payment" forceMount>
            <Card>
              <CardHeader><CardTitle>Payment Settings</CardTitle></CardHeader>
              <CardContent className="space-y-4">
                <FormSection title="Payment Options">
                  <div className="grid gap-4 sm:grid-cols-2">
                    <FormField label="Allow Split Payments"><label className="flex items-center gap-2 cursor-pointer"><input type="checkbox" checked={form.data.payment.allow_split_payments} onChange={e => form.setData('payment.allow_split_payments', e.target.checked)} className="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500" /><span className="text-sm">Allow multiple payment methods per sale</span></label></FormField>
                    <FormField label="Allow Partial Payments"><label className="flex items-center gap-2 cursor-pointer"><input type="checkbox" checked={form.data.payment.allow_partial_payments} onChange={e => form.setData('payment.allow_partial_payments', e.target.checked)} className="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500" /><span className="text-sm">Allow customers to pay partial amounts</span></label></FormField>
                  </div>
                </FormSection>
              </CardContent>
            </Card>
          </TabsContent>

          {/* Notification Settings */}
          <TabsContent value="notifications" forceMount>
            <Card>
              <CardHeader><CardTitle>Notification Settings</CardTitle></CardHeader>
              <CardContent className="space-y-4">
                <FormSection title="Alert Thresholds">
                  <div className="grid gap-4 sm:grid-cols-2">
                    <FormField label="Low Stock Alert Threshold"><Input type="number" value={form.data.notifications.low_stock_threshold} onChange={e => form.setData('notifications.low_stock_threshold', parseInt(e.target.value) || 0)} /></FormField>
                    <FormField label="Expiry Alert Days"><Input type="number" value={form.data.notifications.expiry_alert_days} onChange={e => form.setData('notifications.expiry_alert_days', parseInt(e.target.value) || 0)} /></FormField>
                  </div>
                </FormSection>
              </CardContent>
            </Card>
          </TabsContent>
        </Tabs>

        <FormActions>
          <Button type="submit" className="btn btn-primary" disabled={form.processing}>
            {form.processing ? 'Saving...' : 'Save Settings'}
          </Button>
        </FormActions>
      </form>
    </AppLayout>
  );
}