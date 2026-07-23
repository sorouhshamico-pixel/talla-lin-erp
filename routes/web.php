<?php

use App\Support\Reports\ReportSavedViewRolloutTarget;
use App\Support\Reports\ReportSavedViewRolloutSelectorWebLinks;
use App\Support\Reports\ReportSavedViewRolloutSelector;
use App\Support\Reports\ReportSavedViewCandidateScannerWebLinks;
use App\Support\Reports\ReportSavedViewCandidateScanner;
use App\Support\Reports\ReportSavedViewDiagnosticSnapshotExporter;
use App\Support\Reports\ReportSavedViewDiagnosticsWebLinks;
use App\Support\Reports\ReportSavedViewRegistryDiagnosticReport;
use App\Http\Middleware\EnsurePartyPermission;

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExpenseCategoryController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\RevenueController;
use App\Http\Controllers\RevenueCategoryController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\PurchaseInvoiceController;
use App\Http\Controllers\QuotationController;
use App\Http\Controllers\QuotationItemController;
use App\Http\Controllers\SalesOrderConversionController;
use App\Http\Controllers\SalesOrderController;
use App\Http\Controllers\DeliveryNoteConversionController;
use App\Http\Controllers\DeliveryNoteController;
use App\Http\Controllers\DeliveryNoteInvoiceController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SalesInvoiceController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\PartyFollowUpController;
use App\Http\Controllers\PartyTimelineController;
use App\Http\Controllers\PartyStatementController;
use App\Http\Controllers\PartyTagController;
use App\Http\Controllers\PartyClassificationController;
use App\Http\Controllers\PartyDuplicateController;
use App\Http\Controllers\PartyPermissionController;
use App\Http\Controllers\PartyDashboardController;
use App\Http\Controllers\WarehouseController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->name('login.attempt');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
Route::get('/dashboard/financial-summary/print', \App\Http\Controllers\FinancialDashboardSummaryPrintController::class)->name('dashboard.financial-summary.print');
Route::get('/dashboard/top-overdue-suppliers/export', \App\Http\Controllers\MainDashboardTopOverdueSuppliersExportController::class)->name('dashboard.top-overdue-suppliers.export');
Route::get('/dashboard/top-overdue/print', \App\Http\Controllers\MainDashboardTopOverduePrintController::class)->name('dashboard.top-overdue.print');
Route::get('/dashboard/top-overdue-customers/export', \App\Http\Controllers\MainDashboardTopOverdueCustomersExportController::class)->name('dashboard.top-overdue-customers.export');
Route::get('/dashboard/financial-summary/export', \App\Http\Controllers\FinancialDashboardSummaryExportController::class)->name('dashboard.financial-summary.export');

    Route::get('/branches', [BranchController::class, 'index'])->name('branches.index');
    Route::get('/warehouses', [WarehouseController::class, 'index'])->name('warehouses.index');
    Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
    Route::get('/products', [ProductController::class, 'index'])->name('products.index');

    Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory.index');
    Route::get('/inventory/movements/create', [InventoryController::class, 'createMovement'])->name('inventory.movements.create');
    Route::post('/inventory/movements', [InventoryController::class, 'storeMovement'])->name('inventory.movements.store');


    Route::get('/party-dashboard', [PartyDashboardController::class, 'index'])->name('party-dashboard.index')->middleware(EnsurePartyPermission::class . ':view_parties');

    Route::get('/party-permissions', [PartyPermissionController::class, 'index'])->name('party-permissions.index')->middleware(EnsurePartyPermission::class . ':manage_parties');

    Route::get('/party-duplicates', [PartyDuplicateController::class, 'index'])->name('party-duplicates.index')->middleware(EnsurePartyPermission::class . ':view_parties');

    Route::get('/party-tags', [PartyTagController::class, 'index'])->name('party-tags.index')->middleware(EnsurePartyPermission::class . ':manage_party_classifications');
    Route::post('/party-tags', [PartyTagController::class, 'store'])->name('party-tags.store')->middleware(EnsurePartyPermission::class . ':manage_party_classifications');
    Route::get('/party-tags/{partyTag}', [PartyTagController::class, 'show'])->name('party-tags.show')->middleware(EnsurePartyPermission::class . ':manage_party_classifications');
    Route::post('/party-tags/{partyTag}/toggle-active', [PartyTagController::class, 'toggleActive'])->name('party-tags.toggle-active')->middleware(EnsurePartyPermission::class . ':manage_party_classifications');
    Route::delete('/party-tags/{partyTag}', [PartyTagController::class, 'destroy'])->name('party-tags.destroy')->middleware(EnsurePartyPermission::class . ':manage_party_classifications');

    Route::post('/customers/{customer}/classification', [PartyClassificationController::class, 'customer'])->name('customers.classification.update')->middleware(EnsurePartyPermission::class . ':manage_party_classifications');
    Route::post('/suppliers/{supplier}/classification', [PartyClassificationController::class, 'supplier'])->name('suppliers.classification.update')->middleware(EnsurePartyPermission::class . ':manage_party_classifications');

    Route::get('/party-follow-ups', [PartyFollowUpController::class, 'index'])->name('party-follow-ups.index')->middleware(EnsurePartyPermission::class . ':view_parties');
    Route::post('/party-follow-ups/{contactLog}/complete', [PartyFollowUpController::class, 'complete'])->name('party-follow-ups.complete')->middleware(EnsurePartyPermission::class . ':manage_party_follow_ups');
    Route::post('/party-follow-ups/{contactLog}/reschedule', [PartyFollowUpController::class, 'reschedule'])->name('party-follow-ups.reschedule')->middleware(EnsurePartyPermission::class . ':manage_party_follow_ups');
    Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index')->middleware(EnsurePartyPermission::class . ':view_parties');
    Route::get('/exports/customers', [CustomerController::class, 'exportCsv'])->name('customers.export')->middleware(EnsurePartyPermission::class . ':export_parties');

    Route::get('/exports/customers/template', [CustomerController::class, 'exportTemplateCsv'])->name('customers.export-template')->middleware(EnsurePartyPermission::class . ':export_parties');
    Route::post('/imports/customers', [CustomerController::class, 'importCsv'])->name('customers.import')->middleware(EnsurePartyPermission::class . ':manage_parties');
    Route::patch('/bulk/customers/status', [CustomerController::class, 'bulkUpdateStatus'])->name('customers.bulk-status');
    Route::get('/customers/create', [CustomerController::class, 'create'])->name('customers.create')->middleware(EnsurePartyPermission::class . ':manage_parties');
    Route::post('/customers', [CustomerController::class, 'store'])->name('customers.store')->middleware(EnsurePartyPermission::class . ':manage_parties');
    Route::get('/customers/{customer}', [CustomerController::class, 'show'])->name('customers.show')->middleware(EnsurePartyPermission::class . ':view_parties');
    Route::get('/customers/{customer}/statement', [PartyStatementController::class, 'customer'])->name('customers.statement')->middleware(EnsurePartyPermission::class . ':view_party_financials');
    Route::get('/customers/{customer}/statement/export', [PartyStatementController::class, 'customerCsv'])->name('customers.statement.export')->middleware(EnsurePartyPermission::class . ':export_parties');
    Route::get('/customers/{customer}/activity-timeline', [PartyTimelineController::class, 'customer'])->name('customers.activity-timeline.index')->middleware(EnsurePartyPermission::class . ':view_parties');
    Route::post('/customers/{customer}/contact-logs', [CustomerController::class, 'storeContactLog'])->name('customers.contact-logs.store')->middleware(EnsurePartyPermission::class . ':manage_party_follow_ups');
    Route::delete('/customers/{customer}/contact-logs/{contactLog}', [CustomerController::class, 'destroyContactLog'])->name('customers.contact-logs.destroy')->middleware(EnsurePartyPermission::class . ':manage_party_follow_ups');
    Route::post('/customers/{customer}/attachments', [CustomerController::class, 'storeAttachment'])->name('customers.attachments.store')->middleware(EnsurePartyPermission::class . ':manage_party_attachments');
    Route::get('/customers/{customer}/attachments/{attachment}/download', [CustomerController::class, 'downloadAttachment'])->name('customers.attachments.download')->middleware(EnsurePartyPermission::class . ':view_parties');
    Route::delete('/customers/{customer}/attachments/{attachment}', [CustomerController::class, 'destroyAttachment'])->name('customers.attachments.destroy')->middleware(EnsurePartyPermission::class . ':manage_party_attachments');
    Route::post('/customers/{customer}/notes', [CustomerController::class, 'storeNote'])->name('customers.notes.store')->middleware(EnsurePartyPermission::class . ':manage_party_notes');
    Route::delete('/customers/{customer}/notes/{note}', [CustomerController::class, 'destroyNote'])->name('customers.notes.destroy')->middleware(EnsurePartyPermission::class . ':manage_party_notes');
