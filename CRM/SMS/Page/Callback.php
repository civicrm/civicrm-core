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
    $request = array_merge($_GET, $_POST);
    $provider = CRM_SMS_Provider::singleton($request);

    if (array_key_exists('status', $request)) {
      $provider->callback();
    }
    else {
      $provider->inbound();
    }
  }

}
