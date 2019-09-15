<?php
namespace Civi\Api4;

use Civi\Api4\Generic\BasicGetFieldsAction;

class Route extends \Civi\Api4\Generic\AbstractEntity {

  /**
   * @return \Civi\Api4\Generic\BasicGetAction
   */
  public static function get() {
    return new \Civi\Api4\Generic\BasicGetAction(__CLASS__, __FUNCTION__, function ($get) {
      // Pulling from ::items() rather than DB -- because it provides the final/live/altered data.
      $items = \CRM_Core_Menu::items();
      $result = [];
      foreach ($items as $path => $item) {
        $result[] = ['path' => $path] + $item;
      }
      return $result;
    });
  }

  public static function getFields() {
    return new BasicGetFieldsAction(__CLASS__, __FUNCTION__, function() {
      return [
        [
          'name' => 'path',
          'title' => 'Relative Path',
          'required' => TRUE,
          'data_type' => 'String',
        ],
        [
          'name' => 'title',
          'title' => 'Page Title',
          'required' => TRUE,
          'data_type' => 'String',
        ],
        [
          'name' => 'page_callback',
          'title' => 'Page Callback',
          'required' => TRUE,
          'data_type' => 'String',
        ],
        [
          'name' => 'page_arguments',
          'title' => 'Page Arguments',
          'required' => FALSE,
          'data_type' => 'String',
        ],
        [
          'name' => 'path_arguments',
          'title' => 'Path Arguments',
          'required' => FALSE,
          'data_type' => 'String',
        ],
        [
          'name' => 'access_arguments',
          'title' => 'Access Arguments',
          'required' => FALSE,
          'data_type' => 'Array',
        ],
      ];
    });
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
