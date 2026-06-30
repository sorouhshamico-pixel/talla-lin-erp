<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>صلاحيات العملاء والموردين</title>
    <style>
        body { font-family: Tahoma, Arial, sans-serif; background: #f4f6f8; color: #111827; padding: 24px; }
        .container { max-width: 1180px; margin: 0 auto; }
        .card { background: #fff; border-radius: 14px; padding: 18px; box-shadow: 0 8px 24px rgba(15, 23, 42, 0.08); margin-bottom: 18px; }
        h1 { margin: 0 0 8px; font-size: 26px; }
        h2 { margin: 0 0 10px; font-size: 20px; }
        .muted { color: #6b7280; font-size: 13px; }
        table { width: 100%; border-collapse: collapse; margin-top: 14px; }
        th, td { padding: 11px 10px; border-bottom: 1px solid #e5e7eb; text-align: right; vertical-align: top; }
        th { background: #f9fafb; font-size: 13px; color: #374151; }
        .badge { display: inline-block; padding: 4px 8px; border-radius: 999px; background: #dcfce7; color: #166534; font-size: 12px; font-weight: 700; }
        .badge.no { background: #fee2e2; color: #991b1b; }
        .role { font-weight: 800; direction: ltr; text-align: left; }
        @media (max-width: 800px) {
            table { display: block; overflow-x: auto; white-space: nowrap; }
            body { padding: 14px; }
        }
    </style>
</head>
<body>
<div class="container">
    <div class="card">
        <h1>صلاحيات العملاء والموردين</h1>
        <div class="muted">مرجع داخلي يوضح صلاحيات كل دور داخل وحدة العملاء والموردين.</div>
    </div>

    <div class="card" data-testid="party-permissions-matrix-card">
        <h2>جدول الصلاحيات</h2>

        <table data-testid="party-permissions-table">
            <thead>
                <tr>
                    <th>الدور</th>
                    @foreach($permissions as $permissionLabel)
                        <th>{{ $permissionLabel }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($rolePermissions as $role => $allowedPermissions)
                    <tr data-testid="party-permission-role-{{ $role }}">
                        <td class="role">{{ $role }}</td>

                        @foreach($permissions as $permission => $permissionLabel)
                            <td>
                                @if(in_array($permission, $allowedPermissions, true))
                                    <span class="badge" data-testid="party-permission-{{ $role }}-{{ $permission }}">مسموح</span>
                                @else
                                    <span class="badge no" data-testid="party-permission-denied-{{ $role }}-{{ $permission }}">غير مسموح</span>
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
