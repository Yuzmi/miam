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

    	return $this->redirectToRoute('login');
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
