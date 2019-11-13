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
 * $Id$
 *
 */

/**
 * This class is for building event(participation) block on user dashboard
 */
class CRM_Event_Page_UserDashboard extends CRM_Contact_Page_View_UserDashBoard {

  /**
   * List participations for the UF user.
   *
   */
  public function listParticipations() {
    $controller = new CRM_Core_Controller_Simple(
      'CRM_Event_Form_Search',
      ts('Events'),
      NULL,
      FALSE, FALSE, TRUE, FALSE
    );
    $controller->setEmbedded(TRUE);
    $controller->reset();
    $controller->set('context', 'user');
    $controller->set('cid', $this->_contactId);
    $controller->set('force', 1);
    $controller->process();
    $controller->run();
  }

  /**
   * the main function that is called when the page
   * loads, it decides the which action has to be taken for the page.
   *
   */
  public function run() {
    parent::preProcess();
    $this->listParticipations();
  }

}