Route::get('/customers/{customer}/edit', [CustomerController::class, 'edit'])->name('customers.edit')->middleware(EnsurePartyPermission::class . ':manage_parties');
    Route::put('/customers/{customer}', [CustomerController::class, 'update'])->name('customers.update')->middleware(EnsurePartyPermission::class . ':manage_parties');

    Route::patch('/customers/{customer}/toggle-active', [CustomerController::class, 'toggleActive'])->name('customers.toggle-active')->middleware(EnsurePartyPermission::class . ':manage_parties');


    Route::get('/quotations', [QuotationController::class, 'index'])->name('quotations.index');
    Route::get('/quotations/create', [QuotationController::class, 'create'])->name('quotations.create');
    Route::post('/quotations', [QuotationController::class, 'store'])->name('quotations.store');
    Route::patch('/quotations/{quotation}/status', [QuotationController::class, 'updateStatus'])->name('quotations.update-status');
    Route::post('/quotations/{quotation}/convert-to-sales-order', [SalesOrderConversionController::class, 'store'])->name('quotations.convert-to-sales-order');
    Route::get('/sales-orders', [SalesOrderController::class, 'index'])->name('sales-orders.index');
    Route::patch('/sales-orders/{salesOrder}/status', [SalesOrderController::class, 'updateStatus'])->name('sales-orders.update-status');
    Route::post('/sales-orders/{salesOrder}/convert-to-delivery-note', [DeliveryNoteConversionController::class, 'store'])->name('sales-orders.convert-to-delivery-note');
    Route::get('/delivery-notes', [DeliveryNoteController::class, 'index'])->name('delivery-notes.index');
    Route::patch('/delivery-notes/{deliveryNote}/status', [DeliveryNoteController::class, 'updateStatus'])->name('delivery-notes.update-status');
    Route::post('/delivery-notes/{deliveryNote}/convert-to-sales-invoice', [DeliveryNoteInvoiceController::class, 'store'])->name('delivery-notes.convert-to-sales-invoice');
    Route::get('/delivery-notes/{deliveryNote}/print', [DeliveryNoteController::class, 'print'])->name('delivery-notes.print');
    Route::get('/delivery-notes/{deliveryNote}', [DeliveryNoteController::class, 'show'])->name('delivery-notes.show');
    Route::get('/sales-orders/{salesOrder}/print', [SalesOrderController::class, 'print'])->name('sales-orders.print');
    Route::get('/sales-orders/{salesOrder}', [SalesOrderController::class, 'show'])->name('sales-orders.show');
    Route::get('/quotations/{quotation}/print', [QuotationController::class, 'print'])->name('quotations.print');
    Route::get('/quotations/{quotation}', [QuotationController::class, 'show'])->name('quotations.show');
    Route::post('/quotations/{quotation}/items', [QuotationItemController::class, 'store'])->name('quotations.items.store');
    Route::patch('/quotations/{quotation}/items/{item}', [QuotationItemController::class, 'update'])->name('quotations.items.update');
    Route::delete('/quotations/{quotation}/items/{item}', [QuotationItemController::class, 'destroy'])->name('quotations.items.destroy');

    Route::post('/sales-invoices/{salesInvoice}/collection-notes', [\App\Http\Controllers\SalesInvoiceCollectionNoteController::class, 'store'])->name('sales-invoices.collection-notes.store');
