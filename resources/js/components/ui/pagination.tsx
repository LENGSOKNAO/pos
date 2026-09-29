import * as React from 'react';
import { router } from '@inertiajs/react';
import { cn } from '@/lib/utils';
import { ChevronLeft, ChevronRight, ChevronsLeft, ChevronsRight } from 'lucide-react';

export interface PaginationProps {
  currentPage: number;
  lastPage: number;
  perPage: number;
  total: number;
  onPageChange: (page: number) => void;
  showPerPageSelector?: boolean;
  perPageOptions?: number[];
  buildUrl?: (page: number) => string;
  prefetchOnly?: string[];
  prefetchData?: (page: number) => Record<string, unknown>;
}

export function Pagination({
  currentPage,
  lastPage,
  perPage,
  total,
  onPageChange,
  showPerPageSelector = true,
  perPageOptions = [10, 15, 25, 50, 100],
  buildUrl,
  prefetchOnly,
  prefetchData,
}: PaginationProps) {
  // Preload neighboring pages so clicking them shows instantly without
  // waiting on the database. Same `only` + params as the click handler,
  // so the cached response is reused on click.
  React.useEffect(() => {
    if (!buildUrl) return;
    for (const page of new Set([currentPage - 1, currentPage + 1])) {
      if (page < 1 || page > lastPage || page === currentPage) continue;
      try {
        router.prefetch(buildUrl(page), { only: prefetchOnly, data: prefetchData?.(page) });
      } catch {
        // Prefetch is best-effort; clicks still work without it.
      }
    }
  }, [buildUrl, currentPage, lastPage, prefetchOnly, prefetchData]);

  if (lastPage <= 1) return null;

  const pages = React.useMemo(() => {
    const result: (number | string)[] = [];
    const delta = 2;

    for (let i = 1; i <= lastPage; i++) {
      if (
        i === 1 ||
        i === lastPage ||
        (i >= currentPage - delta && i <= currentPage + delta)
      ) {
        result.push(i);
      } else if (
        result[result.length - 1] !== '...' &&
        i !== 1 &&
        i !== lastPage
      ) {
        result.push('...');
      }
    }
    return result;
  }, [currentPage, lastPage]);

  return (
    <nav className="flex items-center justify-between px-4 py-3" aria-label="Pagination">
      <div className="flex items-center gap-4">
        <p className="text-sm text-slate-600">
          Showing {(currentPage - 1) * perPage + 1} to {Math.min(currentPage * perPage, total)} of {total} results
        </p>

        <select
          value={perPage}
          onChange={(e) => onPageChange(1)}
          className="h-8 w-auto rounded-md border border-slate-200 bg-white px-2 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
        >
          {perPageOptions.map((option) => (
            <option key={option} value={option}>
              {option} per page
            </option>
          ))}
        </select>
      </div>

      <div className="flex items-center gap-1">
        <button
          onClick={() => onPageChange(1)}
          disabled={currentPage === 1}
          className={cn(
            'p-2 rounded-lg text-slate-500 hover:bg-slate-100 disabled:opacity-50 disabled:cursor-not-allowed transition-colors',
            'lg:hidden'
          )}
          aria-label="First page"
        >
          <ChevronsLeft className="size-4" />
        </button>

        <button
          onClick={() => onPageChange(currentPage - 1)}
          disabled={currentPage === 1}
          className="p-2 rounded-lg text-slate-500 hover:bg-slate-100 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
          aria-label="Previous page"
        >
          <ChevronLeft className="size-4" />
        </button>

        <div className="flex items-center gap-1 hidden lg:flex">
          {pages.map((page, index) =>
            page === '...' ? (
              <span key={`ellipsis-${index}`} className="px-2 text-slate-400">
                ...
              </span>
            ) : (
              <button
                key={page}
                onClick={() => onPageChange(page as number)}
                className={cn(
                  'px-3 py-1.5 rounded-lg text-sm font-medium transition-colors',
                  currentPage === page
                    ? 'bg-blue-600 text-white'
                    : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'
                )}
              >
                {page}
              </button>
            )
          )}
        </div>

        <button
          onClick={() => onPageChange(currentPage + 1)}
          disabled={currentPage === lastPage}
          className="p-2 rounded-lg text-slate-500 hover:bg-slate-100 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
          aria-label="Next page"
        >
          <ChevronRight className="size-4" />
        </button>

        <button
          onClick={() => onPageChange(lastPage)}
          disabled={currentPage === lastPage}
          className={cn(
            'p-2 rounded-lg text-slate-500 hover:bg-slate-100 disabled:opacity-50 disabled:cursor-not-allowed transition-colors',
            'lg:hidden'
          )}
          aria-label="Last page"
        >
          <ChevronsRight className="size-4" />
        </button>
      </div>
    </nav>
  );
}