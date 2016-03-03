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
		return trim($url);
	}

	public function getSubscriptionForUserAndUrl(User $user, $url) {
		$subscription = null;

		$feed = $this->getFeedForUrl($url);
		if($feed) {
			$subscription = $this->getSubscriptionForUserAndFeed($user, $feed);
		}

		return $subscription;
	}

	public function getSubscriptionForUserAndFeed(User $user, Feed $feed) {
		$subscription = $this->em->getRepository('MiamBundle:Subscription')->findOneBy(array(
			'user' => $user,
			'feed' => $feed
		));
		if(!$subscription) {
			$subscription = $this->subscribeUserToFeed($user, $feed);
		}

		return $subscription;
	}

	public function getFeedForUrl($url) {
		$feed = null;

		if(filter_var($url, FILTER_VALIDATE_URL) !== false) {
			$url = $this->formatUrl($url);

			$feed = $this->em->getRepository("MiamBundle:Feed")->findOneByUrl($url);
			if(!$feed) {
				$feed = $this->createFeedForUrl($url);
			}
		}

		return $feed;
	}

	public function subscribeUserToFeed(User $user, Feed $feed) {
		$subscription = new Subscription();
		$subscription->setUser($user);
		$subscription->setFeed($feed);
		$subscription->setName($feed->getName());

		$this->em->persist($subscription);
		$this->em->flush();

		return $subscription;
	}

	public function createFeedForUrl($url) {
		$feed = new Feed();
		$feed->setUrl($url);

		$this->em->persist($feed);
		$this->em->flush();

		$this->container->get('data_parsing')->parseFeed($feed);

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

    		$dailyRate = round(($nbItems - $feed->getNbItemsOnParsing()) / ($daysSinceCreation ?: 1), 5);
    		$feed->setGlobalRate($dailyRate);

    		$monthDailyRate = round($nbMonthItems / 30, 5);
    		$feed->setMonthRate($monthDailyRate);

    		$weekDailyRate = round($nbWeekItems / 7, 5);
    		$feed->setWeekRate($weekDailyRate);

    		$this->em->persist($feed);
    	}

    	$this->em->flush();
    }

    public function updateSubscriptions() {
    	$subscriptions = $this->em->getRepository('MiamBundle:Subscription')
    		->createQueryBuilder("s")
    		->innerJoin("s.feed", "f")->addSelect("f")
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