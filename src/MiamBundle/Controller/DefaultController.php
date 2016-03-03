<?php

namespace MiamBundle\Controller;

use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

use MiamBundle\Entity\User;

class DefaultController extends MainController
{
	public function indexAction() {
        if($this->isLogged()) {
            return $this->forward('MiamBundle:User:show', array(
                'userId' => $this->getUser()->getId()
            ));
        }

    	return $this->redirectToRoute('catalog');
	}

    public function ajaxGetItemsAction(Request $request) {
        $success = true;
        $htmlItems = null;

        $subscriberId = intval($request->get('subscriber'));
        $subscriber = null;
        if($subscriberId > 0) {
            $subscriber = $this->getRepo('User')->find($subscriberId);
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
        }

        $marker = null;
        if($this->isLogged() && $request->get("markable")) {
            $marker = $this->getUser();
        }

        $feedId = intval($request->get('feed'));
        $feed = null;
        if($feedId > 0) {
            $feed = $this->getRepo('Feed')->find($feedId);
        }

        $categoryId = intval($request->get('category'));
        $category = null;
        if($subscriber && $categoryId > 0) {
            $category = $this->getRepo('Category')->findOneBy(array(
                'id' => $categoryId,
                'user' => $subscriber
            ));
        }

        if($success) {
            $items = $this->get('item_manager')->getItems(array(
                'category' => $category,
                'feed' => $feed,
                'marker' => $marker,
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
                'marker' => $marker
            ));
        }

        return new JsonResponse(array(
            'success' => $success,
            'items' => $htmlItems
        ));
    }

    public function ajaxGetUnreadAction(Request $request) {
        $success = false;

        $subscriber = $this->getRepo('User')->find(intval($request->get('subscriber')));
        if($subscriber && !$subscriber->getIsPublic() && (!$this->isLogged() || $subscriber->getId() != $this->getUser()->getId())) {
            $subscriber = null;
        }

        $unreadCounts = null;
        if($this->isLogged() && $subscriber) {
            $uc = $this->get('mark_manager')->getUnreadCounts($subscriber, $this->getUser());

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

    public function ajaxReadItemsAction(Request $request) {
        $success = false;

        if($this->isLogged()) {
            $subscriber = $this->getRepo('User')->find($request->get('subscriber'));
            if(!$subscriber || (!$subscriber->getIsPublic() && $subscriber != $this->getUser())) {
                $subscriber = null;
            }

            $type = $request->get('type');
            if($type == "item") {
                $item = $this->getRepo('Item')->find($request->get('item'));
                if($item) {
                    $this->get('mark_manager')->readItemForUser($item, $this->getUser());
                    $success = true;
                }
            } elseif($type == "feed") {
                $feed = $this->getRepo('Feed')->find($request->get('feed'));
                if($feed) {
                    $this->get('mark_manager')->readFeedForUser($feed, $this->getUser());
                    $success = true;
                }
            } elseif($type == "category" && $subscriber) {
                $category = $this->getRepo('Category')->findOneBy(array(
                    'id' => $request->get('category'),
                    'user' => $subscriber
                ));
                if($category) {
                    $this->get('mark_manager')->readCategoryForUser($category, $this->getUser());
                    $success = true;
                }
            } elseif($type == "all" && $subscriber) {
                $this->get('mark_manager')->readUserForUser($subscriber, $this->getUser());
                $success = true;
            }
        }

        return new JsonResponse(array(
            'success' => $success
        ));
    }

    public function ajaxStarItemAction(Request $request, $id) {
        $success = false;

        if($this->isLogged()) {
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

        if($this->isLogged()) {
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
}
