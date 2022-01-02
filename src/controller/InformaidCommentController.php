<?php

namespace Drupal\informaid_suggestions\Controller;

use Drupal\comment\Controller\CommentController;
use Drupal\comment\CommentInterface;
use Drupal\comment\CommentManagerInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Controller for the comment entity.
 *
 * @see \Drupal\comment\Entity\Comment.
 */
class InformaidCommentController extends CommentController {

  /**
   * The HTTP kernel.
   *
   * @var \Symfony\Component\HttpKernel\HttpKernelInterface
   */
  protected $httpKernel;

  /**
   * The comment manager service.
   *
   * @var \Drupal\comment\CommentManagerInterface
   */
  protected $commentManager;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $entityManager;

  /**
   * Constructs a CommentController object.
   *
   * @param \Symfony\Component\HttpKernel\HttpKernelInterface $http_kernel
   *   HTTP kernel to handle requests.
   * @param \Drupal\comment\CommentManagerInterface $comment_manager
   *   The comment manager service.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager service.
   */
  public function __construct(HttpKernelInterface $http_kernel, CommentManagerInterface $comment_manager, EntityManagerInterface $entity_manager) {
    parent::__construct($http_kernel, $comment_manager, $entity_manager);
    $this->entityManager = $entity_manager;
    $this->httpKernel = $http_kernel;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('http_kernel'),
      $container->get('comment.manager'),
      $container->get('entity.manager')
    );
  }

  /**
   * Publishes the specified comment.
   *
   * @param \Drupal\comment\CommentInterface $comment
   *   A comment entity.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Return Redirect response.
   */
  public function commentApprove(CommentInterface $comment) {
    return parent::commentApprove($comment);
  }

  /**
   * Redirects comment links to the correct page depending on comment settings.
   *
   * Since comments are paged there is no way to guarantee which page a comment
   * appears on. Comment paging and threading settings may be changed at any
   * time. With threaded comments, an individual comment may move between pages
   * as comments can be added either before or after it in the overall
   * discussion. Therefore we use a central routing function for comment links,
   * which calculates the page number based on current comment settings and
   * returns the full comment view with the pager set dynamically.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request of the page.
   * @param \Drupal\comment\CommentInterface $comment
   *   A comment entity.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The comment listing set to the page on which the comment appears.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   */
  public function commentPermalink(Request $request, CommentInterface $comment) {
    $response = parent::commentPermalink($request, $comment);
    return $response;
  }

  /**
   * The _title_callback for the page that renders the comment permalink.
   *
   * @param \Drupal\comment\CommentInterface $comment
   *   The current comment.
   *
   * @return string
   *   The translated comment subject.
   */
  public function commentPermalinkTitle(CommentInterface $comment) {
    return $this->entityManager()->getTranslationFromContext($comment)->label();
  }

  /**
   * Redirects legacy node links to the new path.
   *
   * @param \Drupal\Core\Entity\EntityInterface $node
   *   The node object identified by the legacy URL.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Redirects user to new url.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   */
  public function redirectNode(EntityInterface $node) {
    $response = parent::redirectNode($node);
    return $response;
  }

  /**
   * Form constructor for the comment reply form.
   *
   * There are several cases that have to be handled, including:
   *   - replies to comments
   *   - replies to entities.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request object.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity this comment belongs to.
   * @param string $field_name
   *   The field_name to which the comment belongs.
   * @param int $pid
   *   (optional) Some comments are replies to other comments. In those cases,
   *   $pid is the parent comment's comment ID. Defaults to NULL.
   *
   * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
   *   An associative array containing:
   *   - An array for rendering the entity or parent comment.
   *     - comment_entity: If the comment is a reply to the entity.
   *     - comment_parent: If the comment is a reply to another comment.
   *   - comment_form: The comment form as a renderable array.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   */
  public function getReplyForm(Request $request, EntityInterface $entity, $field_name, $pid = NULL) {
    $build = parent::getReplyForm($request, $entity, $field_name, $pid);
    return $build;
  }

  /**
   * Access check for the reply form.
   * 2 levels of comments are allowed:
   *   - 0 : parent
   *   - 1 : first level of chidren
   *   - >1 : not allowed to comment.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity this comment belongs to.
   * @param string $field_name
   *   The field_name to which the comment belongs.
   * @param int $pid
   *   (optional) Some comments are replies to other comments. In those cases,
   *   $pid is the parent comment's comment ID. Defaults to NULL.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   An access result
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   */
  public function replyFormAccess(EntityInterface $entity, $field_name, $pid = NULL) {
    $access = parent::replyFormAccess($entity, $field_name, $pid);

    // If reply to comment.
    if ($pid) {
      // If comment to reply is already child of comment, don't access.
      $childcomment = $this->entityManager->getStorage('comment')->load($pid);
      $access = AccessResult::allowedIf(!empty($childcomment));
      $access = AccessResult::allowedIf(($childcomment->pid->count() == 0));
    }
    return $access;
  }

  /**
   * Returns a set of nodes' last read timestamps.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request of the page.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The JSON response.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   */
  public function renderNewCommentsNodeLinks(Request $request) {
    $links = parent::renderNewCommentsNodeLinks($request);
    return new JsonResponse($links);
  }

}
