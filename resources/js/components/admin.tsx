import type { ReactNode } from 'react';
import { Search } from 'lucide-react';
import { cn } from '@/lib/utils';

export function PageHeader({
    title,
    count,
    description,
    actions,
}: {
    title: string;
    count?: string | number;
    description?: string;
    actions?: ReactNode;
}) {
    return (
        <div className="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div className="min-w-0">
                <div className="flex items-center gap-2.5">
                    <h1 className="text-xl font-extrabold tracking-tight text-slate-900">{title}</h1>
                    {count !== undefined && (
                        <span className="rounded-full bg-slate-900 px-2.5 py-0.5 text-xs font-bold text-white tabular-nums">
                            {count}
                        </span>
                    )}
                </div>
                {description && <p className="mt-1 text-sm text-slate-500">{description}</p>}
            </div>
            {actions && <div className="flex shrink-0 flex-wrap items-center gap-2">{actions}</div>}
        </div>
    );
}

export function SearchInput({
    value,
    onChange,
    onSubmit,
    placeholder,
    className,
}: {
    value: string;
    onChange: (v: string) => void;
    onSubmit: () => void;
    placeholder: string;
    className?: string;
}) {
    return (
        <form
            className={cn('relative w-full sm:max-w-xs', className)}
            onSubmit={(e) => {
                e.preventDefault();
                onSubmit();
            }}
        >
            <Search className="absolute top-1/2 left-3.5 size-4 -translate-y-1/2 text-slate-400" />
            <input
                value={value}
                onChange={(e) => onChange(e.target.value)}
                placeholder={placeholder}
                className="h-10 w-full rounded-xl border border-slate-200 bg-white pr-3 pl-10 text-sm shadow-[0_1px_2px_rgba(15,23,42,0.04)] outline-none placeholder:text-slate-400 focus:border-slate-900 focus:ring-2 focus:ring-slate-900/10"
            />
        </form>
    );
}

export function EmptyState({ icon, title, hint }: { icon: ReactNode; title: string; hint?: string }) {
    return (
        <div className="flex flex-col items-center justify-center px-4 py-14 text-center">
            <span className="flex size-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-400 ring-1 ring-slate-200/70 ring-inset">{icon}</span>
            <p className="mt-3 text-sm font-bold text-slate-900">{title}</p>
            {hint && <p className="mt-1 max-w-xs text-sm text-slate-500">{hint}</p>}
        </div>
    );
}

export function TableShell({ children }: { children: ReactNode }) {
    return (
        <div className="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-[0_1px_2px_rgba(15,23,42,0.05)]">
            <table className="w-full text-sm">{children}</table>
        </div>
    );
}

export function TableHeadRow({ children }: { children: ReactNode }) {
    return (
        <thead className="bg-slate-50/80">
            <tr className="text-left text-[11px] font-semibold tracking-wider text-slate-500 uppercase">{children}</tr>
        </thead>
    );
}

export const thCls = 'h-11 px-4 font-semibold whitespace-nowrap';
export const tdCls = 'h-11 px-4 align-middle';

function Pulse({ className }: { className?: string }) {
    return <div className={cn('animate-pulse rounded-lg bg-slate-200/70', className)} />;
}

export function TableSkeleton({ rows = 6, cols = 5 }: { rows?: number; cols?: number }) {
    return (
        <div className="overflow-hidden rounded-2xl border border-slate-200/90 bg-white p-4 shadow-[0_1px_2px_rgba(15,23,42,0.05)]" aria-label="Loading">
            <div className="space-y-3">
                <Pulse className="h-8 w-full" />
                {Array.from({ length: rows }).map((_, i) => (
                    <div key={i} className="flex gap-3">
                        {Array.from({ length: cols }).map((_, j) => (
                            <Pulse key={j} className="h-6 flex-1" />
                        ))}
                    </div>
                ))}
            </div>
        </div>
    );
}

export function CardsSkeleton({ count = 4 }: { count?: number }) {
    return (
        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Loading">
            {Array.from({ length: count }).map((_, i) => (
                <div key={i} className="rounded-2xl border border-slate-200/90 bg-white p-5 shadow-[0_1px_2px_rgba(15,23,42,0.05)]">
                    <div className="flex items-center justify-between">
                        <Pulse className="h-4 w-1/3" />
                        <Pulse className="size-10 rounded-xl" />
                    </div>
                    <Pulse className="mt-3 h-8 w-2/3" />
                    <Pulse className="mt-2 h-3 w-1/3" />
                </div>
            ))}
        </div>
    );
}

export function RowsSkeleton({ count = 4 }: { count?: number }) {
    return (
        <div className="space-y-2" aria-label="Loading">
            {Array.from({ length: count }).map((_, i) => (
                <div key={i} className="flex items-center gap-3 rounded-2xl border border-slate-200/90 bg-white px-4 py-3">
                    <Pulse className="size-10 shrink-0 rounded-xl" />
                    <div className="flex-1 space-y-1.5">
                        <Pulse className="h-4 w-2/3" />
                        <Pulse className="h-3 w-1/3" />
                    </div>
                    <Pulse className="h-6 w-16 rounded-full" />
                </div>
            ))}
        </div>
    );
}

export function GridSkeleton({ count = 10 }: { count?: number }) {
    return (
        <div className="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5" aria-label="Loading">
            {Array.from({ length: count }).map((_, i) => (
                <div key={i} className="rounded-2xl border border-slate-200/90 bg-white p-4">
                    <Pulse className="h-3 w-1/2" />
                    <Pulse className="mt-2 h-4 w-3/4" />
                    <Pulse className="mt-2 h-5 w-1/3" />
                </div>
            ))}
        </div>
    );
}
