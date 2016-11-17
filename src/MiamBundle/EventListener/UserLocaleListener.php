<?php
	namespace MiamBundle\EventListener;

	use Symfony\Component\HttpFoundation\Session\Session;
	use Symfony\Component\HttpKernel\Event\GetResponseEvent;
	use Symfony\Component\HttpKernel\KernelEvents;
	use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

	class UserLocaleListener
	{
		private $session;

		public function __construct(Session $session) {
			$this->session = $session;
		}

		public function onInteractiveLogin(InteractiveLoginEvent $event) {
			$user = $event->getAuthenticationToken()->getUser();

			if($user->getLocale() !== null) {
				$this->session->set('_locale', $user->getLocale());
			}
		}
	}