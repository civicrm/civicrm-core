<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
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
 * @copyright CiviCRM LLC (c) 2004-2016
 */

/**
 * This class generates form components for Activity.
 */
class CRM_Activity_Form_Activity extends CRM_Contact_Form_Task {

  /**
   * The id of the object being edited / created
   *
   * @var int
   */
  public $_activityId;

  /**
   * Store activity ids when multiple activities are created.
   *
   * @var int
   */
  public $_activityIds = array();

  /**
   * The id of activity type.
   *
   * @var int
   */
  public $_activityTypeId;

  /**
   * The name of activity type.
   *
   * @var string
   */
  public $_activityTypeName;

  /**
   * The id of currently viewed contact.
   *
   * @var int
   */
  public $_currentlyViewedContactId;

  /**
   * The id of source contact and target contact.
   *
   * @var int
   */
  protected $_sourceContactId;
  protected $_targetContactId;
  protected $_asigneeContactId;

  protected $_single;

  public $_context;
  public $_compContext;
  public $_action;
  public $_activityTypeFile;

  /**
   * The id of the logged in user, used when add / edit
   *
   * @var int
   */
  public $_currentUserId;

  /**
   * The array of form field attributes.
   *
   * @var array
   */
  public $_fields;

  /**
   * The the directory inside CRM, to include activity type file from
   *
   * @var string
   */
  protected $_crmDir = 'Activity';

  /**
   * Survey activity.
   *
   * @var boolean
   */
  protected $_isSurveyActivity;

  protected $_values = array();

  protected $unsavedWarn = TRUE;

  /**
   * Explicitly declare the entity api name.
   *
   * @return string
   */
  public function getDefaultEntity() {
    return 'Activity';
  }

  /**
   * The _fields var can be used by sub class to set/unset/edit the
   * form fields based on their requirement
   */
  public function setFields() {
    // Remove print document activity type
    $unwanted = CRM_Core_OptionGroup::values('activity_type', FALSE, FALSE, FALSE, "AND v.name = 'Print PDF Letter'");
    $activityTypes = array_diff_key(CRM_Core_PseudoConstant::ActivityType(FALSE), $unwanted);

    $this->_fields = array(
      'subject' => array(
        'type' => 'text',
        'label' => ts('Subject'),
        'attributes' => CRM_Core_DAO::getAttribute('CRM_Activity_DAO_Activity',
          'subject'
        ),
      ),
      'duration' => array(
        'type' => 'text',
        'label' => ts('Duration'),
        'attributes' => array('size' => 4, 'maxlength' => 8),
        'required' => FALSE,
      ),
      'location' => array(
        'type' => 'text',
        'label' => ts('Location'),
        'attributes' => CRM_Core_DAO::getAttribute('CRM_Activity_DAO_Activity', 'location'),
        'required' => FALSE,
      ),
      'details' => array(
        'type' => 'wysiwyg',
        'label' => ts('Details'),
        'attributes' => array('class' => 'huge'),
        'required' => FALSE,
      ),
      'status_id' => array(
        'type' => 'select',
        'required' => TRUE,
      ),
      'priority_id' => array(
        'type' => 'select',
        'required' => TRUE,
      ),
      'source_contact_id' => array(
        'type' => 'entityRef',
        'label' => ts('Added By'),
        'required' => FALSE,
      ),
      'target_contact_id' => array(
        'type' => 'entityRef',
        'label' => ts('With Contact'),
        'attributes' => array('multiple' => TRUE, 'create' => TRUE),
      ),
      'assignee_contact_id' => array(
        'type' => 'entityRef',
        'label' => ts('Assigned to'),
        'attributes' => array(
          'multiple' => TRUE,
          'create' => TRUE,
          'api' => array('params' => array('is_deceased' => 0)),
        ),
      ),
      'followup_assignee_contact_id' => array(
        'type' => 'entityRef',
        'label' => ts('Assigned to'),
        'attributes' => array(
          'multiple' => TRUE,
          'create' => TRUE,
          'api' => array('params' => array('is_deceased' => 0)),
        ),
      ),
      'followup_activity_type_id' => array(
        'type' => 'select',
        'label' => ts('Followup Activity'),
        'attributes' => array('' => '- ' . ts('select activity') . ' -') + $activityTypes,
        'extra' => array('class' => 'crm-select2'),
      ),
      // Add optional 'Subject' field for the Follow-up Activiity, CRM-4491
      'followup_activity_subject' => array(
        'type' => 'text',
        'label' => ts('Subject'),
        'attributes' => CRM_Core_DAO::getAttribute('CRM_Activity_DAO_Activity',
          'subject'
        ),
      ),
    );
  }

