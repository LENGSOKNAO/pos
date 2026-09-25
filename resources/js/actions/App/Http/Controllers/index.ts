import Auth from './Auth'
import DashboardController from './DashboardController'
import ProductController from './ProductController'
import StockController from './StockController'
import PosController from './PosController'
import PurchaseController from './PurchaseController'
import CashSessionController from './CashSessionController'
import SaleController from './SaleController'
import SalesReturnController from './SalesReturnController'
import CustomerController from './CustomerController'
import SupplierController from './SupplierController'
import ReportController from './ReportController'
import SettingController from './SettingController'
import UserController from './UserController'
const Controllers = {
    Auth: Object.assign(Auth, Auth),
DashboardController: Object.assign(DashboardController, DashboardController),
ProductController: Object.assign(ProductController, ProductController),
StockController: Object.assign(StockController, StockController),
PosController: Object.assign(PosController, PosController),
PurchaseController: Object.assign(PurchaseController, PurchaseController),
CashSessionController: Object.assign(CashSessionController, CashSessionController),
SaleController: Object.assign(SaleController, SaleController),
SalesReturnController: Object.assign(SalesReturnController, SalesReturnController),
CustomerController: Object.assign(CustomerController, CustomerController),
SupplierController: Object.assign(SupplierController, SupplierController),
ReportController: Object.assign(ReportController, ReportController),
SettingController: Object.assign(SettingController, SettingController),
UserController: Object.assign(UserController, UserController),
}

export default Controllers