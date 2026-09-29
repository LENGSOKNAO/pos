import { Head } from '@inertiajs/react';
import { useState } from 'react';
import { Link, router, useForm, usePage } from '@inertiajs/react';
import { Plus, Search, Filter, MoreHorizontal, Edit, Trash2, Eye, Package, Barcode, Tag, Box, Calendar } from 'lucide-react';
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
import { Product } from '@/types';

interface ProductFormData {
  company_id: string;
  category_id: string;
  brand_id: string;
  unit_id: string;
  sku: string;
  barcode: string;
  name: string;
  description: string;
  cost_price: number;
  selling_price: number;
  wholesale_price: number;
  vip_price: number;
  minimum_price: number;
  reorder_level: number;
  maximum_stock: number;
  track_batch: boolean;
  track_expiry: boolean;
  track_serial: boolean;
  status: string;
}

export default function ProductsIndex({ 
  products, 
  categories, 
  brands, 
  units,
  companies,
}: {
  products?: any;
  categories?: any[];
  brands?: any[];
  units?: any[];
  companies?: any[];
}) {
  const safeProducts = products ?? {};
  const safeCategories: any[] = Array.isArray(categories) ? categories : [];
  const safeBrands: any[] = Array.isArray(brands) ? brands : [];
  const safeUnits: any[] = Array.isArray(units) ? units : [];
  const safeCompanies: any[] = Array.isArray(companies) ? companies : [];
  const { hasPermission, user } = useAuth();
  const [isCreateOpen, setIsCreateOpen] = useState(false);
  const [isEditOpen, setIsEditOpen] = useState(false);
  const [editingProduct, setEditingProduct] = useState<any>(null);
  const [search, setSearch] = useState('');
  const [categoryFilter, setCategoryFilter] = useState('');
  const [statusFilter, setStatusFilter] = useState('');

  const createForm = useForm<ProductFormData>({
    company_id: user?.employee?.company_id || '',
    category_id: '',
    brand_id: '',
    unit_id: '',
    sku: '',
    barcode: '',
    name: '',
    description: '',
    cost_price: 0,
    selling_price: 0,
    wholesale_price: 0,
    vip_price: 0,
    minimum_price: 0,
    reorder_level: 0,
    maximum_stock: 0,
    track_batch: false,
    track_expiry: false,
    track_serial: false,
    status: 'active',
  });

  const editForm = useForm<ProductFormData>({
    company_id: '',
    category_id: '',
    brand_id: '',
    unit_id: '',
    sku: '',
    barcode: '',
    name: '',
    description: '',
    cost_price: 0,
    selling_price: 0,
    wholesale_price: 0,
    vip_price: 0,
    minimum_price: 0,
    reorder_level: 0,
    maximum_stock: 0,
    track_batch: false,
    track_expiry: false,
    track_serial: false,
    status: 'active',
  });

  const handleCreate = (e: React.FormEvent) => {
    e.preventDefault();
    createForm.post('/api/v1/products', {
      onSuccess: () => {
        setIsCreateOpen(false);
        createForm.reset();
      },
    });
  };

  const handleEdit = (product: any) => {
    editForm.reset({
      company_id: product.company_id,
      category_id: product.category_id,
      brand_id: product.brand_id,
      unit_id: product.unit_id,
      sku: product.sku,
      barcode: product.barcode || '',
      name: product.name,
      description: product.description || '',
      cost_price: product.cost_price,
      selling_price: product.selling_price,
      wholesale_price: product.wholesale_price,
      vip_price: product.vip_price,
      minimum_price: product.minimum_price,
      reorder_level: product.reorder_level,
      maximum_stock: product.maximum_stock,
      track_batch: product.track_batch,
      track_expiry: product.track_expiry,
      track_serial: product.track_serial,
      status: product.status,
    });
    setEditingProduct(product);
    setIsEditOpen(true);
  };

  const handleUpdate = (e: React.FormEvent) => {
    e.preventDefault();
    if (!editingProduct) return;
    editForm.put(`/api/v1/products/${editingProduct.id}`, {
      onSuccess: () => {
        setIsEditOpen(false);
        setEditingProduct(null);
      },
    });
  };

  const handleDelete = (product: any) => {
    if (confirm(`Are you sure you want to delete ${product.name}?`)) {
      router.delete(`/api/v1/products/${product.id}`);
    }
  };

  const columns: Column<any>[] = [
    { key: 'sku', header: 'SKU', accessor: (item) => <span className="font-mono text-sm">{item.sku}</span>, sortable: true },
    { key: 'barcode', header: 'Barcode', accessor: (item) => item.barcode ? <span className="font-mono text-sm">{item.barcode}</span> : '-', sortable: true },
    { key: 'name', header: 'Name', accessor: (item) => <span className="font-medium">{item.name}</span>, sortable: true },
    { key: 'category', header: 'Category', accessor: (item) => item.category?.name || '-', sortable: true },
    { key: 'brand', header: 'Brand', accessor: (item) => item.brand?.name || '-', sortable: true },
    { key: 'unit', header: 'Unit', accessor: (item) => item.unit?.symbol || '-', sortable: true },
    { key: 'cost_price', header: 'Cost', accessor: (item) => `$${Number(item.cost_price).toLocaleString('en-US', { minimumFractionDigits: 2 })}`, align: 'right', sortable: true },
    { key: 'selling_price', header: 'Price', accessor: (item) => `$${Number(item.selling_price).toLocaleString('en-US', { minimumFractionDigits: 2 })}`, align: 'right', sortable: true },
    { key: 'status', header: 'Status', accessor: (item) => <Badge variant={item.status === 'active' ? 'success' : 'secondary'}>{item.status}</Badge> },
    { key: 'actions', header: 'Actions', accessor: (item) => (
      <div className="flex items-center gap-1">
        <button onClick={() => handleEdit(item)} className="p-1.5 rounded-lg text-slate-500 hover:bg-slate-100" aria-label="Edit">
          <Eye className="size-4" />
        </button>
        <button onClick={() => handleDelete(item)} className="p-1.5 rounded-lg text-red-500 hover:bg-red-50" aria-label="Delete">
          <Trash2 className="size-4" />
        </button>
      </div>
    ), width: '120px' },
  ];

  return (
    <AppLayout title="Products">
      <Head title="Products" />
      
      <div className="mb-5 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
          <h1 className="text-xl font-bold tracking-tight text-slate-900">Products</h1>
          <p className="mt-1 text-sm text-slate-500">Manage your product catalog</p>
        </div>
        {hasPermission('products.create') && (
          <Dialog open={isCreateOpen} onOpenChange={setIsCreateOpen}>
            <DialogTrigger asChild>
              <Button className="h-10 rounded-xl bg-blue-600 font-semibold hover:bg-blue-700">
                <Plus className="size-4" /> Add Product
              </Button>
            </DialogTrigger>
            <DialogContent className="max-w-3xl max-h-[90vh] overflow-y-auto">
              <DialogHeader>
                <DialogTitle>Create Product</DialogTitle>
              </DialogHeader>
              <form onSubmit={handleCreate} className="p-4 space-y-4">
                <FormSection title="Basic Information">
                  <div className="grid gap-4 sm:grid-cols-2">
                    <FormField label="Company" required error={createForm.errors.company_id}>
                      <Select
                        value={createForm.data.company_id}
                        onChange={(e) => createForm.setData('company_id', e.target.value)}
                        disabled={!hasPermission('companies.update')}
                      >
                        <option value="">Select Company</option>
                        {safeCompanies.map((c: any) => <option key={c.id} value={c.id}>{c.name}</option>)}
                      </Select>
                    </FormField>
                    <FormField label="Category" error={createForm.errors.category_id}>
                      <Select
                        value={createForm.data.category_id}
                        onChange={(e) => createForm.setData('category_id', e.target.value)}
                      >
                        <option value="">Select Category</option>
                        {safeCategories.map((c: any) => <option key={c.id} value={c.id}>{c.name}</option>)}
                      </Select>
                    </FormField>
                    <FormField label="Brand" error={createForm.errors.brand_id}>
                      <Select
                        value={createForm.data.brand_id}
                        onChange={(e) => createForm.setData('brand_id', e.target.value)}
                      >
                        <option value="">Select Brand</option>
                        {safeBrands.map((b: any) => <option key={b.id} value={b.id}>{b.name}</option>)}
                      </Select>
                    </FormField>
                    <FormField label="Unit" required error={createForm.errors.unit_id}>
                      <Select
                        value={createForm.data.unit_id}
                        onChange={(e) => createForm.setData('unit_id', e.target.value)}
                      >
                        <option value="">Select Unit</option>
                        {safeUnits.map((u: any) => <option key={u.id} value={u.id}>{u.name} ({u.symbol})</option>)}
                      </Select>
                    </FormField>
                    <FormField label="SKU" required error={createForm.errors.sku}>
                      <Input
                        value={createForm.data.sku}
                        onChange={(e) => createForm.setData('sku', e.target.value)}
                        placeholder="Enter SKU"
                      />
                    </FormField>
                    <FormField label="Barcode" error={createForm.errors.barcode}>
                      <Input
                        value={createForm.data.barcode}
                        onChange={(e) => createForm.setData('barcode', e.target.value)}
                        placeholder="Enter Barcode"
                      />
                    </FormField>
                    <FormField label="Name" required error={createForm.errors.name}>
                      <Input
                        value={createForm.data.name}
                        onChange={(e) => createForm.setData('name', e.target.value)}
                        placeholder="Product Name"
                        className="sm:col-span-2"
                      />
                    </FormField>
                    <FormField label="Description">
                      <textarea
                        value={createForm.data.description}
                        onChange={(e) => createForm.setData('description', e.target.value)}
                        rows={3}
                        className="w-full rounded-md border border-slate-200 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                      />
                    </FormField>
                  </div>
                </FormSection>

                <FormSection title="Pricing">
                  <div className="grid gap-4 sm:grid-cols-5">
                    <FormField label="Cost Price" required error={createForm.errors.cost_price}>
                      <Input
                        type="number"
                        step="0.01"
                        value={createForm.data.cost_price}
                        onChange={(e) => createForm.setData('cost_price', parseFloat(e.target.value) || 0)}
                      />
                    </FormField>
                    <FormField label="Selling Price" required error={createForm.errors.selling_price}>
                      <Input
                        type="number"
                        step="0.01"
                        value={createForm.data.selling_price}
                        onChange={(e) => createForm.setData('selling_price', parseFloat(e.target.value) || 0)}
                      />
                    </FormField>
                    <FormField label="Wholesale Price" error={createForm.errors.wholesale_price}>
                      <Input
                        type="number"
                        step="0.01"
                        value={createForm.data.wholesale_price}
                        onChange={(e) => createForm.setData('wholesale_price', parseFloat(e.target.value) || 0)}
                      />
                    </FormField>
                    <FormField label="VIP Price" error={createForm.errors.vip_price}>
                      <Input
                        type="number"
                        step="0.01"
                        value={createForm.data.vip_price}
                        onChange={(e) => createForm.setData('vip_price', parseFloat(e.target.value) || 0)}
                      />
                    </FormField>
                    <FormField label="Minimum Price" error={createForm.errors.minimum_price}>
                      <Input
                        type="number"
                        step="0.01"
                        value={createForm.data.minimum_price}
                        onChange={(e) => createForm.setData('minimum_price', parseFloat(e.target.value) || 0)}
                      />
                    </FormField>
                  </div>
                </FormSection>

                <FormSection title="Inventory Settings">
                  <div className="grid gap-4 sm:grid-cols-3">
                    <FormField label="Reorder Level" error={createForm.errors.reorder_level}>
                      <Input
                        type="number"
                        step="0.01"
                        value={createForm.data.reorder_level}
                        onChange={(e) => createForm.setData('reorder_level', parseFloat(e.target.value) || 0)}
                      />
                    </FormField>
                    <FormField label="Maximum Stock" error={createForm.errors.maximum_stock}>
                      <Input
                        type="number"
                        step="0.01"
                        value={createForm.data.maximum_stock}
                        onChange={(e) => createForm.setData('maximum_stock', parseFloat(e.target.value) || 0)}
                      />
                    </FormField>
                    <FormField label="Status" required error={createForm.errors.status}>
                      <Select
                        value={createForm.data.status}
                        onChange={(e) => createForm.setData('status', e.target.value)}
                      >
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                        <option value="discontinued">Discontinued</option>
                      </Select>
                    </FormField>
                  </div>
                  <div className="flex items-center gap-4">
                    <label className="flex items-center gap-2 cursor-pointer">
                      <input
                        type="checkbox"
                        checked={createForm.data.track_batch}
                        onChange={(e) => createForm.setData('track_batch', e.target.checked)}
                        className="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                      />
                      <span className="text-sm">Track Batches</span>
                    </label>
                    <label className="flex items-center gap-2 cursor-pointer">
                      <input
                        type="checkbox"
                        checked={createForm.data.track_expiry}
                        onChange={(e) => createForm.setData('track_expiry', e.target.checked)}
                        className="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                      />
                      <span className="text-sm">Track Expiry</span>
                    </label>
                    <label className="flex items-center gap-2 cursor-pointer">
                      <input
                        type="checkbox"
                        checked={createForm.data.track_serial}
                        onChange={(e) => createForm.setData('track_serial', e.target.checked)}
                        className="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                      />
                      <span className="text-sm">Track Serials</span>
                    </label>
                  </div>
                </FormSection>

                <FormActions>
                  <button type="button" onClick={() => setIsCreateOpen(false)} className="btn btn-outline">Cancel</button>
                  <button type="submit" className="btn btn-primary" disabled={createForm.processing}>
                    {createForm.processing ? 'Creating...' : 'Create Product'}
                  </button>
                </FormActions>
              </form>
            </DialogContent>
          </Dialog>
        )}

        <Dialog open={isEditOpen} onOpenChange={setIsEditOpen}>
          <DialogContent className="max-w-3xl max-h-[90vh] overflow-y-auto">
            <DialogHeader>
              <DialogTitle>Edit Product</DialogTitle>
            </DialogHeader>
            <form onSubmit={handleUpdate} className="p-4 space-y-4">
              <FormSection title="Basic Information">
                <div className="grid gap-4 sm:grid-cols-2">
                  <FormField label="SKU" required error={editForm.errors.sku}>
                    <Input
                      value={editForm.data.sku}
                      onChange={(e) => editForm.setData('sku', e.target.value)}
                    />
                  </FormField>
                  <FormField label="Barcode" error={editForm.errors.barcode}>
                    <Input
                      value={editForm.data.barcode}
                      onChange={(e) => editForm.setData('barcode', e.target.value)}
                    />
                  </FormField>
                  <FormField label="Name" required error={editForm.errors.name}>
                    <Input
                      value={editForm.data.name}
                      onChange={(e) => editForm.setData('name', e.target.value)}
                      className="sm:col-span-2"
                    />
                  </FormField>
                  <FormField label="Description">
                    <textarea
                      value={editForm.data.description}
                      onChange={(e) => editForm.setData('description', e.target.value)}
                      rows={3}
                      className="w-full rounded-md border border-slate-200 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                    />
                  </FormField>
                </div>
              </FormSection>

              <FormSection title="Pricing">
                <div className="grid gap-4 sm:grid-cols-5">
                  <FormField label="Cost Price" required error={editForm.errors.cost_price}>
                    <Input type="number" step="0.01" value={editForm.data.cost_price} onChange={(e) => editForm.setData('cost_price', parseFloat(e.target.value) || 0)} />
                  </FormField>
                  <FormField label="Selling Price" required error={editForm.errors.selling_price}>
                    <Input type="number" step="0.01" value={editForm.data.selling_price} onChange={(e) => editForm.setData('selling_price', parseFloat(e.target.value) || 0)} />
                  </FormField>
                  <FormField label="Wholesale Price" error={editForm.errors.wholesale_price}>
                    <Input type="number" step="0.01" value={editForm.data.wholesale_price} onChange={(e) => editForm.setData('wholesale_price', parseFloat(e.target.value) || 0)} />
                  </FormField>
                  <FormField label="VIP Price" error={editForm.errors.vip_price}>
                    <Input type="number" step="0.01" value={editForm.data.vip_price} onChange={(e) => editForm.setData('vip_price', parseFloat(e.target.value) || 0)} />
                  </FormField>
                  <FormField label="Minimum Price" error={editForm.errors.minimum_price}>
                    <Input type="number" step="0.01" value={editForm.data.minimum_price} onChange={(e) => editForm.setData('minimum_price', parseFloat(e.target.value) || 0)} />
                  </FormField>
                </div>
              </FormSection>

              <FormSection title="Inventory Settings">
                <div className="grid gap-4 sm:grid-cols-3">
                  <FormField label="Reorder Level" error={editForm.errors.reorder_level}>
                    <Input type="number" step="0.01" value={editForm.data.reorder_level} onChange={(e) => editForm.setData('reorder_level', parseFloat(e.target.value) || 0)} />
                  </FormField>
                  <FormField label="Maximum Stock" error={editForm.errors.maximum_stock}>
                    <Input type="number" step="0.01" value={editForm.data.maximum_stock} onChange={(e) => editForm.setData('maximum_stock', parseFloat(e.target.value) || 0)} />
                  </FormField>
                  <FormField label="Status" required error={editForm.errors.status}>
                    <Select value={editForm.data.status} onChange={(e) => editForm.setData('status', e.target.value)}>
                      <option value="active">Active</option>
                      <option value="inactive">Inactive</option>
                      <option value="discontinued">Discontinued</option>
                    </Select>
                  </FormField>
                </div>
                <div className="flex items-center gap-4">
                  <label className="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" checked={editForm.data.track_batch} onChange={(e) => editForm.setData('track_batch', e.target.checked)} className="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500" />
                    <span className="text-sm">Track Batches</span>
                  </label>
                  <label className="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" checked={editForm.data.track_expiry} onChange={(e) => editForm.setData('track_expiry', e.target.checked)} className="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500" />
                    <span className="text-sm">Track Expiry</span>
                  </label>
                  <label className="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" checked={editForm.data.track_serial} onChange={(e) => editForm.setData('track_serial', e.target.checked)} className="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500" />
                    <span className="text-sm">Track Serials</span>
                  </label>
                </div>
              </FormSection>

              <FormActions>
                <button type="button" onClick={() => setIsEditOpen(false)} className="btn btn-outline">Cancel</button>
                <button type="submit" className="btn btn-primary" disabled={editForm.processing}>
                  {editForm.processing ? 'Updating...' : 'Update Product'}
                </button>
              </FormActions>
            </form>
          </DialogContent>
        </Dialog>
      </div>

      <div className="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div className="flex flex-col gap-2 sm:flex-row sm:items-center">
          <Input
            placeholder="Search products..."
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            className="w-full sm:w-64"
          />
          <Select
            value={categoryFilter}
            onChange={(e) => setCategoryFilter(e.target.value)}
            className="w-full sm:w-48"
          >
            <option value="">All Categories</option>
            {safeCategories.map((c: any) => <option key={c.id} value={c.id}>{c.name}</option>)}
          </Select>
          <Select
            value={statusFilter}
            onChange={(e) => setStatusFilter(e.target.value)}
            className="w-full sm:w-40"
          >
            <option value="">All Status</option>
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
            <option value="discontinued">Discontinued</option>
          </Select>
        </div>
      </div>

      <DataTable
        columns={columns}
        data={safeProducts.data ?? []}
        keyAccessor={(item) => item.id}
        onRowClick={(item) => handleEdit(item)}
        pagination={{
          currentPage: safeProducts.current_page ?? 1,
          lastPage: safeProducts.last_page ?? 1,
          perPage: safeProducts.per_page ?? 15,
          total: safeProducts.total ?? 0,
          onPageChange: (page) => router.get('/products', { page }, { only: ['products'], preserveState: true, preserveScroll: true }),
          buildUrl: () => '/products',
          prefetchOnly: ['products'],
          prefetchData: (page) => ({ page }),
        }}
        sortBy={safeProducts.sort_by}
        sortOrder={safeProducts.sort_order}
        onSort={(key, order) => router.get(`/products?sort_by=${key}&sort_order=${order}`)}
      />
    </AppLayout>
  );
}