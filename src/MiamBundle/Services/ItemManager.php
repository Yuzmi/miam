<?php

namespace MiamBundle\Services;

class ItemManager extends MainService {
	protected $em;
	private $tokenStorage;
	private $nbItems;
	private $nbMaxItems;

	public function __construct($em, $tokenStorage) {
		$this->em = $em;
		$this->tokenStorage = $tokenStorage;
		$this->nbItems = 40;
		$this->nbMaxItems = 100;
	}

	public function getUser() {
		return $this->tokenStorage->getToken()->getUser();
	}

	public function getItems($options = array()) {
		$catalog = isset($options['catalog']) ? $options['catalog'] : null;
		$category = isset($options['category']) ? $options['category'] : null;
		$createdAfter = isset($options['createdAfter']) ? $options['createdAfter'] : null;
		$feed = isset($options['feed']) ? $options['feed'] : null;
		$marker = isset($options['marker']) ? $options['marker'] : null;
		$nb = isset($options['nb']) ? intval($options['nb']) : $this->nbItems;
		$offset = isset($options['offset']) ? intval($options['offset']) : 0;
		$page = isset($options['page']) ? $options['page'] : 1;
		$subscriber = isset($options['subscriber']) ? $options['subscriber'] : null;
		$subscription = isset($options['subscription']) ? $options['subscription'] : null;
		$type = isset($options['type']) ? $options['type'] : 'all';

		$qb = $this->getRepo('Item')->createQueryBuilder('i')
			->innerJoin('i.feed', 'f')->addSelect('f');

		if($type == 'feed' && $feed) {
			$qb->andWhere('i.feed = :feed')->setParameter('feed', $feed);
		}

		if($subscriber) {
			$qb->innerJoin('f.subscriptions', 's', 'with', 's.user = :subscriber');
			$qb->setParameter('subscriber', $subscriber);

			if($type == 'subscription' && $subscription) {
				$qb->andWhere('s.id = :subscriptionId');
				$qb->setParameter('subscriptionId', $subscription->getId());
			}

			if($type == 'category' && $category) {
				$qb->innerJoin('s.categories', 'c', 'with', 'c.user = :subscriber');
				$qb->andWhere('c.leftPosition >= :catLeft AND c.rightPosition <= :catRight');
				$qb->setParameter('catLeft', $category->getLeftPosition());
				$qb->setParameter('catRight', $category->getRightPosition());
			}
		}

		if($marker) {
			$qb->leftJoin('i.marks', 'im', 'with', 'im.user = :marker');
			$qb->leftJoin('f.marks', 'fm', 'with', 'fm.user = :marker');
			$qb->setParameter('marker', $marker);

			if($type == 'unread') {
				$qb->andWhere('im.isRead IS NULL OR im.isRead = FALSE');
				$qb->andWhere('fm.id IS NULL OR fm.dateRead < i.dateCreated');
			} elseif($type == 'starred') {
				$qb->andWhere('im.isStarred = TRUE');
			}
		}

		if($catalog === true) {
			$qb->andWhere('f.isCatalog = TRUE');
		} elseif($catalog === false) {
			$qb->andWhere('f.isCatalog = FALSE');
		}

		if($createdAfter) {
			$qb->andWhere('i.dateCreated > :createdAfter');
			$qb->setParameter('createdAfter', $createdAfter);
		}

		if($nb > $this->nbMaxItems) {
			$nb = $this->nbMaxItems;
		} elseif($nb <= 0) {
			$nb = $this->nbItems;
		}
		$qb->setMaxResults($nb);

		$offset += $nb * ($page - 1);
		$qb->setFirstResult($offset);
		
		$qb->orderBy('i.datePublished', 'DESC');

		return $qb->getQuery()->getResult();
	}

	public function getDataForItems($items, $options = array()) {
		// Récupération des IDs
		$itemIds = array();
		foreach($items as $i) {
			$itemIds[] = $i->getId();
		}

		// Initialisation
		$data = array();
		foreach($itemIds as $id) {
			$data[$id] = array(
				'enclosures' => array(),
				'feedName' => '',
				'isRead' => false,
				'isStarred' => false,
				'tags' => array()
			);
		}

		$qb = $this->getRepo('Item')->createQueryBuilder('i')
			->leftJoin('i.tags', 't')->addSelect('t')
			->leftJoin('i.enclosures', 'e')->addSelect('e')
			->where('i.id IN (:ids)')->setParameter('ids', $itemIds);

		$subscriber = isset($options['subscriber']) ? $options['subscriber'] : null;
		if($subscriber) {
			$qb->leftJoin('i.feed', 'f')->addSelect('f');
			$qb->leftJoin('f.subscriptions', 's', 'WITH', 's.user = :subscriber')->addSelect('s');
			$qb->setParameter('subscriber', $subscriber);
		}

		$marker = isset($options['marker']) ? $options['marker'] : null;
		if($marker) {
			$qb->leftJoin('i.marks', 'im', 'with', 'im.user = :marker')->addSelect('im');
			$qb->leftJoin('f.marks', 'fm', 'with', 'fm.user = :marker')->addSelect('fm');
			$qb->setParameter('marker', $marker);
		}

		$items = $qb->getQuery()->getResult();

		foreach($items as $i) {
			$data[$i->getId()]['enclosures'] = $i->getEnclosures();
			$data[$i->getId()]['tags'] = $i->getTags();
			
			if($subscriber) {
				foreach($i->getFeed()->getSubscriptions() as $s) {
					$data[$i->getId()]['feedName'] = $s->getName();
				}
			}

			if($marker) {
				foreach($i->getMarks() as $m) {
					if($m->getIsRead()) {
						$data[$i->getId()]['isRead'] = true;
					}

					if($m->getIsStarred()) {
						$data[$i->getId()]['isStarred'] = true;
					}
				}

				if(!$data[$i->getId()]['isRead']) {
					foreach($i->getFeed()->getMarks() as $m) {
						if($m->getDateRead() && $m->getDateRead() >= $i->getDateCreated()) {
							$data[$i->getId()]['isRead'] = true;
						}
					}
				}
			}
		}
		
		return $data;
	}
}
