<?php

namespace MiamBundle\Entity;

use MiamBundle\Entity\Category;

class Subscription
{
    private $id;
    private $name;
    private $dateCreated;
    private $feed;
    private $user;
    private $categories;

    public function __construct() {
        $this->dateCreated = new \DateTime("now");
        $this->categories = new \Doctrine\Common\Collections\ArrayCollection();
    }

    public function getPath(Category $category) {
        return $category->getPath().' / '.$this->getName();
    }

    // Getters
    public function getId() { return $this->id; }
    public function getName() { return $this->name ?: $this->getFeed()->getName(); }
    public function getDateCreated() { return $this->dateCreated; }
    public function getFeed() { return $this->feed; }
    public function getUser() { return $this->user; }
    public function getCategories() { return $this->categories; }

    // Setters
    public function setName($name) { $this->name = $name; return $this; }
    public function setDateCreated($dateCreated) { $this->dateCreated = $dateCreated; return $this; }
    public function setFeed(\MiamBundle\Entity\Feed $feed = null) { $this->feed = $feed; return $this; }
    public function setUser(\MiamBundle\Entity\User $user = null) { $this->user = $user; return $this; }

    // Add to collection
    public function addCategory(\MiamBundle\Entity\Category $category) { $this->categories[] = $category; return $this; }

    // Remove from collection
    public function removeCategory(\MiamBundle\Entity\Category $category) { $this->categories->removeElement($category); }
}
