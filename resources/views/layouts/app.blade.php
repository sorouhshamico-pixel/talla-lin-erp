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
            --danger: #b42318;
            --success: #157347;
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
            color: inherit;
            text-decoration: none;
        }

        .page {
            min-height: 100vh;
        }

        .topbar {
            height: 64px;
            background: var(--primary-dark);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 28px;
            border-bottom: 1px solid rgba(255,255,255,.12);
        }

        .brand {
            font-weight: 700;
            font-size: 18px;
        }

        .topbar-actions {
            display: flex;
            align-items: center;
            gap: 14px;
            font-size: 14px;
        }

        .logout-button {
            background: transparent;
            border: 1px solid rgba(255,255,255,.4);
            color: #fff;
            border-radius: 10px;
            padding: 8px 14px;
            cursor: pointer;
        }

        .container {
            max-width: 1180px;
            margin: 0 auto;
            padding: 28px;
        }

        .card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 18px;
            padding: 24px;
            box-shadow: 0 10px 28px rgba(69, 42, 23, .06);
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

        .muted {
            color: var(--muted);
        }

        .form-wrap {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }

        .login-card {
            width: 100%;
            max-width: 430px;
        }

        .field {
            margin-bottom: 16px;
        }

        .label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            font-size: 14px;
        }

        .input {
            width: 100%;
            border: 1px solid var(--border);
            background: #fff;
            border-radius: 12px;
            padding: 12px 14px;
            font-size: 15px;
            outline: none;
        }

        .input:focus {
            border-color: var(--primary);
        }

        .button {
            width: 100%;
            border: 0;
            background: var(--primary);
            color: #fff;
            border-radius: 12px;
            padding: 13px 16px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
        }

        .button:hover {
            background: var(--primary-dark);
        }

        .error {
            background: #fff2f0;
            color: var(--danger);
            border: 1px solid #ffd0c9;
            padding: 12px;
            border-radius: 12px;
            margin-bottom: 16px;
            font-size: 14px;
        }

        .hint {
            margin-top: 16px;
            font-size: 13px;
            color: var(--muted);
            line-height: 1.8;
        }

        @media (max-width: 800px) {
            .grid {
                grid-template-columns: 1fr;
            }

            .topbar {
                padding: 0 16px;
            }

            .container {
                padding: 18px;
            }
        }
    </style>
</head>
<body>
    <div class="page">
        @yield('content')
    </div>
</body>
</html>
