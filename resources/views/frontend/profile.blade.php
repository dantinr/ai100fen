<section class="account-section" aria-label="账号设置">
    <div class="account-summary">
        <div><h2>{{ $user->name }}</h2><p>{{ $user->email }} · {{ $user->created_at->format('Y-m-d') }}加入</p></div>
        <form method="POST" action="{{ route('logout') }}">@csrf<button class="button button-outline button-small" type="submit">退出登录</button></form>
    </div>
    <div class="account-grid">
        <form class="account-panel auth-form" method="POST" action="{{ route('profile.update') }}" data-submit-form>
            @csrf
            @method('PATCH')
            <h2>个人资料</h2>
            <div class="form-field">
                <label for="profile-name">昵称</label>
                <input class="form-control" id="profile-name" name="name" value="{{ old('name', $user->name) }}" autocomplete="nickname" maxlength="50" required @error('name') aria-invalid="true" aria-describedby="profile-name-error" @enderror>
                @error('name')<p class="form-error" id="profile-name-error" role="alert">{{ $message }}</p>@enderror
            </div>
            <div class="form-field">
                <label for="profile-email">登录邮箱</label>
                <input class="form-control" id="profile-email" type="email" value="{{ $user->email }}" autocomplete="username" readonly aria-describedby="profile-email-help">
                <p class="form-help" id="profile-email-help">邮箱用于登录，暂不支持修改。</p>
            </div>
            @if(session('status') === 'profile-updated')<p class="form-status" role="status">昵称已保存。</p>@endif
            <button class="button button-primary" type="submit">保存资料</button>
        </form>
        <form class="account-panel auth-form" method="POST" action="{{ route('password.update') }}" data-submit-form>
            @csrf
            @method('PUT')
            <h2>修改密码</h2>
            <div class="form-field">
                <label for="current-password">当前密码</label>
                <input class="form-control" id="current-password" type="password" name="current_password" autocomplete="current-password" required @error('current_password') aria-invalid="true" aria-describedby="current-password-error" @enderror>
                @error('current_password')<p class="form-error" id="current-password-error" role="alert">{{ $message }}</p>@enderror
            </div>
            <div class="form-field">
                <label for="new-password">新密码</label>
                <input class="form-control" id="new-password" type="password" name="password" autocomplete="new-password" minlength="8" maxlength="72" required @error('password') aria-invalid="true" aria-describedby="new-password-error" @enderror>
                @error('password')<p class="form-error" id="new-password-error" role="alert">{{ $message }}</p>@enderror
            </div>
            <div class="form-field">
                <label for="new-password-confirmation">确认新密码</label>
                <input class="form-control" id="new-password-confirmation" type="password" name="password_confirmation" autocomplete="new-password" minlength="8" maxlength="72" required>
                <p class="form-help">至少8个字符。修改后其他设备需要重新登录。</p>
            </div>
            @if(session('status') === 'password-updated')<p class="form-status" role="status">密码已修改。</p>@endif
            <button class="button button-outline" type="submit">更新密码</button>
        </form>
    </div>
</section>
