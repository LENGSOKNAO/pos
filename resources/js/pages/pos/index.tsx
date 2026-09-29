import { Head, Deferred } from '@inertiajs/react';
import { useState, useEffect, useRef } from 'react';
import { Link, router, useForm, usePage } from '@inertiajs/react';
import {
  Plus, Minus, Trash2, Search, UserPlus, CreditCard, Banknote, Smartphone,
  Receipt, RotateCcw, ShoppingCart, X, Check, AlertCircle, Loader2, Package, LayoutDashboard, ArrowLeft, User, Pause, ScanBarcode, Wallet, ChevronUp, BadgePercent, Users, Clock
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

const TILE_COLORS = [
  'bg-emerald-100 text-emerald-700',
  'bg-blue-100 text-blue-700',
  'bg-amber-100 text-amber-700',
  'bg-rose-100 text-rose-700',
  'bg-violet-100 text-violet-700',
  'bg-cyan-100 text-cyan-700',
  'bg-orange-100 text-orange-700',
  'bg-lime-100 text-lime-700',
];

function tileColor(name: string) {
  let h = 0;
  for (let i = 0; i < name.length; i++) h = (h * 31 + name.charCodeAt(i)) % 997;
  return TILE_COLORS[h % TILE_COLORS.length];
}

function tenderIcon(name: string, type: string) {
  const n = `${name} ${type}`.toLowerCase();
  if (n.includes('khqr') || n.includes('qr') || n.includes('aba')) return Smartphone;
  if (n.includes('card') || n.includes('visa') || n.includes('credit')) return CreditCard;
  if (n.includes('credit') || n.includes('loan') || n.includes('due')) return Wallet;
  return Banknote;
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
  const [showCartSheet, setShowCartSheet] = useState(false);
  const [showHeldDialog, setShowHeldDialog] = useState(false);
  const [heldOrders, setHeldOrders] = useState<any[]>([]);
  const [heldLoading, setHeldLoading] = useState(false);
  const [receipt, setReceipt] = useState<any | null>(null);
  const [isProcessing, setIsProcessing] = useState(false);
  const [toasts, setToasts] = useState<Array<{ id: string; type: 'success' | 'error' | 'warning' | 'info'; title: string; message?: string }>>([]);
  const barcodeRef = useRef<HTMLInputElement>(null);
  const searchRef = useRef<HTMLInputElement>(null);

  const subtotal = cart.reduce((sum, item) => sum + item.unit_price * item.quantity, 0);
  const totalDiscount = cart.reduce((sum, item) => sum + item.discount * item.quantity, 0);
  const totalTax = cart.reduce((sum, item) => sum + item.tax * item.quantity, 0);
  const total = subtotal - totalDiscount + totalTax;
  const change = amountReceived - total;
  const itemCount = cart.reduce((s, i) => s + i.quantity, 0);

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
      const invoice = payload?.invoice;
      setReceipt({
        invoice_no: invoice?.invoice_number ?? '',
        total,
        paid: amountReceived >= total ? amountReceived : total,
        change: Math.max(0, (amountReceived >= total ? amountReceived : total) - total),
        method: paymentMethod?.name ?? '',
        customer: selectedCustomer?.name ?? 'Walk-in',
        lines: cart.map((i) => ({ name: i.name, qty: i.quantity, price: i.unit_price })),
        time: new Date().toLocaleString(),
      });
      addToast('success', 'Sale Complete', `Invoice ${invoice?.invoice_number ?? ''} created`.trim());
      clearCart();
      setShowPaymentDialog(false);
      setShowCartSheet(false);
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

  const confirmHold = async () => {
    if (!cashSession?.id) {
      addToast('error', 'No Cash Session', 'Open a cash session first');
      return;
    }
    setIsProcessing(true);
    try {
      await api.holdOrder({
        cash_session_id: cashSession.id,
        customer_id: selectedCustomer?.id,
        items: cart.map((item) => ({
          product_id: item.product_id,
          quantity: item.quantity,
          unit_price: item.unit_price,
          discount: item.discount,
          tax: item.tax,
        })),
      });
      addToast('success', 'Order Held', 'Sale has been parked');
      clearCart();
      setShowHoldDialog(false);
      setShowCartSheet(false);
    } catch (error: any) {
      addToast('error', 'Hold Failed', error.message);
    } finally {
      setIsProcessing(false);
    }
  };

  const openHeld = async () => {
    setShowHeldDialog(true);
    setHeldLoading(true);
    try {
      const res: any = await api.heldOrders({});
      const list = res?.data?.data ?? res?.data ?? [];
      setHeldOrders(Array.isArray(list) ? list : []);
    } catch (error: any) {
      addToast('error', 'Load Failed', error.message);
    } finally {
      setHeldLoading(false);
    }
  };

  const resumeHeld = async (order: any) => {
    try {
      await api.resumeOrder(order.id);
      const lines = (order.items ?? []).map((it: any) => ({
        id: Math.random().toString(36).substr(2, 9),
        product_id: it.product_id ?? it.product?.id,
        name: it.product?.name ?? 'Item',
        sku: it.product?.sku ?? '',
        barcode: it.product?.barcode ?? '',
        unit_price: Number(it.unit_price ?? 0),
        cost_price: Number(it.cost_price ?? 0),
        quantity: Number(it.quantity ?? 1),
        discount: Number(it.discount ?? 0),
        tax: Number(it.tax ?? 0),
      }));
      setCart(lines);
      if (order.customer) {
        setSelectedCustomer({
          id: order.customer.id, name: order.customer.name,
          customer_code: order.customer.customer_code ?? '', phone: order.customer.phone ?? '',
          email: order.customer.email ?? '', credit_limit: 0, loyalty_points: 0,
        });
      }
      setShowHeldDialog(false);
      addToast('success', 'Order Resumed', `${order.order_number ?? ''} loaded to cart`.trim());
    } catch (error: any) {
      addToast('error', 'Resume Failed', error.message);
    }
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

  const ticketBody = (
    <>
      {/* Customer row */}
      <div className="shrink-0 px-4 pt-3">
        <div className="flex items-center gap-2">
          <button
            type="button"
            onClick={() => setShowCustomerDialog(true)}
            className={cn(
              'flex h-10 flex-1 items-center gap-2 rounded-xl border px-3 text-sm font-medium transition-colors',
              selectedCustomer
                ? 'border-emerald-200 bg-emerald-50 text-emerald-800'
                : 'border-dashed border-slate-300 bg-slate-50 text-slate-600 hover:border-slate-400 hover:bg-slate-100',
            )}
          >
            <span className="flex size-6 shrink-0 items-center justify-center rounded-full bg-slate-900 text-white">
              <User className="size-3.5" />
            </span>
            <span className="truncate">{selectedCustomer ? selectedCustomer.name : 'Walk-in customer'}</span>
            {selectedCustomer && (
              <span
                role="button"
                tabIndex={0}
                aria-label="Remove customer"
                onClick={(e) => { e.stopPropagation(); setSelectedCustomer(null); }}
                onKeyDown={(e) => { if (e.key === 'Enter') setSelectedCustomer(null); }}
                className="ml-auto rounded-full p-0.5 hover:bg-emerald-100"
              >
                <X className="size-4" />
              </span>
            )}
          </button>
          <Button variant="outline" size="sm" onClick={() => setShowCustomerDialog(true)} className="h-10 w-10 shrink-0 rounded-xl px-0" aria-label="Add customer">
            <UserPlus className="size-4" />
          </Button>
        </div>
      </div>

      {/* Lines */}
      <div className="min-h-0 flex-1 overflow-y-auto px-4 py-3">
        {cart.length === 0 ? (
          <div className="flex h-full flex-col items-center justify-center gap-2 py-10 text-center">
            <span className="flex size-14 items-center justify-center rounded-2xl bg-slate-100">
              <ShoppingCart className="size-6 text-slate-400" />
            </span>
            <p className="text-sm font-semibold text-slate-700">Cart is empty</p>
            <p className="max-w-45 text-xs text-slate-500">Tap a product card to add it to the sale</p>
          </div>
        ) : (
          <ul className="space-y-2">
            {cart.map((item) => (
              <li key={item.id} className="rounded-xl border border-slate-200 bg-white p-2.5">
                <div className="flex items-start justify-between gap-2">
                  <p className="line-clamp-2 min-w-0 flex-1 text-[13px] leading-snug font-semibold text-slate-900">{item.name}</p>
                  <button
                    onClick={() => removeFromCart(item.id)}
                    className="rounded-md p-1 text-slate-300 hover:bg-red-50 hover:text-red-600"
                    aria-label="Remove item"
                  >
                    <Trash2 className="size-4" />
                  </button>
                </div>
                <p className="mt-0.5 text-[11px] text-slate-400 tabular-nums">{item.sku} · {formatCurrency(Number(item.unit_price))} each</p>
                <div className="mt-2 flex items-center justify-between">
                  <div className="flex items-center rounded-lg bg-slate-100 p-0.5">
                    <button
                      onClick={() => updateQuantity(item.id, -1)}
                      className="flex size-7 items-center justify-center rounded-md bg-white text-slate-700 shadow-sm hover:bg-slate-50 active:scale-95"
                      aria-label="Decrease quantity"
                    >
                      <Minus className="size-3.5" />
                    </button>
                    <span className="w-8 text-center text-sm font-extrabold tabular-nums">{item.quantity}</span>
                    <button
                      onClick={() => updateQuantity(item.id, 1)}
                      className="flex size-7 items-center justify-center rounded-md bg-slate-900 text-white shadow-sm hover:bg-slate-700 active:scale-95"
                      aria-label="Increase quantity"
                    >
                      <Plus className="size-3.5" />
                    </button>
                  </div>
                  <p className="text-sm font-extrabold text-slate-900 tabular-nums">
                    {formatCurrency(Number(item.unit_price * item.quantity))}
                  </p>
                </div>
              </li>
            ))}
          </ul>
        )}
      </div>

      {/* Sticky checkout footer */}
      <div className="shrink-0 border-t border-slate-200 bg-white px-4 pt-3 pb-4">
        <dl className="space-y-1 text-[13px]">
          <div className="flex justify-between">
            <dt className="text-slate-500">Subtotal</dt>
            <dd className="font-semibold text-slate-900 tabular-nums">{formatCurrency(subtotal)}</dd>
          </div>
          {totalDiscount > 0 && (
            <div className="flex justify-between text-emerald-700">
              <dt className="flex items-center gap-1"><BadgePercent className="size-3.5" /> Discount</dt>
              <dd className="font-semibold tabular-nums">-{formatCurrency(totalDiscount)}</dd>
            </div>
          )}
          <div className="flex justify-between">
            <dt className="text-slate-500">Tax</dt>
            <dd className="font-semibold text-slate-900 tabular-nums">{formatCurrency(totalTax)}</dd>
          </div>
        </dl>
        <div className="mt-2 flex items-end justify-between rounded-2xl bg-slate-900 px-4 py-3 text-white">
          <span className="text-[11px] font-bold tracking-widest uppercase opacity-60">Total</span>
          <span className="text-[32px] leading-none font-black tracking-tight tabular-nums">{formatCurrency(total)}</span>
        </div>

        {/* Tender keys */}
        <p className="mt-3 mb-1.5 text-[11px] font-bold tracking-wider text-slate-400 uppercase">Tender</p>
        <div className="grid grid-cols-4 gap-1.5">
          {safePaymentMethods.slice(0, 4).map((p) => {
            const Icon = tenderIcon(p.name, p.type);
            const active = paymentMethod?.id === p.id;
            return (
              <button
                key={p.id}
                type="button"
                onClick={() => setPaymentMethod(p)}
                className={cn(
                  'flex flex-col items-center gap-1 rounded-xl border px-1 py-2 text-[11px] font-bold transition-all active:scale-95',
                  active
                    ? 'border-slate-900 bg-slate-900 text-white shadow'
                    : 'border-slate-200 bg-slate-50 text-slate-600 hover:border-slate-400 hover:bg-white',
                )}
              >
                <Icon className="size-4" />
                <span className="max-w-full truncate">{p.name}</span>
              </button>
            );
          })}
        </div>
        {safePaymentMethods.length > 4 && (
          <Select
            value={paymentMethod?.id || ''}
            onChange={(e) => {
              const method = safePaymentMethods.find((m) => m.id === e.target.value);
              setPaymentMethod(method || null);
            }}
            className="mt-1.5 h-9 w-full"
            aria-label="Payment method"
          >
            {safePaymentMethods.map((p) => (
              <option key={p.id} value={p.id}>
                {p.name} ({p.type})
              </option>
            ))}
          </Select>
        )}

        {/* Quick cash */}
        <div className="mt-2 flex flex-wrap gap-1.5">
          {[
            { label: 'Exact', value: total },
            { label: '$5', value: 5 },
            { label: '$10', value: 10 },
            { label: '$20', value: 20 },
            { label: '$50', value: 50 },
            { label: '$100', value: 100 },
          ].map((d) => (
            <button
              key={d.label}
              type="button"
              onClick={() => setAmountReceived(Number(d.value.toFixed(2)))}
              className="rounded-lg bg-slate-100 px-2.5 py-1.5 text-xs font-bold text-slate-700 tabular-nums hover:bg-slate-200 active:scale-95"
            >
              {d.label}
            </button>
          ))}
        </div>
        <div className="mt-2 flex gap-2">
          <div className="relative flex-1">
            <span className="absolute top-1/2 left-3 -translate-y-1/2 text-sm font-bold text-slate-400">$</span>
            <Input
              type="number"
              step="0.01"
              value={amountReceived}
              onChange={(e) => setAmountReceived(parseFloat(e.target.value) || 0)}
              placeholder={total.toFixed(2)}
              className="h-11 flex-1 pr-3 pl-7 text-base font-extrabold tabular-nums"
              aria-label="Amount received"
            />
          </div>
        </div>
        <div className="mt-2 flex items-center justify-between rounded-xl bg-slate-50 px-3 py-2 text-xs font-semibold">
          <span className={change >= 0 ? 'text-emerald-700' : 'text-red-600'}>
            Change: {formatCurrency(change)}
          </span>
          <span className="text-slate-700 tabular-nums">
            Due: {formatCurrency(Math.max(0, total - amountReceived))}
          </span>
        </div>

        <Button
          onClick={handleCheckout}
          disabled={cart.length === 0 || isProcessing || amountReceived < total}
          className="mt-3 h-13 w-full rounded-2xl bg-emerald-600 py-3.5 text-base font-extrabold tracking-wide text-white uppercase hover:bg-emerald-700 disabled:opacity-40"
        >
          {isProcessing ? (
            <>
              <Loader2 className="size-5 animate-spin mr-2" />
              Processing...
            </>
          ) : (
            <>
              <Check className="size-5 mr-2" strokeWidth={3} />
              Complete Sale · {formatCurrency(total)}
            </>
          )}
        </Button>
      </div>
    </>
  );

  return (
    <AppLayout fullscreen title="POS Terminal">
      <Head title="POS Terminal" />

      {/* Top bar: back, search, customer chip, held orders, cashier */}
      <div className="flex h-16 shrink-0 items-center gap-2 border-b border-slate-200 bg-white px-3 sm:gap-3 sm:px-4">
        <Link
          href="/dashboard"
          prefetch="hover"
          title="Back to Dashboard"
          aria-label="Back to Dashboard"
          className="flex size-10 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-700 transition-colors hover:bg-slate-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-slate-400"
        >
          <ArrowLeft className="size-5" />
        </Link>
        <div className="relative min-w-0 flex-1 sm:max-w-xl">
          <Search className="absolute top-1/2 left-3 size-4.5 -translate-y-1/2 text-slate-400" />
          <input
            type="search"
            placeholder="Search products, SKU…"
            value={searchQuery}
            onChange={(e) => setSearchQuery(e.target.value)}
            className="h-11 w-full rounded-xl border border-slate-200 bg-slate-50 pr-20 pl-10 text-sm font-medium text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:bg-white focus:ring-2 focus:ring-slate-200 focus:outline-none"
            ref={searchRef}
            autoFocus
          />
          <span className="absolute top-1/2 right-2 hidden -translate-y-1/2 items-center gap-1 rounded-lg bg-slate-100 px-2 py-1 text-[11px] font-semibold text-slate-500 sm:flex">
            <ScanBarcode className="size-3.5" /> Barcode ready
          </span>
        </div>
        <div className="ml-auto flex shrink-0 items-center gap-2">
          <button
            type="button"
            onClick={() => setShowCustomerDialog(true)}
            className={cn(
              'hidden h-10 items-center gap-2 rounded-xl border px-3 text-sm font-semibold transition-colors md:flex',
              selectedCustomer
                ? 'border-emerald-200 bg-emerald-50 text-emerald-800'
                : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50',
            )}
          >
            <Users className="size-4" />
            {selectedCustomer ? selectedCustomer.name : 'Customer'}
          </button>
          <button
            type="button"
            onClick={openHeld}
            title="Held orders"
            className="relative flex h-10 items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 text-sm font-semibold text-slate-700 hover:bg-slate-50"
          >
            <Clock className="size-4" />
            <span className="hidden lg:inline">Held</span>
          </button>
          <span className="flex h-10 items-center gap-2 rounded-xl bg-slate-900 px-3 text-sm font-semibold text-white">
            <span className="size-2 rounded-full bg-emerald-400" />
            <span className="hidden max-w-24 truncate sm:inline">{user?.name ?? 'Cashier'}</span>
            <span className="sm:hidden"><User className="size-4" /></span>
          </span>
        </div>
      </div>

      <div className="flex min-h-0 flex-1 flex-col bg-slate-100 lg:flex-row">
        {/* Left: category rail + product grid */}
        <div className="flex min-h-0 flex-1 flex-row bg-white">
          {/* Vertical category rail */}
          <div className="flex w-28 shrink-0 flex-col gap-1.5 overflow-y-auto border-r border-slate-200 bg-white p-2">
            <button
              type="button"
              onClick={() => setSelectedCategory('')}
              className={cn(
                'flex shrink-0 flex-col items-center gap-1 rounded-xl px-2 py-2.5 text-xs font-bold transition-all active:scale-95',
                selectedCategory === ''
                  ? 'bg-slate-900 text-white shadow'
                  : 'bg-slate-50 text-slate-600 hover:bg-slate-100',
              )}
            >
              <LayoutDashboard className="size-4.5" />
              All
            </button>
            {safeCategories.map((c) => {
              const active = selectedCategory === c.id;
              return (
                <button
                  key={c.id}
                  type="button"
                  onClick={() => setSelectedCategory(active ? '' : c.id)}
                  title={c.name}
                  className={cn(
                    'flex shrink-0 flex-col items-center gap-1 rounded-xl px-2 py-2.5 text-xs font-bold transition-all active:scale-95',
                    active ? 'bg-slate-900 text-white shadow' : 'bg-slate-50 text-slate-600 hover:bg-slate-100',
                  )}
                >
                  <span className={cn(
                    'flex size-7 items-center justify-center rounded-lg text-sm font-black',
                    active ? 'bg-white/20 text-white' : tileColor(c.name),
                  )}>
                    {c.name.charAt(0).toUpperCase()}
                  </span>
                  <span className="line-clamp-2 w-full text-center leading-tight">{c.name}</span>
                </button>
              );
            })}
          </div>

          {/* Product grid (sticky scroll) */}
          <div className="min-h-0 flex-1 overflow-y-auto bg-slate-100 p-3 pb-24 sm:p-4 lg:pb-4">
            <Deferred data="products" fallback={<GridSkeleton />}>
              {productsToShow.length === 0 ? (
                <div className="flex h-full items-center justify-center rounded-2xl border border-dashed border-slate-300 bg-white p-8">
                  <EmptyState
                    icon={<Package className="size-8" />}
                    title="No products found"
                    hint="Add products to start selling"
                  />
                </div>
              ) : (
                <div className="grid grid-cols-2 gap-3 sm:grid-cols-3 2xl:grid-cols-4">
                  {productsToShow.map((product) => {
                    const stock = stockOf(product);
                    const low = stock <= (product.reorder_level || 0);
                    const out = stock <= 0;
                    return (
                      <button
                        key={product.id}
                        onClick={() => addToCart(product)}
                        disabled={out}
                        className={cn(
                          'group flex flex-col overflow-hidden rounded-2xl border bg-white text-left transition-all active:scale-[0.97]',
                          out
                            ? 'cursor-not-allowed border-slate-200 opacity-55'
                            : 'border-slate-200 hover:border-slate-900 hover:shadow-lg',
                        )}
                      >
                        <div className={cn('relative flex h-20 items-center justify-center', tileColor(product.name).split(' ')[0])}>
                          <span className={cn('text-4xl font-black', tileColor(product.name).split(' ')[1])}>
                            {product.name.charAt(0).toUpperCase()}
                          </span>
                          <span className={cn(
                            'absolute top-2 right-2 rounded-full px-2 py-0.5 text-[11px] font-bold tabular-nums shadow-sm',
                            out ? 'bg-slate-900 text-white' : low ? 'bg-amber-500 text-white' : 'bg-white/90 text-slate-700',
                          )}>
                            {out ? 'Out' : stock}
                          </span>
                        </div>
                        <div className="flex flex-1 flex-col p-2.5">
                          <span className="line-clamp-2 min-h-9 text-[13px] leading-snug font-semibold text-slate-900">{product.name}</span>
                          <span className="mt-1 text-[11px] text-slate-400">{product.unit?.symbol || product.unit?.name || 'pc'} · {product.sku}</span>
                          <span className="mt-1.5 text-lg font-black tracking-tight text-slate-900 tabular-nums">
                            ${Number(product.selling_price).toFixed(2)}
                          </span>
                        </div>
                      </button>
                    );
                  })}
                </div>
              )}
            </Deferred>
          </div>
        </div>

        {/* Right ticket panel — desktop fixed 400px */}
        <div className="hidden min-h-0 w-[400px] shrink-0 flex-col bg-slate-100 p-3 lg:flex">
          <div className="flex min-h-0 flex-1 flex-col overflow-hidden rounded-2xl border border-slate-200 bg-slate-50 shadow-sm">
            <div className="flex h-13 shrink-0 items-center justify-between bg-slate-900 px-4 py-3 text-white">
              <h2 className="flex items-center gap-2 text-[13px] font-extrabold tracking-widest uppercase">
                <Receipt className="size-4" />
                Current Sale
                <span className="rounded-full bg-white/20 px-2 py-0.5 text-xs font-bold tabular-nums">{itemCount}</span>
              </h2>
              <div className="flex items-center gap-1">
                {hasPermission('pos.hold_order') && (
                  <button onClick={handleHold} title="Hold order" className="rounded-lg p-1.5 text-white/70 hover:bg-white/10 hover:text-white">
                    <Pause className="size-4" />
                  </button>
                )}
                {cart.length > 0 && (
                  <button onClick={clearCart} title="Clear cart" className="rounded-lg p-1.5 text-white/70 hover:bg-white/10 hover:text-red-300">
                    <Trash2 className="size-4" />
                  </button>
                )}
              </div>
            </div>
            {ticketBody}
          </div>
        </div>
      </div>

      {/* Mobile sticky bottom bar */}
      <div className="shrink-0 border-t border-slate-200 bg-white px-3 pt-2 pb-[max(0.75rem,env(safe-area-inset-bottom))] lg:hidden">
        <button
          type="button"
          onClick={() => setShowCartSheet(true)}
          className="flex h-14 w-full items-center justify-between rounded-2xl bg-slate-900 px-4 text-white shadow-lg active:scale-[0.99]"
        >
          <span className="flex items-center gap-2 text-sm font-semibold">
            <ShoppingCart className="size-5" />
            View cart
            <span className="rounded-full bg-white/20 px-2 py-0.5 text-xs font-bold tabular-nums">{itemCount}</span>
          </span>
          <span className="text-xl font-black tabular-nums">{formatCurrency(total)}</span>
        </button>
      </div>

      {/* Mobile cart bottom sheet */}
      {showCartSheet && (
        <div className="fixed inset-0 z-50 lg:hidden">
          <div className="absolute inset-0 bg-black/60" onClick={() => setShowCartSheet(false)} />
          <div className="absolute inset-x-0 bottom-0 flex max-h-[92dvh] flex-col overflow-hidden rounded-t-3xl bg-slate-50 shadow-2xl">
            <div className="flex shrink-0 items-center justify-between bg-slate-900 px-4 py-3 text-white">
              <h2 className="flex items-center gap-2 text-[13px] font-extrabold tracking-widest uppercase">
                <Receipt className="size-4" />
                Current Sale
                <span className="rounded-full bg-white/20 px-2 py-0.5 text-xs font-bold tabular-nums">{itemCount}</span>
              </h2>
              <button onClick={() => setShowCartSheet(false)} className="rounded-lg bg-white/10 p-1.5 hover:bg-white/20" aria-label="Close cart">
                <X className="size-5" />
              </button>
            </div>
            <div className="flex min-h-0 flex-1 flex-col overflow-hidden">
              {ticketBody}
            </div>
          </div>
        </div>
      )}

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
            <Button onClick={handleCheckout} disabled={isProcessing} className="bg-blue-600 text-white hover:bg-blue-700">
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

      {/* Hold / held-orders dialog */}
      <Dialog open={showHoldDialog} onOpenChange={setShowHoldDialog}>
        <DialogContent className="max-w-sm rounded-2xl">
          <DialogHeader>
            <DialogTitle>{cart.length > 0 ? 'Hold Current Sale' : 'Held Orders'}</DialogTitle>
          </DialogHeader>
          {cart.length > 0 ? (
            <>
              <p className="text-sm text-slate-500">The current cart ({cart.length} items, {formatCurrency(total)}) will be parked so you can serve the next customer.</p>
              <DialogFooter>
                <Button variant="outline" onClick={() => setShowHoldDialog(false)}>Cancel</Button>
                <Button onClick={confirmHold} disabled={isProcessing} className="bg-blue-600 text-white hover:bg-blue-700">
                  {isProcessing ? <Loader2 className="size-4 animate-spin mr-2" /> : null} Hold Sale
                </Button>
              </DialogFooter>
            </>
          ) : (
            <>
              <div className="max-h-64 space-y-1 overflow-y-auto">
                {heldLoading && <p className="py-4 text-center text-sm text-slate-500">Loading…</p>}
                {!heldLoading && heldOrders.length === 0 && <p className="py-4 text-center text-sm text-slate-500">No held orders.</p>}
                {heldOrders.map((o: any) => (
                  <div key={o.id} className="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-100">
                    <div className="min-w-0 flex-1">
                      <p className="truncate text-sm font-semibold">{o.order_number}</p>
                      <p className="text-xs text-slate-500 tabular-nums">{o.items?.length ?? 0} items · {formatCurrency(Number(o.total ?? 0))}</p>
                    </div>
                    <Button size="sm" onClick={() => resumeHeld(o)} className="h-8 rounded-lg bg-blue-600 text-white hover:bg-blue-700">Resume</Button>
                  </div>
                ))}
              </div>
              <DialogFooter>
                <Button variant="outline" onClick={() => setShowHoldDialog(false)}>Close</Button>
              </DialogFooter>
            </>
          )}
        </DialogContent>
      </Dialog>

      {/* Receipt dialog */}
      <Dialog open={receipt !== null} onOpenChange={(v) => { if (!v) setReceipt(null); }}>
        <DialogContent className="max-w-sm rounded-2xl">
          <DialogHeader>
            <DialogTitle className="flex items-center gap-2"><Check className="size-5 text-emerald-600" /> Sale Complete</DialogTitle>
          </DialogHeader>
          {receipt && (
            <div className="space-y-2 text-sm">
              <div className="flex justify-between"><span className="text-slate-500">Invoice</span><span className="font-mono font-bold">{receipt.invoice_no}</span></div>
              <div className="flex justify-between"><span className="text-slate-500">Customer</span><span className="font-medium">{receipt.customer}</span></div>
              <div className="max-h-40 space-y-1 overflow-y-auto border-y border-slate-100 py-2">
                {receipt.lines.map((l: any, i: number) => (
                  <div key={i} className="flex justify-between gap-2">
                    <span className="min-w-0 flex-1 truncate">{l.name} × {l.qty}</span>
                    <span className="font-semibold tabular-nums">{formatCurrency(l.qty * l.price)}</span>
                  </div>
                ))}
              </div>
              <div className="flex justify-between text-base font-extrabold"><span>Total</span><span className="tabular-nums">{formatCurrency(receipt.total)}</span></div>
              <div className="flex justify-between"><span className="text-slate-500">Paid ({receipt.method})</span><span className="font-medium tabular-nums">{formatCurrency(receipt.paid)}</span></div>
              <div className="flex justify-between"><span className="text-slate-500">Change</span><span className="font-medium tabular-nums">{formatCurrency(receipt.change)}</span></div>
              <p className="text-xs text-slate-400">{receipt.time}</p>
            </div>
          )}
          <DialogFooter>
            <Button variant="outline" onClick={() => setReceipt(null)}>New Sale</Button>
            <Button onClick={() => window.print()} className="bg-blue-600 text-white hover:bg-blue-700"><Receipt className="size-4 mr-2" /> Print</Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      {/* Toasts */}
      <Toaster toasts={toasts} onClose={(id) => setToasts(prev => prev.filter(t => t.id !== id))} />
    </AppLayout>
  );
}