Route::post('/sales-invoices/{salesInvoice}/collection-notes/{collectionNote}/complete', [\App\Http\Controllers\SalesInvoiceCollectionNoteController::class, 'complete'])->name('sales-invoices.collection-notes.complete');
Route::post('/sales-invoices/{salesInvoice}/collection-notes/{collectionNote}/reschedule', [\App\Http\Controllers\SalesInvoiceCollectionNoteController::class, 'reschedule'])->name('sales-invoices.collection-notes.reschedule');
Route::get('/sales-invoices/export', [SalesInvoiceController::class, 'export'])->name('sales-invoices.export');
Route::get('/sales-invoices', [SalesInvoiceController::class, 'index'])->name('sales-invoices.index');
    Route::get('/sales-invoices/create', [SalesInvoiceController::class, 'create'])->name('sales-invoices.create');
    Route::post('/sales-invoices', [SalesInvoiceController::class, 'store'])->name('sales-invoices.store');
    Route::post('/sales-invoices/{salesInvoice}/issue', [SalesInvoiceController::class, 'issue'])->name('sales-invoices.issue');
    Route::get('/sales-invoices/{salesInvoice}/payments/create', [SalesInvoiceController::class, 'createPayment'])->name('sales-invoices.payments.create');
    Route::post('/sales-invoices/{salesInvoice}/payments', [SalesInvoiceController::class, 'storePayment'])->name('sales-invoices.payments.store');
    Route::get('/sales-invoices/{salesInvoice}', [SalesInvoiceController::class, 'show'])->name('sales-invoices.show');

    Route::get('/suppliers', [SupplierController::class, 'index'])->name('suppliers.index')->middleware(EnsurePartyPermission::class . ':view_parties');
    Route::get('/exports/suppliers', [SupplierController::class, 'exportCsv'])->name('suppliers.export')->middleware(EnsurePartyPermission::class . ':export_parties');

    Route::get('/exports/suppliers/template', [SupplierController::class, 'exportTemplateCsv'])->name('suppliers.export-template')->middleware(EnsurePartyPermission::class . ':export_parties');
    Route::post('/imports/suppliers', [SupplierController::class, 'importCsv'])->name('suppliers.import')->middleware(EnsurePartyPermission::class . ':manage_parties');
    Route::patch('/bulk/suppliers/status', [SupplierController::class, 'bulkUpdateStatus'])->name('suppliers.bulk-status');
    Route::get('/suppliers/create', [SupplierController::class, 'create'])->name('suppliers.create')->middleware(EnsurePartyPermission::class . ':manage_parties');
    Route::post('/suppliers', [SupplierController::class, 'store'])->name('suppliers.store')->middleware(EnsurePartyPermission::class . ':manage_parties');

    Route::get('/suppliers/{supplier}', [SupplierController::class, 'show'])->name('suppliers.show')->middleware(EnsurePartyPermission::class . ':view_parties');
    Route::get('/suppliers/{supplier}/statement', [PartyStatementController::class, 'supplier'])->name('suppliers.statement')->middleware(EnsurePartyPermission::class . ':view_party_financials');
    Route::get('/suppliers/{supplier}/statement/export', [PartyStatementController::class, 'supplierCsv'])->name('suppliers.statement.export')->middleware(EnsurePartyPermission::class . ':export_parties');
    Route::get('/suppliers/{supplier}/activity-timeline', [PartyTimelineController::class, 'supplier'])->name('suppliers.activity-timeline.index')->middleware(EnsurePartyPermission::class . ':view_parties');
    Route::post('/suppliers/{supplier}/contact-logs', [SupplierController::class, 'storeContactLog'])->name('suppliers.contact-logs.store')->middleware(EnsurePartyPermission::class . ':manage_party_follow_ups');
    Route::delete('/suppliers/{supplier}/contact-logs/{contactLog}', [SupplierController::class, 'destroyContactLog'])->name('suppliers.contact-logs.destroy')->middleware(EnsurePartyPermission::class . ':manage_party_follow_ups');
    Route::post('/suppliers/{supplier}/attachments', [SupplierController::class, 'storeAttachment'])->name('suppliers.attachments.store')->middleware(EnsurePartyPermission::class . ':manage_party_attachments');
    Route::get('/suppliers/{supplier}/attachments/{attachment}/download', [SupplierController::class, 'downloadAttachment'])->name('suppliers.attachments.download')->middleware(EnsurePartyPermission::class . ':view_parties');
    Route::delete('/suppliers/{supplier}/attachments/{attachment}', [SupplierController::class, 'destroyAttachment'])->name('suppliers.attachments.destroy')->middleware(EnsurePartyPermission::class . ':manage_party_attachments');
    Route::post('/suppliers/{supplier}/notes', [SupplierController::class, 'storeNote'])->name('suppliers.notes.store')->middleware(EnsurePartyPermission::class . ':manage_party_notes');
    Route::delete('/suppliers/{supplier}/notes/{note}', [SupplierController::class, 'destroyNote'])->name('suppliers.notes.destroy')->middleware(EnsurePartyPermission::class . ':manage_party_notes');
Route::get('/suppliers/{supplier}/edit', [SupplierController::class, 'edit'])->name('suppliers.edit')->middleware(EnsurePartyPermission::class . ':manage_parties');
Route::put('/suppliers/{supplier}', [SupplierController::class, 'update'])->name('suppliers.update')->middleware(EnsurePartyPermission::class . ':manage_parties');
    Route::patch('/suppliers/{supplier}/toggle-active', [SupplierController::class, 'toggleActive'])->name('suppliers.toggle-active')->middleware(EnsurePartyPermission::class . ':manage_parties');

