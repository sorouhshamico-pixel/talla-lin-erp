@extends('layouts.app')

@section('content')
    <div class="container"
         data-testid="report-saved-view-share-manager">
        <div class="page-header">
            <div>
                <h1>مشاركة العرض المحفوظ</h1>
                <p>
                    {{ $savedView->name }}
                </p>
            </div>

            <a href="{{ route('reports.saved-views.index') }}"
               class="btn btn-outline-secondary">
                العودة إلى العروض المحفوظة
            </a>
        </div>

        @if (session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="card" style="margin-bottom: 16px;">
            <h2>إضافة مستلم</h2>

            <form method="POST"
                  action="{{ route(
                      'reports.saved-views.shares.store',
                      $savedView
                  ) }}">
                @csrf

                <div class="form-group">
                    <label for="share_recipient_user_id">
                        المستلم
                    </label>

                    <select id="share_recipient_user_id"
                            name="recipient_user_id"
                            required
                            data-testid="report-saved-view-share-recipient">
                        <option value="">اختر المستخدم</option>

                        @foreach ($recipientOptions as $recipientOption)
                            <option value="{{ $recipientOption->id }}">
                                {{ $recipientOption->name }}
                                @if ($recipientOption->email)
                                    - {{ $recipientOption->email }}
                                @endif
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label for="share_permission">
                        الصلاحية
                    </label>

                    <select id="share_permission"
                            name="permission"
                            required
                            data-testid="report-saved-view-share-permission">
                        <option value="view">
                            عرض فقط
                        </option>
                        <option value="use">
                            عرض وتطبيق
                        </option>
                    </select>
                </div>

                <button type="submit"
                        class="btn btn-primary">
                    مشاركة العرض
                </button>
            </form>
        </div>

        <div class="card">
            <h2>المستلمون الحاليون</h2>

            @if ($shares->isEmpty())
                <div class="empty-state"
                     data-testid="report-saved-view-shares-empty">
                    لم تتم مشاركة هذا العرض حتى الآن.
                </div>
            @else
                <div class="table-responsive">
                    <table class="table"
                           data-testid="report-saved-view-shares-table">
                        <thead>
                            <tr>
                                <th>المستلم</th>
                                <th>الصلاحية</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($shares as $share)
                                <tr data-testid="report-saved-view-share-row">
                                    <td>
                                        <strong>
                                            {{ $share->recipient?->name }}
                                        </strong>
                                        <div class="text-muted">
                                            {{ $share->recipient?->email }}
                                        </div>
                                    </td>
                                    <td>
                                        <form method="POST"
                                              action="{{ route(
                                                  'reports.saved-view-shares.update',
                                                  $share
                                              ) }}">
                                            @csrf
                                            @method('PATCH')

                                            <select name="permission"
                                                    data-testid="report-saved-view-share-permission">
                                                <option value="view"
                                                    @selected(
                                                        $share->permission
                                                        === 'view'
                                                    )>
                                                    عرض فقط
                                                </option>
                                                <option value="use"
                                                    @selected(
                                                        $share->permission
                                                        === 'use'
                                                    )>
                                                    عرض وتطبيق
                                                </option>
                                            </select>

                                            <button type="submit"
                                                    class="btn btn-outline-secondary">
                                                تحديث
                                            </button>
                                        </form>
                                    </td>
                                    <td>
                                        <form method="POST"
                                              action="{{ route(
                                                  'reports.saved-view-shares.destroy',
                                                  $share
                                              ) }}"
                                              onsubmit="return confirm(
                                                  'هل تريد إلغاء المشاركة؟'
                                              );">
                                            @csrf
                                            @method('DELETE')

                                            <button type="submit"
                                                    class="btn btn-outline-danger"
                                                    data-testid="report-saved-view-share-revoke-button">
                                                إلغاء المشاركة
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
@endsection
