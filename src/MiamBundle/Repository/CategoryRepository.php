<?php

namespace MiamBundle\Repository;

use MiamBundle\Entity\Category;
use MiamBundle\Entity\User;

class CategoryRepository extends \Doctrine\ORM\EntityRepository
{
	public function findForUser(User $user, $order = null) {
		$qb = $this->createQueryBuilder('c')
			->where('c.user = :user')->setParameter('user', $user);

		if($order == 'leftPosition') {
			$qb->orderBy('c.leftPosition', 'ASC');
		} else {
			$qb->orderBy('c.name', 'ASC');
		}

		return $qb->getQuery()->getResult();
	}

	public function findForUserWithMore(User $user) {
		return $this->createQueryBuilder('c')
			->leftJoin('c.parent', 'p')->addSelect('p')
			->leftJoin('c.subscriptions', 's')->addSelect('s')
			->where('c.user = :user')->setParameter('user', $user)
			->orderBy('c.name', 'ASC')
			->addOrderBy('s.name', 'ASC')
			->getQuery()->getResult();
	}

	public function findOneForUserWithMore($id, User $user) {
		return $this->createQueryBuilder('c')
			->leftJoin('c.parent', 'p')->addSelect('p')
			->leftJoin('c.subscriptions', 's')->addSelect('s')
			->where('c.user = :user')->setParameter('user', $user)
			->andWhere('c.id = :id')->setParameter('id', $id)
			->orderBy('c.name', 'ASC')
			->addOrderBy('s.name', 'ASC')
			->getQuery()->getOneOrNullResult();
	}

	public function findForUserWithMoreOutOf(User $user, Category $category) {
		return $this->createQueryBuilder('c')
			->leftJoin('c.parent', 'p')->addSelect('p')
			->leftJoin('c.subscriptions', 's')->addSelect('s')
			->where('c.user = :user')
			->andWhere('c.leftPosition < :left OR c.rightPosition > :right')
			->orderBy('c.name', 'ASC')
			->addOrderBy('s.name', 'ASC')
			->setParameters(array(
				'user' => $user,
				'left' => $category->getLeftPosition(),
				'right' => $category->getRightPosition()
			))
			->getQuery()->getResult();
	}
}
