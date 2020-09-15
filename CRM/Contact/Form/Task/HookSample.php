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
 * This class provides the functionality to save a search.
 *
 * Saved Searches are used for saving frequently used queries
 */
class CRM_Contact_Form_Task_HookSample extends CRM_Contact_Form_Task {

  /**
   * Build all the data structures needed to build the form.
   */
  public function preProcess() {
    parent::preProcess();

    // display name and email of all contact ids
    $contactIDs = implode(',', $this->_contactIds);
    $query = "
SELECT c.id as contact_id, c.display_name as name,
       c.contact_type as contact_type, e.email as email
FROM   civicrm_contact c, civicrm_email e
WHERE  e.contact_id = c.id
AND    e.is_primary = 1
AND    c.id IN ( $contactIDs )";

    $rows = [];
    $dao = CRM_Core_DAO::executeQuery($query);
    while ($dao->fetch()) {
      $rows[] = [
        'id' => $dao->contact_id,
        'name' => $dao->name,
        'contact_type' => $dao->contact_type,
        'email' => $dao->email,
      ];
    }

    $this->assign('rows', $rows);
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    $this->addDefaultButtons(ts('Back to Search'), 'done');
  }

  /**
   * Process the form after the input has been submitted and validated.
   */
  public function postProcess() {
  }

}
