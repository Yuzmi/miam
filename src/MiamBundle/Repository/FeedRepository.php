<?php

namespace MiamBundle\Repository;

class FeedRepository extends \Doctrine\ORM\EntityRepository
{
	public function findAll() {
        return $this->createQuerybuilder('f')
            ->orderBy('f.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findSubscribed() {
		return $this->createQueryBuilder("f")
            ->innerJoin("f.subscriptions", "s")
            ->orderBy('f.id', 'ASC')
            ->getQuery()->getResult();
	}

    // May change in the future
	public function findUsed() {
		return $this->findSubscribed();
	}

	public function findUnused() {
		$fs = $this->createQueryBuilder("f")
            ->select("f, COUNT(s.id)")
            ->leftJoin("f.subscriptions", "s")
            ->groupBy("f")
            ->having("COUNT(s.id) = 0")
            ->orderBy('f.id', 'ASC')
            ->getQuery()->getResult();
        
        $feeds = array();
        foreach($fs as $f) {
        	$feeds[] = $f[0];
        }

        return $feeds;
	}

    public function countAll() {
        return $this->createQueryBuilder('f')
            ->select('COUNT(f.id)')
            ->getQuery()->getSingleScalarResult();
    }

    public function countItemsPerFeed() {
        $result = $this->createQueryBuilder('f')
            ->select('f.id, COUNT(i.id) AS countItems')
            ->leftJoin('f.items', 'i')
            ->groupBy('f.id')
            ->getQuery()->getResult();

        $ipf = array();

        foreach($result as $r) {
            $ipf[$r['id']] = $r['countItems'];
        }

        return $ipf;
    }

    public function countSubscriptionsPerFeed() {
        $result = $this->createQueryBuilder('f')
            ->select('f.id, COUNT(s.id) AS countSubs')
            ->leftJoin('f.subscriptions', 's')
            ->groupBy('f.id')
            ->getQuery()->getResult();

        $ipf = array();

        foreach($result as $r) {
            $ipf[$r['id']] = $r['countSubs'];
        }

        return $ipf;
    }
}
