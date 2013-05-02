<?php
class CRM_Event_Cart_Controller_Checkout extends CRM_Core_Controller {
  function __construct($title = NULL, $action = CRM_Core_Action::NONE, $modal = TRUE) {
    parent::__construct($title, $modal);


    $this->_stateMachine = new CRM_Event_Cart_StateMachine_Checkout($this, $action);
    $this->addPages($this->_stateMachine, $action);
    $config = CRM_Core_Config::singleton();

    //changes for custom data type File
    $uploadNames = $this->get('uploadNames');
    if (is_array($uploadNames) && !empty($uploadNames)) {
      $this->addActions($config->customFileUploadDir, $uploadNames);
    }
    else {
      // add all the actions
      $this->addActions();
    }
  }
}

