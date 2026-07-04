<?php

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
    Route::get('/delivery-notes/{deliveryNote}', [DeliveryNoteController::class, 'show'])->name('delivery-notes.show');
    Route::get('/sales-orders/{salesOrder}/print', [SalesOrderController::class, 'print'])->name('sales-orders.print');
    Route::get('/sales-orders/{salesOrder}', [SalesOrderController::class, 'show'])->name('sales-orders.show');
    Route::get('/quotations/{quotation}/print', [QuotationController::class, 'print'])->name('quotations.print');
    Route::get('/quotations/{quotation}', [QuotationController::class, 'show'])->name('quotations.show');
    Route::post('/quotations/{quotation}/items', [QuotationItemController::class, 'store'])->name('quotations.items.store');
    Route::patch('/quotations/{quotation}/items/{item}', [QuotationItemController::class, 'update'])->name('quotations.items.update');
    Route::delete('/quotations/{quotation}/items/{item}', [QuotationItemController::class, 'destroy'])->name('quotations.items.destroy');

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

    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');

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

Route::get('/reports/financial-dashboard', \App\Http\Controllers\FinancialDashboardController::class)
    ->middleware('auth')
    ->name('reports.financial-dashboard');

Route::get('/reports/center', \App\Http\Controllers\ReportsCenterController::class)
    ->middleware('auth')
    ->name('reports.center');
