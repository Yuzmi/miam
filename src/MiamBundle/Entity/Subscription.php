<?php

namespace MiamBundle\Entity;

class Subscription
{
    private $id;
    private $name;
    private $dateCreated;
    private $feed;
    private $user;
    private $category;

    public function __construct() {
        $this->dateCreated = new \DateTime("now");
    }

    public function getPath() {
        return $this->category ? $this->category->getPath().' / '.$this->name : $this->name;
    }

    // Getters
    public function getId() { return $this->id; }
    public function getName() { return $this->name ?: $this->getFeed()->getName(); }
    public function getDateCreated() { return $this->dateCreated; }
    public function getFeed() { return $this->feed; }
    public function getUser() { return $this->user; }
    public function getCategory() { return $this->category; }

    // Setters
    public function setName($name) { $this->name = $name; return $this; }
    public function setDateCreated($dateCreated) { $this->dateCreated = $dateCreated; return $this; }
    public function setFeed(\MiamBundle\Entity\Feed $feed = null) { $this->feed = $feed; return $this; }
    public function setUser(\MiamBundle\Entity\User $user = null) { $this->user = $user; return $this; }
    public function setCategory(\MiamBundle\Entity\Category $category = null) { $this->category = $category; return $this; }
}
