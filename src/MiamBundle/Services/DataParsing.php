<?php

namespace MiamBundle\Services;

use MiamBundle\Entity\Enclosure;
use MiamBundle\Entity\Feed;
use MiamBundle\Entity\Item;
use MiamBundle\Entity\Tag;

class DataParsing extends MainService {
	protected $em;
	private $container;

	public function __construct($em, $container) {
		$this->em = $em;
		$this->container = $container;
		$this->rootDir = $this->container->get('kernel')->getRootDir().'/..';
	}

	public function parseFeed(Feed $feed, $options = array()) {
		$pie = new \SimplePie();
		$now = new \DateTime("now");

		if(isset($options['data']) && !empty($options['data'])) {
			$pie->set_raw_data($options['data']);
			$pie->force_feed(true);
		} else {
			$pie->set_feed_url($feed->getUrl());
			$pie->set_autodiscovery_level(SIMPLEPIE_LOCATOR_NONE);

			$timeout = 10;
			if(isset($options['timeout'])) {
				$t = intval($options['timeout']);
				if($t > 0) {
					$timeout = $t;
				}
			}
			$pie->set_timeout($timeout);

			if(isset($options['cache']) && !$options['cache']) {
				$pie->enable_cache(false);
			} else {
				$pie->enable_cache(true);
				$pie->set_cache_duration(300);
				$pie->set_cache_location($this->rootDir.'/var/cache/simplepie');
			}
		}

		$firstParsing = false;
		if(!$feed->getDateParsed()) {
			$firstParsing = true;
		}
		$feed->setDateParsed($now);

		$countNewItems = 0;
		$error = null;

		$pie_init = $pie->init();
		if($pie_init) {
			$feed->setDateSuccess($now);
			
			// Name
			$feed_name = $this->sanitizeText($pie->get_title(), 255);
			if($feed_name) {
				$feed->setOriginalName($feed_name);
			}

			$feed_description = $this->sanitizeText($pie->get_description());
			if($feed_description) {
				$feed->setOriginalDescription($feed_description);
			}

			// Website
			$feed_website = $this->sanitizeUrl($pie->get_link());
			if(filter_var($feed_website, FILTER_VALIDATE_URL) !== false) {
				$feed->setWebsite($feed_website);
			}

			// Identifier (may be used as website in some cases)
			if(!$feed->getWebsite()) {
				$feed_identifier = null;

				if($fi = $pie->get_channel_tags(SIMPLEPIE_NAMESPACE_ATOM_10, 'id')) {
					$feed_identifier = $fi[0]["data"];
				} elseif($fi = $pie->get_channel_tags(SIMPLEPIE_NAMESPACE_ATOM_03, 'id')) {
					$feed_identifier = $fi[0]["data"];
				}

				if(filter_var($feed_identifier, FILTER_VALIDATE_URL) !== false) {
					$feed->setWebsite($feed_identifier);
				}
			}

			// Icon Url
			$feed_icon = $this->sanitizeUrl($pie->get_image_url());
			if(filter_var($feed_icon, FILTER_VALIDATE_URL) !== false) {
				$feed->setIconUrl($feed_icon);
			}

			// Author(s)
			$fas = $pie->get_authors();
			if(count($fas) > 0) {
				$feed_authors = array();

				foreach($fas as $fa) {
					$feed_author_name = $this->sanitizeText($fa->get_name());
					$feed_author_email = $this->sanitizeText($fa->get_email());

					if($feed_author_name) {
						$feed_authors[] = $feed_author_name;
					} elseif($feed_author_email) {
						$feed_authors[] = $feed_author_email;
					}
				}

				$feed_authors = implode(', ', $feed_authors);
				$feed->setAuthor($feed_authors);
			}

			// Language
			$feed_language = $this->sanitizeText($pie->get_language(), 255);
			if($feed_language) {
				$feed->setLanguage($feed_language);
			}

			// Data length
			$dataLength = mb_strlen($pie->get_raw_data());
			if($dataLength > 0) {
				$feed->setDataLength($dataLength);
			}

			$items = $pie->get_items();

			$countItems = count($items);
			if($countItems > 0) {
				$feed->setNbItems($countItems);
			}

			// Find and create new tags
			$cache_tags = array();
			foreach($items as $i) {
				$tags = $i->get_categories() ?: array();
				foreach($tags as $t) {
					$tag_name = $this->sanitizeText($t->get_label(), 255);
					if($tag_name) {
						$tag_hash = hash('sha1', $tag_name);
						if(!array_key_exists($tag_hash, $cache_tags)) {
							$tag = $this->getRepo('Tag')->findOneByHash($tag_hash);
							if(!$tag) {
								$tag = new Tag();
								$tag->setName($tag_name);
								$tag->setHash($tag_hash);

								$this->em->persist($tag);
							}

							$cache_tags[$tag_hash] = $tag;
						}
					}
				}
			}
			$this->em->flush();
			
			$identifiers = array();
			foreach($items as $i) {
				$item_identifier = hash('sha1', $i->get_id());
				$item_hash = $i->get_id(true); // md5(serialize($this->data))
				
				// Ignore duplicates (Not happy? Deal with it!)
				if(in_array($item_identifier, $identifiers)) {
					continue;
				} else {
					$identifiers[] = $item_identifier;
				}

				$is_new_item = false;
				
				// Get item if exists
				$item = $this->getRepo("Item")->createQueryBuilder('i')
					->leftJoin('i.enclosures', 'e')->addSelect('e')
					->leftJoin('i.tags', 't')->addSelect('t')
					->where('i.feed = :feed')
					->andWhere('i.identifier = :identifier')
					->setParameters(array(
						'feed' => $feed,
						'identifier' => $item_identifier
					))
					->getQuery()->getOneOrNullResult();
				
				// Creation if new item
				if(!$item) {
					$item = new Item();
					$item->setFeed($feed);
					$item->setIdentifier($item_identifier);

					// Published date
					$date_published = $i->get_date(DATE_ATOM);
                    if($date_published) {
                    	$date_published = new \DateTime($date_published);
                    }

                    if(!$date_published || $date_published > $now) {
                    	$date_published = $now;
                    }
                    $item->setDatePublished($date_published);

                    $countNewItems++;
                    $is_new_item = true;
				}

                if($is_new_item || $item->getHash() != $item_hash) {
	                $item->setHash($item_hash);

	                // Title
					$item_title = $this->sanitizeText($i->get_title(), 255);
					if($item_title) {
						$item->setTitle($item_title);
					}
					
					// HTML content
					$htmlContent = (string) $i->get_content();
					if(extension_loaded('tidy')) {
						// To avoid unclosed tags
						$htmlContent = tidy_repair_string($htmlContent, array(
			                "output-html" => true,
			                "show-body-only" => true
			            ), "utf8");
					}
					$item->setHtmlContent($htmlContent);

					// Text content
					$textContent = $this->sanitizeText(strip_tags($htmlContent));
					$item->setTextContent($textContent);

					// Link
					$link = $this->sanitizeUrl($i->get_link());
					if(filter_var($link, FILTER_VALIDATE_URL) !== false) {
						$item->setLink($link);
					}

					// Updated date
					$date_updated = $i->get_updated_date(DATE_ATOM);
	                if($date_updated) {
	                	$date_updated = new \DateTime($date_updated);
	                } else {
	                	$date_updated = $item->getDatePublished();
	                }
	                $item->setDateUpdated($date_updated);

	                // Modified date
	        		$item->setDateModified($now);

					// Author(s)
					$as = $i->get_authors();
					if(count($as) > 0) {
						$authors = array();

						foreach($as as $a) {
							$author_name = $this->sanitizeText($a->get_name());
							$author_email = $this->sanitizeText($a->get_email());

							if($author_name) {
								$authors[] = $author_name;
							} elseif($author_email) {
								$authors[] = $author_email;
							}
						}

						$authors = implode(', ', $authors);
						$item->setAuthor($authors);
					}

					// Contributor(s)
					$cs = $i->get_contributors();
					if(count($cs) > 0) {
						$contributors = array();

						foreach($cs as $c) {
							$contributor_name = $this->sanitizeText($c->get_name());
							$contributor_email = $this->sanitizeText($c->get_email());

							if($contributor_name) {
								$contributors[] = $contributor_name;
							} elseif($contributor_email) {
								$contributors[] = $contributor_email;
							}
						}

						$contributors = implode(', ', $contributors);
						$item->setContributor($contributors);
					}

					$all_tags = array();
					$new_tags = array();

					// New tags
					$tags = $i->get_categories() ?: array();
					foreach($tags as $t) {
						$tag_name = $this->sanitizeText($t->get_label(), 255);
						if($tag_name) {
							$tag_hash = hash('sha1', $tag_name);
							if(array_key_exists($tag_hash, $cache_tags)) {
								$tag = $cache_tags[$tag_hash];

								if(($is_new_item || !$item->getTags()->contains($tag)) && !in_array($tag_hash, $new_tags)) {
									$item->addTag($tag);

									$new_tags[] = $tag_hash;
								}
							}

							$all_tags[] = $tag_hash;
						}
					}

					// Remove obsolete tags
					foreach($item->getTags() as $t) {
						if(!in_array($t->getHash(), $all_tags)) {
							$item->removeTag($t);
						}
					}
					
					// Enclosure(s)
					$enclosures = $i->get_enclosures();
					$enclosure_hashes = array();

					foreach($enclosures as $e) {
						$enclosure_url = $this->sanitizeUrl($e->get_link());
						if(filter_var($enclosure_url, FILTER_VALIDATE_URL) !== false) {
							$enclosure = null;

							$enclosure_hash = hash('sha1', $enclosure_url);
							if(!$is_new_item) {
								$enclosure = $this->getRepo('Enclosure')->findOneBy(array(
									'item' => $item,
									'hash' => $enclosure_hash
								));
							}

							if(!$enclosure && !in_array($enclosure_hash, $enclosure_hashes)) {
								$enclosure = new Enclosure();
								$enclosure->setItem($item);
								$enclosure->setUrl($enclosure_url);
								$enclosure->setHash($enclosure_hash);

								$enclosure_type = $this->sanitizeText($e->get_type(), 255);
								$enclosure->setType($enclosure_type);

								$enclosure_length = intval($e->get_length());
								$enclosure->setLength($enclosure_length);

								$enclosure_title = $this->sanitizeText($e->get_title(), 255);
								$enclosure->setTitle($enclosure_title);

								$enclosure_description = $this->sanitizeText($e->get_description());
								$enclosure->setDescription($enclosure_description);

								$this->em->persist($enclosure);

								$enclosure_hashes[] = $enclosure_hash;
							}
						}
					}
				}

				$item->setDateLastSeen($now);

				$this->em->persist($item);
			}
			
			$feed->setErrorCount(0);
		} else {
			$error = $pie->error();

			$feed->setErrorCount($feed->getErrorCount() + 1);
			$feed->setErrorMessage($error);
		}

		if($countNewItems > 0) {
			$feed->setDateNewItem($now);
		}

		$this->em->persist($feed);
		
		$this->em->flush();

		// Get icon every 7 days
		if(!$feed->getDateIcon() || $feed->getDateIcon() < new \DateTime("now - 7 days")) {
			$this->parseIcon($feed);
		}

		return array(
			'success' => $pie_init,
			'countNewItems' => $countNewItems,
			'error' => $error
		);
	}

