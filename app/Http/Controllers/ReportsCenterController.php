<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class ReportsCenterController extends Controller
{
    public function __invoke(): View
    {
        return view('reports.center');
    }
}
