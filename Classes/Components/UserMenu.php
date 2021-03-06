<?php
/**
 * File containing the UserMenu Component class
 *
 * @author Thiago Campos Viana <thiagocamposviana@gmail.com>
 */

namespace Tutei\BaseBundle\Classes\Components;

use eZ\Publish\API\Repository\Values\Content\Query;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion\LocationPriority as LocationPriority2;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion\LogicalAnd;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion\Operator;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion\ParentLocationId;
use eZ\Publish\API\Repository\Values\Content\Query\SortClause\LocationPriority;
use Symfony\Component\HttpFoundation\Response;
use Tutei\BaseBundle\Classes\SearchHelper;

/**
 * Renders page UserMenu
 */
class UserMenu extends Component
{

    /**
     * {@inheritDoc}
     */
    public function render()
    {
        $response = new Response();

        $response->setSharedMaxAge(3600);
        $response->setVary('X-User-Hash');

        $classes = $this->controller->getContainer()->getParameter('project.menufilter.top');

        $filters = SearchHelper::createMenuFilter($classes);

        $searchService = $this->controller->getRepository()->getSearchService();

        $query = new Query();

        $query->criterion = new LogicalAnd(
            array(
            $filters,
            new ParentLocationId(array(2)),
            new LocationPriority2(Operator::GTE, 100)
            )
        );

        $query->sortClauses = array(
            new LocationPriority(Query::SORT_ASC)
        );

        $list = $searchService->findContent($query);


        $currentUser = $this->controller->getRepository()->getCurrentUser();


        return $this->controller->render(
                'TuteiBaseBundle:parts:user_menu.html.twig', array(
                'list' => $list,
                'current_user' => $currentUser
                ), $response
        );
    }

}