	private function sanitizeText($text, $maxLength = 0) {
		$text = html_entity_decode(trim($text), ENT_COMPAT | ENT_HTML5, 'utf-8');

		if($maxLength > 0) {
			$text = mb_substr($text, 0, $maxLength);
		}

		return $text;
	}

	private function sanitizeUrl($url) {
		return trim($url);
	}

    public function parseIcon(Feed $feed) {
    	$success = false;

    	$tmpPath = $this->rootDir.'/web/images/feeds/icon-'.$feed->getId().'.tmp';

    	// Get the icon
    	$iconUrl = $feed->getIconUrl();
    	if($iconUrl) {
    		if($this->getUrlContentTo($iconUrl, $tmpPath)) {
	    		$iconData = getimagesize($tmpPath);

				// Won't use non-square icons (2px tolerance)
				if($iconData && abs($iconData[0] - $iconData[1]) <= 2) {
					if($this->saveIcon($feed, $tmpPath)) {
						$success = true;
					}
				}
			}
    	}
    	
    	// Get the favicon if no icon
    	if(!$success) {
    		$success = $this->getFaviconAsIcon($feed);
    	}

    	// Feed update
    	if($success) {
    		$feed->setHasIcon(true);
    	}
    	$feed->setDateIcon(new \DateTime("now"));

    	$this->em->persist($feed);
    	$this->em->flush();

    	return array(
    		'success' => $success
    	);
    }

