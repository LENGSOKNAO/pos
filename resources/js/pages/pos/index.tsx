import { Head, Deferred } from '@inertiajs/react';
import { useState, useEffect, useRef } from 'react';
import { Link, router, useForm, usePage } from '@inertiajs/react';
import {
  Plus, Minus, Trash2, Search, UserPlus, CreditCard, Banknote, Smartphone,
  Receipt, RotateCcw, ShoppingCart, X, Check, AlertCircle, Loader2, Package, LayoutDashboard, ArrowLeft, User, Pause
} from 'lucide-react';
import { cn } from '@/lib/utils';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select } from '@/components/ui/select';
import { Badge } from '@/components/ui/badge';
import { Card, CardHeader, CardTitle, CardContent, CardFooter } from '@/components/ui/card';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter, DialogTrigger } from '@/components/ui/dialog';
import { FormField } from '@/components/ui/form';
import { EmptyState } from '@/components/ui/empty-state';
import { GridSkeleton } from '@/components/admin';
import { Toast, Toaster } from '@/components/ui/toast';
import { useAuth } from '@/hooks/useAuth';
import { api } from '@/services/api';

interface CartItem {
  id: string;
  product_id: string;
  name: string;
  sku: string;
  barcode: string;
  unit_price: number;
  cost_price: number;
  quantity: number;
  discount: number;
  tax: number;
  variant_id?: string;
  batch_id?: string;
  serial_numbers?: string[];
}

interface Customer {
  id: string;
  name: string;
  customer_code: string;
  phone: string;
  email: string;
  credit_limit: number;
  loyalty_points: number;
}

interface PaymentMethod {
  id: string;
  name: string;
  type: string;
  fee_rate: number;
}

