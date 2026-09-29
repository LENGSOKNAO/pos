import { Head } from '@inertiajs/react';
import { useState } from 'react';
import { Link, router, useForm, usePage } from '@inertiajs/react';
import { Plus, Search, Filter, Edit, Trash2, Eye, User, CreditCard, DollarSign, RotateCcw } from 'lucide-react';
import AppLayout from '@/layouts/app-layout';
import { DataTable, Column } from '@/components/ui/data-table';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select } from '@/components/ui/select';
import { Badge } from '@/components/ui/badge';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter, DialogTrigger } from '@/components/ui/dialog';
import { FormField, FormSection, FormActions } from '@/components/ui/form';
import { api } from '@/services/api';
import { useAuth } from '@/hooks/useAuth';

interface CustomerFormData {
  company_id: string;
  customer_code: string;
  name: string;
  phone: string;
  email: string;
  address: string;
  customer_group_id: string;
  credit_limit: number;
  credit_days: number;
  status: string;
}

export default function CustomersIndex({
  customers,
  groups,
  companies,
}: {
  customers?: any;
  groups?: any[];
  companies?: any[];
}) {
  const safeCustomers = customers ?? { data: [], current_page: 1, last_page: 1, per_page: 15, total: 0 };
  const safeGroups: any[] = Array.isArray(groups) ? groups : [];
  const safeCompanies: any[] = Array.isArray(companies) ? companies : [];
  const { hasPermission, user } = useAuth();
  const [isCreateOpen, setIsCreateOpen] = useState(false);
  const [isEditOpen, setIsEditOpen] = useState(false);
  const [editingCustomer, setEditingCustomer] = useState<any>(null);
  const [search, setSearch] = useState('');
  const [groupFilter, setGroupFilter] = useState('');
  const [statusFilter, setStatusFilter] = useState('');

  const createForm = useForm<any>({
    company_id: user?.employee?.company_id || '',
    customer_code: '',
    name: '',
    phone: '',
    email: '',
    address: '',
    customer_group_id: '',
    credit_limit: 0,
    credit_days: 0,
    status: 'active',
  });

  const editForm = useForm<any>({
    company_id: '',
    customer_code: '',
    name: '',
    phone: '',
    email: '',
    address: '',
    customer_group_id: '',
    credit_limit: 0,
    credit_days: 0,
    status: 'active',
  });

  const handleCreate = (e: React.FormEvent) => {
    e.preventDefault();
    createForm.post('/api/v1/customers', {
      onSuccess: () => { setIsCreateOpen(false); createForm.reset(); },
    });
  };

  const handleEdit = (customer: any) => {
    editForm.reset({
      company_id: customer.company_id,
      customer_code: customer.customer_code,
      name: customer.name,
      phone: customer.phone,
      email: customer.email,
      address: customer.address,
      customer_group_id: customer.customer_group_id,
      credit_limit: customer.credit_limit,
      credit_days: customer.credit_days,
      status: customer.status,
    });
    setEditingCustomer(customer);
    setIsEditOpen(true);
  };

  const handleUpdate = (e: React.FormEvent) => {
    e.preventDefault();
    if (!editingCustomer) return;
    editForm.put(`/api/v1/customers/${editingCustomer.id}`, {
      onSuccess: () => { setIsEditOpen(false); setEditingCustomer(null); },
    });
  };

  const handleDelete = (customer: any) => {
    if (confirm(`Delete customer ${customer.name}?`)) {
      router.delete(`/api/v1/customers/${customer.id}`);
    }
  };

  const viewStatement = (customer: any) => {
    router.get(`/customers/${customer.id}/statement`);
  };

  const columns: Column<any>[] = [
    { key: 'customer_code', header: 'Code', accessor: (item) => <span className="font-mono text-sm">{item.customer_code}</span>, sortable: true },
    { key: 'name', header: 'Name', accessor: (item) => <span className="font-medium">{item.name}</span>, sortable: true },
    { key: 'phone', header: 'Phone', accessor: (item) => item.phone || '-', sortable: true },
    { key: 'email', header: 'Email', accessor: (item) => item.email || '-', sortable: true },
    { key: 'group', header: 'Group', accessor: (item) => item.customer_group?.name || '-', sortable: true },
    { key: 'credit_limit', header: 'Credit Limit', accessor: (item) => `$${Number(item.credit_limit).toLocaleString()}`, align: 'right', sortable: true },
    { key: 'loyalty_points', header: 'Loyalty', accessor: (item) => `${Number(item.loyalty_points).toFixed(0)} pts`, align: 'right', sortable: true },
    { key: 'status', header: 'Status', accessor: (item) => <Badge variant={item.status === 'active' ? 'success' : 'secondary'}>{item.status}</Badge> },
    { key: 'actions', header: 'Actions', accessor: (item) => (
      <div className="flex items-center gap-1">
        <button onClick={() => router.get(`/customers/${item.id}`)} className="p-1.5 rounded-lg text-slate-500 hover:bg-slate-100" aria-label="View"><Eye className="size-4" /></button>
        <button onClick={() => handleEdit(item)} className="p-1.5 rounded-lg text-slate-500 hover:bg-slate-100" aria-label="Edit"><Edit className="size-4" /></button>
        <button onClick={() => router.get(`/customers/${item.id}/statement`)} className="p-1.5 rounded-lg text-blue-500 hover:bg-blue-50" aria-label="Statement"><DollarSign className="size-4" /></button>
        <button onClick={() => router.get(`/customers/${item.id}/loyalty`)} className="p-1.5 rounded-lg text-amber-500 hover:bg-amber-50" aria-label="Loyalty"><RotateCcw className="size-4" /></button>
        <button onClick={() => router.get(`/customers/${item.id}/edit`)} className="p-1.5 rounded-lg text-slate-500 hover:bg-slate-100" aria-label="Edit"><Edit className="size-4" /></button>
        <button onClick={() => { if(confirm(`Delete ${item.name}?`)) router.delete(`/api/v1/customers/${item.id}`) }} className="p-1.5 rounded-lg text-red-500 hover:bg-red-50" aria-label="Delete"><Trash2 className="size-4" /></button>
      </div>
    ), width: '160px' },
  ];

  return (
    <AppLayout title="Customers">
      <Head title="Customers" />
      <div className="mb-5 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
          <h1 className="text-xl font-bold tracking-tight text-slate-900">Customers</h1>
          <p className="mt-1 text-sm text-slate-500">Manage your customers</p>
        </div>
        <Dialog open={isCreateOpen} onOpenChange={setIsCreateOpen}>
          <DialogTrigger asChild>
            <Button className="h-10 rounded-xl bg-blue-600 font-semibold hover:bg-blue-700"><Plus className="size-4" /> Add Customer</Button>
          </DialogTrigger>
          <DialogContent className="max-w-2xl max-h-[90vh] overflow-y-auto">
            <DialogHeader><DialogTitle>Create Customer</DialogTitle></DialogHeader>
            <form onSubmit={(e) => { e.preventDefault(); createForm.post('/api/v1/customers', { onSuccess: () => { setIsCreateOpen(false); createForm.reset(); } }); }} className="p-4 space-y-4">
              <FormSection title="Basic Information">
                <div className="grid gap-4 sm:grid-cols-2">
                  <FormField label="Company" required><Select value={createForm.data.company_id} onChange={e => createForm.setData('company_id', e.target.value)}>{safeCompanies.map(c => <option key={c.id} value={c.id}>{c.name}</option>)}</Select></FormField>
                  <FormField label="Customer Group"><Select value={createForm.data.customer_group_id} onChange={e => createForm.setData('customer_group_id', e.target.value)}><option value="">Select Group</option>{safeGroups.map(g => <option key={g.id} value={g.id}>{g.name}</option>)}</Select></FormField>
                  <FormField label="Customer Code" required><Input value={createForm.data.customer_code} onChange={e => createForm.setData('customer_code', e.target.value)} /></FormField>
                  <FormField label="Name" required><Input value={createForm.data.name} onChange={e => createForm.setData('name', e.target.value)} /></FormField>
                  <FormField label="Phone"><Input value={createForm.data.phone} onChange={e => createForm.setData('phone', e.target.value)} type="tel" /></FormField>
                  <FormField label="Email"><Input value={createForm.data.email} onChange={e => createForm.setData('email', e.target.value)} type="email" /></FormField>
                  <FormField label="Address"><textarea value={createForm.data.address} onChange={e => createForm.setData('address', e.target.value)} rows={3} className="w-full rounded-md border border-slate-200 bg-white px-3 py-2 text-sm" /></FormField>
                </div>
              </FormSection>
              <FormSection title="Credit Settings">
                <div className="grid gap-4 sm:grid-cols-3">
                  <FormField label="Credit Limit"><Input type="number" step="0.01" value={createForm.data.credit_limit} onChange={e => createForm.setData('credit_limit', parseFloat(e.target.value) || 0)} /></FormField>
                  <FormField label="Credit Days"><Input type="number" value={createForm.data.credit_days} onChange={e => createForm.setData('credit_days', parseInt(e.target.value) || 0)} /></FormField>
                  <FormField label="Status" required><Select value={createForm.data.status} onChange={e => createForm.setData('status', e.target.value)}><option value="active">Active</option><option value="inactive">Inactive</option><option value="blocked">Blocked</option></Select></FormField>
                </div>
              </FormSection>
              <FormActions>
                <button type="button" onClick={() => setIsCreateOpen(false)} className="btn btn-outline">Cancel</button>
                <button type="submit" className="btn btn-primary" disabled={createForm.processing}>{createForm.processing ? 'Creating...' : 'Create Customer'}</button>
              </FormActions>
            </form>
          </DialogContent>
        </Dialog>
      </div>

      <div className="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div className="flex flex-col gap-2 sm:flex-row sm:items-center">
          <Input placeholder="Search customers..." value={search} onChange={e => setSearch(e.target.value)} className="w-full sm:w-64" />
          <Select value={groupFilter} onChange={e => setGroupFilter(e.target.value)} className="w-full sm:w-48"><option value="">All Groups</option>{safeGroups.map(g => <option key={g.id} value={g.id}>{g.name}</option>)}</Select>
          <Select value={statusFilter} onChange={e => setStatusFilter(e.target.value)} className="w-full sm:w-40"><option value="">All Status</option><option value="active">Active</option><option value="inactive">Inactive</option><option value="blocked">Blocked</option></Select>
        </div>
      </div>

      <DataTable
        columns={[
          { key: 'customer_code', header: 'Code', accessor: item => <span className="font-mono text-sm">{item.customer_code}</span>, sortable: true },
          { key: 'name', header: 'Name', accessor: item => <span className="font-medium">{item.name}</span>, sortable: true },
          { key: 'phone', header: 'Phone', accessor: item => item.phone || '-', sortable: true },
          { key: 'email', header: 'Email', accessor: item => item.email || '-', sortable: true },
          { key: 'group', header: 'Group', accessor: item => item.customer_group?.name || '-', sortable: true },
          { key: 'credit_limit', header: 'Credit Limit', accessor: item => `$${Number(item.credit_limit).toLocaleString()}`, align: 'right', sortable: true },
          { key: 'loyalty_points', header: 'Loyalty', accessor: item => `${Number(item.loyalty_points).toFixed(0)} pts`, align: 'right', sortable: true },
          { key: 'status', header: 'Status', accessor: item => <Badge variant={item.status === 'active' ? 'success' : 'secondary'}>{item.status}</Badge> },
          { key: 'actions', header: 'Actions', accessor: (item) => (
            <div className="flex items-center gap-1">
              <button onClick={() => router.get(`/customers/${item.id}`)} className="p-1.5 rounded-lg text-slate-500 hover:bg-slate-100" aria-label="View"><Eye className="size-4" /></button>
              <button onClick={() => router.get(`/customers/${item.id}/statement`)} className="p-1.5 rounded-lg text-blue-500 hover:bg-blue-50" aria-label="Statement"><DollarSign className="size-4" /></button>
              <button onClick={() => router.get(`/customers/${item.id}/loyalty`)} className="p-1.5 rounded-lg text-amber-500 hover:bg-amber-50" aria-label="Loyalty"><RotateCcw className="size-4" /></button>
              <button onClick={() => router.get(`/customers/${item.id}/edit`)} className="p-1.5 rounded-lg text-slate-500 hover:bg-slate-100" aria-label="Edit"><Edit className="size-4" /></button>
              <button onClick={() => { if(confirm(`Delete ${item.name}?`)) router.delete(`/api/v1/customers/${item.id}`) }} className="p-1.5 rounded-lg text-red-500 hover:bg-red-50" aria-label="Delete"><Trash2 className="size-4" /></button>
            </div>
          ), width: '160px' },
        ]}
        data={safeCustomers.data}
        keyAccessor={item => item.id}
        pagination={{ currentPage: safeCustomers.current_page, lastPage: safeCustomers.last_page, perPage: safeCustomers.per_page, total: safeCustomers.total, onPageChange: page => router.get(`/customers?page=${page}`, {}, { only: ['customers'], preserveState: true, preserveScroll: true }) }}
      />
    </AppLayout>
  );
}