<?php

namespace MiamBundle\Entity;

class Item
{
    private $id;
    private $title;
    private $link;
    private $identifier;
    private $hash;
    private $htmlContent;
    private $textContent;
    private $author;
    private $contributor;
    private $dateCreated;
    private $datePublished;
    private $dateUpdated;
    private $dateModified;
    private $feed;
    private $tags;
    private $enclosures;
    private $marks;

    public function __construct() {
        $this->dateCreated = new \DateTime("now");
        $this->datePublished = new \DateTime("now");
        $this->dateUpdated = new \DateTime("now");
        $this->dateModified = new \DateTime("now");
        $this->tags = new \Doctrine\Common\Collections\ArrayCollection();
        $this->enclosures = new \Doctrine\Common\Collections\ArrayCollection();
        $this->marks = new \Doctrine\Common\Collections\ArrayCollection();
    }

    // Getters
    public function getId() { return $this->id; }
    public function getTitle() { return $this->title; }
    public function getLink() { return $this->link; }
    public function getIdentifier() { return $this->identifier; }
    public function getHash() { return $this->hash; }
    public function getHtmlContent() { return $this->htmlContent; }
    public function getTextContent() { return $this->textContent; }
    public function getAuthor() { return $this->author; }
    public function getContributor() { return $this->contributor; }
    public function getDateCreated() { return $this->dateCreated; }
    public function getDatePublished() { return $this->datePublished; }
    public function getDateUpdated() { return $this->dateUpdated; }
    public function getDateModified() { return $this->dateModified; }
    public function getFeed() { return $this->feed; }
    public function getTags() { return $this->tags; }
    public function getEnclosures() { return $this->enclosures; }
    public function getMarks() { return $this->marks; }

    // Setters
    public function setTitle($title) { $this->title = $title; return $this; }
    public function setLink($link) { $this->link = $link; return $this; }
    public function setIdentifier($identifier) { $this->identifier = $identifier; return $this; }
    public function setHash($hash) { $this->hash = $hash; return $this; }
    public function setHtmlContent($htmlContent) { $this->htmlContent = $htmlContent; return $this; }
    public function setTextContent($textContent) { $this->textContent = $textContent; return $this; }
    public function setAuthor($author) { $this->author = $author; return $this; }
    public function setContributor($contributor) { $this->contributor = $contributor; return $this; }
    public function setDateCreated($dateCreated) { $this->dateCreated = $dateCreated; return $this; }
    public function setDatePublished($datePublished) { $this->datePublished = $datePublished; return $this; }
    public function setDateUpdated($dateUpdated) { $this->dateUpdated = $dateUpdated; return $this; }
    public function setDateModified($dateModified) { $this->dateModified = $dateModified; return $this; }
    public function setFeed(\MiamBundle\Entity\Feed $feed = null) { $this->feed = $feed; return $this; }
    
    // Add to collection
    public function addTag(\MiamBundle\Entity\Tag $tag) { $this->tags[] = $tag; return $this; }
    public function addEnclosure(\MiamBundle\Entity\Enclosure $enclosure) { $this->enclosures[] = $enclosure; return $this; }
    public function addMark(\MiamBundle\Entity\ItemMark $mark) { $this->marks[] = $mark; return $this; }

    // Remove from collection
    public function removeTag(\MiamBundle\Entity\Tag $tag) { $this->tags->removeElement($tag); }
    public function removeEnclosure(\MiamBundle\Entity\Enclosure $enclosure) { $this->enclosures->removeElement($enclosure); }
    public function removeMark(\MiamBundle\Entity\ItemMark $mark) { $this->marks->removeElement($mark); }
}
