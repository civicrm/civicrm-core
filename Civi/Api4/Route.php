<?php

/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2019
 * $Id$
 *
 */

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
