<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
*/

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id$
 *
 */

/**
 * This file is used to build the form configuring mailing details
 */
class CRM_Mailing_Form_Settings extends CRM_Core_Form {

  /**
   * Function to set variables up before form is built
   *
   * @return void
   * @access public
   */
  public function preProcess() {
    //when user come from search context.
    $ssID = $this->get('ssID');
    $this->assign('ssid',$ssID);
    $this->_searchBasedMailing = CRM_Contact_Form_Search::isSearchContext($this->get('context'));
    if(CRM_Contact_Form_Search::isSearchContext($this->get('context')) && !$ssID){
    $params = array();
    $result = CRM_Core_BAO_PrevNextCache::getSelectedContacts();
    $this->assign("value", $result);
    }
  }

  /**
   * This function sets the default values for the form.
   * the default values are retrieved from the database
   *
   * @access public
   *
   * @return None
   */
  function setDefaultValues() {
    $mailingID = CRM_Utils_Request::retrieve('mid', 'Integer', $this, FALSE, NULL);
    $count = $this->get('count');
    $this->assign('count', $count);
    $defaults = array();

    $componentFields = array(
      'reply_id' => 'Reply',
      'optout_id' => 'OptOut',
      'unsubscribe_id' => 'Unsubscribe',
      'resubscribe_id' => 'Resubscribe',
    );

    foreach ($componentFields as $componentVar => $componentType) {
      $defaults[$componentVar] = CRM_Mailing_PseudoConstant::defaultComponent($componentType, '');
    }

    if ($mailingID) {
      $dao = new CRM_Mailing_DAO_Mailing();
      $dao->id = $mailingID;
      $dao->find(TRUE);
      // override_verp must be flipped, as in 3.2 we reverted
      // its meaning to ‘should CiviMail manage replies?’ – i.e.,
      // ‘should it *not* override Reply-To: with VERP-ed address?’
      $dao->override_verp = !$dao->override_verp;
      $dao->storeValues($dao, $defaults);
      $defaults['visibility'] = $dao->visibility;
    }
    return $defaults;
  }

  /**
   * Function to actually build the form
   *
   * @return None
   * @access public
   */
  public function buildQuickForm() {

    $this->addElement('checkbox', 'override_verp', ts('Track Replies?'));

    $defaults['override_verp'] = CRM_Core_BAO_Setting::getItem(CRM_Core_BAO_Setting::MAILING_PREFERENCES_NAME,
      'track_civimail_replies', NULL, FALSE
    );

    $this->add('checkbox', 'forward_replies', ts('Forward Replies?'));
    $defaults['forward_replies'] = FALSE;

    $this->add('checkbox', 'url_tracking', ts('Track Click-throughs?'));
    $defaults['url_tracking'] = TRUE;

    $this->add('checkbox', 'open_tracking', ts('Track Opens?'));
    $defaults['open_tracking'] = TRUE;

    $this->add('checkbox', 'auto_responder', ts('Auto-respond to Replies?'));
    $defaults['auto_responder'] = FALSE;

    $this->add('select', 'visibility', ts('Mailing Visibility'),
      CRM_Core_SelectValues::ufVisibility(TRUE), TRUE
    );

    $this->add('select', 'reply_id', ts('Auto-responder'),
      CRM_Mailing_PseudoConstant::component('Reply'), TRUE
    );

    $this->add('select', 'unsubscribe_id', ts('Unsubscribe Message'),
      CRM_Mailing_PseudoConstant::component('Unsubscribe'), TRUE
    );

    $this->add('select', 'resubscribe_id', ts('Resubscribe Message'),
      CRM_Mailing_PseudoConstant::component('Resubscribe'), TRUE
    );

    $this->add('select', 'optout_id', ts('Opt-out Message'),
      CRM_Mailing_PseudoConstant::component('OptOut'), TRUE
    );

    $buttons = array(
      array('type' => 'back',
        'name' => ts('<< Previous'),
      ),
      array(
        'type' => 'next',
        'name' => ts('Next >>'),
        'spacing' => '&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;',
        'isDefault' => TRUE,
      ),
      array(
        'type' => 'submit',
        'name' => ts('Save & Continue Later'),
      ),
      array(
        'type' => 'cancel',
        'name' => ts('Cancel'),
      ),
    );

    $this->addButtons($buttons);

    $this->setDefaults($defaults);
  }

