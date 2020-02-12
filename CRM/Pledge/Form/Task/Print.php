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
 * This class provides the functionality to save a search
 * Saved Searches are used for saving frequently used queries
 */
class CRM_Pledge_Form_Task_Print extends CRM_Pledge_Form_Task {

  /**
   * Build all the data structures needed to build the form.
   */
  public function preProcess() {
    parent::preprocess();

    // set print view, so that print templates are called
    $this->controller->setPrint(1);

    // get the formatted params
    $queryParams = $this->get('queryParams');

    $sortID = NULL;
    if ($this->get(CRM_Utils_Sort::SORT_ID)) {
      $sortID = CRM_Utils_Sort::sortIDValue($this->get(CRM_Utils_Sort::SORT_ID),
        $this->get(CRM_Utils_Sort::SORT_DIRECTION)
      );
    }

    $selector = new CRM_Pledge_Selector_Search($queryParams, $this->_action, $this->_componentClause);
    $controller = new CRM_Core_Selector_Controller($selector, NULL, $sortID, CRM_Core_Action::VIEW, $this, CRM_Core_Selector_Controller::SCREEN);
    $controller->setEmbedded(TRUE);
    $controller->run();
  }

  /**
   * Build the form object - it consists of
   *    - displaying the QILL (query in local language)
   *    - displaying elements for saving the search.
   */
  public function buildQuickForm() {
    // just need to add a javacript to popup the window for printing
    $this->addButtons([
        [
          'type' => 'next',
          'name' => ts('Print Pledge List'),
          'js' => ['onclick' => 'window.print()'],
          'isDefault' => TRUE,
        ],
        [
          'type' => 'back',
          'name' => ts('Done'),
        ],
    ]);
  }

  /**
   * Process the form after the input has been submitted and validated.
   */
  public function postProcess() {
    // redirect to the main search page after printing is over
  }

}
