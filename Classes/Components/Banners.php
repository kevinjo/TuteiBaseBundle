<?php

namespace Tutei\BaseBundle\Classes\Components;

use eZ\Publish\API\Repository\Values\Content\Query;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion\ContentTypeIdentifier;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion\LogicalAnd;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion\ParentLocationId;
use eZ\Publish\API\Repository\Values\Content\Query\SortClause\LocationPriority;
use Symfony\Component\HttpFoundation\Response;
use Tutei\BaseBundle\Classes\SearchHelper;

/**
 * Description of Banners
 *
 * @author Thiago Campos Viana <thiagocamposviana at gmail.com>
 */
class Banners extends Component {
    
    public function render() {
        $pathString = $this->parameters['pathString'];
        $locations = explode('/', $pathString);
        

        $locationId = $locations[count($locations) - 2];

        $searchService = $this->controller->getRepository()->getSearchService();

        $query = new Query();

        $query->criterion = new LogicalAnd(
                array(
            new ContentTypeIdentifier(array('multibanner')),
            new ParentLocationId(array($locationId))
                )
        );

        $query->limit = 1;

        $repository = $this->controller->getRepository();
        $locationService = $repository->getLocationService();
        $location = $locationService->loadLocation($locationId);

        $query->sortClauses = array(
            SearchHelper::createSortClause($location)
        );
        $list = $searchService->findContent($query);


        $blocks = array();

        foreach ($list->searchHits as $block) {
            $parentId = $block->valueObject->versionInfo->contentInfo->mainLocationId;
            $query = new Query();

            $query->criterion = new LogicalAnd(
                    array(
                new ParentLocationId(array($parentId))
                    )
            );
            $query->sortClauses = array(
                new LocationPriority(Query::SORT_ASC)
            );
            $blocks[] = $searchService->findContent($query);
        }

        $siteaccess = $this->controller->getContainer()->get('ezpublish.siteaccess')->name;
        $twigGlobals = $this->controller->getContainer()->get('twig')->getGlobals();
        $language = $twigGlobals['siteaccess'][$siteaccess]['language'];
        $contentService = $repository->getContentService();

        $relationList = array();

        foreach ($blocks as $b) {
            foreach ($b->searchHits as $content) {

                if (isset($content->valueObject->fields['link_object'][$language]->destinationContentId)) {
                    $objId = $content->valueObject->fields['link_object'][$language]->destinationContentId;
                    $related = $contentService->loadContent($objId);

                    $relationList[$objId] = $locationService->loadLocation($related->versionInfo->contentInfo->mainLocationId);
                }
            }
        }

        $response = new Response();

        $response->setPublic();
        $response->setSharedMaxAge(86400);

        // Menu will expire when top location cache expires.
        $response->headers->set('X-Location-Id', $locationId);

        return $this->controller->render(
                        'TuteiBaseBundle:parts:page_banners.html.twig', array('banners' => $blocks, 'relationList' => $relationList), $response
        );
    }
}