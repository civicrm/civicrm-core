<?php

use CRM_PaypalPpcp_ExtensionUtil as E;

class CRM_Core_Payment_PPCP extends CRM_Core_Payment {

  /**
   * This function checks to see if we have the right config values.
   *
   * @return null|string
   *   The error message if any.
   */
  public function checkConfig() {
    return NULL;
  }

}
