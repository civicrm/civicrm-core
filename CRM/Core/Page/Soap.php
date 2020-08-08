<?php

/**
 * Execute a SOAP request
 */
class CRM_Core_Page_Soap extends CRM_Core_Page {

  /**
   * Execute a soap request.
   */
  public function run() {
    if (defined('PANTHEON_ENVIRONMENT')) {
      ini_set('session.save_handler', 'files');
    }
    session_start();

    $server = new SoapServer(NULL,
      [
        'uri' => 'urn:civicrm',
        'soap_version' => SOAP_1_2,
      ]
    );

    $crm_soap = new CRM_Utils_SoapServer();

    /* Cache the real UF, override it with the SOAP environment */

    $civicrmConfig = CRM_Core_Config::singleton();

    $server->setClass('CRM_Utils_SoapServer', $civicrmConfig->userFrameworkClass);

    $server->setPersistence(SOAP_PERSISTENCE_SESSION);

    $server->handle();
    CRM_Utils_System::civiExit();
  }

}
