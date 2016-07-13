<?php

namespace MiamBundle\Repository;

use MiamBundle\Entity\Feed;
use MiamBundle\Entity\User;

class ItemMarkRepository extends \Doctrine\ORM\EntityRepository
{
	public function countStarredAndSubscribedForUser(User $user) {
		return $this->createQueryBuilder("m")
			->select("COUNT(m.id)")
			->innerJoin("m.item", "i")
			->innerJoin("i.feed", "f")
			->innerJoin("f.subscriptions", "s")
			->where("m.user = :user")
			->andWhere("m.isStarred = TRUE")
			->andWhere("s.user = :user")
			->setParameter("user", $user)
			->getQuery()->getSingleScalarResult();
	}
}
