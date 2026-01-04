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
 * Page for displaying list of current batches
 */
class CRM_Financial_Page_Batch extends CRM_Core_Page_Basic {

  /**
   * The action links that we need to display for the browse screen
   *
   * @var array
   */
  public static $_links = NULL;

  /**
   * Get BAO Name.
   *
   * @return string
   *   Classname of BAO.
   */
  public function getBAOName() {
    return 'CRM_Batch_BAO_Batch';
  }

  /**
   * Get links.
   */
  public function &links() {
  }

  /**
   * Get name of edit form.
   *
   * @return string
   *   Classname of edit form.
   */
  public function editForm() {
    return 'CRM_Financial_Form_FinancialBatch';
  }

  /**
   * Get edit form name.
   *
   * @return string
   *   name of this page.
   */
  public function editName() {
    return ts('Accounting Batch Processing');
  }

  /**
   * Get user context.
   *
   * @param null $mode
   *
   * @return string
   *   user context.
   */
  public function userContext($mode = NULL) {
    return CRM_Utils_System::currentPath();
  }

  /**
   * Browse all entities.
   */
  public function browse() {
    $status = CRM_Utils_Request::retrieve('status', 'Positive', CRM_Core_DAO::$_nullObject, FALSE, 1);
    $this->assign('status', $status);
    $this->assign('financialAJAXQFKey', CRM_Core_Key::get('CRM_Financial_Page_AJAX'));
    $this->search();
  }

  public function search() {
    if ($this->_action & (CRM_Core_Action::ADD |
        CRM_Core_Action::UPDATE |
        CRM_Core_Action::DELETE
      )
    ) {
      return;
    }

    $form = new CRM_Core_Controller_Simple('CRM_Financial_Form_Search', ts('Search Batches'), CRM_Core_Action::ADD);
    $form->setEmbedded(TRUE);
    $form->setParent($this);
    $form->process();
    $form->run();
  }

}
