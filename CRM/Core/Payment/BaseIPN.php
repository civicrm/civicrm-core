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
 * Class CRM_Core_Payment_BaseIPN.
 *
 * @deprecated will be removed around 6.28
 */
class CRM_Core_Payment_BaseIPN {

  /**
   * Constructor.
   */
  public function __construct() {
    CRM_Core_Error::deprecatedWarning('CRM_Core_Payment_BaseIPN will be removed around 6.6 - if you see this warning your payment processor needs to be updated!');
    self::$_now = date('YmdHis');
  }

  /**
   * Store input array on the class.
   *
   * @param array $parameters
   *
   * @throws CRM_Core_Exception
   */
  public function setInputParameters($parameters) {
    if (!is_array($parameters)) {
      throw new CRM_Core_Exception('Invalid input parameters');
    }
    $this->_inputParameters = $parameters;
  }

}
