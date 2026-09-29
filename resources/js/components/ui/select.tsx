import * as React from 'react';
import { cn } from '@/lib/utils';

export interface SelectProps extends React.SelectHTMLAttributes<HTMLSelectElement> {
  label?: string;
  error?: string;
  helperText?: string;
}

const Select = React.forwardRef<HTMLSelectElement, SelectProps>(
  ({ className, label, error, helperText, id, ...props }, ref) => {
    const inputId = id || React.useId();
    
    return (
      <div className="w-full">
        {label && (
          <label htmlFor={inputId} className="block text-sm font-medium text-slate-700 mb-1.5">
            {label}
          </label>
        )}
        <select
          ref={ref}
          id={inputId}
          className={cn(
            'flex h-10 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm transition-colors focus-visible:border-blue-500 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-100 disabled:cursor-not-allowed disabled:opacity-50',
            error && 'border-red-500 focus:ring-red-500',
            className
          )}
          {...props}
        />
        {error && <p className="mt-1.5 text-sm text-red-600">{error}</p>}
        {helperText && !error && <p className="mt-1.5 text-sm text-slate-500">{helperText}</p>}
      </div>
    );
  }
);

Select.displayName = 'Select';

export { Select };