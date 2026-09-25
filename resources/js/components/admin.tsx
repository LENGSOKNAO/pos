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
        <div className="mb-5 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div className="min-w-0">
                <div className="flex items-center gap-2">
                    <h1 className="text-xl font-bold tracking-tight text-slate-900">{title}</h1>
                    {count !== undefined && (
                        <span className="rounded-full bg-blue-50 px-2.5 py-0.5 text-xs font-bold text-blue-700 tabular-nums">
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
            <Search className="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-slate-400" />
            <input
                value={value}
                onChange={(e) => onChange(e.target.value)}
                placeholder={placeholder}
                className="h-10 w-full rounded-xl border border-slate-200 bg-white pr-3 pl-9 text-sm outline-none placeholder:text-slate-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
            />
        </form>
    );
}

export function EmptyState({ icon, title, hint }: { icon: ReactNode; title: string; hint?: string }) {
    return (
        <div className="flex flex-col items-center justify-center px-4 py-14 text-center">
            <span className="flex size-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">{icon}</span>
            <p className="mt-3 text-sm font-semibold text-slate-900">{title}</p>
            {hint && <p className="mt-1 max-w-xs text-sm text-slate-500">{hint}</p>}
        </div>
    );
}

export function TableShell({ children }: { children: ReactNode }) {
    return (
        <div className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <table className="w-full text-sm">{children}</table>
        </div>
    );
}

export function TableHeadRow({ children }: { children: ReactNode }) {
    return (
        <thead className="bg-slate-50">
            <tr className="text-left text-[11px] font-semibold tracking-wider text-slate-500 uppercase">{children}</tr>
        </thead>
    );
}

export const thCls = 'h-11 px-4 font-semibold whitespace-nowrap';
export const tdCls = 'h-11 px-4 align-middle';
