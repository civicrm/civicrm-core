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
 * $Id$
 *
 */

/**
 * This class contains all the function that are called using AJAX
 */
class CRM_UF_Page_AJAX {

  /**
   * Function the check whether the field belongs.
   * to multi-record custom set
   */
  public function checkIsMultiRecord() {
    $customId = $_GET['customId'];

    $isMultiple = CRM_Core_BAO_CustomField::isMultiRecordField($customId);
    $isMultiple = ['is_multi' => $isMultiple];
    CRM_Utils_JSON::output($isMultiple);
  }

}