Route::get('/purchase-invoices', [PurchaseInvoiceController::class, 'index'])->name('purchase-invoices.index');
    Route::get('/purchase-invoices/create', [PurchaseInvoiceController::class, 'create'])->name('purchase-invoices.create');
    Route::post('/purchase-invoices', [PurchaseInvoiceController::class, 'store'])->name('purchase-invoices.store');
    Route::post('/purchase-invoices/{purchaseInvoice}/receive', [PurchaseInvoiceController::class, 'receive'])->name('purchase-invoices.receive');
    Route::get('/purchase-invoices/{purchaseInvoice}/payments/create', [PurchaseInvoiceController::class, 'createPayment'])->name('purchase-invoices.payments.create');
    Route::post('/purchase-invoices/{purchaseInvoice}/payments', [PurchaseInvoiceController::class, 'storePayment'])->name('purchase-invoices.payments.store');
    Route::get('/purchase-invoices/{purchaseInvoice}', [PurchaseInvoiceController::class, 'show'])->name('purchase-invoices.show');

    Route::get('/expense-categories', [ExpenseCategoryController::class, 'index'])->name('expense-categories.index');
    Route::get('/expense-categories/create', [ExpenseCategoryController::class, 'create'])->name('expense-categories.create');
    Route::post('/expense-categories', [ExpenseCategoryController::class, 'store'])->name('expense-categories.store');
    Route::get('/expense-categories/{expenseCategory}/edit', [ExpenseCategoryController::class, 'edit'])->name('expense-categories.edit');
    Route::patch('/expense-categories/{expenseCategory}', [ExpenseCategoryController::class, 'update'])->name('expense-categories.update');
    Route::patch('/expense-categories/{expenseCategory}/toggle-status', [ExpenseCategoryController::class, 'toggleStatus'])->name('expense-categories.toggle-status');

    Route::get('/expenses', [ExpenseController::class, 'index'])->name('expenses.index');
    Route::get('/expenses/export', [ExpenseController::class, 'export'])->name('expenses.export');
    Route::get('/expenses/create', [ExpenseController::class, 'create'])->name('expenses.create');
    Route::post('/expenses', [ExpenseController::class, 'store'])->name('expenses.store');
    Route::get('/expenses/{expense}/edit', [ExpenseController::class, 'edit'])->name('expenses.edit');
    Route::patch('/expenses/{expense}', [ExpenseController::class, 'update'])->name('expenses.update');
    Route::delete('/expenses/{expense}/attachment', [ExpenseController::class, 'destroyAttachment'])->name('expenses.attachment.destroy');
    Route::delete('/expenses/{expense}', [ExpenseController::class, 'destroy'])->name('expenses.destroy');

    Route::post('/reports/saved-views', [ReportController::class, 'storeSavedView'])->name('reports.index.saved-views.store');
    Route::delete('/reports/saved-views/bulk-destroy', [\App\Http\Controllers\ReportSavedViewController::class, 'bulkDestroy'])->name('reports.saved-views.bulk-destroy');
    Route::get('/reports/saved-view-share-activities/export', [\App\Http\Controllers\ReportSavedViewShareActivityExportController::class, 'owner'])->name('reports.saved-view-share-activities.owner.export');
    Route::get('/reports/saved-view-share-activities', [\App\Http\Controllers\ReportSavedViewShareActivityController::class, 'ownerIndex'])->name('reports.saved-view-share-activities.owner.index');
    Route::get('/reports/saved-views/{savedView}/shares', [\App\Http\Controllers\ReportSavedViewShareController::class, 'index'])->name('reports.saved-views.shares.index');
    Route::post('/reports/saved-views/{savedView}/shares', [\App\Http\Controllers\ReportSavedViewShareController::class, 'store'])->name('reports.saved-views.shares.store');
    Route::patch('/reports/saved-view-shares/{share}', [\App\Http\Controllers\ReportSavedViewShareController::class, 'update'])->name('reports.saved-view-shares.update');
    Route::delete('/reports/saved-view-shares/{share}', [\App\Http\Controllers\ReportSavedViewShareController::class, 'destroy'])->name('reports.saved-view-shares.destroy');
    Route::get('/reports/shared-saved-view-activities/export', [\App\Http\Controllers\ReportSavedViewShareActivityExportController::class, 'recipient'])->name('reports.shared-saved-view-activities.export');
    Route::get('/reports/shared-saved-view-activities', [\App\Http\Controllers\ReportSavedViewShareActivityController::class, 'recipientIndex'])->name('reports.shared-saved-view-activities.index');
    Route::get('/reports/shared-saved-views', [\App\Http\Controllers\SharedReportSavedViewController::class, 'index'])->name('reports.shared-saved-views.index');
    Route::post('/reports/shared-saved-views/{share}/copy', [\App\Http\Controllers\SharedReportSavedViewController::class, 'copy'])->name('reports.shared-saved-views.copy');
    Route::get('/reports/shared-saved-views/{share}/apply', [\App\Http\Controllers\SharedReportSavedViewController::class, 'apply'])->name('reports.shared-saved-views.apply');
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
Route::get('/reports/sales-invoice-collections', [\App\Http\Controllers\SalesInvoiceCollectionReportController::class, 'index'])->name('reports.sales-invoice-collections.index');
Route::get('/reports/sales-invoice-collections/json', [\App\Http\Controllers\SalesInvoiceCollectionReportController::class, 'json'])->name('reports.sales-invoice-collections.json');
Route::post('/reports/sales-invoice-collections/saved-views', [\App\Http\Controllers\SalesInvoiceCollectionReportController::class, 'storeSavedView'])->name('reports.sales-invoice-collections.saved-views.store');
Route::get('/reports/sales-invoice-collection-follow-ups/export', [\App\Http\Controllers\SalesInvoiceCollectionFollowUpReportController::class, 'export'])->name('reports.sales-invoice-collection-follow-ups.export');
Route::post('/reports/sales-invoice-collection-follow-ups/saved-views', [\App\Http\Controllers\SalesInvoiceCollectionFollowUpReportController::class, 'storeSavedView'])->name('reports.sales-invoice-collection-follow-ups.saved-views.store');
Route::get('/reports/sales-invoice-collection-follow-ups', [\App\Http\Controllers\SalesInvoiceCollectionFollowUpReportController::class, 'index'])->name('reports.sales-invoice-collection-follow-ups.index');
Route::get('/reports/sales-invoice-aging/export', [\App\Http\Controllers\SalesInvoiceAgingReportController::class, 'export'])->name('reports.sales-invoice-aging.export');
Route::get('/reports/sales-invoice-aging', [\App\Http\Controllers\SalesInvoiceAgingReportController::class, 'index'])->name('reports.sales-invoice-aging.index');
Route::get('/reports/customer-sales-invoice-aging/print', [\App\Http\Controllers\CustomerSalesInvoiceAgingReportController::class, 'print'])->name('reports.customer-sales-invoice-aging.print');
Route::get('/reports/customer-sales-invoice-aging/open-invoices/export', [\App\Http\Controllers\CustomerSalesInvoiceAgingDrilldownController::class, 'export'])->name('reports.customer-sales-invoice-aging.drilldown.export');
Route::get('/reports/customer-sales-invoice-aging/open-invoices', [\App\Http\Controllers\CustomerSalesInvoiceAgingDrilldownController::class, 'index'])->name('reports.customer-sales-invoice-aging.drilldown');
Route::get('/reports/customer-sales-invoice-aging/export', [\App\Http\Controllers\CustomerSalesInvoiceAgingReportController::class, 'export'])->name('reports.customer-sales-invoice-aging.export');
Route::get('/reports/supplier-purchase-invoice-aging/print', [\App\Http\Controllers\SupplierPurchaseInvoiceAgingReportController::class, 'print'])->name('reports.supplier-purchase-invoice-aging.print');
Route::get('/reports/supplier-purchase-invoice-aging/open-invoices/export', [\App\Http\Controllers\SupplierPurchaseInvoiceAgingDrilldownController::class, 'export'])->name('reports.supplier-purchase-invoice-aging.drilldown.export');
Route::get('/reports/supplier-purchase-invoice-aging/open-invoices', [\App\Http\Controllers\SupplierPurchaseInvoiceAgingDrilldownController::class, 'index'])->name('reports.supplier-purchase-invoice-aging.drilldown');
Route::get('/reports/supplier-purchase-invoice-aging/export', [\App\Http\Controllers\SupplierPurchaseInvoiceAgingReportController::class, 'export'])->name('reports.supplier-purchase-invoice-aging.export');
Route::get('/reports/receivable-payable-aging-dashboard/print', [\App\Http\Controllers\ReceivablePayableAgingDashboardController::class, 'print'])->name('reports.receivable-payable-aging-dashboard.print');
Route::get('/reports/receivable-payable-aging-dashboard/export', [\App\Http\Controllers\ReceivablePayableAgingDashboardController::class, 'export'])->name('reports.receivable-payable-aging-dashboard.export');
Route::post('/reports/receivable-payable-aging-dashboard/saved-views', [\App\Http\Controllers\ReceivablePayableAgingDashboardController::class, 'storeSavedView'])->name('reports.receivable-payable-aging-dashboard.saved-views.store');
Route::get('/reports/cash-flow-dashboard/print', [\App\Http\Controllers\CashFlowDashboardController::class, 'print'])->name('reports.cash-flow-dashboard.print');
Route::get('/reports/cash-flow-dashboard/export', [\App\Http\Controllers\CashFlowDashboardController::class, 'export'])->name('reports.cash-flow-dashboard.export');
Route::post('/reports/cash-flow-dashboard/saved-views', [\App\Http\Controllers\CashFlowDashboardController::class, 'storeSavedView'])->name('reports.cash-flow-dashboard.saved-views.store');
Route::get('/reports/cash-flow-dashboard', [\App\Http\Controllers\CashFlowDashboardController::class, 'index'])->name('reports.cash-flow-dashboard.index');
Route::get('/reports/receivable-payable-aging-dashboard', [\App\Http\Controllers\ReceivablePayableAgingDashboardController::class, 'index'])->name('reports.receivable-payable-aging-dashboard.index');
Route::get('/reports/supplier-purchase-invoice-aging', [\App\Http\Controllers\SupplierPurchaseInvoiceAgingReportController::class, 'index'])->name('reports.supplier-purchase-invoice-aging.index');
Route::get('/reports/customer-sales-invoice-aging', [\App\Http\Controllers\CustomerSalesInvoiceAgingReportController::class, 'index'])->name('reports.customer-sales-invoice-aging.index');

    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
});