  /**
   * Build the form object.
   */
  public function preProcess() {
    CRM_Core_Form_RecurringEntity::preProcess('civicrm_activity');
    $this->_atypefile = CRM_Utils_Array::value('atypefile', $_GET);
    $this->assign('atypefile', FALSE);
    if ($this->_atypefile) {
      $this->assign('atypefile', TRUE);
    }

    $session = CRM_Core_Session::singleton();
    $this->_currentUserId = $session->get('userID');

    $this->_currentlyViewedContactId = $this->get('contactId');
    if (!$this->_currentlyViewedContactId) {
      $this->_currentlyViewedContactId = CRM_Utils_Request::retrieve('cid', 'Positive', $this);
    }
    $this->assign('contactId', $this->_currentlyViewedContactId);

    // Give the context.
    if (!isset($this->_context)) {
      $this->_context = CRM_Utils_Request::retrieve('context', 'String', $this);
      if (CRM_Contact_Form_Search::isSearchContext($this->_context)) {
        $this->_context = 'search';
      }
      elseif (!in_array($this->_context, array('dashlet', 'dashletFullscreen'))
        && $this->_currentlyViewedContactId
      ) {
        $this->_context = 'activity';
      }
      $this->_compContext = CRM_Utils_Request::retrieve('compContext', 'String', $this);
    }

    $this->assign('context', $this->_context);

    $this->_action = CRM_Utils_Request::retrieve('action', 'String', $this);

    if ($this->_action & CRM_Core_Action::DELETE) {
      if (!CRM_Core_Permission::check('delete activities')) {
        CRM_Core_Error::fatal(ts('You do not have permission to access this page.'));
      }
    }

    // CRM-6957
    // When we come from contact search, activity id never comes.
    // So don't try to get from object, it might gives you wrong one.

    // if we're not adding new one, there must be an id to
    // an activity we're trying to work on.
    if ($this->_action != CRM_Core_Action::ADD &&
      get_class($this->controller) != 'CRM_Contact_Controller_Search'
    ) {
      $this->_activityId = CRM_Utils_Request::retrieve('id', 'Positive', $this);
    }

    $this->_activityTypeId = CRM_Utils_Request::retrieve('atype', 'Positive', $this);
    $this->assign('atype', $this->_activityTypeId);

    $this->assign('activityId', $this->_activityId);

    // Check for required permissions, CRM-6264.
    if ($this->_activityId &&
      in_array($this->_action, array(
        CRM_Core_Action::UPDATE,
        CRM_Core_Action::VIEW,
      )) &&
      !CRM_Activity_BAO_Activity::checkPermission($this->_activityId, $this->_action)
    ) {
      CRM_Core_Error::fatal(ts('You do not have permission to access this page.'));
    }
    if (($this->_action & CRM_Core_Action::VIEW) &&
      CRM_Activity_BAO_Activity::checkPermission($this->_activityId, CRM_Core_Action::UPDATE)
    ) {
      $this->assign('permission', 'edit');
    }

    if (!$this->_activityTypeId && $this->_activityId) {
      $this->_activityTypeId = CRM_Core_DAO::getFieldValue('CRM_Activity_DAO_Activity',
        $this->_activityId,
        'activity_type_id'
      );
    }

    // Assigning Activity type name.
    if ($this->_activityTypeId) {
      $activityTName = CRM_Core_OptionGroup::values('activity_type', FALSE, FALSE, FALSE, 'AND v.value = ' . $this->_activityTypeId, 'label');
      if ($activityTName[$this->_activityTypeId]) {
        $this->_activityTypeName = $activityTName[$this->_activityTypeId];
        $this->assign('activityTName', $activityTName[$this->_activityTypeId]);
      }
    }

    // Set title.
    if (isset($activityTName)) {
      $activityName = CRM_Utils_Array::value($this->_activityTypeId, $activityTName);
      $this->assign('pageTitle', ts('%1 Activity', array(1 => $activityName)));

      if ($this->_currentlyViewedContactId) {
        $displayName = CRM_Contact_BAO_Contact::displayName($this->_currentlyViewedContactId);
        // Check if this is default domain contact CRM-10482.
        if (CRM_Contact_BAO_Contact::checkDomainContact($this->_currentlyViewedContactId)) {
          $displayName .= ' (' . ts('default organization') . ')';
        }
        CRM_Utils_System::setTitle($displayName . ' - ' . $activityName);
      }
      else {
        CRM_Utils_System::setTitle(ts('%1 Activity', array(1 => $activityName)));
      }
    }

    // Check the mode when this form is called either single or as
    // search task action.
    if ($this->_activityTypeId ||
      $this->_context == 'standalone' ||
      $this->_currentlyViewedContactId
    ) {
      $this->_single = TRUE;
      $this->assign('urlPath', 'civicrm/activity');
    }
    else {
      // Set the appropriate action.
      $url = CRM_Utils_System::currentPath();
      $urlArray = explode('/', $url);
      $searchPath = array_pop($urlArray);
      $searchType = 'basic';
      $this->_action = CRM_Core_Action::BASIC;
      switch ($searchPath) {
        case 'basic':
          $searchType = $searchPath;
          $this->_action = CRM_Core_Action::BASIC;
          break;

        case 'advanced':
          $searchType = $searchPath;
          $this->_action = CRM_Core_Action::ADVANCED;
          break;

        case 'builder':
          $searchType = $searchPath;
          $this->_action = CRM_Core_Action::PROFILE;
          break;

        case 'custom':
          $this->_action = CRM_Core_Action::COPY;
          $searchType = $searchPath;
          break;
      }

      parent::preProcess();
      $this->_single = FALSE;

      $this->assign('urlPath', "civicrm/contact/search/$searchType");
      $this->assign('urlPathVar', "_qf_Activity_display=true&qfKey={$this->controller->_key}");
    }

    $this->assign('single', $this->_single);
    $this->assign('action', $this->_action);

    if ($this->_action & CRM_Core_Action::VIEW) {
      // Get the tree of custom fields.
      $this->_groupTree = CRM_Core_BAO_CustomGroup::getTree('Activity', $this,
        $this->_activityId, 0, $this->_activityTypeId
      );
    }

    if ($this->_activityTypeId) {
      // Set activity type name and description to template.
      list($this->_activityTypeName, $activityTypeDescription) = CRM_Core_BAO_OptionValue::getActivityTypeDetails($this->_activityTypeId);
      $this->assign('activityTypeName', $this->_activityTypeName);
      $this->assign('activityTypeDescription', $activityTypeDescription);
    }

    // set user context
    $urlParams = $urlString = NULL;
    $qfKey = CRM_Utils_Request::retrieve('key', 'String', $this);
    if (!$qfKey) {
      $qfKey = CRM_Utils_Request::retrieve('qfKey', 'String', $this);
    }

    // Validate the qfKey.
    if (!CRM_Utils_Rule::qfKey($qfKey)) {
      $qfKey = NULL;
    }

    if ($this->_context == 'fulltext') {
      $keyName = '&qfKey';
      $urlParams = 'force=1';
      $urlString = 'civicrm/contact/search/custom';
      if ($this->_action == CRM_Core_Action::UPDATE) {
        $keyName = '&key';
        $urlParams .= '&context=fulltext&action=view';
        $urlString = 'civicrm/contact/view/activity';
      }
      if ($qfKey) {
        $urlParams .= "$keyName=$qfKey";
      }
      $this->assign('searchKey', $qfKey);
    }
    elseif (in_array($this->_context, array(
      'standalone',
      'home',
      'dashlet',
      'dashletFullscreen',
    ))
    ) {
      $urlParams = 'reset=1';
      $urlString = 'civicrm/dashboard';
    }
    elseif ($this->_context == 'search') {
      $urlParams = 'force=1';
      if ($qfKey) {
        $urlParams .= "&qfKey=$qfKey";
      }
      $path = CRM_Utils_System::currentPath();
      if ($this->_compContext == 'advanced') {
        $urlString = 'civicrm/contact/search/advanced';
      }
      elseif ($path == 'civicrm/group/search'
        || $path == 'civicrm/contact/search'
        || $path == 'civicrm/contact/search/advanced'
        || $path == 'civicrm/contact/search/custom'
        || $path == 'civicrm/group/search'
      ) {
        $urlString = $path;
      }
      else {
        $urlString = 'civicrm/activity/search';
      }
      $this->assign('searchKey', $qfKey);
    }
    elseif ($this->_context != 'caseActivity') {
      $urlParams = "action=browse&reset=1&cid={$this->_currentlyViewedContactId}&selectedChild=activity";
      $urlString = 'civicrm/contact/view';
    }

    if ($urlString) {
      $session->pushUserContext(CRM_Utils_System::url($urlString, $urlParams));
    }

    // hack to retrieve activity type id from post variables
    if (!$this->_activityTypeId) {
      $this->_activityTypeId = CRM_Utils_Array::value('activity_type_id', $_POST);
    }

    // when custom data is included in this page
    if (!empty($_POST['hidden_custom'])) {
      // We need to set it in the session for the code below to work.
      // CRM-3014
      // Need to assign custom data subtype to the template.
      $this->set('type', 'Activity');
      $this->set('subType', $this->_activityTypeId);
      $this->set('entityId', $this->_activityId);
      CRM_Custom_Form_CustomData::preProcess($this, NULL, $this->_activityTypeId, 1, 'Activity', $this->_activityId);
      CRM_Custom_Form_CustomData::buildQuickForm($this);
      CRM_Custom_Form_CustomData::setDefaultValues($this);
    }

    // add attachments part
    CRM_Core_BAO_File::buildAttachment($this, 'civicrm_activity', $this->_activityId, NULL, TRUE);

    // figure out the file name for activity type, if any
    if ($this->_activityTypeId &&
      $this->_activityTypeFile = CRM_Activity_BAO_Activity::getFileForActivityTypeId($this->_activityTypeId, $this->_crmDir)
    ) {
      $this->assign('activityTypeFile', $this->_activityTypeFile);
      $this->assign('crmDir', $this->_crmDir);
    }

    $this->setFields();

    if ($this->_activityTypeFile) {
      $className = "CRM_{$this->_crmDir}_Form_Activity_{$this->_activityTypeFile}";
      $className::preProcess($this);
    }

    $this->_values = $this->get('values');
    if (!is_array($this->_values)) {
      $this->_values = array();
      if (isset($this->_activityId) && $this->_activityId) {
        $params = array('id' => $this->_activityId);
        CRM_Activity_BAO_Activity::retrieve($params, $this->_values);
      }
      $this->set('values', $this->_values);
    }

    if ($this->_action & CRM_Core_Action::UPDATE) {
      CRM_Core_Form_RecurringEntity::preProcess('civicrm_activity');
    }
  }