    private function getRootUrlFromUrl($url) {
    	$rootUrl = null;

    	if(filter_var($url, FILTER_VALIDATE_URL) !== false) {
    		$parsed = parse_url($url);

    		if($parsed !== false) {
	    		$rootUrl = $parsed["scheme"]."://";

	    		if(isset($parsed["user"]) && isset($parsed["pass"])) {
	    			$rootUrl .= $parsed["user"].":".$parsed["pass"]."@";
	    		}

	    		$rootUrl .= $parsed["host"];

	    		if(isset($parsed["port"])) {
	    			$rootUrl .= ":".$parsed["port"];
	    		}
	    	}
    	}

    	return $rootUrl;
    }

    private function getUrlContentTo($url, $dst) {
    	$content = $this->getUrlContent($url);

	    if(!is_null($content) && $content !== false && $content !== "") {
			if(file_put_contents($dst, $content) !== false) {
				return true;
			}
		}

	    return false;
    }

    private function getUrlContent($url) {
    	$content = null;

    	if(extension_loaded('curl')) {
	    	$ch = curl_init();

	    	curl_setopt_array($ch, array(
	    		CURLOPT_URL => $url,
	    		CURLOPT_RETURNTRANSFER => 1,
	    		CURLOPT_CONNECTTIMEOUT => 5,
	    		CURLOPT_TIMEOUT => 10,
	    		CURLOPT_FOLLOWLOCATION => true
	    	));

	    	$content = curl_exec($ch);

	    	curl_close($ch);
	    } else {
	    	try {
	    		$content = file_get_contents($url);
	    	} catch(\Exception $e) {
	    		$content = null;
	    	}
	    }

	    return $content;
    }

