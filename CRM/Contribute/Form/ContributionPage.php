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
 * form to process actions on the group aspect of Custom Data
 */
class CRM_Contribute_Form_ContributionPage extends CRM_Core_Form {

  /**
   * the page id saved to the session for an update
   *
   * @var int
   * @access protected
   */
  protected $_id;

  /**
   * the pledgeBlock id saved to the session for an update
   *
   * @var int
   * @access protected
   */
  protected $_pledgeBlockID;

  /**
   * are we in single form mode or wizard mode?
   *
   * @var boolean
   * @access protected
   */
  protected $_single;

  /**
   * is this the first page?
   *
   * @var boolean
   * @access protected
   */
  protected $_first = FALSE;

  /**
   * store price set id.
   *
   * @var int
   * @access protected
   */
  protected $_priceSetID = NULL;

  protected $_values;

  /**
   * Function to set variables up before form is built
   *
   * @return void
   * @access public
   */
  public function preProcess() {
    // current contribution page id
    $this->_id = CRM_Utils_Request::retrieve('id', 'Positive',
      $this, FALSE, NULL, 'REQUEST'
    );
    $this->assign('contributionPageID', $this->_id);

    // get the requested action
    $this->_action = CRM_Utils_Request::retrieve('action', 'String',
      // default to 'browse'
      $this, FALSE, 'browse'
    );

    // setting title and 3rd level breadcrumb for html page if contrib page exists
    if ($this->_id) {
      $title = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_ContributionPage', $this->_id, 'title');

      if ($this->_action == CRM_Core_Action::UPDATE) {
        $this->_single = TRUE;
      }
    }

    // set up tabs
    CRM_Contribute_Form_ContributionPage_TabHeader::build($this);

    if ($this->_action == CRM_Core_Action::UPDATE) {
      CRM_Utils_System::setTitle(ts('Configure Page - %1', array(1 => $title)));
    }
    elseif ($this->_action == CRM_Core_Action::VIEW) {
      CRM_Utils_System::setTitle(ts('Preview Page - %1', array(1 => $title)));
    }
    elseif ($this->_action == CRM_Core_Action::DELETE) {
      CRM_Utils_System::setTitle(ts('Delete Page - %1', array(1 => $title)));
    }

    //cache values.
    $this->_values = $this->get('values');
    if (!is_array($this->_values)) {
      $this->_values = array();
      if (isset($this->_id) && $this->_id) {
        $params = array('id' => $this->_id);
        CRM_Core_DAO::commonRetrieve('CRM_Contribute_DAO_ContributionPage', $params, $this->_values);
      }
      $this->set('values', $this->_values);
    }
  }

  /**
   * Function to actually build the form
   *
   * @return void
   * @access public
   */
  public function buildQuickForm() {
    $this->applyFilter('__ALL__', 'trim');

    $session = CRM_Core_Session::singleton();
    $this->_cancelURL = CRM_Utils_Array::value('cancelURL', $_POST);

    if (!$this->_cancelURL) {
      $this->_cancelURL = CRM_Utils_System::url('civicrm/admin/contribute', 'reset=1');
    }

    if ($this->_cancelURL) {
      $this->addElement('hidden', 'cancelURL', $this->_cancelURL);
    }


    if ($this->_single) {
      $this->addButtons(array(
          array(
            'type' => 'next',
            'name' => ts('Save'),
            'spacing' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
            'isDefault' => TRUE,
          ),
          array(
            'type' => 'upload',
            'name' => ts('Save and Done'),
            'spacing' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
            'subName' => 'done',
          ),
          array(
            'type' => 'submit',
            'name' => ts('Save and Next'),
            'spacing' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
            'subName' => 'savenext',
          ),
          array(
            'type' => 'cancel',
            'name' => ts('Cancel'),
          ),
        )
      );
    }
    else {
      $buttons = array();
      if (!$this->_first) {
        $buttons[] = array(
          'type' => 'back',
          'name' => ts('<< Previous'),
          'spacing' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
        );
      }
      $buttons[] = array(
        'type' => 'next',
        'name' => ts('Continue >>'),
        'spacing' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
        'isDefault' => TRUE,
      );
      $buttons[] = array(
        'type' => 'cancel',
        'name' => ts('Cancel'),
      );

      $this->addButtons($buttons);
    }

    $session->replaceUserContext($this->_cancelURL);
    // views are implemented as frozen form
    if ($this->_action & CRM_Core_Action::VIEW) {
      $this->freeze();
      $this->addElement('button', 'done', ts('Done'), array('onclick' => "location.href='civicrm/admin/custom/group?reset=1&action=browse'"));
    }
  }

