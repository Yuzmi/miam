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

    public function __construct()
    {
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

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set name
     *
     * @param string $name
     *
     * @return Feed
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        if($this->customName) {
            return $this->customName;
        }

        return $this->name;
    }

    /**
     * Set url
     *
     * @param string $url
     *
     * @return Feed
     */
    public function setUrl($url)
    {
        $this->url = $url;

        return $this;
    }

    /**
     * Get url
     *
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Set hash
     *
     * @param string $hash
     *
     * @return Feed
     */
    public function setHash($hash)
    {
        $this->hash = $hash;

        return $this;
    }

    /**
     * Get hash
     *
     * @return string
     */
    public function getHash()
    {
        return $this->hash;
    }

    /**
     * Set iconUrl
     *
     * @param string $iconUrl
     *
     * @return Feed
     */
    public function setIconUrl($iconUrl)
    {
        $this->iconUrl = $iconUrl;

        return $this;
    }

    /**
     * Get iconUrl
     *
     * @return string
     */
    public function getIconUrl()
    {
        return $this->iconUrl;
    }

    /**
     * Set dateCreated
     *
     * @param \DateTime $dateCreated
     *
     * @return Feed
     */
    public function setDateCreated($dateCreated)
    {
        $this->dateCreated = $dateCreated;

        return $this;
    }

    /**
     * Get dateCreated
     *
     * @return \DateTime
     */
    public function getDateCreated()
    {
        return $this->dateCreated;
    }

    /**
     * Add item
     *
     * @param \MiamBundle\Entity\Item $item
     *
     * @return Feed
     */
    public function addItem(\MiamBundle\Entity\Item $item)
    {
        $this->items[] = $item;

        return $this;
    }

    /**
     * Remove item
     *
     * @param \MiamBundle\Entity\Item $item
     */
    public function removeItem(\MiamBundle\Entity\Item $item)
    {
        $this->items->removeElement($item);
    }

    /**
     * Get items
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getItems()
    {
        return $this->items;
    }

    /**
     * Add subscription
     *
     * @param \MiamBundle\Entity\Subscription $subscription
     *
     * @return Feed
     */
    public function addSubscription(\MiamBundle\Entity\Subscription $subscription)
    {
        $this->subscriptions[] = $subscription;

        return $this;
    }

    /**
     * Remove subscription
     *
     * @param \MiamBundle\Entity\Subscription $subscription
     */
    public function removeSubscription(\MiamBundle\Entity\Subscription $subscription)
    {
        $this->subscriptions->removeElement($subscription);
    }

    /**
     * Get subscriptions
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getSubscriptions()
    {
        return $this->subscriptions;
    }

    /**
     * Set website
     *
     * @param string $website
     *
     * @return Feed
     */
    public function setWebsite($website)
    {
        $this->website = $website;

        return $this;
    }

    /**
     * Get website
     *
     * @return string
     */
    public function getWebsite()
    {
        return $this->website;
    }

    /**
     * Set dateParsed
     *
     * @param \DateTime $dateParsed
     *
     * @return Feed
     */
    public function setDateParsed($dateParsed)
    {
        $this->dateParsed = $dateParsed;

        return $this;
    }

    /**
     * Get dateParsed
     *
     * @return \DateTime
     */
    public function getDateParsed()
    {
        return $this->dateParsed;
    }

    /**
     * Set dateSuccess
     *
     * @param \DateTime $dateSuccess
     *
     * @return Feed
     */
    public function setDateSuccess($dateSuccess)
    {
        $this->dateSuccess = $dateSuccess;

        return $this;
    }

    /**
     * Get dateSuccess
     *
     * @return \DateTime
     */
    public function getDateSuccess()
    {
        return $this->dateSuccess;
    }

    /**
     * Set dateNewItem
     *
     * @param \DateTime $dateNewItem
     *
     * @return Feed
     */
    public function setDateNewItem($dateNewItem)
    {
        $this->dateNewItem = $dateNewItem;

        return $this;
    }

    /**
     * Get dateNewItem
     *
     * @return \DateTime
     */
    public function getDateNewItem()
    {
        return $this->dateNewItem;
    }

    /**
     * Set dateIcon
     *
     * @param \DateTime $dateIcon
     *
     * @return Feed
     */
    public function setDateIcon($dateIcon)
    {
        $this->dateIcon = $dateIcon;

        return $this;
    }

    /**
     * Get dateIcon
     *
     * @return \DateTime
     */
    public function getDateIcon()
    {
        return $this->dateIcon;
    }

    /**
     * Set customName
     *
     * @param string $customName
     *
     * @return Feed
     */
    public function setCustomName($customName)
    {
        $this->customName = $customName;

        return $this;
    }

    /**
     * Get customName
     *
     * @return string
     */
    public function getCustomName()
    {
        return $this->customName;
    }

    /**
     * Set dataLength
     *
     * @param integer $dataLength
     *
     * @return Feed
     */
    public function setDataLength($dataLength)
    {
        $this->dataLength = $dataLength;

        return $this;
    }

    /**
     * Get dataLength
     *
     * @return integer
     */
    public function getDataLength()
    {
        return $this->dataLength;
    }

    /**
     * Set nbItems
     *
     * @param integer $nbItems
     *
     * @return Feed
     */
    public function setNbItems($nbItems)
    {
        $this->nbItems = $nbItems;

        return $this;
    }

    /**
     * Get nbItems
     *
     * @return integer
     */
    public function getNbItems()
    {
        return $this->nbItems;
    }

    /**
     * Set nbErrors
     *
     * @param integer $nbErrors
     *
     * @return Feed
     */
    public function setNbErrors($nbErrors)
    {
        $this->nbErrors = $nbErrors;

        return $this;
    }

    /**
     * Get nbErrors
     *
     * @return integer
     */
    public function getNbErrors()
    {
        return $this->nbErrors;
    }

    /**
     * Add mark
     *
     * @param \MiamBundle\Entity\FeedMark $mark
     *
     * @return Feed
     */
    public function addMark(\MiamBundle\Entity\FeedMark $mark)
    {
        $this->marks[] = $mark;

        return $this;
    }

    /**
     * Remove mark
     *
     * @param \MiamBundle\Entity\FeedMark $mark
     */
    public function removeMark(\MiamBundle\Entity\FeedMark $mark)
    {
        $this->marks->removeElement($mark);
    }

    /**
     * Get marks
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getMarks()
    {
        return $this->marks;
    }

    /**
     * Set isCatalog
     *
     * @param boolean $isCatalog
     *
     * @return Feed
     */
    public function setIsCatalog($isCatalog)
    {
        $this->isCatalog = $isCatalog;

        return $this;
    }

    /**
     * Get isCatalog
     *
     * @return boolean
     */
    public function getIsCatalog()
    {
        return $this->isCatalog;
    }

    /**
     * Set hasIcon
     *
     * @param boolean $hasIcon
     *
     * @return Feed
     */
    public function setHasIcon($hasIcon)
    {
        $this->hasIcon = $hasIcon;

        return $this;
    }

    /**
     * Get hasIcon
     *
     * @return boolean
     */
    public function getHasIcon()
    {
        return $this->hasIcon;
    }

    public function getIcon() {
        return $this->hasIcon ? $this->getIconPath() : '';
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
