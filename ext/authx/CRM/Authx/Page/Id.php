<?php

use CRM_Authx_ExtensionUtil as E;

class CRM_Authx_Page_Id extends CRM_Core_Page {

  public function run() {
    $authxUf = _authx_uf();

    $response = [
      'contact_id' => CRM_Core_Session::getLoggedInContactID(),
      'user_id' => $authxUf->getCurrentUserId(),
    ];

    CRM_Utils_System::setHttpHeader('Content-Type', 'application/json');
    echo json_encode($response);
    CRM_Utils_System::civiExit();
  }

}
