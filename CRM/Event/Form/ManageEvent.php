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
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */

/**
 * This class generates form components for processing Event.
 */
class CRM_Event_Form_ManageEvent extends CRM_Core_Form {
  use CRM_Event_Form_EventFormTrait;

  /**
   * The id of the event we are processing.
   *
   * @var int
   */
  public $_id;

  /**
   * Is this the first page?
   *
   * @var bool
   */
  protected $_first = FALSE;

  /**
   * Are we in single form mode or wizard mode?
   *
   * @var bool
   */
  protected $_single;

  public $_action;

  /**
   * Are we actually managing an event template?
   * @var bool
   */
  protected $_isTemplate = FALSE;

  /**
   * Pre-populate fields based on this template event_id.
   *
   * @var int
   */
  protected $_templateId;

  /**
   * The campaign id of the existing event, we use this to know if we need to update
   * the participant records
   * @var int
   */
  protected $_campaignID = NULL;

  /**
   * Check if repeating event.
   * @var bool
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
    $this->assign('CiviEvent', CRM_Core_Component::isEnabled('CiviEvent'));
    CRM_Core_Form_RecurringEntity::preProcess('civicrm_event');

    $this->_action = CRM_Utils_Request::retrieve('action', 'String', $this, FALSE, 'add', 'REQUEST');

    $this->assign('action', $this->_action);

    if ($this->getEventID()) {
      $this->_isRepeatingEvent = CRM_Core_BAO_RecurringEntity::getParentFor($this->_id, 'civicrm_event');
      $this->assign('eventId', $this->_id);
      $this->_single = TRUE;

      // its an update mode, do a permission check
      if (!CRM_Event_BAO_Event::checkPermission($this->_id, CRM_Core_Permission::EDIT)) {
        CRM_Core_Error::statusBounce(ts('You do not have permission to access this page.'));
      }

      $participantListingID = $this->getEventValue('participant_listing_id');
      if ($participantListingID) {
        $participantListingURL = CRM_Utils_System::url('civicrm/event/participant',
          "reset=1&id={$this->_id}",
          FALSE, NULL, TRUE, TRUE
        );
      }
      $this->assign('participantListingURL', $participantListingURL ?? NULL);
      $this->assign('participantListingID', $participantListingID);
      $this->assign('isOnlineRegistration', $this->getEventValue('is_online_registration'));

      $this->assign('id', $this->_id);
    }

    // figure out whether we’re handling an event or an event template
    if ($this->_id) {
      $this->_isTemplate = $this->getEventValue('is_template');
    }
    elseif ($this->_action & CRM_Core_Action::ADD) {
      $this->_isTemplate = CRM_Utils_Request::retrieve('is_template', 'Boolean', $this);
    }

    $this->assign('isTemplate', $this->_isTemplate);

    // Set "Manage Event" Title
    $title = NULL;
    if ($this->_id) {
      if ($this->_isTemplate) {
        $title = ts('Edit Event Template') . ' - ' . ($this->getEventValue('template_title'));
      }
      else {
        $configureText = $this->_isRepeatingEvent ? ts('Configure Repeating Event') : ts('Configure Event');
        $title = $configureText . ' - ' . $this->getEventValue('title');
      }
    }
    elseif ($this->_action & CRM_Core_Action::ADD) {
      $title = $this->_isTemplate ? ts('New Event Template') : ts('New Event');
    }
    $this->setTitle($title);

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

    $this->assign('isRepeatingEntity', $this->_isRepeatingEvent);

    // CRM-16776 - show edit/copy/create buttons for Profiles if user has required permission.
    $ufGroups = CRM_Core_DAO_UFField::buildOptions('uf_group_id');
    $ufCreate = CRM_ACL_API::group(CRM_Core_Permission::CREATE, NULL, 'civicrm_uf_group', $ufGroups);
    $ufEdit = CRM_ACL_API::group(CRM_Core_Permission::EDIT, NULL, 'civicrm_uf_group', $ufGroups);
    $checkPermission = [
      [
        'administer CiviCRM data',
        'manage event profiles',
      ],
    ];
    if (CRM_Core_Permission::check($checkPermission) || !empty($ufCreate) || !empty($ufEdit)) {
      $this->assign('perm', TRUE);
    }
    else {
      $this->assign('perm', FALSE);
    }

    // also set up tabs
    $this->build();

    // Set Done button URL and breadcrumb. Templates go back to Manage Templates,
    // otherwise go to Manage Event for new event or ManageEventEdit if event if exists.
    if (!$this->_isTemplate) {
      if ($this->_id) {
        $breadCrumb = ['title' => ts('Configure Event')];
        $breadCrumb['url'] = CRM_Utils_System::url(CRM_Utils_System::currentPath(),
          "action=update&reset=1&id={$this->_id}"
        );
      }
      else {
        $breadCrumb = ['title' => ts('Manage Events')];
        $breadCrumb['url'] = CRM_Utils_System::url('civicrm/event/manage',
          'reset=1'
        );
      }
      CRM_Utils_System::appendBreadCrumb([$breadCrumb]);
    }
    else {
      CRM_Utils_System::appendBreadCrumb([
        [
          'title' => ts('Manage Event Templates'),
          'url' => CRM_Utils_System::url('civicrm/admin/eventTemplate', 'reset=1'),
        ],
      ]);
    }
  }

  /**
   * Set default values for the form.
   *
   * For edit/view mode the default values are retrieved from the database.
   */
  public function setDefaultValues() {
    $defaults = [];
    $event = \Civi\Api4\Event::get(FALSE);
    if (isset($this->_id)) {
      $event->addWhere('id', '=', $this->_id);
      $defaults = $event->execute()->first();
      $this->_campaignID = $defaults['campaign_id'] ?? NULL;
    }
    elseif ($this->_templateId) {
      $event->addWhere('id', '=', $this->_templateId);
      $defaults = $event->execute()->first();
      $defaults['is_template'] = $this->_isTemplate;
      $defaults['template_id'] = $defaults['id'];
      unset($defaults['id']);
      unset($defaults['start_date']);
      unset($defaults['end_date']);
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

    $cancelURL = $_POST['cancelURL'] ?? NULL;

    if (!$cancelURL) {
      if ($this->_isTemplate) {
        $cancelURL = CRM_Utils_System::url('civicrm/admin/eventTemplate',
          'reset=1'
        );
      }
      else {
        $cancelURL = CRM_Utils_System::url('civicrm/event/manage',
          'reset=1'
        );
      }
    }

    if ($cancelURL) {
      $this->addElement('hidden', 'cancelURL', $cancelURL);
    }

    if ($this->_single) {
      $buttons = [
        [
          'type' => 'upload',
          'name' => ts('Save'),
          'isDefault' => TRUE,
        ],
        [
          'type' => 'cancel',
          'name' => ts('Cancel'),
        ],
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
        'type' => 'upload',
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
    $session->replaceUserContext($cancelURL);
    $this->add('hidden', 'is_template', $this->_isTemplate);
  }

  public function endPostProcess() {
    // make submit buttons keep the current working tab opened.
    if ($this->_action & CRM_Core_Action::UPDATE) {
      $className = CRM_Utils_String::getClassName($this->_name);

      // hack for special cases.
      switch ($className) {
        case 'Event':
          $attributes = $this->_attributes;
          $subPage = CRM_Utils_Request::retrieveComponent($attributes);
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
        [1 => $this->get('tabHeader')[$subPage]['title'] ?? $className]
      ), $this->getTitle(), 'success');

      if (CRM_Core_Component::isEnabled('CiviCampaign')) {
        $values = $this->controller->exportValues($this->_name);
        $newCampaignID = $values['campaign_id'] ?? NULL;
        $eventID = $values['id'] ?? NULL;
        if ($eventID && $this->_campaignID != $newCampaignID) {
          CRM_Event_BAO_Event::updateParticipantCampaignID($eventID, $newCampaignID);
        }
      }
      $this->postProcessHook();
      if ($this->controller->getButtonName('submit') === "_qf_{$className}_upload_done") {
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
    if ($this->controller->getPrint() || $this->_id <= 0 || $this->_action & CRM_Core_Action::DELETE) {
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
    CRM_UF_Page_ProfileEditor::registerSchemas(['IndividualModel', 'ParticipantModel']);
  }

  /**
   * @return int|null
   * @throws \CRM_Core_Exception
   */
  public function getEventID() {
    if (!$this->_id) {
      $this->_id = CRM_Utils_Request::retrieve('id', 'Positive', $this, FALSE, NULL, 'GET');
    }
    return $this->_id ? (int) $this->_id : NULL;
  }

  /**
   *
   * @return array
   * @throws \CRM_Core_Exception
   */
  private function build() {
    $tabs = $this->get('tabHeader');
    if (!$tabs || empty($_GET['reset'])) {
      $tabs = $this->processTab() ?? [];
      $this->set('tabHeader', $tabs);
    }
    $tabs = \CRM_Core_Smarty::setRequiredTabTemplateKeys($tabs);
    $this->assign('tabHeader', $tabs);
    CRM_Core_Resources::singleton()
      ->addScriptFile('civicrm', 'templates/CRM/common/TabHeader.js', 1, 'html-header')
      ->addSetting([
        'tabSettings' => [
          'active' => $this->getCurrentTab($tabs),
        ],
      ]);
    CRM_Event_Form_ManageEvent::addProfileEditScripts();
    return $tabs;
  }

  /**
   * @return array
   * @throws Exception
   */
  private function processTab() {
    if ($this->getEventID() <= 0) {
      return NULL;
    }

    $default = [
      'link' => NULL,
      'valid' => TRUE,
      'active' => TRUE,
      'current' => FALSE,
      'class' => 'ajaxForm',
    ];

    $tabs = [];
    $tabs['settings'] = ['title' => ts('Info and Settings'), 'class' => 'ajaxForm livePage', 'icon' => 'crm-i fa-circle-info'] + $default;
    $tabs['location'] = ['title' => ts('Event Location'), 'icon' => 'crm-i fa-map-marker'] + $default;
    // If CiviContribute is active, create the Fees tab.
    if (CRM_Core_Component::isEnabled('CiviContribute')) {
      $tabs['fee'] = ['title' => ts('Fees'), 'icon' => 'crm-i fa-money'] + $default;
    }
    $tabs['registration'] = ['title' => ts('Online Registration'), 'icon' => 'crm-i fa-check'] + $default;
    // @fixme I don't understand the event permissions check here - can we just get rid of it?
    $permissions = CRM_Event_BAO_Event::getAllPermissions();
    if (CRM_Core_Permission::check('administer CiviCRM data') || !empty($permissions[CRM_Core_Permission::EDIT])) {
      $tabs['reminder'] = ['title' => ts('Schedule Reminders'), 'class' => 'livePage', 'icon' => 'crm-i fa-envelope'] + $default;
    }

    $tabs['pcp'] = ['title' => ts('Personal Campaigns'), 'icon' => 'crm-i fa-user'] + $default;
    $tabs['repeat'] = ['title' => ts('Repeat'), 'icon' => 'crm-i fa-repeat'] + $default;

    // Repeat tab must refresh page when switching repeat mode so js & vars will get set-up
    if (!$this->_isRepeatingEvent) {
      unset($tabs['repeat']['class']);
    }

    $eventID = $this->getEventID();
    if ($eventID) {
      // disable tabs based on their configuration status
      $sql = "
SELECT     e.loc_block_id as is_location, e.is_online_registration, e.is_monetary, taf.is_active, pcp.is_active as is_pcp, sch.id as is_reminder, re.id as is_repeating_event
FROM       civicrm_event e
LEFT JOIN  civicrm_tell_friend taf ON ( taf.entity_table = 'civicrm_event' AND taf.entity_id = e.id )
LEFT JOIN  civicrm_pcp_block pcp   ON ( pcp.entity_table = 'civicrm_event' AND pcp.entity_id = e.id )
LEFT JOIN  civicrm_action_schedule sch ON ( sch.mapping_id = %2 AND sch.entity_value = %1 )
LEFT JOIN  civicrm_recurring_entity re ON ( e.id = re.entity_id AND re.entity_table = 'civicrm_event' )
WHERE      e.id = %1
";
      //Check if repeat is configured
      CRM_Core_BAO_RecurringEntity::getParentFor($eventID, 'civicrm_event');
      $params = [
        1 => [$eventID, 'Integer'],
        2 => [CRM_Event_ActionMapping::EVENT_NAME_MAPPING_ID, 'Integer'],
      ];
      $dao = CRM_Core_DAO::executeQuery($sql, $params);
      if (!$dao->fetch()) {
        throw new CRM_Core_Exception('Unable to determine Event information');
      }
      if (!$dao->is_location) {
        $tabs['location']['valid'] = FALSE;
      }
      if (!$dao->is_online_registration) {
        $tabs['registration']['valid'] = FALSE;
      }
      if (!$dao->is_monetary) {
        $tabs['fee']['valid'] = FALSE;
      }
      if (!$dao->is_pcp) {
        $tabs['pcp']['valid'] = FALSE;
      }
      if (!$dao->is_reminder) {
        $tabs['reminder']['valid'] = FALSE;
      }
      if (!$dao->is_repeating_event) {
        $tabs['repeat']['valid'] = FALSE;
      }
    }

    // see if any other modules want to add any tabs
    // note: status of 'valid' flag of any injected tab, needs to be taken care in the hook implementation.
    CRM_Utils_Hook::tabset('civicrm/event/manage', $tabs,
      ['event_id' => $eventID]);

    $fullName = $this->_name;
    $className = CRM_Utils_String::getClassName($fullName);
    $new = '';

    // hack for special cases.
    switch ($className) {
      case 'Event':
        $attributes = $this->_attributes;
        $class = CRM_Utils_Request::retrieveComponent($attributes);
        break;

      case 'EventInfo':
        $class = 'settings';
        break;

      case 'ScheduleReminders':
        $class = 'reminder';
        break;

      default:
        $class = strtolower($className);
        break;
    }

    if (array_key_exists($class, $tabs)) {
      $tabs[$class]['current'] = TRUE;
      $qfKey = $this->get('qfKey');
      if ($qfKey) {
        $tabs[$class]['qfKey'] = "&qfKey={$qfKey}";
      }
    }

    if ($eventID) {
      $reset = !empty($_GET['reset']) ? 'reset=1&' : '';

      foreach ($tabs as $key => $value) {
        if (!isset($tabs[$key]['qfKey'])) {
          $tabs[$key]['qfKey'] = NULL;
        }

        $action = 'update';
        if ($key === 'reminder') {
          $action = 'browse';
        }

        $link = "civicrm/event/manage/{$key}";
        $query = "{$reset}action={$action}&id={$eventID}&component=event{$tabs[$key]['qfKey']}";

        $tabs[$key]['link'] = $value['link'] ?? CRM_Utils_System::url($link, $query);
      }
    }

    return $tabs;
  }

  /**
   * @param $tabs
   *
   * @return int|string
   */
  private function getCurrentTab($tabs) {
    static $current = FALSE;

    if ($current) {
      return $current;
    }

    if (is_array($tabs)) {
      foreach ($tabs as $subPage => $pageVal) {
        if (($pageVal['current'] ?? NULL) === TRUE) {
          $current = $subPage;
          break;
        }
      }
    }

    $current = $current ?: 'settings';
    return $current;
  }

}
