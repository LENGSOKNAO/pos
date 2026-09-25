import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, Banknote, Barcode, CheckCircle2, Clock3, Minus, Pause, Play, Plus, Search, Store, Trash2, User, X } from 'lucide-react';
import { useEffect, useMemo, useRef, useState } from 'react';
import AppLayout from '@/layouts/app-layout';
import PosController from '@/actions/App/Http/Controllers/PosController';
import { dashboard } from '@/routes';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { ScrollArea } from '@/components/ui/scroll-area';
import { Separator } from '@/components/ui/separator';
import { cn } from '@/lib/utils';

interface PosUnit {
    id: number;
    unit?: string | null;
    sale_price: number;
    stock?: number;
}

interface PosProduct {
    id: number;
    name: string;
    sku: string;
    sale_price: number;
    category?: string | null;
    units: PosUnit[];
}

interface CartLine {
    product_unit_id: number;
    name: string;
    quantity: number;
    unit_price: number;
    stock?: number;
}

interface ReceiptPayment {
    payment_method_id?: number | null;
    method?: string;
    amount: number;
}

interface ReceiptItem {
    quantity: number;
    unit_price: number;
    line_total: number;
}

interface Receipt {
    id: number;
    invoice_no: string;
    created_at?: string;
    cashier?: string | null;
    customer?: { id: number; name: string; code: string } | null;
    subtotal: number;
    discount_amount: number;
    grand_total: number;
    paid_amount: number;
    change: number;
    items: ReceiptItem[];
    payments?: ReceiptPayment[];
}

interface HeldOrder {
    id: string;
    at: string;
    cart: CartLine[];
    customerId: number | null;
    discount: string;
    discountType: 'fixed' | 'percent';
}

interface PayRow {
    id: number;
    payment_method_id: number | null;
    amount: string;
}

interface Props {
    products: PosProduct[];
    warehouses: { id: number; name: string }[];
    paymentMethods: { id: number; name: string; code: string }[];
    customers: { id: number; name: string; code: string }[];
    filters: { search: string };
    receipt?: Receipt | null;
}

const fmt = (n: number) => n.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
const HELD_KEY = 'pos-held-orders';

function loadHeld(): HeldOrder[] {
    try {
        return JSON.parse(localStorage.getItem(HELD_KEY) ?? '[]');
    } catch {
        return [];
    }
}

function categoryHue(name: string | null | undefined): number {
    const s = name ?? 'Uncategorized';
    let h = 0;
    for (let i = 0; i < s.length; i++) {
        h = (h * 31 + s.charCodeAt(i)) % 360;
    }
    return h;
}

