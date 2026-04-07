<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */
namespace Civi\Api4;

/**
 * Extensions - add-on modules extend the functionality of CiviCRM.
 *
 * @see https://docs.civicrm.org/user/en/latest/introduction/extensions/
 * @searchable secondary
 * @since 5.48
 * @package Civi\Api4
 */
class Extension extends Generic\AbstractEntity {

  /**
   * @param bool $checkPermissions
   * @return Generic\BasicGetAction
   */
  public static function get($checkPermissions = TRUE) {
    return (new Action\Extension\Get(__CLASS__, __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * @param bool $checkPermissions
   * @return Generic\BasicGetFieldsAction
   */
  public static function getFields($checkPermissions = TRUE) {
    return (new Generic\BasicGetFieldsAction(static::getEntityName(), __FUNCTION__, function() {
      return [
        [
          'name' => 'key',
          'description' => 'Long, unique extension identifier',
        ],
        [
          'name' => 'file',
          'description' => 'Short, unique extension identifier',
        ],
        [
          'name' => 'label',
          'description' => 'User-facing extension title',
        ],
        [
          'name' => 'description',
          'description' => 'Additional information about the extension',
        ],
        [
          'name' => 'version',
          'description' => 'Current version number (string)',
        ],
        [
          'name' => 'tags',
          'data_type' => 'Array',
          'description' => "Tags which characterize the extension's purpose or functionality",
        ],
        [
          'name' => 'path',
          'description' => 'Absolute file path',
        ],
        [
          'name' => 'releaseDate',
          'description' => 'Release date',
        ],
        [
          'name' => 'compatibility',
          'description' => 'CiviCRM compatibility',
        ],
        [
          'name' => 'develStage',
          'description' => 'Development stage',
        ],
        [
          'name' => 'urls',
          'data_type' => 'Array',
          'description' => 'URLs for extension page, documentation, licensing and support',
        ],
        [
          'name' => 'authors',
          'data_type' => 'Array',
          'description' => 'Authors',
        ],
        [
          'name' => 'license',
          'description' => 'License',
        ],
        [
          'name' => 'comments',
          'description' => 'Comments',
        ],
        [
          'name' => 'status',
          'description' => 'Extension enabled/disabled/uninstalled status',
          'options' => [
            \CRM_Extension_Manager::STATUS_UNINSTALLED => ts('Uninstalled'),
            \CRM_Extension_Manager::STATUS_DISABLED => ts('Disabled'),
            \CRM_Extension_Manager::STATUS_INSTALLED => ts('Enabled'),
            \CRM_Extension_Manager::STATUS_DISABLED_MISSING => ts('Disabled (Missing)'),
            \CRM_Extension_Manager::STATUS_INSTALLED_MISSING => ts('Enabled (Missing)'),
          ],
        ],
      ];
    }))->setCheckPermissions($checkPermissions);
  }

  /**
   * @inheritDoc
   */
  public static function getInfo() {
    $info = parent::getInfo();
    $info['title'] = ts('Extension');
    $info['title_plural'] = ts('Extensions');
    $info['primary_key'] = ['key'];
    $info['label_field'] = 'label';
    return $info;
  }

}
