<?php

namespace Drupal\informaid_suggestions\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    // Change path '/user/login' to '/login'.
    /*if ($route = $collection->get('comment.reply')) {
    $route->setDefault('_controller', '\Drupal\informaid_suggestions\Controller\InformaidCommentController::getReplyForm');
    $route->setRequirement('_custom_access', '\Drupal\informaid_suggestions\Controller\InformaidCommentController::replyFormAccess');
    }*/
    /*if ($route = $collection->get('entity.taxonomy_term.canonical')) {
    print 'canonical';
    $route->setDefault('_controller', '\Drupal\informaid_suggestions\Controller\TaxonomyTermController::getTermPage');
    $route->setRequirement('_entity_access', 'taxonomy_term.view');
    }*/
  }

}
