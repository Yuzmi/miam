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

        $timezones = \DateTimeZone::listIdentifiers();

		return $this->render('MiamBundle:Manager:index.html.twig', array(
            'categories' => $categories,
            'subscriptions' => $subscriptions,
            'timezones' => \DateTimeZone::listIdentifiers()
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

    public function ajaxPopupEditCategoryAction(Request $request, $id) {
        $response = array(
            "success" => false
        );

        if($request->isXmlHttpRequest() && $this->isLogged()) {
            $category = $this->getRepo("Category")->findOneForUserWithMore($id, $this->getUser());
            if($category) {
                $categories = $this->getRepo("Category")->findForUserWithMoreOutOf($this->getUser(), $category);
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

    public function saveCategoryAction(Request $request, $id = null) {
        $em = $this->getEm();

        if($this->isTokenValid('manager_category_save', $request->get('csrf_token'))) {
            $category = null;
            if($id) {
                $category = $this->getRepo("Category")->findOneBy(array(
                    'id' => $id,
                    'user' => $this->getUser()
                ));
            }

            $new_category = false;
            if(!$category) {
                $category = new Category();
                $category->setUser($this->getUser());

                $new_category = true;
            }

            $name = mb_substr(trim($request->get("name")), 0, 255);
            if(!empty($name)) {
                $category->setName($name);

                $parentId = intval($request->get('parent'));
                $parent = null;
                if($parentId) {
                    $parent = $this->getRepo("Category")->findOneBy(array(
                        'id' => $parentId,
                        'user' => $this->getUser()
                    ));
                }
                if($parent) {
                    if(!$new_category && $parent->getLeftPosition() >= $category->getLeftPosition() && $parent->getRightPosition() <= $category->getRightPosition()) {
                        $category->setParent(null);
                    } else {
                        $category->setParent($parent);
                    }
                } else {
                    $category->setParent(null);
                }

                $em->persist($category);
                $em->flush();

                $this->get('category_manager')->updateForUser($this->getUser());

                if($new_category) {
                    $this->addFm("category_created", "success", array("%category%" => $category->getName()), "flashbag");
                } else {
                    $this->addFm("category_updated", "success", array("%category%" => $category->getName()), "flashbag");
                }
            } else {
                $this->addFm("error.invalid_name", "error", array(), "flashbag");
            }
        } else {
            $this->addFm("error.invalid_token", "error", array(), "flashbag");
        }

        return $this->redirectToRoute("manager", array("tab" => "catsubs"));
    }

    public function ajaxPopupEditCategorySubscriptionsAction(Request $request, $id) {
        $response = array(
            "success" => false
        );

        if($request->isXmlHttpRequest() && $this->isLogged()) {
            $category = $this->getRepo("Category")->findOneBy(array(
                'id' => $id, 
                'user' => $this->getUser()
            ));
            if($category) {
                $subscriptions = $this->getRepo("Subscription")
                    ->createQueryBuilder('s')
                    ->innerJoin('s.feed', 'f')->addSelect('f')
                    ->leftJoin('s.category', 'c')->addSelect('c')
                    ->where('s.user = :user')->setParameter('user', $this->getUser())
                    ->orderBy('c.leftPosition', 'ASC')
                    ->addOrderBy('s.name', 'ASC')
                    ->getQuery()->getResult();

                $html = $this->renderView("MiamBundle:Manager:popup_category_edit_subscriptions.html.twig", array(
                    'category' => $category,
                    'subscriptions' => $subscriptions
                ));

                $response["success"] = true;
                $response["html"] = $html;
            }
        }

        return new JsonResponse($response);
    }

    public function updateCategorySubscriptionsAction(Request $request, $id) {
        if($this->isTokenValid('manager_category_update_subscriptions', $request->get('csrf_token'))) {
            $category = $this->getRepo("Category")->findOneBy(array(
                'id' => $id,
                'user' => $this->getUser()
            ));
            if($category) {
                $postSubscriptions = (array) $request->request->get('subscriptions');
                
                $subIds = array();
                foreach($postSubscriptions as $s) {
                    $subId = intval($s);
                    if($s > 0 && !in_array($subId, $subIds)) {
                        $subIds[] = $subId;
                    }
                }

                $em = $this->getEm();

                $subscriptions = $this->getRepo("Subscription")->findByUser($this->getUser());
                foreach($subscriptions as $s) {
                    if($s->getCategory() && $s->getCategory()->getId() == $category->getId() && !in_array($s->getId(), $subIds)) {
                        $s->setCategory(null);
                        $em->persist($s);
                    } elseif((!$s->getCategory() || $s->getCategory()->getId() != $category->getId()) && in_array($s->getId(), $subIds)) {
                        $s->setCategory($category);
                        $em->persist($s);
                    }
                }

                $em->flush();

                $this->addFm("category_subscriptions_updated", "success", array("%category%" => $category->getName()), "flashbag");
            } else {
                $this->addFm("error", "error", array(), "flashbag");
            }
        } else {
            $this->addFm("error.invalid_token", "error", array(), "flashbag");
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

                $this->addFm("category_deleted", "success", array("%category%" => $category->getName()), "flashbag");
            } else {
                $this->addFm("error", "error", array(), "flashbag");
            }
        } else {
            $this->addFm("error.invalid_token", "error", array(), "flashbag");
        }

        return $this->redirectToRoute("manager", array("tab" => "catsubs"));
    }

    public function ajaxPopupNewSubscriptionAction(Request $request) {
        $response = array(
            "success" => false
        );

        if($request->isXmlHttpRequest() && $this->isLogged()) {
            $categories = $this->getRepo("Category")->findForUser($this->getUser(), "leftPosition");

            $html = $this->renderView("MiamBundle:Manager:popup_subscription_new.html.twig", array(
                "categories" => $categories
            ));

            $response["success"] = true;
            $response["html"] = $html;
        }

        return new JsonResponse($response);
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

    public function saveSubscriptionAction(Request $request, $id = null) {
        $em = $this->getEm();

        if($this->isTokenValid('manager_subscription_save', $request->get('csrf_token'))) {
            $subscription = null;
            if($id) {
                $subscription = $this->getRepo("Subscription")->findOneBy(array(
                    'id' => $id,
                    'user' => $this->getUser()
                ));
            }

            $new_subscription = false;
            if(!$subscription) {
                $subscription = new Subscription();
                $subscription->setUser($this->getUser());

                $new_subscription = true;
            }

            $feed = $this->get('feed_manager')->getFeedForUrl(trim($request->get('url')), true);
            if($feed) {
                $subscription->setFeed($feed);

                $name = mb_substr(trim($request->get("name")), 0, 255);
                if(!empty($name)) {
                    $subscription->setName($name);
                } else {
                    $subscription->setName($feed->getName());
                }

                $categoryId = intval($request->get('category'));
                if($categoryId > 0) {
                    $category = $this->getRepo("Category")->findOneBy(array(
                        'id' => $categoryId,
                        'user' => $this->getUser()
                    ));
                } else {
                    $category = null;
                }
                $subscription->setCategory($category ?: null);

                $em->persist($subscription);
                $em->flush();

                $countSubscribers = $this->getRepo("Subscription")->countForFeed($feed);
                if($countSubscribers == 1) {
                    $this->get('data_parsing')->parseFeed($feed);
                }

                if($new_subscription) {
                    $this->addFm(
                        "subscription_created", 
                        "success", 
                        array("%subscription%" => $subscription->getName() ?: $subscription->getUrl()), 
                        "flashbag"
                    );
                } else {
                    $this->addFm(
                        "subscription_updated", 
                        "success", 
                        array("%subscription%" => $subscription->getName() ?: $subscription->getUrl()), 
                        "flashbag"
                    );
                }
            } else {
                $this->addFm("error.feed_not_found", "error", array(), "flashbag");
            }
        } else {
            $this->addFm("error.invalid_token", "error", array(), "flashbag");
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

                $this->addFm(
                        "subscription_deleted", 
                        "success", 
                        array("%subscription%" => $subscription->getName() ?: $subscription->getUrl()), 
                        "flashbag"
                    );
            } else {
                $this->addFm("error", "error", array(), "flashbag");
            }
        } else {
            $this->addFm("error.invalid_token", "error", array(), "flashbag");
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
        $settings = $this->getUser()->getSettings();

        $opml = $this->renderView("MiamBundle:Default:export.opml.twig", array(
            'what' => $what,
            'categories' => $categories,
            'subscriptions' => $subscriptions,
            'settings' => $settings,
            'ownerName' => $this->getUser()->getUsername()
        ));

        $response = new Response($opml);
        $response->headers->set('Content-Type', "application/xml+opml");
        $response->headers->set('Content-Disposition', "attachment;filename=Miam_".date('Y-m-d').".opml");

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
            if(extension_loaded('SimpleXML') && isset($_FILES["opml"]["tmp_name"])) {
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

                $this->addFm("opml_imported", "success", array(), "flashbag");
            } else {
                $this->addFm("error", "error", array(), "flashbag");
            }
        } else {
            $this->addFm("error.invalid_token", "error", array(), "flashbag");
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

                if(isset($outline['text']) || isset($outline['title'])) {
                    $name = isset($outline['text']) ? $outline['text'] : $outline['title'];
                    $name = mb_substr(trim($name), 0, 255);
                    if(!empty($name)) {
                        $subscription->setName($name);
                    }
                }

                if($parentCategory && !$subscription->getCategory()) {
                    $subscription->setCategory($parentCategory);
                }

                $em->persist($subscription);
                $em->flush();
            }
        } elseif($type == "setting") {
            $name = isset($outline['name']) ? (string) $outline['name'] : null;
            $value = isset($outline['value']) ? (string) $outline['value'] : null;
            if($name && $value !== null) {
                $this->get('setting_manager')->setUserSetting($user, $name, $value);

                $em->persist($user);
                $em->flush();
            }
        } else {
            $category = null;
            if(isset($outline['text']) || isset($outline['title'])) {
                $name = isset($outline['text']) ? $outline['text'] : $outline['title'];
                $name = mb_substr(trim($name), 0, 255);
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

    public function updateSettingsAction(Request $request) {
        if($this->isTokenValid('manager_settings_update', $request->get('csrf_token'))) {
            $sm = $this->get('setting_manager');
            $user = $this->getUser();
            
            $sm->setUserSetting($user, 'DATE_FORMAT', $request->get("DATE_FORMAT"));
            $sm->setUserSetting($user, 'FONT_FAMILY', $request->get("FONT_FAMILY"));
            $sm->setUserSetting($user, 'FONT_SIZE', $request->get("FONT_SIZE"));
            $sm->setUserSetting($user, 'HIDE_SIDEBAR', $request->get("HIDE_SIDEBAR"));
            $sm->setUserSetting($user, 'SHOW_ITEM_DETAILS', $request->get("SHOW_ITEM_DETAILS"));
            $sm->setUserSetting($user, 'SHOW_ITEM_PICTURES', $request->get("SHOW_ITEM_PICTURES"));
            $sm->setUserSetting($user, 'THEME', $request->get("THEME"));
            $sm->setUserSetting($user, 'TIMEZONE', $request->get('TIMEZONE'));
            
            $locale = $request->get("LOCALE");
            if(in_array($locale, array("en", "fr"))) {
                $user->setLocale($locale);
                $this->get('session')->set('_locale', $locale);
            }

            $em = $this->getEm();
            $em->persist($user);
            $em->flush();

            $this->addFm("settings_updated", "success", array(), "flashbag");
        } else {
            $this->addFm("error.invalid_token", "error", array(), "flashbag");
        }

        return $this->redirectToRoute("manager", array("tab" => "settings"));
    }

    public function updatePasswordAction(Request $request) {
        if($this->isTokenValid('manager_password_update', $request->get('csrf_token'))) {
            $encoder = $this->get('security.password_encoder');
            $user = $this->getUser();

            $current_password = $request->get('current_password');
            $new_password = $request->get('new_password');
            $new_password_again = $request->get('new_password_again');

            if(!$encoder->isPasswordValid($user, $current_password)) {
                $this->addFm("error.current_password_wrong", "error", array(), "flashbag");
            } elseif(empty($new_password)) {
                $this->addFm("error.new_password_empty", "error", array(), "flashbag");
            } elseif($new_password != $new_password_again) {
                $this->addFm("error.new_passwords_different", "error", array(), "flashbag");
            } else {
                $password = $encoder->encodePassword($user, $new_password);
                $user->setPassword($password);

                $em = $this->getEm();
                $em->persist($user);
                $em->flush();

                $this->addFm("password_updated", "success", array(), "flashbag");
            }
        } else {
            $this->addFm("error.invalid_token", "error", array(), "flashbag");
        }

        return $this->redirectToRoute("manager", array("tab" => "password"));
    }
}
