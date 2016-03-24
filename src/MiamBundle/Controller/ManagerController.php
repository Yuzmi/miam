<?php

namespace MiamBundle\Controller;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use MiamBundle\Entity\Category;
use MiamBundle\Entity\Feed;
use MiamBundle\Entity\Subscription;
use MiamBundle\Entity\User;

class ManagerController extends MainController
{
	public function indexAction() {
        $categories = $this->getRepo('Category')->findForUserWithMore($this->getUser());
        $subscriptions = $this->getRepo('Subscription')->findForUserWithMore($this->getUser());

		return $this->render('MiamBundle:Manager:index.html.twig', array(
            'categories' => $categories,
            'subscriptions' => $subscriptions
        ));
	}

    public function ajaxPopupNewCategoryAction(Request $request) {
        $response = array(
            "success" => false
        );

        if($request->isXmlHttpRequest() && $this->isLogged()) {
            $categories = $this->getRepo("Category")->findForUser($this->getUser(), 'leftPosition');

            $html = $this->renderView("MiamBundle:Manager:popup_category_new.html.twig", array(
                'categories' => $categories
            ));

            $response["success"] = true;
            $response["html"] = $html;
        }

        return new JsonResponse($response);
    }

    public function createCategoryAction(Request $request) {
        if($this->isTokenValid('manager_category_create', $request->get('csrf_token'))) {
            $name = trim($request->get("name"));
            if(!empty($name)) {
                $category = new Category();
                $category->setUser($this->getUser());
                $category->setName($name);

                $parentId = intval($request->get('parent'));
                if($parentId > 0) {
                    $parent = $this->getRepo("Category")->findOneBy(array(
                        'id' => $parentId,
                        'user' => $this->getUser()
                    ));
                    if($parent) {
                        $category->setParent($category);
                    }
                }

                $em = $this->getEm();
                $em->persist($category);
                $em->flush();

                $this->get('category_manager')->updateForUser($this->getUser());

                $this->addFm("Category created", "success");
            } else {
                $this->addFm("Error", "error");
            }
        } else {
            $this->addFm("Invalid token", "error");
        }

        return $this->redirectToRoute("manager", array("tab" => "catsubs"));
    }

    public function ajaxPopupEditCategoryAction(Request $request, $id) {
        $response = array(
            "success" => false
        );

        if($request->isXmlHttpRequest() && $this->isLogged()) {
            $category = $this->getRepo("Category")->findOneForUserWithMore($id, $this->getUser());
            if($category) {
                $categories = $this->getRepo("Category")->findForUserWithMore($this->getUser());
                $subscriptions = $this->getRepo("Subscription")->findForUserWithMore($this->getUser());

                $html = $this->renderView("MiamBundle:Manager:popup_category_edit.html.twig", array(
                    'category' => $category,
                    'categories' => $categories,
                    'subscriptions' => $subscriptions
                ));

                $response["success"] = true;
                $response["html"] = $html;
            }
        }

        return new JsonResponse($response);
    }

    public function updateCategoryAction(request $request, $id) {
        if($this->isTokenValid('manager_category_update', $request->get('csrf_token'))) {
            $category = $this->getRepo("Category")->findOneBy(array(
                'id' => $id,
                'user' => $this->getUser()
            ));

            $name = trim($request->get("name"));

            $parentId = intval($request->get("parent"));
            $parent = null;
            if($parentId > 0) {
                $parent = $this->getRepo("Category")->findOneBy(array(
                    'id' => $parentId,
                    'user' => $this->getUser()
                ));
                if($parent) {
                    $category->setParent($category);
                }
            }

            $subscriptionIds = $request->get("subscriptions");
            $subscriptions = array();
            if(is_array($subscriptionIds) && !empty($subscriptionIds)) {
                $subscriptions = $this->getRepo("Subscription")->createQueryBuilder("s")
                    ->where("s.user = :user")->setParameter("user", $this->getUser())
                    ->andWhere("s.id IN (:ids)")->setParameter("ids", $subscriptionIds)
                    ->getQuery()->getResult();
            }
            
            if($category && !empty($name)) {
                $category->setName($name);

                if($parent) {
                    $category->setParent($parent);
                } else {
                    $category->setParent(null);
                }
                
                foreach($category->getSubscriptions() as $s) {
                    if(!in_array($s->getId(), $subscriptionIds)) {
                        $category->removeSubscription($s);
                    }
                }
                
                foreach($subscriptions as $s) {
                    if(!$category->getSubscriptions()->contains($s)) {
                        $category->addSubscription($s);
                    }
                }
                
                $em = $this->getEm();
                $em->persist($category);
                $em->flush();

                $this->get('category_manager')->updateForUser($this->getUser());

                $this->addFm("Category updated", "success");
            } else {
                $this->addFm("Error", "error");
            }
        } else {
            $this->addFm("Invalid token", "error");
        }
        
        return $this->redirectToRoute("manager", array("tab" => "catsubs"));
    }

