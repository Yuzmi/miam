<?php

namespace MiamBundle\Controller;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

use MiamBundle\Entity\Category;
use MiamBundle\Entity\Feed;
use MiamBundle\Entity\Subscription;

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
            $subscriptions = null;
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
            $subscription = $this->get('feed_manager')->subscribeUserToUrl($this->getUser(), $request->get('url'));
            if($subscription) {
                $this->addFm("Feed subscribed", "success");
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
}
