<?php

namespace MiamBundle\Controller;

use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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

        $readerId = intval($request->get('reader'));
        $reader = null;
        if($readerId > 0) {
            $reader = $this->getRepo('User')->find($readerId);
            if($reader && (!$this->isLogged() || $reader->getId() != $this->getUser()->getId())) {
                $reader = null;
            }
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
                'reader' => $reader,
                'subscriber' => $subscriber,
                'type' => $request->get('type')
            ));

            $dataItems = $this->get('item_manager')->getDataForItems($items, array(
                'subscriber' => $subscriber,
                'reader' => $reader
            ));

            $htmlItems = $this->renderView('MiamBundle:Default:items.html.twig', array(
                'items' => $items,
                'dataItems' => $dataItems
            ));
        }

        return new Response(json_encode(array(
            'success' => $success,
            'items' => $htmlItems
        )));
    }

    public function ajaxReadItemsAction(Request $request) {
        $success = false;

        $subscriber = $this->getRepo('User')->find(intval($request->get('subscriber')));
        if($subscriber && !$subscriber->getIsPublic() && (!$this->isLogged() || $subscriber->getId() != $this->getUser()->getId())) {
            $subscriber = null;
        }

        $reader = $this->getRepo('User')->find(intval($request->get('reader')));
        if(!$reader || !$this->isLogged() || $reader->getId() != $this->getUser()->getId()) {
            $reader = null;
        }

        if($reader) {
            $type = $request->get('type');
            if($type == "item") {
                $itemId = intval($request->get('item'));
                if($itemId > 0) {
                    $item = $this->getRepo('Item')->find($itemId);
                    if($item) {
                        $this->get('mark_manager')->readItemForUser($item, $reader);

                        $success = true;
                    }
                }
            } elseif($type == "feed") {
                $feedId = intval($request->get('feed'));
                if($feedId > 0) {
                    $feed = $this->getRepo('Feed')->find($feedId);
                    if($feed) {
                        $this->get('mark_manager')->readFeedForUser($feed, $reader);

                        $success = true;
                    }
                }
            } elseif($type == "category" && $subscriber) {
                $categoryId = intval($request->get('category'));
                if($categoryId > 0) {
                    $category = $this->getRepo('Category')->find($categoryId);
                    if($category && $category->getUser()->getId() == $subscriber->getId()) {
                        $this->get('mark_manager')->readCategoryForUser($category, $reader);

                        $success = true;
                    }
                }
            } elseif($type == "all" && $subscriber) {
                $this->get('mark_manager')->readUserForUser($subscriber, $reader);

                $success = true;
            }
        }

        return new Response(json_encode(array(
            'success' => $success
        )));
    }

    public function ajaxGetUnreadAction(Request $request) {
        $success = false;

        $subscriber = $this->getRepo('User')->find(intval($request->get('subscriber')));
        if($subscriber && !$subscriber->getIsPublic() && (!$this->isLogged() || $subscriber->getId() != $this->getUser()->getId())) {
            $subscriber = null;
        }

        $reader = $this->getRepo('User')->find(intval($request->get('reader')));
        if(!$reader || !$this->isLogged() || $reader->getId() != $this->getUser()->getId()) {
            $reader = null;
        }

        $unreadCounts = null;
        if($subscriber) {
            $uc = $this->get('mark_manager')->getUnreadCounts($subscriber, $reader);

            foreach($uc as $key => $value) {
                $unreadCounts[] = array(
                    'feedId' => $key,
                    'count' => $value
                );
            }

            $success = true;
        }

        return new Response(json_encode(array(
            'success' => $success,
            'unreadCounts' => $unreadCounts
        )));
    }
}
