import type { PaginatedResponse } from '@/types';

function getXsrfToken(): string | null {
  const match = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]*)/);
  return match ? decodeURIComponent(match[1]) : null;
}

class ApiService {
  private baseUrl: string;

  constructor() {
    this.baseUrl = '/api/v1';
  }

  private async request<T>(
    endpoint: string,
    options: RequestInit = {}
  ): Promise<T> {
    const url = `${this.baseUrl}${endpoint}`;
    const xsrfToken = typeof document !== 'undefined' ? getXsrfToken() : null;

    const response = await fetch(url, {
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        ...(xsrfToken ? { 'X-XSRF-TOKEN': xsrfToken } : {}),
        ...(options.headers || {}),
      },
      credentials: 'same-origin',
      ...options,
    });

    if (!response.ok) {
      const error = await response.json().catch(() => ({ message: 'Request failed' }));
      throw new Error(error.message || `HTTP error! status: ${response.status}`);
    }

    if (response.status === 204) {
      return {} as T;
    }

    return response.json();
  }

  async get<T>(endpoint: string, params?: Record<string, unknown>): Promise<T> {
    const searchParams = new URLSearchParams();
    if (params) {
      Object.entries(params).forEach(([key, value]) => {
        if (value !== undefined && value !== null && value !== '') {
          searchParams.append(key, String(value));
        }
      });
    }
    const queryString = searchParams.toString();
    return this.request<T>(`${endpoint}${queryString ? `?${queryString}` : ''}`);
  }

  async post<T>(endpoint: string, data: unknown): Promise<T> {
    return this.request<T>(endpoint, {
      method: 'POST',
      body: JSON.stringify(data),
    });
  }

  async put<T>(endpoint: string, data: unknown): Promise<T> {
    return this.request<T>(endpoint, {
      method: 'PUT',
      body: JSON.stringify(data),
    });
  }

  async patch<T>(endpoint: string, data: unknown): Promise<T> {
    return this.request<T>(endpoint, {
      method: 'PATCH',
      body: JSON.stringify(data),
    });
  }

  async delete<T>(endpoint: string): Promise<T> {
    return this.request<T>(endpoint, {
      method: 'DELETE',
    });
  }

  // Auth (session based — no tokens)
  async login(username: string, password: string) {
    return this.post<{ user: any }>('/auth/login', { username, password });
  }

  async logout() {
    return this.post('/auth/logout', {});
  }

  async me() {
    return this.get('/auth/me');
  }

  async refreshToken() {
    return this.post('/auth/refresh', {});
  }

  // Companies
  getCompanies(params?: Record<string, unknown>) {
    return this.get<PaginatedResponse<any>>('/companies', params);
  }

  createCompany(data: any) {
    return this.post('/companies', data);
  }

  getCompany(id: string) {
    return this.get(`/companies/${id}`);
  }

  updateCompany(id: string, data: any) {
    return this.put(`/companies/${id}`, data);
  }

  deleteCompany(id: string) {
    return this.delete(`/companies/${id}`);
  }

  // Branches
  getBranches(params?: Record<string, unknown>) {
    return this.get<PaginatedResponse<any>>('/branches', params);
  }

  createBranch(data: any) {
    return this.post('/branches', data);
  }

  getBranch(id: string) {
    return this.get(`/branches/${id}`);
  }

  updateBranch(id: string, data: any) {
    return this.put(`/branches/${id}`, data);
  }

  deleteBranch(id: string) {
    return this.delete(`/branches/${id}`);
  }

  // Warehouses
  getWarehouses(params?: Record<string, unknown>) {
    return this.get<PaginatedResponse<any>>('/warehouses', params);
  }

  createWarehouse(data: any) {
    return this.post('/warehouses', data);
  }

  getWarehouse(id: string) {
    return this.get(`/warehouses/${id}`);
  }

  updateWarehouse(id: string, data: any) {
    return this.put(`/warehouses/${id}`, data);
  }

  deleteWarehouse(id: string) {
    return this.delete(`/warehouses/${id}`);
  }

  // Products
  getProducts(params?: Record<string, unknown>) {
    return this.get<PaginatedResponse<any>>('/products', params);
  }

  createProduct(data: any) {
    return this.post('/products', data);
  }

  getProduct(id: string) {
    return this.get(`/products/${id}`);
  }

  updateProduct(id: string, data: any) {
    return this.put(`/products/${id}`, data);
  }

  deleteProduct(id: string) {
    return this.delete(`/products/${id}`);
  }

  getProductStock(id: string) {
    return this.get(`/products/${id}/stock`);
  }

  // Categories
  getCategories(params?: Record<string, unknown>) {
    return this.get<PaginatedResponse<any>>('/categories', params);
  }

  createCategory(data: any) {
    return this.post('/categories', data);
  }

  getCategory(id: string) {
    return this.get(`/categories/${id}`);
  }

  updateCategory(id: string, data: any) {
    return this.put(`/categories/${id}`, data);
  }

  deleteCategory(id: string) {
    return this.delete(`/categories/${id}`);
  }

  // Brands
  getBrands(params?: Record<string, unknown>) {
    return this.get<PaginatedResponse<any>>('/brands', params);
  }

  createBrand(data: any) {
    return this.post('/brands', data);
  }

  getBrand(id: string) {
    return this.get(`/brands/${id}`);
  }

  updateBrand(id: string, data: any) {
    return this.put(`/brands/${id}`, data);
  }

  deleteBrand(id: string) {
    return this.delete(`/brands/${id}`);
  }

  // Units
  getUnits(params?: Record<string, unknown>) {
    return this.get<PaginatedResponse<any>>('/units', params);
  }

  createUnit(data: any) {
    return this.post('/units', data);
  }

  getUnit(id: string) {
    return this.get(`/units/${id}`);
  }

  updateUnit(id: string, data: any) {
    return this.put(`/units/${id}`, data);
  }

  deleteUnit(id: string) {
    return this.delete(`/units/${id}`);
  }

  // Stock
  getStock(params?: Record<string, unknown>) {
    return this.get<PaginatedResponse<any>>('/stock', params);
  }

  getLowStock(params?: Record<string, unknown>) {
    return this.get<PaginatedResponse<any>>('/stock/low-stock', params);
  }

  getExpiring(params?: Record<string, unknown>) {
    return this.get<PaginatedResponse<any>>('/stock/expiring', params);
  }

  // Stock Movements
  getStockMovements(params?: Record<string, unknown>) {
    return this.get<PaginatedResponse<any>>('/stock-movements', params);
  }

  // Stock Adjustments
  getStockAdjustments(params?: Record<string, unknown>) {
    return this.get<PaginatedResponse<any>>('/stock-adjustments', params);
  }

  createStockAdjustment(data: any) {
    return this.post('/stock-adjustments', data);
  }

  getStockAdjustment(id: string) {
    return this.get(`/stock-adjustments/${id}`);
  }

  approveStockAdjustment(id: string) {
    return this.post(`/stock-adjustments/${id}/approve`, {});
  }

  rejectStockAdjustment(id: string) {
    return this.post(`/stock-adjustments/${id}/reject`, {});
  }

  // Stock Transfers
  getStockTransfers(params?: Record<string, unknown>) {
    return this.get<PaginatedResponse<any>>('/stock-transfers', params);
  }

  createStockTransfer(data: any) {
    return this.post('/stock-transfers', data);
  }

  getStockTransfer(id: string) {
    return this.get(`/stock-transfers/${id}`);
  }

  receiveStockTransfer(id: string) {
    return this.post(`/stock-transfers/${id}/receive`, {});
  }

  // POS
  getPosProducts(params?: Record<string, unknown>) {
    return this.get<PaginatedResponse<any>>('/pos/products', params);
  }

  getPosCustomers(params?: Record<string, unknown>) {
    return this.get<PaginatedResponse<any>>('/pos/customers', params);
  }

  checkout(data: any) {
    return this.post('/pos/checkout', data);
  }

  holdOrder(data: any) {
    return this.post('/pos/hold', data);
  }

  getHeldOrders(params?: Record<string, unknown>) {
    return this.get<PaginatedResponse<any>>('/pos/held-orders', params);
  }

  resumeOrder(id: string) {
    return this.post(`/pos/resume/${id}`, {});
  }

  // Sales Orders
  getSalesOrders(params?: Record<string, unknown>) {
    return this.get<PaginatedResponse<any>>('/sales-orders', params);
  }

  createSalesOrder(data: any) {
    return this.post('/sales-orders', data);
  }

  getSalesOrder(id: string) {
    return this.get(`/sales-orders/${id}`);
  }

  updateSalesOrder(id: string, data: any) {
    return this.put(`/sales-orders/${id}`, data);
  }

  deleteSalesOrder(id: string) {
    return this.delete(`/sales-orders/${id}`);
  }

  // Quotations
  getQuotations(params?: Record<string, unknown>) {
    return this.get<PaginatedResponse<any>>('/quotations', params);
  }

  createQuotation(data: any) {
    return this.post('/quotations', data);
  }

  getQuotation(id: string) {
    return this.get(`/quotations/${id}`);
  }

  updateQuotation(id: string, data: any) {
    return this.put(`/quotations/${id}`, data);
  }

  convertQuotation(id: string) {
    return this.post(`/quotations/${id}/convert`, {});
  }

  deleteQuotation(id: string) {
    return this.delete(`/quotations/${id}`);
  }

  // Invoices
  getInvoices(params?: Record<string, unknown>) {
    return this.get<PaginatedResponse<any>>('/invoices', params);
  }

  createInvoice(data: any) {
    return this.post('/invoices', data);
  }

  getInvoice(id: string) {
    return this.get(`/invoices/${id}`);
  }

  cancelInvoice(id: string) {
    return this.post(`/invoices/${id}/cancel`, {});
  }

  printInvoice(id: string) {
    return this.get(`/invoices/${id}/print`);
  }

  // Sales Returns
  getSalesReturns(params?: Record<string, unknown>) {
    return this.get<PaginatedResponse<any>>('/sales-returns', params);
  }

  createSalesReturn(data: any) {
    return this.post('/sales-returns', data);
  }

  getSalesReturn(id: string) {
    return this.get(`/sales-returns/${id}`);
  }

  approveSalesReturn(id: string) {
    return this.post(`/sales-returns/${id}/approve`, {});
  }

  rejectSalesReturn(id: string) {
    return this.post(`/sales-returns/${id}/reject`, {});
  }

  // Refunds
  getRefunds(params?: Record<string, unknown>) {
    return this.get<PaginatedResponse<any>>('/refunds', params);
  }

  createRefund(data: any) {
    return this.post('/refunds', data);
  }

  getRefund(id: string) {
    return this.get(`/refunds/${id}`);
  }

  approveRefund(id: string) {
    return this.post(`/refunds/${id}/approve`, {});
  }

  rejectRefund(id: string) {
    return this.post(`/refunds/${id}/reject`, {});
  }

  // Purchase Orders
  getPurchaseOrders(params?: Record<string, unknown>) {
    return this.get<PaginatedResponse<any>>('/purchase-orders', params);
  }

  createPurchaseOrder(data: any) {
    return this.post('/purchase-orders', data);
  }

  getPurchaseOrder(id: string) {
    return this.get(`/purchase-orders/${id}`);
  }

  updatePurchaseOrder(id: string, data: any) {
    return this.put(`/purchase-orders/${id}`, data);
  }

  approvePurchaseOrder(id: string) {
    return this.post(`/purchase-orders/${id}/approve`, {});
  }

  cancelPurchaseOrder(id: string) {
    return this.post(`/purchase-orders/${id}/cancel`, {});
  }

  // Purchase Receipts
  getPurchaseReceipts(params?: Record<string, unknown>) {
    return this.get<PaginatedResponse<any>>('/purchase-receipts', params);
  }

  createPurchaseReceipt(data: any) {
    return this.post('/purchase-receipts', data);
  }

  getPurchaseReceipt(id: string) {
    return this.get(`/purchase-receipts/${id}`);
  }

  // Purchase Returns
  getPurchaseReturns(params?: Record<string, unknown>) {
    return this.get<PaginatedResponse<any>>('/purchase-returns', params);
  }

  createPurchaseReturn(data: any) {
    return this.post('/purchase-returns', data);
  }

  getPurchaseReturn(id: string) {
    return this.get(`/purchase-returns/${id}`);
  }

  approvePurchaseReturn(id: string) {
    return this.post(`/purchase-returns/${id}/approve`, {});
  }

  rejectPurchaseReturn(id: string) {
    return this.post(`/purchase-returns/${id}/reject`, {});
  }

  // Customers
  getCustomers(params?: Record<string, unknown>) {
    return this.get<PaginatedResponse<any>>('/customers', params);
  }

  createCustomer(data: any) {
    return this.post('/customers', data);
  }

  getCustomer(id: string) {
    return this.get(`/customers/${id}`);
  }

  updateCustomer(id: string, data: any) {
    return this.put(`/customers/${id}`, data);
  }

  deleteCustomer(id: string) {
    return this.delete(`/customers/${id}`);
  }

  getCustomerStatement(id: string) {
    return this.get(`/customers/${id}/statement`);
  }

  getCustomerLoyalty(id: string) {
    return this.get(`/customers/${id}/loyalty`);
  }

  // Customer Groups
  getCustomerGroups(params?: Record<string, unknown>) {
    return this.get<PaginatedResponse<any>>('/customer-groups', params);
  }

  createCustomerGroup(data: any) {
    return this.post('/customer-groups', data);
  }

  getCustomerGroup(id: string) {
    return this.get(`/customer-groups/${id}`);
  }

  updateCustomerGroup(id: string, data: any) {
    return this.put(`/customer-groups/${id}`, data);
  }

  deleteCustomerGroup(id: string) {
    return this.delete(`/customer-groups/${id}`);
  }

  // Customer Payments
  getCustomerPayments(params?: Record<string, unknown>) {
    return this.get<PaginatedResponse<any>>('/customer-payments', params);
  }

  createCustomerPayment(data: any) {
    return this.post('/customer-payments', data);
  }

  // Loyalty Transactions
  getLoyaltyTransactions(params?: Record<string, unknown>) {
    return this.get<PaginatedResponse<any>>('/loyalty-transactions', params);
  }

  // Suppliers
  getSuppliers(params?: Record<string, unknown>) {
    return this.get<PaginatedResponse<any>>('/suppliers', params);
  }

  createSupplier(data: any) {
    return this.post('/suppliers', data);
  }

  getSupplier(id: string) {
    return this.get(`/suppliers/${id}`);
  }

  updateSupplier(id: string, data: any) {
    return this.put(`/suppliers/${id}`, data);
  }

  deleteSupplier(id: string) {
    return this.delete(`/suppliers/${id}`);
  }

  getSupplierStatement(id: string) {
    return this.get(`/suppliers/${id}/statement`);
  }

  // Supplier Payments
  getSupplierPayments(params?: Record<string, unknown>) {
    return this.get<PaginatedResponse<any>>('/supplier-payments', params);
  }

  createSupplierPayment(data: any) {
    return this.post('/supplier-payments', data);
  }

  // Payments
  getPayments(params?: Record<string, unknown>) {
    return this.get<PaginatedResponse<any>>('/payments', params);
  }

  createPayment(data: any) {
    return this.post('/payments', data);
  }

  // Payment Methods
  getPaymentMethods(params?: Record<string, unknown>) {
    return this.get<PaginatedResponse<any>>('/payment-methods', params);
  }

  createPaymentMethod(data: any) {
    return this.post('/payment-methods', data);
  }

  getPaymentMethod(id: string) {
    return this.get(`/payment-methods/${id}`);
  }

  updatePaymentMethod(id: string, data: any) {
    return this.put(`/payment-methods/${id}`, data);
  }

  deletePaymentMethod(id: string) {
    return this.delete(`/payment-methods/${id}`);
  }

  // Bank Accounts
  getBankAccounts(params?: Record<string, unknown>) {
    return this.get<PaginatedResponse<any>>('/bank-accounts', params);
  }

  createBankAccount(data: any) {
    return this.post('/bank-accounts', data);
  }

  getBankAccount(id: string) {
    return this.get(`/bank-accounts/${id}`);
  }

  updateBankAccount(id: string, data: any) {
    return this.put(`/bank-accounts/${id}`, data);
  }

  deleteBankAccount(id: string) {
    return this.delete(`/bank-accounts/${id}`);
  }

  reconcileBankAccount(id: string, data: any) {
    return this.post(`/bank-accounts/${id}/reconcile`, data);
  }

  // Bank Transactions
  getBankTransactions(params?: Record<string, unknown>) {
    return this.get<PaginatedResponse<any>>('/bank-transactions', params);
  }

  createBankTransaction(data: any) {
    return this.post('/bank-transactions', data);
  }

  // Expenses
  getExpenses(params?: Record<string, unknown>) {
    return this.get<PaginatedResponse<any>>('/expenses', params);
  }

  createExpense(data: any) {
    return this.post('/expenses', data);
  }

  getExpense(id: string) {
    return this.get(`/expenses/${id}`);
  }

  updateExpense(id: string, data: any) {
    return this.put(`/expenses/${id}`, data);
  }

  approveExpense(id: string) {
    return this.post(`/expenses/${id}/approve`, {});
  }

  rejectExpense(id: string) {
    return this.post(`/expenses/${id}/reject`, {});
  }

  deleteExpense(id: string) {
    return this.delete(`/expenses/${id}`);
  }

  // Expense Categories
  getExpenseCategories(params?: Record<string, unknown>) {
    return this.get<PaginatedResponse<any>>('/expense-categories', params);
  }

  createExpenseCategory(data: any) {
    return this.post('/expense-categories', data);
  }

  getExpenseCategory(id: string) {
    return this.get(`/expense-categories/${id}`);
  }

  updateExpenseCategory(id: string, data: any) {
    return this.put(`/expense-categories/${id}`, data);
  }

  deleteExpenseCategory(id: string) {
    return this.delete(`/expense-categories/${id}`);
  }

  // Cash Registers
  getCashRegisters(params?: Record<string, unknown>) {
    return this.get<PaginatedResponse<any>>('/cash-registers', params);
  }

  createCashRegister(data: any) {
    return this.post('/cash-registers', data);
  }

  getCashRegister(id: string) {
    return this.get(`/cash-registers/${id}`);
  }

  updateCashRegister(id: string, data: any) {
    return this.put(`/cash-registers/${id}`, data);
  }

  deleteCashRegister(id: string) {
    return this.delete(`/cash-registers/${id}`);
  }

  // Cash Sessions
  getCashSessions(params?: Record<string, unknown>) {
    return this.get<PaginatedResponse<any>>('/cash-sessions', params);
  }

  openCashSession(data: any) {
    return this.post('/cash-sessions', data);
  }

  getCashSession(id: string) {
    return this.get(`/cash-sessions/${id}`);
  }

  closeCashSession(id: string, data: any) {
    return this.post(`/cash-sessions/${id}/close`, data);
  }

  reconcileCashSession(id: string) {
    return this.post(`/cash-sessions/${id}/reconcile`, {});
  }

  // Accounting Accounts
  getAccountingAccounts(params?: Record<string, unknown>) {
    return this.get<PaginatedResponse<any>>('/accounting-accounts', params);
  }

  createAccountingAccount(data: any) {
    return this.post('/accounting-accounts', data);
  }

  getAccountingAccount(id: string) {
    return this.get(`/accounting-accounts/${id}`);
  }

  updateAccountingAccount(id: string, data: any) {
    return this.put(`/accounting-accounts/${id}`, data);
  }

  deleteAccountingAccount(id: string) {
    return this.delete(`/accounting-accounts/${id}`);
  }

  // Journal Entries
  getJournalEntries(params?: Record<string, unknown>) {
    return this.get<PaginatedResponse<any>>('/journal-entries', params);
  }

  createJournalEntry(data: any) {
    return this.post('/journal-entries', data);
  }

  getJournalEntry(id: string) {
    return this.get(`/journal-entries/${id}`);
  }

  postJournalEntry(id: string) {
    return this.post(`/journal-entries/${id}/post`, {});
  }

  reverseJournalEntry(id: string) {
    return this.post(`/journal-entries/${id}/reverse`, {});
  }

  // Employees
  getEmployees(params?: Record<string, unknown>) {
    return this.get<PaginatedResponse<any>>('/employees', params);
  }

  createEmployee(data: any) {
    return this.post('/employees', data);
  }

  getEmployee(id: string) {
    return this.get(`/employees/${id}`);
  }

  updateEmployee(id: string, data: any) {
    return this.put(`/employees/${id}`, data);
  }

  deleteEmployee(id: string) {
    return this.delete(`/employees/${id}`);
  }

  // Employee Attendance
  getEmployeeAttendance(params?: Record<string, unknown>) {
    return this.get<PaginatedResponse<any>>('/employee-attendance', params);
  }

  createEmployeeAttendance(data: any) {
    return this.post('/employee-attendance', data);
  }

  // Employee Shifts
  getEmployeeShifts(params?: Record<string, unknown>) {
    return this.get<PaginatedResponse<any>>('/employee-shifts', params);
  }

  createEmployeeShift(data: any) {
    return this.post('/employee-shifts', data);
  }

  // Employee Commissions
  getEmployeeCommissions(params?: Record<string, unknown>) {
    return this.get<PaginatedResponse<any>>('/employee-commissions', params);
  }

  // Promotions
  getPromotions(params?: Record<string, unknown>) {
    return this.get<PaginatedResponse<any>>('/promotions', params);
  }

  createPromotion(data: any) {
    return this.post('/promotions', data);
  }

  getPromotion(id: string) {
    return this.get(`/promotions/${id}`);
  }

  updatePromotion(id: string, data: any) {
    return this.put(`/promotions/${id}`, data);
  }

  deletePromotion(id: string) {
    return this.delete(`/promotions/${id}`);
  }

  // Coupons
  getCoupons(params?: Record<string, unknown>) {
    return this.get<PaginatedResponse<any>>('/coupons', params);
  }

  createCoupon(data: any) {
    return this.post('/coupons', data);
  }

  getCoupon(id: string) {
    return this.get(`/coupons/${id}`);
  }

  updateCoupon(id: string, data: any) {
    return this.put(`/coupons/${id}`, data);
  }

  deleteCoupon(id: string) {
    return this.delete(`/coupons/${id}`);
  }

  // Users
  getUsers(params?: Record<string, unknown>) {
    return this.get<PaginatedResponse<any>>('/users', params);
  }

  createUser(data: any) {
    return this.post('/users', data);
  }

  getUser(id: string) {
    return this.get(`/users/${id}`);
  }

  updateUser(id: string, data: any) {
    return this.put(`/users/${id}`, data);
  }

  resetUserPassword(id: string, data: any) {
    return this.post(`/users/${id}/reset-password`, data);
  }

  deleteUser(id: string) {
    return this.delete(`/users/${id}`);
  }

  // Roles
  getRoles(params?: Record<string, unknown>) {
    return this.get<PaginatedResponse<any>>('/roles', params);
  }

  createRole(data: any) {
    return this.post('/roles', data);
  }

  getRole(id: string) {
    return this.get(`/roles/${id}`);
  }

  updateRole(id: string, data: any) {
    return this.put(`/roles/${id}`, data);
  }

  deleteRole(id: string) {
    return this.delete(`/roles/${id}`);
  }

  // Permissions
  getPermissions(params?: Record<string, unknown>) {
    return this.get<PaginatedResponse<any>>('/permissions', params);
  }

  createPermission(data: any) {
    return this.post('/permissions', data);
  }

  getPermission(id: string) {
    return this.get(`/permissions/${id}`);
  }

  updatePermission(id: string, data: any) {
    return this.put(`/permissions/${id}`, data);
  }

  deletePermission(id: string) {
    return this.delete(`/permissions/${id}`);
  }

  // Audit Logs
  getAuditLogs(params?: Record<string, unknown>) {
    return this.get<PaginatedResponse<any>>('/audit-logs', params);
  }

  getAuditLog(id: string) {
    return this.get(`/audit-logs/${id}`);
  }

  // Approval Requests
  getApprovalRequests(params?: Record<string, unknown>) {
    return this.get<PaginatedResponse<any>>('/approval-requests', params);
  }

  createApprovalRequest(data: any) {
    return this.post('/approval-requests', data);
  }

  getApprovalRequest(id: string) {
    return this.get(`/approval-requests/${id}`);
  }

  approveApprovalRequest(id: string) {
    return this.post(`/approval-requests/${id}/approve`, {});
  }

  rejectApprovalRequest(id: string) {
    return this.post(`/approval-requests/${id}/reject`, {});
  }

  // Notifications
  getNotifications(params?: Record<string, unknown>) {
    return this.get<PaginatedResponse<any>>('/notifications', params);
  }

  createNotification(data: any) {
    return this.post('/notifications', data);
  }

  markNotificationAsRead(id: string) {
    return this.patch(`/notifications/${id}`, { is_read: true });
  }

  markAllNotificationsAsRead() {
    return this.post('/notifications/mark-all-read', {});
  }

  // Reports
  getSalesReport(params?: Record<string, unknown>) {
    return this.get('/reports/sales', params);
  }

  getProfitReport(params?: Record<string, unknown>) {
    return this.get('/reports/profit', params);
  }

  getInventoryReport(params?: Record<string, unknown>) {
    return this.get('/reports/inventory', params);
  }

  getPurchasesReport(params?: Record<string, unknown>) {
    return this.get('/reports/purchases', params);
  }

  getExpensesReport(params?: Record<string, unknown>) {
    return this.get('/reports/expenses', params);
  }

  getCustomersReport(params?: Record<string, unknown>) {
    return this.get('/reports/customers', params);
  }

  getSuppliersReport(params?: Record<string, unknown>) {
    return this.get('/reports/suppliers', params);
  }

  getEmployeesReport(params?: Record<string, unknown>) {
    return this.get('/reports/employees', params);
  }

  getBranchesReport(params?: Record<string, unknown>) {
    return this.get('/reports/branches', params);
  }

  getCashReport(params?: Record<string, unknown>) {
    return this.get('/reports/cash', params);
  }

  getTaxReport(params?: Record<string, unknown>) {
    return this.get('/reports/tax', params);
  }

  // Settings
  getSettings() {
    return this.get('/settings');
  }

  updateSettings(data: any) {
    return this.put('/settings', data);
  }
}

export const api = new ApiService();