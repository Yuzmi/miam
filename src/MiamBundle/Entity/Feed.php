<?php

namespace MiamBundle\Entity;

class Feed
{
    private $id;
    private $name;
    private $customName;
    private $url;
    private $hash;
    private $iconUrl;
    private $website;
    private $author;
    private $dataLength;
    private $nbItems;
    private $nbErrors;
    private $isCatalog;
    private $hasIcon;
    private $dateCreated;
    private $dateParsed;
    private $dateSuccess;
    private $dateNewItem;
    private $dateIcon;
    private $items;
    private $subscriptions;
    private $marks;

    public function __construct() {
        $this->dataLength = 0;
        $this->nbItems = 0;
        $this->nbErrors = 0;
        $this->isCatalog = false;
        $this->hasIcon = false;
        $this->dateCreated = new \DateTime("now");
        $this->items = new \Doctrine\Common\Collections\ArrayCollection();
        $this->subscriptions = new \Doctrine\Common\Collections\ArrayCollection();
        $this->marks = new \Doctrine\Common\Collections\ArrayCollection();
    }

    public function __toString() {
        return $this->name ?: $this->url;
    }

    // Getters
    public function getId() { return $this->id; }
    public function getName() { return $this->customName ?: $this->name; }
    public function getCustomName() { return $this->customName; }
    public function getUrl() { return $this->url; }
    public function getHash() { return $this->hash; }
    public function getIconUrl() { return $this->iconUrl; }
    public function getWebsite() { return $this->website; }
    public function getAuthor() { return $this->author; }
    public function getDataLength() { return $this->dataLength; }
    public function getNbItems() { return $this->nbItems; }
    public function getNbErrors() { return $this->nbErrors; }
    public function getIsCatalog() { return $this->isCatalog; }
    public function getHasIcon() { return $this->hasIcon; }
    public function getDateCreated() { return $this->dateCreated; }
    public function getDateParsed() { return $this->dateParsed; }
    public function getDateSuccess() { return $this->dateSuccess; }
    public function getDateNewItem() { return $this->dateNewItem; }
    public function getDateIcon() { return $this->dateIcon; }
    public function getItems() { return $this->items; }
    public function getSubscriptions() { return $this->subscriptions; }
    public function getMarks() { return $this->marks; }

    // Setters
    public function setName($name) { $this->name = $name; return $this; }
    public function setCustomName($customName) { $this->customName = $customName; return $this; }
    public function setUrl($url) { $this->url = $url; return $this; }
    public function setHash($hash) { $this->hash = $hash; return $this; }
    public function setIconUrl($iconUrl) { $this->iconUrl = $iconUrl; return $this; }
    public function setWebsite($website) { $this->website = $website; return $this; }
    public function setAuthor($author) { $this->author = $author; return $this; }
    public function setDataLength($dataLength) { $this->dataLength = $dataLength; return $this; }
    public function setNbItems($nbItems) { $this->nbItems = $nbItems; return $this; }
    public function setNbErrors($nbErrors) { $this->nbErrors = $nbErrors; return $this; }
    public function setIsCatalog($isCatalog) { $this->isCatalog = $isCatalog; return $this; }
    public function setHasIcon($hasIcon) { $this->hasIcon = $hasIcon; return $this; }
    public function setDateCreated($dateCreated) { $this->dateCreated = $dateCreated; return $this; }
    public function setDateParsed($dateParsed) { $this->dateParsed = $dateParsed; return $this; }
    public function setDateSuccess($dateSuccess) { $this->dateSuccess = $dateSuccess; return $this; }
    public function setDateNewItem($dateNewItem) { $this->dateNewItem = $dateNewItem; return $this; }
    public function setDateIcon($dateIcon) { $this->dateIcon = $dateIcon; return $this; }

    // Add to collection
    public function addItem(\MiamBundle\Entity\Item $item) { $this->items[] = $item; return $this; }
    public function addSubscription(\MiamBundle\Entity\Subscription $subscription) { $this->subscriptions[] = $subscription; return $this; }
    public function addMark(\MiamBundle\Entity\FeedMark $mark) { $this->marks[] = $mark; return $this; }

    // Remove from collection
    public function removeItem(\MiamBundle\Entity\Item $item) { $this->items->removeElement($item); }
    public function removeSubscription(\MiamBundle\Entity\Subscription $subscription) { $this->subscriptions->removeElement($subscription); }
    public function removeMark(\MiamBundle\Entity\FeedMark $mark) { $this->marks->removeElement($mark); }


    // Icon-related methods

    public function getIcon() {
        return $this->hasIcon ? $this->getIconPath() : 'images/no-icon.png';
    }

    private function getIconPath() {
        return 'images/feeds/icon-'.$this->id.'.png';
    }

    private $iconPathForRemoval;

    public function prepareIconRemoval() {
        $this->iconPathForRemoval = __DIR__.'/../../../web/'.$this->getIconPath();
    }

    public function removeIcon() {
        if(is_file($this->iconPathForRemoval)) {
            @unlink($this->iconPathForRemoval);
        }
    }
}
