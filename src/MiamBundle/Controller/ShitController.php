<?php

namespace MiamBundle\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

use MiamBundle\Entity\Category;
use MiamBundle\Entity\User;

// Yeah, it's a shitty name but i'll change it when i find something i like

class ShitController extends MainController
{
	public function indexAction() {
        if(!$this->isLogged()) {
            return $this->redirectToRoute("index");
        }

        $tree = $this->getTreeForUser($this->getUser());

        $unreadCounts = $this->get('mark_manager')->getUnreadCounts($this->getUser(), $this->getUser());
        $starredCount = $this->getRepo("ItemMark")->countStarredAndSubscribedForUser($this->getUser());

        $items = $this->getRepo("Item")->getItems(array(
            'marker' => $this->getUser(),
            'subscriber' => $this->getUser(),
            'count' => 40
        ));

        $dataItems = $this->getRepo("Item")->getDataForItems($items, array(
            'marker' => $this->getUser(),
            'subscriber' => $this->getUser()
        ));

        $itemOptions = array('loadMore', 'markable');

    	return $this->render('MiamBundle:Shit:index.html.twig', array(
            'items' => $items,
            'dataItems' => $dataItems,
            'itemOptions' => $itemOptions,
            'countMaxItems' => 40,
            'tree' => $tree,
            'unreadCounts' => $unreadCounts,
            'starredCount' => $starredCount
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
            ->leftJoin('s.category', 'c')->addSelect('c')
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
            if(!$s->getCategory()) {
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
            if($s->getCategory() && $s->getCategory()->getId() == $category->getId()) {
                $tree['subscriptions'][] = $s;
            }
        }

        return $tree;
    }

    public function ajaxGetItemsAction(Request $request) {
        $success = false;
        $countMaxItems = 40;
        $htmlItems = null;
        $items = null;
        $page = null;

        if($request->isXmlHttpRequest()) {
            $options = array(
                'subscriber' => $this->getUser(),
                'marker' => $this->getUser()
            );

            $type = $request->get('type');
            if($type == 'subscription') {
                $subscription = $this->getRepo('Subscription')->find($request->get('subscription'));
                if($subscription) {
                    $options['subscription'] = $subscription;
                }
            } elseif($type == 'category') {
                $category = $this->getRepo('Category')->find($request->get('category'));
                if($category) {
                    $options['category'] = $category;
                }
            } else {
                $options['type'] = $type;
            }
            
            $createdAfter = date_create_from_format(DATE_ATOM, $request->get("created_after"));
            if($createdAfter !== false) {
                $options['createdAfter'] = $createdAfter;
                $countMaxItems = 100;
            }

            $options['count'] = $countMaxItems;
            
            $page = intval($request->get('page'));
            if($page < 1) {
                $page = 1;
            }
            $options['page'] = $page;

            $offset = intval($request->get('offset'));
            if($offset > 0) {
                $options['offset'] = $offset;
            }

            $items = $this->getRepo("Item")->getItems($options);

            $dataItems = $this->getRepo("Item")->getDataForItems($items, array(
                'subscriber' => $this->getUser(),
                'marker' => $this->getUser()
            ));

            $itemOptions = array('markable');
            if($request->get("load_more")) $itemOptions[] = 'loadMore';

            $htmlItems = $this->renderView('MiamBundle:Default:item_list.html.twig', array(
                'items' => $items,
                'dataItems' => $dataItems,
                'itemOptions' => $itemOptions,
                'countMaxItems' => $countMaxItems
            ));

            $success = true;
        }

        return new JsonResponse(array(
            'success' => $success,
            'count' => count($items),
            'page' => $page,
            'dateRefresh' => date_format(new \DateTime(), DATE_ATOM),
            'html' => $htmlItems
        ));
    }

    public function ajaxGetItemAction(Request $request) {
        $success = false;
        $html = null;

        if($request->isXmlHttpRequest() && $this->isLogged()) {
            $item = $this->getRepo("Item")->find($request->get("item"));
            if($item) {
                $dataItem = $this->getRepo("Item")->getDataForItem($item);

                $html = $this->renderView('MiamBundle:Default:item_full.html.twig', array(
                    'item' => $item,
                    'dataItem' => $dataItem
                ));

                $success = true;
            }
        }

        return new JsonResponse(array(
            'success' => $success,
            'html' => $html
        ));
    }

    public function ajaxReadItemAction(Request $request) {
        $success = false;

        if($request->isXmlHttpRequest() && $this->isLogged()) {
            $item = $this->getRepo("Item")->find($request->get("item"));
            if($item) {
                $this->get("mark_manager")->readItemForUser($item, $this->getUser());
                $success = true;
            }
        }

        return new JsonResponse(array(
            'success' => $success,
            'item' => isset($item) ? $item->getId() : null
        ));
    }

    public function ajaxUnreadItemAction(Request $request) {
        $success = false;

        if($request->isXmlHttpRequest() && $this->isLogged()) {
            $item = $this->getRepo("Item")->find($request->get("item"));
            if($item) {
                $this->get("mark_manager")->unreadItemForUser($item, $this->getUser());
                $success = true;
            }
        }

        return new JsonResponse(array(
            'success' => $success,
            'item' => isset($item) ? $item->getId() : null
        ));
    }

    public function ajaxReadSubscriptionAction(Request $request) {
        $success = false;

        if($request->isXmlHttpRequest() && $this->isLogged()) {
            $subscription = $this->getRepo('Subscription')->find($request->get("subscription"));
            if($subscription) {
                $this->get('mark_manager')->readSubscriptionForUser($subscription, $this->getUser());
                $success = true;
            }
        }

        return new JsonResponse(array(
            'success' => $success
        ));
    }

    public function ajaxReadCategoryAction(Request $request) {
        $success = false;

        if($request->isXmlHttpRequest() && $this->isLogged()) {
            $category = $this->getRepo('Category')->findOneBy(array(
                'id' => $request->get("category"),
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

    public function ajaxReadAllAction(Request $request) {
        $success = false;

        if($request->isXmlHttpRequest() && $this->isLogged()) {
            $this->get('mark_manager')->readUserForUser($this->getUser(), $this->getUser());
            $success = true;
        }

        return new JsonResponse(array(
            'success' => $success
        ));
    }

    public function ajaxStarItemAction(Request $request) {
        $success = false;

        if($request->isXmlHttpRequest() && $this->isLogged()) {
            $item = $this->getRepo("Item")->find($request->get("item"));
            if($item) {
                $this->get("mark_manager")->starItemForUser($item, $this->getUser());

                $success = true;
            }
        }

        return new JsonResponse(array(
            'success' => $success
        ));
    }

    public function ajaxUnstarItemAction(Request $request) {
        $success = false;

        if($request->isXmlHttpRequest() && $this->isLogged()) {
            $item = $this->getRepo("Item")->find($request->get("item"));
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
                    'subscriptionId' => $key,
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
