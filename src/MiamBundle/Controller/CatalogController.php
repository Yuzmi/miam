<?php

namespace MiamBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use MiamBundle\Entity\Feed;
use MiamBundle\Entity\Subscription;

class CatalogController extends MainController
{
	public function indexAction() {
		return $this->forward('MiamBundle:Catalog:showItems');
	}

	public function showFeedsAction() {
		$feeds = $this->getRepo('Feed')->findCatalog();

		$subscribedFeedIds = array();
        if($this->isLogged()) {
            $subscribedFeedIds = $this->getRepo('Subscription')->findFeedIdsForUser($this->getUser());
        }

        return $this->render('MiamBundle:Catalog:feeds.html.twig', array(
            'feeds' => $feeds,
            'subscribedFeedIds' => $subscribedFeedIds
        ));
	}

	public function showItemsAction() {
		$items = $this->get('item_manager')->getItems(array(
			'catalog' => true,
			'nb' => 50,
			'reader' => $this->getUser()
		));

		$dataItems = $this->get('item_manager')->getDataForItems($items);

		return $this->render('MiamBundle:Catalog:items.html.twig', array(
			'items' => $items,
			'dataItems' => $dataItems
		));
	}

	public function ajaxSubscribeToFeedAction($id) {
		$success = false;

		if($this->isLogged()) {
			$feed = $this->getRepo('Feed')->find($id);
			if($feed) {
				$subscription = $this->getRepo('Subscription')->findOneBy(array(
					'user' => $this->getUser(),
					'feed' => $feed
				));
				if(!$subscription) {
					$subscription = new Subscription();
					$subscription->setUser($this->getUser());
					$subscription->setFeed($feed);
					$subscription->setName($feed->getName());

					$em = $this->getEm();
					$em->persist($subscription);
					$em->flush();
				}
				
				$success = true;
			}
		}

		return new Response(json_encode(array(
			'success' => $success
		)));
	}

	public function ajaxUnsubscribeFromFeedAction($id) {
		$success = false;

		if($this->isLogged()) {
			$feed = $this->getRepo('Feed')->find($id);
			if($feed) {
				$unsubscribe = $this->get('feed_manager')->unsubscribeUserFromFeed($this->getUser(), $feed);
				if($unsubscribe) {
					$success = true;
				}
			}
		}

		return new Response(json_encode(array(
			'success' => $success
		)));
	}
}
