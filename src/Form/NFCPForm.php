<?php

namespace Drupal\nfcp\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\Query\QueryFactoryInterface;

/**
 * Provides a nfcp configuration form.
 */
class NFCPForm extends ConfigFormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityManagerWrapper;

  /**
   * The Query factory service.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactoryInterface
   */
  protected $queryFactory;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManagerWrapper;

  /**
   * Creates a AdminSettingsForm instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_manager_wrapper
   *   The entity type manager.
   * @param \Drupal\Core\Entity\Query\QueryFactoryInterface $query_factory
   *   The Query factory service.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager_wrapper
   *   The entity field manager.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_manager_wrapper,
    QueryFactoryInterface $query_factory,
    EntityFieldManagerInterface $entity_field_manager_wrapper
  ) {
    $this->entityManagerWrapper = $entity_manager_wrapper;
    $this->queryFactory = $query_factory;
    $this->entityFieldManagerWrapper = $entity_field_manager_wrapper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('entity.query.sql'),
      $container->get('entity_field.manager'),
      $container->get('config.factory'),
    );
  }

  /**
   * Get form Id..
   */
  public function getFormId() {
    return 'nfcp_configuration_form';
  }

  /**
   * Build form.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->configFactory()->getEditable('nfcp.configuration');

    $form['general_setting'] = [
      '#type'  => 'fieldset',
      '#title' => $this->t('GENERAL SETTINGS'),
    ];

    $form['general_setting']['hide_nfcp_block_on_complete'] = [
      '#type' => 'checkbox',
      '#option' => ['1'],
      '#default_value' => $config->get('hide_block_on_complete'),
      '#title' => $this->t('Hide Block When Complete.'),
      '#description' => $this->t('When a user reaches 100% complete of their profile, do you want the profile complete percent block to go away? If so, check this box on.'),
    ];

    $form['general_setting']['field_order'] = [
      '#type' => 'radios',
      '#title' => $this->t('Profile Fields Order'),
      '#options' => ['0' => $this->t('Random'), '1' => $this->t('Fixed')],
      '#default_value' => $config->get('field_order') ?: 0,
      '#description' => $this->t('Select to show which field come first.'),
    ];

    $form['general_setting']['open_field_link'] = [
      '#type' => 'radios',
      '#title' => $this->t('Profile Fields Open Link'),
      '#options' => ['0' => $this->t('Same Window'), '1' => $this->t('New Window')],
      '#default_value' => $config->get('open_link') ?: 0,
      '#description' => $this->t('Select to open field link in browser.'),
    ];

    $form['node_types'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Node Types'),
    ];

    $node_types = $this->entityManagerWrapper->getStorage('node_type')->loadMultiple();
    $node_type_options = [];
    // $node_type_bundles = [];
    foreach ($node_types as $node_type) {
      $node_type_options[$node_type->id()] = $node_type->label();
      // $node_type_bundles[$node_type->id()] = $node_type->bundle();
    }

    $form['node_types']['node_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Node type'),
      '#options' => $node_type_options,
      '#default_value' => $config->get('node_type') ?: 'school',
      '#description' => $this->t('Select a node type to be used for completion percentage.'),
      '#recalculate' => TRUE,
      '#ajax' => [
        'callback' => [get_class($this), 'nfcpNodetypeFieldsFormElement'],
        'event' => 'change',
        'progress' => [
          'type' => 'throbber',
          'message' => $this->t('Pls wait awhile...'),
        ],
        'wrapper' => 'node-fields-select',
      ],
    ];

    $form['node_field_setting'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('NODE FIELDS SETTINGS'),
    ];

    $fields = [];
    // $node_fields = [];
    $node_fields_labels = [];

    if ($node_type = $config->get('node_type')) {
      $nids = $this->queryFactory->get($node_type, 'AND')->condition('type', $node_type)->execute();
      if (count($nids) > 0) {
        $node = $this->entityManagerWrapper->getStorage('node')->load(array_pop($nids));
        $fields = $this->entityFieldManagerWrapper->getFieldDefinitions('node', $node->bundle());
      }

      foreach ($fields as $field_name => $field_definition) {
        if (!empty($field_definition->getTargetBundle())) {
          // $node_fields[$field_name] = $field_definition->getType();
          $node_fields_labels[$field_name] = $field_definition->getLabel();
        }
      }
    }

    $form['node_field_setting']['node_fields'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Node Fields'),
      '#options' => $node_fields_labels,
      '#default_value' => $config->get('node_fields') ?: [],
      '#description' => $this->t('Checking a node field below will add that field to the logic of the complete percentage.'),
      '#prefix' => '<div id="node-fields-select">',
      '#suffix' => '</div>',
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->configFactory->getEditable('nfcp.configuration');

    $config->set('field_order', $form_state->getValue('field_order'))
      ->set('open_link', $form_state->getValue('open_field_link'))
      ->set('hide_block_on_complete', $form_state->getValue('hide_nfcp_block_on_complete'))
      ->set('node_type', $form_state->getValue('node_type'))
      ->set('node_fields', $form_state->getValue('node_fields'))
      ->save();
    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function nfcpNodetypeFieldsFormElement(array &$form, FormStateInterface $form_state) {
    // Prepare our textfield. check if the example select
    // field has a selected option.
    if ($node_type = $form_state->getValue('node_type')) {
      $fields = [];
      // $node_fields = [];
      $node_fields_labels = [];

      $nids = $this->queryFactory->get($node_type, 'AND')->condition('type', $node_type)->execute();
      if (count($nids) > 0) {
        $node = $this->node->load(array_pop($nids));
        $fields = $this->entityFieldManagerWrapper->getFieldDefinitions('node', $node->bundle());
      }

      foreach ($fields as $field_name => $field_definition) {
        if (!empty($field_definition->getTargetBundle())) {
          // $node_fields[$field_name] = $field_definition->getType();
          $node_fields_labels[$field_name] = $field_definition->getLabel();
        }
      }

      $form['node_fields'] = [
        '#type' => 'checkboxes',
        '#title' => 'Node Fields',
        '#options' => $node_fields_labels,
        '#default_value' => [],
        '#description' => $this->t('X Checking a node field below will add that field to the logic of the complete percentage.'),
      ];
    }
    return $form['node_fields'];
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [];
  }

}
