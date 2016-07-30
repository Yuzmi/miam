<?php

namespace MiamBundle\Services;

use MiamBundle\Entity\Category;
use MiamBundle\Entity\User;

class CategoryManager extends MainService {
	protected $em;
	private $container;

	public function __construct($em, $container) {
		$this->em = $em;
		$this->container = $container;
	}

	public function updateAll() {
		$this->updatePositions();
		$this->updateLevels();
	}

	public function updateForUser(User $user) {
		$this->updatePositionsForUser($user);
		$this->updateLevelsForUser($user);
	}

	public function updatePositions() {
		$users = $this->getRepo("User")->findAll();
		foreach($users as $u) {
			$this->updatePositionsForUser($u);
		}
	}

	public function updatePositionsForUser(User $user) {
		$categories = $this->getRepo("Category")
			->createQueryBuilder("c")
			->where("c.user = :user AND c.parent IS NULL")
			->setParameter("user", $user)
			->orderBy('c.name', 'ASC')
			->getQuery()->getResult();

		if(count($categories) > 0) {
			$value = 0;
			foreach($categories as $c) {
				$this->updatePositionsForCategory($c, $value);
			}

			$this->em->flush();
		}
	}

	private function updatePositionsForCategory(Category $category, &$value) {
		$value = $value + 1;
		$category->setLeftPosition($value);

		$subcategories = $category->getSubcategories();
		if(!empty($subcategories)) {
			foreach($subcategories as $c) {
				$this->updatePositionsForCategory($c, $value);
			}
		}

		$value = $value + 1;
		$category->setRightPosition($value);

		$this->em->persist($category);
	}

	public function updateLevels() {
		$users = $this->getRepo("User")->findAll();
		foreach($users as $u) {
			$this->updateLevelsForUser($u);
		}
	}

	public function updateLevelsForUser(User $user) {
		$categories = $this->getRepo("Category")
			->createQueryBuilder("c")
			->where("c.user = :user AND c.parent IS NULL")
			->setParameter("user", $user)
			->getQuery()->getResult();

		if(count($categories) > 0) {
			foreach($categories as $c) {
				$this->updateLevelsForCategory($c, 0);
			}

			$this->em->flush();
		}
	}

	private function updateLevelsForCategory(Category $category, $value) {
		$category->setLevel($value);
		$this->em->persist($category);

		foreach($category->getSubcategories() as $c) {
			$this->updateLevelsForCategory($c, $value + 1);
		}
	}
}