// Expense category activation toggle - Stage 11C
Route::patch('/expense-categories/{expenseCategory}/toggle', [\App\Http\Controllers\ExpenseCategoryController::class, 'toggle'])
    ->name('expense-categories.toggle');


// Expense category delete route - Stage 11C
Route::delete('/expense-categories/{expenseCategory}', [\App\Http\Controllers\ExpenseCategoryController::class, 'destroy'])->name('expense-categories.destroy');

Route::get('/revenue-categories', [RevenueCategoryController::class, 'index'])->name('revenue-categories.index');
Route::post('/revenue-categories', [RevenueCategoryController::class, 'store'])->name('revenue-categories.store');
Route::get('/revenue-categories/{revenueCategory}/edit', [RevenueCategoryController::class, 'edit'])->name('revenue-categories.edit');
Route::put('/revenue-categories/{revenueCategory}', [RevenueCategoryController::class, 'update'])->name('revenue-categories.update');
Route::patch('/revenue-categories/{revenueCategory}/toggle', [RevenueCategoryController::class, 'toggle'])->name('revenue-categories.toggle');
Route::get('/revenues', [RevenueController::class, 'index'])->name('revenues.index');
Route::get('/revenues/create', [RevenueController::class, 'create'])->name('revenues.create');
Route::post('/revenues', [RevenueController::class, 'store'])->name('revenues.store');
Route::get('/revenues/{revenue}/edit', [RevenueController::class, 'edit'])->name('revenues.edit');
Route::put('/revenues/{revenue}', [RevenueController::class, 'update'])->name('revenues.update');
Route::patch('/revenues/{revenue}/toggle-collection', [RevenueController::class, 'toggleCollection'])->name('revenues.toggle-collection');
Route::patch('/revenues/{revenue}/archive', [RevenueController::class, 'archive'])->name('revenues.archive');
Route::patch('/revenues/{revenue}/restore', [RevenueController::class, 'restore'])->name('revenues.restore');

