<?php

namespace Ycs77\LaravelRecoverSession\Middleware;

use Closure;
use Illuminate\Config\Repository as Config;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Contracts\Session\Session;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Ycs77\LaravelRecoverSession\Support\Base64Url;
use Ycs77\LaravelRecoverSession\UserSource;

class RecoverSession
{
    /**
     * The config instance.
     */
    protected Config $config;

    /**
     * The session store instance.
     */
    protected Session $session;

    /**
     * The encrypter instance.
     */
    protected Encrypter $encrypter;

    /**
     * The user source manager instance.
     */
    protected UserSource $userSource;

    /**
     * Create a new middleware.
     */
    public function __construct(Config $config,
                                Session $session,
                                Encrypter $encrypter,
                                UserSource $userSource)
    {
        $this->config = $config;
        $this->session = $session;
        $this->encrypter = $encrypter;
        $this->userSource = $userSource;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $encryptedSessionId = $this->getSessionIdFromRequest($request);

        if ($encryptedSessionId &&
            $sessionId = $this->decryptSessionId($encryptedSessionId)
        ) {
            $this->recoverSession($request, $this->session, $sessionId);
        }

        return $next($request);
    }

    /**
     * Get session ID from request.
     */
    protected function getSessionIdFromRequest(Request $request): string|null
    {
        return $request->query(
            $this->config->get('recover-session.session_id_key')
        );
    }

    /**
     * Decrypt the session ID from callback url query.
     */
    protected function decryptSessionId(string $sessionId): string
    {
        try {
            return $this->encrypter->decrypt(Base64Url::decode($sessionId), false);
        } catch (DecryptException $e) {
            $this->undecrypted($e);
        }
    }

    /**
     * Handle on undecrypted.
     */
    protected function undecrypted(DecryptException $e): void
    {
        //
    }

    /**
     * Recover the session ID for current request.
     */
    protected function recoverSession(Request $request, Session $session, string $sessionId): void
    {
        $session->setId($sessionId);

        $session->start();

        if (! $this->userSource->validate($request)) {
            // If user soruce is invalid, will regenerate a new session id.
            $session->setId(null);

            $session->start();
        }

        $this->userSource->clear();
    }
}
