<?php

namespace MiamBundle\Repository;

class FeedRepository extends \Doctrine\ORM\EntityRepository
{
	public function findCatalog() {
		return $this->createQueryBuilder('f')
			->where('f.isCatalog = TRUE')
			->orderBy('f.customName', 'ASC')
			->getQuery()->getResult();
	}

	public function findSubscribed() {
		return $this->createQueryBuilder('f')
			->innerJoin('f.subscriptions', 's')
			->getQuery()->getResult();

		/*
        return $this->createQueryBuilder('f')
            ->select('f, COUNT(s.id)')
            ->leftJoin('f.subscriptions', 's')
            ->groupBy('f')
            ->having('COUNT(s.id) > 0')
            ->getQuery()->getResult();
        */
	}

	public function findUsed() {
		$fs = $this->createQueryBuilder("f")
            ->select("f, COUNT(s.id)")
            ->leftJoin("f.subscriptions", "s")
            ->groupBy("f")
            ->having("f.isCatalog = TRUE OR COUNT(s.id) > 0")
            ->getQuery()->getResult();

        $feeds = array();
        foreach($fs as $f) {
        	$feeds[] = $f[0];
        }

        return $feeds;
	}

	public function findUnused() {
		$fs = $this->createQueryBuilder("f")
            ->select("f, COUNT(s.id)")
            ->leftJoin("f.subscriptions", "s")
            ->groupBy("f")
            ->having("f.isCatalog = FALSE AND COUNT(s.id) = 0")
            ->getQuery()->getResult();
        
        $feeds = array();
        foreach($fs as $f) {
        	$feeds[] = $f[0];
        }

        return $feeds;
	}

	public function findCountItems() {
		return $this->createQueryBuilder('f')
			->addSelect('COUNT(i) as nb_items')
			->leftJoin('f.items', 'i')
			->groupBy('f')
			->getQuery()->getResult();
	}
}
