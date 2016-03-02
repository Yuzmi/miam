<?php

namespace MiamBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Security\Csrf\CsrfToken;

class MainController extends Controller
{
	public function getEm() {
		return $this->getDoctrine()->getManager();
	}

    public function getRepo($repository) {
    	return $this->getEm()->getRepository('MiamBundle:'.$repository);
    }

    public function isLogged() {
    	return $this->get('security.authorization_checker')->isGranted('ROLE_USER');
    }

    public function addFm($message, $type = "notice") {
    	$this->get('session')->getFlashBag()->add($type, $message);
    }

    public function isTokenValid($id, $value) {
        return $this->get('security.csrf.token_manager')->isTokenValid(new CsrfToken($id, $value));
    }
}