Route::get('/expenses/export/top-large', [ExpenseController::class, 'exportTopLarge'])->name('expenses.export-top-large');
Route::get('/expenses/export/large-unpaid', [ExpenseController::class, 'exportLargeUnpaid'])->name('expenses.export-large-unpaid');
Route::get('/expenses/export/large-paid', [ExpenseController::class, 'exportLargePaid'])->name('expenses.export-large-paid');
Route::get('/revenues/export', [\App\Http\Controllers\RevenueController::class, 'exportCsv'])
    ->middleware(['auth'])
    ->name('revenues.export');
Route::get('/revenues/uncollected/export', [\App\Http\Controllers\RevenueController::class, 'exportUncollectedCsv'])
    ->middleware(['auth'])
    ->name('revenues.uncollected.export');


Route::get('/reports/profit-loss', \App\Http\Controllers\ProfitLossReportController::class)
    ->middleware('auth')
    ->name('reports.profit-loss');

Route::get('/reports/profit-loss/export', [\App\Http\Controllers\ProfitLossReportController::class, 'export'])
    ->middleware('auth')
    ->name('reports.profit-loss.export');

Route::post('/reports/profit-loss/saved-views', [\App\Http\Controllers\ProfitLossReportController::class, 'storeSavedView'])
    ->middleware('auth')
    ->name('reports.profit-loss.saved-views.store');

Route::get('/reports/financial-dashboard', \App\Http\Controllers\FinancialDashboardController::class)
    ->middleware('auth')
    ->name('reports.financial-dashboard');

Route::get('/reports/financial-dashboard/json', [\App\Http\Controllers\FinancialDashboardController::class, 'json'])
    ->middleware('auth')
    ->name('reports.financial-dashboard.json');

Route::post('/reports/financial-dashboard/saved-views', [\App\Http\Controllers\FinancialDashboardController::class, 'storeSavedView'])
    ->middleware('auth')
    ->name('reports.financial-dashboard.saved-views.store');

Route::get('/reports/center', \App\Http\Controllers\ReportsCenterController::class)
    ->middleware('auth')
    ->name('reports.center');

Route::middleware('auth')->group(function () {
    Route::get('/reports/filter-preferences', [\App\Http\Controllers\ReportFilterPreferenceController::class, 'index'])->name('reports.filter-preferences.index');
    Route::delete('/reports/filter-preferences', [\App\Http\Controllers\ReportFilterPreferenceController::class, 'destroyAll'])->name('reports.filter-preferences.destroy-all');
    Route::delete('/reports/filter-preferences/{reportKey}', [\App\Http\Controllers\ReportFilterPreferenceController::class, 'destroy'])->name('reports.filter-preferences.destroy');
});