    private function getFaviconAsIcon(Feed $feed) {
    	$success = false;

    	$tmpPath = $this->rootDir.'/web/images/feeds/icon-'.$feed->getId().'.tmp';

    	$urls = array();

    	if($feed->getWebsite()) {
    		$urls[] = $feed->getWebsite();

    		$rootUrl = $this->getRootUrlFromUrl($feed->getWebsite());
    		if($rootUrl) {
    			$urls[] = $rootUrl;
    		}
    	}

    	$item = $this->getRepo('Item')->findLastOneForFeed($feed);
    	if($item && $item->getLink()) {
    		$urls[] = $item->getLink();

    		$rootUrl = $this->getRootUrlFromUrl($item->getLink());
    		if($rootUrl) {
    			$urls[] = $rootUrl;
    		}
    	}

    	$checkedFaviconUrls = array();

    	$urls = array_unique($urls);
    	foreach($urls as $url) {
    		$faviconUrls = $this->getFaviconUrlsFromUrl($url);
    		if(count($faviconUrls) > 0) {
				foreach($faviconUrls as $faviconUrl) {
					if(!in_array($faviconUrl, $checkedFaviconUrls)) {
						if(
							$this->getUrlContentTo($faviconUrl, $tmpPath) 
							&& $this->saveIcon($feed, $tmpPath)
						) {
							$success = true;
							break 2;
						}

						$checkedFaviconUrls[] = $faviconUrl;
					}
				}
			}
    	}
    	
    	return $success;
    }

    private function getFaviconUrlsFromUrl($url) {
    	$faviconUrls = array();

    	$favicons = array();
    	
    	// https://stackoverflow.com/questions/5701593
    	libxml_use_internal_errors(true);
		try {
			$doc = new \DOMDocument();
    		$doc->strictErrorChecking = false;

    		$html = $this->getUrlContent($url);
    		if($html && $doc->loadHTML($html) !== false) {
	    		$xml = simplexml_import_dom($doc);
	    		if($xml instanceof \SimpleXmlElement) {
	    			$rels = array("shortcut icon", "icon", "SHORTCUT ICON", "ICON");
	    			foreach($rels as $rel) {
	    				$arr = $xml->xpath('//link[@rel="'.$rel.'"]');
	    				if(isset($arr[0]['href'])) {
				    		$favicon = $arr[0]['href'];
				    		if(!in_array($favicon, $favicons)) {
				    			$favicons[] = $favicon;
				    		}
				    	}
	    			}
		    	}
		    }
		} catch(\Exception $e) {}
		libxml_clear_errors();

		if(!in_array('favicon.ico', $favicons)) {
			$favicons[] = 'favicon.ico';
		}
		
		$parsedUrl = parse_url($url);
		
	    foreach($favicons as $favicon) {
		    if(filter_var($favicon, FILTER_VALIDATE_URL) !== false) {
		    	$faviconUrls[] = $favicon;
		    } else { // Fix if relative url
		    	$parsedFav = parse_url($favicon);
		    	
		    	if($parsedFav !== false) {
		    		$newFavicon = '';

		    		if(isset($parsedFav["scheme"])) {
		    			$newFavicon .= $parsedUrl["scheme"]."://";
		    		} else {
		    			$newFavicon .= "http://";
		    		}

		    		if(isset($parsedUrl["user"]) && isset($parsedUrl["pass"])) {
			    		$newFavicon .= $parsedUrl["user"].":".$parsedUrl["pass"]."@";
			    	}

		    		$newFavicon .= $parsedUrl["host"];

		    		if(isset($parsedUrl["port"])) {
		    			$newFavicon .= ":".$parsedUrl["port"];
		    		}

		    		if(isset($parsedUrl["path"]) && mb_substr($favicon, 0, 1) != '/') {
		    			if(mb_substr($parsedUrl["path"], 0, 1) != '/') {
		    				$newFavicon .= "/";
		    			}
		    			$newFavicon .= $parsedUrl["path"];
		    		}

		    		if(isset($parsedFav["path"])) {
				    	if(mb_substr($newFavicon, -1) != "/" && mb_substr($parsedFav['path'], 0, 1) != "/") {
				    		$newFavicon .= "/";
				    	}
				    	$newFavicon .= $parsedFav['path'];
				    }

			    	if(isset($parsedFav["query"])) {
			    		$newFavicon .= "?".$parsedFav["query"];
			    	}
			    	
			    	if(filter_var($newFavicon, FILTER_VALIDATE_URL) !== false) {
			    		$faviconUrls[] = $newFavicon;
			    	}
			    }
		    }
		}
	    
	    return array_unique($faviconUrls);
    }