  /**
   * Set default values for the form.
   *
   * For edit/view mode the default values are retrieved from the database.
   *
   * @return array
   */
  public function setDefaultValues() {

    $defaults = $this->_values + CRM_Core_Form_RecurringEntity::setDefaultValues();
    // if we're editing...
    if (isset($this->_activityId)) {
      if (empty($defaults['activity_date_time'])) {
        list($defaults['activity_date_time'], $defaults['activity_date_time_time']) = CRM_Utils_Date::setDateDefaults(NULL, 'activityDateTime');
      }
      elseif ($this->_action & CRM_Core_Action::UPDATE) {
        $this->assign('current_activity_date_time', $defaults['activity_date_time']);
        list($defaults['activity_date_time'],
          $defaults['activity_date_time_time']
          ) = CRM_Utils_Date::setDateDefaults($defaults['activity_date_time'], 'activityDateTime');
        list($defaults['repetition_start_date'], $defaults['repetition_start_date_time']) = CRM_Utils_Date::setDateDefaults($defaults['activity_date_time'], 'activityDateTime');
      }

      if ($this->_context != 'standalone') {
        $this->assign('target_contact_value',
          CRM_Utils_Array::value('target_contact_value', $defaults)
        );
        $this->assign('assignee_contact_value',
          CRM_Utils_Array::value('assignee_contact_value', $defaults)
        );
      }

      // Fixme: why are we getting the wrong keys from upstream?
      $defaults['target_contact_id'] = CRM_Utils_Array::value('target_contact', $defaults);
      $defaults['assignee_contact_id'] = CRM_Utils_Array::value('assignee_contact', $defaults);

      // set default tags if exists
      $defaults['tag'] = CRM_Core_BAO_EntityTag::getTag($this->_activityId, 'civicrm_activity');
    }
    else {
      // if it's a new activity, we need to set default values for associated contact fields
      $this->_sourceContactId = $this->_currentUserId;
      $this->_targetContactId = $this->_currentlyViewedContactId;

      $defaults['source_contact_id'] = $this->_sourceContactId;
      $defaults['target_contact_id'] = $this->_targetContactId;

      list($defaults['activity_date_time'], $defaults['activity_date_time_time'])
        = CRM_Utils_Date::setDateDefaults(NULL, 'activityDateTime');
    }

    if ($this->_activityTypeId) {
      $defaults['activity_type_id'] = $this->_activityTypeId;
    }

    if (!$this->_single && !empty($this->_contactIds)) {
      $defaults['target_contact_id'] = $this->_contactIds;
    }

    // CRM-15472 - 50 is around the practical limit of how many items a select2 entityRef can handle
    if (!empty($defaults['target_contact_id'])) {
      $count = count(is_array($defaults['target_contact_id']) ? $defaults['target_contact_id'] : explode(',', $defaults['target_contact_id']));
      if ($count > 50) {
        $this->freeze(array('target_contact_id'));
      }
    }

    if ($this->_action & (CRM_Core_Action::DELETE | CRM_Core_Action::RENEW)) {
      $this->assign('delName', CRM_Utils_Array::value('subject', $defaults));
    }

    if ($this->_activityTypeFile) {
      $className = "CRM_{$this->_crmDir}_Form_Activity_{$this->_activityTypeFile}";
      $defaults += $className::setDefaultValues($this);
    }
    if (empty($defaults['priority_id'])) {
      $priority = CRM_Core_PseudoConstant::get('CRM_Activity_DAO_Activity', 'priority_id');
      $defaults['priority_id'] = array_search('Normal', $priority);
    }
    if (empty($defaults['status_id'])) {
      $defaults['status_id'] = CRM_Core_OptionGroup::getDefaultValue('activity_status');
    }
    return $defaults;
  }

