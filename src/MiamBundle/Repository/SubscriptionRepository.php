<?php

namespace MiamBundle\Repository;

use MiamBundle\Entity\Category;
use MiamBundle\Entity\User;

class SubscriptionRepository extends \Doctrine\ORM\EntityRepository
{
	public function findFeedIdsForUser(User $user) {
		$subscriptions = $this->createQueryBuilder('s')
			->innerJoin('s.feed', 'f')->addSelect('f')
			->where('s.user = :user')->setParameter('user', $user)
			->getQuery()->getResult();

		$ids = array();
		foreach($subscriptions as $s) {
			$ids[] = $s->getFeed()->getId();
		}

		return $ids;
	}

	public function findForCategory(Category $category, $recursive = false) {
		if($recursive) {
			return $this->createQueryBuilder('s')
				->innerJoin('s.categories', 'c')
				->where('c.leftPosition >= :leftPosition')
				->andWhere('c.rightPosition <= :rightPosition')
				->andWhere('c.user = :user')
				->setParameters(array(
					'leftPosition' => $category->getLeftPosition(),
					'rightPosition' => $category->getRightPosition(),
					'user' => $category->getUser()
				))
				->getQuery()->getResult();
		} else {
			return $this->createQueryBuilder('s')
				->innerJoin('s.categories', 'c')
				->where('c.id = :categoryId')
				->setParameter('categoryId', $category->getId())
				->getQuery()->getResult();
		}
	}

	public function findForUser(User $user) {
		return $this->createQueryBuilder('s')
			->innerJoin('s.feed', 'f')->addSelect('f')
			->where('s.user = :user')->setParameter('user', $user)
			->orderBy('s.name', 'ASC')
			->getQuery()->getResult();
	}

	public function findForUserWithMore(User $user) {
		return $this->createQueryBuilder('s')
			->innerJoin('s.feed', 'f')->addSelect('f')
			->leftJoin('s.categories', 'c')->addSelect('c')
			->where('s.user = :user')->setParameter('user', $user)
			->orderBy('s.name', 'ASC')
			->addOrderBy('c.name', 'ASC')
			->getQuery()->getResult();
	}

	public function findOneForUserWithMore($id, User $user) {
		return $this->createQueryBuilder('s')
			->innerJoin('s.feed', 'f')->addSelect('f')
			->leftJoin('s.categories', 'c')->addSelect('c')
			->where('s.user = :user')->setParameter('user', $user)
			->andWhere('s.id = :id')->setParameter('id', $id)
			->orderBy('s.name', 'ASC')
			->addorderBy('c.name', 'ASC')
			->getQuery()->getOneOrNullResult();
	}
}
