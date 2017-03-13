<?php

namespace MiamBundle\Entity;

class Feed
{
    private $id;
    private $originalName;
    private $customName;
    private $originalDescription;
    private $customDescription;
    private $url;
    private $urlHash;
    private $iconUrl;
    private $website;
    private $author;
    private $language;
    private $dataLength;
    private $countFirstParsedItems;
    private $countLastParsedItems;
    private $countTotalItems;
    private $countDailyItems;
    private $errorCount;
    private $errorMessage;
    private $hasIcon;
    private $dateCreated;
    private $dateParsed;
    private $dateFirstSuccess;
    private $dateLastSuccess;
    private $dateNewItem;
    private $dateIcon;
    private $items;
    private $pshbSubscriptions;
    private $subscriptions;

    public function __construct() {
        $this->dataLength = 0;
        $this->countFirstParsedItems = 0;
        $this->countLastParsedItems = 0;
        $this->countTotalItems = 0;
        $this->countDailyItems = 0;
        $this->errorCount = 0;
        $this->hasIcon = false;
        $this->dateCreated = new \DateTime();
        $this->items = new \Doctrine\Common\Collections\ArrayCollection();
        $this->pshbSubscriptions = new \Doctrine\Common\Collections\ArrayCollection();
        $this->subscriptions = new \Doctrine\Common\Collections\ArrayCollection();
    }

    public function __toString() {
        return $this->customName ?: $this->originalName ?: $this->url;
    }

    // Getters
    public function getId() { return $this->id; }
    public function getName() { return $this->customName ?: $this->originalName; }
    public function getOriginalName() { return $this->originalName; }
    public function getCustomName() { return $this->customName; }
    public function getDescription() { return $this->customDescription ?: $this->originalDescription; }
    public function getOriginalDescription() { return $this->originalDescription; }
    public function getCustomDescription() { return $this->customDescription; }
    public function getUrl() { return $this->url; }
    public function getUrlHash() { return $this->urlHash; }
    public function getIconUrl() { return $this->iconUrl; }
    public function getWebsite() { return $this->website; }
    public function getAuthor() { return $this->author; }
    public function getLanguage() { return $this->language; }
    public function getDataLength() { return $this->dataLength; }
    public function getCountFirstParsedItems() { return $this->countFirstParsedItems; }
    public function getCountLastParsedItems() { return $this->countLastParsedItems; }
    public function getCountTotalItems() { return $this->countTotalItems; }
    public function getCountDailyItems() { return $this->countDailyItems; }
    public function getErrorCount() { return $this->errorCount; }
    public function getErrorMessage() { return $this->errorMessage; }
    public function getHasIcon() { return $this->hasIcon; }
    public function getDateCreated() { return $this->dateCreated; }
    public function getDateParsed() { return $this->dateParsed; }
    public function getDateFirstSuccess() { return $this->dateFirstSuccess; }
    public function getDateLastSuccess() { return $this->dateLastSuccess; }
    public function getDateNewItem() { return $this->dateNewItem; }
    public function getDateIcon() { return $this->dateIcon; }
    public function getItems() { return $this->items; }
    public function getSubscriptions() { return $this->subscriptions; }

    // Setters
    public function setOriginalName($originalName) { $this->originalName = $originalName; return $this; }
    public function setCustomName($customName) { $this->customName = $customName; return $this; }
    public function setOriginalDescription($originalDescription) { $this->originalDescription = $originalDescription; return $this; }
    public function setCustomDescription($customDescription) { $this->customDescription = $customDescription; return $this; }
    public function setUrl($url) { $this->url = $url; return $this; }
    public function setUrlHash($urlHash) { $this->urlHash = $urlHash; return $this; }
    public function setIconUrl($iconUrl) { $this->iconUrl = $iconUrl; return $this; }
    public function setWebsite($website) { $this->website = $website; return $this; }
    public function setAuthor($author) { $this->author = $author; return $this; }
    public function setLanguage($language) { $this->language = $language; return $this; }
    public function setDataLength($dataLength) { $this->dataLength = $dataLength; return $this; }
    public function setCountFirstParsedItems($countFirstParsedItems) { $this->countFirstParsedItems = $countFirstParsedItems; return $this; }
    public function setCountLastParsedItems($countLastParsedItems) { $this->countLastParsedItems = $countLastParsedItems; return $this; }
    public function setCountTotalItems($countTotalItems) { $this->countTotalItems = $countTotalItems; return $this; }
    public function setCountDailyItems($countDailyItems) { $this->countDailyItems = $countDailyItems; return $this; }
    public function setErrorCount($errorCount) { $this->errorCount = $errorCount; return $this; }
    public function setErrorMessage($errorMessage) { $this->errorMessage = $errorMessage; return $this; }
    public function setHasIcon($hasIcon) { $this->hasIcon = $hasIcon; return $this; }
    public function setDateCreated($dateCreated) { $this->dateCreated = $dateCreated; return $this; }
    public function setDateParsed($dateParsed) { $this->dateParsed = $dateParsed; return $this; }
    public function setDateFirstSuccess($dateFirstSuccess) { $this->dateFirstSuccess = $dateFirstSuccess; return $this; }
    public function setDateLastSuccess($dateLastSuccess) { $this->dateLastSuccess = $dateLastSuccess; return $this; }
    public function setDateNewItem($dateNewItem) { $this->dateNewItem = $dateNewItem; return $this; }
    public function setDateIcon($dateIcon) { $this->dateIcon = $dateIcon; return $this; }

    // Add to collection
    public function addItem(\MiamBundle\Entity\Item $item) { $this->items[] = $item; return $this; }
    public function addPshbSubscription(\MiamBundle\Entity\PshbSubscription $pshbSubscription) { $this->pshbSubscriptions[] = $pshbSubscription; return $this; }
    public function addSubscription(\MiamBundle\Entity\Subscription $subscription) { $this->subscriptions[] = $subscription; return $this; }

    // Remove from collection
    public function removeItem(\MiamBundle\Entity\Item $item) { $this->items->removeElement($item); }
    public function removePshbSubscription(\MiamBundle\Entity\PshbSubscription $pshbSubscription) { $this->pshbSubscriptions->removeElement($pshbSubscription); }
    public function removeSubscription(\MiamBundle\Entity\Subscription $subscription) { $this->subscriptions->removeElement($subscription); }

    // Icon-related methods

    public function getIcon() {
        return $this->hasIcon ? $this->getIconRelativePath() : 'images/no-icon.png';
    }

    public function getIconRelativePath() {
        return 'images/feeds/icon-'.$this->id.'.png';
    }

    private $iconPathForRemoval;

    public function prepareIconRemoval() {
        $this->iconPathForRemoval = __DIR__.'/../../../web/'.$this->getIconRelativePath();
    }

    public function removeIcon() {
        if(is_file($this->iconPathForRemoval)) {
            @unlink($this->iconPathForRemoval);
        }
    }
}