  public function buildQuickForm() {
    if ($this->_action & (CRM_Core_Action::DELETE | CRM_Core_Action::RENEW)) {
      //enable form element (ActivityLinks sets this true)
      $this->assign('suppressForm', FALSE);

      $button = ts('Delete');
      if ($this->_action & CRM_Core_Action::RENEW) {
        $button = ts('Restore');
      }
      $this->addButtons(array(
        array(
          'type' => 'next',
          'name' => $button,
          'spacing' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
          'isDefault' => TRUE,
        ),
        array(
          'type' => 'cancel',
          'name' => ts('Cancel'),
        ),
      ));
      return;
    }

    // Build other activity links.
    CRM_Activity_Form_ActivityLinks::commonBuildQuickForm($this);

    // Enable form element (ActivityLinks sets this true).
    $this->assign('suppressForm', FALSE);

    $element = &$this->add('select', 'activity_type_id', ts('Activity Type'),
      array('' => '- ' . ts('select') . ' -') + $this->_fields['followup_activity_type_id']['attributes'],
      FALSE, array(
        'onchange' => "CRM.buildCustomData( 'Activity', this.value );",
        'class' => 'crm-select2 required',
      )
    );

    // Freeze for update mode.
    if ($this->_action & CRM_Core_Action::UPDATE) {
      $element->freeze();
    }

    // Call to RecurringEntity buildQuickForm for add/update mode.
    if ($this->_action & (CRM_Core_Action::UPDATE | CRM_Core_Action::ADD)) {
      CRM_Core_Form_RecurringEntity::buildQuickForm($this);
    }

    foreach ($this->_fields as $field => $values) {
      if (!empty($this->_fields[$field])) {
        $attribute = CRM_Utils_Array::value('attributes', $values);
        $required = !empty($values['required']);

        if ($values['type'] == 'select' && empty($attribute)) {
          $this->addSelect($field, array('entity' => 'activity'), $required);
        }
        elseif ($values['type'] == 'entityRef') {
          $this->addEntityRef($field, $values['label'], $attribute, $required);
        }
        else {
          $this->add($values['type'], $field, $values['label'], $attribute, $required, CRM_Utils_Array::value('extra', $values));
        }
      }
    }

    // CRM-7362 --add campaigns.
    CRM_Campaign_BAO_Campaign::addCampaign($this, CRM_Utils_Array::value('campaign_id', $this->_values));

    // Add engagement level CRM-7775
    $buildEngagementLevel = FALSE;
    if (CRM_Campaign_BAO_Campaign::isCampaignEnable() &&
      CRM_Campaign_BAO_Campaign::accessCampaign()
    ) {
      $buildEngagementLevel = TRUE;
      $this->addSelect('engagement_level', array('entity' => 'activity'));
      $this->addRule('engagement_level',
        ts('Please enter the engagement index as a number (integers only).'),
        'positiveInteger'
      );
    }
    $this->assign('buildEngagementLevel', $buildEngagementLevel);

    // check for survey activity
    $this->_isSurveyActivity = FALSE;

    if ($this->_activityId && CRM_Campaign_BAO_Campaign::isCampaignEnable() &&
      CRM_Campaign_BAO_Campaign::accessCampaign()
    ) {

      $this->_isSurveyActivity = CRM_Campaign_BAO_Survey::isSurveyActivity($this->_activityId);
      if ($this->_isSurveyActivity) {
        $surveyId = CRM_Core_DAO::getFieldValue('CRM_Activity_DAO_Activity',
          $this->_activityId,
          'source_record_id'
        );
        $responseOptions = CRM_Campaign_BAO_Survey::getResponsesOptions($surveyId);
        if ($responseOptions) {
          $this->add('select', 'result', ts('Result'),
            array('' => ts('- select -')) + array_combine($responseOptions, $responseOptions)
          );
        }
        $surveyTitle = NULL;
        if ($surveyId) {
          $surveyTitle = CRM_Core_DAO::getFieldValue('CRM_Campaign_DAO_Survey', $surveyId, 'title');
        }
        $this->assign('surveyTitle', $surveyTitle);
      }
    }
    $this->assign('surveyActivity', $this->_isSurveyActivity);

    // this option should be available only during add mode
    if ($this->_action != CRM_Core_Action::UPDATE) {
      $this->add('advcheckbox', 'is_multi_activity', ts('Create a separate activity for each contact.'));
    }

    $this->addRule('duration',
      ts('Please enter the duration as number of minutes (integers only).'), 'positiveInteger'
    );
    $this->addDateTime('activity_date_time', ts('Date'), TRUE, array('formatType' => 'activityDateTime'));

    // Add followup date.
    $this->addDateTime('followup_date', ts('in'), FALSE, array('formatType' => 'activityDateTime'));

    // Only admins and case-workers can change the activity source
    if (!CRM_Core_Permission::check('administer CiviCRM') && $this->_context != 'caseActivity') {
      $this->getElement('source_contact_id')->freeze();
    }

    //need to assign custom data type and subtype to the template
    $this->assign('customDataType', 'Activity');
    $this->assign('customDataSubType', $this->_activityTypeId);
    $this->assign('entityID', $this->_activityId);

    CRM_Core_BAO_Tag::getTags('civicrm_activity', $tags, NULL,
      '&nbsp;&nbsp;', TRUE);

    if (!empty($tags)) {
      $this->add('select', 'tag', ts('Tags'), $tags, FALSE,
        array('id' => 'tags', 'multiple' => 'multiple', 'class' => 'crm-select2 huge')
      );
    }

    // we need to hide activity tagset for special activities
    $specialActivities = array('Open Case');

    if (!in_array($this->_activityTypeName, $specialActivities)) {
      // build tag widget
      $parentNames = CRM_Core_BAO_Tag::getTagSet('civicrm_activity');
      CRM_Core_Form_Tag::buildQuickForm($this, $parentNames, 'civicrm_activity', $this->_activityId);
    }

    // if we're viewing, we're assigning different buttons than for adding/editing
    if ($this->_action & CRM_Core_Action::VIEW) {
      if (isset($this->_groupTree)) {
        CRM_Core_BAO_CustomGroup::buildCustomDataView($this, $this->_groupTree, FALSE, NULL, NULL, NULL, $this->_activityId);
      }
      // form should be frozen for view mode
      $this->freeze();

      $buttons = array();
      $buttons[] = array(
        'type' => 'cancel',
        'name' => ts('Done'),
      );
      $this->addButtons($buttons);
    }
    else {
      $message = array(
        'completed' => ts('Are you sure? This is a COMPLETED activity with the DATE in the FUTURE. Click Cancel to change the date / status. Otherwise, click OK to save.'),
        'scheduled' => ts('Are you sure? This is a SCHEDULED activity with the DATE in the PAST. Click Cancel to change the date / status. Otherwise, click OK to save.'),
      );
      $js = array('onclick' => "return activityStatus(" . json_encode($message) . ");");
      $this->addButtons(array(
        array(
          'type' => 'upload',
          'name' => ts('Save'),
          'js' => $js,
          'isDefault' => TRUE,
        ),
        array(
          'type' => 'cancel',
          'name' => ts('Cancel'),
        ),
      ));
    }

    if ($this->_activityTypeFile) {
      $className = "CRM_{$this->_crmDir}_Form_Activity_{$this->_activityTypeFile}";

      $className::buildQuickForm($this);
      $this->addFormRule(array($className, 'formRule'), $this);
    }

    $this->addFormRule(array('CRM_Activity_Form_Activity', 'formRule'), $this);

    if (Civi::settings()->get('activity_assignee_notification')) {
      $this->assign('activityAssigneeNotification', TRUE);
    }
    else {
      $this->assign('activityAssigneeNotification', FALSE);
    }
  }

