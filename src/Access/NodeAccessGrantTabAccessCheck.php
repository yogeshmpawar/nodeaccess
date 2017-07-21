<?php

namespace Drupal\nodeaccess\Access;

use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\node\Entity\Node;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\Routing\Route;

/**
 * Determines access to routes based on permissions.
 */
class NodeAccessGrantTabAccessCheck implements AccessInterface {

  /**
   * Access check.
   */
  public function access(AccountInterface $account, RouteMatchInterface $route_match, $custom_grant, $custom_arg) {
    $config = \Drupal::configFactory()->getEditable('nodeaccess.settings');
    $node = $route_match->getParameter('node');
    $all_nodes_access = $account->hasPermission('administer nodeaccess');
    $allowed_types = $config->get('nodeaccess_types');

    if ($custom_grant == 'grant') {
      return AccessResult::allowedIf($node->nid && isset($allowed_types[$node->type]) && !empty($allowed_types[$node->type])
      && ($account->hasPermission('grant node permissions', $account) || ($account->hasPermission('grant editable node permissions', $account) && $account->hasPermission('update', $node, $account)) || ($account->hasPermission('grant deletable node permissions', $account) && $account->hasPermission('delete', $node, $account)) || ($account->hasPermission('grant own node permissions', $account) && ($account->uid == $node->uid))));
    }
    return FALSE;
  }

}