Route::middleware('auth')->group(function () {
    Route::get('/reports/saved-views', [\App\Http\Controllers\ReportSavedViewController::class, 'index'])->name('reports.saved-views.index');
    Route::get('/reports/saved-views/export', [\App\Http\Controllers\ReportSavedViewController::class, 'export'])->name('reports.saved-views.export');
    Route::post('/reports/saved-views/export-selected', [\App\Http\Controllers\ReportSavedViewController::class, 'exportSelected'])->name('reports.saved-views.export-selected');
    Route::post('/reports/saved-view-tags', [\App\Http\Controllers\ReportSavedViewTagController::class, 'store'])->name('reports.saved-view-tags.store');
    Route::patch('/reports/saved-view-tags/{tag}', [\App\Http\Controllers\ReportSavedViewTagController::class, 'update'])->name('reports.saved-view-tags.update');
    Route::delete('/reports/saved-view-tags/{tag}', [\App\Http\Controllers\ReportSavedViewTagController::class, 'destroy'])->name('reports.saved-view-tags.destroy');
    Route::put('/reports/saved-views/{savedView}/tags', [\App\Http\Controllers\ReportSavedViewTagController::class, 'sync'])->name('reports.saved-views.tags.sync');
    Route::post('/reports/saved-views/bulk-attach-tags', [\App\Http\Controllers\ReportSavedViewTagController::class, 'bulkAttach'])->name('reports.saved-views.bulk-attach-tags');
    Route::delete('/reports/saved-views/bulk-detach-tags', [\App\Http\Controllers\ReportSavedViewTagController::class, 'bulkDetach'])->name('reports.saved-views.bulk-detach-tags');
    Route::post('/reports/saved-views/import-preview', [\App\Http\Controllers\ReportSavedViewController::class, 'previewImport'])->name('reports.saved-views.import-preview');
    Route::post('/reports/saved-views/import-apply', [\App\Http\Controllers\ReportSavedViewController::class, 'applyImport'])->name('reports.saved-views.import-apply');
    Route::patch('/reports/saved-views/bulk-archive', [\App\Http\Controllers\ReportSavedViewController::class, 'bulkArchive'])->name('reports.saved-views.bulk-archive');
    Route::patch('/reports/saved-views/bulk-restore', [\App\Http\Controllers\ReportSavedViewController::class, 'bulkRestore'])->name('reports.saved-views.bulk-restore');
    Route::get('/reports/saved-views/{savedView}/edit', [\App\Http\Controllers\ReportSavedViewController::class, 'edit'])->name('reports.saved-views.edit');
    Route::patch('/reports/saved-views/{savedView}', [\App\Http\Controllers\ReportSavedViewController::class, 'update'])->name('reports.saved-views.update');
    Route::patch('/reports/saved-views/{savedView}/archive', [\App\Http\Controllers\ReportSavedViewController::class, 'archive'])->name('reports.saved-views.archive');
    Route::patch('/reports/saved-views/{savedView}/restore', [\App\Http\Controllers\ReportSavedViewController::class, 'restore'])->name('reports.saved-views.restore');
    Route::post('/reports/saved-views/{savedView}/duplicate', [\App\Http\Controllers\ReportSavedViewController::class, 'duplicate'])->name('reports.saved-views.duplicate');
    Route::get('/reports/saved-views/{savedView}/apply', [\App\Http\Controllers\ReportSavedViewController::class, 'apply'])->name('reports.saved-views.apply');
    Route::delete('/reports/saved-views', [\App\Http\Controllers\ReportSavedViewController::class, 'destroyAll'])->name('reports.saved-views.destroy-all');
    Route::patch('/reports/saved-views/{savedView}/default', [\App\Http\Controllers\ReportSavedViewController::class, 'makeDefault'])->name('reports.saved-views.make-default');
    Route::delete('/reports/saved-views/{savedView}', [\App\Http\Controllers\ReportSavedViewController::class, 'destroy'])->name('reports.saved-views.destroy');

    /* Phase 85B saved view sharing activity retention administration. */
    Route::middleware(
        'can:manage_saved_view_share_activity_retention'
    )->group(function (): void {
        Route::get(
            '/reports/saved-view-share-activity-retention',
            [\App\Http\Controllers\ReportSavedViewShareActivityRetentionAdminController::class, 'index']
        )->name('reports.saved-view-share-activity-retention.index');
    Route::get('/reports/saved-view-share-activity-retention/summary-cache-diagnostics', [\App\Http\Controllers\ReportSavedViewShareActivityRetentionAdminController::class, 'summaryCacheDiagnostics'])->name('reports.saved-view-share-activity-retention.summary-cache-diagnostics')->middleware(EnsurePartyPermission::class . ':manage_saved_view_share_activity_retention')
        ->middleware('audit.saved-view-retention-summary-cache-diagnostics-refresh')
        ->middleware('throttle:saved-view-retention-summary-cache-diagnostics-refresh');

        Route::post(
            '/reports/saved-view-share-activity-retention/preview',
            [\App\Http\Controllers\ReportSavedViewShareActivityRetentionAdminController::class, 'preview']
        )->name('reports.saved-view-share-activity-retention.preview');

        Route::post(
            '/reports/saved-view-share-activity-retention/execute',
            [\App\Http\Controllers\ReportSavedViewShareActivityRetentionAdminController::class, 'execute']
        )->name('reports.saved-view-share-activity-retention.execute');
    });

});
Route::post('/reports/sales-invoice-aging/saved-views', [\App\Http\Controllers\SalesInvoiceAgingReportController::class, 'storeSavedView'])->middleware('auth')->name('reports.sales-invoice-aging.saved-views.store');
Route::post('/reports/customer-sales-invoice-aging/saved-views', [\App\Http\Controllers\CustomerSalesInvoiceAgingReportController::class, 'storeSavedView'])->middleware('auth')->name('reports.customer-sales-invoice-aging.saved-views.store');
Route::post('/reports/supplier-purchase-invoice-aging/saved-views', [\App\Http\Controllers\SupplierPurchaseInvoiceAgingReportController::class, 'storeSavedView'])->middleware('auth')->name('reports.supplier-purchase-invoice-aging.saved-views.store');
Route::post('/reports/customer-sales-invoice-aging/open-invoices/saved-views', [\App\Http\Controllers\CustomerSalesInvoiceAgingDrilldownController::class, 'storeSavedView'])->middleware('auth')->name('reports.customer-sales-invoice-aging.drilldown.saved-views.store');
Route::post('/reports/supplier-purchase-invoice-aging/open-invoices/saved-views', [\App\Http\Controllers\SupplierPurchaseInvoiceAgingDrilldownController::class, 'storeSavedView'])->middleware('auth')->name('reports.supplier-purchase-invoice-aging.drilldown.saved-views.store');

Route::get('/reports/saved-view-diagnostics', function () {
    return view('reports.saved-view-diagnostics', [
        'diagnosticReport' => ReportSavedViewRegistryDiagnosticReport::build(),
        'diagnosticMarkdown' => ReportSavedViewRegistryDiagnosticReport::markdown(),
        'diagnosticWebLinks' => ReportSavedViewDiagnosticsWebLinks::items(),
        'diagnosticSnapshotActionLinks' => ReportSavedViewDiagnosticsWebLinks::snapshotActionItems(),
        'diagnosticCommandExamples' => ReportSavedViewDiagnosticsWebLinks::commandExamples(),
    ]);
})->middleware('auth')->name('reports.saved-view-diagnostics.index');

Route::get('/reports/saved-view-diagnostics/markdown', function () {
    return response(ReportSavedViewRegistryDiagnosticReport::markdown(), 200, [
        'Content-Type' => 'text/markdown; charset=UTF-8',
    ]);
})->middleware('auth')->name('reports.saved-view-diagnostics.markdown');

Route::get('/reports/saved-view-diagnostics/json', function () {
    return response()->json(ReportSavedViewRegistryDiagnosticReport::build());
})->middleware('auth')->name('reports.saved-view-diagnostics.json');

Route::post('/reports/saved-view-diagnostics/snapshots/markdown', function () {
    $snapshot = ReportSavedViewDiagnosticSnapshotExporter::exportMarkdown();

    return redirect()
        ->route('reports.saved-view-diagnostics.index')
        ->with('status', 'Markdown diagnostic snapshot written to '.$snapshot['relative_path']);
})->middleware('auth')->name('reports.saved-view-diagnostics.snapshots.markdown');

Route::post('/reports/saved-view-diagnostics/snapshots/json', function () {
    $snapshot = ReportSavedViewDiagnosticSnapshotExporter::exportJson();

    return redirect()
        ->route('reports.saved-view-diagnostics.index')
        ->with('status', 'JSON diagnostic snapshot written to '.$snapshot['relative_path']);
})->middleware('auth')->name('reports.saved-view-diagnostics.snapshots.json');

Route::post('/reports/saved-view-diagnostics/snapshots/prune', function () {
    $result = ReportSavedViewDiagnosticSnapshotExporter::pruneSnapshots((bool) request()->boolean('include_manifest'));

    return redirect()
        ->route('reports.saved-view-diagnostics.index')
        ->with('status', 'Diagnostic snapshots pruned: '.$result['deleted_count']);
})->middleware('auth')->name('reports.saved-view-diagnostics.snapshots.prune');

