@if(session('social_auth_error'))
    <div class="alert alert-danger auth-social-alert" role="alert">
        {{ session('social_auth_error') }}
    </div>
@endif

<div class="auth-divider" aria-hidden="true">
    <span>або</span>
</div>

<div class="auth-social">
    <a href="{{ route('social.redirect', 'google') }}" class="auth-social-button auth-social-button--google">
        <i class="bi bi-google" aria-hidden="true"></i>
        Продовжити з Google
    </a>
    <a href="{{ route('social.redirect', 'facebook') }}" class="auth-social-button auth-social-button--facebook">
        <i class="bi bi-facebook" aria-hidden="true"></i>
        Продовжити з Facebook
    </a>
</div>
