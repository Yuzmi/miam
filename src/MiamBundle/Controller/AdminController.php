<?php

namespace MiamBundle\Controller;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

use MiamBundle\Entity\Feed;

class AdminController extends MainController
{
	public function indexAction() {
        $feeds = $this->getRepo('Feed')
            ->createQueryBuilder('f')
            ->orderBy('f.dateCreated', 'DESC')
            ->addOrderBy('f.id', 'DESC')
            ->getQuery()->getResult();

        $itemsPerFeed = $this->getRepo('Feed')->countItemsPerFeed();
        $subscriptionsPerFeed = $this->getRepo('Feed')->countSubscriptionsPerFeed();

        $createFeedForm = $this->createCreateFeedForm();

        return $this->render('MiamBundle:Admin:index.html.twig', array(
            'feeds' => $feeds,
            'itemsPerFeed' => $itemsPerFeed,
            'subscriptionsPerFeed' => $subscriptionsPerFeed,
            'createFeedForm' => $createFeedForm->createView()
        ));
	}

    private function createCreateFeedForm() {
    	return $this->createFormBuilder()
    		->setAction($this->generateUrl('admin_feed_create'))
    		->setMethod('POST')
    		->add('url', TextType::class)
    		->add('submit', SubmitType::class)
    		->getForm();
    }

    public function createFeedAction(Request $request) {
    	$form = $this->createCreateFeedForm();
    	$form->handleRequest($request);

    	if($form->isSubmitted() && $form->isValid()) {
            $this->get('feed_manager')->getFeedForUrl($form->get('url')->getData(), true);
    	}

    	return $this->redirectToRoute('admin');
    }

    public function ajaxParseFeedAction(Request $request, $id) {
        $success = false;

        if($request->isXmlHttpRequest() && $this->isLoggedAdmin()) {
            $feed = $this->getRepo("Feed")->find($id);
            if($feed) {
                $this->get('data_parsing')->parseFeed($feed);

                $success = true;
            }
        }

        return new JsonResponse(array(
            'success' => $success
        ));
    }

    public function ajaxDeleteFeedAction(Request $request, $id) {
        $success = false;

        if($request->isXmlHttpRequest() && $this->isLoggedAdmin()) {
            $feed = $this->getRepo("Feed")->find($id);
            if($feed) {
                $this->get('feed_manager')->deleteFeed($feed);

                $success = true;
            }
        }

        return new JsonResponse(array(
            'success' => $success
        ));
    }

    public function exportFeedsAction() {
        $opml = $this->renderView("MiamBundle:Default:export.opml.twig", array(
            'what' => "feeds",
            'feeds' => $this->getRepo("Feed")->findAll(),
            'ownerName' => "Admin"
        ));

        $response = new Response($opml);
        $response->headers->set('Content-Type', "application/xml+opml");
        $response->headers->set('Content-Disposition', "attachment;filename=Miam_Admin_".date('Y-m-d').".opml");

        return $response;

    }

    public function ajaxPopupImportFeedsAction(Request $request) {
        $response = array(
            "success" => false
        );

        if($request->isXmlHttpRequest() && $this->isLoggedAdmin()) {
            $html = $this->renderView("MiamBundle:Admin:popup_feeds_import.html.twig");

            $response["success"] = true;
            $response["html"] = $html;
        }

        return new JsonResponse($response);
    }

    public function importFeedsAction(Request $request) {
        if($this->isTokenValid('admin_feeds_import', $request->get('csrf_token'))) {
            $opml = false;
            if(extension_loaded('SimpleXML') && isset($_FILES["opml"]["tmp_name"])) {
                try {
                    $opml = simplexml_load_file($_FILES["opml"]["tmp_name"]);
                } catch(\Exception $e) {
                    $opml = false;
                }
            }

            if($opml !== false) {
                $countNew = 0;

                foreach($opml->body->children() as $outline) {
                    if(isset($outline["xmlUrl"])) {
                        $url = $outline["xmlUrl"];
                        if(filter_var($url, FILTER_VALIDATE_URL) !== false) {
                            $feed = $this->get("feed_manager")->findFeedForUrl($url);
                            if(!$feed) {
                                $feed = $this->get('feed_manager')->createFeedForUrl($url);
                                $countNew++;
                            }
                        }
                    }
                }

                $this->addFm("Feeds imported (".$countNew.")", "success");
            } else {
                $this->addFm("Error", "error");
            }
        } else {
            $this->addFm("Invalid token", "error");
        }

        return $this->redirectToRoute("admin");
    }
}