  /**
   * This function sets the default values for the form. Note that in edit/view mode
   * the default values are retrieved from the database
   *
   * @access public
   *
   * @return void
   */
  function setDefaultValues() {
    //some child classes calling setdefaults directly w/o preprocess.
    $this->_values = $this->get('values');
    if (!is_array($this->_values)) {
      $this->_values = array();
      if (isset($this->_id) && $this->_id) {
        $params = array('id' => $this->_id);
        CRM_Core_DAO::commonRetrieve('CRM_Contribute_DAO_ContributionPage', $params, $this->_values);
      }
      $this->set('values', $this->_values);
    }
    $defaults = $this->_values;

    $config = CRM_Core_Config::singleton();
    if (isset($this->_id)) {

      //set defaults for pledgeBlock values.
      $pledgeBlockParams = array(
        'entity_id' => $this->_id,
        'entity_table' => ts('civicrm_contribution_page'),
      );
      $pledgeBlockDefaults = array();
      CRM_Pledge_BAO_PledgeBlock::retrieve($pledgeBlockParams, $pledgeBlockDefaults);
      if ($this->_pledgeBlockID = CRM_Utils_Array::value('id', $pledgeBlockDefaults)) {
        $defaults['is_pledge_active'] = TRUE;
      }
      $pledgeBlock = array(
        'is_pledge_interval', 'max_reminders',
        'initial_reminder_day', 'additional_reminder_day',
      );
      foreach ($pledgeBlock as $key) {
        $defaults[$key] = CRM_Utils_Array::value($key, $pledgeBlockDefaults);
      }
      if (CRM_Utils_Array::value('pledge_frequency_unit', $pledgeBlockDefaults)) {
        $defaults['pledge_frequency_unit'] = array_fill_keys(explode(CRM_Core_DAO::VALUE_SEPARATOR,
            $pledgeBlockDefaults['pledge_frequency_unit']
          ), '1');
      }

      // fix the display of the monetary value, CRM-4038
      if (isset($defaults['goal_amount'])) {
        $defaults['goal_amount'] = CRM_Utils_Money::format($defaults['goal_amount'], NULL, '%a');
      }

      // get price set of type contributions
      //this is the value for stored in db if price set extends contribution
      $usedFor = 2;
      $this->_priceSetID = CRM_Price_BAO_Set::getFor('civicrm_contribution_page', $this->_id, $usedFor, 1);
      if ($this->_priceSetID) {
        $defaults['price_set_id'] = $this->_priceSetID;
      }

      if (CRM_Utils_Array::value('end_date', $defaults)) {
        list($defaults['end_date'], $defaults['end_date_time']) = CRM_Utils_Date::setDateDefaults($defaults['end_date']);
      }

      if (CRM_Utils_Array::value('start_date', $defaults)) {
        list($defaults['start_date'], $defaults['start_date_time']) = CRM_Utils_Date::setDateDefaults($defaults['start_date']);
      }
    }
    else {
      $defaults['is_active'] = 1;
      // set current date as start date
      list($defaults['start_date'], $defaults['start_date_time']) = CRM_Utils_Date::setDateDefaults();
    }

    if (!isset($defaults['for_organization'])) {
      $defaults['for_organization'] = ts('I am contributing on behalf of an organization.');
    }

    if (CRM_Utils_Array::value('recur_frequency_unit', $defaults)) {
      $defaults['recur_frequency_unit'] = array_fill_keys(explode(CRM_Core_DAO::VALUE_SEPARATOR,
          $defaults['recur_frequency_unit']
        ), '1');
    }
    else {
      # CRM 10860
      $defaults['recur_frequency_unit'] = array('month' => 1);
    }

    if (CRM_Utils_Array::value('is_for_organization', $defaults)) {
      $defaults['is_organization'] = 1;
    }
    else {
      $defaults['is_for_organization'] = 1;
    }

    // confirm page starts out enabled
    if (!isset($defaults['is_confirm_enabled'])) {
      $defaults['is_confirm_enabled'] = 1;
    }

    return $defaults;
  }

