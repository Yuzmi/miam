<?php

namespace MiamBundle\Entity;

class PshbSubscription
{
    private $id;
    private $leaseSeconds;
    private $reason;
    private $dateCreated;
    private $dateSubscribed;
    private $dateUnsubscribed;
    private $dateDenied;
    private $feed;

    public function __construct() {
        $this->leaseSeconds = 0;
        $this->dateCreated = new \DateTime();
    }

    // Getters
    public function getId() { return $this->id; }
    public function getLeaseSeconds() { return $this->leaseSeconds; }
    public function getReason() { return $this->reason; }
    public function getDateCreated() { return $this->dateCreated; }
    public function getDateSubscribed() { return $this->dateSubscribed; }
    public function getDateUnsubscribed() { return $this->dateUnsubscribed; }
    public function getDateDenied() { return $this->dateDenied; }
    public function getFeed() { return $this->feed; }

    // Setters
    public function setLeaseSeconds($leaseSeconds) { $this->leaseSeconds = $leaseSeconds; return $this; }
    public function setReason($reason) { $this->reason = $reason; return $this; }
    public function setDateCreated($dateCreated) { $this->dateCreated = $dateCreated; return $this; }
    public function setDateSubscribed($dateSubscribed) { $this->dateSubscribed = $dateSubscribed; return $this; }
    public function setDateUnsubscribed($dateUnsubscribed) { $this->dateUnsubscribed = $dateUnsubscribed; return $this; }
    public function setDateDenied($dateDenied) { $this->dateDenied = $dateDenied; return $this; }
    public function setFeed(\MiamBundle\Entity\Feed $feed = null) { $this->feed = $feed; return $this; }
}
