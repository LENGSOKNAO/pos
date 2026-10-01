import * as React from 'react';
import { cn } from '@/lib/utils';
import {
  Table,
  TableHeader,
  TableBody,
  TableRow,
  TableHead,
  TableCell,
} from '@/components/ui/table';
import { Pagination, PaginationProps } from '@/components/ui/pagination';
import { Checkbox } from '@/components/ui/checkbox';
import { ChevronUp, ChevronDown } from 'lucide-react';

export interface Column<T> {
  key: string;
  header: string;
  accessor: (item: T) => React.ReactNode;
  sortable?: boolean;
  width?: string;
  align?: 'left' | 'center' | 'right';
  className?: string;
}

interface DataTableProps<T> {
  columns: Column<T>[];
  data: T[];
  keyAccessor: (item: T) => string;
  isLoading?: boolean;
  emptyState?: React.ReactNode;
  onRowClick?: (item: T) => void;
  selection?: {
    selectedKeys: Set<string>;
    onSelectionChange: (keys: Set<string>) => void;
  };
  pagination?: PaginationProps;
  sortBy?: string;
  sortOrder?: 'asc' | 'desc';
  onSort?: (key: string, order: 'asc' | 'desc') => void;
  className?: string;
}

export function DataTable<T>({
  columns,
  data,
  keyAccessor,
  isLoading = false,
  emptyState,
  onRowClick,
  selection,
  pagination,
  sortBy,
  sortOrder,
  onSort,
  className,
}: DataTableProps<T>) {
  const [sortConfig, setSortConfig] = React.useState<{ key: string; order: 'asc' | 'desc' } | null>(
    sortBy ? { key: sortBy, order: sortOrder || 'asc' } : null
  );

  const handleSort = (key: string) => {
    if (!onSort) return;
    let order: 'asc' | 'desc' = 'asc';
    if (sortConfig?.key === key && sortConfig.order === 'asc') {
      order = 'desc';
    }
    setSortConfig({ key, order });
    onSort(key, order);
  };

  const handleSelectAll = () => {
    if (!selection) return;
    if (selection.selectedKeys.size === data.length) {
      selection.onSelectionChange(new Set());
    } else {
      selection.onSelectionChange(new Set(data.map(keyAccessor)));
    }
  };

  if (isLoading) {
    return (
      <div className="rounded-2xl border border-neutral-200 bg-white">
        <div className="overflow-auto">
          <Table>
            <TableHeader>
              <TableRow>
                {selection && (
                  <TableHead className="w-12 text-center">
                    <Checkbox
                      checked={selection.selectedKeys.size === data.length && data.length > 0}
                      indeterminate={selection.selectedKeys.size > 0 && selection.selectedKeys.size < data.length}
                      onCheckedChange={handleSelectAll}
                      aria-label="Select all"
                    />
                  </TableHead>
                )}
                {columns.map((column) => (
                  <TableHead
                    key={column.key}
                    className={cn(
                      column.align === 'center' && 'text-center',
                      column.align === 'right' && 'text-right',
                      column.className
                    )}
                    style={{ width: column.width }}
                  >
                    {column.sortable ? (
                      <button
                        onClick={() => handleSort(column.key)}
                        className="flex items-center gap-1 hover:text-neutral-900"
                      >
                        {column.header}
                        {sortConfig?.key === column.key && (
                          sortConfig.order === 'asc' ? (
                            <ChevronUp className="size-4" />
                          ) : (
                            <ChevronDown className="size-4" />
                          )
                        )}
                      </button>
                    ) : (
                      column.header
                    )}
                  </TableHead>
                ))}
              </TableRow>
            </TableHeader>
            <TableBody>
              {data.map((item) => (
                <TableRow
                  key={keyAccessor(item)}
                  onClick={() => onRowClick?.(item)}
                  className={cn(
                    onRowClick && 'cursor-pointer',
                    selection?.selectedKeys.has(keyAccessor(item)) && 'bg-brand-50'
                  )}
                  data-selected={selection?.selectedKeys.has(keyAccessor(item))}
                >
                  {selection && (
                    <TableCell className="w-12 text-center">
                      <Checkbox
                        checked={selection.selectedKeys.size === data.length && data.length > 0}
                        indeterminate={selection.selectedKeys.size > 0 && selection.selectedKeys.size < data.length}
                        onCheckedChange={handleSelectAll}
                        aria-label="Select all"
                      />
                    </TableCell>
                  )}
                  {columns.map((column) => (
                    <TableCell
                      key={column.key}
                      className={cn(
                        column.align === 'center' && 'text-center',
                        column.align === 'right' && 'text-right',
                        column.className
                      )}
                      style={{ width: column.width }}
                    >
                      {column.accessor(item)}
                    </TableCell>
                  ))}
                </TableRow>
              ))}
            </TableBody>
          </Table>
        </div>
        {pagination && (
          <Pagination
            {...pagination}
            onPageChange={pagination.onPageChange}
          />
        )}
      </div>
    );
  }

  if (data.length === 0) {
    return (
      <div className="rounded-2xl border border-neutral-200 bg-white p-12">
        {emptyState || (
          <div className="text-center py-8">
            <div className="flex h-12 w-12 items-center justify-center rounded-full bg-neutral-100 mx-auto mb-4 text-neutral-400">
              <svg className="size-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
            </div>
            <p className="text-neutral-500">No data available</p>
          </div>
        )}
      </div>
    );
  }

  return (
    <div className={cn('rounded-2xl border border-neutral-200 bg-white', className)}>
      <div className="overflow-auto">
        <Table>
          <TableHeader>
            <TableRow>
              {selection && (
                <TableHead className="w-12 text-center">
                  <Checkbox
                    checked={selection.selectedKeys.size === data.length && data.length > 0}
                    indeterminate={selection.selectedKeys.size > 0 && selection.selectedKeys.size < data.length}
                    onCheckedChange={handleSelectAll}
                    aria-label="Select all"
                  />
                </TableHead>
              )}
              {columns.map((column) => (
                <TableHead
                  key={column.key}
                  className={cn(
                    column.align === 'center' && 'text-center',
                    column.align === 'right' && 'text-right',
                    column.className
                  )}
                  style={{ width: column.width }}
                >
                  {column.sortable ? (
                    <button
                      onClick={() => handleSort(column.key)}
                      className="flex items-center gap-1 hover:text-neutral-900"
                    >
                      {column.header}
                      {sortConfig?.key === column.key && (
                        sortConfig.order === 'asc' ? (
                          <ChevronUp className="size-4" />
                        ) : (
                          <ChevronDown className="size-4" />
                        )
                      )}
                    </button>
                  ) : (
                    column.header
                  )}
                </TableHead>
              ))}
            </TableRow>
          </TableHeader>
          <TableBody>
            {data.map((item) => (
              <TableRow
                key={keyAccessor(item)}
                onClick={() => onRowClick?.(item)}
                className={cn(
                  onRowClick && 'cursor-pointer',
                  selection?.selectedKeys.has(keyAccessor(item)) && 'bg-brand-50'
                )}
                data-selected={selection?.selectedKeys.has(keyAccessor(item))}
              >
                {selection && (
                  <TableCell className="w-12 text-center">
                    <Checkbox
                      checked={selection.selectedKeys.has(keyAccessor(item))}
                      onCheckedChange={(checked) => {
                        const newKeys = new Set(selection.selectedKeys);
                        const key = keyAccessor(item);
                        if (checked) {
                          newKeys.add(key);
                        } else {
                          newKeys.delete(key);
                        }
                        selection.onSelectionChange(newKeys);
                      }}
                      aria-label="Select row"
                    />
                  </TableCell>
                )}
                {columns.map((column) => (
                  <TableCell
                    key={column.key}
                    className={cn(
                      column.align === 'center' && 'text-center',
                      column.align === 'right' && 'text-right',
                      column.className
                    )}
                    style={{ width: column.width }}
                  >
                    {column.accessor(item)}
                  </TableCell>
                ))}
              </TableRow>
            ))}
          </TableBody>
        </Table>
      </div>
      {pagination && (
        <Pagination
          {...pagination}
          onPageChange={pagination.onPageChange}
        />
      )}
    </div>
  );
}