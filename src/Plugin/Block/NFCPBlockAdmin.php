<?php

namespace Drupal\nfcp\Plugin\Block;

use Drupal\node\NodeInterface;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\CurrentRouteMatch;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides a 'nfcp' block for admin.
 *
 * @Block(
 *   id = "nfcp_admin_block",
 *   admin_label = @Translation("Node Fields Complete Percentage: Admn"),
 * )
 */
class NFCPBlockAdmin extends BlockBase {

  /**
   * The Query factory service.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $currentRoute;

  /**
   * The entity type manager.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $node;

  /**
   * Creates a AdminSettingsForm instance.
   *
   * @param \Drupal\Core\Routing\CurrentRouteMatch $current_route_match
   *   The route service.
   * @param \Drupal\node\NodeInterface $node
   *   The node.
   */
  public function __construct(
    CurrentRouteMatch $current_route_match,
    NodeInterface $node
  ) {
    $this->currentRoute = $current_route_match;
    $this->node = $node;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_route_match'),
      $container->get('node'),
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    return AccessResult::allowedIfHasPermission($account, 'nfcp administer');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $node = $this->currentRoute->getParameter('node');
    if (!$node instanceof NodeInterface) {
      return FALSE;
    }

    $config = $this->configFactory->config('nfcp.configuration');
    $node_type = $config->get('node_type');
    if ($node_type != $node->getType()) {
      return FALSE;
    }

    module_load_include('inc', 'nfcp', 'nfcp');
    $nfcp_data = nfcp_get_complete_percentage_data($node);

    $nfcp_markup = [
      '#theme' => 'nfcp_admin_template',
      '#title' => $nfcp_data['title'],
      '#nid' => $nfcp_data['nid'],
      '#uid' => $nfcp_data['uid'],
      '#total' => $nfcp_data['total'],
      '#open_link' => $nfcp_data['open_link'],
      '#completed' => $nfcp_data['completed'],
      '#incomplete' => $nfcp_data['incomplete'],
      '#next_percent' => $nfcp_data['next_percent'],
      '#nextfield_name' => $nfcp_data['nextfield_name'],
      '#nextfield_title' => $nfcp_data['nextfield_title'],
      '#current_percent' => $nfcp_data['current_percent'],
      '#attached' => [
        'library' => ['nfcp/nfcp.block'],
      ],
    ];

    return $nfcp_markup;
  }

}
