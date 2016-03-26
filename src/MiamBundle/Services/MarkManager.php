<?php

namespace MiamBundle\Services;

use MiamBundle\Entity\Category;
use MiamBundle\Entity\Feed;
use MiamBundle\Entity\FeedMark;
use MiamBundle\Entity\Item;
use MiamBundle\Entity\ItemMark;
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

        if(!$mark->getIsRead()) {
	        $mark->setIsRead(true);
	        $mark->setDateRead(new \DateTime("now"));

	        $this->em->persist($mark);
        	$this->em->flush();
	    }
	}

	public function readFeedForUser(Feed $feed, User $user) {
		$mark = $this->getRepo('FeedMark')->findOneBy(array(
            'feed' => $feed,
            'user' => $user
        ));
        if(!$mark) {
            $mark = new FeedMark();
            $mark->setFeed($feed);
            $mark->setUser($user);
        }

        $mark->setDateRead(new \DateTime("now"));
        $this->em->persist($mark);
        $this->em->flush();
	}

	public function readCategoryForUser(Category $category, User $user) {
		$subscriptions = $this->getRepo('Subscription')->findForCategory($category, true);

		if(count($subscriptions) > 0) {
			foreach($subscriptions as $s) {
				$feed = $s->getFeed();

				$mark = $this->getRepo('FeedMark')->findOneBy(array(
					'feed' => $feed,
					'user' => $user
				));
				if(!$mark) {
					$mark = new FeedMark();
					$mark->setFeed($feed);
					$mark->setUser($user);
				}

				$mark->setDateRead(new \DateTime("now"));
				$this->em->persist($mark);
			}

			$this->em->flush();
		}
	}

	public function readUserForUser(User $subscriber, User $reader) {
		$subscriptions = $this->getRepo('Subscription')->findByUser($subscriber);
		if(count($subscriptions) > 0) {
			foreach($subscriptions as $s) {
				$feed = $s->getFeed();

				$mark = $this->getRepo('FeedMark')->findOneBy(array(
					'feed' => $feed,
					'user' => $reader
				));
				if(!$mark) {
					$mark = new FeedMark();
					$mark->setFeed($feed);
					$mark->setUser($reader);
				}

				$mark->setDateRead(new \DateTime("now"));
				$this->em->persist($mark);
			}

			$this->em->flush();
		}
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
            ->select('f.id AS feed_id, COUNT(i.id) AS unread_count')
            ->innerJoin('i.feed', 'f')
            ->innerJoin('f.subscriptions', 's')
            ->leftJoin('i.marks', 'im', 'with', 'im.user = :reader')
            ->leftJoin('f.marks', 'fm', 'with', 'fm.user = :reader')
            ->where('s.user = :subscriber')
            ->andWhere('im.id IS NULL OR im.isRead = FALSE')
            ->andWhere('fm.id IS NULL OR fm.dateRead < i.dateCreated')
            ->groupBy('f.id')
            ->setParameters(array(
            	'subscriber' => $subscriber,
            	'reader' => $reader
            ))
            ->getQuery()->getResult();

        foreach($result as $r) {
            $unreadCounts[$r['feed_id']] = $r['unread_count'];
        }
        
        return $unreadCounts;
	}
}
