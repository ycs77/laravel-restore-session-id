# Laravel Restore Session ID

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE)
[![GitHub Tests Action Status][ico-github-action]][link-github-action]
[![Style CI Build Status][ico-style-ci]][link-style-ci]
[![Total Downloads][ico-downloads]][link-downloads]

Restore Laravel session ID when form post back from 3rd party API.

## Installation

Via Composer:

```bash
composer require ycs77/laravel-restore-session-id
```

## Usage

Currently, the default value for Laravel's Cookie SameSite is set to `Lax`. This prevents cookies from being sent when using form post to transmit data to websites on other domains. As a result, after completing a payment and being redirected back to the original website, there is an issue where the user appears to be automatically logged out due to the inability to retrieve the original login cookie.

To address this, we need to adjust the order of the middleware so that `RestoreSessionId` is placed below `StartSession`. By default, Laravel's `Kernel` does not have the `$middlewarePriority` property. You can find it in the Laravel Framework or copy the code below and paste it into `app/Http/Kernel.php`:

```php
class Kernel extends HttpKernel
{
    /**
     * The priority-sorted list of middleware.
     *
     * Forces non-global middleware to always be in the given order.
     *
     * @var string[]
     */
    protected $middlewarePriority = [
        \Illuminate\Foundation\Http\Middleware\HandlePrecognitiveRequests::class,
        \Illuminate\Cookie\Middleware\EncryptCookies::class,
        \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
        \Illuminate\Session\Middleware\StartSession::class,
        \Ycs77\LaravelRestoreSessionId\Middleware\RestoreSessionId::class, // need to place `RestoreSessionId` below `StartSession`
        \Illuminate\View\Middleware\ShareErrorsFromSession::class,
        \Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests::class,
        \Illuminate\Routing\Middleware\ThrottleRequests::class,
        \Illuminate\Routing\Middleware\ThrottleRequestsWithRedis::class,
        \Illuminate\Contracts\Session\Middleware\AuthenticatesSessions::class,
        \Illuminate\Routing\Middleware\SubstituteBindings::class,
        \Illuminate\Auth\Middleware\Authorize::class,
    ];
}
```

Then you can preserve current user source data before 3rd party API, make sure to return the same user to resume the session:

```php
use Ycs77\LaravelRestoreSessionId\RestoreSessionId;

public function form(Request $request)
{
    RestoreSessionId::preserveUserSource($request);

    // post form to 3rd party API...
}
```

Final, you can add the `RestoreSessionId` middleware to the callback route for the API. This middleware will automatically retrieve the encrypted session ID from the callback URL and restore the original session state:

```php
Route::post('/pay/callback', [PaymentController::class, 'callback'])
    ->middleware(\Ycs77\LaravelRestoreSessionId\Middleware\RestoreSessionId::class);
```

> Reference details for SameSite: https://developers.google.com/search/blog/2020/01/get-ready-for-new-samesitenone-secure

## Sponsor

If you think this package has helped you, please consider [Becoming a sponsor](https://www.patreon.com/ycs77) to support my work~ and your avatar will be visible on my major projects.

<p align="center">
  <a href="https://www.patreon.com/ycs77">
    <img src="https://cdn.jsdelivr.net/gh/ycs77/static/sponsors.svg"/>
  </a>
</p>

<a href="https://www.patreon.com/ycs77">
  <img src="https://c5.patreon.com/external/logo/become_a_patron_button.png" alt="Become a Patron" />
</a>

## Credits

* [SameSite Cookie 之踩坑過程](https://kira5033.github.io/2020/09/samesite-cookie-%E4%B9%8B%E8%B8%A9%E5%9D%91%E9%81%8E%E7%A8%8B/)
* [imi/laravel-transsid](https://github.com/iMi-digital/laravel-transsid)

## License

[MIT LICENSE](LICENSE)

[ico-version]: https://img.shields.io/packagist/v/ycs77/laravel-restore-session-id?style=flat-square
[ico-license]: https://img.shields.io/badge/license-MIT-brightgreen?style=flat-square
[ico-github-action]: https://img.shields.io/github/actions/workflow/status/ycs77/laravel-restore-session-id/tests.yml?branch=main&label=tests&style=flat-square
[ico-style-ci]: https://github.styleci.io/repos/651973134/shield?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/ycs77/laravel-restore-session-id?style=flat-square

[link-packagist]: https://packagist.org/packages/ycs77/laravel-restore-session-id
[link-github-action]: https://github.com/ycs77/laravel-restore-session-id/actions/workflows/tests.yml?query=branch%3Amain
[link-style-ci]: https://github.styleci.io/repos/651973134
[link-downloads]: https://packagist.org/packages/ycs77/laravel-restore-session-id