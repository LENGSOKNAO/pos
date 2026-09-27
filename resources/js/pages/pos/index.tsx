import { Head } from '@inertiajs/react';
import { useState, useEffect, useRef } from 'react';
import { Link, router, useForm, usePage } from '@inertiajs/react';
import {
  Plus, Minus, Trash2, Search, UserPlus, CreditCard, Banknote, Smartphone,
  Receipt, RotateCcw, ShoppingCart, X, Check, AlertCircle, Loader2, Package
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
      
      <div className="flex h-full bg-slate-50">
        {/* Left Panel - Products */}
        <div className="flex flex-col w-full lg:w-3/5 border-r border-slate-200 bg-white">
          {/* Search & Filters */}
          <div className="p-4 border-b border-slate-200 bg-slate-50">
            <div className="flex flex-col gap-3 sm:flex-row sm:items-center">
              <div className="relative flex-1">
                <Search className="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-slate-400" />
                <input
                  type="search"
                  placeholder="Search products by name, SKU, or barcode..."
                  value={searchQuery}
                  onChange={(e) => setSearchQuery(e.target.value)}
                  className="h-10 w-full rounded-xl border border-slate-200 bg-white pr-3 pl-9 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                  ref={searchRef}
                  autoFocus
                />
              </div>
              <Select
                value={selectedCategory}
                onChange={(e) => setSelectedCategory(e.target.value)}
                className="w-full sm:w-48"
              >
                <option value="">All Categories</option>
                {safeCategories.map(c => <option key={c.id} value={c.id}>{c.name}</option>)}
              </Select>
            </div>
          </div>

          {/* Product Grid */}
          <div className="flex-1 overflow-y-auto p-4">
            {productsToShow.length === 0 ? (
              <EmptyState
                icon={<Package className="size-8" />}
                title="No products found"
                hint="Add products to start selling"
              />
            ) : (
              <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-3">
                {productsToShow.map(product => (
                  <button
                    key={product.id}
                    onClick={() => addToCart(product)}
                    disabled={stockOf(product) <= 0}
                    className={cn(
                      'relative flex flex-col items-start gap-2 rounded-xl border p-3 transition-all',
                      'hover:border-blue-300 hover:bg-blue-50 hover:shadow-md',
                      'disabled:opacity-50 disabled:cursor-not-allowed'
                    )}
                  >
                    <div className="flex items-center justify-between w-full">
                      <span className="text-xs font-medium text-slate-500 truncate">{product.sku}</span>
                      <Badge variant={stockOf(product) <= (product.reorder_level || 0) ? 'warning' : 'secondary'}>
                        {stockOf(product)}
                      </Badge>
                    </div>
                    <h3 className="font-medium text-slate-900 line-clamp-1">{product.name}</h3>
                    <div className="flex items-center justify-between w-full mt-auto">
                      <span className="text-lg font-bold text-slate-900 tabular-nums">
                        ${Number(product.selling_price).toFixed(2)}
                      </span>
                      <span className="text-xs text-slate-500">
                        {product.unit?.symbol || 'pc'}
                      </span>
                    </div>
                  </button>
                ))}
              </div>
            )}
          </div>
        </div>

        {/* Right Panel - Cart & Payment */}
        <div className="flex flex-col w-full lg:w-2/5 bg-slate-50">
          {/* Cart Header */}
          <div className="flex h-16 items-center justify-between border-b border-slate-200 bg-white px-4">
            <h2 className="text-lg font-semibold text-slate-900">Cart ({cart.length})</h2>
            <div className="flex items-center gap-2">
              {cart.length > 0 && (
                <Button variant="ghost" size="sm" onClick={clearCart} className="text-red-600 hover:text-red-700">
                  <Trash2 className="size-4" /> Clear
                </Button>
              )}
              {cart.length > 0 && hasPermission('pos.hold_order') && (
                <Button variant="outline" size="sm" onClick={handleHold}>
                  <RotateCcw className="size-4" /> Hold
                </Button>
              )}
            </div>
          </div>

          {/* Cart Items */}
          <div className="flex-1 overflow-y-auto p-4">
            {cart.length === 0 ? (
              <EmptyState
                icon={<ShoppingCart className="size-8" />}
                title="Cart is empty"
                hint="Add products from the left panel"
              />
            ) : (
              <div className="space-y-3">
                {cart.map(item => (
                  <div key={item.id} className="flex gap-3 rounded-xl border border-slate-200 bg-white p-3">
                    <div className="flex-1 min-w-0">
                      <div className="flex items-start justify-between gap-2">
                        <div className="min-w-0">
                          <p className="font-medium text-slate-900 truncate">{item.name}</p>
                          <p className="text-xs text-slate-500">{item.sku}</p>
                        </div>
                        <button
                          onClick={() => removeFromCart(item.id)}
                          className="p-1 rounded-lg text-slate-400 hover:bg-slate-100 hover:text-red-600"
                          aria-label="Remove item"
                        >
                          <X className="size-4" />
                        </button>
                      </div>
                      <div className="flex items-center gap-2 mt-2">
                        <span className="text-lg font-bold text-slate-900 tabular-nums">
                          ${Number(item.unit_price).toFixed(2)}
                        </span>
                        <div className="flex items-center border border-slate-200 rounded-lg">
                          <button
                            onClick={() => updateQuantity(item.id, -1)}
                            className="p-1.5 text-slate-500 hover:bg-slate-100"
                            aria-label="Decrease quantity"
                          >
                            <Minus className="size-4" />
                          </button>
                          <span className="w-10 text-center font-mono font-medium">{item.quantity}</span>
                          <button
                            onClick={() => updateQuantity(item.id, 1)}
                            className="p-1.5 text-slate-500 hover:bg-slate-100"
                            aria-label="Increase quantity"
                          >
                            <Plus className="size-4" />
                          </button>
                        </div>
                      </div>
                    </div>
                    <div className="w-24 text-right">
                      <p className="text-lg font-bold text-slate-900 tabular-nums">
                        ${Number(item.unit_price * item.quantity).toFixed(2)}
                      </p>
                    </div>
                  </div>
                ))}
              </div>
            )}
          </div>

          {/* Cart Summary */}
          <div className="border-t border-slate-200 bg-white p-4">
            <div className="space-y-2 mb-4">
              <div className="flex justify-between text-sm">
                <span className="text-slate-600">Subtotal</span>
                <span className="font-medium tabular-nums">{formatCurrency(subtotal)}</span>
              </div>
              {totalDiscount > 0 && (
                <div className="flex justify-between text-sm text-red-600">
                  <span>Discount</span>
                  <span className="font-medium">-{formatCurrency(totalDiscount)}</span>
                </div>
              )}
              {totalTax > 0 && (
                <div className="flex justify-between text-sm">
                  <span className="text-slate-600">Tax</span>
                  <span className="font-medium tabular-nums">{formatCurrency(totalTax)}</span>
                </div>
              )}
              <div className="flex justify-between text-lg font-bold border-t border-slate-200 pt-2">
                <span>Total</span>
                <span className="tabular-nums">{formatCurrency(total)}</span>
              </div>
            </div>

            {/* Customer Selection */}
            <div className="mb-4">
              <label className="block text-sm font-medium text-slate-700 mb-2">Customer</label>
              <div className="flex gap-2">
                <Select
                  value={selectedCustomer?.id || ''}
                  onChange={(e) => {
                    const customer = safeCustomers.find((c: any) => c.id === e.target.value);
                    setSelectedCustomer(customer || null);
                  }}
                  className="flex-1"
                >
                  <option value="">Walk-in Customer</option>
                  {safeCustomers.map((c: any) => (
                    <option key={c.id} value={c.id}>
                      {c.name} ({c.customer_code})
                    </option>
                  ))}
                </Select>
                <Button variant="outline" size="sm" onClick={() => setShowCustomerDialog(true)}>
                  <UserPlus className="size-4" />
                </Button>
              </div>
              {selectedCustomer && (
                <div className="mt-2 p-2 rounded-lg bg-blue-50 text-sm">
                  <p className="font-medium">{selectedCustomer.name}</p>
                  <p className="text-slate-600">Credit: {formatCurrency((selectedCustomer.credit_limit || 0) - (selectedCustomer.due_amount || 0))} / {formatCurrency(selectedCustomer.credit_limit || 0)}</p>
                  <p className="text-slate-600">Loyalty: {selectedCustomer.loyalty_points || 0} pts</p>
                </div>
              )}
            </div>

            {/* Payment Method */}
            <div className="mb-4">
              <label className="block text-sm font-medium text-slate-700 mb-2">Payment Method</label>
              <Select
                value={paymentMethod?.id || ''}
                onChange={(e) => {
                  const method = safePaymentMethods.find(p => p.id === e.target.value);
                  setPaymentMethod(method || null);
                }}
                className="w-full"
              >
                {safePaymentMethods.map(p => (
                  <option key={p.id} value={p.id}>
                    {p.name} ({p.type})
                  </option>
                ))}
              </Select>
            </div>

            {/* Payment Amount */}
            <div className="mb-4">
              <label className="block text-sm font-medium text-slate-700 mb-2">Amount Received</label>
              <Input
                type="number"
                step="0.01"
                value={amountReceived}
                onChange={(e) => setAmountReceived(parseFloat(e.target.value) || 0)}
                placeholder={formatCurrency(total)}
                className="text-lg font-bold"
              />
              <div className="flex justify-between text-sm mt-1">
                <span className={change >= 0 ? 'text-emerald-600' : 'text-red-600'}>
                  Change: {formatCurrency(change)}
                </span>
                <span className="font-bold tabular-nums">
                  Due: {formatCurrency(Math.max(0, total - amountReceived))}
                </span>
              </div>
            </div>

            {/* Checkout Button */}
            <Button
              onClick={handleCheckout}
              disabled={cart.length === 0 || isProcessing || amountReceived < total}
              className="w-full h-14 text-lg font-semibold rounded-xl"
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

      {/* Toasts */}
      <Toaster toasts={toasts} onClose={(id) => setToasts(prev => prev.filter(t => t.id !== id))} />
    </AppLayout>
  );
}