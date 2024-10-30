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

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
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
      ->addPermissions(['administer reserved tags', 'administer Tagsets']);

    $usedFor = $tagsets = [];

    $result = civicrm_api3('OptionValue', 'get', [
      'return' => ["value", "name"],
      'option_group_id' => "tag_used_for",
    ]);
    foreach ($result['values'] as $value) {
      $usedFor[$value['value']] = $value['name'];
    }

    $result = civicrm_api3('Tag', 'get', [
      'return' => ["name", "label", "used_for", "description", "created_id.display_name", "created_date", "is_reserved"],
      'is_tagset' => 1,
      'options' => ['limit' => 0],
    ]);
    foreach ($result['values'] as $id => $tagset) {
      $used = explode(',', CRM_Utils_Array::value('used_for', $tagset, ''));
      $tagset['used_for_label'] = array_values(array_intersect_key($usedFor, array_flip($used)));
      $tagset['used_for_label_str'] = implode(', ', $tagset['used_for_label']);
      if (isset($tagset['created_id.display_name'])) {
        $tagset['display_name'] = $tagset['created_id.display_name'];
      }
      unset($tagset['created_id.display_name']);
      $tagsets[$id] = $tagset;
    }

    $this->assign('usedFor', $usedFor);
    $this->assign('usedForStr', implode(', ', $usedFor));
    $this->assign('tagsets', $tagsets);

    return parent::run();
  }

}
