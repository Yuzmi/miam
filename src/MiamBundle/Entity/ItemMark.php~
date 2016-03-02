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
        $this->isRead = false;
        $this->isStarred = false;
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
     * Set isRead
     *
     * @param boolean $isRead
     *
     * @return ItemMark
     */
    public function setIsRead($isRead)
    {
        $this->isRead = $isRead;

        return $this;
    }

    /**
     * Get isRead
     *
     * @return boolean
     */
    public function getIsRead()
    {
        return $this->isRead;
    }

    /**
     * Set item
     *
     * @param \MiamBundle\Entity\Item $item
     *
     * @return ItemMark
     */
    public function setItem(\MiamBundle\Entity\Item $item = null)
    {
        $this->item = $item;

        return $this;
    }

    /**
     * Get item
     *
     * @return \MiamBundle\Entity\Item
     */
    public function getItem()
    {
        return $this->item;
    }

    /**
     * Set user
     *
     * @param \MiamBundle\Entity\User $user
     *
     * @return ItemMark
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
     * Set dateRead
     *
     * @param \DateTime $dateRead
     *
     * @return ItemMark
     */
    public function setDateRead($dateRead)
    {
        $this->dateRead = $dateRead;

        return $this;
    }

    /**
     * Get dateRead
     *
     * @return \DateTime
     */
    public function getDateRead()
    {
        return $this->dateRead;
    }

    /**
     * Set isStarred
     *
     * @param boolean $isStarred
     *
     * @return ItemMark
     */
    public function setIsStarred($isStarred)
    {
        $this->isStarred = $isStarred;

        return $this;
    }

    /**
     * Get isStarred
     *
     * @return boolean
     */
    public function getIsStarred()
    {
        return $this->isStarred;
    }
}
