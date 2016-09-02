<?php

namespace MiamBundle\Entity;

class FeedMark
{
    private $id;
    private $isRead;
    private $dateRead;
    private $feed;
    private $user;

    public function __construct() {
        $this->isRead = null;
    }

    // Getters
    public function getId() { return $this->id; }
    public function getIsRead() { return $this->isRead; }
    public function getDateRead() { return $this->dateRead; }
    public function getFeed() { return $this->feed; }
    public function getUser() { return $this->user; }

    // Setters
    public function setIsRead($isRead) { $this->isRead = $isRead; return $this; }
    public function setDateRead($dateRead) { $this->dateRead = $dateRead; return $this; }
    public function setFeed(\MiamBundle\Entity\Feed $feed = null) { $this->feed = $feed; return $this; }
    public function setUser(\MiamBundle\Entity\User $user = null) { $this->user = $user; return $this; }
}
