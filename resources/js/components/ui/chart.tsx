import { type ReactNode } from 'react';
import { cn } from '@/lib/utils';

type Point = { label: string; value: number };

function scale(values: number[], min = 0) {
    const max = Math.max(1, ...values);
    const range = max - min || 1;
    return (v: number) => min + ((v - min) / range) * 100;
}

/**
 * Smooth area chart with a baseline grid. Values are real; nothing is faked.
 */
export function AreaChart({
    data,
    height = 180,
    className,
    highlightIndex,
    formatValue = (v: number) => String(v),
}: {
    data: Point[];
    height?: number;
    className?: string;
    highlightIndex?: number;
    formatValue?: (value: number) => string;
}) {
    if (data.length === 0) return null;

    const values = data.map((d) => d.value);
    const y = scale(values);
    const stepX = data.length > 1 ? 100 / (data.length - 1) : 100;

    const coords = data.map((d, i) => ({ x: i * stepX, y: 100 - y(d.value) }));
    const line = coords.map((c) => `${c.x},${c.y}`).join(' L ');
    const area = `M 0,100 L ${coords.map((c) => `${c.x},${c.y}`).join(' L ')} L ${100},100 Z`;
    const active = highlightIndex ?? data.length - 1;
    const activePoint = coords[active];

    return (
        <div className={cn('relative', className)} style={{ height }}>
            <svg
                viewBox="0 0 100 100"
                preserveAspectRatio="none"
                className="h-full w-full overflow-visible"
                role="img"
                aria-label="Sales trend"
            >
                <defs>
                    <linearGradient id="area-fill" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0%" stopColor="var(--color-brand-500)" stopOpacity="0.22" />
                        <stop offset="100%" stopColor="var(--color-brand-500)" stopOpacity="0" />
                    </linearGradient>
                </defs>

                {[25, 50, 75].map((gy) => (
                    <line
                        key={gy}
                        x1="0"
                        x2="100"
                        y1={gy}
                        y2={gy}
                        stroke="currentColor"
                        strokeWidth="0.25"
                        className="text-slate-200"
                        vectorEffect="non-scaling-stroke"
                    />
                ))}

                <path d={area} fill="url(#area-fill)" />
                <path
                    d={`M ${line}`}
                    fill="none"
                    stroke="var(--color-brand-600)"
                    strokeWidth="2"
                    strokeLinecap="round"
                    strokeLinejoin="round"
                    vectorEffect="non-scaling-stroke"
                />

                {activePoint && (
                    <>
                        <line
                            x1={activePoint.x}
                            x2={activePoint.x}
                            y1="0"
                            y2="100"
                            stroke="var(--color-brand-300)"
                            strokeWidth="1"
                            strokeDasharray="3 3"
                            vectorEffect="non-scaling-stroke"
                        />
                        <circle cx={activePoint.x} cy={activePoint.y} r="1.6" className="fill-white stroke-brand-600" strokeWidth="0.8" />
                    </>
                )}
            </svg>

            <div className="pointer-events-none absolute inset-x-0 bottom-0 flex justify-between">
                {data.map((d, i) => (
                    <span
                        key={d.label}
                        className={cn(
                            'text-[11px] tabular-nums',
                            i === active ? 'font-bold text-brand-700' : 'font-medium text-slate-400',
                        )}
                    >
                        {d.label}
                    </span>
                ))}
            </div>

            {activePoint && (
                <div
                    className="pointer-events-none absolute -top-1 z-10 -translate-x-1/2 -translate-y-full rounded-lg bg-slate-900 px-2 py-1 text-[11px] font-bold whitespace-nowrap text-white shadow-sm"
                    style={{ left: `${activePoint.x}%` }}
                >
                    {formatValue(data[active].value)}
                </div>
            )}
        </div>
    );
}

