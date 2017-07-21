<?php

namespace Drupal\nodeaccess\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\Html;
use Drupal\Core\Extension\ModuleHandler;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityFieldManager;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Node Access Grant Tab form.
 *
 * @package Drupal\nodeaccess\Form
 */
class NodeAccessGrantTabForm extends FormBase {

  /**
   * Implements \Drupal\Core\Form\FormInterface::getFormID().
   */
  public function getFormId() {
    return 'nodeaccess_grant_tab';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = \Drupal::configFactory()->getEditable('nodeaccess.settings');
    $form_values = $form_state->getValues();

    if (!$form_values) {
      $form_values = [];
      $grants = nodeaccess_get_grants($node);
      $form_values['rid'] = isset($grants['rid']) ? $grants['rid'] : array();
      $form_values['uid'] = isset($grants['uid']) ? $grants['uid'] : array();
    }
    elseif ($form_values['keys']) {
      // @todo rewrite
      $params = [];
      $sql = "SELECT uid, name FROM {users} WHERE name LIKE :name";
      $name = preg_replace('!\*+!', '%', $form_values['keys']);
      $params[':name'] = $name;

      if (isset($form_values['uid']) && count($form_values['uid'])) {
        $sql .= ' AND uid NOT IN (:uid)';
        $params[':uid'] = array_keys($form_values['uid']);
      }

      $result = db_query($sql, $params);
      foreach ($result as $account) {
        $form_values['uid'][$account->uid] = [
          'name' => $account->name,
          'keep' => 1,
          'grant_view' => isset($form_values['rid'][DRUPAL_AUTHENTICATED_RID]['grant_view']) ?
            $form_values['rid'][DRUPAL_AUTHENTICATED_RID]['grant_view'] : 0,
          'grant_update' => isset($form_values['rid'][DRUPAL_AUTHENTICATED_RID]['grant_update']) ?
            $form_values['rid'][DRUPAL_AUTHENTICATED_RID]['grant_update'] : 0,
          'grant_delete' => isset($form_values['rid'][DRUPAL_AUTHENTICATED_RID]['grant_delete']) ?
            $form_values['rid'][DRUPAL_AUTHENTICATED_RID]['grant_delete'] : 0,
        ];
      }
    }

    if (!isset($form_values['rid'])) {
      $form_values['rid'] = [];
    }

    if (!isset($form_values['uid'])) {
      $form_values['uid'] = [];
    }

    $roles = $form_values['rid'];
    $users = $form_values['uid'];

    $form['nid'] = [
      '#type' => 'hidden',
      '#value' => $node->nid,
    ];

    $allowed_roles = $config->get('nodeaccess_roles');
    $allowed_grants = $config->get('nodeaccess_grants');
    // If $preserve is TRUE, the fields the user is not allowed to view or
    // edit are included in the form as hidden fields to preserve them.
    $preserve = $config->get('nodeaccess_preserve');
    // Roles table.
    if (is_array($roles)) {
      $form['rid'] = ['#tree' => TRUE];
      foreach ($roles as $key => $field) {
        if (isset($allowed_roles[$key]) && $allowed_roles[$key]) {
          $form['rid'][$key]['name'] = [
            '#type' => 'hidden',
            '#value' => $field['name'],
          ];
          if ($allowed_grants['view']) {
            $form['rid'][$key]['grant_view'] = [
              '#type' => 'checkbox',
              '#default_value' => $field['grant_view'],
            ];
          }
          elseif ($preserve) {
            $form['rid'][$key]['grant_view'] = [
              '#type' => 'hidden',
              '#value' => $field['grant_view'],
            ];
          }
          if ($allowed_grants['edit']) {
            $form['rid'][$key]['grant_update'] = [
              '#type' => 'checkbox',
              '#default_value' => $field['grant_update'],
            ];
          }
          elseif ($preserve) {
            $form['rid'][$key]['grant_update'] = [
              '#type' => 'hidden',
              '#value' => $field['grant_update'],
            ];
          }
          if ($allowed_grants['delete']) {
            $form['rid'][$key]['grant_delete'] = [
              '#type' => 'checkbox',
              '#default_value' => $field['grant_delete'],
            ];
          }
          elseif ($preserve) {
            $form['rid'][$key]['grant_delete'] = [
              '#type' => 'hidden',
              '#value' => $field['grant_delete'],
            ];
          }
        }
        elseif ($preserve) {
          $form['rid'][$key]['name'] = [
            '#type' => 'hidden',
            '#value' => $field['name'],
          ];
          $form['rid'][$key]['grant_view'] = [
            '#type' => 'hidden',
            '#value' => $field['grant_view'],
          ];
          $form['rid'][$key]['grant_update'] = [
            '#type' => 'hidden',
            '#value' => $field['grant_update'],
          ];
          $form['rid'][$key]['grant_delete'] = [
            '#type' => 'hidden',
            '#value' => $field['grant_delete'],
          ];
        }
      }
    }

    // Users table.
    if (is_array($users)) {
      $form['uid'] = ['#tree' => TRUE];
      foreach ($users as $key => $field) {
        $form['uid'][$key]['name'] = [
          '#type' => 'hidden',
          '#value' => $field['name'],
        ];
        $form['uid'][$key]['keep'] = [
          '#type' => 'checkbox',
          '#default_value' => $field['keep'],
        ];
        if ($allowed_grants['view']) {
          $form['uid'][$key]['grant_view'] = [
            '#type' => 'checkbox',
            '#default_value' => $field['grant_view'],
          ];
        }
        elseif ($preserve) {
          $form['uid'][$key]['grant_view'] = [
            '#type' => 'hidden',
            '#value' => $field['grant_view'],
          ];
        }
        if ($allowed_grants['edit']) {
          $form['uid'][$key]['grant_update'] = [
            '#type' => 'checkbox',
            '#default_value' => $field['grant_update'],
          ];
        }
        elseif ($preserve) {
          $form['uid'][$key]['grant_update'] = [
            '#type' => 'hidden',
            '#value' => $field['grant_update'],
          ];
        }
        if ($allowed_grants['delete']) {
          $form['uid'][$key]['grant_delete'] = [
            '#type' => 'checkbox',
            '#default_value' => $field['grant_delete'],
          ];
        }
        elseif ($preserve) {
          $form['uid'][$key]['grant_delete'] = [
            '#type' => 'hidden',
            '#value' => $field['grant_delete'],
          ];
        }
      }
    }

    // Autocomplete returns errors if users don't have access to profiles.
    if (user_access('access user profiles')) {
      $form['keys'] = [
        '#type' => 'textfield',
        '#default_value' => isset($form_values['keys']) ? $form_values['keys'] : '',
        '#size' => 40,
        '#autocomplete_path' => 'user/autocomplete',
      ];
    }
    else {
      $form['keys'] = [
        '#type' => 'textfield',
        '#default_value' => isset($form_values['keys'])? $form_values['keys'] : '',
        '#size' => 40,
      ];
    }

    $form['search'] = [
      '#type' => 'submit',
      '#value' => t('Search'),
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Save Grants'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $form_values = $form_state->getValues();
    $allowed_grants = [];
    // Save priority.
    $this->config('nodeaccess.settings')->set('nodeaccess-priority', $form_values['priority']);
    // Save preserve.
    $this->config('nodeaccess.settings')->set('nodeaccess-preserve', $form_values['preserve']);
    // Save allowed grants.
    foreach ($form_values['grant'] as $id => $val) {
      $allowed_grants[$id] = $val;
    }
    $this->config('nodeaccess.settings')->set('nodeaccess-grants', $allowed_grants);
    // Save allowed roles, role aliases and weights.
    $alias_prefs = [];
    $allowed_roles = [];
    foreach ($form_values['role'] as $id => $val) {
      $allowed_roles[$id] = $val['allow'];
      // Save alias and weight only for allowed roles.
      if ($val['allow']) {
        // If alias is empty, default to role name.
        if ($val['alias']) {
          $alias_prefs[$id]['name'] = $val['alias'];
        }
        else {
          $alias_prefs[$id]['name'] = $val['name'];
        }
        $alias_prefs[$id]['weight'] = $val['weight'];
      }
      else {
        // Otherwise, we only save alias if one was specified.
        if ($val['alias']) {
          $alias_prefs[$id]['name'] = $val['alias'];
          $alias_prefs[$id]['weight'] = $val['weight'];
        }
      }
    }
    $this->config('nodeaccess.settings')->set('nodeaccess-roles', $allowed_roles);
    nodeaccess_save_role_aliases($alias_prefs);
    // Save author and role permissions for each node type.
    $author_prefs = [];
    foreach (node_type_get_types() as $type => $name) {
      $grants = [];
      foreach ($form_values[$type]['roles'] as $role => $val) {
        $grants[] = [
          'gid' => $role,
          'realm' => 'nodeaccess_rid',
          'grant_view' => $val['grant_view'],
          'grant_update' => $val['grant_update'],
          'grant_delete' => $val['grant_delete'],
        ];
      }
      $this->config('nodeaccess.settings')->set('nodeaccess_' . $type, $grants);
      $author_prefs[$type] = $form_values[$type]['author'];
      // Also save userreference default permissions if enabled.
      if ($this->moduleHandler->moduleExists('user_reference') && isset($form_values[$type]['user_reference'])) {
        $user_reference_grants = [];
        foreach ($form_values[$type]['user_reference'] as $user_reference_field => $val) {
          $user_reference_grants[$user_reference_field] = [
            'gid' => 'nodeaccess_uid',
            'enabled' => $val['enabled'],
            'grant_view' => $val['grant_view'],
            'grant_update' => $val['grant_update'],
            'grant_delete' => $val['grant_delete'],
          ];
        }
        $this->config('nodeaccess.settings')->set('nodeaccess_' . $type . '_user_reference', $user_reference_grants);
      }
    }
    $this->config('nodeaccess.settings')->set('nodeaccess_authors', $author_prefs);

    // Save allowed node type grant tab.
    $allowed_types = [];
    foreach ($form_values['tabs']['show'] as $type => $value) {
      $allowed_types[$type] = (bool) $value;
    }

    nodeaccess_set_type_grants($allowed_types);
    $this->config('nodeaccess.settings')->save();
    drupal_set_message(t('Grants saved.'));
    parent::submitForm($form, $form_state);
  }

}



/**
 * Menu callback. Draws the grant tab.
 *
 * @param $form
 * @param $form_state
 * @param $node
 *
 * @return mixed
 */
function nodeaccess_grants_form($form, &$form_state, $node) {
  $config = \Drupal::configFactory()->getEditable('nodeaccess.settings');
  $form_values = &$form_state['values'];

  if (!$form_values) {
    $form_values = [];
    $grants = nodeaccess_get_grants($node);
    $form_values['rid'] = isset($grants['rid']) ? $grants['rid'] : array();
    $form_values['uid'] = isset($grants['uid']) ? $grants['uid'] : array();
  }
  elseif ($form_values['keys']) {
    // @todo rewrite
    $params = [];
    $sql = "SELECT uid, name FROM {users} WHERE name LIKE :name";
    $name = preg_replace('!\*+!', '%', $form_values['keys']);
    $params[':name'] = $name;

    if (isset($form_values['uid']) && count($form_values['uid'])) {
      $sql .= ' AND uid NOT IN (:uid)';
      $params[':uid'] = array_keys($form_values['uid']);
    }

    $result = db_query($sql, $params);
    foreach ($result as $account) {
      $form_values['uid'][$account->uid] = [
        'name' => $account->name,
        'keep' => 1,
        'grant_view' => isset($form_values['rid'][DRUPAL_AUTHENTICATED_RID]['grant_view']) ?
          $form_values['rid'][DRUPAL_AUTHENTICATED_RID]['grant_view'] : 0,
        'grant_update' => isset($form_values['rid'][DRUPAL_AUTHENTICATED_RID]['grant_update']) ?
          $form_values['rid'][DRUPAL_AUTHENTICATED_RID]['grant_update'] : 0,
        'grant_delete' => isset($form_values['rid'][DRUPAL_AUTHENTICATED_RID]['grant_delete']) ?
          $form_values['rid'][DRUPAL_AUTHENTICATED_RID]['grant_delete'] : 0,
      ];
    }
  }

  if (!isset($form_values['rid'])) {
    $form_values['rid'] = [];
  }

  if (!isset($form_values['uid'])) {
    $form_values['uid'] = [];
  }

  $roles = $form_values['rid'];
  $users = $form_values['uid'];

  $form['nid'] = [
    '#type' => 'hidden',
    '#value' => $node->nid,
  ];

  $allowed_roles = $config->get('nodeaccess_roles');
  $allowed_grants = $config->get('nodeaccess_grants');
  // If $preserve is TRUE, the fields the user is not allowed to view or
  // edit are included in the form as hidden fields to preserve them.
  $preserve = $config->get('nodeaccess_preserve');
  // Roles table.
  if (is_array($roles)) {
    $form['rid'] = ['#tree' => TRUE];
    foreach ($roles as $key => $field) {
      if (isset($allowed_roles[$key]) && $allowed_roles[$key]) {
        $form['rid'][$key]['name'] = [
          '#type' => 'hidden',
          '#value' => $field['name'],
        ];
        if ($allowed_grants['view']) {
          $form['rid'][$key]['grant_view'] = [
            '#type' => 'checkbox',
            '#default_value' => $field['grant_view'],
          ];
        }
        elseif ($preserve) {
          $form['rid'][$key]['grant_view'] = [
            '#type' => 'hidden',
            '#value' => $field['grant_view'],
          ];
        }
        if ($allowed_grants['edit']) {
          $form['rid'][$key]['grant_update'] = [
            '#type' => 'checkbox',
            '#default_value' => $field['grant_update'],
          ];
        }
        elseif ($preserve) {
          $form['rid'][$key]['grant_update'] = [
            '#type' => 'hidden',
            '#value' => $field['grant_update'],
          ];
        }
        if ($allowed_grants['delete']) {
          $form['rid'][$key]['grant_delete'] = [
            '#type' => 'checkbox',
            '#default_value' => $field['grant_delete'],
          ];
        }
        elseif ($preserve) {
          $form['rid'][$key]['grant_delete'] = [
            '#type' => 'hidden',
            '#value' => $field['grant_delete'],
          ];
        }
      }
      elseif ($preserve) {
        $form['rid'][$key]['name'] = [
          '#type' => 'hidden',
          '#value' => $field['name'],
        ];
        $form['rid'][$key]['grant_view'] = [
          '#type' => 'hidden',
          '#value' => $field['grant_view'],
        ];
        $form['rid'][$key]['grant_update'] = [
          '#type' => 'hidden',
          '#value' => $field['grant_update'],
        ];
        $form['rid'][$key]['grant_delete'] = [
          '#type' => 'hidden',
          '#value' => $field['grant_delete'],
        ];
      }
    }
  }

  // Users table.
  if (is_array($users)) {
    $form['uid'] = ['#tree' => TRUE];
    foreach ($users as $key => $field) {
      $form['uid'][$key]['name'] = [
        '#type' => 'hidden',
        '#value' => $field['name'],
      ];
      $form['uid'][$key]['keep'] = [
        '#type' => 'checkbox',
        '#default_value' => $field['keep'],
      ];
      if ($allowed_grants['view']) {
        $form['uid'][$key]['grant_view'] = [
          '#type' => 'checkbox',
          '#default_value' => $field['grant_view'],
        ];
      }
      elseif ($preserve) {
        $form['uid'][$key]['grant_view'] = [
          '#type' => 'hidden',
          '#value' => $field['grant_view'],
        ];
      }
      if ($allowed_grants['edit']) {
        $form['uid'][$key]['grant_update'] = [
          '#type' => 'checkbox',
          '#default_value' => $field['grant_update'],
        ];
      }
      elseif ($preserve) {
        $form['uid'][$key]['grant_update'] = [
          '#type' => 'hidden',
          '#value' => $field['grant_update'],
        ];
      }
      if ($allowed_grants['delete']) {
        $form['uid'][$key]['grant_delete'] = [
          '#type' => 'checkbox',
          '#default_value' => $field['grant_delete'],
        ];
      }
      elseif ($preserve) {
        $form['uid'][$key]['grant_delete'] = [
          '#type' => 'hidden',
          '#value' => $field['grant_delete'],
        ];
      }
    }
  }

  // Autocomplete returns errors if users don't have access to profiles.
  if (user_access('access user profiles')) {
    $form['keys'] = [
      '#type' => 'textfield',
      '#default_value' => isset($form_values['keys']) ? $form_values['keys'] : '',
      '#size' => 40,
      '#autocomplete_path' => 'user/autocomplete',
    ];
  }
  else {
    $form['keys'] = [
      '#type' => 'textfield',
      '#default_value' => isset($form_values['keys'])? $form_values['keys'] : '',
      '#size' => 40,
    ];
  }

  $form['search'] = [
    '#type' => 'submit',
    '#value' => t('Search'),
  ];

  $form['submit'] = [
    '#type' => 'submit',
    '#value' => t('Save Grants'),
  ];

  return $form;
}


/**
 * Validate function for nodeaccess_grants_form.
 *
 * @param array $form
 * @param array &$form_state
 */
function nodeaccess_grants_form_validate($form, &$form_state) {
  $form_values = &$form_state['values'];

  // Delete unkept users.
  if (isset($form_values['uid']) && is_array($form_values['uid'])) {
    foreach ($form_values['uid'] as $uid => $row) {
      if (!$row['keep']) {
        unset($form_values['uid'][$uid]);
      }
    }
  }

  if (!isset($form_values['uid'])) {
    unset($form_values['uid']);
  }

}


/**
 * Submit function for nodeaccess_grants_form.
 *
 * @param array $form
 * @param array &$form_state
 */
function nodeaccess_grants_form_submit($form, &$form_state) {

  if ($form_state['clicked_button']['#id'] == 'edit-search') {
    $form_state['rebuild'] = TRUE;
    $form_state['storage']['values'] = $form_state['values'];
  }
  else {
    unset($form_state['rebuild']);
    _nodeaccess_grants_form_submit($form, $form_state);
    drupal_set_message(t('Grants saved.'));
  }
}
