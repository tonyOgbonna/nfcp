<?php

namespace Drupal\nfcp\Plugin\Block;

use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\Query\QueryFactoryInterface;
use Drupal\Core\Session\AccountProxyInterface;

/**
 * Provides a 'nfcp' block.
 *
 * @Block(
 *   id = "nfcp_block",
 *   admin_label = @Translation("Node Fields Complete Percentage"),
 * )
 */
class NFCPBlock extends BlockBase {

  /**
   * The Query factory service.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactoryInterface
   */
  protected $queryFactory;

  /**
   * The entity type manager.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $node;

  /**
   * Drupal\Core\Session\AccountProxyInterface definition.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Creates a AdminSettingsForm instance.
   *
   * @param \Drupal\Core\Entity\Query\QueryFactoryInterface $query_factory
   *   The Query factory service.
   * @param \Drupal\node\NodeInterface $node
   *   The node.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user.
   */
  public function __construct(
    QueryFactoryInterface $query_factory,
    NodeInterface $node,
    AccountProxyInterface $current_user
  ) {
    $this->queryFactory = $query_factory;
    $this->node = $node;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.query.sql'),
      $container->get('node'),
      $container->get('current_user'),
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    $config = $this->configFactory->config('nfcp.configuration');
    $node_type = $config->get('node_type');
    return AccessResult::allowedIfHasPermission($account, 'create ' . $node_type . ' content');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $user_id = $this->currentUser->id();
    $config = $this->configFactory->config('nfcp.configuration');
    $node_type = $config->get('node_type');
    $nids = $this->queryFactory->get($node_type, 'AND')
      ->condition('type', $node_type)
      ->condition('uid', $user_id)
      ->execute();
    if (!count($nids)) {
      return FALSE;
    }
    module_load_include('inc', 'nfcp', 'nfcp');
    $nfcp_markups = [];
    foreach ($nids as $nid) {
      $node = $this->node->load($nid);
      $nfcp_data = nfcp_get_complete_percentage_data($node);
      if (($nfcp_data['hide_nfcp_block'] && $nfcp_data['incomplete'] == 0) || $nfcp_data['total'] == 0) {
        return FALSE;
      }

      $nfcp_markup = [
        '#theme' => 'nfcp_template',
        '#title'   => $nfcp_data['title'],
        '#nid'   => $nfcp_data['nid'],
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
      $nfcp_markups[] = $nfcp_markup;
    }

    return $nfcp_markups;
  }

}
