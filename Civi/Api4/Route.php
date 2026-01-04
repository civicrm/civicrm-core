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
 * CiviCRM menu route.
 *
 * Provides page routes registered in the CiviCRM menu system.
 *
 * Note: this is a read-only api as routes are set via xml files and hooks.
 *
 * @see https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_alterMenu/
 * @searchable none
 * @since 5.19
 * @package Civi\Api4
 */
class Route extends Generic\AbstractEntity {

  /**
   * @param bool $checkPermissions
   * @return Generic\BasicGetAction
   */
  public static function get($checkPermissions = TRUE) {
    return (new Generic\BasicGetAction(__CLASS__, __FUNCTION__, function ($get) {
      $result = [];
      // Pulling from ::items() rather than DB -- because it provides the final/live/altered data.
      foreach (\CRM_Core_Menu::items() as $path => $item) {
        if (isset($item['page_callback']) && is_array($item['page_callback'])) {
          // Satisfy declared field-type ("String") and match literal config values (xml/Menu/*.xml).
          $item['page_callback'] = implode('::', $item['page_callback']);
        }
        $result[] = ['path' => $path] + $item;
      }
      return $result;
    }))->setCheckPermissions($checkPermissions);
  }

  /**
   * @param bool $checkPermissions
   * @return Generic\BasicGetFieldsAction
   */
  public static function getFields($checkPermissions = TRUE) {
    return (new Generic\BasicGetFieldsAction(__CLASS__, __FUNCTION__, function() {
      return [
        [
          'name' => 'path',
          'title' => 'Relative Path',
          'data_type' => 'String',
        ],
        [
          'name' => 'title',
          'title' => 'Page Title',
          'data_type' => 'String',
        ],
        [
          'name' => 'page_callback',
          'title' => 'Page Callback',
          'data_type' => 'String',
        ],
        [
          'name' => 'page_arguments',
          'title' => 'Page Arguments',
          'data_type' => 'String',
        ],
        [
          'name' => 'path_arguments',
          'title' => 'Path Arguments',
          'data_type' => 'String',
        ],
        [
          'name' => 'access_arguments',
          'title' => 'Access Arguments',
          'data_type' => 'Array',
        ],
      ];
    }))->setCheckPermissions($checkPermissions);
  }

  /**
   * @return array
   */
  public static function permissions() {
    return [
      "meta" => ["access CiviCRM"],
      "default" => ["administer CiviCRM"],
    ];
  }

}
