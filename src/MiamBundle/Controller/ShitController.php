<?php

namespace MiamBundle\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

use MiamBundle\Entity\Category;
use MiamBundle\Entity\User;

// Yeah, it's a shitty name but i'll change it when i find something i like

class ShitController extends MainController
{
	public function indexAction($userId) {
        $subscriber = $this->getRepo("User")->find($userId);
        if(!$subscriber || (!$subscriber->getIsPublic() && (!$this->isLogged() || $subscriber->getId() != $this->getUser()->getId()))) 
        {
            return $this->redirectToRoute("index");
        }

        $tree = $this->getTreeForUser($subscriber);

        $marker = null;
        $unreadCounts = null;

        if($this->isLogged() && $subscriber->getId() == $this->getUser()->getId()) {
            $marker = $this->getUser();
            $unreadCounts = $this->get('mark_manager')->getUnreadCounts($subscriber, $marker);
        }

        $items = $this->get('item_manager')->getItems(array(
            'marker' => $marker,
            'subscriber' => $subscriber
        ));

        $dataItems = $this->get('item_manager')->getDataForItems($items, array(
            'marker' => $marker,
            'subscriber' => $subscriber
        ));

    	return $this->render('MiamBundle:Shit:index.html.twig', array(
            'items' => $items,
            'dataItems' => $dataItems,
            'tree' => $tree,
            'unreadCounts' => $unreadCounts,
            'subscriber' => $subscriber,
            'markable' => $marker ? true : false
        ));
	}

    private function getTreeForUser(User $user) {
        $tree = array(
            'categories' => array(),
            'subscriptions' => array()
        );

        $categories = $this->getRepo('Category')->createQueryBuilder('c')
            ->leftJoin('c.parent', 'p')->addSelect('p')
            ->where('c.user = :user')->setParameter('user', $user)
            ->orderBy('c.name', 'ASC')
            ->getQuery()->getResult();

        $subscriptions = $this->getRepo('Subscription')->createQueryBuilder('s')
            ->innerJoin('s.feed', 'f')->addSelect('f')
            ->leftJoin('s.categories', 'c')->addSelect('c')
            ->where('s.user = :user')->setParameter('user', $user)
            ->orderBy('s.name', 'ASC')
            ->getQuery()->getResult();

        $data = array(
            'categories' => $categories,
            'subscriptions' => $subscriptions
        );

        foreach($categories as $c) {
            if(!$c->getParent()) {
                $tree['categories'][] = $this->getCategoryTree($c, $data);
            }
        }

        foreach($subscriptions as $s) {
            $categories = $s->getCategories();
            if(count($categories) == 0) {
                $tree['subscriptions'][] = $s;
            }
        }

        return $tree;
    }

    private function getCategoryTree(Category $category, &$data) {
        $tree = array(
            'category' => $category,
            'categories' => array(),
            'subscriptions' => array()
        );

        foreach($data['categories'] as $c) {
            if($c->getParent() && $c->getParent()->getId() == $category->getId()) {
                $tree['categories'][] = $this->getCategoryTree($c, $data);
            }
        }

        foreach($data['subscriptions'] as $s) {
            foreach($s->getCategories() as $c) {
                if($c->getId() == $category->getId()) {
                    $tree['subscriptions'][] = $s;
                    break;
                }
            }
        }

        return $tree;
    }

