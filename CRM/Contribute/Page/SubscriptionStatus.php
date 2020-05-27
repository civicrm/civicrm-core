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
class CRM_Contribute_Page_SubscriptionStatus extends CRM_Core_Page {

  /**
   * the main function that is called when the page loads,
   * it decides the which action has to be taken for the page.
   *
   * @return null
   */
  public function run() {
    $task = CRM_Utils_Request::retrieve('task', 'String');
    $result = CRM_Utils_Request::retrieve('result', 'Integer');

    $this->assign('task', $task);
    $this->assign('result', $result);

    if ($task == 'billing') {
      $session = CRM_Core_Session::singleton();
      $tplParams = $session->get('resultParams');
      foreach ($tplParams as $key => $val) {
        $this->assign($key, $val);
      }
    }

    return parent::run();
  }

}
