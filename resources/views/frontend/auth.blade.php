@extends('layouts.frontend')
@section('title', ($register ? '注册' : '登录').' · AI100分')
@section('content')
<div class="shell auth-main">
    <div class="auth-surface">
        <span class="eyebrow">你的每一步，都值得记录</span>
        <h1>{{ $register ? '开始你的100分。' : '继续把问题做成。' }}</h1>
        <p>{{ $register ? '用邮箱创建账号，先把第一步做成。' : '登录你的账号，继续下一步。' }}</p>
        <form class="auth-form" method="POST" action="{{ route($register ? 'register.store' : 'login.store') }}" data-submit-form>
            @csrf
            @if($register)
                <div class="form-field">
                    <label for="name">昵称</label>
                    <input class="form-control" id="name" name="name" value="{{ old('name') }}" autocomplete="nickname" maxlength="50" required autofocus @error('name') aria-invalid="true" aria-describedby="name-error" @enderror>
                    @error('name')<p class="form-error" id="name-error" role="alert">{{ $message }}</p>@enderror
                </div>
            @endif
            <div class="form-field">
                <label for="email">邮箱</label>
                <input class="form-control" id="email" type="email" name="email" value="{{ old('email') }}" autocomplete="username" maxlength="255" required @if(! $register) autofocus @endif @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
                @error('email')<p class="form-error" id="email-error" role="alert">{{ $message }}</p>@enderror
            </div>
            <div class="form-field">
                <label for="password">密码</label>
                <input class="form-control" id="password" type="password" name="password" autocomplete="{{ $register ? 'new-password' : 'current-password' }}" @if($register) minlength="8" maxlength="72" @endif required @error('password') aria-invalid="true" aria-describedby="password-error" @else @if($register) aria-describedby="password-help" @endif @enderror>
                @error('password')<p class="form-error" id="password-error" role="alert">{{ $message }}</p>@else @if($register)<p class="form-help" id="password-help">至少8个字符。</p>@endif @enderror
            </div>
            @if($register)
                <div class="form-field">
                    <label for="password-confirmation">确认密码</label>
                    <input class="form-control" id="password-confirmation" type="password" name="password_confirmation" autocomplete="new-password" minlength="8" maxlength="72" required>
                </div>
            @else
                <label class="form-checkbox"><input type="checkbox" name="remember" value="1" @checked(old('remember'))>记住登录状态</label>
            @endif
            <button class="button button-primary full-width" type="submit">{{ $register ? '注册并登录' : '登录' }}<i data-lucide="arrow-right"></i></button>
        </form>
        <p class="auth-switch">{{ $register ? '已经有账号？' : '还没有账号？' }} <a href="{{ route($register ? 'login' : 'register') }}">{{ $register ? '直接登录' : '创建账号' }}</a></p>
        <a class="text-link auth-secondary" href="{{ app(\App\Services\CourseCatalog::class)->starter()['url'] ?? route('free.index') }}">先免费体验第一课<i data-lucide="arrow-right"></i></a>
    </div>
</div>
@endsection