  /**
   * Global form rule.
   *
   * @param array $fields
   *   The input form values.
   * @param array $files
   *   The uploaded files if any.
   * @param $self
   *
   * @return bool|array
   *   true if no errors, else array of errors
   */
  public static function formRule($fields, $files, $self) {
    // skip form rule if deleting
    if (CRM_Utils_Array::value('_qf_Activity_next_', $fields) == 'Delete') {
      return TRUE;
    }
    $errors = array();
    if ((array_key_exists('activity_type_id', $fields) || !$self->_single) && empty($fields['activity_type_id'])) {
      $errors['activity_type_id'] = ts('Activity Type is a required field');
    }

    if (CRM_Utils_Array::value('activity_type_id', $fields) == 3 &&
      CRM_Utils_Array::value('status_id', $fields) == 1
    ) {
      $errors['status_id'] = ts('You cannot record scheduled email activity.');
    }
    elseif (CRM_Utils_Array::value('activity_type_id', $fields) == 4 &&
      CRM_Utils_Array::value('status_id', $fields) == 1
    ) {
      $errors['status_id'] = ts('You cannot record scheduled SMS activity.');
    }

    if (!empty($fields['followup_activity_type_id']) && empty($fields['followup_date'])) {
      $errors['followup_date_time'] = ts('Followup date is a required field.');
    }
    // Activity type is mandatory if subject or follow-up date is specified for an Follow-up activity, CRM-4515.
    if ((!empty($fields['followup_activity_subject']) || !empty($fields['followup_date'])) && empty($fields['followup_activity_type_id'])) {
      $errors['followup_activity_subject'] = ts('Follow-up Activity type is a required field.');
    }
    return $errors;
  }

