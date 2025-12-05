<?php

namespace Civi\Api4;

use Civi\Api4\Generic\BasicGetFieldsAction;
use CRM_CivicrmSearchUi_ExtensionUtil as E;

/**
 * The Report API allows a user to view and run reports on their data
 *
 * It bridges over classic CiviReport and SearchKit-based reports to provide an easy way
 * for users to access their reports.
 *
 * It's also hookable so other extensions could expose Reports (see how `civi_report` does it
 * as model)
 *
 * @searchable primary
 * @labelField title
 * @iconField type:icon
 * @since 6.8
 * @package search_kit_reports
 */
class Report extends Generic\AbstractEntity {

  /**
   * @param bool $checkPermissions
   * @return Action\Report\Get
   */
  public static function get($checkPermissions = TRUE) {
    return (new Action\Report\Get('Report', __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  // TODO: implement editing
  //  /**
  //   * @param bool $checkPermissions
  //   * @return Action\Report\Update
  //   */
  //  public static function update($checkPermissions = TRUE) {
  //    return (new Action\Report\Update('Report', __FUNCTION__))
  //      ->setCheckPermissions($checkPermissions);
  //  }

  // TODO: provide a common facade for running a report in different output formats?
  //  /**
  //   * @param bool $checkPermissions
  //   * @return Action\Report\Run
  //   */
  //  public static function run($checkPermissions = TRUE) {
  //    return (new Action\Report\Run('Report', __FUNCTION__))
  //      ->setCheckPermissions($checkPermissions);
  //  }

  /**
   * @param bool $checkPermissions
   * @return Generic\BasicGetFieldsAction
   */
  public static function getFields($checkPermissions = TRUE) {
    return (new Generic\BasicGetFieldsAction('Report', __FUNCTION__, function(BasicGetFieldsAction $self) {
      $fields = [
        [
          'name' => 'id',
          'title' => E::ts('Generated ID'),
          'input_type' => 'Text',
        ],
        [
          'name' => 'name',
          'title' => E::ts('Machine Name'),
          'input_type' => 'Text',
        ],
        [
          'name' => 'type',
          'title' => E::ts('Type'),
          'pseudoconstant' => ['optionGroupName' => 'report_type'],
          'default_value' => 'civi_report',
          'input_type' => 'Select',
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
        // note: afform reports can have multiple searches => multiple primary entities
        [
          'name' => 'primary_entities',
          'title' => E::ts('Primary Entity'),
          'description' => 'The main entity or entities featured in this report',
          'input_type' => 'Select',
          'pseudoconstant' => [
            'callback' => [self::class, 'getEntityOptions'],
          ],
        ],
        [
          'name' => 'tags',
          'title' => E::ts('Tags'),
          'pseudoconstant' => [
            'callback' => [Utils\AfformTags::class, 'getTagOptions'],
          ],
          'data_type' => 'Array',
          'input_type' => 'Select',
        ],
        [
          'name' => 'icon',
          'title' => E::ts('Icon'),
          'description' => 'Icon',
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
          'name' => 'created_date',
          'title' => E::ts('Date Created'),
          'data_type' => 'Timestamp',
          'readonly' => TRUE,
        ],
        [
          'name' => 'modified_date',
          'title' => E::ts('Date Modified'),
          'data_type' => 'Timestamp',
          'readonly' => TRUE,
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
          'name' => 'extension',
          'type' => 'Extra',
          'description' => 'The extension that provides this report',
          'readonly' => TRUE,
          'input_type' => 'Select',
          'pseudoconstant' => [
            'callback' => [self::class, 'getExtensionOptions'],
          ],
        ],
        [
          'name' => 'view_url',
          'type' => 'Link',
          'description' => 'Link to view the report',
          'readonly' => TRUE,
        ],
        [
          'name' => 'edit_url',
          'type' => 'Link',
          'description' => 'Link to edit the report',
          'readonly' => TRUE,
        ],
      ];

      return $fields;
    }))->setCheckPermissions($checkPermissions);
  }

  public static function getEntityOptions(): array {
    return Entity::get(FALSE)
      ->addSelect('name', 'title')
      ->addWhere('searchable', '!=', 'none')
      ->addOrderBy('title')
      ->execute()
      ->indexBy('name')
      ->column('title');
  }

  public static function getExtensionOptions(): array {
    return Extension::get(FALSE)
      ->addSelect('key', 'label')
      ->addWhere('status', '=', 'installed')
      ->addOrderBy('label')
      ->execute()
      ->indexBy('key')
      ->column('label');
  }

  /**
   * @return array
   */
  public static function permissions() {
    return [
      'default' => ['access Reports'],
    ];
  }

  /**
   * @inheritDoc
   */
  public static function getInfo() {
    $info = parent::getInfo();
    $info['primary_key'] = ['id'];
    return $info;
  }

}