    public function ajaxGetItemsAction(Request $request) {
        $success = true;
        $htmlItems = null;

        $subscriber = $this->getRepo('User')->find($request->get('subscriber'));
        if(
            !$subscriber || (
                !$subscriber->getIsPublic() && (
                    !$this->isLogged() || $subscriber->getId() != $this->getUser()->getId()
                )
            ) 
        ) {
            $subscriber = null;
            $success = false;
        }

        if($request->isXmlHttpRequest() && $success) {
            $marker = null;
            if($this->isLogged() && $subscriber->getId() == $this->getUser()->getId()) {
                $marker = $this->getUser();
            }
            
            $feedId = intval($request->get('feed'));
            $feed = null;
            if($feedId > 0) {
                $feed = $this->getRepo('Feed')->find($feedId);
            }

            $categoryId = intval($request->get('category'));
            $category = null;
            if($categoryId > 0) {
                $category = $this->getRepo('Category')->findOneBy(array(
                    'id' => $categoryId,
                    'user' => $subscriber
                ));
            }
            
            $createdAfter = \DateTime::createFromFormat("Y-m-d H:i:s", $request->get("createdAfter"));

            $page = intval($request->get('page'));
            if($page <= 0) {
                $page = 1;
            }

            $items = $this->get('item_manager')->getItems(array(
                'createdAfter' => $createdAfter,
                'category' => $category,
                'feed' => $feed,
                'marker' => $marker,
                'page' => $page,
                'subscriber' => $subscriber,
                'type' => $request->get('type')
            ));

            $dataItems = $this->get('item_manager')->getDataForItems($items, array(
                'subscriber' => $subscriber,
                'marker' => $marker
            ));

            $htmlItems = $this->renderView('MiamBundle:Default:items.html.twig', array(
                'items' => $items,
                'dataItems' => $dataItems,
                'markable' => $marker ? true : false,
                'loadMore' => $request->get("loadMore")
            ));
        }

        return new JsonResponse(array(
            'success' => $success,
            'items' => $htmlItems,
            'page' => $page,
            'dateRefresh' => date_format(new \DateTime("now"), "Y-m-d H:i:s")
        ));
    }

    public function ajaxReadItemAction(Request $request, $id) {
        $success = false;

        if($request->isXmlHttpRequest() && $this->isLogged()) {
            $item = $this->getRepo("Item")->find($id);
            if($item) {
                $this->get("mark_manager")->readItemForUser($item, $this->getUser());
                $success = true;
            }
        }

        return new JsonResponse(array(
            'success' => $success
        ));
    }

    public function ajaxReadFeedItemsAction(Request $request, $id) {
        $success = false;

        if($request->isXmlHttpRequest() && $this->isLogged()) {
            $feed = $this->getRepo('Feed')->find($id);
            if($feed) {
                $this->get('mark_manager')->readFeedForUser($feed, $this->getUser());
                $success = true;
            }
        }

        return new JsonResponse(array(
            'success' => $success
        ));
    }

    public function ajaxReadCategoryItemsAction(Request $request, $id) {
        $success = false;

        if($request->isXmlHttpRequest() && $this->isLogged()) {
            $category = $this->getRepo('Category')->findOneBy(array(
                'id' => $id,
                'user' => $this->getUser()
            ));
            if($category) {
                $this->get('mark_manager')->readCategoryForUser($category, $this->getUser());
                $success = true;
            }
        }

        return new JsonResponse(array(
            'success' => $success
        ));
    }

    public function ajaxReadUserItemsAction(Request $request, $id) {
        $success = false;

        if($request->isXmlHttpRequest() && $this->isLogged()) {
            $user = $this->getRepo("User")->find($id);
            if($user && ($user->getIsPublic() || $user->getId() == $this->getUser()->getId())) {
                $this->get('mark_manager')->readUserForUser($user, $this->getUser());
                $success = true;
            }
        }

        return new JsonResponse(array(
            'success' => $success
        ));
    }

    public function ajaxStarItemAction(Request $request, $id) {
        $success = false;

        if($request->isXmlHttpRequest() && $this->isLogged()) {
            $item = $this->getRepo("Item")->find($id);
            if($item) {
                $this->get("mark_manager")->starItemForUser($item, $this->getUser());

                $success = true;
            }
        }

        return new JsonResponse(array(
            'success' => $success
        ));
    }

    public function ajaxUnstarItemAction(Request $request, $id) {
        $success = false;

        if($request->isXmlHttpRequest() && $this->isLogged()) {
            $item = $this->getRepo("Item")->find($id);
            if($item) {
                $this->get("mark_manager")->unstarItemForUser($item, $this->getUser());

                $success = true;
            }
        }

        return new JsonResponse(array(
            'success' => $success
        ));
    }

    public function ajaxGetUnreadCountsAction(Request $request) {
        $success = false;

        $unreadCounts = null;
        if($request->isXmlHttpRequest() && $this->isLogged()) {
            $uc = $this->get('mark_manager')->getUnreadCounts($this->getUser(), $this->getUser());

            foreach($uc as $key => $value) {
                $unreadCounts[] = array(
                    'feedId' => $key,
                    'count' => $value
                );
            }

            $success = true;
        }

        return new JsonResponse(array(
            'success' => $success,
            'unreadCounts' => $unreadCounts
        ));
    }
}
