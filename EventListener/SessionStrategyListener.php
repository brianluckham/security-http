<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Security\Http\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;
use Symfony\Component\Security\Http\Session\SessionAuthenticationStrategy;
use Symfony\Component\Security\Http\Session\SessionAuthenticationStrategyInterface;

/**
 * Migrates/invalidate the session after successful login.
 *
 * This should be registered as subscriber to any "stateful" firewalls.
 *
 * @see SessionAuthenticationStrategy
 *
 * @author Wouter de Jong <wouter@wouterj.nl>
 */
class SessionStrategyListener implements EventSubscriberInterface
{
    private $sessionAuthenticationStrategy;
    private $statelessProviderKeys;

    public function __construct(SessionAuthenticationStrategyInterface $sessionAuthenticationStrategy, array $statelessProviderKeys = [])
    {
        $this->sessionAuthenticationStrategy = $sessionAuthenticationStrategy;
        $this->statelessProviderKeys = $statelessProviderKeys;
    }

    public function onSuccessfulLogin(LoginSuccessEvent $event): void
    {
        $request = $event->getRequest();
        $token = $event->getAuthenticatedToken();
        $providerKey = $event->getProviderKey();

        if (!$request->hasSession() || !$request->hasPreviousSession() || \in_array($providerKey, $this->statelessProviderKeys, true)) {
            return;
        }

        $this->sessionAuthenticationStrategy->onAuthentication($request, $token);
    }

    public static function getSubscribedEvents(): array
    {
        return [LoginSuccessEvent::class => 'onSuccessfulLogin'];
    }
}