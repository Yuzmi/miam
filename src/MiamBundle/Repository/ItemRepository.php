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
}
