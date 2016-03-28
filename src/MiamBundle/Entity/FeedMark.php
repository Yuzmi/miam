<?php

namespace MiamBundle\Entity;

class FeedMark
{
    private $id;
    private $feed;
    private $user;
    private $isRead;
    private $dateRead;

    public function __construct() {
        $this->isRead = null;
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
     * Set dateRead
     *
     * @param \DateTime $dateRead
     *
     * @return FeedMark
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
     * Set feed
     *
     * @param \MiamBundle\Entity\Feed $feed
     *
     * @return FeedMark
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
     * @return FeedMark
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
     * Set isRead
     *
     * @param boolean $isRead
     *
     * @return FeedMark
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
}
