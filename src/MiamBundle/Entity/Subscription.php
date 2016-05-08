<?php

namespace MiamBundle\Entity;

use MiamBundle\Entity\Category;

class Subscription
{
    private $id;
    private $name;
    private $dateCreated;
    private $feed;
    private $user;
    private $categories;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->dateCreated = new \DateTime("now");
        $this->categories = new \Doctrine\Common\Collections\ArrayCollection();
    }

    public function getPath(Category $category) {
        return $category->getPath().' / '.$this->getName();
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
     * @return Subscription
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
        if(!$this->name) {
            return $this->getFeed()->getName();
        }

        return $this->name;
    }

    /**
     * Set dateCreated
     *
     * @param \DateTime $dateCreated
     *
     * @return Subscription
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
     * Set feed
     *
     * @param \MiamBundle\Entity\Feed $feed
     *
     * @return Subscription
     */
    public function setFeed(\MiamBundle\Entity\Feed $feed = null)
    {
        $this->feed = $feed;

        return $this;
    }

    /**
     * Get feed
     *
     * @return \MiamBundle\Entity\Feed
     */
    public function getFeed()
    {
        return $this->feed;
    }

    /**
     * Set user
     *
     * @param \MiamBundle\Entity\User $user
     *
     * @return Subscription
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
     * Add category
     *
     * @param \MiamBundle\Entity\Category $category
     *
     * @return Subscription
     */
    public function addCategory(\MiamBundle\Entity\Category $category)
    {
        $this->categories[] = $category;

        return $this;
    }

    /**
     * Remove category
     *
     * @param \MiamBundle\Entity\Category $category
     */
    public function removeCategory(\MiamBundle\Entity\Category $category)
    {
        $this->categories->removeElement($category);
    }

    /**
     * Get categories
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getCategories()
    {
        return $this->categories;
    }
}
