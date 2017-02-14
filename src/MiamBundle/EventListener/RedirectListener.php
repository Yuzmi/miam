<?php
	namespace MiamBundle\EventListener;

	use Symfony\Component\HttpFoundation\RedirectResponse;
	use Symfony\Component\HttpKernel\Event\GetResponseEvent;
	use Symfony\Component\Routing\RouterInterface;
	use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

	use MiamBundle\Entity\User;

	class RedirectListener
	{
		private $router;
		private $tokenStorage;

		public function __construct(RouterInterface $r, TokenStorageInterface $t) {
			$this->router = $r;
			$this->tokenStorage = $t;
		}

		public function onKernelRequest(GetResponseEvent $event) {
			if($event->isMasterRequest()) {
				$route = $event->getRequest()->attributes->get('_route');

				// Admin
				if(preg_match('/^admin($|_)/', $route)) {
					if(!$this->isUserLogged()) {
						$response = new RedirectResponse($this->router->generate('login'));
						$event->setResponse($response);
					} elseif(!in_array('ROLE_ADMIN', $this->getUser()->getRoles())) {
						$response = new RedirectResponse($this->router->generate('index'));
						$event->setResponse($response);
					}
				}

				// Manager
				elseif(preg_match('/^manager($|_)/', $route)) {
					if(!$this->isUserLogged()) {
						$response = new RedirectResponse($this->router->generate('login'));
						$event->setResponse($response);
					}
				}

				// Login
				elseif(preg_match('/^login$/', $route)) {
					if($this->isUserLogged()) {
						$response = new RedirectResponse($this->router->generate('index'));
						$event->setResponse($response);
					}
				}
			}
		}

		private function getUser() {
			return $this->tokenStorage->getToken()->getUser();
		}

		private function isUserLogged() {
			$user = $this->getUser();
			return $user instanceof User;
		}
	}
