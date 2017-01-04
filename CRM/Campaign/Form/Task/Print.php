<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2017                                |
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
 * @copyright CiviCRM LLC (c) 2004-2017
 */

/**
 * This class provides the functionality to print voter records
 */
class CRM_Campaign_Form_Task_Print extends CRM_Campaign_Form_Task {

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

    $selector = new CRM_Campaign_Selector_Search($queryParams,
      $this->_action,
      $this->_componentClause
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
   * Build the form object.
   *
   * It consists of
   *    - displaying the QILL (query in local language)
   *    - displaying elements for saving the search
   */
  public function buildQuickForm() {
    //
    // just need to add a javacript to popup the window for printing
    //
    $this->addButtons(array(
        array(
          'type' => 'next',
          'name' => ts('Print Respondents'),
          'js' => array('onclick' => 'window.print()'),
          'isDefault' => TRUE,
        ),
        array(
          'type' => 'back',
          'name' => ts('Done'),
        ),
      )
    );
  }

  /**
   * Process the form after the input has been submitted and validated.
   */
  public function postProcess() {
    // redirect to the main search page after printing is over
  }

}
