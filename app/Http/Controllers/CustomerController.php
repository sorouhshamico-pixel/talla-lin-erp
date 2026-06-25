<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function index(): View
    {
        $customers = Customer::query()
            ->withCount('salesInvoices')
            ->orderBy('id')
            ->get();

        return view('customers.index', [
            'customers' => $customers,
        ]);
    }
}
