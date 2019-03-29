<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
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
 * @copyright CiviCRM LLC (c) 2004-2019
 */

/**
 * Contribution Page form.
 */
class CRM_Contribute_Form_ContributionPage extends CRM_Core_Form {

  /**
   * The page id saved to the session for an update.
   *
   * @var int
   */
  protected $_id;

  /**
   * The pledgeBlock id saved to the session for an update.
   *
   * @var int
   */
  protected $_pledgeBlockID;

  /**
   * Are we in single form mode or wizard mode?
   *
   * @var boolean
   */
  protected $_single;

  /**
   * Is this the first page?
   *
   * @var boolean
   */
  protected $_first = FALSE;

  /**
   * Is this the last page?
   *
   * @var boolean
   */
  protected $_last = FALSE;

  /**
   * Store price set id.
   *
   * @var int
   */
  protected $_priceSetID = NULL;

  protected $_values;

  /**
   * Explicitly declare the entity api name.
   */
  public function getDefaultEntity() {
    return 'Contribution';
  }

  /**
   * Set variables up before form is built.
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

    // CRM-16776 - show edit/copy/create buttons on Profiles Tab if user has required permission.
    if (CRM_Core_Permission::check('administer CiviCRM')) {
      $this->assign('perm', TRUE);
    }
    // set up tabs
    CRM_Contribute_Form_ContributionPage_TabHeader::build($this);

    if ($this->_action == CRM_Core_Action::UPDATE) {
      CRM_Utils_System::setTitle(ts('Configure Page - %1', [1 => $title]));
    }
    elseif ($this->_action == CRM_Core_Action::VIEW) {
      CRM_Utils_System::setTitle(ts('Preview Page - %1', [1 => $title]));
    }
    elseif ($this->_action == CRM_Core_Action::DELETE) {
      CRM_Utils_System::setTitle(ts('Delete Page - %1', [1 => $title]));
    }

    //cache values.
    $this->_values = $this->get('values');
    if (!is_array($this->_values)) {
      $this->_values = [];
      if (isset($this->_id) && $this->_id) {
        $params = ['id' => $this->_id];
        CRM_Core_DAO::commonRetrieve('CRM_Contribute_DAO_ContributionPage', $params, $this->_values);
        CRM_Contribute_BAO_ContributionPage::setValues($this->_id, $this->_values);
      }
      $this->set('values', $this->_values);
    }

    // Check permission to edit contribution page
    if (CRM_Financial_BAO_FinancialType::isACLFinancialTypeStatus() && $this->_action & CRM_Core_Action::UPDATE) {
      $financialTypeID = CRM_Contribute_PseudoConstant::financialType($this->_values['financial_type_id']);
      if (!CRM_Core_Permission::check('edit contributions of type ' . $financialTypeID)) {
        CRM_Core_Error::fatal(ts('You do not have permission to access this page.'));
      }
    }

    // Preload libraries required by the "Profiles" tab
    $schemas = ['IndividualModel', 'OrganizationModel', 'ContributionModel'];
    if (in_array('CiviMember', CRM_Core_Config::singleton()->enableComponents)) {
      $schemas[] = 'MembershipModel';
    }
    CRM_UF_Page_ProfileEditor::registerProfileScripts();
    CRM_UF_Page_ProfileEditor::registerSchemas($schemas);
  }

  /**
   * Build the form object.
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
      $buttons = [
        [
          'type' => 'next',
          'name' => ts('Save'),
          'spacing' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
          'isDefault' => TRUE,
        ],
        [
          'type' => 'upload',
          'name' => ts('Save and Done'),
          'spacing' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
          'subName' => 'done',
        ],
      ];
      if (!$this->_last) {
        $buttons[] = [
          'type' => 'submit',
          'name' => ts('Save and Next'),
          'spacing' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
          'subName' => 'savenext',
        ];
      }
      $buttons[] = [
        'type' => 'cancel',
        'name' => ts('Cancel'),
      ];
      $this->addButtons($buttons);
    }
    else {
      $buttons = [];
      if (!$this->_first) {
        $buttons[] = [
          'type' => 'back',
          'name' => ts('Previous'),
          'spacing' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
        ];
      }
      $buttons[] = [
        'type' => 'next',
        'name' => ts('Continue'),
        'spacing' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
        'isDefault' => TRUE,
      ];
      $buttons[] = [
        'type' => 'cancel',
        'name' => ts('Cancel'),
      ];

      $this->addButtons($buttons);
    }

    $session->replaceUserContext($this->_cancelURL);
    // views are implemented as frozen form
    if ($this->_action & CRM_Core_Action::VIEW) {
      $this->freeze();
      $this->addElement('button', 'done', ts('Done'), ['onclick' => "location.href='civicrm/admin/custom/group?reset=1&action=browse'"]);
    }

    // don't show option for contribution amounts section if membership price set
    // this flag is sent to template

    $membershipBlock = new CRM_Member_DAO_MembershipBlock();
    $membershipBlock->entity_table = 'civicrm_contribution_page';
    $membershipBlock->entity_id = $this->_id;
    $membershipBlock->is_active = 1;
    $hasMembershipBlk = FALSE;
    if ($membershipBlock->find(TRUE) &&
      ($setID = CRM_Price_BAO_PriceSet::getFor('civicrm_contribution_page', $this->_id, NULL, 1))
    ) {
      $extends = CRM_Core_DAO::getFieldValue('CRM_Price_DAO_PriceSet', $setID, 'extends');
      if ($extends && $extends == CRM_Core_Component::getComponentID('CiviMember')) {
        $hasMembershipBlk = TRUE;
      }
    }
    // set value in DOM that membership price set exists
    CRM_Core_Resources::singleton()->addSetting(['memberPriceset' => $hasMembershipBlk]);
  }

  /**
   * Set default values for the form. Note that in edit/view mode
   * the default values are retrieved from the database
   *
   *
   * @return array
   *   defaults
   */
  public function setDefaultValues() {
    //some child classes calling setdefaults directly w/o preprocess.
    $this->_values = $this->get('values');
    if (!is_array($this->_values)) {
      $this->_values = [];
      if (isset($this->_id) && $this->_id) {
        $params = ['id' => $this->_id];
        CRM_Core_DAO::commonRetrieve('CRM_Contribute_DAO_ContributionPage', $params, $this->_values);
      }
      $this->set('values', $this->_values);
    }
    $defaults = $this->_values;

    $config = CRM_Core_Config::singleton();
    if (isset($this->_id)) {

      //set defaults for pledgeBlock values.
      $pledgeBlockParams = [
        'entity_id' => $this->_id,
        'entity_table' => ts('civicrm_contribution_page'),
      ];
      $pledgeBlockDefaults = [];
      CRM_Pledge_BAO_PledgeBlock::retrieve($pledgeBlockParams, $pledgeBlockDefaults);
      if ($this->_pledgeBlockID = CRM_Utils_Array::value('id', $pledgeBlockDefaults)) {
        $defaults['is_pledge_active'] = TRUE;
      }
      $pledgeBlock = [
        'is_pledge_interval',
        'max_reminders',
        'initial_reminder_day',
        'additional_reminder_day',
        'pledge_start_date',
        'is_pledge_start_date_visible',
        'is_pledge_start_date_editable',
      ];
      foreach ($pledgeBlock as $key) {
        $defaults[$key] = CRM_Utils_Array::value($key, $pledgeBlockDefaults);
        if ($key == 'pledge_start_date' && CRM_Utils_Array::value($key, $pledgeBlockDefaults)) {
          $defaultPledgeDate = (array) json_decode($pledgeBlockDefaults['pledge_start_date']);
          $pledgeDateFields = [
            'pledge_calendar_date' => 'calendar_date',
            'pledge_calendar_month' => 'calendar_month',
          ];
          $defaults['pledge_default_toggle'] = key($defaultPledgeDate);
          foreach ($pledgeDateFields as $key => $value) {
            if (array_key_exists($value, $defaultPledgeDate)) {
              $defaults[$key] = reset($defaultPledgeDate);
              $this->assign($key, reset($defaultPledgeDate));
            }
          }
        }
      }
      if (!empty($pledgeBlockDefaults['pledge_frequency_unit'])) {
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
      $this->_priceSetID = CRM_Price_BAO_PriceSet::getFor('civicrm_contribution_page', $this->_id, $usedFor, 1);
      if ($this->_priceSetID) {
        $defaults['price_set_id'] = $this->_priceSetID;
      }
    }
    else {
      $defaults['is_active'] = 1;
      // set current date as start date
      // @todo look to change to $defaults['start_date'] = date('Ymd His');
      // main settings form overrides this to implement above but this is left here
      // 'in case' another extending form uses start_date - for now
      list($defaults['start_date'], $defaults['start_date_time']) = CRM_Utils_Date::setDateDefaults();
    }

    if (!empty($defaults['recur_frequency_unit'])) {
      $defaults['recur_frequency_unit'] = array_fill_keys(explode(CRM_Core_DAO::VALUE_SEPARATOR,
        $defaults['recur_frequency_unit']
      ), '1');
    }
    else {
      // CRM-10860
      $defaults['recur_frequency_unit'] = ['month' => 1];
    }

    // confirm page starts out enabled
    if (!isset($defaults['is_confirm_enabled'])) {
      $defaults['is_confirm_enabled'] = 1;
    }

    return $defaults;
  }

  /**
   * Process the form.
   */
  public function postProcess() {
    $pageId = $this->get('id');
    //page is newly created.
    if ($pageId && !$this->_id) {
      $session = CRM_Core_Session::singleton();
      $session->pushUserContext(CRM_Utils_System::url('civicrm/admin/contribute', 'reset=1'));
    }
  }

  public function endPostProcess() {
    // make submit buttons keep the current working tab opened, or save and next tab
    if ($this->_action & CRM_Core_Action::UPDATE) {
      $className = CRM_Utils_String::getClassName($this->_name);

      //retrieve list of pages from StateMachine and find next page
      //this is quite painful because StateMachine is full of protected variables
      //so we have to retrieve all pages, find current page, and then retrieve next
      $stateMachine = new CRM_Contribute_StateMachine_ContributionPage($this);
      $states = $stateMachine->getStates();
      $statesList = array_keys($states);
      $currKey = array_search($className, $statesList);
      $nextPage = (array_key_exists($currKey + 1, $statesList)) ? $statesList[$currKey + 1] : '';

      //unfortunately, some classes don't map to subpage names, so we alter the exceptions

      switch ($className) {
        case 'Contribute':
          $attributes = $this->getVar('_attributes');
          $subPage = CRM_Utils_Request::retrieveComponent($attributes);
          if ($subPage == 'friend') {
            $nextPage = 'custom';
          }
          else {
            $nextPage = 'settings';
          }
          break;

        case 'MembershipBlock':
          $subPage = 'membership';
          $nextPage = 'thankyou';
          break;

        default:
          $subPage = strtolower($className);
          $nextPage = strtolower($nextPage);

          if ($subPage == 'amount') {
            $nextPage = 'membership';
          }
          elseif ($subPage == 'thankyou') {
            $nextPage = 'friend';
          }
          break;
      }

      CRM_Core_Session::setStatus(ts("'%1' information has been saved.",
        [1 => CRM_Utils_Array::value('title', CRM_Utils_Array::value($subPage, $this->get('tabHeader')), $className)]
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

  /**
   * Use the form name to create the tpl file name.
   *
   * @return string
   */
  /**
   * @return string
   */
  public function getTemplateFileName() {
    if ($this->controller->getPrint() || $this->getVar('_id') <= 0 ||
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
