import * as React from 'react';
import { cn } from '@/lib/utils';
import { X, AlertCircle, CheckCircle, Info, AlertTriangle } from 'lucide-react';

export interface ToastProps {
  id: string;
  type: 'success' | 'error' | 'warning' | 'info';
  title: string;
  message?: string;
  onClose: (id: string) => void;
  duration?: number;
}

const icons = {
  success: <CheckCircle className="text-emerald-500" />,
  error: <AlertCircle className="text-red-500" />,
  warning: <AlertTriangle className="text-amber-500" />,
  info: <Info className="text-blue-500" />,
};

const colors = {
  success: 'bg-emerald-50 border-emerald-200 text-emerald-900',
  error: 'bg-red-50 border-red-200 text-red-900',
  warning: 'bg-amber-50 border-amber-200 text-amber-900',
  info: 'bg-blue-50 border-blue-200 text-blue-900',
};

export function Toast({
  id,
  type,
  title,
  message,
  onClose,
  duration = 5000,
}: ToastProps) {
  React.useEffect(() => {
    const timer = setTimeout(() => onClose(id), duration);
    return () => clearTimeout(timer);
  }, [id, duration, onClose]);

  return (
    <div
      className={cn(
        'relative flex w-full max-w-sm items-start gap-3 rounded-lg border p-4 shadow-lg animate-in slide-in-from-right',
        colors[type]
      )}
      role="alert"
    >
      <div className="flex shrink-0 items-center">
        {type === 'success' && <CheckCircle className="size-5 text-emerald-500" />}
        {type === 'error' && <AlertCircle className="size-5 text-red-500" />}
        {type === 'warning' && <AlertTriangle className="size-5 text-amber-500" />}
        {type === 'info' && <Info className="size-5 text-blue-500" />}
      </div>
      <div className="flex-1">
        <h5 className="font-medium">{title}</h5>
        {message && <p className="mt-1 text-sm opacity-90">{message}</p>}
      </div>
      <button
        onClick={() => onClose(id)}
        className="flex-shrink-0 rounded-sm opacity-50 hover:opacity-100 transition-opacity"
        aria-label="Dismiss"
      >
        <X className="size-4" />
      </button>
    </div>
  );
}

export interface ToasterProps {
  toasts: Array<{
    id: string;
    type: 'success' | 'error' | 'warning' | 'info';
    title: string;
    message?: string;
  }>;
  onClose: (id: string) => void;
}

export function Toaster({ toasts, onClose }: ToasterProps) {
  return (
    <div className="fixed bottom-4 right-4 z-50 flex flex-col gap-2 w-full max-w-sm">
      {toasts.map((toast) => (
        <Toast
          key={toast.id}
          id={toast.id}
          type={toast.type}
          title={toast.title}
          message={toast.message}
          onClose={onClose}
        />
      ))}
    </div>
  );
}