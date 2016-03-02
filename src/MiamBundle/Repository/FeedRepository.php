<?php

namespace MiamBundle\Repository;

class FeedRepository extends \Doctrine\ORM\EntityRepository
{
	public function findCatalog() {
		return $this->createQueryBuilder('f')
			->orderBy('f.name', 'ASC')
			->getQuery()->getResult();
	}

	public function findCountItems() {
		return $this->createQueryBuilder('f')
			->addSelect('COUNT(i) as nb_items')
			->leftJoin('f.items', 'i')
			->groupBy('f')
			->getQuery()->getResult();
	}
}
