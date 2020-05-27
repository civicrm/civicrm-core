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
class CRM_Mailing_Page_Optout extends CRM_Mailing_Page_Common {

  /**
   * Run page.
   *
   * This includes assigning smarty variables and other page processing.
   *
   * @return string
   * @throws Exception
   */
  public function run() {
    $this->_type = 'optout';
    return parent::run();
  }

}
