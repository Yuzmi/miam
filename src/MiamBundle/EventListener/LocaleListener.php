<?php
	namespace MiamBundle\EventListener;

	use Symfony\Component\EventDispatcher\EventSubscriberInterface;
	use Symfony\Component\HttpKernel\Event\GetResponseEvent;
	use Symfony\Component\HttpKernel\KernelEvents;

	class LocaleListener implements EventSubscriberInterface
	{
		private $defaultLocale;

		public function __construct($defaultLocale) {
			$this->defaultLocale = $defaultLocale;
		}

		public function onKernelRequest(GetResponseEvent $event) {
			$request = $event->getRequest();
			if(!$request->hasPreviousSession()) {
				return;
			}

			if($locale = $request->attributes->get('_locale')) {
				$request->getSession()->set('_locale', $locale);
			} elseif($locale = $request->getSession()->get('_locale')) {
				$request->setLocale($locale);
			} elseif(isset($_COOKIE['_locale'])) {
				$request->setLocale($_COOKIE['_locale']);
			} else {
				$request->setLocale($this->defaultLocale);
			}
		}

		public static function getSubscribedEvents() {
			return array(
				KernelEvents::REQUEST => array(array('onKernelRequest', 15))
			);
		}
	}