<?php

namespace Civi\Api4;

use Civi\Api4\Generic\AutocompleteAction;
use Civi\Api4\Generic\BasicGetFieldsAction;
use Civi\Schema\EntityRepository;
use Civi\Schema\FileEntityMetadata;
use CRM_Afform_ExtensionUtil as E;

/**
 * User-configurable forms.
 *
 * Afform stands for *The Affable Administrative Angular Form Framework*.
 *
 * This API provides actions for
 *   1. **_Managing_ forms:**
 *      The `create`, `get`, `save`, `update`, & `revert` actions read/write form html & json files.
 *   2. **_Using_ forms:**
 *      The `prefill` and `submit` actions are used for preparing forms and processing submissions.
 *
 * @see https://lab.civicrm.org/extensions/afform
 * @labelField title
 * @iconField type:icon
 * @since 5.31
 * @package Civi\Api4
 */
class Afform extends Generic\AbstractEntity {

  /**
   * @param bool $checkPermissions
   * @return Action\Afform\Get
   */
  public static function get($checkPermissions = TRUE) {
    return (new Action\Afform\Get('Afform', __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * @param bool $checkPermissions
   * @return Action\Afform\Create
   */
  public static function create($checkPermissions = TRUE) {
    return (new Action\Afform\Create('Afform', __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * @param bool $checkPermissions
   * @return Action\Afform\Update
   */
  public static function update($checkPermissions = TRUE) {
    return (new Action\Afform\Update('Afform', __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * @param bool $checkPermissions
   * @return Action\Afform\Save
   */
  public static function save($checkPermissions = TRUE) {
    return (new Action\Afform\Save('Afform', __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * @param bool $checkPermissions
   * @return \Civi\Api4\Generic\AutocompleteAction
   */
  public static function autocomplete($checkPermissions = TRUE) {
    return (new AutocompleteAction('Afform', __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * @param bool $checkPermissions
   * @return Action\Afform\Convert
   */
  public static function convert($checkPermissions = TRUE) {
    return (new Action\Afform\Convert('Afform', __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * @param bool $checkPermissions
   * @return Action\Afform\Prefill
   */
  public static function prefill($checkPermissions = TRUE) {
    return (new Action\Afform\Prefill('Afform', __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * @param bool $checkPermissions
   * @return Action\Afform\Submit
   */
  public static function submit($checkPermissions = TRUE) {
    return (new Action\Afform\Submit('Afform', __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * @param bool $checkPermissions
   * @return Action\Afform\Process
   */
  public static function process($checkPermissions = TRUE) {
    return (new Action\Afform\Process('Afform', __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * @param bool $checkPermissions
   * @return Action\Afform\SubmitFile
   */
  public static function submitFile($checkPermissions = TRUE) {
    return (new Action\Afform\SubmitFile('Afform', __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * @param bool $checkPermissions
   * @return Action\Afform\SubmitDraft
   */
  public static function submitDraft($checkPermissions = TRUE) {
    return (new Action\Afform\SubmitDraft('Afform', __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * @param bool $checkPermissions
   * @return Action\Afform\GetOptions
   */
  public static function getOptions($checkPermissions = TRUE) {
    return (new Action\Afform\GetOptions('Afform', __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * @param bool $checkPermissions
   * @return Action\Afform\Revert
   */
  public static function revert($checkPermissions = TRUE) {
    return (new Action\Afform\Revert('Afform', __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * @param bool $checkPermissions
   * @return Generic\BasicGetFieldsAction
   */
  public static function getFields($checkPermissions = TRUE) {
    return (new Generic\BasicGetFieldsAction('Afform', __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  public static function getBasicMeta() {
    return [
      'name' => 'Afform',
      'getInfo' => fn() => [
        'title' => E::ts('Afform'),
        'title_plural' => E::ts('Afforms'),
        'description' => E::ts('FormBuilder forms'),
        'log' => FALSE,
      ],
      'getPaths' => fn() => [
        'view' => '[server_route]',
        'edit' => 'civicrm/admin/afform#/edit/[name]',
      ],
      // TODO: ensure this is updated to most recent list!
      'getFields' => fn() => [
        'name' => [
          'title' => E::ts('Name'),
          'data_type' => 'String',
          'input_type' => 'Text',
          'required' => TRUE,
          'description' => E::ts('Afform file name'),
          'primary_key' => TRUE,
        ],
        'type' => [
          'title' => E::ts('Type'),
          'data_type' => 'String',
          'pseudoconstant' => ['option_group_name' => 'afform_type'],
          'default_value' => 'form',
        ],
        'requires' => [
          'title' => E::ts('Requires'),
          'data_type' => 'Array',
        ],
        'entity_type' => [
          'title' => E::ts('Block Entity'),
          'data_type' => 'String',
          'description' => E::ts('Block used for this entity type'),
        ],
        'join_entity' => [
          'title' => E::ts('Join Entity'),
          'data_type' => 'String',
          'description' => E::ts('Used for blocks that join a sub-entity (e.g. Emails for a Contact)'),
        ],
        'title' => [
          'title' => E::ts('Title'),
          'data_type' => 'String',
          'required' => TRUE,
        ],
        'description' => [
          'title' => E::ts('Description'),
          'data_type' => 'String',
        ],
        'placement' => [
          'title' => E::ts('Placement'),
          'pseudoconstant' => ['option_group_name' => 'afform_placement'],
          'data_type' => 'Array',
        ],
        'summary_contact_type' => [
          'title' => E::ts('Summary Contact Type'),
          'data_type' => 'Array',
          'pseudoconstant' => [
            'table' => 'civicrm_contact_type',
            'key_column' => 'name',
            'label_column' => 'label',
            'icon_column' => 'icon',
          ],
        ],
        'summary_weight' => [
          'title' => E::ts('Order'),
          'data_type' => 'Integer',
        ],
        'icon' => [
          'title' => E::ts('Icon'),
          'data_type' => 'String',
          'description' => E::ts('Icon shown in the contact summary tab'),
        ],
        'server_route' => [
          'title' => E::ts('Page Route'),
          'data_type' => 'String',
        ],
        'is_public' => [
          'title' => E::ts('Is Public'),
          'data_type' => 'Boolean',
          'default_value' => FALSE,
        ],
        'permission' => [
          'title' => E::ts('Permission'),
          'data_type' => 'Array',
          'default_value' => ['access CiviCRM'],
        ],
        'permission_operator' => [
          'title' => E::ts('Permission Operator'),
          'data_type' => 'String',
          'default_value' => 'AND',
          'pseudoconstant' => [
            'callback' => ['CRM_Core_SelectValues', 'andOr'],
          ],
        ],
        'redirect' => [
          'title' => E::ts('Post-Submit Page'),
          'data_type' => 'String',
        ],
        'submit_enabled' => [
          'title' => E::ts('Allow Submissions'),
          'data_type' => 'Boolean',
          'default_value' => TRUE,
        ],
        'submit_limit' => [
          'title' => E::ts('Maximum Submissions'),
          'data_type' => 'Integer',
        ],
        'create_submission' => [
          'title' => E::ts('Log Submissions'),
          'data_type' => 'Boolean',
        ],
        'manual_processing' => [
          'title' => E::ts('Manual Processing'),
          'data_type' => 'Boolean',
        ],
        'allow_verification_by_email' => [
          'title' => E::ts('Allow Verification by Email'),
          'data_type' => 'Boolean',
        ],
        'email_confirmation_template_id' => [
          'title' => E::ts('Email Confirmation Template'),
          'data_type' => 'Integer',
        ],
        'navigation' => [
          'title' => E::ts('Navigation Menu'),
          'data_type' => 'Array',
          'description' => E::ts('Insert into navigation menu {parent: string, label: string, weight: int}'),
        ],
        'layout' => [
          'title' => E::ts('Layout'),
          'data_type' => 'Array',
          'description' => E::ts('HTML form layout; format is controlled by layoutFormat param'),
        ],
        // Calculated readonly fields
        'modified_date' => [
          'title' => E::ts('Date Modified'),
          'data_type' => 'Timestamp',
          'readonly' => TRUE,
        ],
        'module_name' => [
          'title' => E::ts('Module Name'),
          'data_type' => 'String',
          'description' => E::ts('Name of generated Angular module (CamelCase)'),
          'readonly' => TRUE,
        ],
        'directive_name' => [
          'title' => E::ts('Directive Name'),
          'data_type' => 'String',
          'description' => E::ts('Html tag name to invoke this form (dash-case)'),
          'readonly' => TRUE,
        ],
        'submission_count' => [
          'title' => E::ts('Submission Count'),
          'data_type' => 'Integer',
          'input_type' => 'Number',
          'description' => E::ts('Number of submission records for this form'),
          'readonly' => TRUE,
        ],
        'submission_date' => [
          'title' => E::ts('Submission Date'),
          'data_type' => 'Timestamp',
          'input_type' => 'Date',
          'description' => E::ts('Date & time of last form submission'),
          'readonly' => TRUE,
        ],
        'submit_currently_open' => [
          'title' => E::ts('Submit Currently Open'),
          'data_type' => 'Boolean',
          'input_type' => 'Select',
          'description' => E::ts('Based on settings and current submission count, is the form open for submissions'),
          'readonly' => TRUE,
        ],
        'has_local' => [
          'title' => E::ts('Saved Locally'),
          'data_type' => 'Boolean',
          'description' => E::ts('Whether a local copy is saved on site'),
          'readonly' => TRUE,
        ],
        'has_base' => [
          'title' => E::ts('Packaged'),
          'data_type' => 'Boolean',
          'description' => E::ts('Is provided by an extension'),
          'readonly' => TRUE,
        ],
        'base_module' => [
          'title' => E::ts('Extension'),
          'data_type' => 'String',
          'description' => E::ts('Name of extension which provides this form'),
          'readonly' => TRUE,
          'pseudoconstant' => [
            'callback' => ['CRM_Core_BAO_Managed', 'getBaseModules'],
          ],
        ],
        'search_displays' => [
          'title' => E::ts('Search Displays'),
          'data_type' => 'Array',
          'readonly' => TRUE,
          'description' => E::ts('Embedded search displays, formatted like ["search-name.display-name"]'),
        ],
        [
          'name' => 'confirmation_type',
          'pseudoconstant' => ['optionGroupName' => 'afform_confirmation_type'],
          'default_value' => 'redirect_to_url',
        ],
      ],
    ];
  }

  /**
   * @return array
   */
  public static function permissions() {
    return [
      'meta' => ['access CiviCRM'],
      'default' => ['manage own afform'],
      // These all check form-level permissions
      'get' => [],
      'getOptions' => [],
      'prefill' => [],
      'submit' => [],
      'submitFile' => [],
      'submitDraft' => [],
    ];
  }

  /**
   * @inheritDoc
   */
  public static function getInfo() {
    $info = parent::getInfo();
    $info['primary_key'] = ['name'];
    return $info;
  }

}
