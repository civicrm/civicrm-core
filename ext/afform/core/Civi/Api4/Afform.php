<?php

namespace Civi\Api4;

use Civi\Api4\Generic\AutocompleteAction;
use Civi\Api4\Generic\BasicGetFieldsAction;
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
    return (new Generic\BasicGetFieldsAction('Afform', __FUNCTION__, function(BasicGetFieldsAction $self) {
      $fields = [
        [
          'name' => 'name',
          'title' => E::ts('Name'),
          'input_type' => 'Text',
        ],
        [
          'name' => 'type',
          'title' => E::ts('Type'),
          'pseudoconstant' => ['optionGroupName' => 'afform_type'],
          'default_value' => 'form',
          'input_type' => 'Select',
        ],
        [
          'name' => 'requires',
          'title' => E::ts('Requires'),
          'data_type' => 'Array',
          'description' => 'Angular module dependencies; calculated at runtime',
        ],
        [
          'name' => 'entity_type',
          'title' => E::ts('Block Entity'),
          'description' => 'Block used for this entity type',
        ],
        [
          'name' => 'join_entity',
          'title' => E::ts('Join Entity'),
          'description' => 'Used for blocks that join a sub-entity (e.g. Emails for a Contact)',
        ],
        [
          'name' => 'title',
          'title' => E::ts('Title'),
          'required' => $self->getAction() === 'create',
          'input_type' => 'Text',
        ],
        [
          'name' => 'description',
          'title' => E::ts('Description'),
          'input_type' => 'Text',
        ],
        [
          'name' => 'placement',
          'title' => E::ts('Placement'),
          'pseudoconstant' => ['optionGroupName' => 'afform_placement'],
          'data_type' => 'Array',
        ],
        [
          'name' => 'placement_filters',
          'title' => E::ts('Placement Filters'),
          'data_type' => 'Array',
          'description' => 'E.g. contact_type, case_type, event_type, etc.',
        ],
        [
          'name' => 'placement_weight',
          'title' => E::ts('Placement Order'),
          'data_type' => 'Integer',
        ],
        [
          'name' => 'tags',
          'title' => E::ts('Tags'),
          'pseudoconstant' => [
            'callback' => [Utils\AfformTags::class, 'getTagOptions'],
            'suffixes' => [
              'name',
              'label',
              'color',
              'description',
            ],
          ],
          'data_type' => 'Array',
          'input_type' => 'Select',
        ],
        [
          'name' => 'icon',
          'title' => E::ts('Icon'),
          'description' => 'Icon shown in the placement',
        ],
        [
          'name' => 'server_route',
          'title' => E::ts('Page Route'),
        ],
        [
          'name' => 'is_public',
          'title' => E::ts('Is Public'),
          'data_type' => 'Boolean',
          'default_value' => FALSE,
        ],
        [
          'name' => 'permission',
          'title' => E::ts('Permission'),
          'data_type' => 'Array',
          'default_value' => ['access CiviCRM'],
        ],
        [
          'name' => 'permission_operator',
          'title' => E::ts('Permission Operator'),
          'data_type' => 'String',
          'default_value' => 'AND',
          'options' => \CRM_Core_SelectValues::andOr(),
        ],
        [
          'name' => 'redirect',
          'title' => E::ts('Post-Submit Page'),
        ],
        [
          'name' => 'submit_enabled',
          'title' => E::ts('Allow Submissions'),
          'data_type' => 'Boolean',
          'default_value' => TRUE,
        ],
        [
          'name' => 'submit_limit',
          'title' => E::ts('Max Submissions (total)'),
          'data_type' => 'Integer',
        ],
        [
          'name' => 'submit_limit_per_user',
          'title' => E::ts('Max Submissions (per user)'),
          'data_type' => 'Integer',
        ],
        [
          'name' => 'create_submission',
          'title' => E::ts('Log Submissions'),
          'data_type' => 'Boolean',
          'description' => E::ts('Keep a log of the date, time, user, and items saved by each form submission.'),
        ],
        [
          'name' => 'manual_processing',
          'data_type' => 'Boolean',
        ],
        [
          'name' => 'allow_verification_by_email',
          'data_type' => 'Boolean',
        ],
        [
          'name' => 'email_confirmation_template_id',
          'data_type' => 'Integer',
        ],
        [
          'title' => E::ts('Autosave Draft'),
          'name' => 'autosave_draft',
          'data_type' => 'Boolean',
          'description' => E::ts('For authenticated users, form will auto-save periodically.'),
        ],
        [
          'name' => 'navigation',
          'title' => E::ts('Navigation Menu'),
          'data_type' => 'Array',
          'description' => 'Insert into navigation menu {parent: string, label: string, weight: int}',
        ],
        [
          'name' => 'layout',
          'title' => E::ts('Layout'),
          'data_type' => 'Array',
          'description' => 'HTML form layout; format is controlled by layoutFormat param',
        ],
        [
          'name' => 'modified_date',
          'title' => E::ts('Date Modified'),
          'data_type' => 'Timestamp',
          'readonly' => TRUE,
        ],
        [
          'name' => 'confirmation_type',
          'pseudoconstant' => ['optionGroupName' => 'afform_confirmation_type'],
          'default_value' => 'redirect_to_url',
        ],
        [
          'name' => 'confirmation_message',
          'title' => E::ts('Confirmation Message'),
          'input_type' => 'Text',
        ],
        [
          'name' => 'created_id',
          'title' => ts('Created By Contact ID'),
          'data_type' => 'Integer',
          'fk_entity' => 'Contact',
          'fk_column' => 'id',
          'input_type' => 'EntityRef',
          'label' => ts('Created By'),
          'default_value' => NULL,
          'readonly' => TRUE,
          'required' => FALSE,
        ],
        [
          'name' => 'locale',
          'title' => ts('Locale'),
          'data_type' => 'String',
          'input_type' => 'Select',
          'required' => \CRM_Core_I18n::isMultiLingual(),
        ],
      ];
      // Calculated fields returned by get action
      if ($self->getAction() === 'get') {
        $fields[] = [
          'name' => 'module_name',
          'type' => 'Extra',
          'description' => 'Name of generated Angular module (CamelCase)',
          'readonly' => TRUE,
        ];
        $fields[] = [
          'name' => 'directive_name',
          'type' => 'Extra',
          'description' => 'Html tag name to invoke this form (dash-case)',
          'readonly' => TRUE,
        ];
        $fields[] = [
          'name' => 'submission_count',
          'type' => 'Extra',
          'data_type' => 'Integer',
          'input_type' => 'Number',
          'description' => 'Number of submission records for this form',
          'readonly' => TRUE,
        ];
        $fields[] = [
          'name' => 'user_submission_count',
          'type' => 'Extra',
          'data_type' => 'Integer',
          'input_type' => 'Number',
          'description' => 'Number of submission records for the current user',
          'readonly' => TRUE,
        ];
        $fields[] = [
          'name' => 'submission_date',
          'type' => 'Extra',
          'data_type' => 'Timestamp',
          'input_type' => 'Date',
          'description' => 'Date & time of last form submission',
          'readonly' => TRUE,
        ];
        $fields[] = [
          'name' => 'submit_currently_open',
          'type' => 'Extra',
          'data_type' => 'Boolean',
          'input_type' => 'Select',
          'description' => 'Based on settings and current submission count, is the form open for submissions',
          'readonly' => TRUE,
        ];
        $fields[] = [
          'name' => 'has_local',
          'type' => 'Extra',
          'data_type' => 'Boolean',
          'description' => 'Whether a local copy is saved on site',
          'readonly' => TRUE,
        ];
        $fields[] = [
          'name' => 'has_base',
          'type' => 'Extra',
          'data_type' => 'Boolean',
          'description' => 'Is provided by an extension',
          'readonly' => TRUE,
        ];
        $fields[] = [
          'name' => 'base_module',
          'type' => 'Extra',
          'data_type' => 'String',
          'description' => 'Name of extension which provides this form',
          'readonly' => TRUE,
          'pseudoconstant' => ['callback' => ['CRM_Core_BAO_Managed', 'getBaseModules']],
          'input_type' => 'Select',
        ];
        $fields[] = [
          'name' => 'search_displays',
          'type' => 'Extra',
          'data_type' => 'Array',
          'readonly' => TRUE,
          'description' => 'Embedded search displays, formatted like ["search-name.display-name"]',
        ];
      }

      return $fields;
    }))->setCheckPermissions($checkPermissions);
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