  public function postProcess() {
    $params = $ids = array();

    $session = CRM_Core_Session::singleton();
    $params['created_id'] = $session->get('userID');

    $uploadParams = array('reply_id', 'unsubscribe_id', 'optout_id', 'resubscribe_id');
    $uploadParamsBoolean = array('override_verp', 'forward_replies', 'url_tracking', 'open_tracking', 'auto_responder');

    $qf_Settings_submit = $this->controller->exportValue($this->_name, '_qf_Settings_submit');

    foreach ($uploadParams as $key) {
      $params[$key] = $this->controller->exportvalue($this->_name, $key);
      $this->set($key, $this->controller->exportvalue($this->_name, $key));
    }

    foreach ($uploadParamsBoolean as $key) {
      if ($this->controller->exportvalue($this->_name, $key)) {
        $params[$key] = TRUE;
      }
      else {
        $params[$key] = FALSE;
      }
      $this->set($key, $this->controller->exportvalue($this->_name, $key));
    }

    $params['visibility'] = $this->controller->exportvalue($this->_name, 'visibility');

    // override_verp must be flipped, as in 3.2 we reverted
    // its meaning to ‘should CiviMail manage replies?’ – i.e.,
    // ‘should it *not* override Reply-To: with VERP-ed address?’
    $params['override_verp'] = !$params['override_verp'];

    $ids['mailing_id'] = $this->get('mailing_id');

    // update mailing
    CRM_Mailing_BAO_Mailing::create($params, $ids);

    if ($qf_Settings_submit) {
      //when user perform mailing from search context
      //redirect it to search result CRM-3711.
      $ssID = $this->get('ssID');
      if ($ssID && $this->_searchBasedMailing) {
        if ($this->_action == CRM_Core_Action::BASIC) {
          $fragment = 'search';
        }
        elseif ($this->_action == CRM_Core_Action::PROFILE) {
          $fragment = 'search/builder';
        }
        elseif ($this->_action == CRM_Core_Action::ADVANCED) {
          $fragment = 'search/advanced';
        }
        else {
          $fragment = 'search/custom';
        }

        $context = $this->get('context');
        if (!CRM_Contact_Form_Search::isSearchContext($context)) {
          $context = 'search';
        }
        $urlParams = "force=1&reset=1&ssID={$ssID}&context={$context}";
        $qfKey = CRM_Utils_Request::retrieve('qfKey', 'String', $this);
        if (CRM_Utils_Rule::qfKey($qfKey)) {
          $urlParams .= "&qfKey=$qfKey";
        }

        $draftURL = CRM_Utils_System::url('civicrm/mailing/browse/unscheduled', 'scheduled=false&reset=1');
        $status = ts("You can continue later by clicking the 'Continue' action to resume working on it.<br />From <a href='%1'>Draft and Unscheduled Mailings</a>.", array(1 => $draftURL));

        // Redirect user to search.
        $url = CRM_Utils_System::url('civicrm/contact/' . $fragment, $urlParams);
      }
      else {
        $status = ts("Click the 'Continue' action to resume working on it.");
        $url = CRM_Utils_System::url('civicrm/mailing/browse/unscheduled', 'scheduled=false&reset=1');
      }
      CRM_Core_Session::setStatus($status, ts('Mailing Saved'), 'success');
      CRM_Utils_System::redirect($url);
    }
  }

  /**
   * Display Name of the form
   *
   * @access public
   *
   * @return string
   */
  public function getTitle() {
    return ts('Track and Respond');
  }
}
