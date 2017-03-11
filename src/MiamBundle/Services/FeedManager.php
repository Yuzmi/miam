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

	private function formatUrl($url) {
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

	private function hashUrl($url) {
		return hash('sha1', $url);
	}
	
	public function getFeedForUrl($url, $createIfNotExists = false, $parse = false) {
		$feed = null;

		if(filter_var($url, FILTER_VALIDATE_URL) !== false) {
			$url = $this->formatUrl($url);
			$urlHash = $this->hashUrl($url);

			$feed = $this->getRepo("Feed")->findOneByUrlHash($urlHash);
			if(!$feed && $createIfNotExists) {
				$feed = $this->createFeedForUrl($url, $parse);
			}
		}

		return $feed;
	}

	// Create a feed
	public function createFeedForUrl($url, $parse = false) {
		$url = $this->formatUrl($url);
		$urlHash = $this->hashUrl($url);

		$feed = new Feed();
		$feed->setUrl($url);
		$feed->setUrlHash($urlHash);

		$this->em->persist($feed);
		$this->em->flush();

		if($parse) {
			$this->container->get('data_parsing')->parseFeed($feed);
		}

		return $feed;
	}

	// Find feeds from the argument (for commands)
	public function getFeeds($arg = null) {
        if($arg == 'all' || is_null($arg) || $arg == '') {
            return $this->getRepo("Feed")->findAll();
        } elseif($arg == 'subscribed') {
            return $this->getRepo("Feed")->findSubscribed();
        } elseif($arg == 'used') {
            return $this->getRepo("Feed")->findUsed();
        } elseif($arg == 'unused') {
            return $this->getRepo("Feed")->findUnused();
        } elseif(filter_var($arg, FILTER_VALIDATE_URL) !== false) {
            $feed = $this->getFeedForUrl($arg);
            if($feed) {
            	return array($feed);
            }
        } elseif(intval($arg) > 0) {
            $feed = $this->getRepo("Feed")->find(intval($arg));
            if($feed) {
            	return array($feed);
            }
        }

        return null;
    }

	public function deleteSubscription(Subscription $subscription) {
		$this->em->remove($subscription);
		$this->em->flush();
	}

	public function deleteFeed(Feed $feed) {
		$this->em->remove($feed);
		$this->em->flush();
	}
}