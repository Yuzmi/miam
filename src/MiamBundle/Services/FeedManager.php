<?php

namespace MiamBundle\Services;

use MiamBundle\Entity\Feed;
use MiamBundle\Entity\Subscription;
use MiamBundle\Entity\User;

class FeedManager {
	private $em;
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

			$feed = $this->em->getRepository("MiamBundle:Feed")->findOneByUrl($url);
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

	public function unsubscribeUserFromFeed(User $user, Feed $feed) {
		$subscription = $this->em->getRepository('MiamBundle:Subscription')->findOneBy(array(
			'user' => $user,
			'feed' => $feed
		));
		if($subscription) {
			$this->deleteSubscription($subscription);
		}

		return true;
	}

	public function deleteSubscription(Subscription $subscription) {
		$this->em->remove($subscription);
		$this->em->flush();
	}

	public function deleteFeed(Feed $feed) {
		$this->em->remove($feed);
		$this->em->flush();
	}

	public function updateFeeds() {
    	$oneMonthAgo = new \DateTime("now - 30 days");
    	$oneWeekAgo = new \DateTime("now - 7 days");

    	$feeds = $this->em->getrepository('MiamBundle:Feed')->findAll();
    	foreach($feeds as $feed) {
    		$nbItems = 0;
    		$nbMonthItems = 0;
    		$nbWeekItems = 0;

    		$items = $feed->getItems();
    		foreach($items as $item) {
    			$nbItems++;

    			if($item->getDatePublished() >= $oneMonthAgo) {
    				$nbMonthItems++;
    			}

    			if($item->getDatePublished() >= $oneWeekAgo) {
    				$nbWeekItems++;
    			}
    		}

    		$interval = date_diff($feed->getDateCreated(), new \DateTime("now"), true);
    		$daysSinceCreation = $interval->format("%a");

    		$dailyRate = round(($nbItems - $feed->getNbItems()) / ($daysSinceCreation ?: 1), 5);
    		$feed->setGlobalRate($dailyRate);

    		$monthDailyRate = round($nbMonthItems / 30, 5);
    		$feed->setMonthRate($monthDailyRate);

    		$weekDailyRate = round($nbWeekItems / 7, 5);
    		$feed->setWeekRate($weekDailyRate);

    		$this->em->persist($feed);
    	}

    	$this->em->flush();

    	$subscriptions = $this->em->getRepository('MiamBundle:Subscription')
    		->createQueryBuilder("s")
    		->innerJoin("s.feed", "f")->addSelect("f")
    		->where("s.name IS NULL")
    		->getQuery()->getResult();

    	foreach($subscriptions as $s) {
    		$name = $s->getName();
    		$feedName = $s->getFeed()->getName();
    		if(empty($name) && !empty($feedName)) {
    			$s->setName($feedName);
    			$this->em->persist($s);
    		}
    	}

    	$this->em->flush();
    }
}