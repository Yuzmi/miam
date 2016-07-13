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
    private $settings;
    private $subscriptions;
    private $categories;

    public function __construct()
    {
        $this->salt = uniqid(mt_rand(), true);
        $this->dateCreated = new \DateTime("now");
        $this->isAdmin = false;
        $this->settings = serialize(array());
        $this->subscriptions = new \Doctrine\Common\Collections\ArrayCollection();
    }

    public function __toString() {
        return $this->id." - ".$this->username;
    }

    public function getId() {
        return $this->id;
    }

    public function getUsername() {
        return $this->username;
    }

    public function getPassword() {
        return $this->password;
    }

    public function getSalt() {
        return $this->salt;
    }

    public function getRoles() {
        $roles = array('ROLE_USER');

        if($this->isAdmin) {
            $roles[] = 'ROLE_ADMIN';
        }

        return $roles;
    }

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

    public function eraseCredentials() {}

    /**
     * Set username
     *
     * @param string $username
     *
     * @return User
     */
    public function setUsername($username)
    {
        $this->username = $username;

        return $this;
    }

    /**
     * Set password
     *
     * @param string $password
     *
     * @return User
     */
    public function setPassword($password)
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Set salt
     *
     * @param string $salt
     *
     * @return User
     */
    public function setSalt($salt)
    {
        $this->salt = $salt;

        return $this;
    }

    /**
     * Set dateCreated
     *
     * @param \DateTime $dateCreated
     *
     * @return User
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
     * Set dateLogin
     *
     * @param \DateTime $dateLogin
     *
     * @return User
     */
    public function setDateLogin($dateLogin)
    {
        $this->dateLogin = $dateLogin;

        return $this;
    }

    /**
     * Get dateLogin
     *
     * @return \DateTime
     */
    public function getDateLogin()
    {
        return $this->dateLogin;
    }

    /**
     * Add subscription
     *
     * @param \MiamBundle\Entity\Subscription $subscription
     *
     * @return User
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
     * Set isAdmin
     *
     * @param boolean $isAdmin
     *
     * @return User
     */
    public function setIsAdmin($isAdmin)
    {
        $this->isAdmin = $isAdmin;

        return $this;
    }

    /**
     * Get isAdmin
     *
     * @return boolean
     */
    public function getIsAdmin()
    {
        return $this->isAdmin;
    }

    /**
     * Add category
     *
     * @param \MiamBundle\Entity\Category $category
     *
     * @return User
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

    public function getSettings() {
        try {
            $settings = unserialize($this->settings);
        } catch(\Exception $e) {
            $settings = array();
        }

        return is_array($settings) ? $settings : array();
    }

    public function setSettings(array $settings) {
        $this->settings = serialize($settings);
    }

    public function getSetting($key) {
        $settings = $this->getSettings();

        if(array_key_exists($key, $settings)) {
            return $settings[$key];
        } elseif($key == 'SHOW_PICTURES') {
            return "yes";
        } elseif($key == 'IS_PUBLIC') {
            return false;
        } elseif($key == 'HIDE_SIDEBAR') {
            return false;
        }

        return null;
    }

    public function setSetting($key, $value) {
        $settings = $this->getSettings();

        $settings[$key] = $value;

        $this->setSettings($settings);
    }
}
