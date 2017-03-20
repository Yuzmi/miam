<?php

namespace MiamBundle\Repository;

use MiamBundle\Entity\Feed;
use MiamBundle\Entity\User;

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

	public function findLastPublishedForFeed(Feed $feed, $count = 10) {
		return $this->createQueryBuilder("i")
			->innerJoin("i.feed", "f")
			->where("f.id = :id")->setParameter("id", $feed->getId())
			->orderBy("i.datePublished", "DESC")
			->setMaxResults($count)
			->getQuery()->getResult();
	}

	public function findLastCreatedOneForSubscriber(User $user) {
		return $this->createQueryBuilder("i")
			->innerJoin("i.feed", "f")
			->innerJoin("f.subscriptions", "s")
			->where("s.user = :user")
			->setMaxResults(1)
			->orderBy("i.id", "DESC")
			->setParameters(array(
				'user' => $user
			))
			->getQuery()->getOneOrNullResult();
	}

	public function countAll() {
		return $this->createQueryBuilder('i')
			->select('COUNT(i.id)')
			->getQuery()->getSingleScalarResult();
	}

	public function countForFeed(Feed $feed) {
		return $this->createQueryBuilder('i')
			->select('COUNT(i.id)')
			->where('i.feed = :feed')->setParameter('feed', $feed)
			->getQuery()->getSingleScalarResult();
	}
}
