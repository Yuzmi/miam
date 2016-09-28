<?php

namespace MiamBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

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

			$feed = $this->getRepo("Feed")->findOneByUrl($topic);
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
			$header_links = explode(',', $request->headers->get("link"));
			if($header_links) {
				foreach($header_links as $hl) {
					if(preg_match('#^<(.*)>;rel=([a-z]+)$#i', $hl, $matches) && $matches[2] == "self") {
						$feed = $this->getRepo("Feed")->findOneByUrl($matches[1]);
						if($feed) {
							$data = file_get_contents("php://input");
							if($data !== false) {
								$this->get("data_parsing")->parseFeed($feed, array('data' => $data));
							}
						}
					}
				}
			}

			return new Response("", 202);
		}

		return new Response("", 404);
	}
}
