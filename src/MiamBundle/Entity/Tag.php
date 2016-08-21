<?php

namespace MiamBundle\Entity;

class Tag
{
    private $id;
    private $name;
    private $hash;
    private $dateCreated;
    private $items;

    public function __construct()
    {
        $this->dateCreated = new \DateTime("now");
        $this->items = new \Doctrine\Common\Collections\ArrayCollection();
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
     * @return Tag
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

    public function setHash($hash) {
        $this->hash = $hash;

        return $this;
    }

    public function getHash() {
        return $this->hash;
    }

    /**
     * Set dateCreated
     *
     * @param \DateTime $dateCreated
     *
     * @return Tag
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
     * @return Tag
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
}
