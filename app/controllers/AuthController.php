<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/Auth.php';

final class AuthController
{
    public static function loginView(): void
    {
        require __DIR__ . '/../views/auth/login.php';
    }

    public static function registerView(): void
    {
        require __DIR__ . '/../views/auth/registar.php';
    }

    public static function logout(): void
    {
        Auth::logout();
        header('Location: /erpplus/index.php?page=login');
        exit;
    }

   /* public static function googleRedirect(): void
    {
        require_once __DIR__ . '/../services/GoogleOAuthService.php';
        $url = GoogleOAuthService::getAuthUrl();
        header('Location: ' . $url);
        exit;
    }

    public static function googleCallback(): void
    {
        require_once __DIR__ . '/../services/GoogleOAuthService.php';
        GoogleOAuthService::handleCallback();
    }

    public static function facebookRedirect(): void
    {
        require_once __DIR__ . '/../services/FacebookOAuthService.php';
        $url = FacebookOAuthService::getAuthUrl();
        header('Location: ' . $url);
        exit;
    }

    public static function facebookCallback(): void
    {
        require_once __DIR__ . '/../services/FacebookOAuthService.php';
        FacebookOAuthService::handleCallback();
    } */
}