    public function ajaxPopupDeleteCategoryAction(Request $request, $id) {
        $response = array(
            "success" => false
        );

        if($request->isXmlHttpRequest() && $this->isLogged()) {
            $category = $this->getRepo("Category")->findOneBy(array(
                'id' => $id,
                'user' => $this->getUser()
            ));
            if($category) {
                $html = $this->renderView("MiamBundle:Manager:popup_category_delete.html.twig", array(
                    'category' => $category
                ));

                $response["success"] = true;
                $response["html"] = $html;
            }
        }

        return new JsonResponse($response);
    }

    public function deleteCategoryAction(Request $request, $id) {
        if($this->isTokenValid('manager_category_delete', $request->get('csrf_token'))) {
            $category = $this->getRepo("Category")->findOneBy(array(
                'id' => $id,
                'user' => $this->getUser()
            ));
            if($category) {
                $em = $this->getEm();
                $em->remove($category);
                $em->flush();

                $this->get('category_manager')->updateForUser($this->getUser());

                $this->addFm("Category deleted", "success");
            } else {
                $this->addFm("Error", "error");
            }
        } else {
            $this->addFm("Invalid token", "error");
        }

        return $this->redirectToRoute("manager", array("tab" => "catsubs"));
    }

    public function ajaxPopupNewSubscriptionAction(Request $request) {
        $response = array(
            "success" => false
        );

        if($request->isXmlHttpRequest() && $this->isLogged()) {
            $html = $this->renderView("MiamBundle:Manager:popup_subscription_new.html.twig");

            $response["success"] = true;
            $response["html"] = $html;
        }

        return new JsonResponse($response);
    }

    public function createSubscriptionAction(Request $request) {
        if($this->isTokenValid('manager_subscription_create', $request->get('csrf_token'))) {
            $feed = $this->get('feed_manager')->getFeedForUrl($request->get('url'));
            if($feed) {
                $subscription = $this->getRepo('Subscription')->findOneBy(array(
                    'user' => $this->getUser(),
                    'feed' => $feed
                ));
                if(!$subscription) {
                    $subscription = new Subscription();
                    $subscription->setUser($this->getUser());
                    $subscription->setFeed($feed);

                    $name = trim($request->get("name"));
                    if(!empty($name)) {
                        $subscription->setName($name);
                    } else {
                        $subscription->setName($feed->getName());
                    }

                    $em = $this->getEm();
                    $em->persist($subscription);
                    $em->flush();

                    $this->addFm("Feed subscribed", "success");
                } else {
                    $this->addFm("Feed already subscribed", "error");
                }
            } else {
                $this->addFm("Error", "error");
            }
        } else {
            $this->addFm("Invalid token", "error");
        }

        return $this->redirectToRoute("manager", array("tab" => "catsubs"));
    }

    public function ajaxPopupEditSubscriptionAction(Request $request, $id) {
        $response = array(
            "success" => false
        );

        if($request->isXmlHttpRequest() && $this->isLogged()) {
            $subscription = $this->getRepo("Subscription")->findOneForUserWithMore($id, $this->getUser());
            if($subscription) {
                $categories = $this->getRepo("Category")->findForUser($this->getUser(), "leftPosition");

                $html = $this->renderView("MiamBundle:Manager:popup_subscription_edit.html.twig", array(
                    "subscription" => $subscription,
                    "categories" => $categories
                ));

                $response["success"] = true;
                $response["html"] = $html;
            }
        }

        return new JsonResponse($response);
    }

