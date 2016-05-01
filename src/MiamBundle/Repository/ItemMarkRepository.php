<?php

namespace MiamBundle\Repository;

use MiamBundle\Entity\Feed;
use MiamBundle\Entity\User;

class ItemMarkRepository extends \Doctrine\ORM\EntityRepository
{
	public function countStarredForUser(User $user) {
		return $this->createQueryBuilder("m")
			->select("COUNT(m.id)")
			->where("m.user = :user")->setParameter("user", $user)
			->andWhere("m.isStarred = TRUE")
			->getQuery()->getSingleScalarResult();
	}

	public function findStarredForFeedAndUser(Feed $feed, User $user) {
		return $this->createQueryBuilder("m")
			->innerJoin("m.item", "i")
			->where("m.user = :user")
			->andWhere("m.isStarred = TRUE")
			->andWhere("i.feed = :feed")
			->setParameters(array(
				'feed' => $feed,
				'user' => $user
			))
			->getQuery()->getResult();
	}
}
