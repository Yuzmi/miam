<?php

namespace MiamBundle\Services;

use MiamBundle\Entity\Category;
use MiamBundle\Entity\Feed;
use MiamBundle\Entity\Item;
use MiamBundle\Entity\ItemMark;
use MiamBundle\Entity\Subscription;
use MiamBundle\Entity\User;

class MarkManager extends MainService {
	protected $em;

	public function __construct($em) {
		$this->em = $em;
	}

	public function readItemForUser(Item $item, User $user) {
		$mark = $this->getRepo('ItemMark')->findOneBy(array(
            'item' => $item,
            'user' => $user
        ));
        if(!$mark) {
            $mark = new ItemMark();
            $mark->setItem($item);
            $mark->setUser($user);
        }

        $mark->setIsRead(true);
        $mark->setDateRead(new \DateTime("now"));

        $this->em->persist($mark);
    	$this->em->flush();
	}

	public function unreadItemForUser(Item $item, User $user) {
		$mark = $this->getRepo('ItemMark')->findOneBy(array(
            'item' => $item,
            'user' => $user
        ));
        if(!$mark) {
            $mark = new ItemMark();
            $mark->setItem($item);
            $mark->setUser($user);
        }

        $mark->setIsRead(false);
        $mark->setDateRead(new \DateTime("now"));

        $this->em->persist($mark);
    	$this->em->flush();
	}

	public function readSubscriptionForUser(Subscription $subscription, User $user) {
		$db = $this->em->getConnection();

		$stmt = $db->prepare('
			UPDATE item_mark im
			INNER JOIN item i ON i.id = im.item_id
			INNER JOIN feed f ON f.id = i.feed_id
			INNER JOIN subscription s ON s.feed_id = f.id
			SET im.is_read = TRUE
			WHERE s.id = :subscriptionId
			AND im.user_id = :userId AND im.is_read = FALSE
			;
		');
		$stmt->execute(array(
			'subscriptionId' => $subscription->getId(),
			'userId' => $user->getId()
		));

		$stmt = $db->prepare('
			INSERT INTO item_mark (item_id, user_id, is_read, is_starred)
				SELECT i.id, :userId, TRUE, FALSE
				FROM item i
				LEFT JOIN item_mark im ON im.item_id = i.id AND im.user_id = :userId
				INNER JOIN feed f ON f.id = i.feed_id
				INNER JOIN subscription s ON s.feed_id = f.id
				WHERE s.id = :subscriptionId
				AND im.id IS NULL
			;
		');
		$stmt->execute(array(
			'subscriptionId' => $subscription->getId(),
			'userId' => $user->getId()
		));
	}

	public function readCategoryForUser(Category $category, User $user) {
		$db = $this->em->getConnection();

		$stmt = $db->prepare('
			UPDATE item_mark im
			INNER JOIN item i ON i.id = im.item_id
			INNER JOIN feed f ON f.id = i.feed_id
			INNER JOIN subscription s ON s.feed_id = f.id
			INNER JOIN category c ON c.id = s.category_id
			SET im.is_read = TRUE
			WHERE c.left_position >= :catLeft AND c.right_position <= :catRight
			AND im.user_id = :userId AND im.is_read = FALSE 
			;
		');
		$stmt->execute(array(
			'catLeft' => $category->getLeftPosition(),
			'catRight' => $category->getRightPosition(),
			'userId' => $user->getId()
		));

		$stmt = $db->prepare('
			INSERT INTO item_mark (item_id, user_id, is_read, is_starred)
				SELECT i.id, :userId, TRUE, FALSE
				FROM item i
				LEFT JOIN item_mark im ON im.item_id = i.id AND im.user_id = :userId
				INNER JOIN feed f ON f.id = i.feed_id
				INNER JOIN subscription s ON s.feed_id = f.id
				INNER JOIN category c ON c.id = s.category_id
				WHERE c.left_position >= :catLeft AND c.right_position <= :catRight
				AND im.id IS NULL
			;
		');
		$stmt->execute(array(
			'userId' => $user->getId(),
			'catLeft' => $category->getLeftPosition(),
			'catRight' => $category->getRightPosition()
		));
	}

	public function readUserForUser(User $subscriber, User $reader) {
		$db = $this->em->getConnection();

		$stmt = $db->prepare('
			UPDATE item_mark im
			INNER JOIN item i ON i.id = im.item_id
			INNER JOIN feed f ON f.id = i.feed_id
			INNER JOIN subscription s ON s.feed_id = f.id
			SET im.is_read = TRUE
			WHERE s.user_id = :subscriberId
			AND im.user_id = :userId AND im.is_read = FALSE 
			;
		');
		$stmt->execute(array(
			'subscriberId' => $subscriber->getId(),
			'userId' => $reader->getId()
		));

		$stmt = $db->prepare('
			INSERT INTO item_mark (item_id, user_id, is_read, is_starred)
				SELECT i.id, :userId, TRUE, FALSE
				FROM item i
				LEFT JOIN item_mark im ON im.item_id = i.id AND im.user_id = :userId
				INNER JOIN feed f ON f.id = i.feed_id
				INNER JOIN subscription s ON s.feed_id = f.id
				WHERE s.user_id = :subscriberId
				AND im.id IS NULL
			;
		');
		$stmt->execute(array(
			'subscriberId' => $subscriber->getId(),
			'userId' => $reader->getId()
		));
	}

	public function starItemForUser(Item $item, User $user) {
		$mark = $this->getRepo('ItemMark')->findOneBy(array(
            'item' => $item,
            'user' => $user
        ));
        if(!$mark) {
            $mark = new ItemMark();
            $mark->setItem($item);
            $mark->setUser($user);
        }

        if(!$mark->getIsStarred()) {
	        $mark->setIsStarred(true);

	        $this->em->persist($mark);
        	$this->em->flush();
	    }
	}

	public function unstarItemForUser(Item $item, User $user) {
		$mark = $this->getRepo('ItemMark')->findOneBy(array(
            'item' => $item,
            'user' => $user
        ));
        if($mark) {
            $mark->setIsStarred(false);

	        $this->em->persist($mark);
        	$this->em->flush();
        }
	}

	public function getUnreadCounts(User $subscriber, User $reader) {
		$unreadCounts = array();

        $result = $this->getRepo('Item')->createQueryBuilder('i')
            ->select('s.id AS subscription_id, COUNT(i.id) AS unread_count')
            ->innerJoin('i.feed', 'f')
            ->innerJoin('f.subscriptions', 's')
            ->leftJoin('i.marks', 'im', 'with', 'im.user = :reader')
            ->where('s.user = :subscriber')
            ->andWhere('im.id IS NULL OR im.isRead = FALSE')
            ->groupBy('s.id')
            ->setParameters(array(
            	'subscriber' => $subscriber,
            	'reader' => $reader
            ))
            ->getQuery()->getResult();

        foreach($result as $r) {
            $unreadCounts[$r['subscription_id']] = $r['unread_count'];
        }
        
        return $unreadCounts;
	}
}
