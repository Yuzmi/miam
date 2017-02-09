<?php

namespace MiamBundle\Entity;

use Symfony\Component\Security\Core\User\UserInterface;

class User implements UserInterface, \Serializable
{
    private $id;
    private $username;
    private $password;
    private $salt;
    private $dateCreated;
    private $dateLogin;
    private $isAdmin;
    private $locale;
    private $settings;
    private $subscriptions;
    private $categories;

    public function __construct() {
        $this->salt = uniqid(mt_rand(), true);
        $this->dateCreated = new \DateTime();
        $this->isAdmin = false;
        $this->settings = serialize(array());
        $this->subscriptions = new \Doctrine\Common\Collections\ArrayCollection();
    }

    public function __toString() {
        return $this->id." - ".$this->username;
    }

    // Getters
    public function getId() { return $this->id; }
    public function getUsername() { return $this->username; }
    public function getPassword() { return $this->password; }
    public function getSalt() { return $this->salt; }
    public function getIsAdmin() { return $this->isAdmin; }
    public function getLocale() { return $this->locale; }
    public function getDateCreated() { return $this->dateCreated; }
    public function getDateLogin() { return $this->dateLogin; }
    public function getSubscriptions() { return $this->subscriptions; }
    public function getCategories() { return $this->categories; }

    // Setters
    public function setUsername($username) { $this->username = $username; return $this; }
    public function setPassword($password) { $this->password = $password; return $this; }
    public function setSalt($salt) { $this->salt = $salt; return $this; }
    public function setIsAdmin($isAdmin) { $this->isAdmin = $isAdmin; return $this; }
    public function setLocale($locale) { $this->locale = $locale; return $this; }
    public function setDateCreated($dateCreated) { $this->dateCreated = $dateCreated; return $this; }
    public function setDateLogin($dateLogin) { $this->dateLogin = $dateLogin; return $this; }

    // Add to collection
    public function addSubscription(\MiamBundle\Entity\Subscription $subscription) { $this->subscriptions[] = $subscription; return $this; }
    public function addCategory(\MiamBundle\Entity\Category $category) { $this->categories[] = $category; return $this; }

    // Remove from collection
    public function removeSubscription(\MiamBundle\Entity\Subscription $subscription) { $this->subscriptions->removeElement($subscription); }
    public function removeCategory(\MiamBundle\Entity\Category $category) { $this->categories->removeElement($category); }

    // UserInterface methods

    public function getRoles() {
        $roles = array('ROLE_USER');

        if($this->isAdmin) {
            $roles[] = 'ROLE_ADMIN';
        }

        return $roles;
    }

    public function eraseCredentials() {}

    // Serializable methods

    public function serialize() {
        return serialize(array(
            $this->id,
            $this->username,
            $this->password
        ));
    }

    public function unserialize($serialized) {
        list(
            $this->id,
            $this->username,
            $this->password
        ) = unserialize($serialized);
    }

    // Settings-related methods

    // All settings and their default values
    private $allSettings = array(
        'SHOW_ITEM_PICTURES' => "always",
        'SHOW_ITEM_DETAILS' => "onclick",
        'HIDE_SIDEBAR' => false,
        'THEME' => "basic",
        'FONT_SIZE' => 10,
        'DATE_FORMAT' => "dmy"
    );

    public function getSettings() {
        try {
            $settings = (array) unserialize($this->settings);
        } catch(\Exception $e) {
            $settings = array();
        }

        return array_intersect_key($settings, $this->allSettings);
    }

    public function setSettings(array $settings) {
        $this->settings = serialize($settings);
    }

    public function getSetting($key) {
        $settings = $this->getSettings();

        if(array_key_exists($key, $settings)) {
            return $settings[$key];
        } elseif(array_key_exists($key, $this->allSettings)) {
            return $this->allSettings[$key];
        }

        return null;
    }

    public function setSetting($key, $value) {
        $settings = $this->getSettings();

        if($key == 'SHOW_ITEM_PICTURES') {
            if(!in_array($value, array('always', 'onclick', 'never'))) {
                return;
            }
        } elseif($key == 'SHOW_ITEM_DETAILS') {
            if(!in_array($value, array('always', 'onclick'))) {
                return;
            }
        } elseif($key == 'HIDE_SIDEBAR') {
            $value = boolval($value);
        } elseif($key == 'THEME') {
            if(!in_array($value, array('basic', 'dark'))) {
                return;
            }
        } elseif($key == 'FONT_SIZE') {
            $font_size = intval($value);
            if($font_size < 7 || $font_size > 18) {
                return;
            }
        } elseif($key == 'DATE_FORMAT') {
            if(!in_array($value, array('dmy', 'mdy', 'ymd'))) {
                return;
            }
        }

        $settings[$key] = $value;

        $this->setSettings($settings);
    }
}
