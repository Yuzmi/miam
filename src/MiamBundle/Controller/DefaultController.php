<?php

namespace MiamBundle\Controller;

class DefaultController extends MainController
{
	public function indexAction() {
        if($this->isLogged()) {
            return $this->forward('MiamBundle:Shit:index', array(
                'userId' => $this->getUser()->getId()
            ));
        }

    	return $this->redirectToRoute('login');
	}
}
