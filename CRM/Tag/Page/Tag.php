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

use Civi\Api4\OptionValue;
use Civi\Api4\Tag;

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

    $usedFor = OptionValue::get(FALSE)
      ->addSelect('value', 'name')
      ->addWhere('option_group_id:name', '=', 'tag_used_for')
      ->execute()
      ->column('name', 'value');

    $tagsets = [];
    $tagsetRecords = Tag::get(FALSE)
      ->addSelect('id', 'name', 'label', 'used_for', 'description', 'created_id.display_name', 'created_date', 'is_reserved')
      ->addWhere('is_tagset', '=', TRUE)
      ->execute();
    foreach ($tagsetRecords as $tagset) {
      $used = $tagset['used_for'] ?? [];
      $tagset['used_for_label'] = array_values(array_intersect_key($usedFor, array_flip($used)));
      $tagset['used_for_label_str'] = implode(', ', $tagset['used_for_label']);
      // The page script also receives tagsets from the tag edit form, which
      // returns used_for in its stored comma-separated format.
      $tagset['used_for'] = implode(',', $used);
      $tagset['display_name'] = $tagset['created_id.display_name'];
      unset($tagset['created_id.display_name']);
      $tagsets[$tagset['id']] = $tagset;
    }

    $this->assign('usedFor', $usedFor);
    $this->assign('usedForStr', implode(', ', $usedFor));
    $this->assign('tagsets', $tagsets);
    $this->assign('userDisplayName', CRM_Core_Session::singleton()->getLoggedInContactDisplayName());

    return parent::run();
  }

}