Route::get('/reports/saved-view-candidates', function (\App\Services\ReportSavedViewService $savedViews) {
    $savedViewsForReport = \Illuminate\Support\Facades\Schema::hasTable('report_saved_views')
        ? $savedViews->listForReport(request()->user(), 'saved-view-candidates')
        : collect();

    return view('reports.saved-view-candidates', [
        'candidateSummary' => ReportSavedViewCandidateScanner::summary(),
        'candidates' => ReportSavedViewCandidateScanner::candidates(),
        'candidateMarkdown' => ReportSavedViewCandidateScanner::markdown(),
        'candidateWebLinks' => ReportSavedViewCandidateScannerWebLinks::items(),
        'candidateCommandExamples' => ReportSavedViewCandidateScannerWebLinks::commandExamples(),
        'savedViews' => $savedViewsForReport,
    ]);
})->middleware('auth')->name('reports.saved-view-candidates.index');

Route::get('/reports/saved-view-candidates/markdown', function () {
    return response(ReportSavedViewCandidateScanner::markdown(), 200, [
        'Content-Type' => 'text/markdown; charset=UTF-8',
    ]);
})->middleware('auth')->name('reports.saved-view-candidates.markdown');

Route::get('/reports/saved-view-candidates/json', function () {
    return response()->json([
        'summary' => ReportSavedViewCandidateScanner::summary(),
        'candidates' => ReportSavedViewCandidateScanner::candidates(),
    ]);
})->middleware('auth')->name('reports.saved-view-candidates.json');

Route::post('/reports/saved-view-candidates/saved-views', function (\Illuminate\Http\Request $request, \App\Services\ReportSavedViewService $savedViews) {
    $validated = $request->validate([
        'name' => ['required', 'string', 'max:120'],
        'is_default' => ['nullable'],
    ]);

    $savedViews->save(
        $request->user(),
        'saved-view-candidates',
        $validated['name'],
        [],
        $request->boolean('is_default')
    );

    return redirect()
        ->route('reports.saved-view-candidates.index')
        ->with('status', 'تم حفظ عرض مرشحي Saved Views بنجاح.');
})->middleware('auth')->name('reports.saved-view-candidates.saved-views.store');

Route::get('/reports/saved-view-rollout-selector', function () {
    return view('reports.saved-view-rollout-selector', [
        'rolloutPlan' => ReportSavedViewRolloutSelector::plan(),
        'rolloutMarkdown' => ReportSavedViewRolloutSelector::markdown(),
        'rolloutWebLinks' => ReportSavedViewRolloutSelectorWebLinks::items(),
        'rolloutCommandExamples' => ReportSavedViewRolloutSelectorWebLinks::commandExamples(),
        'rolloutWorkflowSteps' => ReportSavedViewRolloutSelectorWebLinks::workflowSteps(),
    ]);
})->middleware('auth')->name('reports.saved-view-rollout-selector.index');

Route::get('/reports/saved-view-rollout-selector/markdown', function () {
    return response(ReportSavedViewRolloutSelector::markdown(), 200, [
        'Content-Type' => 'text/markdown; charset=UTF-8',
    ]);
})->middleware('auth')->name('reports.saved-view-rollout-selector.markdown');

Route::get('/reports/saved-view-rollout-selector/json', function () {
    return response()->json(ReportSavedViewRolloutSelector::plan());
})->middleware('auth')->name('reports.saved-view-rollout-selector.json');

Route::get('/reports/saved-view-rollout-target', function () {
    return view('reports.saved-view-rollout-target', [
        'rolloutTargetSummary' => ReportSavedViewRolloutTarget::summary(),
        'rolloutTargetMarkdown' => ReportSavedViewRolloutTarget::markdown(),
    ]);
})->middleware('auth')->name('reports.saved-view-rollout-target.index');

Route::get('/reports/saved-view-rollout-target/markdown', function () {
    return response(ReportSavedViewRolloutTarget::markdown(), 200, [
        'Content-Type' => 'text/markdown; charset=UTF-8',
    ]);
})->middleware('auth')->name('reports.saved-view-rollout-target.markdown');

Route::get('/reports/saved-view-rollout-target/json', function () {
    return response()->json(ReportSavedViewRolloutTarget::summary());
})->middleware('auth')->name('reports.saved-view-rollout-target.json');

/*
 * Phase 86B retention execution history.
 */
Route::get(
    '/reports/saved-view-share-activity-retention/history',
    function (\Illuminate\Http\Request $request) {
        $query = \App\Models\ReportSavedViewShareActivityRetentionExecution::query()
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        foreach (['type', 'status', 'actor_user_id'] as $filter) {
            if ($request->filled($filter)) {
                $query->where($filter, $request->input($filter));
            }
        }

        if ($request->filled('started_from')) {
            $query->where('started_at', '>=', $request->input('started_from'));
        }

        if ($request->filled('started_to')) {
            $query->where('started_at', '<=', $request->input('started_to'));
        }

        $perPage = min(
            max((int) $request->integer('per_page', 25), 1),
            100
        );

        return response()->json(
            $query->paginate($perPage)
        );
    }
)->middleware([
    'auth',
    'can:manage_saved_view_share_activity_retention',
])->name(
    'reports.saved-view-share-activity-retention.history'
);

/*
 * Phase 87B retention execution history export.
 */
Route::get(
    '/reports/saved-view-share-activity-retention/history/export/csv',
    [
        \App\Http\Controllers\ReportSavedViewShareActivityRetentionExecutionHistoryExportController::class,
        'csv',
    ]
)->middleware([
    'auth',
    'can:manage_saved_view_share_activity_retention',
])->name(
    'reports.saved-view-share-activity-retention.history.export.csv'
);

Route::get(
    '/reports/saved-view-share-activity-retention/history/export/json',
    [
        \App\Http\Controllers\ReportSavedViewShareActivityRetentionExecutionHistoryExportController::class,
        'json',
    ]
)->middleware([
    'auth',
    'can:manage_saved_view_share_activity_retention',
])->name(
    'reports.saved-view-share-activity-retention.history.export.json'
);