  /**
   * Process the form
   *
   * @return void
   * @access public
   */
  public function postProcess() {
    $pageId = $this->get('id');
    //page is newly created.
    if ($pageId && !$this->_id) {
      $session = CRM_Core_Session::singleton();
      $session->pushUserContext(CRM_Utils_System::url('civicrm/admin/contribute', 'reset=1'));
    }
  }

  function endPostProcess() {
    // make submit buttons keep the current working tab opened, or save and next tab
    if ($this->_action & CRM_Core_Action::UPDATE) {
      $className = CRM_Utils_String::getClassName($this->_name);

      //retrieve list of pages from StateMachine and find next page
      //this is quite painful because StateMachine is full of protected variables
      //so we have to retrieve all pages, find current page, and then retrieve next
      $stateMachine = new CRM_Contribute_StateMachine_ContributionPage($this);
      $states       = $stateMachine->getStates();
      $statesList   = array_keys($states);
      $currKey      = array_search($className, $statesList);
      $nextPage     = (array_key_exists($currKey + 1, $statesList)) ? $statesList[$currKey + 1] : '';

      //unfortunately, some classes don't map to subpage names, so we alter the exceptions

      switch ($className) {
        case 'Contribute':
          $attributes  = $this->getVar('_attributes');
          $subPage     = strtolower(basename(CRM_Utils_Array::value('action', $attributes)));
          $subPageName = ucFirst($subPage);
          if ($subPage == 'friend') {
            $nextPage = 'custom';
          }
          else {
            $nextPage = 'settings';
          }
          break;

        case 'MembershipBlock':
          $subPage     = 'membership';
          $subPageName = 'MembershipBlock';
          $nextPage    = 'thankyou';
          break;

        default:
          $subPage     = strtolower($className);
          $subPageName = $className;
          $nextPage    = strtolower($nextPage);

          if ($subPage == 'amount') {
            $nextPage = 'membership';
          }
          elseif ($subPage == 'thankyou') {
            $nextPage = 'friend';
          }
          break;
      }

      CRM_Core_Session::setStatus(ts("'%1' information has been saved.",
          array(1 => $subPageName)
        ), ts('Saved'), 'success');

      $this->postProcessHook();

      if ($this->controller->getButtonName('submit') == "_qf_{$className}_next") {
        CRM_Utils_System::redirect(CRM_Utils_System::url("civicrm/admin/contribute/{$subPage}",
            "action=update&reset=1&id={$this->_id}"
          ));
      }
      elseif ($this->controller->getButtonName('submit') == "_qf_{$className}_submit_savenext") {
        if ($nextPage) {
          CRM_Utils_System::redirect(CRM_Utils_System::url("civicrm/admin/contribute/{$nextPage}",
              "action=update&reset=1&id={$this->_id}"
            ));
        }
        else {
          CRM_Utils_System::redirect(CRM_Utils_System::url("civicrm/admin/contribute",
              "reset=1"
            ));
        }
      }
      else {
        CRM_Utils_System::redirect(CRM_Utils_System::url("civicrm/admin/contribute", 'reset=1'));
      }
    }
  }

  function getTemplateFileName() {
    if ($this->controller->getPrint() == CRM_Core_Smarty::PRINT_NOFORM ||
      $this->getVar('_id') <= 0 ||
      ($this->_action & CRM_Core_Action::DELETE) ||
      (CRM_Utils_String::getClassName($this->_name) == 'AddProduct')
    ) {
      return parent::getTemplateFileName();
    }
    else {
      // hack lets suppress the form rendering for now
      self::$_template->assign('isForm', FALSE);
      return 'CRM/Contribute/Form/ContributionPage/Tab.tpl';
    }
  }
}

