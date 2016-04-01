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
		$this->rssDir = $this->rootDir.'/rss';
	}

	public function parseFeed(Feed $feed, $options = array()) {
		$pie = new \SimplePie();
		$now = new \DateTime("now");

		if(isset($options['data']) && !empty($options['data'])) {
			$pie->set_raw_data($options['data']);
		} else {
			$pie->set_feed_url($feed->getUrl());
			//$pie->force_feed(true);
			$pie->enable_cache(false);
			$pie->set_timeout(10);
			$pie->set_autodiscovery_level(SIMPLEPIE_LOCATOR_NONE);
		}

		$firstParsing = false;
		if(!$feed->getDateParsed()) {
			$firstParsing = true;
		}
		$feed->setDateParsed($now);

		$countNewItems = 0;

		$pie_init = $pie->init();
		if($pie_init) {
			$feed->setDateSuccess($now);
			
			$feed_name = html_entity_decode(trim($pie->get_title()), ENT_COMPAT | ENT_HTML5, 'utf-8');
			if($feed_name) {
				$feed->setName($feed_name);
			}

			$feed_website = trim($pie->get_link());
			if(filter_var($feed_website, FILTER_VALIDATE_URL) !== false) {
				$feed->setWebsite($feed_website);
			}

			$dataLength = strlen($pie->get_raw_data());
			if($dataLength > 0) {
				$feed->setDataLength($dataLength);
			}

			$items = $pie->get_items();

			$countItems = count($items);
			if($countItems > 0) {
				$feed->setNbItems($countItems);
			}

			// Récupération/création des tags
			$cache_tags = array();
			foreach($items as $i) {
				$tags = $i->get_categories() ?: array();
				foreach($tags as $t) {
					$tag_name = trim($t->get_label());
					if($tag_name) {
						if(array_key_exists($tag_name, $cache_tags)) {
							$tag = $cache_tags[$tag_name];
						} else {
							$tag = $this->getRepo('Tag')->findOneByName($tag_name);
							if(!$tag) {
								$tag = new Tag();
								$tag->setName($tag_name);

								$this->em->persist($tag);
							}

							$cache_tags[$tag_name] = $tag;
						}
					}
				}
			}
			$this->em->flush();
			
			$identifiers = array();
			foreach($items as $i) {
				$item_identifier = $i->get_id();
				$item_hash = $i->get_id(true); // md5(serialize($this->data))
				
				// On ignore les doublons (Quoi ?! T'es pas content ?!)
				if(in_array($item_identifier, $identifiers)) {
					continue;
				} else {
					$identifiers[] = $item_identifier;
				}

				$is_new_item = false;
				
				// On vérifie l'existence de l'article
				$item = $this->getRepo('Item')->findOneBy(array(
					'feed' => $feed,
					'identifier' => $item_identifier
				));
				
				// Création de l'article si c'est un nouveau
				if(!$item) {
					$item = new Item();
					$item->setFeed($feed);
					$item->setIdentifier($item_identifier);

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

	                // Titre
					$item_title = html_entity_decode(trim($i->get_title()), ENT_COMPAT | ENT_HTML5, 'utf-8');
					$item->setTitle($item_title);

					// Contenu de base
					$content = (string) $i->get_content();
					
					// Contenu HTML
					$item->setHtmlContent($content);

					// Contenu texte
					$textContent = html_entity_decode(trim(strip_tags($content)), ENT_COMPAT | ENT_HTML5, 'utf-8'); // A améliorer
					$item->setTextContent($textContent);

					// Lien
					$link = trim($i->get_link());
					if(filter_var($link, FILTER_VALIDATE_URL) !== false) {
						$item->setLink($link);
					}

					// Date de mise à jour
					$date_updated = $i->get_updated_date(DATE_ATOM);
	                if($date_updated) {
	                	$date_updated = new \DateTime($date_updated);
	                } else {
	                	$date_updated = $item->getDatePublished();
	                }
	                $item->setDateUpdated($date_updated);

					// Auteur(s)
					$as = $i->get_authors();
					if(count($as) > 0) {
						$authors = array();

						foreach($as as $a) {
							$author_name = trim($a->get_name());
							$author_email = trim($a->get_email());

							if($author_name) {
								$authors[] = $author_name;
							} elseif($author_email) {
								$authors[] = $author_email;
							}
						}

						$authors = implode(', ', $authors);
						$item->setAuthor($authors);
					}

					$all_tags = array();
					$new_tags = array();

					// New tags
					$tags = $i->get_categories() ?: array();
					foreach($tags as $t) {
						$tag_name = trim($t->get_label());
						if(!empty($tag_name)) {
							if(array_key_exists($tag_name, $cache_tags)) {
								$tag = $cache_tags[$tag_name];

								if(($is_new_item || !$item->getTags()->contains($tag)) && !in_array($tag_name, $new_tags)) {
									$item->addTag($tag);

									$new_tags[] = $tag_name;
								}
							}

							$all_tags[] = $tag_name;
						}
					}

					// Remove obsolete tags
					foreach($item->getTags() as $t) {
						if(!in_array($t->getName(), $all_tags)) {
							$item->removeTag($t);
						}
					}
					
					// Pièce(s) jointe(s)
					$enclosures = $i->get_enclosures();
					$enclosure_urls = array();

					foreach($enclosures as $e) {
						$enclosure_url = trim($e->get_link());
						if(filter_var($enclosure_url, FILTER_VALIDATE_URL) !== false) {
							$enclosure = null;
							if(!$is_new_item) {
								$enclosure = $this->getRepo('Enclosure')->findOneBy(array(
									'item' => $item,
									'url' => $enclosure_url
								));
							}

							if(!$enclosure && !in_array($enclosure_url, $enclosure_urls)) {
								$enclosure = new Enclosure();
								$enclosure->setItem($item);
								$enclosure->setUrl($enclosure_url);
								$enclosure->setType($e->get_type());
								$enclosure->setLength($e->get_length());
								$enclosure->setTitle(trim($e->get_title()));
								$enclosure->setDescription(trim($e->get_description()));

								$this->em->persist($enclosure);

								$enclosure_urls[] = $enclosure_url;
							}
						}
					}

					$this->em->persist($item);
				}
			}
			
			$feed->setNbErrors(0);
		} else {
			$feed->setNbErrors($feed->getNbErrors() + 1);
		}

		if($countNewItems > 0) {
			$feed->setDateNewItem($now);
		}

		$this->em->persist($feed);
		
		$this->em->flush();

		if(isset($options['verbose']) && $options['verbose']) {
			if($pie_init) {
				if($countNewItems > 0) {
					echo '+';
				} else {
					echo '-';
				}
			} else {
				echo 'x';
			}
		}

		if($firstParsing) {
			$this->updateFeedIcon($feed);
		}
	}

	public function parseAll($options = array()) {
		$feeds = $this->getRepo('Feed')->findAll();

		$nb = 0;
		foreach($feeds as $feed) {
			$feed = $this->getRepo('Feed')->find($feed->getId());

			if($feed) {
				$this->parseFeed($feed, $options);

				$nb++;
			}

			if($nb%20 == 0) {
				$this->em->clear();
			}
		}
	}

	public function parseSelected($options = array()) {
		$feeds = $this->getRepo('Feed')->findAll();

		$nb = 0;
		foreach($feeds as $feed) {
			$feed = $this->getRepo('Feed')->find($feed->getId());

			if($feed) {
				$now = new \DateTime("now");
				$oneHourAgo = new \DateTime("now - 1 hour");
				$oneDayAgo = new \DateTime("now - 1 day");
				$oneWeekAgo = new \DateTime("now - 1 week");
				$oneMonthAgo = new \DateTime("now - 1 month");

				$date = $feed->getDateNewItem() ?: $feed->getDateCreated();
				if(
					$date > $oneWeekAgo
					|| ($date > $oneMonthAgo && $feed->getDateParsed() < $oneHourAgo)
					|| ($date < $oneMonthAgo && $feed->getDateParsed() < $oneDayAgo)
				) {
					$this->parseFeed($feed, $options);

					$nb++;
				}
			}

			if($nb%20 == 0) {
				$this->em->clear();
			}
		}
	}

	public function parseFile($filename, $options = array()) {
		$path = $this->rssDir.'/'.$filename;
		if(preg_match('#^([0-9]+)\.rss$#', $filename, $match) && file_exists($path)) {
			$feed = $this->getRepo('Feed')->find($match[1]);
			if($feed) {
				$data = file_get_contents($path);
				$this->parseFeed($feed, array_merge($options, array('data' => $data)));
			}

			@unlink($path);
		}
	}

	public function parseFiles($options = array()) {
		$handle = opendir($this->rssDir);
		if($handle) {
			$nb = 0;
			while(($entry = readdir($handle)) !== false) {
				$this->parseFile($entry, $options);

				$nb++;

				if($nb%20 == 0) {
					$this->em->clear();
				}
			}
		}
	}

	public function generateJson() {
		$array = array();

		$feeds = $this->getRepo('Feed')->findAll();
		foreach($feeds as $feed) {
			$array[] = array(
				'id' => $feed->getId(),
				'url' => $feed->getUrl()
			);
		}

		$generate = file_put_contents($this->rootDir.'/feeds.json', json_encode($array));

		return $generate !== false ? true : false;
	}

    public function updateFeedIcons() {
    	$feeds = $this->getRepo('Feed')->findAll();
    	foreach($feeds as $feed) {
	    	$icon = $this->updateFeedIcon($feed);
    	}
    }

    public function updateFeedIcon(Feed $feed) {
    	$success = false;

    	// Dossier des images du flux
    	$feedDir = $this->rootDir.'/web/images/feeds/'.$feed->getId();
    	if(!is_dir($feedDir)) {
    		mkdir($feedDir, 0777, true);
    	}

    	$tmpPath = $feedDir.'/icon.tmp';
    	$iconPath = $feedDir.'/icon.png';

    	// Récupération de l'URL de l'icône
    	$favicon = $this->getUrlForFeedIcon($feed);
    	if($favicon) {
    		try {
	    		$content = file_get_contents($favicon);
	    		if($content !== false) {
	    			file_put_contents($tmpPath, $content);
	    		}
	    	} catch(\Exception $e) {}
    	}

    	$iconSize = 16;

    	// Conversion et stockage
    	if(is_file($tmpPath)) {
    		$iconData = getimagesize($tmpPath);
    		
    		$iconSrcWidth = $iconData[0];
    		$iconSrcHeight = $iconData[1];
    		$iconSrcType = $iconData[2];
    		
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
    		} elseif($iconSrcType == IMAGETYPE_BMP) {
    			$icon = new \Imagick($tmpPath);
				$icon->thumbnailImage($iconSize, $iconSize);
				$icon->setImageFormat('png');
				$icon->writeImage($iconPath);
				$icon->clear();
				$icon->destroy();

    			$success = true;
    		} elseif($iconSrcType == IMAGETYPE_ICO) {
    			// Must edit the extension here or it fails
				$icoPath = $feedDir.'/icon.ico';
				rename($tmpPath, $icoPath);

				$icon = new \Imagick($icoPath);
				$icon->thumbnailImage($iconSize, $iconSize);
				$icon->setImageFormat('png');
				$icon->writeImage($iconPath);
				$icon->clear();
				$icon->destroy();

    			@unlink($icoPath);

    			$success = true;
    		} else {
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

    		@unlink($tmpPath);
    	}

    	// Mise à jour du flux
    	if($success && !$feed->getHasIcon()) {
    		$feed->setHasIcon(true);

    		$this->em->persist($feed);
    		$this->em->flush();
    	}
    }

    // May need improvements
    private function getUrlForFeedIcon(Feed $feed) {
    	$favicon = null;
    	
    	$url = $feed->getWebsite();
    	if(empty($url)) {
    		$item = $this->getRepo('Item')->findLastOneForFeed($feed);
    		if($item) {
    			$url = $item->getLink();
    		}
    	}

    	$rootUrl = null;
    	if(filter_var($url, FILTER_VALIDATE_URL) !== false) {
    		$parsed = parse_url($url);

    		$rootUrl = $parsed["scheme"]."://";

    		if(isset($parsed["user"]) && isset($parsed["pass"])) {
    			$rootUrl .= $parsed["user"].":".$parsed["pass"]."@";
    		}

    		$rootUrl .= $parsed["host"];

    		if(isset($parsed["port"])) {
    			$rootUrl .= ":".$parsed["port"];
    		}
    	}

    	if($rootUrl) {
    		// https://stackoverflow.com/questions/5701593
    		$doc = new \DOMDocument();
    		$doc->strictErrorChecking = false;

    		// Récupération du chemin de l'icône
    		try {
	    		$html = file_get_contents($rootUrl);
	    		if($html && $doc->loadHTML($html) !== false) {
		    		$xml = simplexml_import_dom($doc);
		    		if($xml instanceof \SimpleXmlElement) {
		    			$rels = array("shortcut icon", "SHORTCUT ICON", "icon", "ICON");
		    			foreach($rels as $rel) {
		    				$arr = $xml->xpath('//link[@rel="'.$rel.'"]');
		    				if(isset($arr[0]['href'])) {
					    		$favicon = $arr[0]['href'];
					    		break;
					    	}
		    			}
			    	}
			    }
			} catch(\Exception $e) {}

		    // Sinon on tente celui par défaut
		    if(!$favicon) {
		    	$favicon = "/favicon.ico";
		    }
		    
		    // Correction en cas d'erreurs
		    if(!filter_var($favicon, FILTER_VALIDATE_URL) !== false) {
		    	$parsedFav = parse_url($favicon);

		    	if(!isset($parsedFav["host"])) {
			    	$favicon = $rootUrl;
			    } elseif(!isset($parsedFav["scheme"])) {
			    	$favicon = $parsedFav["host"];

			    	if(isset($parsedFav["port"])) {
		    			$favicon .= ":".$parsedFav["port"];
		    		}

		    		if(isset($parsedFav["user"]) && isset($parsedFav["pass"])) {
		    			$favicon = $parsedFav["user"].":".$parsedFav["pass"]."@".$favicon;
		    		}

		    		$favicon = "http://".$favicon;
			    }

		    	$path = $parsedFav['path'] ?: "/favicon.ico";
		    	if(substr($path, 0, 1) != "/") {
		    		$path = "/".$path;
		    	}
		    	$favicon .= $path;

		    	if(isset($parsedFav["query"])) {
		    		$favicon .= "?".$parsedFav["query"];
		    	}
		    }
    	}
    	
    	return $favicon;
    }
}