  /**
   * Process the form submission.
   *
   *
   * @param array $params
   * @return array|null
   */
  public function postProcess($params = NULL) {
    if ($this->_action & CRM_Core_Action::DELETE) {
      $deleteParams = array('id' => $this->_activityId);
      $moveToTrash = CRM_Case_BAO_Case::isCaseActivity($this->_activityId);
      CRM_Activity_BAO_Activity::deleteActivity($deleteParams, $moveToTrash);

      // delete tags for the entity
      $tagParams = array(
        'entity_table' => 'civicrm_activity',
        'entity_id' => $this->_activityId,
      );

      CRM_Core_BAO_EntityTag::del($tagParams);

      CRM_Core_Session::setStatus(ts("Selected Activity has been deleted successfully."), ts('Record Deleted'), 'success');
      return NULL;
    }

    // store the submitted values in an array
    if (!$params) {
      $params = $this->controller->exportValues($this->_name);
    }

    // Set activity type id.
    if (empty($params['activity_type_id'])) {
      $params['activity_type_id'] = $this->_activityTypeId;
    }

    if (!empty($params['hidden_custom']) &&
      !isset($params['custom'])
    ) {
      $customFields = CRM_Core_BAO_CustomField::getFields('Activity', FALSE, FALSE,
        $this->_activityTypeId
      );
      $customFields = CRM_Utils_Array::crmArrayMerge($customFields,
        CRM_Core_BAO_CustomField::getFields('Activity', FALSE, FALSE,
          NULL, NULL, TRUE
        )
      );
      $params['custom'] = CRM_Core_BAO_CustomField::postProcess($params,
        $this->_activityId,
        'Activity'
      );
    }

    // store the date with proper format
    $params['activity_date_time'] = CRM_Utils_Date::processDate($params['activity_date_time'], $params['activity_date_time_time']);

    // format params as arrays
    foreach (array('target', 'assignee', 'followup_assignee') as $name) {
      if (!empty($params["{$name}_contact_id"])) {
        $params["{$name}_contact_id"] = explode(',', $params["{$name}_contact_id"]);
      }
      else {
        $params["{$name}_contact_id"] = array();
      }
    }

    // get ids for associated contacts
    if (!$params['source_contact_id']) {
      $params['source_contact_id'] = $this->_currentUserId;
    }

    if (isset($this->_activityId)) {
      $params['id'] = $this->_activityId;
    }

    // add attachments as needed
    CRM_Core_BAO_File::formatAttachment($params,
      $params,
      'civicrm_activity',
      $this->_activityId
    );

    $activity = array();
    if (!empty($params['is_multi_activity']) &&
      !CRM_Utils_Array::crmIsEmptyArray($params['target_contact_id'])
    ) {
      $targetContacts = $params['target_contact_id'];
      foreach ($targetContacts as $targetContactId) {
        $params['target_contact_id'] = array($targetContactId);
        // save activity
        $activity[] = $this->processActivity($params);
      }
    }
    else {
      // save activity
      $activity = $this->processActivity($params);
    }

    $activityIds = empty($this->_activityIds) ? array($this->_activityId) : $this->_activityIds;
    foreach ($activityIds as $activityId) {
      // set params for repeat configuration in create mode
      $params['entity_id'] = $activityId;
      $params['entity_table'] = 'civicrm_activity';
      if (!empty($params['entity_id']) && !empty($params['entity_table'])) {
        $checkParentExistsForThisId = CRM_Core_BAO_RecurringEntity::getParentFor($params['entity_id'], $params['entity_table']);
        if ($checkParentExistsForThisId) {
          $params['parent_entity_id'] = $checkParentExistsForThisId;
          $scheduleReminderDetails = CRM_Core_BAO_RecurringEntity::getReminderDetailsByEntityId($checkParentExistsForThisId, $params['entity_table']);
        }
        else {
          $params['parent_entity_id'] = $params['entity_id'];
          $scheduleReminderDetails = CRM_Core_BAO_RecurringEntity::getReminderDetailsByEntityId($params['entity_id'], $params['entity_table']);
        }
        if (property_exists($scheduleReminderDetails, 'id')) {
          $params['schedule_reminder_id'] = $scheduleReminderDetails->id;
        }
      }
      $params['dateColumns'] = array('activity_date_time');

      // Set default repetition start if it was not provided.
      if (empty($params['repetition_start_date'])) {
        $params['repetition_start_date'] = $params['activity_date_time'];
      }

      // unset activity id
      unset($params['id']);
      $linkedEntities = array(
        array(
          'table' => 'civicrm_activity_contact',
          'findCriteria' => array(
            'activity_id' => $activityId,
          ),
          'linkedColumns' => array('activity_id'),
          'isRecurringEntityRecord' => FALSE,
        ),
      );
      CRM_Core_Form_RecurringEntity::postProcess($params, 'civicrm_activity', $linkedEntities);
    }

    return array('activity' => $activity);
  }

