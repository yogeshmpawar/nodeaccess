<?php

namespace Drupal\nodeaccess\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\Html;
use Drupal\Core\Extension\ModuleHandler;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityFieldManager;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Node Access settings form.
 *
 * @package Drupal\nodeaccess\Form
 */
class NodeAccessAdminSettingsForm extends ConfigFormBase {

  /**
   * ModuleHandler services object.
   *
   * @var \Drupal\Core\Extension\ModuleHandler
   */
  protected $moduleManager;

  /**
   * EntityFieldManager services object.
   *
   * @var \Drupal\Core\Entity\EntityFieldManager
   */
  protected $entityFieldManager;

  /**
   * NodeAccess Admin Settings Form Constructor
   *
   * @param \Drupal\Core\Extension\ModuleHandler $moduleManager
   *    ModuleManager Object.
   *
   * @param \Drupal\Core\Entity\EntityFieldManager $entityFieldManager
   *    EntityFieldManager Object.s
   */
  public function __construct(ModuleHandler $moduleManager, EntityFieldManager $entityFieldManager) {
    $this->moduleHandler = $moduleManager;
    $this->entityFieldManager = $entityFieldManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('module_handler'),
      $container->get('entity_field.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['nodeaccess.settings'];
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::getFormID().
   */
  public function getFormId() {
    return 'nodeaccess_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $show = $this->config('nodeaccess.settings')->get('nodeaccess_types');
    $roles = nodeaccess_get_role_aliases();
    $allowed_roles = $this->config('nodeaccess.settings')->get('nodeaccess_roles');
    $allowed_grants = $this->config('nodeaccess.settings')->get('nodeaccess_grants');

    $form['priority'] = [
      '#type' => 'checkbox',
      '#title' => t('Give node grants priority'),
      '#default_value' => $this->config('nodeaccess.settings')->get('nodeaccess_priority'),
      '#description' => t('If you are only using this access control module,
        you can safely ignore this. If you are using multiple access control
        modules, and you want the grants given on individual nodes to override
        any grants given by other modules, you should check this box.'),
    ];

    // Select whether to preserve hidden grants.
    $form['preserve'] = [
      '#type' => 'checkbox',
      '#title' => t('Preserve hidden grants'),
      '#default_value' => $this->config('nodeaccess.settings')->get('nodeaccess_preserve'),
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
        '#title' => Html::escape($role['name']),
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
        'content_type' => Html::escape($bundle->name),
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
        '#title' => Html::escape($bundle->name),
        '#tree' => TRUE,
        '#theme' => 'nodeaccess_admin_form_types',
      ];

      // Set default author permissions for node type.
      $author_prefs = $this->config('nodeaccess.settings')->get('nodeaccess_authors');
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

      $perms = $this->config('nodeaccess.settings')->get('nodeaccess_' . $type);
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
        $bundle = $type->bundle();
        $entityFieldManager = $this->entityFieldManager;
        $fields = $entityFieldManager->getFieldDefinitions('node', $bundle_machine_name);
        $user_reference_perms = $this->config('nodeaccess.settings')->get('nodeaccess_' . $type . '_user_reference');

        $field_types = \Drupal::service('plugin.manager.field.field_type')->getDefinitions();

        foreach ($fields as $field) {
          $field = FieldStorageConfig::loadByName($entity_type_id, $field['field_name']);
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
