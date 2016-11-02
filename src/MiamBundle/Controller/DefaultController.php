<?php

namespace MiamBundle\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use MiamBundle\Entity\Item;
use MiamBundle\Entity\PshbSubscription;

class DefaultController extends MainController
{
	public function indexAction() {
		if($this->isLogged()) {
            return $this->forward('MiamBundle:Shit:index', array(
                'userId' => $this->getUser()->getId()
            ));
        }

    	return $this->redirectToRoute('catalog');
	}

	public function ajaxGetItemAction(Request $request) {
		$success = false;
		$htmlData = null;

		if($request->isXmlHttpRequest()) {
			$item = $this->getRepo("Item")->find($request->get("id"));
			if($item && $this->isItemReadable($item)) {
				$htmlData = $this->renderView('MiamBundle:Default:item_data.html.twig', array(
					'item' => $item,
					'dataItem' => $this->getRepo("Item")->getDataForItem($item)
				));

				$success = true;
			}
		}

		return new JsonResponse(array(
			'success' => $success,
			'htmlData' => $htmlData
		));
	}

	// Check if you can read an item
	private function isItemReadable(Item $item) {
		// Catalog
		if($item->getFeed()->getIsCatalog()) {
			return true;
		}

		// Admin
		if($this->isLogged() && $this->getUser()->getIsAdmin()) {
			return true;
		}

		// User subscription
		$qb = $this->getRepo("Subscription")->createQueryBuilder("s")
			->leftJoin('s.user', 'u')
			->where('s.feed = :feed')->setParameter('feed', $item->getFeed());

		if($this->isLogged()) {
			$qb->andWhere('u.id = :userId OR u.isPublic = TRUE');
			$qb->setParameter('userId', $this->getUser()->getId());
		} else {
			$qb->andWhere('u.isPublic = TRUE');
		}
		
		$subscriptions = $qb->getQuery()->getResult();
		if($subscriptions->count() > 0) {
			return true;
		}

		return false;
	}

	// https://pubsubhubbub.github.io/PubSubHubbub/pubsubhubbub-core-0.4.html
	public function pshbAction(Request $request) {
		$em = $this->getEm();
		$now = new \DateTime("now");

		if($request->getMethod() == "GET") {
			$mode = $request->query->get('hub_mode');
			$topic = $request->query->get('hub_topic');
			$challenge = $request->query->get('hub_challenge');
			$lease_seconds = intval($request->query->get('hub_lease_seconds'));
			$reason = $request->query->get('hub_reason');

			$feed = $this->get('feed_manager')->findFeedForUrl($topic);
			if($feed) {
				$subscription = $this->getRepo("PshbSubscription")->findOneByFeed($feed);
				if(!$subscription) {
					$subscription = new PshbSubscription();
					$subscription->setFeed($feed);
				}

				if($mode == "subscribe") {
					$subscription->setDateSubscribed($now);
					$subscription->setLeaseSeconds($lease_seconds);
					$em->persist($subscription);
					$em->flush();

					return new Response($challenge, 200);
				} elseif($mode == "unsubscribe") {
					// ...
				} elseif($mode == "denied") {
					$subscription->setDateDenied($now);
					$subscription->setReason($reason);
					$em->persist($subscription);
					$em->flush();

					return new Response("", 200);
				}
			}
		} elseif($request->getMethod() == "POST") {
			// Feed
			$feed = null;
			$header_links = explode(',', $request->headers->get("link"));
			if($header_links) {
				foreach($header_links as $hl) {
					if(preg_match('#^<(.*)>;rel=self$#i', $hl, $matches)) {
						$feed = $this->get('feed_manager')->findFeedForUrl($matches[1]);
					}
				}
			}

			// Data
			$data = file_get_contents("php://input");

			// HMAC
			$signature = explode('=', $request->headers->get("X-Hub-Signature"));
			$received_hmac = count($signature) > 1 ? $signature[1] : null;

			if($feed && $data !== false && !empty($received_hmac)) {
				$secret = $this->container->getParameter("pshb_secret");
				$calculated_hmac = hash_hmac('sha1', $data, $secret);
				
				// Check the HMAC and parse the data
				if($received_hmac == $calculated_hmac) {
					$this->get("data_parsing")->parseFeed($feed, array('data' => $data));
				}
			}

			return new Response("", 202);
		}

		return new Response("", 404);
	}
}
