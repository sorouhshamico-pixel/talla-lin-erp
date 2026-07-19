@extends('layouts.app')

@section('content')
    <div class="container"
         data-testid="shared-saved-views-page">
        <div class="page-header">
            <div>
                <h1>العروض المحفوظة المشتركة معي</h1>
                <p>
                    العروض التي شاركها مستخدمون آخرون معك.
                </p>
            </div>

            <a href="{{ route('reports.saved-views.index') }}"
               class="btn btn-outline-secondary">
                العروض المحفوظة
            </a>
        </div>

        @if ($shares->isEmpty())
            <div class="card empty-state"
                 data-testid="shared-saved-views-empty">
                لا توجد عروض محفوظة مشتركة معك.
            </div>
        @else
            <div class="card">
                <div class="table-responsive">
                    <table class="table"
                           data-testid="shared-saved-views-table">
                        <thead>
                            <tr>
                                <th>اسم العرض</th>
                                <th>التقرير</th>
                                <th>المالك</th>
                                <th>الصلاحية</th>
                                <th>الحالة</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($shares as $share)
                                <tr data-testid="shared-saved-view-row">
                                    <td>
                                        <strong>
                                            {{ $share->savedView?->name }}
                                        </strong>
                                    </td>
                                    <td dir="ltr">
                                        {{ $share->savedView?->report_key }}
                                    </td>
                                    <td>
                                        {{ $share->owner?->name }}
                                    </td>
                                    <td>
                                        {{ $share->permission === 'use'
                                            ? 'عرض وتطبيق'
                                            : 'عرض فقط' }}
                                    </td>
                                    <td>
                                        @if ($share->savedView?->isArchived())
                                            مؤرشف
                                        @else
                                            نشط
                                        @endif
                                    </td>
                                    <td>
                                        @if (
                                            $share->permission === 'use'
                                            && ! $share->savedView
                                                ?->isArchived()
                                        )
                                            <a href="{{ route(
                                                'reports.shared-saved-views.apply',
                                                $share
                                            ) }}"
                                               class="btn btn-primary"
                                               data-testid="shared-saved-view-apply-button">
                                                تطبيق العرض
                                            </a>
                                        @else
                                            <span class="text-muted">
                                                التطبيق غير متاح
                                            </span>
                                        @endif

                                        <form method="POST"
                                              action="{{ route(
                                                  'reports.shared-saved-views.copy',
                                                  $share
                                              ) }}"
                                              style="display:inline-block;">
                                            @csrf

                                            <button type="submit"
                                                    class="btn btn-outline-secondary"
                                                    data-testid="shared-saved-view-copy-button">
                                                نسخ إلى حسابي
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
@endsection
