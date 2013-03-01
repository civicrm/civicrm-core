<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id$
 *
 */

/**
 * This class build form elements for select exitsing or create new contact widget
 */
class CRM_Contact_Form_NewContact {

  /**
   * Function used to build form element for new contact or select contact widget
   *
   * @param object   $form form object
   * @param int      $blocNo by default it is one, except for address block where it is
   * build for each block
   * @param array    $extrProfiles extra profiles that should be included besides reserved
   *
   * @access public
   *
   * @return void
   */
  static function buildQuickForm(&$form, $blockNo = 1, $extraProfiles = NULL, $required = FALSE, $prefix = '') {
    // call to build contact autocomplete
    $attributes = array('width' => '200px');

    $selectContacts = $form->add('text', "{$prefix}contact[{$blockNo}]", ts('Select Contact'), $attributes, $required);

    // use submitted values to set default if form submit fails dues to form rules
    if ($selectContacts->getValue()) {
      $form->assign("selectedContacts", $selectContacts->getValue());
    }

    $form->addElement('hidden', "{$prefix}contact_select_id[{$blockNo}]");

    if (CRM_Core_Permission::check('edit all contacts') || CRM_Core_Permission::check('add contacts')) {
      // build select for new contact
      $contactProfiles = CRM_Core_BAO_UFGroup::getReservedProfiles('Contact', $extraProfiles);
      $form->add('select', "{$prefix}profiles[{$blockNo}]", ts('Create New Contact'), array(
          '' => ts('- create new contact -'),
        ) + $contactProfiles, FALSE, array(
          'onChange' => "if (this.value) {  newContact{$prefix}{$blockNo}( this.value, {$blockNo}, '{$prefix}' );}",
        ));
    }

    $form->assign('blockNo', $blockNo);
    $form->assign('prefix', $prefix);
  }
}

