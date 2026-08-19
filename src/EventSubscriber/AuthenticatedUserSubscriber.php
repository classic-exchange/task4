<?php

namespace App\EventSubscriber;

use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class AuthenticatedUserSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private Security $security,
        private UrlGeneratorInterface $urlGenerator,
    ) {}

    public function onRequestEvent(RequestEvent $event): void
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            return;
        }
        if ($user->getStatus() !== 'blocked') {
            return;
        }
        $this->security->logout(false);
        $event->setResponse(
            new RedirectResponse($this->urlGenerator->generate('app_login'))
        );
    }

    public static function getSubscribedEvents(): array
    {
        return [
            RequestEvent::class => 'onRequestEvent',
        ];
    }
}
