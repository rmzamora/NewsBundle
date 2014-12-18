<?php

namespace Rz\NewsBundle\Controller;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class NewsController
 * @package Rz\NewsBundle\Controller
 */
class NewsCollectionController extends AbstractNewsController
{

    const NEWS_LIST_TYPE_COLLECTION = 'collection';

    /**
     * @param $collection
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function collectionAction($collection){
        if(!$collection = $this->verifyCollection($collection)) {
            throw new NotFoundHttpException('Unable to find the collection');
        }

        try {
            $response = $this->renderCollectionList($collection);
        } catch (\Exception $e) {
            throw $e;
        }

        return $response;
    }


    /**
     * @param $page
     * @param $collection
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     * @return \Symfony\Component\HttpFoundation\Response
     *
     */
    public function collectionPagerAction($collection, $page) {
        if(!$collection = $this->verifyCollection($collection)) {
            throw new NotFoundHttpException('Unable to find the collection');
        }

        try {
            $response = $this->renderCollectionList($collection, $page);
        } catch (\Exception $e) {
            throw $e;
        }

        return $response;
    }

    public function collectionAjaxPagerAction($collection, $page) {

        if(!$collection = $this->verifyCollection($collection)) {
            throw new NotFoundHttpException('Unable to find the collection');
        }

        //redirect to normal controller if not ajax
        if (!$this->get('request_stack')->getCurrentRequest()->isXmlHttpRequest()) {
            return $this->redirect($this->generateUrl('rz_news_collection_pager', array('collection'=>$collection->getSlug(), 'page'=>$page)), 301);
        }

        try {
            $parameters = $this->getCollectionDataForView($collection, $page);
        } catch(\Exception $e) {
            throw $e;
        }

        return $this->getAjaxResponse($collection, $parameters, self::NEWS_LIST_TYPE_COLLECTION);
    }

    /**
     *
     * @param $collection
     * @param $permalink
     *
     *
     * @throws \Exception
     * @return Response
     */
    public function collectionViewAction($collection, $permalink)
    {

        if(!$collection = $this->verifyCollection($collection)) {
            throw new NotFoundHttpException('Unable to find the collection');
        }

        if ($post = $this->getPostManager()->findOneByPermalink($permalink, $this->container->get('sonata.news.blog'))) {
            try {
                return $this->renderCollectionView($post, $collection);
            } catch (\Exception $e) {
                throw $e;
            }
        } else {
            throw new NotFoundHttpException('Invalid URL');
        }
    }

    protected function renderCollectionView($post, $collection) {

        if (!$post || !$post->isPublic()) {
            throw new NotFoundHttpException('Unable to find the post');
        }

        if ($seoPage = $this->getSeoPage()) {
            $request = $this->get('request_stack')->getCurrentRequest();
            $seoPage
                ->setTitle($post->getTitle())
                ->addMeta('name', 'description', $post->getAbstract())
                ->addMeta('property', 'og:title', $post->getTitle())
                ->addMeta('property', 'og:type', 'blog')
                ->addMeta('property', 'og:url',  $this->generateUrl('rz_news_collection_view', array(
                    'collection'  => $collection->getSlug(),
                    'permalink'  => $this->getBlog()->getPermalinkGenerator()->generate($post, true),
                    '_format' => $request->getRequestFormat()
                ), true))
                ->addMeta('property', 'og:description', $post->getAbstract())
                ->setLinkCanonical($this->generateUrl('rz_news_view', array(
                    'permalink'  => $this->getBlog()->getPermalinkGenerator()->generate($post, true),
                    '_format' => $request->getRequestFormat()
                ), true))
            ;
        }

        //set default template
        $template = $this->getFallbackTemplate();


        $viewTemplate = $post->getSetting('template');
        if($viewTemplate) {
            if ($this->getTemplating()->exists($template)) {
                $template = $viewTemplate;
            } else {
                //get generic template
                $pool = $this->getNewsPool();
                $defaultTemplateName = $pool->getDefaultTemplateNameByCollection($pool->getDefaultDefaultCollection());
                $defaultViewTemplate = $pool->getTemplateByCollection($defaultTemplateName);

                if($defaultViewTemplate) {
                    $template = $viewTemplate['path'];
                }
            }
        }

        return $this->render($template, array(
            'post' => $post,
            'form' => false,
            'blog' => $this->get('sonata.news.blog')
        ));
    }

    protected function renderCollectionList($collection, $page = null) {

        try {
            $parameters = $this->getCollectionDataForView($collection, $page);
        } catch(\Exception $e) {
            throw $e;
        }

        $template = $collection->getSetting('template');

        if($template && $this->getTemplating()->exists($template) ) {
            return $this->render($template, $parameters);
        } else {
            return $this->renderNewsList($parameters, self::NEWS_LIST_TYPE_COLLECTION);
        }
    }

    protected function getCollectionDataForView($collection, $page = null) {
        $parameters = array('collection' => $collection);
        if($page) {
            $parameters['page'] = $page;
        }

        $pager = $this->fetchNews($parameters);

        if ($pager->getNbResults() <= 0) {
            throw new NotFoundHttpException('Invalid URL');
        }

        return $this->buildParameters($pager, $this->get('request_stack')->getCurrentRequest(), array('collection' => $collection, 'is_ajax_pagination'=>$this->container->getParameter('rz_news.settings.ajax_pagination')));
    }

    protected function verifyCollection($collection) {
        $collection = $this->get('sonata.classification.manager.collection')->findOneBy(array(
            'slug' => $collection,
            'enabled' => true
        ));

        if (!$collection) {
            return false;
        }

        if (!$collection->getEnabled()) {
            return false;
        }

        return $collection;
    }
}