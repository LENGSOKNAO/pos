import * as React from 'react';
import { cn } from '@/lib/utils';

export interface FormFieldProps {
  label?: string;
  error?: string;
  helperText?: string;
  required?: boolean;
  children: React.ReactNode;
  className?: string;
}

export function FormField({ label, error, helperText, required, children, className }: FormFieldProps) {
  const id = React.useId();

  return (
    <div className={cn('space-y-1.5', className)}>
      {label && (
        <label className={cn('block text-sm font-medium text-slate-700')}>
          {label}
          {required && <span className="text-red-500 ml-1" aria-hidden="true">*</span>}
        </label>
      )}
      <div className="relative">{children}</div>
      {error && <p className="text-sm text-red-600" role="alert">{error}</p>}
      {helperText && !error && <p className="text-sm text-slate-500">{helperText}</p>}
    </div>
  );
}

export interface FormSectionProps {
  title: string;
  description?: string;
  children: React.ReactNode;
  className?: string;
}

export function FormSection({ title, description, children, className }: FormSectionProps) {
  return (
    <div className={cn('space-y-4', className)}>
      <div>
        <h3 className="text-lg font-semibold text-slate-900">{title}</h3>
        {description && <p className="text-sm text-slate-500 mt-1">{description}</p>}
      </div>
      <div className="pt-4 border-t border-slate-200">{children}</div>
    </div>
  );
}

export interface FormActionsProps {
  children: React.ReactNode;
  className?: string;
}

export function FormActions({ children, className }: FormActionsProps) {
  return (
    <div className={cn('flex flex-wrap items-center justify-end gap-3 pt-4 border-t border-slate-200', className)}>
      {children}
    </div>
  );
}