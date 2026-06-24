<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'طلة لين ERP' }}</title>

    <style>
        :root {
            --bg: #f6f1eb;
            --card: #ffffff;
            --text: #2f2723;
            --muted: #7a6d66;
            --primary: #8b5e3c;
            --primary-dark: #5d3b25;
            --border: #e7dcd2;
            --sidebar: #2f2723;
            --sidebar-muted: #cdbfb4;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Tahoma, Arial, sans-serif;
            background: var(--bg);
            color: var(--text);
        }

        a {
            text-decoration: none;
            color: inherit;
        }

        .admin-shell {
            min-height: 100vh;
            display: grid;
            grid-template-columns: 260px 1fr;
        }

        .sidebar {
            background: var(--sidebar);
            color: #fff;
            padding: 22px;
        }

        .brand {
            font-size: 20px;
            font-weight: 800;
            margin-bottom: 28px;
        }

        .nav {
            display: grid;
            gap: 8px;
        }

        .nav a {
            display: block;
            padding: 12px 14px;
            border-radius: 12px;
            color: var(--sidebar-muted);
            font-size: 14px;
        }

        .nav a:hover,
        .nav a.active {
            background: rgba(255,255,255,.1);
            color: #fff;
        }

        .main {
            min-width: 0;
        }

        .topbar {
            height: 64px;
            background: #fff;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 28px;
        }

        .topbar-title {
            font-weight: 700;
        }

        .topbar-actions {
            display: flex;
            align-items: center;
            gap: 14px;
            color: var(--muted);
            font-size: 14px;
        }

        .logout-button {
            background: var(--primary);
            border: 0;
            color: #fff;
            border-radius: 10px;
            padding: 8px 14px;
            cursor: pointer;
        }

        .content {
            padding: 28px;
        }

        .card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 18px;
            padding: 24px;
            box-shadow: 0 10px 28px rgba(69, 42, 23, .06);
        }

        .page-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 20px;
        }

        .page-title {
            margin: 0 0 8px;
            font-size: 26px;
        }

        .muted {
            color: var(--muted);
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 18px;
        }

        .metric {
            background: #fff;
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 20px;
        }

        .metric-label {
            color: var(--muted);
            font-size: 13px;
            margin-bottom: 8px;
        }

        .metric-value {
            font-size: 28px;
            font-weight: 700;
        }

        .table-wrap {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 760px;
        }

        th,
        td {
            text-align: right;
            padding: 14px;
            border-bottom: 1px solid var(--border);
            font-size: 14px;
        }

        th {
            color: var(--muted);
            font-weight: 700;
            background: #fbf8f5;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            padding: 5px 10px;
            font-size: 12px;
            background: #eee4dc;
            color: var(--primary-dark);
        }

        .badge.green {
            background: #e8f5ee;
            color: #157347;
        }

        .badge.gray {
            background: #f1f1f1;
            color: #555;
        }

        @media (max-width: 900px) {
            .admin-shell {
                grid-template-columns: 1fr;
            }

            .sidebar {
                position: static;
            }

            .nav {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .grid {
                grid-template-columns: 1fr;
            }

            .content {
                padding: 18px;
            }
        }
    </style>
</head>
<body>
    <div class="admin-shell">
        <aside class="sidebar">
            <div class="brand">طلة لين ERP</div>

            <nav class="nav">
                <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    لوحة التحكم
                </a>

                <a href="{{ route('branches.index') }}" class="{{ request()->routeIs('branches.*') ? 'active' : '' }}">
                    الفروع
                </a>

                <a href="{{ route('warehouses.index') }}" class="{{ request()->routeIs('warehouses.*') ? 'active' : '' }}">
                    المستودعات
                </a>

                <a href="{{ route('categories.index') }}" class="{{ request()->routeIs('categories.*') ? 'active' : '' }}">
                    التصنيفات
                </a>

                <a href="{{ route('products.index') }}" class="{{ request()->routeIs('products.*') ? 'active' : '' }}">
                    المنتجات
                </a>

                <a href="{{ route('inventory.index') }}" class="{{ request()->routeIs('inventory.*') ? 'active' : '' }}">
                    المخزون
                </a>
            </nav>
        </aside>

        <section class="main">
            <header class="topbar">
                <div class="topbar-title">{{ $header ?? 'لوحة التحكم' }}</div>

                <div class="topbar-actions">
                    <span>{{ auth()->user()?->name }}</span>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="logout-button" type="submit">تسجيل الخروج</button>
                    </form>
                </div>
            </header>

            <main class="content">
                @yield('content')
            </main>
        </section>
    </div>
</body>
</html>
