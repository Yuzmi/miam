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
    
    public function __construct()
    {
        $this->dateCreated = new \DateTime("now");
        $this->level = 0;
        $this->subscriptions = new \Doctrine\Common\Collections\ArrayCollection();
    }

    public function getPath() {
        $path = $this->name;

        if($this->parent) {
            $path = $this->parent->getPath().' / '.$path;
        }

        return $path;
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
     * @return Category
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
        return $this->name;
    }

    /**
     * Set dateCreated
     *
     * @param \DateTime $dateCreated
     *
     * @return Category
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
     * Add subcategory
     *
     * @param \MiamBundle\Entity\Category $subcategory
     *
     * @return Category
     */
    public function addSubcategory(\MiamBundle\Entity\Category $subcategory)
    {
        $this->subcategories[] = $subcategory;

        return $this;
    }

    /**
     * Remove subcategory
     *
     * @param \MiamBundle\Entity\Category $subcategory
     */
    public function removeSubcategory(\MiamBundle\Entity\Category $subcategory)
    {
        $this->subcategories->removeElement($subcategory);
    }

    /**
     * Get subcategories
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getSubcategories()
    {
        return $this->subcategories;
    }

    /**
     * Set parent
     *
     * @param \MiamBundle\Entity\Category $parent
     *
     * @return Category
     */
    public function setParent(\MiamBundle\Entity\Category $parent = null)
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * Get parent
     *
     * @return \MiamBundle\Entity\Category
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Set user
     *
     * @param \MiamBundle\Entity\User $user
     *
     * @return Category
     */
    public function setUser(\MiamBundle\Entity\User $user = null)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user
     *
     * @return \MiamBundle\Entity\User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set leftPosition
     *
     * @param integer $leftPosition
     *
     * @return Category
     */
    public function setLeftPosition($leftPosition)
    {
        $this->leftPosition = $leftPosition;

        return $this;
    }

    /**
     * Get leftPosition
     *
     * @return integer
     */
    public function getLeftPosition()
    {
        return $this->leftPosition;
    }

    /**
     * Set rightPosition
     *
     * @param integer $rightPosition
     *
     * @return Category
     */
    public function setRightPosition($rightPosition)
    {
        $this->rightPosition = $rightPosition;

        return $this;
    }

    /**
     * Get rightPosition
     *
     * @return integer
     */
    public function getRightPosition()
    {
        return $this->rightPosition;
    }

    /**
     * Set level
     *
     * @param integer $level
     *
     * @return Category
     */
    public function setLevel($level)
    {
        $this->level = $level;

        return $this;
    }

    /**
     * Get level
     *
     * @return integer
     */
    public function getLevel()
    {
        return $this->level;
    }

    /**
     * Add subscription
     *
     * @param \MiamBundle\Entity\Subscription $subscription
     *
     * @return Category
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
}
