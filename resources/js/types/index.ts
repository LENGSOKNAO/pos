export type User = {
  id: string;
  username: string;
  email: string;
  employee_id: string;
  last_login: string | null;
  status: string;
  created_at: string;
  updated_at: string;
  employee?: Employee;
  roles?: Role[];
};

export interface Employee {
  id: string;
  company_id: string;
  branch_id: string | null;
  employee_code: string;
  first_name: string;
  last_name: string;
  phone: string | null;
  email: string | null;
  position: string | null;
  hire_date: string | null;
  salary: number;
  commission_rate: number;
  status: string;
  created_at: string;
  updated_at: string;
  branch?: Branch;
  company?: Company;
  user?: User;
}

export interface Branch {
  id: string;
  company_id: string;
  code: string;
  name: string;
  phone: string | null;
  address: string | null;
  manager_id: string | null;
  status: string;
  opened_at: string | null;
  created_at: string;
  updated_at: string;
}

export interface Company {
  id: string;
  code: string;
  name: string;
  legal_name: string | null;
  phone: string | null;
  email: string | null;
  address: string | null;
  tax_number: string | null;
  currency: string;
  timezone: string;
  status: string;
  created_at: string;
  updated_at: string;
}

export interface Role {
  id: string;
  company_id: string;
  name: string;
  description: string | null;
  status: string;
  created_at: string;
  updated_at: string;
  permissions?: Permission[];
}

export interface Permission {
  id: string;
  code: string;
  name: string;
  module: string;
  created_at: string;
  updated_at: string;
}

export interface Auth {
  user: User;
}

export interface PaginatedResponse<T> {
  data: T[];
  meta: {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number;
    to: number;
  };
}