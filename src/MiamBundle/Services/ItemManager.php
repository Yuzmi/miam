<?php

namespace MiamBundle\Services;

class ItemManager extends MainService {
	protected $em;

	public function __construct($em) {
		$this->em = $em;
	}

	public function getItems($options = array()) {
		$qb = $this->getRepo("Item")->createQueryBuilder('i')
			->innerJoin('i.feed', 'f')->addSelect('f');

		$feed = isset($options['feed']) ? $options['feed'] : null;
		if($feed) {
			$qb->andWhere('i.feed = :feed')->setParameter('feed', $feed);
		}

		$subscription = isset($options['subscription']) ? $options['subscription'] : null;
		$category = isset($options['category']) ? $options['category'] : null;
		$subscriber = isset($options['subscriber']) ? $options['subscriber'] : null;

		if($subscription || $category || $subscriber) {
			$qb->innerJoin('f.subscriptions', 's');
		}

		if($subscription) {
			$qb->andWhere('s.id = :subscriptionId');
			$qb->setParameter('subscriptionId', $subscription->getId());
		}
		
		if($category) {
			$qb->innerJoin('s.category', 'c');

			$qb->andWhere('c.leftPosition >= :catLeft AND c.rightPosition <= :catRight');
			$qb->setParameter('catLeft', $category->getLeftPosition());
			$qb->setParameter('catRight', $category->getRightPosition());

			if($subscriber) {
				$qb->andWhere('c.user = :subscriber');
			}
		}

		if($subscriber) {
			$qb->andWhere('s.user = :subscriber');
			$qb->setParameter('subscriber', $subscriber);
		}

		$marker = isset($options['marker']) ? $options['marker'] : null;
		if($marker) {
			$qb->leftJoin('i.marks', 'im', 'with', 'im.user = :marker');
			$qb->setParameter('marker', $marker);

			$type = isset($options['type']) ? $options['type'] : 'all';
			if($type == 'unread') {
				$qb->andWhere($qb->expr()->notIn(
					'i.id', 
					$this->getRepo("ItemMark")->createQueryBuilder('im2')
						->select('i2.id')
						->innerJoin('im2.item', 'i2')
						->where('im2.user = :marker AND im2.isRead = TRUE')
						->getQuery()->getDQL()
				));
			} elseif($type == 'new') {
				$duration_new_articles = (int) $marker->getSetting('DURATION_NEW_ARTICLES');
				$qb->andWhere('i.dateCreated > :newAfter');
				$qb->setParameter('newAfter', new \DateTime("-".$duration_new_articles." hours"));
			} elseif($type == 'starred') {
				$qb->andWhere('im.isStarred = TRUE');
			} elseif($type == 'last-read') {
				$qb->andWhere('im.isRead = TRUE AND im.dateRead IS NOT NULL');
			}
		}

		$createdAfter = isset($options['createdAfter']) ? $options['createdAfter'] : null;
		if($createdAfter) {
			$qb->andWhere('i.dateCreated > :createdAfter');
			$qb->setParameter('createdAfter', $createdAfter);
		}

		$ids = isset($options['ids']) ? $options['ids'] : array();
		if(count($ids) > 0) {
			$qb->andWhere('i.id IN (:ids)');
			$qb->setParameter('ids', $ids);
		}

		$count = isset($options['count']) ? intval($options['count']) : 0;
		if($count > 0) {
			$qb->setMaxResults($count);
		}

		$offset = isset($options['offset']) ? intval($options['offset']) : 0;
		$page = isset($options['page']) ? $options['page'] : 1;
		$offset += $count * ($page - 1);
		$qb->setFirstResult($offset);

		if($marker && $type == 'last-read') {
			$qb->orderBy('im.dateRead', 'DESC');
		} else {
			$qb->orderBy('i.datePublished', 'DESC');
		}

		return $qb->getQuery()->getResult();
	}

	public function getItem($id, $options = array()) {
		$item = null;

		$options['ids'] = array($id);

		$items = $this->getItems($options);
		if(count($items) > 0) {
			$item = $items[0];
		}

		return $item;
	}

	public function getDataForItems($items, $options = array()) {
		// Get IDs
		$itemIds = array();
		foreach($items as $i) {
			$itemIds[] = $i->getId();
		}

		// Initialize
		$data = array();
		foreach($itemIds as $id) {
			$data[$id] = array(
				'enclosures' => array(),
				'isRead' => false,
				'isStarred' => false,
				'subscriptionId' => null,
				'subscriptionName' => null,
				'tags' => array()
			);
		}

		$qb = $this->getRepo("Item")->createQueryBuilder('i')
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
			$qb->leftJoin('i.marks', 'im', 'WITH', 'im.user = :marker')->addSelect('im');
			$qb->setParameter('marker', $marker);
		}

		$items = $qb->getQuery()->getResult();

		foreach($items as $item) {
			$data[$item->getId()]['enclosures'] = $item->getEnclosures();
			$data[$item->getId()]['tags'] = $item->getTags();
			
			if($subscriber) {
				if($subscription = $item->getFeed()->getSubscriptions()->first()) {
					$data[$item->getId()]['subscriptionId'] = $subscription->getId();
					$data[$item->getId()]['subscriptionName'] = $subscription->getName();
				}
			}

			if($marker) {
				$mark = $item->getMarks()->first();
				if($mark) {
					if($mark->getIsRead()) {
						$data[$item->getId()]['isRead'] = true;
					}

					if($mark->getIsStarred()) {
						$data[$item->getId()]['isStarred'] = true;
					}
				}
			}
		}
		
		return $data;
	}

	public function getDataForItem(Item $item, $options = array()) {
		$dataItems = $this->getDataForItems(array($item), $options);
		return $dataItems[$item->getId()];
	}

}