    public function updateSubscriptionAction(Request $request, $id) {
        if($this->isTokenValid('manager_subscription_update', $request->get('csrf_token'))) {
            $subscription = $this->getRepo("Subscription")->findOneBy(array(
                'id' => $id,
                'user' => $this->getUser()
            ));
            if($subscription) {
                $em = $this->getEm();

                $name = trim($request->get("name"));
                if(!empty($name)) {
                    $subscription->setName($name);
                }

                $url = $request->get("url");
                $feed = $this->get("feed_manager")->getFeedForUrl($url);
                if($feed && $feed->getId() != $subscription->getFeed()->getId()) {
                    $feedSubscription = $this->getRepo("Subscription")->findOneBy(array(
                        'feed' => $feed,
                        'user' => $this->getUser()
                    ));
                    if(!$feedSubscription) {
                        $subscription->setFeed($feed);
                    } else {
                        $this->addFm("Subscription already exists for the given URL", "error");
                    }
                }

                $categoryIds = $request->get("categories");
                $categories = array();
                if(is_array($categoryIds) && !empty($categoryIds)) {
                    $categories = $this->getRepo("Category")->createQueryBuilder('c')
                        ->where("c.user = :user")->setParameter("user", $this->getUser())
                        ->andWhere("c.id IN (:ids)")->setParameter("ids", $categoryIds)
                        ->getQuery()->getResult();
                }

                foreach($subscription->getCategories() as $c) {
                    if(!in_array($c->getId(), $categoryIds)) {
                        $c->removeSubscription($subscription);
                        $em->persist($c);
                    }
                }

                foreach($categories as $c) {
                    if(!$subscription->getCategories()->contains($c)) {
                        $c->addSubscription($subscription);
                        $em->persist($c);
                    }
                }

                $em->persist($subscription);
                $em->flush();

                $this->addFm("Subscription updated", "success");
            } else {
                $this->addFm("Error", "error");
            }
        } else {
            $this->addFm("Invalid token", "error");
        }

        return $this->redirectToRoute("manager", array("tab" => "catsubs"));
    }

    public function ajaxPopupDeleteSubscriptionAction(Request $request, $id) {
        $response = array(
            "success" => false
        );

        if($request->isXmlHttpRequest() && $this->isLogged()) {
            $subscription = $this->getRepo("Subscription")->findOneBy(array(
                'id' => $id,
                'user' => $this->getUser()
            ));
            if($subscription) {
                $html = $this->renderView("MiamBundle:Manager:popup_subscription_delete.html.twig", array(
                    'subscription' => $subscription
                ));

                $response["success"] = true;
                $response["html"] = $html;
            }
        }

        return new JsonResponse($response);
    }

    public function deleteSubscriptionAction(Request $request, $id) {
        if($this->isTokenValid('manager_subscription_delete', $request->get('csrf_token'))) {
            $subscription = $this->getRepo("Subscription")->findOneBy(array(
                'id' => $id,
                'user' => $this->getUser()
            ));
            if($subscription) {
                $this->get("feed_manager")->deleteSubscription($subscription);

                $this->addFm("Feed unsubscribed", "success");
            } else {
                $this->addFm("Error", "error");
            }
        } else {
            $this->addFm("Invalid token", "error");
        }

        return $this->redirectToRoute("manager", array("tab" => "catsubs"));
    }

    public function ajaxPopupExportOPMLAction(Request $request) {
        $response = array(
            "success" => false
        );

        if($request->isXmlHttpRequest() && $this->isLogged()) {
            $html = $this->renderView("MiamBundle:Manager:popup_opml_export.html.twig");

            $response["success"] = true;
            $response["html"] = $html;
        }

        return new JsonResponse($response);
    }

