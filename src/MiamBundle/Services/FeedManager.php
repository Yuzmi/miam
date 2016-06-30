<?php

namespace MiamBundle\Services;

use MiamBundle\Entity\Feed;
use MiamBundle\Entity\Subscription;
use MiamBundle\Entity\User;

class FeedManager extends MainService {
	protected $em;
	private $container;

	public function __construct($em, $container) {
		$this->em = $em;
		$this->container = $container;
	}

	public function formatUrl($url) {
		$parsed = parse_url($url);

		$url = strtolower($parsed["scheme"])."://";

		if(isset($parsed["user"]) && isset($parsed["pass"])) {
			$url .= $parsed["user"].":".$parsed["pass"]."@";
		}

		$url .= strtolower($parsed["host"]);

		if(isset($parsed["path"])) {
			$url .= $parsed["path"];
		}

		if(isset($parsed["query"])) {
			$url .= "?".$parsed["query"];
		}

		if(isset($parsed["fragment"])) {
			$url .= "#".$parsed["fragment"];
		}

		return $url;
	}

	public function getFeedForUrl($url, $fast = false) {
		$feed = null;

		if(filter_var($url, FILTER_VALIDATE_URL) !== false) {
			$url = $this->formatUrl($url);

			$feed = $this->getRepo("Feed")->findOneByUrl($url);
			if(!$feed) {
				$feed = new Feed();
				$feed->setUrl($url);

				$this->em->persist($feed);
				$this->em->flush();

				if(!$fast) {
					$this->container->get('data_parsing')->parseFeed($feed);
				}
			}
		}

		return $feed;
	}

	public function deleteSubscription(Subscription $subscription) {
		$marks = $this->getRepo("ItemMark")->findStarredForFeedAndUser(
			$subscription->getFeed(), 
			$subscription->getUser()
		);
		
		foreach($marks as $mark) {
			if($mark->getIsStarred()) {
				$mark->setIsStarred(false);
				$this->em->persist($mark);
			}
		}

		$this->em->remove($subscription);
		$this->em->flush();
	}

	public function deleteFeed(Feed $feed) {
		$this->em->remove($feed);
		$this->em->flush();
	}
}