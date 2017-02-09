<?php

namespace MiamBundle\Entity;

class Category
{
    private $id;
    private $name;
    private $dateCreated;
    private $leftPosition;
    private $rightPosition;
    private $level;
    private $parent;
    private $user;
    private $subcategories;
    private $subscriptions;
    
    public function __construct() {
        $this->dateCreated = new \DateTime();
        $this->level = 0;
        $this->subcategories = new \Doctrine\Common\Collections\ArrayCollection();
        $this->subscriptions = new \Doctrine\Common\Collections\ArrayCollection();
    }

    // Getters
    public function getId() { return $this->id; }
    public function getName() { return $this->name; }
    public function getDateCreated() { return $this->dateCreated; }
    public function getLeftPosition() { return $this->leftPosition; }
    public function getRightPosition() { return $this->rightPosition; }
    public function getLevel() { return $this->level; }
    public function getParent() { return $this->parent; }
    public function getUser() { return $this->user; }
    public function getSubcategories() { return $this->subcategories; }
    public function getSubscriptions() { return $this->subscriptions; }

    // Setters
    public function setName($name) { $this->name = $name; return $this; }
    public function setDateCreated($dateCreated) { $this->dateCreated = $dateCreated; return $this; }
    public function setLeftPosition($leftPosition) { $this->leftPosition = $leftPosition; return $this; }
    public function setRightPosition($rightPosition) { $this->rightPosition = $rightPosition; return $this; }
    public function setLevel($level) { $this->level = $level; return $this; }
    public function setParent(\MiamBundle\Entity\Category $parent = null) { $this->parent = $parent; return $this; }
    public function setUser(\MiamBundle\Entity\User $user = null) { $this->user = $user; return $this; }

    // Add to collection
    public function addSubcategory(\MiamBundle\Entity\Category $subcategory) { $this->subcategories[] = $subcategory; return $this; }
    public function addSubscription(\MiamBundle\Entity\Subscription $subscription) { $this->subscriptions[] = $subscription; return $this; }

    // Remove from collection
    public function removeSubcategory(\MiamBundle\Entity\Category $subcategory) { $this->subcategories->removeElement($subcategory); }
    public function removeSubscription(\MiamBundle\Entity\Subscription $subscription) { $this->subscriptions->removeElement($subscription); }

    // Full path
    public function getPath() {
        return $this->parent ? $this->parent->getPath().' / '.$this->name : $this->name;
    }
}
