<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.4                                                |
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
class CRM_Grant_Form_GrantPage extends CRM_Core_Form {

  /**
   * the page id saved to the session for an update
   *
   * @var int
   * @access protected
   */
  protected $_id;
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
   * is this the last page?
   *
   * @var boolean
   * @access protected
   */
  protected $_isLast = FALSE;
  
  protected $_values;

  /**
   * Function to set variables up before form is built
   *
   * @return void
   * @access public
   */
  public function preProcess() {
    // current grant application page id
    $this->_id = CRM_Utils_Request::retrieve('id', 'Positive',
      $this, FALSE, NULL, 'REQUEST'
    );
    $this->assign('grantApplicationPageID', $this->_id);

    // get the requested action
    $this->_action = CRM_Utils_Request::retrieve('action', 'String',
      // default to 'browse'
      $this, FALSE, 'browse'
    );

    // setting title and 3rd level breadcrumb for html page if application page exists
    if ($this->_id) {
      $title = CRM_Core_DAO::getFieldValue('CRM_Grant_DAO_GrantApplicationPage', $this->_id, 'title');

      if ($this->_action == CRM_Core_Action::UPDATE) {
        $this->_single = TRUE;
      }
    }

    // set up tabs
    CRM_Grant_Form_GrantPage_TabHeader::build($this);

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
        CRM_Core_DAO::commonRetrieve('CRM_Grant_DAO_GrantApplicationPage', $params, $this->_values);
      }
      $this->set('values', $this->_values);
    }
	$schemas = array('IndividualModel', 'OrganizationModel', 'GrantModel');
    CRM_UF_Page_ProfileEditor::registerProfileScripts();
    CRM_UF_Page_ProfileEditor::registerSchemas($schemas);
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
      $this->_cancelURL = CRM_Utils_System::url('civicrm/grant', 'reset=1');
    }

    if ($this->_cancelURL) {
      $this->addElement('hidden', 'cancelURL', $this->_cancelURL);
    }


    if ($this->_single) {
      $buttons = array( 
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
      );
      if (!$this->_isLast) {
        $buttons[] = array(
          'type' => 'submit',
          'name' => ts('Save and Next'),
          'spacing' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
          'subName' => 'savenext',
        );
      }
    }
    else {
      $buttons = array();
      $buttons[] = array(
        'type' => 'next',
        'name' => ts('Continue >>'),
        'spacing' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
        'isDefault' => TRUE,
      );
    }

    $buttons[] = array(
      'type' => 'cancel',
      'name' => ts('Cancel'),
    );
    $this->addButtons($buttons);
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
        CRM_Core_DAO::commonRetrieve('CRM_Grant_DAO_GrantApplicationPage', $params, $this->_values);
      }
      $this->set('values', $this->_values);
    }
    $defaults = $this->_values;

    $config = CRM_Core_Config::singleton();
    if (isset($this->_id)) {

      // fix the display of the monetary value, CRM-4038
      if (isset($defaults['default_amount'])) {
        $defaults['default_amount'] = CRM_Utils_Money::format($defaults['default_amount'], NULL, '%a');
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
      $defaults['for_organization'] = ts('I am applying for a grant on behalf of an organization.');
    }
    if (CRM_Utils_Array::value('is_for_organization', $defaults)) {
      $defaults['is_organization'] = 1;
    }
    else {
      $defaults['is_for_organization'] = 1;
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
      $session->pushUserContext(CRM_Utils_System::url('civicrm/admin/grant', 'reset=1'));
    }
  }

  function endPostProcess() {
     
    // make submit buttons keep the current working tab opened, or save and next tab
    if ($this->_action & CRM_Core_Action::UPDATE) {
      $className = CRM_Utils_String::getClassName($this->_name);
     
      //retrieve list of pages from StateMachine and find next page
      //this is quite painful because StateMachine is full of protected variables
      //so we have to retrieve all pages, find current page, and then retrieve next
      $stateMachine = new CRM_Grant_StateMachine_GrantPage($this);
      $states       = $stateMachine->getStates();
      $statesList   = array_keys($states);
      $currKey      = array_search($className, $statesList);
      $nextPage     = (array_key_exists($currKey + 1, $statesList)) ? $statesList[$currKey + 1] : '';

      //unfortunately, some classes don't map to subpage names, so we alter the exceptions
          
      if ($className) {
      
          $subPage     = strtolower($className);
          $subPageName = $className;
          $nextPage    = strtolower($nextPage);
        
          if ( $subPage == "custom" ) {
              $nextPage = "settings";
          }
      }

      CRM_Core_Session::setStatus(ts("'%1' information has been saved.",
          array(1 => $subPageName)
        ));

      $this->postProcessHook();

      if ($this->controller->getButtonName('submit') == "_qf_{$className}_next") {
            CRM_Utils_System::redirect(CRM_Utils_System::url("civicrm/admin/grant/{$subPage}",
           "action=update&reset= &id={$this->_id}"
          ));
      }
      elseif ($this->controller->getButtonName('submit') == "_qf_{$className}_submit_savenext") {
        if ($nextPage) {
          CRM_Utils_System::redirect(CRM_Utils_System::url("civicrm/admin/grant/{$nextPage}",
              "action=update&reset=1&id={$this->_id}"
            ));
        }
        else {
          CRM_Utils_System::redirect(CRM_Utils_System::url("civicrm/admin/grant",
              "reset=1"
            ));
        }
      }
      else {
        CRM_Utils_System::redirect(CRM_Utils_System::url("civicrm/grant", 'reset=1'));
      }
    }
  }

  function getTemplateFileName() {
    if ($this->controller->getPrint() || $this->getVar('_id') <= 0 ||
      ($this->_action & CRM_Core_Action::DELETE) ||
      (CRM_Utils_String::getClassName($this->_name) == 'AddProduct')
    ) {
      return parent::getTemplateFileName();
    }
    else {
      // hack lets suppress the form rendering for now
      self::$_template->assign('isForm', FALSE);
      return 'CRM/Grant/Form/GrantPage/Tab.tpl';
    }
  }
}

