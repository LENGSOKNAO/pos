import * as React from 'react';
import { cva, type VariantProps } from 'class-variance-authority';
import { cn } from '@/lib/utils';
import { X, AlertCircle, CheckCircle, Info, AlertTriangle } from 'lucide-react';

const alertVariants = cva(
  'relative w-full rounded-lg border p-4 [&>svg]:absolute [&>svg]:left-4 [&>svg]:top-4 [&>svg]:size-5 [&>div]:pl-10',
  {
    variants: {
      variant: {
        default: 'bg-slate-50 border-slate-200 text-slate-900',
        destructive: 'bg-red-50 border-red-200 text-red-900',
        success: 'bg-emerald-50 border-emerald-200 text-emerald-900',
        warning: 'bg-amber-50 border-amber-200 text-amber-900',
        info: 'bg-blue-50 border-blue-200 text-blue-900',
      },
    },
    defaultVariants: { variant: 'default' },
  }
);

export interface AlertProps
  extends React.HTMLAttributes<HTMLDivElement>,
    VariantProps<typeof alertVariants> {
  title?: string;
  description?: string;
  onClose?: () => void;
}

const Alert = React.forwardRef<HTMLDivElement, AlertProps>(
  ({ className, variant, title, description, onClose, children, ...props }, ref) => {
    const icons = {
      default: <Info className="text-slate-500" />,
      destructive: <AlertCircle className="text-red-500" />,
      success: <CheckCircle className="text-emerald-500" />,
      warning: <AlertTriangle className="text-amber-500" />,
      info: <Info className="text-blue-500" />,
    };

    return (
      <div
        ref={ref}
        role="alert"
        className={cn(alertVariants({ variant }), className)}
        {...props}
      >
        <div className="flex">
          {icons[variant] || icons.default}
          <div className="flex-1">
            {title && <h5 className="mb-1 font-medium leading-none">{title}</h5>}
            {description && <div className="text-sm opacity-90">{description}</div>}
            {children && <div className="mt-2">{children}</div>}
          </div>
          {props.onClose && (
            <button
              onClick={props.onClose}
              className="absolute right-3 top-3 rounded-sm opacity-50 hover:opacity-100 transition-opacity"
              aria-label="Dismiss"
            >
              <X className="size-4" />
            </button>
          )}
        </div>
      </div>
    );
  }
);
Alert.displayName = 'Alert';

export { Alert };