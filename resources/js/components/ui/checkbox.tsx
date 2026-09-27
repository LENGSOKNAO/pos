import * as React from 'react';
import { cn } from '@/lib/utils';
import { Check } from 'lucide-react';

export interface CheckboxProps extends React.InputHTMLAttributes<HTMLInputElement> {
  label?: string;
}

const Checkbox = React.forwardRef<HTMLInputElement, CheckboxProps>(
  ({ className, label, id, ...props }, ref) => {
    const checkboxId = id || React.useId();

    return (
      <div className="flex items-center gap-2">
        <div className="relative">
          <input
            type="checkbox"
            ref={ref}
            id={checkboxId}
            className={cn(
              'peer h-4 w-4 shrink-0 rounded-sm border border-slate-300 text-blue-600',
              'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500',
              'disabled:cursor-not-allowed disabled:opacity-50',
              'checked:bg-blue-600 checked:border-blue-600 checked:text-white',
              'transition-colors'
            )}
            {...props}
            ref={ref}
          />
          <div className="pointer-events-none absolute inset-0 flex items-center justify-center text-white">
            <Check className="size-3.5 opacity-0 peer-checked:opacity-100 transition-opacity" />
          </div>
        </div>
        {label && (
          <label htmlFor={checkboxId} className="text-sm text-slate-700 cursor-pointer">
            {label}
          </label>
        )}
      </div>
    );
  }
);

Checkbox.displayName = 'Checkbox';

export { Checkbox };