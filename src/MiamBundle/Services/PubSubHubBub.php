<?php

namespace MiamBundle\Services;

/*
	https://github.com/pubsubhubbub/php-subscriber
	https://pubsubhubbub.github.io/PubSubHubbub/pubsubhubbub-core-0.4.html
*/

class PubSubHubBub extends MainService {
	protected $em;
	private $container;

	public function __construct($em, $container) {
		$this->em = $em;
		$this->container = $container;
	}

	public function subscribe($feed_url, $options = array()) {
		return $this->change_subscription("subscribe", $feed_url, $options);
	}

	public function unsubscribe($feed_url) {
		return $this->change_subscription("unsubscribe", $feed_url);
	}

	private function change_subscription($mode, $feed_url, $options = array()) {
		$hub_url = $this->container->getParameter("pshb_hub");
		if(filter_var($hub_url, FILTER_VALIDATE_URL) === false) {
			throw new \Exception("PSHB: Hub url is invalid");
		}

		$callback_url = $this->container->getParameter("pshb_callback");
		if(filter_var($callback_url, FILTER_VALIDATE_URL) === false) {
			throw new \Exception("PSHB: Callback url is invalid");
		}

		$secret = $this->container->getParameter("pshb_secret");
		if(empty($secret)) {
			throw new \Exception("PSHB: Secret is invalid");
		}

		$post_string = "hub.mode=".$mode;
		$post_string .= "&hub.callback=".urlencode($callback_url);
		$post_string .= "&hub.topic=".urlencode($feed_url);

		if(isset($options['lease-seconds'])) {
			$lease_seconds = intval($options['lease-seconds']);
			if($lease_seconds > 0) {
				$post_string .= "&hub.lease_seconds=".$lease_seconds;
			}
		}

		$post_string .= "&hub.secret=".urlencode($secret);
		
		$options = array(
			CURLOPT_URL => $hub_url,
			//CURLOPT_USERAGENT => "Miam Agregator",
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_POST => true,
			CURLOPT_POSTFIELDS => $post_string
		);

		$ch = curl_init();
		curl_setopt_array($ch, $options);

		$response = curl_exec($ch);
		$info = curl_getinfo($ch);
		
		if(substr($info['http_code'], 0, 1) == "2") {
			return true;
		}

		return false;
	}
}
