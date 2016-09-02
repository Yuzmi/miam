<?php

namespace MiamBundle\Entity;

class ItemMark
{
    private $id;
    private $isRead;
    private $isStarred;
    private $dateRead;
    private $item;
    private $user;

    public function __construct() {
        $this->isRead = null;
        $this->isStarred = false;
    }
    
    // Getters
    public function getId() { return $this->id; }
    public function getIsRead() { return $this->isRead; }
    public function getIsStarred() { return $this->isStarred; }
    public function getDateRead() { return $this->dateRead; }
    public function getItem() { return $this->item; }
    public function getUser() { return $this->user; }

    // Setters
    public function setIsRead($isRead) { $this->isRead = $isRead; return $this; }
    public function setIsStarred($isStarred) { $this->isStarred = $isStarred; return $this; }
    public function setDateRead($dateRead) { $this->dateRead = $dateRead; return $this; }
    public function setItem(\MiamBundle\Entity\Item $item = null) { $this->item = $item; return $this; }
    public function setUser(\MiamBundle\Entity\User $user = null) { $this->user = $user; return $this; }
}