    public function exportOPMlAction(Request $request) {
        $what = $request->get("what");

        $categories = $this->getRepo("Category")->findForUserWithMore($this->getUser());
        $subscriptions = $this->getRepo("Subscription")->findForUserWithMore($this->getUser());
        $settings = array();

        $opml = $this->renderView("MiamBundle:Manager:export.opml.twig", array(
            'what' => $what,
            'categories' => $categories,
            'subscriptions' => $subscriptions,
            'settings' => $settings,
            'user' => $this->getUser(),
            'dateCreated' => date_format(new \DateTime("now"), DATE_ATOM)
        ));

        $response = new Response($opml);
        $response->headers->set('Content-Type', "application/xml+opml");
        $response->headers->set('Content-Disposition', "attachment;filename=export.opml");

        return $response;
    }

    public function ajaxPopupImportOPMLAction(Request $request) {
        $response = array(
            "success" => false
        );

        if($request->isXmlHttpRequest() && $this->isLogged()) {
            $html = $this->renderView("MiamBundle:Manager:popup_opml_import.html.twig");

            $response["success"] = true;
            $response["html"] = $html;
        }

        return new JsonResponse($response);
    }

    public function importOPMLAction(Request $request) {
        set_time_limit(120);

        if($this->isTokenValid('manager_opml_import', $request->get('csrf_token'))) {
            $opml = false;
            if(isset($_FILES["opml"]["tmp_name"])) {
                try {
                    $opml = simplexml_load_file($_FILES["opml"]["tmp_name"]);
                } catch(\Exception $e) {
                    $opml = false;
                }
            }
            
            if($opml !== false) {
                foreach($opml->body->children() as $child) {
                    $this->importOPMLOutlineForUser($child, $this->getUser());
                }

                $this->get('category_manager')->updateForUser($this->getUser());

                $this->addFm("OPML imported", "success");
            } else {
                $this->addFm("Error", "error");
            }
        } else {
            $this->addFm("Invalid token", "error");
        }

        return $this->redirectToRoute("manager", array("tab" => "catsubs"));
    }

    private function importOPMLOutlineForUser($outline, User $user, Category $parentCategory = null) {
        $em = $this->getEm();

        $type = isset($outline['type']) ? trim($outline['type']) : null;
        if(isset($outline["xmlUrl"])) {
            $subscription = null;

            $feed = $this->get("feed_manager")->getFeedForUrl($outline["xmlUrl"], true);
            if($feed) {
                $subscription = $this->getRepo('Subscription')->findOneBy(array(
                    'user' => $user,
                    'feed' => $feed
                ));
                if(!$subscription) {
                    $subscription = new Subscription();
                    $subscription->setUser($user);
                    $subscription->setFeed($feed);
                    $subscription->setName($feed->getName());
                }

                $text = null;
                if(isset($outline['text']) || isset($outline['title'])) {
                    $text = isset($outline['text']) ? trim($outline['text']) : trim($outline['title']);
                    if(!empty($text)) {
                        $subscription->setName($text);
                    }
                }

                $em->persist($subscription);

                if($parentCategory && !$parentCategory->getSubscriptions()->contains($subscription)) {
                    $parentCategory->addSubscription($subscription);
                    $em->persist($parentCategory);
                }

                $em->flush();
            }
        } elseif($type == "setting") {
            // TODO
        } else {
            $category = null;
            if(isset($outline['text']) || isset($outline['title'])) {
                $name = isset($outline['text']) ? trim($outline['text']) : trim($outline['title']);
                if(!empty($name)) {
                    $category = $this->getRepo("Category")->findOneBy(array(
                        'name' => $name,
                        'parent' => $parentCategory,
                        'user' => $user
                    ));
                    if(!$category) {
                        $category = new Category();
                        $category->setUser($user);
                        $category->setName($name);

                        if($parentCategory) {
                            $category->setParent($parentCategory);
                        }

                        $em->persist($category);
                        $em->flush();
                    }
                }
            }

            foreach($outline->children() as $child) {
                $this->importOPMLOutlineForUser($child, $user, $category);
            }
        }
    }
}
