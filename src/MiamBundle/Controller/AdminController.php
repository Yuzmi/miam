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
        $countFeeds = $this->getRepo("Feed")->countAll();
        $countItems = $this->getRepo("Item")->countAll();
        $countTags = $this->getRepo("Tag")->countAll();

        $lastFeeds = $this->getRepo('Feed')
            ->createQueryBuilder('f')
            ->orderBy('f.id', 'DESC')
            ->setMaxResults(10)
            ->getQuery()->getResult();

        $mostActiveFeeds = $this->getRepo('Feed')
            ->createQueryBuilder('f')
            ->orderBy('f.countDailyItems', 'DESC')
            ->addOrderBy('f.originalName', 'ASC')
            ->setMaxResults(10)
            ->getQuery()->getResult();

        $mostSubscribedFeeds = $this->getRepo('Feed')
            ->createQueryBuilder('f')
            ->select('f, COUNT(s) AS countSubscriptions')
            ->leftJoin('f.subscriptions', 's')
            ->groupBy('f')
            ->orderBy('countSubscriptions', 'DESC')
            ->addOrderBy('f.originalName', 'ASC')
            ->setMaxResults(10)
            ->getQuery()->getResult();

        $errorFeeds = $this->getRepo('Feed')
            ->createQueryBuilder('f')
            ->where('f.errorCount > 0')
            ->orderBy('f.errorCount', 'ASC')
            ->setMaxResults(10)
            ->getQuery()->getResult();

        $mostPopularTags = $this->getRepo("Tag")
            ->createQueryBuilder('t')
            ->select('t, COUNT(i) AS countItems')
            ->leftJoin('t.items', 'i')
            ->groupBy('t')
            ->orderBy('countItems', 'DESC')
            ->setMaxResults(10)
            ->getQuery()->getResult();

        return $this->render('MiamBundle:Admin:index.html.twig', array(
            'countFeeds' => $countFeeds,
            'countItems' => $countItems,
            'countTags' => $countTags,
            'lastFeeds' => $lastFeeds,
            'mostActiveFeeds' => $mostActiveFeeds,
            'mostSubscribedFeeds' => $mostSubscribedFeeds,
            'mostPopularTags' => $mostPopularTags,
            'errorFeeds' => $errorFeeds
        ));
	}

    public function showFeedsAction(Request $request) {
        $countTotalFeeds = $this->getRepo("Feed")->countAll();

        $countFeedsPerPage = 50;

        $countPages = ceil($countTotalFeeds / $countFeedsPerPage);
        $page = intval($request->query->get('page'));
        if($page <= 0 || $page > $countPages) {
            $page = 1;
        }

        $qb = $this->getRepo("Feed")
            ->createQueryBuilder('f')
            ->setMaxResults($countFeedsPerPage)
            ->setFirstResult($countFeedsPerPage * ($page - 1));

        $qb->orderBy('f.id', 'DESC');

        $feeds = $qb->getQuery()->getResult();

        return $this->render('MiamBundle:Admin:feeds.html.twig', array(
            'countTotalFeeds' => $countTotalFeeds,
            'countPages' => $countPages,
            'feeds' => $feeds,
            'page' => $page,
            'itemsPerFeed' => $this->getRepo('Feed')->countItemsPerFeed(),
            'subscriptionsPerFeed' => $this->getRepo('Feed')->countSubscriptionsPerFeed(),
            'createFeedForm' => $this->createCreateFeedForm()->createView()
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

    	return $this->redirectToRoute('admin_feeds');
    }

    public function ajaxParseFeedAction(Request $request, $id) {
        $success = false;

        if($request->isXmlHttpRequest() && $this->isLoggedAdmin()) {
            $feed = $this->getRepo("Feed")->find($id);
            if($feed) {
                $this->get('data_parsing')->parseFeed($feed, array('cache' => false));

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

        return $this->redirectToRoute("admin_feeds");
    }

    public function showFeedAction($id) {
        $feed = $this->getRepo('Feed')->find($id);
        if(!$feed) {
            return $this->redirectToRoute('admin');
        }

        return $this->render('MiamBundle:Admin:feed.html.twig', array(
            'feed' => $feed,
            'countItems' => $this->getRepo('Item')->countForFeed($feed),
            'countSubscriptions' => $this->getRepo('Subscription')->countForFeed($feed),
            'lastItems' => $this->getRepo('Item')->findLastPublishedForFeed($feed, 10),
            'parseFeedForm' => $this->createParseFeedForm($feed)->createView(),
            'deleteFeedForm' => $this->createDeleteFeedForm($feed)->createView()
        ));
    }

    private function createParseFeedForm(Feed $feed) {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('admin_feed_parse', array('id' => $feed->getId())))
            ->setMethod('POST')
            ->getForm();
    }

    public function parseFeedAction(Request $request, $id) {
        $feed = $this->getRepo("Feed")->find($id);
        if(!$feed) {
            return $this->redirectToRoute('admin_feeds');
        }

        $form = $this->createParseFeedForm($feed);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()) {
            $this->get('data_parsing')->parseFeed($feed, array('cache' => false));
        }

        return $this->redirectToRoute('admin_feed', array('id' => $feed->getId()));
    }

    private function createDeleteFeedForm(Feed $feed) {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('admin_feed_delete', array('id' => $feed->getId())))
            ->setMethod('POST')
            ->add('submit', SubmitType::class)
            ->getForm();
    }

    public function deleteFeedAction(Request $request, $id) {
        $feed = $this->getRepo("Feed")->find($id);
        if($feed) {
            $form = $this->createDeleteFeedForm($feed);
            $form->handleRequest($request);

            if($form->isSubmitted() && $form->isValid()) {
                $this->get('feed_manager')->deleteFeed($feed);
            }
        }

        return $this->redirectToRoute('admin_feeds');
    }
}
