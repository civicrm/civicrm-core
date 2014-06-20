<?php

/**
 * Class CRM_Custom_Import_Form_Summary
 */
class CRM_Custom_Import_Form_Summary extends CRM_Contact_Import_Form_Summary {
  /**
   * Function to set variables up before form is built
   *
   * @return void
   * @access public
   */
  public function preProcess() {
    parent::preProcess();
    $session = CRM_Core_Session::singleton();
    $session->pushUserContext(CRM_Utils_System::url('civicrm/import/custom', 'reset=1'));
  }
}
