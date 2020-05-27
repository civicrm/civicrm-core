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
class CRM_SMS_Page_Callback {

  public function run() {
    $provider = CRM_SMS_Provider::singleton($_REQUEST);

    if (array_key_exists('status', $_REQUEST)) {
      $provider->callback();
    }
    else {
      $provider->inbound();
    }
  }

}
