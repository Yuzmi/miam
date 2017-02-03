<?php

namespace MiamBundle\Entity;

class Enclosure
{
    private $id;
    private $title;
    private $description;
    private $url;
    private $hash;
    private $type;
    private $length;
    private $dateCreated;
    private $item;

    public function __construct() {
        $this->dateCreated = new \DateTime("now");
    }

    public function __toString() {
        return $this->url;
    }

    // Getters
    public function getId() { return $this->id; }
    public function getTitle() { return $this->title; }
    public function getDescription() { return $this->description; }
    public function getUrl() { return $this->url; }
    public function getHash() { return $this->hash; }
    public function getType() { return $this->type; }
    public function getLength() { return $this->length; }
    public function getDateCreated() { return $this->dateCreated; }
    public function getItem() { return $this->item; }

    // Setters
    public function setTitle($title) { $this->title = $title; return $this; }
    public function setDescription($description) { $this->description = $description; return $this; }
    public function setUrl($url) { $this->url = $url; return $this; }
    public function setHash($hash) { $this->hash = $hash; return $this; }
    public function setType($type) { $this->type = $type; return $this; }
    public function setLength($length) { $this->length = $length; return $this; }
    public function setDateCreated($dateCreated) { $this->dateCreated = $dateCreated; return $this; }
    public function setItem(\MiamBundle\Entity\Item $item = null) { $this->item = $item; return $this; }
}