export default function PosIndex({
  products,
  categories,
  customers,
  paymentMethods,
  cashSession,
}: {
  products?: any;
  categories?: any[];
  customers?: any;
  paymentMethods?: PaymentMethod[];
  cashSession?: any;
}) {
  const safeProducts = Array.isArray(products?.data) ? products.data : Array.isArray(products) ? products : [];
  const safeCategories = Array.isArray(categories) ? categories : [];
  const safeCustomers = Array.isArray(customers?.data) ? customers.data : Array.isArray(customers) ? customers : [];
  const safePaymentMethods = Array.isArray(paymentMethods) ? paymentMethods : [];
  const { hasPermission, user } = useAuth();
  const [cart, setCart] = useState<CartItem[]>([]);
  const [selectedCustomer, setSelectedCustomer] = useState<Customer | null>(null);
  const [selectedCategory, setSelectedCategory] = useState<string>('');
  const [searchQuery, setSearchQuery] = useState('');
  const [paymentMethod, setPaymentMethod] = useState<PaymentMethod | null>(safePaymentMethods[0] || null);
  const [amountReceived, setAmountReceived] = useState(0);
  const [showPaymentDialog, setShowPaymentDialog] = useState(false);
  const [showCustomerDialog, setShowCustomerDialog] = useState(false);
  const [showHoldDialog, setShowHoldDialog] = useState(false);
  const [isProcessing, setIsProcessing] = useState(false);
  const [toasts, setToasts] = useState<Array<{ id: string; type: 'success' | 'error' | 'warning' | 'info'; title: string; message?: string }>>([]);
  const barcodeRef = useRef<HTMLInputElement>(null);
  const searchRef = useRef<HTMLInputElement>(null);

  const subtotal = cart.reduce((sum, item) => sum + item.unit_price * item.quantity, 0);
  const totalDiscount = cart.reduce((sum, item) => sum + item.discount * item.quantity, 0);
  const totalTax = cart.reduce((sum, item) => sum + item.tax * item.quantity, 0);
  const total = subtotal - totalDiscount + totalTax;
  const change = amountReceived - total;

  const addToast = (type: 'success' | 'error' | 'warning' | 'info', title: string, message?: string) => {
    const id = Math.random().toString(36).substr(2, 9);
    setToasts(prev => [...prev, { id, type, title, message }]);
    setTimeout(() => setToasts(prev => prev.filter(t => t.id !== title)), 5000);
  };

  const addToCart = (product: any) => {
    if (product.track_serial && !product.serial_numbers) {
      addToast('warning', 'Serial Required', 'This product requires serial number tracking');
      return;
    }
    if (product.track_batch && !product.batches?.length) {
      addToast('warning', 'Batch Required', 'This product requires batch tracking');
      return;
    }

    setCart(prev => {
      const existing = prev.find(item => item.product_id === product.id);
      if (existing) {
        if (existing.quantity >= (stockOf(product) || 999999)) {
          addToast('error', 'Insufficient Stock', `Only ${stockOf(product)} in stock`);
          return prev;
        }
        return prev.map(item =>
          item.product_id === product.id
            ? { ...item, quantity: item.quantity + 1 }
            : item
        );
      }
      return [...prev, {
        id: Math.random().toString(36).substr(2, 9),
        product_id: product.id,
        name: product.name,
        sku: product.sku,
        barcode: product.barcode,
        unit_price: product.selling_price,
        cost_price: product.cost_price,
        quantity: 1,
        discount: 0,
        tax: 0,
      }];
    });
  };

  const updateQuantity = (id: string, delta: number) => {
    setCart(prev => prev.map(item => {
      if (item.id !== id) return item;
      const newQty = Math.max(1, item.quantity + delta);
      return { ...item, quantity: newQty };
    }));
  };

  const removeFromCart = (id: string) => {
    setCart(prev => prev.filter(item => item.id !== id));
  };

  const clearCart = () => {
    setCart([]);
    setSelectedCustomer(null);
    setAmountReceived(0);
  };

  const handleCheckout = async () => {
    if (cart.length === 0) {
      addToast('error', 'Empty Cart', 'Please add items to the cart');
      return;
    }

    if (!paymentMethod) {
      addToast('error', 'Payment Method Required', 'Please select a payment method');
      return;
    }

    setIsProcessing(true);
    try {
      const response = await api.checkout({
        cash_session_id: cashSession?.id,
        customer_id: selectedCustomer?.id,
        items: cart.map(item => ({
          product_id: item.product_id,
          quantity: item.quantity,
          unit_price: item.unit_price,
          discount: item.discount,
          tax: item.tax,
        })),
        payments: [{
          payment_method_id: paymentMethod.id,
          amount: total,
          reference_number: `POS-${Date.now()}`,
        }],
      });

      const payload: any = (response as any)?.data ?? response;
      addToast('success', 'Sale Complete', `Invoice ${payload?.invoice?.invoice_number ?? ''} created`.trim());
      clearCart();
      setShowPaymentDialog(false);
    } catch (error: any) {
      addToast('error', 'Checkout Failed', error.message);
    } finally {
      setIsProcessing(false);
    }
  };

  const handleHold = () => {
    if (cart.length === 0) {
      addToast('warning', 'Empty Cart', 'Nothing to hold');
      return;
    }
    setShowHoldDialog(true);
  };

  const handleResume = async (orderId: string) => {
    // Implementation for resuming held order
    addToast('info', 'Resuming Order', 'Loading held order...');
  };

  const matchesFilter = (p: any) => {
    if (selectedCategory && p.category_id !== selectedCategory) return false;
    if (searchQuery && !p.name.toLowerCase().includes(searchQuery.toLowerCase()) &&
        !p.sku.toLowerCase().includes(searchQuery.toLowerCase()) &&
        !p.barcode?.toLowerCase().includes(searchQuery.toLowerCase())) return false;
    return true;
  };
  const productsToShow = safeProducts.filter(matchesFilter);

  const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(amount);
  };

  const stockOf = (product: any): number => {
    if (typeof product.stock_quantity === 'number') return Number(product.stock_quantity);
    if (typeof product.stock_quantity === 'string') return Number(product.stock_quantity);
    if (Array.isArray(product.stock)) return product.stock.reduce((s: number, r: any) => s + Number(r.quantity || 0), 0);
    if (product.stock && typeof product.stock.quantity !== 'undefined') return Number(product.stock.quantity);
    return 0;
  };

  return (
    <AppLayout fullscreen title="POS Terminal">
      <Head title="POS Terminal" />

      {/* Slim white top bar */}
      <div className="flex h-14 shrink-0 items-center gap-3 border-b border-slate-200 bg-white px-3 sm:px-4">
        <Link
          href="/dashboard"
          prefetch="hover"
          title="Back to Dashboard"
          aria-label="Back to Dashboard"
          className="flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-sm font-medium text-slate-700 transition-colors hover:bg-slate-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-slate-400"
        >
          <ArrowLeft className="size-4" />
          <span className="hidden sm:inline">Dashboard</span>
        </Link>
        <div className="min-w-0">
          <p className="truncate text-sm font-semibold text-slate-900">POS Terminal</p>
          <p className="hidden text-xs text-slate-500 sm:block">{cashSession?.name ?? 'New sale'}</p>
        </div>
        <div className="ml-auto flex items-center gap-2">
          {selectedCustomer ? (
            <span className="hidden items-center gap-1.5 rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs font-medium text-slate-700 md:flex">
              <User className="size-3.5" />
              {selectedCustomer.name}
            </span>
          ) : null}
          <span className="flex items-center gap-1.5 rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs font-medium text-slate-700">
            <span className="size-1.5 rounded-full bg-emerald-500" />
            {user?.name ?? 'Cashier'}
          </span>
        </div>
      </div>

      <div className="flex h-full flex-col bg-slate-100 lg:flex-row">
        {/* Left Panel - Products */}
        <div className="flex min-h-0 w-full flex-1 flex-col bg-white lg:border-r lg:border-slate-200">
          {/* Search + category pills */}
          <div className="border-b border-slate-200 bg-white p-3 sm:p-4">
            <div className="relative">
              <Search className="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-slate-400" />
              <input
                type="search"
                placeholder="Search products by name, SKU, or barcode..."
                value={searchQuery}
                onChange={(e) => setSearchQuery(e.target.value)}
                className="h-11 w-full rounded-xl border border-slate-200 bg-slate-50 pr-3 pl-9 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-400 focus:bg-white focus:ring-2 focus:ring-slate-100 focus:outline-none"
                ref={searchRef}
                autoFocus
              />
            </div>
            <div className="mt-3 flex gap-2 overflow-x-auto pb-1">
              <button
                type="button"
                onClick={() => setSelectedCategory('')}
                className={cn(
                  'shrink-0 rounded-full border px-3.5 py-1.5 text-sm font-medium transition-colors',
                  selectedCategory === ''
                    ? 'border-slate-900 bg-slate-900 text-white'
                    : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300 hover:bg-slate-50'
                )}
              >
                All
              </button>
              {safeCategories.map((c) => (
                <button
                  key={c.id}
                  type="button"
                  onClick={() => setSelectedCategory(selectedCategory === c.id ? '' : c.id)}
                  className={cn(
                    'shrink-0 rounded-full border px-3.5 py-1.5 text-sm font-medium transition-colors',
                    selectedCategory === c.id
                      ? 'border-slate-900 bg-slate-900 text-white'
                      : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300 hover:bg-slate-50'
                  )}
                >
                  {c.name}
                </button>
              ))}
              <Select
                value={selectedCategory}
                onChange={(e) => setSelectedCategory(e.target.value)}
                className="ml-auto hidden w-44 shrink-0 lg:block"
                aria-label="Category filter"
              >
                <option value="">All Categories</option>
                {safeCategories.map(c => <option key={c.id} value={c.id}>{c.name}</option>)}
              </Select>
            </div>
          </div>

          {/* Product Grid */}
          <div className="min-h-0 flex-1 overflow-y-auto bg-slate-100 p-3 sm:p-4">
            <Deferred data="products" fallback={<GridSkeleton />}>
            {productsToShow.length === 0 ? (
              <div className="flex h-full items-center justify-center rounded-xl border border-dashed border-slate-300 bg-white p-8">
                <EmptyState
                  icon={<Package className="size-8" />}
                  title="No products found"
                  hint="Add products to start selling"
                />
              </div>
            ) : (
              <div className="grid grid-cols-2 gap-3 sm:grid-cols-3 xl:grid-cols-4">
                {productsToShow.map(product => {
                  const stock = stockOf(product);
                  const low = stock <= (product.reorder_level || 0);
                  const out = stock <= 0;
                  return (
                    <button
                      key={product.id}
                      onClick={() => addToCart(product)}
                      disabled={out}
                      className={cn(
                        'flex min-h-32 flex-col rounded-xl border bg-white p-3 text-left transition-all active:scale-[0.98]',
                        out
                          ? 'cursor-not-allowed border-slate-200 opacity-50'
                          : 'border-slate-200 hover:border-slate-400 hover:shadow-sm'
                      )}
                    >
                      <div className="flex w-full items-center justify-between gap-2">
                        <span className="truncate text-[11px] font-medium tracking-wide text-slate-400 uppercase">{product.sku}</span>
                        <span className={cn(
                          'shrink-0 rounded-full px-2 py-0.5 text-[11px] font-semibold tabular-nums',
                          out ? 'bg-slate-100 text-slate-500' : low ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-800'
                        )}>
                          {out ? 'Out' : `${stock} left`}
                        </span>
                      </div>
                      <span className="mt-1.5 line-clamp-2 min-h-10 text-sm leading-snug font-semibold text-slate-900">{product.name}</span>
                      <span className="mt-auto flex w-full items-baseline justify-between gap-2 pt-2">
                        <span className="text-base font-bold text-slate-900 tabular-nums">
                          ${Number(product.selling_price).toFixed(2)}
                        </span>
                        <span className="text-xs text-slate-400">
                          {product.unit?.symbol || 'pc'}
                        </span>
                      </span>
                    </button>
                  );
                })}
              </div>
            )}
            </Deferred>
          </div>
        </div>

        {/* Right Panel - Cart card */}
        <div className="flex min-h-0 w-full flex-col bg-slate-100 p-3 sm:p-4 lg:w-[400px] lg:shrink-0 xl:w-[420px]">
          <div className="flex min-h-0 flex-1 flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            {/* Cart Header */}
            <div className="flex h-14 shrink-0 items-center justify-between border-b border-slate-200 px-4">
              <h2 className="flex items-center gap-2 text-sm font-semibold text-slate-900">
                <ShoppingCart className="size-4" />
                Current Sale
                <span className="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-600 tabular-nums">{cart.length}</span>
              </h2>
              <div className="flex items-center gap-1.5">
                {cart.length > 0 && hasPermission('pos.hold_order') && (
                  <Button variant="ghost" size="sm" onClick={handleHold} className="h-8 text-slate-600">
                    <Pause className="size-4" /> Hold
                  </Button>
                )}
                {cart.length > 0 && (
                  <Button variant="ghost" size="sm" onClick={clearCart} className="h-8 text-red-600 hover:text-red-700">
                    <Trash2 className="size-4" />
                  </Button>
                )}
              </div>
            </div>

            {/* Cart Items */}
            <div className="min-h-0 flex-1 overflow-y-auto p-3">
              {cart.length === 0 ? (
                <div className="flex h-full flex-col items-center justify-center gap-2 py-10 text-center">
                  <span className="flex size-12 items-center justify-center rounded-full bg-slate-100">
                    <ShoppingCart className="size-5 text-slate-400" />
                  </span>
                  <p className="text-sm font-semibold text-slate-700">Cart is empty</p>
                  <p className="text-xs text-slate-500">Tap a product to add it to the sale</p>
                </div>
              ) : (
                <ul className="divide-y divide-slate-100">
                  {cart.map(item => (
                    <li key={item.id} className="flex gap-2 py-3 first:pt-1 last:pb-1">
                      <div className="min-w-0 flex-1">
                        <div className="flex items-start justify-between gap-2">
                          <p className="truncate text-sm font-semibold text-slate-900">{item.name}</p>
                          <button
                            onClick={() => removeFromCart(item.id)}
                            className="rounded-md p-1 text-slate-400 hover:bg-slate-100 hover:text-red-600"
                            aria-label="Remove item"
                          >
                            <X className="size-4" />
                          </button>
                        </div>
                        <p className="text-xs text-slate-400 tabular-nums">{item.sku} · ${Number(item.unit_price).toFixed(2)} each</p>
                        <div className="mt-2 flex items-center justify-between">
                          <div className="flex items-center rounded-lg border border-slate-200">
                            <button
                              onClick={() => updateQuantity(item.id, -1)}
                              className="flex size-8 items-center justify-center rounded-l-lg text-slate-600 hover:bg-slate-100"
                              aria-label="Decrease quantity"
                            >
                              <Minus className="size-4" />
                            </button>
                            <span className="w-9 text-center text-sm font-bold tabular-nums">{item.quantity}</span>
                            <button
                              onClick={() => updateQuantity(item.id, 1)}
                              className="flex size-8 items-center justify-center rounded-r-lg text-slate-600 hover:bg-slate-100"
                              aria-label="Increase quantity"
                            >
                              <Plus className="size-4" />
                            </button>
                          </div>
                          <p className="text-sm font-bold text-slate-900 tabular-nums">
                            ${Number(item.unit_price * item.quantity).toFixed(2)}
                          </p>
                        </div>
                      </div>
                    </li>
                  ))}
                </ul>
              )}
            </div>

            {/* Summary */}
            <div className="shrink-0 border-t border-slate-200 bg-white p-4">
              <dl className="space-y-1.5 text-sm">
                <div className="flex justify-between">
                  <dt className="text-slate-500">Subtotal</dt>
                  <dd className="font-medium text-slate-900 tabular-nums">{formatCurrency(subtotal)}</dd>
                </div>
                {totalDiscount > 0 && (
                  <div className="flex justify-between text-red-600">
                    <dt>Discount</dt>
                    <dd className="font-medium">-{formatCurrency(totalDiscount)}</dd>
                  </div>
                )}
                {totalTax > 0 && (
                  <div className="flex justify-between">
                    <dt className="text-slate-500">Tax</dt>
                    <dd className="font-medium text-slate-900 tabular-nums">{formatCurrency(totalTax)}</dd>
                  </div>
                )}
                <div className="flex justify-between border-t border-slate-100 pt-2 text-base font-bold text-slate-900">
                  <dt>Total</dt>
                  <dd className="tabular-nums">{formatCurrency(total)}</dd>
                </div>
              </dl>

              {/* Customer row */}
              <div className="mt-3 flex gap-2">
                <Select
                  value={selectedCustomer?.id || ''}
                  onChange={(e) => {
                    const customer = safeCustomers.find((c: any) => c.id === e.target.value);
                    setSelectedCustomer(customer || null);
                  }}
                  className="h-10 flex-1"
                  aria-label="Customer"
                >
                  <option value="">Walk-in Customer</option>
                  {safeCustomers.map((c: any) => (
                    <option key={c.id} value={c.id}>
                      {c.name} ({c.customer_code})
                    </option>
                  ))}
                </Select>
                <Button variant="outline" size="sm" onClick={() => setShowCustomerDialog(true)} className="h-10 w-10 shrink-0 px-0" aria-label="Add customer">
                  <UserPlus className="size-4" />
                </Button>
              </div>
              {selectedCustomer && (
                <div className="mt-2 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-600">
                  <p className="font-semibold text-slate-900">{selectedCustomer.name}</p>
                  <p className="tabular-nums">Credit: {formatCurrency((selectedCustomer.credit_limit || 0) - ((selectedCustomer as any).due_amount || 0))} / {formatCurrency(selectedCustomer.credit_limit || 0)} · {selectedCustomer.loyalty_points || 0} pts</p>
                </div>
              )}

              {/* Payment row */}
              <div className="mt-3 grid grid-cols-2 gap-2">
                <Select
                  value={paymentMethod?.id || ''}
                  onChange={(e) => {
                    const method = safePaymentMethods.find(p => p.id === e.target.value);
                    setPaymentMethod(method || null);
                  }}
                  className="h-10"
                  aria-label="Payment method"
                >
                  {safePaymentMethods.map(p => (
                    <option key={p.id} value={p.id}>
                      {p.name} ({p.type})
                    </option>
                  ))}
                </Select>
                <Input
                  type="number"
                  step="0.01"
                  value={amountReceived}
                  onChange={(e) => setAmountReceived(parseFloat(e.target.value) || 0)}
                  placeholder={formatCurrency(total)}
                  className="h-10 font-semibold tabular-nums"
                  aria-label="Amount received"
                />
              </div>
              <div className="mt-1.5 flex justify-between text-xs">
                <span className={change >= 0 ? 'font-medium text-emerald-700' : 'font-medium text-red-600'}>
                  Change: {formatCurrency(change)}
                </span>
                <span className="font-semibold text-slate-700 tabular-nums">
                  Due: {formatCurrency(Math.max(0, total - amountReceived))}
                </span>
              </div>

              <Button
                onClick={handleCheckout}
                disabled={cart.length === 0 || isProcessing || amountReceived < total}
                className="mt-3 h-13 w-full rounded-xl bg-slate-900 py-3.5 text-base font-semibold text-white hover:bg-slate-800 disabled:opacity-40"
              >
                {isProcessing ? (
                  <>
                    <Loader2 className="size-5 animate-spin mr-2" />
                    Processing...
                  </>
                ) : (
                  `Pay ${formatCurrency(total)}`
                )}
              </Button>
            </div>
          </div>
        </div>
      </div>

      {/* Payment dialog */}
      <Dialog open={showPaymentDialog} onOpenChange={setShowPaymentDialog}>
        <DialogContent className="max-w-sm rounded-2xl">
          <DialogHeader>
            <DialogTitle>Confirm Payment</DialogTitle>
          </DialogHeader>
          <div className="space-y-2 text-sm">
            <div className="flex justify-between"><span className="text-slate-500">Total</span><span className="font-bold tabular-nums">{formatCurrency(total)}</span></div>
            <div className="flex justify-between"><span className="text-slate-500">Received</span><span className="font-medium tabular-nums">{formatCurrency(amountReceived)}</span></div>
            <div className="flex justify-between"><span className="text-slate-500">Change</span><span className="font-medium tabular-nums">{formatCurrency(change)}</span></div>
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => setShowPaymentDialog(false)}>Cancel</Button>
            <Button onClick={handleCheckout} disabled={isProcessing} className="bg-slate-900 text-white hover:bg-slate-800">
              {isProcessing ? <Loader2 className="size-4 animate-spin mr-2" /> : null} Confirm
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      {/* Customer dialog */}
      <Dialog open={showCustomerDialog} onOpenChange={setShowCustomerDialog}>
        <DialogContent className="max-w-sm rounded-2xl">
          <DialogHeader>
            <DialogTitle>Select Customer</DialogTitle>
          </DialogHeader>
          <div className="max-h-64 space-y-1 overflow-y-auto">
            <button type="button" onClick={() => { setSelectedCustomer(null); setShowCustomerDialog(false); }} className="w-full rounded-lg px-3 py-2 text-left text-sm hover:bg-slate-100">
              Walk-in Customer
            </button>
            {safeCustomers.map((c: any) => (
              <button
                key={c.id}
                type="button"
                onClick={() => { setSelectedCustomer(c); setShowCustomerDialog(false); }}
                className={cn('w-full rounded-lg px-3 py-2 text-left text-sm hover:bg-slate-100', selectedCustomer?.id === c.id && 'bg-slate-100 font-semibold')}
              >
                {c.name} <span className="text-slate-400">({c.customer_code})</span>
              </button>
            ))}
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => setShowCustomerDialog(false)}>Close</Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      {/* Hold dialog */}
      <Dialog open={showHoldDialog} onOpenChange={setShowHoldDialog}>
        <DialogContent className="max-w-sm rounded-2xl">
          <DialogHeader>
            <DialogTitle>Hold Current Sale</DialogTitle>
          </DialogHeader>
          <p className="text-sm text-slate-500">The current cart ({cart.length} items, {formatCurrency(total)}) will be parked so you can serve the next customer.</p>
          <DialogFooter>
            <Button variant="outline" onClick={() => setShowHoldDialog(false)}>Cancel</Button>
            <Button onClick={() => { setShowHoldDialog(false); addToast('info', 'Order Held', 'Sale has been parked'); }} className="bg-slate-900 text-white hover:bg-slate-800">
              Hold Sale
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      {/* Toasts */}
      <Toaster toasts={toasts} onClose={(id) => setToasts(prev => prev.filter(t => t.id !== id))} />
    </AppLayout>
  );
}