/** Vertical bars, used where discrete comparison matters more than trend. */
export function ColumnChart({
    data,
    height = 160,
    className,
    highlightIndex,
    formatValue = (v: number) => String(v),
}: {
    data: Point[];
    height?: number;
    className?: string;
    highlightIndex?: number;
    formatValue?: (value: number) => string;
}) {
    if (data.length === 0) return null;
    const max = Math.max(1, ...data.map((d) => d.value));
    const active = highlightIndex ?? data.length - 1;

    return (
        <div className={cn('flex items-end gap-2', className)} style={{ height }}>
            {data.map((d, i) => (
                <div key={d.label} className="group flex h-full min-w-0 flex-1 flex-col justify-end gap-2">
                    <span
                        className={cn(
                            'text-center text-[11px] font-bold tabular-nums transition-opacity',
                            i === active ? 'text-brand-700 opacity-100' : 'text-slate-400 opacity-0 group-hover:opacity-100',
                        )}
                    >
                        {d.value > 0 ? formatValue(d.value) : ''}
                    </span>
                    <div
                        className={cn(
                            'w-full rounded-t-md transition-all duration-300',
                            i === active ? 'bg-brand-600' : 'bg-brand-200 group-hover:bg-brand-300',
                        )}
                        style={{ height: `${Math.max(3, (d.value / max) * 100)}%` }}
                        title={`${d.label}: ${formatValue(d.value)}`}
                    />
                    <span
                        className={cn(
                            'pb-1 text-center text-[11px] tabular-nums',
                            i === active ? 'font-bold text-brand-700' : 'font-medium text-slate-400',
                        )}
                    >
                        {d.label}
                    </span>
                </div>
            ))}
        </div>
    );
}

/** Proportion bar: label, share bar, amount. Replaces donut charts for 2-4 rows. */
export function MeterRow({
    label,
    value,
    total,
    amount,
    tone = 'brand',
}: {
    label: string;
    value: number;
    total: number;
    amount: string;
    tone?: 'brand' | 'success' | 'warning' | 'info';
}) {
    const pct = total > 0 ? Math.min(100, Math.max(0, (value / total) * 100)) : 0;
    const tones = {
        brand: 'bg-brand-600',
        success: 'bg-success-600',
        warning: 'bg-warning-600',
        info: 'bg-info-600',
    };

    return (
        <div className="px-3 py-2">
            <div className="flex items-baseline justify-between gap-3">
                <span className="truncate text-[13px] font-semibold text-slate-700">{label}</span>
                <span className="shrink-0 text-[13px] font-bold text-slate-900 tabular-nums">{amount}</span>
            </div>
            <div className="mt-1.5 flex items-center gap-2">
                <div className="h-1.5 flex-1 overflow-hidden rounded-full bg-slate-100">
                    <div className={cn('h-full rounded-full', tones[tone])} style={{ width: `${pct}%` }} />
                </div>
                <span className="w-9 shrink-0 text-right text-[11px] font-semibold text-slate-400 tabular-nums">
                    {pct.toFixed(0)}%
                </span>
            </div>
        </div>
    );
}

/** Compact KPI tile. Flat surface, hairline border, tinted icon, tabular figures. */
export function StatTile({
    label,
    value,
    hint,
    icon,
    tone = 'brand',
}: {
    label: string;
    value: ReactNode;
    hint?: ReactNode;
    icon?: ReactNode;
    tone?: 'brand' | 'success' | 'warning' | 'danger' | 'neutral';
}) {
    const tones = {
        brand: 'bg-brand-50 text-brand-600',
        success: 'bg-success-50 text-success-600',
        warning: 'bg-warning-50 text-warning-600',
        danger: 'bg-error-50 text-error-600',
        neutral: 'bg-slate-100 text-slate-500',
    };

    return (
        <div className="flex items-center gap-3 rounded-xl border border-slate-200 bg-white px-4 py-3.5">
            {icon && <span className={cn('flex size-10 shrink-0 items-center justify-center rounded-lg', tones[tone])}>{icon}</span>}
            <div className="min-w-0 flex-1">
                <p className="truncate text-[11px] font-semibold tracking-wide text-slate-500 uppercase">{label}</p>
                <p className="font-display mt-0.5 truncate text-xl font-extrabold tracking-tight text-slate-900 tabular-nums">{value}</p>
                {hint && <div className="mt-0.5 truncate text-[11px] font-medium text-slate-400">{hint}</div>}
            </div>
        </div>
    );
}
