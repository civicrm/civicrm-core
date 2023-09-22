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
class CRM_Contact_Form_Task_Print extends CRM_Contact_Form_Task {

  /**
   * Build all the data structures needed to build the form.
   */
  public function preProcess() {
    parent::preprocess();

    // set print view, so that print templates are called
    $this->controller->setPrint(CRM_Core_Smarty::PRINT_PAGE);
    $this->assign('id', $this->get('id'));
    $this->assign('pageTitle', ts('CiviCRM Contact Listing'));

    $params = $this->get('queryParams');
    if (!empty($this->_contactIds)) {
      //using _contactIds field for creating params for query so that multiple selections on multiple pages
      //can be printed.
      foreach ($this->_contactIds as $contactId) {
        $params[] = [
          CRM_Core_Form::CB_PREFIX . $contactId,
          '=',
          1,
          0,
          0,
        ];
      }
    }

    // create the selector, controller and run - store results in session
    $fv = $this->get('formValues');
    $returnProperties = $this->get('returnProperties');

    $sortID = NULL;
    if ($this->get(CRM_Utils_Sort::SORT_ID)) {
      $sortID = CRM_Utils_Sort::sortIDValue($this->get(CRM_Utils_Sort::SORT_ID),
        $this->get(CRM_Utils_Sort::SORT_DIRECTION)
      );
    }

    $includeContactIds = FALSE;
    if ($fv['radio_ts'] == 'ts_sel') {
      $includeContactIds = TRUE;
    }

    $selectorName = $this->controller->selectorName();
    require_once str_replace('_', DIRECTORY_SEPARATOR, $selectorName) . '.php';

    $returnP = $returnProperties ?? "";
    $customSearchClass = $this->get('customSearchClass');
    $this->assign('customSearchID', $this->get('customSearchID'));
    $selector = new $selectorName($customSearchClass,
      $fv,
      $params,
      $returnP,
      $this->_action,
      $includeContactIds
    );
    $controller = new CRM_Core_Selector_Controller($selector,
      NULL,
      $sortID,
      CRM_Core_Action::VIEW,
      $this,
      CRM_Core_Selector_Controller::SCREEN
    );
    $controller->setEmbedded(TRUE);
    $controller->run();
  }

  /**
   * Build the form object - it consists of
   *    - displaying the QILL (query in local language)
   *    - displaying elements for saving the search
   */
  public function buildQuickForm() {
    //
    // just need to add a javacript to popup the window for printing
    //
    $this->addButtons([
      [
        'type' => 'next',
        'name' => ts('Print Contact List'),
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
