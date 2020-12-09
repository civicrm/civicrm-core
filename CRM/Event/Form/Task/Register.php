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

/**
 * This class provides the register functionality from a search context.
 *
 * Originally the functionality was all munged into the main Participant form.
 *
 * Ideally it would be entirely separated but for now this overrides the main form,
 * just providing a better separation of the functionality for the search vs main form.
 */
class CRM_Event_Form_Task_Register extends CRM_Event_Form_Participant {


  /**
   * Are we operating in "single mode", i.e. adding / editing only
   * one participant record, or is this a batch add operation
   *
   * ote the goal is to disentangle all the non-single stuff
   * into this form and discontinue this param.
   *
   * @var bool
   */
  public $_single = FALSE;

  /**
   * Assign the url path to the template.
   */
  protected function assignUrlPath() {
    //set the appropriate action
    $context = $this->get('context');
    $urlString = 'civicrm/contact/search';
    $this->_action = CRM_Core_Action::BASIC;
    switch ($context) {
      case 'advanced':
        $urlString = 'civicrm/contact/search/advanced';
        $this->_action = CRM_Core_Action::ADVANCED;
        break;

      case 'builder':
        $urlString = 'civicrm/contact/search/builder';
        $this->_action = CRM_Core_Action::PROFILE;
        break;

      case 'basic':
        $urlString = 'civicrm/contact/search/basic';
        $this->_action = CRM_Core_Action::BASIC;
        break;

      case 'custom':
        $urlString = 'civicrm/contact/search/custom';
        $this->_action = CRM_Core_Action::COPY;
        break;
    }
    CRM_Contact_Form_Task::preProcessCommon($this);

    $this->_contactId = NULL;

    //set ajax path, this used for custom data building
    $this->assign('urlPath', 'civicrm/contact/view/participant');

    $key = CRM_Core_Key::get('CRM_Event_Form_Participant', TRUE);
    $this->assign('participantQfKey', $key);
    $this->assign('participantAction', CRM_Core_Action::ADD);
    $this->assign('urlPathVar', "_qf_Participant_display=true&context=search");
  }

}
