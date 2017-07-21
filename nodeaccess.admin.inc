<?php

/**
 * @file Nodeaccess admin forms.
 */

use Drupal\Core\Extension\ModuleHandler;
use Drupal\Core\Form\FormStateInterface;

/**
 * Menu callback. Draws the admin page.
 */
function nodeaccess_admin() {
  return drupal_get_form('nodeaccess_admin_form');
}


/**
 * Menu callback. Draws the admin page.
 *
 * @param $form
 * @param $form_state
 *
 * @return array $form
 */
function nodeaccess_admin_form(array $form, FormStateInterface $form_state) {

  $config = \Drupal::configFactory()->getEditable('nodeaccess.settings');
  $show = $config->get('nodeaccess-types');
  $roles = nodeaccess_get_role_aliases();
  $allowed_roles = $config->get('nodeaccess-roles');
  $allowed_grants = $config->get('nodeaccess-grants');

  $form['priority'] = [
    '#type' => 'checkbox',
    '#title' => t('Give node grants priority'),
    '#default_value' => $config->get('nodeaccess-priority'),
    '#description' => t('If you are only using this access control module,
      you can safely ignore this. If you are using multiple access control
      modules, and you want the grants given on individual nodes to override
      any grants given by other modules, you should check this box.'),
  ];

  // Select whether to preserve hidden grants.
  $form['preserve'] = [
    '#type' => 'checkbox',
    '#title' => t('Preserve hidden grants'),
    '#default_value' => $config->get('nodeaccess-preserve'),
    '#description' => '<small>' . t('If you check this box, any hidden grants
      are preserved when you save grants. Otherwise all grants users are not
      allowed to view or edit are revoked on save.') . '</small>',
  ];

  // Select permissions you want to allow users to view and edit.
  $form['grant'] = [
    '#type' => 'fieldset',
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
    '#title' => t('Allowed Grants'),
    '#tree' => TRUE,
    '#description' => '<small>' . t('The selected grants will be listed on
      individual node grants. If you wish for certain grants to be hidden from
      users on the node grants tab, make sure they are not selected here.') .
      '</small>',
  ];
  $form['grant']['view'] = [
    '#type' => 'checkbox',
    '#title' => t('View'),
    '#default_value' => $allowed_grants['view'],
  ];
  $form['grant']['edit'] = [
    '#type' => 'checkbox',
    '#title' => t('Edit'),
    '#default_value' => $allowed_grants['edit'],
  ];
  $form['grant']['delete'] = [
    '#type' => 'checkbox',
    '#title' => t('Delete'),
    '#default_value' => $allowed_grants['delete'],
  ];

  // Select roles the permissions of which you want to allow users to
  // view and edit, and the aliases and weights of those roles.
  $form['role'] = [
    '#type' => 'fieldset',
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
    '#title' => t('Allowed Roles'),
    '#tree' => TRUE,
    '#theme' => 'nodeaccess_admin_form_roles',
    '#description' => t('The selected roles will be listed on individual node
      grants. If you wish for certain roles to be hidden from users on the node
      grants tab, make sure they are not selected here. You may also provide an
      alias for each role to be displayed to the user and a weight to order them
      by. This is useful if your roles have machine-readable names not intended
      for human users.'),
  ];

  foreach ($roles as $id => $role) {
    // Catch NULL values.
    if (!$role['alias']) {
      $role['alias'] = '';
    }
    if (!$role['weight']) {
      $role['weight'] = 0;
    }
    $form['role'][$id]['name'] = [
      '#type' => 'hidden',
      '#value' => $role['name'],
    ];
    $form['role'][$id]['allow'] = [
      '#type' => 'checkbox',
      '#title' => check_plain($role['name']),
      '#default_value' => isset($allowed_roles[$id]) ? $allowed_roles[$id] : 0,
    ];
    $form['role'][$id]['alias'] = [
      '#type' => 'textfield',
      '#default_value' => $role['alias'],
      '#size' => 50,
      '#maxlength' => 50,
    ];
    $form['role'][$id]['weight'] = [
      '#type' => 'weight',
      '#default_value' => $role['weight'],
      '#delta' => 10,
    ];
  }

  // Grant tab to node types.
  $form['nodeaccess']['tabs'] = [
    '#type' => 'fieldset',
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
    '#title' => t('Content Type Grant Tab Access'),
    '#tree' => TRUE,
    '#description' => t('Show grant tab for the following node types.'),
  ];

  $options = [];
  foreach (node_type_get_types() as $type => $bundle) {
    $options[$type] = [
      'content_type' => check_plain($bundle->name),
    ];
  }
  $form['nodeaccess']['tabs']['show'] = [
    '#type' => 'tableselect',
    '#header' => [
      'content_type' => t('Content type'),
    ],
    '#options' => $options,
    '#default_value' => $show,
    '#empty' => t('No content types to add a grant tab.'),
  ];

  // Generate fieldsets for each node type.
  foreach (node_type_get_types() as $type => $bundle) {
    $form['nodeaccess'][$type] = [
      '#type' => 'fieldset',
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
      '#title' => check_plain($bundle->name),
      '#tree' => TRUE,
      '#theme' => 'nodeaccess_admin_form_types',
    ];

    // Set default author permissions for node type.
    $author_prefs = $config->get('nodeaccess_authors');
    $form['nodeaccess'][$type]['author']['grant_view'] = [
      '#type' => 'checkbox',
      '#default_value' => $author_prefs[$type]['grant_view'],
    ];
    $form['nodeaccess'][$type]['author']['grant_update'] = [
      '#type' => 'checkbox',
      '#default_value' => $author_prefs[$type]['grant_update'],
    ];
    $form['nodeaccess'][$type]['author']['grant_delete'] = [
      '#type' => 'checkbox',
      '#default_value' => $author_prefs[$type]['grant_delete'],
    ];

    $perms = $config->get('nodeaccess_' . $type);
    foreach ($perms as $perm) {
      $opts[$perm['gid']] = $perm;
    }

    // Set default role permissions for node type.
    foreach (user_roles() as $id => $role) {
      $form['nodeaccess'][$type]['roles'][$id]['name'] = ['#markup' => $role];
      $form['nodeaccess'][$type]['roles'][$id]['grant_view'] = [
        '#type' => 'checkbox',
        '#default_value' => isset($opts[$id]['grant_view']) ? $opts[$id]['grant_view'] : 0,
      ];
      $form['nodeaccess'][$type]['roles'][$id]['grant_update'] = [
        '#type' => 'checkbox',
        '#default_value' => isset($opts[$id]['grant_update']) ? $opts[$id]['grant_update'] : 0,
      ];
      $form['nodeaccess'][$type]['roles'][$id]['grant_delete'] = [
        '#type' => 'checkbox',
        '#default_value' => isset($opts[$id]['grant_delete']) ? $opts[$id]['grant_delete'] : 0,
      ];
    }

    // Set the default permissions if userreference exists and is enabled on
    // the content type.
    if ($this->moduleHandler->moduleExists('user_reference')) {
      $bundle = field_extract_bundle('node', $bundle);
      $fields = field_info_instances('node', $bundle);
      $user_reference_perms = $config->get('nodeaccess_' . $type . '_user_reference');

      $field_types = field_info_field_types();

      foreach ($fields as $field) {
        $field = field_info_field($field['field_name']);
        if ($field['type'] == 'user_reference') {
          $enabled = isset($user_reference_perms[$field['field_name']]['enabled']) ?
            $user_reference_perms[$field['field_name']]['enabled'] : 0;
          $view = isset($user_reference_perms[$field['field_name']]['grant_view']) ?
            $user_reference_perms[$field['field_name']]['grant_view'] : 0;
          $update = isset($user_reference_perms[$field['field_name']]['grant_update']) ?
            $user_reference_perms[$field['field_name']]['grant_update'] : 0;
          $delete = isset($user_reference_perms[$field['field_name']]['grant_delete']) ?
            $user_reference_perms[$field['field_name']]['grant_delete'] : 0;

          $form['nodeaccess'][$type]['user_reference'][$field['field_name']]['name'] = [
            '#value' => t($field_types[$field['type']]['label']),
          ];
          $form['nodeaccess'][$type]['user_reference'][$field['field_name']]['enabled'] = [
            '#type' => 'checkbox',
            '#default_value' => $enabled,
          ];
          $form['nodeaccess'][$type]['user_reference'][$field['field_name']]['grant_view'] = [
            '#type' => 'checkbox',
            '#default_value' => $view,
          ];
          $form['nodeaccess'][$type]['user_reference'][$field['field_name']]['grant_update'] = [
            '#type' => 'checkbox',
            '#default_value' => $update,
          ];
          $form['nodeaccess'][$type]['user_reference'][$field['field_name']]['grant_delete'] = [
            '#type' => 'checkbox',
            '#default_value' => $delete,
          ];
        }
      }
    }
  }
  $form['submit'] = [
    '#type' => 'submit',
    '#value' => t('Save Grants'),
  ];
  return $form;
}

/**
 * Submit function for nodeaccess_admin_form.
 *
 * @param $form
 * @param $form_state
 */
function nodeaccess_admin_form_submit($form, $form_state) {
  $config = \Drupal::configFactory()->getEditable('nodeaccess.settings');
  $form_values = $form_state['values'];
  $allowed_grants = [];
  // Save priority.
  $config->set('nodeaccess-priority', $form_values['priority']);
  // Save preserve.
  $config->set('nodeaccess-preserve', $form_values['preserve']);
  // Save allowed grants.
  foreach ($form_values['grant'] as $id => $val) {
    $allowed_grants[$id] = $val;
  }
  $config->set('nodeaccess-grants', $allowed_grants);
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
  $config->set('nodeaccess-roles', $allowed_roles);
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
    $config->set('nodeaccess_' . $type, $grants);
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
      $config->set('nodeaccess_' . $type . '_user_reference', $user_reference_grants);
    }
  }
  $config->set('nodeaccess_authors', $author_prefs);

  // Save allowed node type grant tab.
  $allowed_types = [];
  foreach ($form_values['tabs']['show'] as $type => $value) {
    $allowed_types[$type] = (bool) $value;
  }

  nodeaccess_set_type_grants($allowed_types);

  drupal_set_message(t('Grants saved.'));
}

/**
 * Theme function for nodeaccess_admin_form_roles.
 *
 * @param $variables
 * @throws Exception
 * @return string
 */
function theme_nodeaccess_admin_form_roles($variables) {
  $output = '';
  $form = $variables['form'];
  $rows = [];
  foreach (element_children($form) as $rid) {
    // Classify the weight element for TableDrag.
    $form[$rid]['weight']['#attributes']['class'] = ['roles-order-weight'];

    // Mark the table row as draggable for TableDrag.
    $row = [
      'data' => [],
      'class' => ['draggable'],
    ];
    // Render the table columns.
    $row['data'][] = drupal_render($form[$rid]['allow']);
    $row['data'][] = drupal_render($form[$rid]['alias']);
    $row['data'][] = drupal_render($form[$rid]['weight']);
    $rows[] = $row;
  }

  $header = [t('Allow Role'), t('Alias'), t('Weight')];
  $output .= theme('table', [
    'header' => $header,
    'rows' => $rows,
    'attributes' => array('id' => 'roles-order'),
  ]);
  $output .= drupal_render_children($form);

  // Attach TableDrag to the table ID and contained weight elements.
  drupal_add_tabledrag('roles-order', 'order', 'sibling', 'roles-order-weight');

  return $output;
}


/**
 * Theme function to render admin form.
 *
 * @param $variables
 *
 * @return string
 */
function theme_nodeaccess_admin_form_types($variables) {
  $form = $variables['form'];
  $output = drupal_render($form['show']);
  $roles = element_children($form['roles']);
  $header = [t('Role'), t('View'), t('Edit'), t('Delete')];
  $rows = [];
  foreach ($roles as $role) {
    $row = [];
    $row[] = drupal_render($form['roles'][$role]['name']);
    $row[] = drupal_render($form['roles'][$role]['grant_view']);
    $row[] = drupal_render($form['roles'][$role]['grant_update']);
    $row[] = drupal_render($form['roles'][$role]['grant_delete']);
    $rows[] = $row;
  }
  $output .= theme('table', ['header' => $header, 'rows' => $rows]);
  $header = [t('Author Settings'), t('View'), t('Edit'), t('Delete')];
  $row = [];
  $row[] = t('Node author');
  $row[] = drupal_render($form['author']['grant_view']);
  $row[] = drupal_render($form['author']['grant_update']);
  $row[] = drupal_render($form['author']['grant_delete']);

  $output .= theme('table', ['header' => $header, 'rows' => [$row]]);

  $output .= '<small>' . t('The settings selected for the node author will
    define what permissions the node author has. This cannot be changed on
    individual node grants.') . '</small>';

  if ($this->moduleHandler->moduleExists('user_reference') && isset($form['user_reference'])) {
    $user_reference_fields = element_children($form['user_reference']);
    $header = [t('User Reference Field'),
      t('Enable this field'),
      t('View'),
      t('Edit'),
      t('Delete'),
    ];
    $rows = [];
    foreach ($user_reference_fields as $user_reference_field) {
      $row = [];

      $row[] = $form['user_reference'][$user_reference_field]['name']['#value'];
      $row[] = drupal_render($form['user_reference'][$user_reference_field]['enabled']);
      $row[] = drupal_render($form['user_reference'][$user_reference_field]['grant_view']);
      $row[] = drupal_render($form['user_reference'][$user_reference_field]['grant_update']);
      $row[] = drupal_render_children($form['user_reference'][$user_reference_field]['grant_delete']);
      $rows[] = $row;
    }
    $output .= theme('table', [
      'header' => $header,
      'rows' => $rows,
    ]);
    $output .= '<small>' . t('If enabled, the value of the user reference field
      will be granted the associated permissions. If a user changes the value of
      the user reference field on a node, the associated user will be modified
      in the node-specific access table.') . '</small>';
  }
  return $output;
}
