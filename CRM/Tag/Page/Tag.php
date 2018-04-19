<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
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
 * @copyright CiviCRM LLC (c) 2004-2018
 */

/**
 * Page for managing tags.
 */
class CRM_Tag_Page_Tag extends CRM_Core_Page {

  /**
   * Run page
   */
  public function run() {
    CRM_Core_Resources::singleton()
      ->addScriptFile('civicrm', 'bower_components/jstree/dist/jstree.min.js', 0, 'html-header')
      ->addStyleFile('civicrm', 'bower_components/jstree/dist/themes/default/style.min.css')
      ->addPermissions(array('administer reserved tags', 'administer Tagsets'));

    $usedFor = $tagsets = array();

    $result = civicrm_api3('OptionValue', 'get', array(
      'return' => array("value", "name"),
      'option_group_id' => "tag_used_for",
    ));
    foreach ($result['values'] as $value) {
      $usedFor[$value['value']] = $value['name'];
    }

    $result = civicrm_api3('Tag', 'get', array(
      'return' => array("name", "used_for", "description", "created_id.display_name", "created_date", "is_reserved"),
      'is_tagset' => 1,
      'options' => array('limit' => 0),
    ));
    foreach ($result['values'] as $id => $tagset) {
      $used = explode(',', CRM_Utils_Array::value('used_for', $tagset, ''));
      $tagset['used_for_label'] = array_values(array_intersect_key($usedFor, array_flip($used)));
      if (isset($tagset['created_id.display_name'])) {
        $tagset['display_name'] = $tagset['created_id.display_name'];
      }
      unset($tagset['created_id.display_name']);
      $tagsets[$id] = $tagset;
    }

    $this->assign('usedFor', $usedFor);
    $this->assign('tagsets', $tagsets);

    return parent::run();
  }

}
