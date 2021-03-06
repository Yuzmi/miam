<?php

namespace MiamBundle\Entity;

class Tag
{
    private $id;
    private $name;
    private $hash;
    private $dateCreated;
    private $items;

    public function __construct() {
        $this->dateCreated = new \DateTime();
        $this->items = new \Doctrine\Common\Collections\ArrayCollection();
    }

    public function __toString() {
        return $this->name;
    }

    // Getters
    public function getId() { return $this->id; }
    public function getName() { return $this->name; }
    public function getHash() { return $this->hash; }
    public function getDateCreated() { return $this->dateCreated; }
    public function getItems() { return $this->items; }

    // Setters
    public function setName($name) { $this->name = $name; return $this; }
    public function setHash($hash) { $this->hash = $hash; return $this; }
    public function setDateCreated($dateCreated) { $this->dateCreated = $dateCreated; return $this; }

    // Add to collection
    public function addItem(\MiamBundle\Entity\Item $item) { $this->items[] = $item; return $this; }

    // Remove from collection
    public function removeItem(\MiamBundle\Entity\Item $item) { $this->items->removeElement($item); }
}
