<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2015
 */

/**
 * This class generates form components for processing Event.
 */
class CRM_Event_Form_ManageEvent extends CRM_Core_Form {

  /**
   * The id of the event we are processing.
   *
   * @var int
   */
  public $_id;

  /**
   * Is this the first page?
   *
   * @var boolean
   */
  protected $_first = FALSE;

  /**
   * Are we in single form mode or wizard mode?
   *
   * @var boolean
   */
  protected $_single;

  protected $_action;

  /**
   * Are we actually managing an event template?
   * @var boolean
   */
  protected $_isTemplate = FALSE;

  /**
   * Pre-populate fields based on this template event_id
   * @var integer
   */
  protected $_templateId;

  protected $_cancelURL = NULL;

  /**
   * The campaign id of the existing event, we use this to know if we need to update
   * the participant records
   */
  protected $_campaignID = NULL;

  /**
   * Check if repeating event.
   */
  public $_isRepeatingEvent;

  /**
   * Explicitly declare the entity api name.
   */
  public function getDefaultEntity() {
    return 'Event';
  }

  /**
   * Explicitly declare the form context.
   */
  public function getDefaultContext() {
    return 'create';
  }

  /**
   * Set variables up before form is built.
   */
  public function preProcess() {
    $config = CRM_Core_Config::singleton();
    if (in_array('CiviEvent', $config->enableComponents)) {
      $this->assign('CiviEvent', TRUE);
    }
    CRM_Core_Form_RecurringEntity::preProcess('civicrm_event');

    $this->_action = CRM_Utils_Request::retrieve('action', 'String', $this, FALSE, 'add', 'REQUEST');

    $this->assign('action', $this->_action);

    $this->_id = CRM_Utils_Request::retrieve('id', 'Positive', $this, FALSE, NULL, 'GET');
    if ($this->_id) {
      $this->_isRepeatingEvent = CRM_Core_BAO_RecurringEntity::getParentFor($this->_id, 'civicrm_event');
      $this->assign('eventId', $this->_id);
      if (!empty($this->_addBlockName) && empty($this->_addProfileBottom) && empty($this->_addProfileBottomAdd)) {
        $this->add('hidden', 'id', $this->_id);
      }
      $this->_single = TRUE;

      $params = array('id' => $this->_id);
      CRM_Event_BAO_Event::retrieve($params, $eventInfo);

      // its an update mode, do a permission check
      if (!CRM_Event_BAO_Event::checkPermission($this->_id, CRM_Core_Permission::EDIT)) {
        CRM_Core_Error::fatal(ts('You do not have permission to access this page.'));
      }

      $participantListingID = CRM_Utils_Array::value('participant_listing_id', $eventInfo);
      //CRM_Core_DAO::getFieldValue( 'CRM_Event_DAO_Event', $this->_id, 'participant_listing_id' );
      if ($participantListingID) {
        $participantListingURL = CRM_Utils_System::url('civicrm/event/participant',
          "reset=1&id={$this->_id}",
          TRUE, NULL, TRUE, TRUE
        );
        $this->assign('participantListingURL', $participantListingURL);
      }

      $this->assign('isOnlineRegistration', CRM_Utils_Array::value('is_online_registration', $eventInfo));

      $this->assign('id', $this->_id);
    }

    // figure out whether we’re handling an event or an event template
    if ($this->_id) {
      $this->_isTemplate = CRM_Utils_Array::value('is_template', $eventInfo);
    }
    elseif ($this->_action & CRM_Core_Action::ADD) {
      $this->_isTemplate = CRM_Utils_Request::retrieve('is_template', 'Boolean', $this);
    }

    $this->assign('isTemplate', $this->_isTemplate);

    if ($this->_id) {
      if ($this->_isTemplate) {
        $title = CRM_Utils_Array::value('template_title', $eventInfo);
        CRM_Utils_System::setTitle(ts('Edit Event Template') . " - $title");
      }
      else {
        $configureText = ts('Configure Event');
        $title = CRM_Utils_Array::value('title', $eventInfo);
        //If it is a repeating event change title
        if ($this->_isRepeatingEvent) {
          $configureText = 'Configure Repeating Event';
        }
        CRM_Utils_System::setTitle($configureText . " - $title");
      }
      $this->assign('title', $title);
    }
    elseif ($this->_action & CRM_Core_Action::ADD) {
      if ($this->_isTemplate) {
        $title = ts('New Event Template');
        CRM_Utils_System::setTitle($title);
      }
      else {
        $title = ts('New Event');
        CRM_Utils_System::setTitle($title);
      }
      $this->assign('title', $title);
    }

    if (CRM_Core_Permission::check('view event participants') &&
      CRM_Core_Permission::check('view all contacts')
    ) {
      $statusTypes = CRM_Event_PseudoConstant::participantStatus(NULL, 'is_counted = 1', 'label');
      $statusTypesPending = CRM_Event_PseudoConstant::participantStatus(NULL, 'is_counted = 0', 'label');
      $findParticipants['statusCounted'] = implode(', ', array_values($statusTypes));
      $findParticipants['statusNotCounted'] = implode(', ', array_values($statusTypesPending));
      $this->assign('findParticipants', $findParticipants);
    }

    $this->_templateId = (int) CRM_Utils_Request::retrieve('template_id', 'Integer', $this);

    //Is a repeating event
    if ($this->_isRepeatingEvent) {
      $isRepeatingEntity = TRUE;
      $this->assign('isRepeatingEntity', $isRepeatingEntity);
    }

    // CRM-16776 - show edit/copy/create buttons for Profiles if user has required permission.
    $ufGroups = CRM_Core_PseudoConstant::get('CRM_Core_DAO_UFField', 'uf_group_id');
    $ufCreate = CRM_ACL_API::group(CRM_Core_Permission::CREATE, NULL, 'civicrm_uf_group', $ufGroups);
    $ufEdit = CRM_ACL_API::group(CRM_Core_Permission::EDIT, NULL, 'civicrm_uf_group', $ufGroups);
    $checkPermission = array(
      array(
        'administer CiviCRM',
        'manage event profiles',
      ),
    );
    if (CRM_Core_Permission::check($checkPermission) || !empty($ufCreate) || !empty($ufEdit)) {
      $this->assign('perm', TRUE);
    }

    // also set up tabs
    CRM_Event_Form_ManageEvent_TabHeader::build($this);

    // Set Done button URL and breadcrumb. Templates go back to Manage Templates,
    // otherwise go to Manage Event for new event or ManageEventEdit if event if exists.
    $breadCrumb = array();
    if (!$this->_isTemplate) {
      if ($this->_id) {
        $this->_doneUrl = CRM_Utils_System::url(CRM_Utils_System::currentPath(),
          "action=update&reset=1&id={$this->_id}"
        );
      }
      else {
        $this->_doneUrl = CRM_Utils_System::url('civicrm/event/manage',
          'reset=1'
        );
        $breadCrumb = array(
          array(
            'title' => ts('Manage Events'),
            'url' => $this->_doneUrl,
          ),
        );
      }
    }
    else {
      $this->_doneUrl = CRM_Utils_System::url('civicrm/admin/eventTemplate', 'reset=1');
      $breadCrumb = array(
        array(
          'title' => ts('Manage Event Templates'),
          'url' => $this->_doneUrl,
        ),
      );
    }
    CRM_Utils_System::appendBreadCrumb($breadCrumb);
  }