    private function saveIcon(Feed $feed, $tmpPath) {
    	$iconSize = 16;
    	$success = false;

    	try {
			$iconData = getimagesize($tmpPath);
		} catch(\Exception $e) {
			$iconData = false;
		}

		if($iconData) {
			$iconSrcWidth = $iconData[0];
    		$iconSrcHeight = $iconData[1];
    		$iconSrcType = $iconData[2];

    		$iconPath = $this->rootDir.'/web/'.$feed->getIconRelativePath();

    		if(extension_loaded('imagick')) {
    			// Must edit the extension for ICO icons or it fails
    			if($iconSrcType == IMAGETYPE_ICO) {
					$icoPath = $this->rootDir.'/web/images/feeds/icon-'.$feed->getId().'.ico';
					rename($tmpPath, $icoPath);
					$tmpPath = $icoPath;
				}

				try {
    				$icon = new \Imagick($tmpPath);
					$icon->thumbnailImage($iconSize, $iconSize);
					$icon->setImageFormat('png');
					$icon->writeImage($iconPath);
					$icon->clear();
					$icon->destroy();

	    			$success = true;
    			} catch(\Exception $e) {
    				$success = false;
    			}
    		}

    		if(!$success && extension_loaded('gd')) {
    			if($iconSrcType == IMAGETYPE_GIF) {
	    			$iconDst = imagecreatetruecolor($iconSize, $iconSize);
					
					$blackBg = imagecolorallocate($iconDst, 0, 0, 0);
	    			imagecolortransparent($iconDst, $blackBg);

					$iconSrc = imagecreatefromgif($tmpPath);
					imagecopyresampled($iconDst, $iconSrc, 0, 0, 0, 0, $iconSize, $iconSize, $iconSrcWidth, $iconSrcHeight);
					
					imagepng($iconDst, $iconPath);
	            	imagedestroy($iconDst);

	            	$success = true;
	    		} elseif($iconSrcType == IMAGETYPE_JPEG) {
	    			$iconDst = imagecreatetruecolor($iconSize, $iconSize);

					$iconSrc = imagecreatefromjpeg($tmpPath);
					imagecopyresampled($iconDst, $iconSrc, 0, 0, 0, 0, $iconSize, $iconSize, $iconSrcWidth, $iconSrcHeight);
					
					imagepng($iconDst, $iconPath);
	            	imagedestroy($iconDst);

	            	$success = true;
	    		} elseif($iconSrcType == IMAGETYPE_PNG) {
	    			$iconDst = imagecreatetruecolor($iconSize, $iconSize);

	    			$blackBg = imagecolorallocate($iconDst, 0, 0, 0);
	    			imagecolortransparent($iconDst, $blackBg);
	            	imagealphablending($iconDst, false);
	            	imagesavealpha($iconDst, true);

	            	$iconSrc = imagecreatefrompng($tmpPath);
	            	imagecopyresampled($iconDst, $iconSrc, 0, 0, 0, 0, $iconSize, $iconSize, $iconSrcWidth, $iconSrcHeight);

	            	imagepng($iconDst, $iconPath);
	            	imagedestroy($iconDst);

	            	$success = true;
	    		}
    		}
		}

		@unlink($tmpPath);

		return $success;
    }
}