  /**
   * Process activity creation.
   *
   * @param array $params
   *   Associated array of submitted values.
   *
   * @return self|null|object
   */
  protected function processActivity(&$params) {
    $activityAssigned = array();
    $activityContacts = CRM_Core_OptionGroup::values('activity_contacts', FALSE, FALSE, FALSE, NULL, 'name');
    $assigneeID = CRM_Utils_Array::key('Activity Assignees', $activityContacts);
    // format assignee params
    if (!CRM_Utils_Array::crmIsEmptyArray($params['assignee_contact_id'])) {
      //skip those assignee contacts which are already assigned
      //while sending a copy.CRM-4509.
      $activityAssigned = array_flip($params['assignee_contact_id']);
      if ($this->_activityId) {
        $assigneeContacts = CRM_Activity_BAO_ActivityContact::getNames($this->_activityId, $assigneeID);
        $activityAssigned = array_diff_key($activityAssigned, $assigneeContacts);
      }
    }

    // call begin post process. Idea is to let injecting file do
    // any processing before the activity is added/updated.
    $this->beginPostProcess($params);

    $activity = CRM_Activity_BAO_Activity::create($params);

    // add tags if exists
    $tagParams = array();
    if (!empty($params['tag'])) {
      foreach ($params['tag'] as $tag) {
        $tagParams[$tag] = 1;
      }
    }

    // Save static tags.
    CRM_Core_BAO_EntityTag::create($tagParams, 'civicrm_activity', $activity->id);

    // Save free tags.
    if (isset($params['activity_taglist']) && !empty($params['activity_taglist'])) {
      CRM_Core_Form_Tag::postProcess($params['activity_taglist'], $activity->id, 'civicrm_activity', $this);
    }

    // call end post process. Idea is to let injecting file do any
    // processing needed, after the activity has been added/updated.
    $this->endPostProcess($params, $activity);

    // CRM-9590
    if (!empty($params['is_multi_activity'])) {
      $this->_activityIds[] = $activity->id;
    }
    else {
      $this->_activityId = $activity->id;
    }

    // create follow up activity if needed
    $followupStatus = '';
    $followupActivity = NULL;
    if (!empty($params['followup_activity_type_id'])) {
      $followupActivity = CRM_Activity_BAO_Activity::createFollowupActivity($activity->id, $params);
      $followupStatus = ts('A followup activity has been scheduled.');
    }

    // send copy to assignee contacts.CRM-4509
    $mailStatus = '';

    if (Civi::settings()->get('activity_assignee_notification')) {
      $activityIDs = array($activity->id);
      if ($followupActivity) {
        $activityIDs = array_merge($activityIDs, array($followupActivity->id));
      }
      $assigneeContacts = CRM_Activity_BAO_ActivityAssignment::getAssigneeNames($activityIDs, TRUE, FALSE);

      if (!CRM_Utils_Array::crmIsEmptyArray($params['assignee_contact_id'])) {
        $mailToContacts = array();

        // Build an associative array with unique email addresses.
        foreach ($activityAssigned as $id => $dnc) {
          if (isset($id) && array_key_exists($id, $assigneeContacts)) {
            $mailToContacts[$assigneeContacts[$id]['email']] = $assigneeContacts[$id];
          }
        }

        $sent = CRM_Activity_BAO_Activity::sendToAssignee($activity, $mailToContacts);
        if ($sent) {
          $mailStatus .= ts("A copy of the activity has also been sent to assignee contacts(s).");
        }
      }

      // Also send email to follow-up activity assignees if set
      if ($followupActivity) {
        $mailToFollowupContacts = array();
        foreach ($assigneeContacts as $values) {
          if ($values['activity_id'] == $followupActivity->id) {
            $mailToFollowupContacts[$values['email']] = $values;
          }
        }

        $sentFollowup = CRM_Activity_BAO_Activity::sendToAssignee($followupActivity, $mailToFollowupContacts);
        if ($sentFollowup) {
          $mailStatus .= '<br />' . ts("A copy of the follow-up activity has also been sent to follow-up assignee contacts(s).");
        }
      }
    }

    // set status message
    $subject = '';
    if (!empty($params['subject'])) {
      $subject = "'" . $params['subject'] . "'";
    }

    CRM_Core_Session::setStatus(ts('Activity %1 has been saved. %2 %3',
      array(
        1 => $subject,
        2 => $followupStatus,
        3 => $mailStatus,
      )
    ), ts('Saved'), 'success');

    return $activity;
  }

