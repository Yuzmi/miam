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
     * Set title
     *
     * @param string $title
     *
     * @return Item
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title
     *
     * @return string
     */
    public function getTitle()
    {
        if(!$this->title) {
            return "[NO TITLE]";
        }

        return $this->title;
    }

    public function getRealTitle() {
        return $this->title;
    }

    /**
     * Set link
     *
     * @param string $link
     *
     * @return Item
     */
    public function setLink($link)
    {
        $this->link = $link;

        return $this;
    }

    /**
     * Get link
     *
     * @return string
     */
    public function getLink()
    {
        return $this->link;
    }
    
    /**
     * Set dateCreated
     *
     * @param \DateTime $dateCreated
     *
     * @return Item
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
     * @return Item
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
     * Set author
     *
     * @param string $author
     *
     * @return Item
     */
    public function setAuthor($author)
    {
        $this->author = $author;

        return $this;
    }

    /**
     * Get author
     *
     * @return string
     */
    public function getAuthor()
    {
        return $this->author;
    }

    /**
     * Set datePublished
     *
     * @param \DateTime $datePublished
     *
     * @return Item
     */
    public function setDatePublished($datePublished)
    {
        $this->datePublished = $datePublished;

        return $this;
    }

    /**
     * Get datePublished
     *
     * @return \DateTime
     */
    public function getDatePublished()
    {
        return $this->datePublished;
    }

    /**
     * Set dateUpdated
     *
     * @param \DateTime $dateUpdated
     *
     * @return Item
     */
    public function setDateUpdated($dateUpdated)
    {
        $this->dateUpdated = $dateUpdated;

        return $this;
    }

    /**
     * Get dateUpdated
     *
     * @return \DateTime
     */
    public function getDateUpdated()
    {
        return $this->dateUpdated;
    }

    /**
     * Set dateModified
     *
     * @param \DateTime $dateModified
     *
     * @return Item
     */
    public function setDateModified($dateModified)
    {
        $this->dateModified = $dateModified;

        return $this;
    }

    /**
     * Get dateModified
     *
     * @return \DateTime
     */
    public function getDateModified()
    {
        return $this->dateModified;
    }

    /**
     * Set identifier
     *
     * @param string $identifier
     *
     * @return Item
     */
    public function setIdentifier($identifier)
    {
        $this->identifier = $identifier;

        return $this;
    }

    /**
     * Get identifier
     *
     * @return string
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * Add enclosure
     *
     * @param \MiamBundle\Entity\Enclosure $enclosure
     *
     * @return Item
     */
    public function addEnclosure(\MiamBundle\Entity\Enclosure $enclosure)
    {
        $this->enclosures[] = $enclosure;

        return $this;
    }

    /**
     * Remove enclosure
     *
     * @param \MiamBundle\Entity\Enclosure $enclosure
     */
    public function removeEnclosure(\MiamBundle\Entity\Enclosure $enclosure)
    {
        $this->enclosures->removeElement($enclosure);
    }

    /**
     * Get enclosures
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getEnclosures()
    {
        return $this->enclosures;
    }

    /**
     * Add tag
     *
     * @param \MiamBundle\Entity\Tag $tag
     *
     * @return Item
     */
    public function addTag(\MiamBundle\Entity\Tag $tag)
    {
        $this->tags[] = $tag;

        return $this;
    }

    /**
     * Remove tag
     *
     * @param \MiamBundle\Entity\Tag $tag
     */
    public function removeTag(\MiamBundle\Entity\Tag $tag)
    {
        $this->tags->removeElement($tag);
    }

    /**
     * Get tags
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getTags()
    {
        return $this->tags;
    }

    /**
     * Set htmlContent
     *
     * @param string $htmlContent
     *
     * @return Item
     */
    public function setHtmlContent($htmlContent)
    {
        $this->htmlContent = $htmlContent;

        return $this;
    }

    /**
     * Get htmlContent
     *
     * @return string
     */
    public function getHtmlContent()
    {
        return $this->htmlContent;
    }

    /**
     * Set textContent
     *
     * @param string $textContent
     *
     * @return Item
     */
    public function setTextContent($textContent)
    {
        $this->textContent = $textContent;

        return $this;
    }

    /**
     * Get textContent
     *
     * @return string
     */
    public function getTextContent()
    {
        return $this->textContent;
    }

    /**
     * Set rawContent
     *
     * @param string $rawContent
     *
     * @return Item
     */
    public function setRawContent($rawContent)
    {
        $this->rawContent = $rawContent;

        return $this;
    }

    /**
     * Get rawContent
     *
     * @return string
     */
    public function getRawContent()
    {
        return $this->rawContent;
    }

    /**
     * Add mark
     *
     * @param \MiamBundle\Entity\ItemMark $mark
     *
     * @return Item
     */
    public function addMark(\MiamBundle\Entity\ItemMark $mark)
    {
        $this->marks[] = $mark;

        return $this;
    }

    /**
     * Remove mark
     *
     * @param \MiamBundle\Entity\ItemMark $mark
     */
    public function removeMark(\MiamBundle\Entity\ItemMark $mark)
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
     * Set hash
     *
     * @param string $hash
     *
     * @return Item
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
}
