<?php

namespace MiamBundle\Repository;

use MiamBundle\Entity\Feed;
use MiamBundle\Entity\Item;

class ItemRepository extends \Doctrine\ORM\EntityRepository
{
	public function findLastOneForFeed(Feed $feed) {
		return $this->createQueryBuilder("i")
			->innerJoin("i.feed", "f")
			->where("f.id = :id")->setParameter("id", $feed->getId())
			->orderBy("i.datePublished", "DESC")
			->setMaxResults(1)
			->getQuery()->getOneOrNullResult();
	}

	public function getItems($options = array()) {
		$qb = $this->createQueryBuilder('i')
			->innerJoin('i.feed', 'f')->addSelect('f');

		$feed = isset($options['feed']) ? $options['feed'] : null;
		if($feed) {
			$qb->andWhere('i.feed = :feed')->setParameter('feed', $feed);
		}

		$subscriber = isset($options['subscriber']) ? $options['subscriber'] : null;
		$category = isset($options['category']) ? $options['category'] : null;
		if($subscriber || $category) {
			$qb->innerJoin('f.subscriptions', 's');

			if($subscriber) {
				$qb->andWhere('s.user = :subscriber');
				$qb->setParameter('subscriber', $subscriber);
			}

			if($category) {
				$qb->innerJoin('s.categories', 'c');

				$qb->andWhere('c.leftPosition >= :catLeft AND c.rightPosition <= :catRight');
				$qb->setParameter('catLeft', $category->getLeftPosition());
				$qb->setParameter('catRight', $category->getRightPosition());

				if($subscriber) {
					$qb->andWhere('c.user = :subscriber');
				}
			}
		}

		$marker = isset($options['marker']) ? $options['marker'] : null;
		if($marker) {
			$qb->leftJoin('i.marks', 'im', 'with', 'im.user = :marker');
			$qb->leftJoin('f.marks', 'fm', 'with', 'fm.user = :marker');
			$qb->setParameter('marker', $marker);

			$type = isset($options['type']) ? $options['type'] : 'all';
			if($type == 'unread') {
				$qb->andWhere('
	            	((im.id IS NULL OR im.isRead = FALSE) AND (fm.id IS NULL OR fm.isRead = FALSE OR fm.dateRead < i.dateCreated))
            		OR (im.isRead = TRUE AND (fm.isRead = FALSE OR fm.dateRead < i.dateCreated) AND im.dateRead < fm.dateRead)
            		OR (im.isRead = FALSE AND (fm.isRead = TRUE AND fm.dateRead >= i.dateCreated) AND im.dateRead > fm.dateRead)
	            ');
			} elseif($type == 'starred') {
				$qb->andWhere('im.isStarred = TRUE');
			}
		}

		$catalog = isset($options['catalog']) ? $options['catalog'] : null;
		if($catalog === true) {
			$qb->andWhere('f.isCatalog = TRUE');
		} elseif($catalog === false) {
			$qb->andWhere('f.isCatalog = FALSE');
		}

		$createdAfter = isset($options['createdAfter']) ? $options['createdAfter'] : null;
		if($createdAfter) {
			$qb->andWhere('i.dateCreated > :createdAfter');
			$qb->setParameter('createdAfter', $createdAfter);
		}

		$nbDefaultItems = 40;
		$nbMaxItems = 100;

		$nb = isset($options['nb']) ? intval($options['nb']) : $nbDefaultItems;
		if($nb > $nbMaxItems) {
			$nb = $nbMaxItems;
		} elseif($nb <= 0) {
			$nb = $nbDefaultItems;
		}
		$qb->setMaxResults($nb);

		$offset = isset($options['offset']) ? intval($options['offset']) : 0;
		$page = isset($options['page']) ? $options['page'] : 1;
		$offset += $nb * ($page - 1);
		$qb->setFirstResult($offset);

		$qb->orderBy('i.datePublished', 'DESC');

		return $qb->getQuery()->getResult();
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
				'feedName' => '',
				'isRead' => false,
				'isStarred' => false,
				'tags' => array()
			);
		}

		$qb = $this->createQueryBuilder('i')
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
			$qb->leftJoin('f.marks', 'fm', 'WITH', 'fm.user = :marker')->addSelect('fm');
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
				$lastDateRead = $i->getDateCreated();

				foreach($i->getMarks() as $m) {
					if($m->getDateRead() && ($lastDateRead < $m->getDateRead())) {
						$data[$i->getId()]['isRead'] = $m->getIsRead();
						$lastDateRead = $m->getDateRead();
					}

					if($m->getIsStarred()) {
						$data[$i->getId()]['isStarred'] = true;
					}
				}

				foreach($i->getFeed()->getMarks() as $m) {
					if($m->getDateRead() && $lastDateRead < $m->getDateRead()) {
						$data[$i->getId()]['isRead'] = $m->getIsRead();
						$lastDateRead = $m->getDateRead();
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
