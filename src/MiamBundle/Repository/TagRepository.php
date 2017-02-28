<?php

namespace MiamBundle\Repository;

class TagRepository extends \Doctrine\ORM\EntityRepository
{
	public function countAll() {
		return $this->createQueryBuilder('t')
			->select('COUNT(t)')
			->getQuery()->getSingleScalarResult();
	}
}
