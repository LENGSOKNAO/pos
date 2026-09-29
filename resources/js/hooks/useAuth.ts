import { usePage } from '@inertiajs/react';

export interface User {
  id: string;
  username: string;
  email: string;
  employee_id: string;
  last_login: string | null;
  status: string;
  employee?: {
    id: string;
    first_name: string;
    last_name: string;
    phone: string;
    email: string;
    position: string;
    branch_id: string;
    company_id: string;
    status: string;
    branch?: {
      id: string;
      name: string;
      company_id: string;
    };
    company?: {
      id: string;
      name: string;
      currency: string;
      timezone: string;
    };
  };
  roles?: Array<{
    id: string;
    name: string;
    description: string;
    permissions?: Array<{
      id: string;
      code: string;
      name: string;
      module: string;
    }>;
  }>;
}

export function useAuth() {
  const { props } = usePage<{ auth?: { user?: User }; authProfile?: User }>();
  // Identity arrives instantly; full profile (employee, roles, permissions)
  // streams in deferred. Merge so everything works before and after load.
  const user = props.authProfile ?? props.auth?.user;

  const hasPermission = (permission: string): boolean => {
    if (!user?.roles) return false;
    return user.roles.some(role =>
      role.permissions?.some(p => p.code === permission) ?? false
    );
  };

  const hasRole = (roleName: string): boolean => {
    if (!user?.roles) return false;
    return user.roles.some(role => role.name === roleName);
  };

  const hasAnyRole = (roleNames: string[]): boolean => {
    if (!user?.roles) return false;
    return user.roles.some(role => roleNames.includes(role.name));
  };

  const hasAnyPermission = (permissions: string[]): boolean => {
    return permissions.some(p => hasPermission(p));
  };

  const isOwner = (): boolean => hasRole('Owner');
  const isAdmin = (): boolean => hasRole('Administrator') || hasRole('Owner');
  const isBranchManager = (): boolean => hasRole('Branch Manager') || isAdmin();
  const isSalesManager = (): boolean => hasRole('Sales Manager') || isAdmin();
  const isCashier = (): boolean => hasRole('Cashier');
  const isWarehouseManager = (): boolean => hasRole('Warehouse Manager') || isAdmin();
  const isPurchasingStaff = (): boolean => hasRole('Purchasing Staff') || isAdmin();
  const isAccountant = (): boolean => hasRole('Accountant') || isAdmin();
  const isHR = (): boolean => hasRole('HR') || isAdmin();

  return {
    user,
    hasPermission,
    hasRole,
    hasAnyRole,
    hasAnyPermission,
    isOwner,
    isAdmin,
    isBranchManager,
    isSalesManager,
    isCashier,
    isWarehouseManager,
    isPurchasingStaff,
    isAccountant,
    isHR,
  };
}