  /**
   * Shorthand for getting id by display name (makes code more readable)
   * @param $displayName
   * @return null|string
   */
  protected function _getIdByDisplayName($displayName) {
    return CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact',
      $displayName,
      'id',
      'sort_name'
    );
  }

  /**
   * Shorthand for getting display name by id (makes code more readable)
   * @param $id
   * @return null|string
   */
  protected function _getDisplayNameById($id) {
    return CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact',
      $id,
      'sort_name',
      'id'
    );
  }

  /**
   * Let injecting activity type file do any processing.
   * needed, before the activity is added/updated
   *
   * @param array $params
   */
  public function beginPostProcess(&$params) {
    if ($this->_activityTypeFile) {
      $className = "CRM_{$this->_crmDir}_Form_Activity_{$this->_activityTypeFile}";
      $className::beginPostProcess($this, $params);
    }
  }

  /**
   * Let injecting activity type file do any processing
   * needed, after the activity has been added/updated
   *
   * @param array $params
   * @param $activity
   */
  public function endPostProcess(&$params, &$activity) {
    if ($this->_activityTypeFile) {
      $className = "CRM_{$this->_crmDir}_Form_Activity_{$this->_activityTypeFile}";
      $className::endPostProcess($this, $params, $activity);
    }
  }

}