export default function PosIndex({ products, warehouses, paymentMethods, customers, filters, receipt }: Props) {
    const [search, setSearch] = useState(filters.search ?? '');
    const [barcode, setBarcode] = useState('');
    const [barcodeError, setBarcodeError] = useState('');
    const [category, setCategory] = useState('All');
    const [cart, setCart] = useState<CartLine[]>([]);
    const [warehouseId, setWarehouseId] = useState<number>(warehouses[0]?.id ?? 0);
    const [customerId, setCustomerId] = useState<number | null>(null);
    const [discount, setDiscount] = useState('');
    const [discountType, setDiscountType] = useState<'fixed' | 'percent'>('fixed');
    const [tendered, setTendered] = useState('');
    const [open, setOpen] = useState(false);
    const [heldOpen, setHeldOpen] = useState(false);
    const [held, setHeld] = useState<HeldOrder[]>([]);
    const [processing, setProcessing] = useState(false);
    const [success, setSuccess] = useState(false);
    const [clock, setClock] = useState(new Date());
    const [payRows, setPayRows] = useState<PayRow[]>([{ id: 1, payment_method_id: paymentMethods[0]?.id ?? null, amount: '' }]);
    const searchRef = useRef<HTMLInputElement>(null);
    const barcodeRef = useRef<HTMLInputElement>(null);
    const tenderedRef = useRef<HTMLInputElement>(null);
    const payId = useRef(2);

    useEffect(() => {
        const t = setInterval(() => setClock(new Date()), 1000);
        return () => clearInterval(t);
    }, []);

    useEffect(() => {
        setHeld(loadHeld());
    }, []);

    const [receiptOpen, setReceiptOpen] = useState(!!receipt);
    useEffect(() => {
        if (receipt) {
            setReceiptOpen(true);
            setCart([]);
            setTendered('');
            setDiscount('');
            setSuccess(true);
        }
    }, [receipt?.id]); // eslint-disable-line react-hooks/exhaustive-deps

    useEffect(() => {
        const onKey = (e: KeyboardEvent) => {
            if (e.key === 'F2') {
                e.preventDefault();
                (barcodeRef.current ?? searchRef.current)?.focus();
            } else if (e.key === 'F9') {
                e.preventDefault();
                tenderedRef.current?.focus();
            }
        };
        window.addEventListener('keydown', onKey);
        return () => window.removeEventListener('keydown', onKey);
    }, []);

    const categories = useMemo(() => {
        const set = new Set<string>();
        products.forEach((p) => {
            if (p.category) {
                set.add(p.category);
            }
        });
        return ['All', ...[...set].sort()];
    }, [products]);

    const filtered = useMemo(() => {
        const q = search.trim().toLowerCase();
        return products.filter((p) => {
            const matchCat = category === 'All' || p.category === category;
            const matchQ = !q || p.name.toLowerCase().includes(q) || p.sku.toLowerCase().includes(q);
            return matchCat && matchQ;
        });
    }, [products, search, category]);

    const subtotal = useMemo(() => cart.reduce((s, l) => s + l.quantity * l.unit_price, 0), [cart]);
    const discountRaw = Number(discount) || 0;
    const discountNum = discountType === 'percent' ? Math.min(Math.max(discountRaw, 0), 100) * subtotal / 100 : Math.min(discountRaw, subtotal);
    const total = subtotal - discountNum;
    const paid = Number(tendered) || 0;
    const change = paid - total;
    const discountValid = discountType === 'percent' ? discountRaw >= 0 && discountRaw <= 100 : discountRaw <= subtotal;
    const orderNo = useMemo(() => `ORD-${clock.getHours()}${clock.getMinutes()}${String(cart.length).padStart(2, '0')}`, [clock, cart.length]);

    // Split-payment derived state (charge dialog)
    const splitTotal = payRows.reduce((s, r) => s + (Number(r.amount) || 0), 0);
    const remaining = total - splitTotal;
    const splitValid = payRows.length > 0 && payRows.every((r) => r.payment_method_id && (Number(r.amount) || 0) > 0) && remaining <= 0.01;

    function fillRemaining(id: number) {
        setPayRows((prev) => {
            const others = prev.filter((r) => r.id !== id).reduce((s, r) => s + (Number(r.amount) || 0), 0);
            return prev.map((r) => (r.id === id ? { ...r, amount: String(Math.max(0, Math.round((total - others) * 100) / 100)) } : r));
        });
    }

    function addLine(product_unit_id: number, name: string, unit_price: number, stock?: number) {
        if ((stock ?? Infinity) <= 0) {
            return;
        }
        setCart((prev) => {
            const found = prev.find((l) => l.product_unit_id === product_unit_id);
            if (found) {
                if (stock !== undefined && found.quantity + 1 > stock) {
                    return prev;
                }
                return prev.map((l) => (l.product_unit_id === product_unit_id ? { ...l, quantity: l.quantity + 1 } : l));
            }
            return [...prev, { product_unit_id, name, quantity: 1, unit_price, stock }];
        });
        setSuccess(false);
    }

    function addToCart(p: PosProduct, u: PosUnit) {
        const price = Number(u.sale_price) || Number(p.sale_price) || 0;
        addLine(u.id, `${p.name}${u.unit ? ` (${u.unit})` : ''}`, price, u.stock);
    }

    async function scanBarcode(code: string) {
        const c = code.trim();
        if (!c) {
            return;
        }
        setBarcodeError('');
        try {
            const res = await fetch(`${PosController.index.url()}?barcode=${encodeURIComponent(c)}`, {
                headers: { Accept: 'application/json' },
            });
            if (!res.ok) {
                setBarcodeError('Barcode not found.');
                return;
            }
            const data = await res.json();
            addLine(data.product_unit_id, `${data.product.name}${data.unit ? ` (${data.unit})` : ''}`, Number(data.sale_price) || 0);
            setBarcode('');
        } catch {
            setBarcodeError('Barcode lookup failed.');
        }
        barcodeRef.current?.focus();
    }

    function bump(id: number, delta: number) {
        setCart((prev) =>
            prev
                .map((l) => {
                    if (l.product_unit_id !== id) {
                        return l;
                    }
                    const next = l.quantity + delta;
                    if (delta > 0 && l.stock !== undefined && next > l.stock) {
                        return l;
                    }
                    return { ...l, quantity: next };
                })
                .filter((l) => l.quantity > 0),
        );
    }

    function holdCart() {
        if (cart.length === 0) {
            return;
        }
        const order: HeldOrder = { id: `${Date.now()}`, at: new Date().toLocaleString(), cart, customerId, discount, discountType };
        const next = [...held, order];
        setHeld(next);
        localStorage.setItem(HELD_KEY, JSON.stringify(next));
        setCart([]);
        setCustomerId(null);
        setDiscount('');
    }

    function resumeHeld(order: HeldOrder) {
        setCart(order.cart);
        setCustomerId(order.customerId);
        setDiscount(order.discount);
        setDiscountType(order.discountType ?? 'fixed');
        const next = held.filter((h) => h.id !== order.id);
        setHeld(next);
        localStorage.setItem(HELD_KEY, JSON.stringify(next));
        setHeldOpen(false);
    }

    function deleteHeld(id: string) {
        const next = held.filter((h) => h.id !== id);
        setHeld(next);
        localStorage.setItem(HELD_KEY, JSON.stringify(next));
    }

    function openCharge() {
        // Seed first split row with legacy tendered value when present
        if (tendered && payRows.length === 1 && !payRows[0].amount) {
            setPayRows([{ ...payRows[0], amount: tendered }]);
        }
        setOpen(true);
    }

    function confirmCharge() {
        if (cart.length === 0 || !warehouseId || !discountValid || !splitValid) {
            return;
        }
        setProcessing(true);
        router.post(
            PosController.store.url(),
            {
                warehouse_id: warehouseId,
                payment_method_id: payRows[0]?.payment_method_id,
                customer_id: customerId,
                discount_type: discountType,
                discount_amount: discountRaw,
                paid_amount: splitTotal,
                payments: payRows.map((r) => ({ payment_method_id: r.payment_method_id, amount: Number(r.amount) || 0 })),
                items: cart.map((l) => ({ product_unit_id: l.product_unit_id, quantity: l.quantity, unit_price: l.unit_price })),
            },
            {
                onFinish: () => setProcessing(false),
                onSuccess: () => {
                    setOpen(false);
                },
            },
        );
    }

    function newSale() {
        setReceiptOpen(false);
        setSuccess(false);
        setTendered('');
        setDiscount('');
        setCustomerId(null);
        setPayRows([{ id: payId.current++, payment_method_id: paymentMethods[0]?.id ?? null, amount: '' }]);
    }

    return (
        <AppLayout fullscreen>
            <Head title="POS Terminal" />
            <style>{`@media print { body * { visibility: hidden; } #pos-receipt, #pos-receipt * { visibility: visible; } #pos-receipt { position: absolute; left: 0; top: 0; width: 100%; } }`}</style>
            {/* Terminal header */}
            <header className="flex h-16 items-center gap-4 border-b border-slate-800 bg-slate-950 px-5 text-white">
                <Link href={dashboard.url()}>
                    <Button variant="ghost" size="icon" className="text-slate-300 hover:bg-slate-800 hover:text-white focus-visible:ring-2 focus-visible:ring-blue-500" title="Back to dashboard">
                        <ArrowLeft className="size-5" />
                    </Button>
                </Link>
                <span className="flex size-9 items-center justify-center rounded-lg bg-blue-600 text-lg font-black text-white shadow-[0_0_18px_rgba(37,99,235,0.6)]">$</span>
                <div className="leading-tight">
                    <p className="text-sm font-bold text-slate-100">POS Terminal</p>
                    <p className="flex items-center gap-1 text-xs text-slate-400">
                        <Store className="size-3" /> {warehouses.find((w) => w.id === warehouseId)?.name ?? 'Store'}
                    </p>
                </div>
                <Badge variant="secondary" className="ml-2 border-slate-700 bg-slate-800 text-slate-200">{orderNo}</Badge>
                <div className="ml-auto flex items-center gap-3 text-sm">
                    <Button variant="outline" size="sm" onClick={() => setHeldOpen(true)} className="border-blue-800 bg-blue-950 text-blue-100 hover:bg-blue-900 focus-visible:ring-2 focus-visible:ring-blue-400">
                        <Pause className="size-4" /> Held ({held.length})
                    </Button>
                    <span className="flex items-center gap-1.5 text-slate-300">
                        <Clock3 className="size-4" />
                        {clock.toLocaleTimeString()}
                    </span>
                    <span className="flex items-center gap-1.5 text-slate-300">
                        <User className="size-4" /> Cashier
                    </span>
                </div>
            </header>

            <div className="flex h-[calc(100vh-4rem)] flex-col bg-slate-950 lg:flex-row">
                {/* LEFT: cart / order panel */}
                <aside className="flex w-full shrink-0 flex-col border-b border-slate-800 bg-slate-900 text-slate-100 lg:h-auto lg:w-[400px] lg:border-r lg:border-b-0">
                    <div className="grid grid-cols-2 gap-2 border-b border-slate-800 p-4">
                        <div>
                            <label className="text-xs font-semibold tracking-wide text-slate-400 uppercase">Warehouse</label>
                            <select
                                value={warehouseId}
                                onChange={(e) => setWarehouseId(Number(e.target.value))}
                                className="mt-1 h-10 w-full rounded-lg border border-slate-700 bg-slate-800 px-2 text-sm font-medium text-slate-100 focus:border-blue-500 focus:ring-2 focus:ring-blue-500 focus:outline-none"
                            >
                                {warehouses.map((w) => (
                                    <option key={w.id} value={w.id}>{w.name}</option>
                                ))}
                            </select>
                        </div>
                        <div>
                            <label className="text-xs font-semibold tracking-wide text-slate-400 uppercase">Customer</label>
                            <select
                                value={customerId ?? ''}
                                onChange={(e) => setCustomerId(e.target.value ? Number(e.target.value) : null)}
                                className="mt-1 h-10 w-full rounded-lg border border-slate-700 bg-slate-800 px-2 text-sm font-medium text-slate-100 focus:border-blue-500 focus:ring-2 focus:ring-blue-500 focus:outline-none"
                            >
                                <option value="">Walk-in</option>
                                {customers.map((c) => (
                                    <option key={c.id} value={c.id}>{c.name} ({c.code})</option>
                                ))}
                            </select>
                        </div>
                    </div>
                    <div className="flex gap-2 border-b border-slate-800 p-3">
                        <Button variant="outline" size="sm" className="flex-1 border-slate-700 bg-slate-800 text-slate-200 hover:bg-slate-700 focus-visible:ring-2 focus-visible:ring-blue-500" onClick={holdCart} disabled={cart.length === 0}>
                            <Pause className="size-4" /> Hold
                        </Button>
                    </div>
                    <ScrollArea className="max-h-64 flex-1 p-3 lg:max-h-none">
                        {cart.length === 0 ? (
                            <p className="py-10 text-center text-sm text-slate-500">Cart is empty — tap a product tile or scan.</p>
                        ) : (
                            cart.map((l) => (
                                <div key={l.product_unit_id} className="mb-2 rounded-2xl border border-slate-800 bg-slate-800/80 p-3 shadow-sm">
                                    <div className="flex items-start justify-between gap-2">
                                        <div className="flex min-w-0 items-start gap-2">
                                            <span className="mt-0.5 flex size-8 shrink-0 items-center justify-center rounded-xl bg-blue-600/20 text-xs font-black text-blue-300">
                                                {l.name.charAt(0).toUpperCase()}
                                            </span>
                                            <div className="min-w-0">
                                                <p className="truncate text-sm font-semibold text-slate-100">{l.name}</p>
                                                <p className="text-xs text-slate-400 tabular-nums">${fmt(l.unit_price)} each</p>
                                            </div>
                                        </div>
                                        <button onClick={() => bump(l.product_unit_id, -l.quantity)} aria-label="Remove line" className="rounded-lg p-1 text-slate-400 hover:bg-red-950 hover:text-red-400 focus-visible:ring-2 focus-visible:ring-blue-500">
                                            <X className="size-4" />
                                        </button>
                                    </div>
                                    <div className="mt-2 flex items-center justify-between">
                                        <div className="flex items-center gap-1.5">
                                            <Button size="icon" variant="outline" className="size-8 rounded-xl border-slate-700 bg-slate-900 text-slate-200 hover:bg-slate-700" onClick={() => bump(l.product_unit_id, -1)}>
                                                <Minus className="size-4" />
                                            </Button>
                                            <span className="w-8 text-center text-sm font-bold text-slate-100 tabular-nums">{l.quantity}</span>
                                            <Button size="icon" variant="outline" className="size-8 rounded-xl border-slate-700 bg-slate-900 text-slate-200 hover:bg-slate-700" onClick={() => bump(l.product_unit_id, 1)}>
                                                <Plus className="size-4" />
                                            </Button>
                                        </div>
                                        <p className="text-sm font-bold text-slate-100 tabular-nums">${fmt(l.quantity * l.unit_price)}</p>
                                    </div>
                                </div>
                            ))
                        )}
                    </ScrollArea>
                    {/* Sticky bottom checkout bar */}
                    <div className="sticky bottom-0 space-y-1.5 border-t border-slate-800 bg-slate-900 p-4 text-sm shadow-[0_-4px_16px_rgba(0,0,0,0.5)]">
                        <div className="flex justify-between text-slate-300"><span>Subtotal</span><span className="font-semibold tabular-nums">${fmt(subtotal)}</span></div>
                        <div className="flex items-center justify-between gap-2 text-slate-300">
                            <span className="flex items-center gap-1.5">Discount
                                <span className="flex overflow-hidden rounded-lg border border-slate-700">
                                    {(['fixed', 'percent'] as const).map((t) => (
                                        <button
                                            key={t}
                                            onClick={() => setDiscountType(t)}
                                            className={cn('px-2 py-1 text-[11px] font-bold uppercase focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-inset', discountType === t ? 'bg-blue-600 text-white' : 'bg-slate-800 text-slate-400 hover:text-slate-200')}
                                        >
                                            {t === 'fixed' ? '$' : '%'}
                                        </button>
                                    ))}
                                </span>
                            </span>
                            <Input
                                value={discount}
                                onChange={(e) => setDiscount(e.target.value)}
                                inputMode="decimal"
                                placeholder={discountType === 'percent' ? '0–100' : '0.00'}
                                className={cn('h-9 w-28 border-slate-700 bg-slate-800 text-right font-semibold text-slate-100 placeholder:text-slate-500 focus-visible:ring-blue-500', !discountValid && 'border-red-500 text-red-400')}
                            />
                        </div>
                        {!discountValid && <p className="text-right text-xs text-red-400">{discountType === 'percent' ? 'Percent must be 0–100.' : 'Discount cannot exceed subtotal.'}</p>}
                        <div className="flex justify-between rounded-xl bg-blue-600 px-3 py-2.5 text-2xl font-extrabold text-white shadow-[0_0_24px_rgba(37,99,235,0.5)] tabular-nums"><span className="text-sm font-semibold self-center">Total</span><span>${fmt(total)}</span></div>
                        <Separator className="my-2 bg-slate-800" />
                        <label className="text-xs font-semibold tracking-wide text-slate-400 uppercase">Tendered (F9)</label>
                        <Input
                            ref={tenderedRef}
                            value={tendered}
                            onChange={(e) => setTendered(e.target.value)}
                            onKeyDown={(e) => {
                                if (e.key === 'Enter') {
                                    openCharge();
                                }
                            }}
                            inputMode="decimal"
                            placeholder="0.00"
                            className="h-11 border-slate-700 bg-slate-800 text-lg font-bold text-slate-100 placeholder:text-slate-500 focus-visible:ring-blue-500"
                        />
                        <div className="grid grid-cols-4 gap-1.5 pt-1">
                            {[1, 5, 10, 20, 50, 100, total, total + 10].map((v, i) => (
                                <Button key={i} variant="outline" size="sm" className="h-9 border-slate-700 bg-slate-800 text-slate-200 hover:bg-slate-700" onClick={() => setTendered(String(Math.max(0, Math.round(Number(v) * 100) / 100)))}>
                                    {Number(v) >= 1000 ? `${Math.round(Number(v))}` : `$${fmt(Number(v))}`}
                                </Button>
                            ))}
                        </div>
                        <div className={cn('flex justify-between rounded-lg px-3 py-2 font-bold', change >= 0 && paid > 0 ? 'bg-emerald-950 text-emerald-300' : 'bg-slate-800 text-slate-400')}>
                            <span className="flex items-center gap-1"><Banknote className="size-4" /> Change</span>
                            <span>${fmt(paid > 0 ? Math.max(0, change) : 0)}</span>
                        </div>
                        <Button onClick={openCharge} disabled={cart.length === 0 || !discountValid} className="h-14 w-full rounded-xl bg-blue-500 text-lg font-extrabold text-white shadow-[0_0_28px_rgba(59,130,246,0.55)] hover:bg-blue-600 focus-visible:ring-2 focus-visible:ring-blue-300">
                            CHARGE ${fmt(total)}
                        </Button>
                    </div>
                </aside>

                {/* RIGHT: catalog */}
                <div className="flex min-w-0 flex-1 flex-col bg-slate-950">
                    <div className="sticky top-0 z-10 border-b border-slate-800 bg-slate-950">
                        <div className="flex items-center gap-2 p-3">
                            <div className="relative max-w-xs flex-1">
                                <Search className="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-slate-500" />
                                <Input
                                    ref={searchRef}
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    onKeyDown={(e) => {
                                        if (e.key === 'Enter') {
                                            router.get(PosController.index.url(), { search }, { preserveState: true });
                                        }
                                    }}
                                    placeholder="Search name or SKU… (Enter, F2)"
                                    className="h-11 border-slate-700 bg-slate-900 pl-9 text-base text-slate-100 placeholder:text-slate-500 focus-visible:ring-blue-500"
                                />
                            </div>
                            <div className="relative w-56">
                                <Barcode className="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-slate-500" />
                                <Input
                                    ref={barcodeRef}
                                    autoFocus
                                    value={barcode}
                                    onChange={(e) => setBarcode(e.target.value)}
                                    onKeyDown={(e) => {
                                        if (e.key === 'Enter') {
                                            scanBarcode(barcode);
                                        }
                                    }}
                                    placeholder="Scan barcode… (Enter)"
                                    className="h-11 border-slate-700 bg-slate-900 pl-9 font-mono text-base text-slate-100 placeholder:text-slate-500 focus-visible:ring-blue-500"
                                />
                            </div>
                        </div>
                        <div className="flex gap-2 overflow-x-auto px-3 pb-3">
                            {categories.map((c) => (
                                <Button
                                    key={c}
                                    size="sm"
                                    variant={category === c ? 'default' : 'outline'}
                                    onClick={() => setCategory(c)}
                                    className={cn('h-10 shrink-0 px-5 text-sm focus-visible:ring-2 focus-visible:ring-blue-400', category === c ? 'bg-blue-600 text-white hover:bg-blue-700' : 'border-slate-700 bg-transparent text-slate-300 hover:bg-slate-800 hover:text-white')}
                                >
                                    {c}
                                </Button>
                            ))}
                        </div>
                    </div>
                    {barcodeError && <p className="bg-red-950 px-4 py-1.5 text-sm font-medium text-red-300">{barcodeError}</p>}
                    <ScrollArea className="flex-1 p-4">
                        {success && !receipt && (
                            <div className="mb-3 flex items-center gap-2 rounded-lg bg-emerald-950 px-4 py-3 text-sm font-medium text-emerald-300">
                                <CheckCircle2 className="size-4" /> Payment successful — cart cleared.
                            </div>
                        )}
                        <div className="grid grid-cols-2 gap-3 sm:grid-cols-3 xl:grid-cols-3 2xl:grid-cols-4">
                            {filtered.map((p) => {
                                const units = p.units.length > 0 ? p.units : [{ id: 0, unit: null, sale_price: p.sale_price }];
                                const stock = units[0]?.stock;
                                const out = stock !== undefined && stock <= 0;
                                const hue = categoryHue(p.category);
                                return (
                                    <div
                                        key={p.id}
                                        role="button"
                                        tabIndex={out || units[0].id === 0 ? -1 : 0}
                                        aria-disabled={out || units[0].id === 0}
                                        onClick={() => units[0].id !== 0 && addToCart(p, units[0] as PosUnit)}
                                        onKeyDown={(e) => {
                                            if (e.key === 'Enter' || e.key === ' ') {
                                                e.preventDefault();
                                                units[0].id !== 0 && addToCart(p, units[0] as PosUnit);
                                            }
                                        }}
                                        className="relative min-h-36 cursor-pointer overflow-hidden rounded-2xl border border-slate-800 bg-slate-900 text-left shadow-[0_2px_12px_rgba(0,0,0,0.4)] transition hover:border-blue-500 hover:ring-2 hover:ring-blue-500/40 focus-visible:ring-2 focus-visible:ring-blue-400 focus-visible:outline-none active:scale-[0.98] aria-disabled:cursor-not-allowed aria-disabled:opacity-50"
                                    >
                                        <span className="block h-1.5 w-full" style={{ backgroundColor: `hsl(${hue} 80% 55%)`, boxShadow: `0 0 12px hsl(${hue} 80% 55% / 0.7)` }} />
                                        <span className="block p-3">
                                            <span className="flex items-start justify-between gap-2">
                                                <span className="line-clamp-2 min-h-10 flex-1 text-sm leading-snug font-semibold text-slate-100">{p.name}</span>
                                                {stock !== undefined && (
                                                    <Badge variant={out ? 'destructive' : 'secondary'} className="absolute top-3 right-3 shrink-0 border-slate-700 bg-slate-800 font-mono text-[11px] text-slate-200 tabular-nums">
                                                        {out ? 'Out' : `×${stock}`}
                                                    </Badge>
                                                )}
                                            </span>
                                            <span className="mt-1 block font-mono text-[11px] text-slate-500">{p.sku}</span>
                                            <span className="mt-1 block text-lg font-extrabold text-blue-400 tabular-nums">${fmt(Number(p.sale_price) || 0)}</span>
                                            {units.length > 1 ? (
                                                <span className="mt-2 flex flex-wrap gap-1.5" onClick={(e) => e.stopPropagation()} onKeyDown={(e) => e.stopPropagation()}>
                                                    {(units as PosUnit[]).map((u) => (
                                                        <button
                                                            key={u.id}
                                                            disabled={(u.stock ?? 1) <= 0}
                                                            onClick={() => addToCart(p, u)}
                                                            className="min-h-9 rounded-xl border border-slate-700 bg-slate-800 px-2.5 py-1.5 text-xs font-semibold text-slate-200 hover:border-blue-500 hover:bg-blue-950 hover:text-blue-200 focus-visible:ring-2 focus-visible:ring-blue-400 disabled:opacity-40"
                                                        >
                                                            {u.unit ?? 'Default'}
                                                        </button>
                                                    ))}
                                                </span>
                                            ) : (
                                                <span className="mt-1 block text-xs font-semibold text-blue-400">{out ? 'Out of stock' : 'Tap to add +'}</span>
                                            )}
                                        </span>
                                    </div>
                                );
                            })}
                            {filtered.length === 0 && <p className="col-span-full py-10 text-center text-slate-500">No products found.</p>}
                        </div>
                    </ScrollArea>
                </div>
            </div>

            <Dialog open={open} onOpenChange={setOpen}>
                <DialogContent className="max-h-[90vh] overflow-y-auto">
                    <DialogHeader>
                        <DialogTitle>Confirm payment — ${fmt(total)}</DialogTitle>
                        <DialogDescription>Add one or more payment rows. Remaining must reach $0.00 before confirming.</DialogDescription>
                    </DialogHeader>
                    <div className="space-y-2">
                        {payRows.map((r) => (
                            <div key={r.id} className="flex items-center gap-2">
                                <select
                                    value={r.payment_method_id ?? ''}
                                    onChange={(e) => setPayRows((prev) => prev.map((x) => (x.id === r.id ? { ...x, payment_method_id: e.target.value ? Number(e.target.value) : null } : x)))}
                                    className="h-10 flex-1 rounded-lg border px-2 text-sm font-medium"
                                >
                                    <option value="">Method…</option>
                                    {paymentMethods.map((m) => (
                                        <option key={m.id} value={m.id}>{m.name} ({m.code})</option>
                                    ))}
                                </select>
                                <Input
                                    value={r.amount}
                                    onChange={(e) => setPayRows((prev) => prev.map((x) => (x.id === r.id ? { ...x, amount: e.target.value } : x)))}
                                    inputMode="decimal"
                                    placeholder="0.00"
                                    className="h-10 w-28 text-right font-semibold"
                                />
                                <Button variant="ghost" size="sm" onClick={() => fillRemaining(r.id)} title="Fill remaining">Fill</Button>
                                {payRows.length > 1 && (
                                    <Button variant="ghost" size="icon" onClick={() => setPayRows((prev) => prev.filter((x) => x.id !== r.id))} aria-label="Remove payment row">
                                        <Trash2 className="size-4" />
                                    </Button>
                                )}
                            </div>
                        ))}
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={() => setPayRows((prev) => [...prev, { id: payId.current++, payment_method_id: paymentMethods[0]?.id ?? null, amount: '' }])}
                        >
                            <Plus className="size-4" /> Add payment
                        </Button>
                        <div className={cn('flex justify-between rounded-lg px-3 py-2 text-sm font-bold', remaining <= 0.01 ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700')}>
                            <span>Remaining</span><span>${fmt(Math.max(0, remaining))}</span>
                        </div>
                        {remaining < -0.01 && <p className="text-xs text-slate-500">Overpay of ${fmt(-remaining)} will be returned as change.</p>}
                    </div>
                    <DialogFooter className="gap-2">
                        <Button variant="outline" onClick={() => setOpen(false)}>Cancel</Button>
                        <Button onClick={confirmCharge} disabled={processing || !splitValid} className="bg-blue-600 hover:bg-blue-700">
                            {processing ? 'Processing…' : `Confirm $${fmt(total)}`}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <Dialog open={heldOpen} onOpenChange={setHeldOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Held orders ({held.length})</DialogTitle>
                        <DialogDescription>Resume a parked cart or discard it.</DialogDescription>
                    </DialogHeader>
                    <div className="max-h-80 space-y-2 overflow-y-auto">
                        {held.length === 0 && <p className="py-6 text-center text-sm text-slate-500">No held orders.</p>}
                        {held.map((h) => (
                            <div key={h.id} className="flex items-center justify-between gap-2 rounded-lg border p-3">
                                <div className="text-sm">
                                    <p className="font-bold">{h.cart.length} lines · ${fmt(h.cart.reduce((s, l) => s + l.quantity * l.unit_price, 0))}</p>
                                    <p className="text-xs text-slate-500">{h.at}</p>
                                </div>
                                <div className="flex gap-1.5">
                                    <Button size="sm" onClick={() => resumeHeld(h)}><Play className="size-4" /> Resume</Button>
                                    <Button size="sm" variant="outline" onClick={() => deleteHeld(h.id)}><X className="size-4" /></Button>
                                </div>
                            </div>
                        ))}
                    </div>
                </DialogContent>
            </Dialog>

            <Dialog open={receiptOpen && !!receipt} onOpenChange={setReceiptOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-2"><CheckCircle2 className="size-5 text-emerald-600" /> Sale complete</DialogTitle>
                    </DialogHeader>
                    {receipt && (
                        <div id="pos-receipt" className="mx-auto max-w-sm bg-white font-mono text-sm text-black">
                            <div className="text-center">
                                <p className="text-lg font-black">SquarePOS</p>
                                <p className="text-xs text-slate-500">{receipt.invoice_no} · {receipt.created_at}</p>
                                <p className="text-xs text-slate-500">Cashier: {receipt.cashier ?? '—'}</p>
                                {receipt.customer && <p className="text-xs text-slate-500">Customer: {receipt.customer.name} ({receipt.customer.code})</p>}
                            </div>
                            <div className="my-3 border-t border-dashed border-slate-300" />
                            {receipt.items.map((i, idx) => (
                                <div key={idx} className="flex justify-between py-0.5 tabular-nums">
                                    <span>×{i.quantity} @ ${fmt(Number(i.unit_price))}</span>
                                    <span className="font-bold">${fmt(Number(i.line_total))}</span>
                                </div>
                            ))}
                            <div className="my-3 border-t border-dashed border-slate-300" />
                            <div className="space-y-1 tabular-nums">
                                <div className="flex justify-between"><span>Subtotal</span><span>${fmt(Number(receipt.subtotal))}</span></div>
                                <div className="flex justify-between"><span>Discount</span><span>-${fmt(Number(receipt.discount_amount))}</span></div>
                                <div className="flex justify-between text-base font-black"><span>Total</span><span>${fmt(Number(receipt.grand_total))}</span></div>
                                {(receipt.payments ?? []).map((p, idx) => (
                                    <div key={idx} className="flex justify-between text-xs"><span>{p.method ?? 'Payment'}</span><span>${fmt(Number(p.amount))}</span></div>
                                ))}
                                <div className="flex justify-between"><span>Tendered</span><span>${fmt(Number(receipt.paid_amount))}</span></div>
                                <div className="flex justify-between"><span>Change</span><span>${fmt(Math.max(0, Number(receipt.change)))}</span></div>
                            </div>
                            <div className="my-3 border-t border-dashed border-slate-300" />
                            <p className="text-center text-xs text-slate-400">*** Thank you for shopping! ***</p>
                        </div>
                    )}
                    <DialogFooter className="gap-2">
                        <Button variant="outline" onClick={() => window.print()}>Print</Button>
                        <Button onClick={newSale} className="bg-blue-600 hover:bg-blue-700">New Sale</Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
