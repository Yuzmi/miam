<?php

namespace MiamBundle\Repository;

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
}
