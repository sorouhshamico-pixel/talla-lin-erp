@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="page-header">
            <div>
                <h1>تقرير أعمار ذمم الموردين</h1>
                <p>متابعة فواتير المشتريات المفتوحة حسب المورد وشرائح العمر.</p>
            </div>

            <a href="{{ route('reports.index') }}" class="btn btn-outline-secondary">مركز التقارير</a>
        </div>

        <div class="card" data-testid="supplier-purchase-invoice-aging-report">
            <div class="card-body">
                <h2>تقرير أعمار ذمم الموردين</h2>

                <p data-testid="supplier-aging-report-date">تاريخ التقرير: {{ $reportDate }}</p>
                <p data-testid="supplier-aging-supplier-filter">فلتر المورد: {{ $supplierFilter ?: 'all' }}</p>
                <p data-testid="supplier-aging-bucket-filter">فلتر شريحة العمر: {{ $agingBucketFilter ?: 'all' }}</p>

                <div data-testid="supplier-aging-skeleton">
                    سيتم ربط بيانات فواتير المشتريات المفتوحة وشرائح الأعمار في المرحلة التالية.
                </div>
            </div>
        </div>
    </div>
@endsection
