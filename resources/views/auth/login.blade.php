@extends('layouts.app', ['title' => 'تسجيل الدخول | طلة لين ERP'])

@section('content')
    <div class="form-wrap">
        <div class="card login-card">
            <h1 style="margin-top: 0; margin-bottom: 8px;">تسجيل الدخول</h1>
            <p class="muted" style="margin-top: 0; margin-bottom: 24px;">
                نظام إدارة ومحاسبة متجر طلة لين
            </p>

            @if ($errors->any())
                <div class="error">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('login.attempt') }}">
                @csrf

                <div class="field">
                    <label class="label" for="email">البريد الإلكتروني</label>
                    <input
                        class="input"
                        id="email"
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        required
                        autofocus
                    >
                </div>

                <div class="field">
                    <label class="label" for="password">كلمة المرور</label>
                    <input
                        class="input"
                        id="password"
                        type="password"
                        name="password"
                        required
                    >
                </div>

                <div class="field">
                    <label style="display: flex; align-items: center; gap: 8px; color: var(--muted); font-size: 14px;">
                        <input type="checkbox" name="remember" value="1">
                        تذكرني
                    </label>
                </div>

                <button class="button" type="submit">دخول</button>
            </form>

            <div class="hint">
                بيانات المدير الافتراضية للتجربة المحلية:<br>
                البريد: admin@tallalin.local<br>
                كلمة المرور: password
            </div>
        </div>
    </div>
@endsection