  /**
   * Set default values for the form.
   *
   * For edit/view mode the default values are retrieved from the database.
   */
  public function setDefaultValues() {
    $defaults = array();
    if (isset($this->_id)) {
      $params = array('id' => $this->_id);
      CRM_Event_BAO_Event::retrieve($params, $defaults);

      $this->_campaignID = CRM_Utils_Array::value('campaign_id', $defaults);
    }
    elseif ($this->_templateId) {
      $params = array('id' => $this->_templateId);
      CRM_Event_BAO_Event::retrieve($params, $defaults);
      $defaults['is_template'] = $this->_isTemplate;
      $defaults['template_id'] = $defaults['id'];
      unset($defaults['id']);
    }
    else {
      $defaults['is_active'] = 1;
      $defaults['style'] = 'Inline';
    }

    return $defaults;
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    $session = CRM_Core_Session::singleton();

    $this->_cancelURL = CRM_Utils_Array::value('cancelURL', $_POST);

    if (!$this->_cancelURL) {
      if ($this->_isTemplate) {
        $this->_cancelURL = CRM_Utils_System::url('civicrm/admin/eventTemplate',
          'reset=1'
        );
      }
      else {
        $this->_cancelURL = CRM_Utils_System::url('civicrm/event/manage',
          'reset=1'
        );
      }
    }

    if ($this->_cancelURL) {
      $this->addElement('hidden', 'cancelURL', $this->_cancelURL);
    }

    if ($this->_single) {
      $buttons = array(
        array(
          'type' => 'upload',
          'name' => ts('Save'),
          'isDefault' => TRUE,
        ),
        array(
          'type' => 'upload',
          'name' => ts('Save and Done'),
          'spacing' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
          'subName' => 'done',
        ),
        array(
          'type' => 'cancel',
          'name' => ts('Cancel'),
        ),
      );
      $this->addButtons($buttons);
    }
    else {
      $buttons = array();
      if (!$this->_first) {
        $buttons[] = array(
          'type' => 'back',
          'name' => ts('Previous'),
          'spacing' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
        );
      }
      $buttons[] = array(
        'type' => 'upload',
        'name' => ts('Continue'),
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
    $this->add('hidden', 'is_template', $this->_isTemplate);
  }

  public function endPostProcess() {
    // make submit buttons keep the current working tab opened.
    if ($this->_action & CRM_Core_Action::UPDATE) {
      $className = CRM_Utils_String::getClassName($this->_name);

      // hack for special cases.
      switch ($className) {
        case 'Event':
          $attributes = $this->getVar('_attributes');
          $subPage = strtolower(basename(CRM_Utils_Array::value('action', $attributes)));
          break;

        case 'EventInfo':
          $subPage = 'settings';
          break;

        case 'ScheduleReminders':
          $subPage = 'reminder';
          break;

        default:
          $subPage = strtolower($className);
          break;
      }

      CRM_Core_Session::setStatus(ts("'%1' information has been saved.",
        array(1 => CRM_Utils_Array::value('title', CRM_Utils_Array::value($subPage, $this->get('tabHeader')), $className))
      ), ts('Saved'), 'success');

      $config = CRM_Core_Config::singleton();
      if (in_array('CiviCampaign', $config->enableComponents)) {
        $values = $this->controller->exportValues($this->_name);
        $newCampaignID = CRM_Utils_Array::value('campaign_id', $values);
        $eventID = CRM_Utils_Array::value('id', $values);
        if ($eventID && $this->_campaignID != $newCampaignID) {
          CRM_Event_BAO_Event::updateParticipantCampaignID($eventID, $newCampaignID);
        }
      }
      $this->postProcessHook();
      if ($this->controller->getButtonName('submit') == "_qf_{$className}_upload_done") {
        if ($this->_isTemplate) {
          CRM_Core_Session::singleton()
            ->pushUserContext(CRM_Utils_System::url('civicrm/admin/eventTemplate', 'reset=1'));
        }
        else {
          CRM_Core_Session::singleton()->pushUserContext(CRM_Utils_System::url('civicrm/event/manage', 'reset=1'));
        }
      }
      else {
        CRM_Core_Session::singleton()->pushUserContext(CRM_Utils_System::url("civicrm/event/manage/{$subPage}",
          "action=update&reset=1&id={$this->_id}"
        ));
      }
    }
  }

  /**
   * @return string
   */
  public function getTemplateFileName() {
    if ($this->controller->getPrint() || $this->getVar('_id') <= 0 || $this->_action & CRM_Core_Action::DELETE) {
      return parent::getTemplateFileName();
    }
    else {
      // hack lets suppress the form rendering for now
      self::$_template->assign('isForm', FALSE);
      return 'CRM/Event/Form/ManageEvent/Tab.tpl';
    }
  }

  /**
   * Pre-load libraries required by Online Registration Profile fields
   */
  public static function addProfileEditScripts() {
    CRM_UF_Page_ProfileEditor::registerProfileScripts();
    CRM_UF_Page_ProfileEditor::registerSchemas(array('IndividualModel', 'ParticipantModel'));
  }

}
