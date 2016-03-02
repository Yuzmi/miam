<?php

namespace MiamBundle\Controller;

use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use MiamBundle\Entity\Category;
use MiamBundle\Entity\User;

class UserController extends MainController
{
	public function showAction($userId) {
        $subscriber = $this->getRepo("User")->find($userId);
        if(!$subscriber || (!$subscriber->getIsPublic() && (!$this->isLogged() || $subscriber->getId() != $this->getUser()->getId()))) 
        {
            return $this->redirectToRoute("index");
        }

        $reader = null;
        if($this->isLogged()) {
            $reader = $this->getUser();
        }

        $items = $this->get('item_manager')->getItems(array(
            'reader' => $reader,
            'subscriber' => $subscriber
        ));

        $dataItems = $this->get('item_manager')->getDataForItems($items, array(
            'reader' => $reader,
            'subscriber' => $subscriber
        ));

        $tree = $this->getTreeForUser($subscriber);

        $unreadCounts = null;
        if($reader) {
            $unreadCounts = $this->get('mark_manager')->getUnreadCounts($subscriber, $reader);
        }

    	return $this->render('MiamBundle:User:show.html.twig', array(
            'items' => $items,
            'dataItems' => $dataItems,
            'tree' => $tree,
            'unreadCounts' => $unreadCounts,
            'subscriber' => $subscriber,
            'reader' => $reader
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
}
