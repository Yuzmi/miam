<?php

namespace MiamBundle\Services;

use MiamBundle\Entity\Enclosure;
use MiamBundle\Entity\Feed;
use MiamBundle\Entity\Item;
use MiamBundle\Entity\Tag;

class DataParsing {
	private $em;
	private $container;

	public function __construct($em, $container) {
		$this->em = $em;
		$this->container = $container;
		$this->rootDir = $this->container->get('kernel')->getRootDir().'/..';
		$this->fileDir = $this->rootDir.'/files';
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
					$tag_name = strtolower(trim($t->get_label()));
					if($tag_name) {
						if(array_key_exists($tag_name, $cache_tags)) {
							$tag = $cache_tags[$tag_name];
						} else {
							$tag = $this->em->getRepository('MiamBundle:Tag')->findOneByName($tag_name);
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
				$identifier = $i->get_id();
				
				// On ignore les doublons (Quoi ?! T'es pas content ?!)
				if(in_array($identifier, $identifiers)) {
					continue;
				} else {
					$identifiers[] = $identifier;
				}

				$is_new_item = false;
				
				// On vérifie l'existence de l'article
				$item = $this->em->getRepository('MiamBundle:Item')->findOneBy(array(
					'feed' => $feed,
					'identifier' => $identifier
				));
				
				// Création de l'article si c'est un nouveau
				if(!$item) {
					$item = new Item();
					$item->setFeed($feed);
					$item->setIdentifier($identifier);

					$date_published = null;
					try {
                        // Equivalent de la méthode Item::get_date() de SimplePie / Correction d'un problème
                        if($foo = $i->get_item_tags(SIMPLEPIE_NAMESPACE_RSS_20, 'pubDate')) $date_published = date(DATE_ATOM, strtotime($foo[0]['data']));
                        elseif($foo = $i->get_item_tags(SIMPLEPIE_NAMESPACE_ATOM_10, 'published')) $date_published = date(DATE_ATOM, strtotime($foo[0]['data']));
                        elseif($foo = $i->get_item_tags(SIMPLEPIE_NAMESPACE_ATOM_03, 'issued')) $date_published = date(DATE_ATOM, strtotime($foo[0]['data']));
                        elseif($foo = $i->get_item_tags(SIMPLEPIE_NAMESPACE_ATOM_03, 'created')) $date_published = date(DATE_ATOM, strtotime($foo[0]['data']));
                        elseif($foo = $i->get_item_tags(SIMPLEPIE_NAMESPACE_DC_11, 'date')) $date_published = date(DATE_ATOM, strtotime($foo[0]['data']));
                        elseif($foo = $i->get_item_tags(SIMPLEPIE_NAMESPACE_DC_10, 'date')) $date_published = date(DATE_ATOM, strtotime($foo[0]['data']));
                        elseif($foo = $i->get_item_tags(SIMPLEPIE_NAMESPACE_ATOM_10, 'updated')) $date_published = date(DATE_ATOM, strtotime($foo[0]['data']));
                        elseif($foo = $i->get_item_tags(SIMPLEPIE_NAMESPACE_ATOM_03, 'modified')) $date_published = date(DATE_ATOM, strtotime($foo[0]['data']));
                    } catch(\Exception $e) {
                        $date_published = $i->get_date(DATE_ATOM);
                    }

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

                // Hash
                $item_hash = (string) $i; // md5(serialize($this->data))

                if($is_new_item || $item->getHash() != $item_hash) {
	                $item->setHash($item_hash);

	                // Titre
					$item_title = html_entity_decode(trim($i->get_title()), ENT_COMPAT | ENT_HTML5, 'utf-8');
					$item->setTitle($item_title);

					// Contenu HTML
					$htmlContent = (string) $i->get_content();
					$item->setHtmlContent($htmlContent);

					// Contenu texte
					$textContent = html_entity_decode(trim(strip_tags($htmlContent)), ENT_COMPAT | ENT_HTML5, 'utf-8'); // A améliorer
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

					// Tags
					$tags = $i->get_categories() ?: array();
					foreach($tags as $t) {
						$tag_name = trim($t->get_label());
						if($tag_name) {
							if(array_key_exists($tag_name, $cache_tags)) {
								$tag = $cache_tags[$tag_name];

								if($is_new_item || !$item->getTags()->contains($tag)) {
									$item->addTag($tag);
								}
							}
						}
					}
					
					// Pièce(s) jointe(s)
					$enclosures = $i->get_enclosures();
					foreach($enclosures as $e) {
						$enclosure_url = trim($e->get_link());
						if(filter_var($enclosure_url, FILTER_VALIDATE_URL) !== false) {
							$enclosure = null;
							if(!$is_new_item) {
								$enclosure = $this->em->getRepository('MiamBundle:Enclosure')->findOneBy(array(
									'item' => $item,
									'url' => $enclosure_url
								));
							}

							if(!$enclosure) {
								$enclosure = new Enclosure();
								$enclosure->setItem($item);
								$enclosure->setUrl($enclosure_url);
								$enclosure->setType($e->get_type());
								$enclosure->setLength($e->get_length());
								$enclosure->setTitle(trim($e->get_title()));
								$enclosure->setDescription(trim($e->get_description()));

								$this->em->persist($enclosure);
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
	}

	public function parseAll($options = array()) {
		$feeds = $this->em->getRepository('MiamBundle:Feed')->findAll();

		$nb = 0;
		foreach($feeds as $feed) {
			$feed = $this->em->getRepository('MiamBundle:Feed')->find($feed->getId());

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
		$feeds = $this->em->getRepository('MiamBundle:Feed')->findAll();

		$nb = 0;
		foreach($feeds as $feed) {
			$feed = $this->em->getRepository('MiamBundle:Feed')->find($feed->getId());

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
		$path = $this->fileDir.'/'.$filename;
		if(preg_match('#^([0-9]+)\.rss$#', $filename, $match) && file_exists($path)) {
			$feed = $this->em->getRepository('MiamBundle:Feed')->find($match[1]);
			if($feed) {
				$data = file_get_contents($path);
				$this->parseFeed($feed, array_merge($options, array('data' => $data)));
			}

			@unlink($path);
		}
	}

	public function parseFiles($options = array()) {
		$handle = opendir($this->fileDir);
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

		$feeds = $this->em->getRepository('MiamBundle:Feed')->findAll();
		foreach($feeds as $feed) {
			$array[] = array(
				'id' => $feed->getId(),
				'url' => $feed->getUrl()
			);
		}

		$generate = file_put_contents($this->rootDir.'/feeds.json', json_encode($array));

		return $generate !== false ? true : false;
	}

	private function decodeEntities($text, $nb = 1) {
        for($i=0;$i<$nb;$i++) {
            $text = html_entity_decode($text, ENT_COMPAT | ENT_HTML401, 'UTF-8');
            
            // Décodage d'entités illégales mais utilisées malgré tout
            $charmap = array(
                '&#128;' => '€', '&#129;' => '', '&#130;' => '‚', '&#131;' => 'ƒ', 
                '&#132;' => '„', '&#133;' => '…', '&#134;' => '†', '&#135;' => '‡', 
                '&#136;' => 'ˆ', '&#137;' => '‰', '&#138;' => 'Š', '&#139;' => '‹', 
                '&#140;' => 'Œ', '&#141;' => '', '&#142;' => 'Ž', '&#143;' => '', 
                '&#144;' => '', '&#145;' => '‘', '&#146;' => '’', '&#147;' => '“', 
                '&#148;' => '”', '&#149;' => '•', '&#150;' => '–', '&#151;' => '—', 
                '&#152;' => '˜', '&#153;' => '™', '&#154;' => 'š', '&#155;' => '›', 
                '&#156;' => 'œ', '&#157;' => '', '&#158;' => 'ž', '&#159;' => 'Ÿ',

                '&#x80;' => '€', '&#x81;' => '', '&#x82;' => '‚', '&#x83;' => 'ƒ', 
                '&#x84;' => '„', '&#x85;' => '…', '&#x86;' => '†', '&#x87;' => '‡', 
                '&#x88;' => 'ˆ', '&#x89;' => '‰', '&#x8a;' => 'Š', '&#x8b;' => '‹', 
                '&#x8c;' => 'Œ', '&#x8d;' => '', '&#x8e;' => 'Ž', '&#x8f;' => '', 
                '&#x90;' => '', '&#x91;' => '‘', '&#x92;' => '’', '&#x93;' => '“', 
                '&#x94;' => '”', '&#x95;' => '•', '&#x96;' => '–', '&#x97;' => '—', 
                '&#x98;' => '˜', '&#x99;' => '™', '&#x9a;' => 'š', '&#x9b;' => '›', 
                '&#x9c;' => 'œ', '&#x9d;' => '', '&#x9e;' => 'ž', '&#x9f;' => 'Ÿ'
            );
            foreach($charmap AS $a => $b) $text = str_replace($a, $b, $text);
        }

        // Caractères non-décodés parfois restants
        $charmap = array(
            '&#034;' => '"', '&#34;' => '"', 
            '&#039;' => '\'', '&#39;' => '\'', '&#8217;' => '\'', 
            '&#65279;' => ' ', '&nbsp;' => ' ',
            '&amp;' => '&', '&quot;' => '"', '&apos;' => '\''
        );
        foreach($charmap AS $a => $b) $text = str_replace($a, $b, $text);
            
        return $text;
    }
}

/*
public function mustBeRetrieved() {
    $now = new \DateTime("now");

    if(!$this->getDateAttempt()) {
        return true;
    }

    $interval = $now->diff($this->getDateAttempt(), true);
    $hoursSinceLastAttempt = $interval->format("%a") * 24 + $interval->format("%h");
    $minutesSinceLastAttempt = $hoursSinceLastAttempt * 60 + $interval->format("%i");

    if(!$this->getDateRetrieved()) {
        $daysSinceCreation = $now->diff($this->getDateCreated(), true)->format("%a");
        if($daysSinceCreation > 7 && $hoursSinceAttempt < 6) {
            return false;
        }
    }

    if($this->getDateRetrieved()) {
        $daysBetweenAttemptAndRetrieval = $this->getDateAttempt()->diff($this->getDateRetrieved(), true)->format("%a");
        
        if($daysBetweenAttemptAndRetrieval > 7 && $hoursSinceLastAttempt < 6) {
            return false;
        }
    }

    return true;
}
*/