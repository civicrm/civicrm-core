<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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
 * @copyright CiviCRM LLC (c) 2004-2015
 */

/**
 * Form helper class for an Website object.
 */
class CRM_Contact_Form_Edit_Website {

  /**
   * Build the form object elements for an Website object.
   *
   * @param CRM_Core_Form $form
   *   Reference to the form object.
   * @param int $blockCount
   *   Block number to build.
   */
  public static function buildQuickForm(&$form, $blockCount = NULL) {
    if (!$blockCount) {
      $blockId = ($form->get('Website_Block_Count')) ? $form->get('Website_Block_Count') : 1;
    }
    else {
      $blockId = $blockCount;
    }

    $form->applyFilter('__ALL__', 'trim');

    //Website type select
    $form->addField("website[$blockId][website_type_id]", array('entity' => 'website', 'class' => 'eight'));

    //Website box
    $form->addField("website[$blockId][url]", array('entity' => 'website'));
    $form->addRule("website[$blockId][url]", ts('Enter a valid web address beginning with \'http://\' or \'https://\'.'), 'url');

  }

}
