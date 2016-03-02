<?php

namespace MiamBundle\Controller;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use MiamBundle\Entity\Feed;

class AdminController extends MainController
{
	public function indexAction() {
        $feeds = $this->getRepo('Feed')
            ->createQueryBuilder('f')
            ->orderBy('f.name', 'ASC')
            ->getQuery()->getResult();

        $createFeedForm = $this->createCreateFeedForm();

        return $this->render('MiamBundle:Admin:index.html.twig', array(
            'feeds' => $feeds,
            'createFeedForm' => $createFeedForm->createView()
        ));
	}

    private function createCreateFeedForm() {
    	return $this->createFormBuilder()
    		->setAction($this->generateUrl('admin_feed_create'))
    		->setMethod('POST')
    		->add('url', TextType::class)
    		->add('submit', SubmitType::class, array('label' => "Add"))
    		->getForm();
    }

    public function createFeedAction(Request $request) {
    	$form = $this->createCreateFeedForm();
    	$form->handleRequest($request);

    	if($form->isValid()) {
            $this->get('feed_manager')->createFeedForUrl($form->get('url')->getData());
    	}

    	return $this->redirectToRoute('admin');
    }

    public function parseFeedAction($id) {
        $feed = $this->getRepo('Feed')->find($id);
        if($feed) {
            $this->get('data_parsing')->parseFeed($feed);
        }
        
        return $this->redirectToRoute('admin');
    }

    public function deleteFeedAction($id) {
        $feed = $this->getrepo("Feed")->find($id);
        if($feed) {
            $this->get('feed_manager')->deleteFeed($feed);
        }

        return $this->redirectToRoute('admin');
    }
}
