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
 * This class generates form components for Activity.
 */
class CRM_Activity_Form_Activity extends CRM_Contact_Form_Task {

  use CRM_Activity_Form_ActivityFormTrait;
  use CRM_Custom_Form_CustomDataTrait;

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
  public $_activityIds = [];

  /**
   * The id of activity type.
   *
   * @var int
   */
  public $_activityTypeId;

  /**
   * The label of the activity type.
   * Unfortunately this variable is called Name but don't want to change it
   * since it's public and might be commonly used in customized code. See also
   * activityTypeNameAndLabel used in the smarty template.
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
   * @var bool
   */
  protected $_isSurveyActivity;

  protected $_values = [];

  protected $unsavedWarn = TRUE;

  /**
   *
   * Is it possible to create separate activities with this form?
   *
   * When TRUE, the form will ask whether the user wants to create separate
   * activities (if the user has specified multiple contacts in the "with"
   * field).
   *
   * When FALSE, the form will create one activity with all contacts together
   * and won't ask the user anything.
   *
   * Note: This is a class property so that child classes can turn off this
   * behavior (e.g. in CRM_Case_Form_Activity)
   *
   * @var bool
   *
   */
  protected $supportsActivitySeparation = TRUE;

  public $submitOnce = TRUE;

  /**
   * @var array
   */
  public $_groupTree;

  public $_entityTagValues;

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

    $this->_fields = [
      'subject' => [
        'type' => 'text',
        'label' => ts('Subject'),
        'attributes' => CRM_Core_DAO::getAttribute('CRM_Activity_DAO_Activity', 'activity_subject'),
      ],
      'duration' => [
        'type' => 'number',
        'label' => ts('Duration'),
        'attributes' => ['class' => 'four', 'min' => 1],
        'required' => FALSE,
      ],
      'location' => [
        'type' => 'text',
        'label' => ts('Location'),
        'attributes' => CRM_Core_DAO::getAttribute('CRM_Activity_DAO_Activity', 'location'),
        'required' => FALSE,
      ],
      'details' => [
        'type' => 'wysiwyg',
        'label' => ts('Details'),
        'attributes' => ['class' => 'huge'],
        'required' => FALSE,
      ],
      'status_id' => [
        'type' => 'select',
        'required' => TRUE,
      ],
      'priority_id' => [
        'type' => 'select',
        'required' => TRUE,
      ],
      'source_contact_id' => [
        'type' => 'entityRef',
        'label' => ts('Added by'),
        'required' => TRUE,
      ],
      'target_contact_id' => [
        'type' => 'entityRef',
        'label' => ts('With Contact'),
        'attributes' => ['multiple' => TRUE, 'create' => TRUE],
      ],
      'assignee_contact_id' => [
        'type' => 'entityRef',
        'label' => ts('Assigned to'),
        'attributes' => [
          'multiple' => TRUE,
          'create' => TRUE,
          'api' => ['params' => ['is_deceased' => 0]],
        ],
      ],
      'activity_date_time' => [
        'type' => 'datepicker',
        'label' => ts('Date'),
        'required' => TRUE,
      ],
      'followup_assignee_contact_id' => [
        'type' => 'entityRef',
        'label' => ts('Assigned to'),
        'attributes' => [
          'multiple' => TRUE,
          'create' => TRUE,
          'api' => ['params' => ['is_deceased' => 0]],
        ],
      ],
      'followup_activity_type_id' => [
        'type' => 'select',
        'label' => ts('Followup Activity'),
        'attributes' => ['' => '- ' . ts('select activity') . ' -'] + $activityTypes,
        'extra' => ['class' => 'crm-select2'],
      ],
      // Add optional 'Subject' field for the Follow-up Activiity, CRM-4491
      'followup_activity_subject' => [
        'type' => 'text',
        'label' => ts('Subject'),
        'attributes' => CRM_Core_DAO::getAttribute('CRM_Activity_DAO_Activity', 'subject'),
      ],
    ];
  }

  /**
   * Build the form object.
   *
   * @throws \CRM_Core_Exception
   */
  public function preProcess() {
    CRM_Core_Form_RecurringEntity::preProcess('civicrm_activity');

    $session = CRM_Core_Session::singleton();
    $this->_currentUserId = CRM_Core_Session::getLoggedInContactID();

    $this->_currentlyViewedContactId = $this->get('contactId');
    if (!$this->_currentlyViewedContactId) {
      $this->_currentlyViewedContactId = CRM_Utils_Request::retrieve('cid', 'Positive', $this);
    }
    $this->assign('contactId', $this->_currentlyViewedContactId);

    // FIXME: Overcomplicated 'context' causes push-pull between various use-cases for the form
    // FIXME: the solution is typically to ditch 'context' and just respond to the data
    // (e.g. is an activity_type_id present? is case_id present?)
    if (!isset($this->_context)) {
      $this->_context = CRM_Utils_Request::retrieve('context', 'Alphanumeric', $this);
      if (CRM_Contact_Form_Search::isSearchContext($this->_context)) {
        $this->_context = 'search';
      }
      elseif (!in_array($this->_context, ['standalone', 'dashlet', 'case', 'dashletFullscreen'])
        && $this->_currentlyViewedContactId
      ) {
        $this->_context = 'activity';
      }
      $this->_compContext = CRM_Utils_Request::retrieve('compContext', 'String', $this);
    }

    $this->assign('context', $this->_context);

    $this->_action = CRM_Utils_Request::retrieve('action', 'String', $this);
    if ($this->getAction() & CRM_Core_Action::DELETE) {
      if (!CRM_Core_Permission::check('delete activities')) {
        CRM_Core_Error::statusBounce(ts('You do not have permission to access this page.'));
      }
    }

    $this->_activityTypeId = $this->getActivityValue('activity_type_id') ?: CRM_Utils_Request::retrieve('atype', 'Positive', $this);
    $this->assign('atype', $this->_activityTypeId);
    $this->assign('activityId', $this->_activityId);

    // Check for required permissions, CRM-6264.
    if ($this->getActivityID() &&
      in_array($this->_action, [CRM_Core_Action::UPDATE, CRM_Core_Action::VIEW]) &&
      !CRM_Activity_BAO_Activity::checkPermission($this->_activityId, $this->_action)
    ) {
      CRM_Core_Error::statusBounce(ts('You do not have permission to access this page.'));
    }
    if (($this->_action & CRM_Core_Action::VIEW) &&
      CRM_Activity_BAO_Activity::checkPermission($this->_activityId, CRM_Core_Action::UPDATE)
    ) {
      $this->assign('permission', 'edit');
      $this->assign('allow_edit_inbound_emails', CRM_Activity_BAO_Activity::checkEditInboundEmailsPermissions());
    }

    $this->assignActivityType();

    // Check the mode when this form is called either single or as
    // search task action.
    if ($this->_activityTypeId ||
      $this->_context === 'standalone' ||
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
      $this->_groupTree = CRM_Core_BAO_CustomGroup::getTree('Activity', NULL,
        $this->_activityId, 0, $this->_activityTypeId, NULL, TRUE, NULL, FALSE, CRM_Core_Permission::VIEW
      );
    }

    $activityTypeDescription = NULL;
    if ($this->_activityTypeId) {
      [$this->_activityTypeName, $activityTypeDescription] = CRM_Core_BAO_OptionValue::getActivityTypeDetails($this->_activityTypeId);
    }

    // Set activity type name and description to template. Type should no longer be used anywhere
    // except the case_activity workflow template - unsure how to test that... We want to remove
    // it due to mis-naming of the variable. The workflow template can use a token...
    $this->assign('activityTypeName', $this->_activityTypeName);
    $this->assign('activityTypeDescription', $activityTypeDescription ?? FALSE);

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

    if ($this->_context === 'fulltext') {
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
    elseif (in_array($this->_context, ['standalone', 'home', 'dashlet', 'dashletFullscreen'])) {
      $urlParams = 'reset=1';
      $urlString = 'civicrm/dashboard';
    }
    elseif ($this->_context === 'search') {
      $urlParams = 'force=1';
      if ($qfKey) {
        $urlParams .= "&qfKey=$qfKey";
      }
      $path = CRM_Utils_System::currentPath();
      if ($this->_compContext === 'advanced') {
        $urlString = 'civicrm/contact/search/advanced';
      }
      elseif ($path === 'civicrm/group/search'
        || $path === 'civicrm/contact/search'
        || $path === 'civicrm/contact/search/advanced'
        || $path === 'civicrm/contact/search/custom'
        || $path === 'civicrm/group/search'
        || $path === 'civicrm/contact/search/builder'
      ) {
        $urlString = $path;
      }
      else {
        $urlString = 'civicrm/activity/search';
      }
      $this->assign('searchKey', $qfKey);
    }
    elseif ($this->_context !== 'caseActivity') {
      $urlParams = "action=browse&reset=1&cid={$this->_currentlyViewedContactId}&selectedChild=activity";
      $urlString = 'civicrm/contact/view';
    }

    if ($urlString) {
      $session->pushUserContext(CRM_Utils_System::url($urlString, $urlParams));
    }

    // hack to retrieve activity type id from post variables
    if (!$this->_activityTypeId) {
      $this->_activityTypeId = $_POST['activity_type_id'] ?? NULL;
    }

    // when custom data is included in this page
    $this->assign('cid', $this->_currentlyViewedContactId);
    if ($this->isSubmitted()) {
      // The custom data fields are added to the form by an ajax form.
      // However, if they are not present in the element index they will
      // not be available from `$this->getSubmittedValue()` in post process.
      // We do not have to set defaults or otherwise render - just add to the element index.
      $this->addCustomDataFieldsToForm('Activity', array_filter([
        'id' => $this->getActivityID(),
        'activity_type_id' => $this->_activityTypeId,
      ]));
    }

    // add attachments part
    CRM_Core_BAO_File::buildAttachment($this, 'civicrm_activity', $this->_activityId, NULL, TRUE);

    // figure out the file name for activity type, if any
    if ($this->_activityTypeId) {
      $this->_activityTypeFile = CRM_Activity_BAO_Activity::getFileForActivityTypeId($this->_activityTypeId, $this->_crmDir);
    }
    $this->assign('activityTypeFile', $this->_activityTypeFile);
    $this->assign('crmDir', $this->_crmDir);

    $this->setFields();

    if ($this->_activityTypeFile) {
      $className = "CRM_{$this->_crmDir}_Form_Activity_{$this->_activityTypeFile}";
      $className::preProcess($this);
    }

    $this->_values = $this->get('values');
    if (!is_array($this->_values)) {
      $this->_values = [];
      if (isset($this->_activityId) && $this->_activityId) {
        $params = ['id' => $this->_activityId];
        CRM_Activity_BAO_Activity::retrieve($params, $this->_values);
      }

      $this->set('values', $this->_values);
    }

    if ($this->_action & CRM_Core_Action::UPDATE) {
      // We filter out alternatives, in case this is a stored e-mail, before sending to front-end
      if (isset($this->_values['details'])) {
        $this->_values['details'] = CRM_Utils_String::stripAlternatives($this->_values['details']) ?: '';
      }

      if ($this->_activityTypeName === 'Inbound Email' &&
        !CRM_Core_Permission::check('edit inbound email basic information and content')
      ) {
        $this->_fields['details']['type'] = 'static';
      }

      CRM_Core_Form_RecurringEntity::preProcess('civicrm_activity');
    }

    if ($this->_action & CRM_Core_Action::VIEW) {
      $this->_values['details'] = CRM_Utils_String::purifyHtml($this->_values['details'] ?? '');
      $url = CRM_Utils_System::url(implode("/", $this->urlPath), "reset=1&id={$this->_activityId}&action=view&cid={$this->_values['source_contact_id']}");
      CRM_Utils_Recent::add(CRM_Utils_Array::value('subject', $this->_values, ts('(no subject)')),
        $url,
        $this->_values['id'],
        'Activity',
        $this->_values['source_contact_id'],
        $this->_values['source_contact']
      );
    }
  }

  /**
   * Get any smarty elements that may not be present in the form.
   *
   * To make life simpler for smarty we ensure they are set to null
   * rather than unset. This is done at the last minute when $this
   * is converted to an array to be assigned to the form.
   *
   * @return array
   */
  public function getOptionalQuickFormElements(): array {
    return array_merge(['separation', 'tag'], $this->optionalQuickFormElements);
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

      if ($this->_context !== 'standalone') {
        $this->assign('target_contact_value', $defaults['target_contact_value'] ?? NULL);
        $this->assign('assignee_contact_value', $defaults['assignee_contact_value'] ?? NULL);
      }

      // Fixme: why are we getting the wrong keys from upstream?
      $defaults['target_contact_id'] = $defaults['target_contact'] ?? NULL;
      $defaults['assignee_contact_id'] = $defaults['assignee_contact'] ?? NULL;

      // set default tags if exists
      $defaults['tag'] = implode(',', CRM_Core_BAO_EntityTag::getTag($this->_activityId, 'civicrm_activity'));
    }
    else {
      // if it's a new activity, we need to set default values for associated contact fields
      $this->_sourceContactId = $this->_currentUserId;
      $this->_targetContactId = $this->_currentlyViewedContactId;

      $defaults['source_contact_id'] = $this->_sourceContactId;
      $defaults['target_contact_id'] = $this->_targetContactId;
    }

    if (empty($defaults['activity_date_time'])) {
      $defaults['activity_date_time'] = date('Y-m-d H:i:s');
    }

    if ($this->_activityTypeId) {
      $defaults['activity_type_id'] = $this->_activityTypeId;
    }

    if (!$this->_single && !empty($this->_contactIds)) {
      $defaults['target_contact_id'] = $this->_contactIds;
    }

    // CRM-15472 - 50 is around the practical limit of how many items a select2 entityRef can handle
    if ($this->_action == CRM_Core_Action::UPDATE && !empty($defaults['target_contact_id'])) {
      $count = count(is_array($defaults['target_contact_id']) ? $defaults['target_contact_id'] : explode(',', $defaults['target_contact_id']));
      if ($count > 50) {
        $this->freeze(['target_contact_id']);
        $this->assign('disable_swap_button', TRUE);
      }
    }

    if ($this->_action & (CRM_Core_Action::DELETE | CRM_Core_Action::RENEW)) {
      $this->assign('delName', $defaults['subject'] ?? NULL);
    }

    if ($this->_activityTypeFile) {
      $className = "CRM_{$this->_crmDir}_Form_Activity_{$this->_activityTypeFile}";
      $defaults += $className::setDefaultValues($this);
    }
    if (empty($defaults['priority_id'])) {
      $defaults['priority_id'] = CRM_Core_OptionGroup::getDefaultValue('priority');
    }
    if (empty($defaults['status_id'])) {
      $defaults['status_id'] = CRM_Core_OptionGroup::getDefaultValue('activity_status');
    }
    return $defaults;
  }

  /**
   * Build Quick form.
   *
   * @throws \CRM_Core_Exception
   */
  public function buildQuickForm() {
    if ($this->_action & (CRM_Core_Action::DELETE | CRM_Core_Action::RENEW)) {
      //enable form element (ActivityLinks sets this true)
      $this->assign('suppressForm', FALSE);

      $button = ts('Delete');
      if ($this->_action & CRM_Core_Action::RENEW) {
        $button = ts('Restore');
      }
      $this->addButtons([
        [
          'type' => 'next',
          'name' => $button,
          'spacing' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
          'isDefault' => TRUE,
        ],
        [
          'type' => 'cancel',
          'name' => ts('Cancel'),
        ],
      ]);
      return;
    }

    // Build other activity links.
    CRM_Activity_Form_ActivityLinks::commonBuildQuickForm($this);

    // Enable form element (ActivityLinks sets this true).
    $this->assign('suppressForm', FALSE);

    $element = $this->add('select', 'activity_type_id', ts('Activity Type'),
      $this->_fields['followup_activity_type_id']['attributes'],
      FALSE, [
        'onchange' => "CRM.buildCustomData( 'Activity', this.value, false, false, false, false, false, false, {$this->_currentlyViewedContactId});",
        'class' => 'crm-select2 required',
        'placeholder' => TRUE,
      ]
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
        $attribute = $values['attributes'] ?? NULL;
        $required = !empty($values['required']);

        if ($values['type'] === 'select' && empty($attribute)) {
          $this->addSelect($field, ['entity' => 'activity'], $required);
        }
        elseif ($values['type'] === 'entityRef') {
          $this->addEntityRef($field, $values['label'], $attribute, $required);
        }
        else {
          $this->add($values['type'], $field, $values['label'], $attribute, $required, $values['extra'] ?? NULL);
        }
      }
    }

    // CRM-7362 --add campaigns.
    CRM_Campaign_BAO_Campaign::addCampaign($this, $this->_values['campaign_id'] ?? NULL);

    // Add engagement level CRM-7775
    $buildEngagementLevel = FALSE;
    if (CRM_Core_Component::isEnabled('CiviCampaign') &&
      CRM_Campaign_BAO_Campaign::accessCampaign()
    ) {
      $buildEngagementLevel = TRUE;
      $this->addSelect('engagement_level', ['entity' => 'activity']);
      $this->addRule('engagement_level',
        ts('Please enter the engagement index as a number (integers only).'),
        'positiveInteger'
      );
    }
    $this->assign('buildEngagementLevel', $buildEngagementLevel);

    // check for survey activity
    $this->_isSurveyActivity = FALSE;

    if ($this->_activityId && CRM_Core_Component::isEnabled('CiviCampaign') &&
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
            array_combine($responseOptions, $responseOptions),
            FALSE, ['placeholder' => TRUE]
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

    // Add the "Activity Separation" field
    $actionIsAdd = ($this->_action != CRM_Core_Action::UPDATE && $this->_action != CRM_Core_Action::VIEW);
    $separationIsPossible = $this->supportsActivitySeparation;
    if ($actionIsAdd && $separationIsPossible) {
      $this->addRadio(
        'separation',
        ts('Activity Separation'),
        [
          'separate' => ts('Create separate activities for each contact'),
          'combined' => ts('Create one activity with all contacts together'),
        ]
      );
    }

    $this->addRule('duration',
      ts('Please enter the duration as number of minutes (integers only).'), 'positiveInteger'
    );

    // Add followup date.
    $this->add('datepicker', 'followup_date', ts('in'));

    // Only admins and case-workers can change the activity source
    if (!CRM_Core_Permission::check('administer CiviCRM') && $this->_context !== 'caseActivity') {
      $this->getElement('source_contact_id')->freeze();
    }

    //need to assign custom data subtype to the template for the initial loading of the custom data.
    $this->assign('customDataSubType', $this->_activityTypeId);
    $this->assign('entityID', $this->_activityId);

    $tags = CRM_Core_BAO_Tag::getColorTags('civicrm_activity');

    if (!empty($tags)) {
      $this->add('select2', 'tag', ts('Tags'), $tags, FALSE, [
        'class' => 'huge',
        'placeholder' => ts('- select -'),
        'multiple' => TRUE,
      ]);
    }

    // we need to hide activity tagset for special activities
    $specialActivities = ['Open Case'];

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

      $this->addButtons([
        [
          'type' => 'cancel',
          'name' => ts('Done'),
        ],
      ]);
    }
    else {
      $this->addButtons([
        [
          'type' => 'upload',
          'name' => ts('Save'),
          'isDefault' => TRUE,
        ],
        [
          'type' => 'cancel',
          'name' => ts('Cancel'),
        ],
      ]);
    }

    if ($this->_activityTypeFile) {
      $className = "CRM_{$this->_crmDir}_Form_Activity_{$this->_activityTypeFile}";

      $className::buildQuickForm($this);
      $this->addFormRule([$className, 'formRule'], $this);
    }

    $this->addFormRule(['CRM_Activity_Form_Activity', 'formRule'], $this);

    $doNotNotifyAssigneeFor = (array) Civi::settings()
      ->get('do_not_notify_assignees_for');
    if (($this->_activityTypeId && in_array($this->_activityTypeId, $doNotNotifyAssigneeFor)) || !Civi::settings()
      ->get('activity_assignee_notification')) {
      $this->assign('activityAssigneeNotification', FALSE);
    }
    else {
      $this->assign('activityAssigneeNotification', TRUE);
    }
    $this->assign('doNotNotifyAssigneeFor', $doNotNotifyAssigneeFor);
  }

  /**
   * Global form rule.
   *
   * @param array $fields
   *   The input form values.
   * @param array $files
   *   The uploaded files if any.
   * @param self $self
   *
   * @return bool|array
   *   true if no errors, else array of errors
   */
  public static function formRule($fields, $files, $self) {
    // skip form rule if deleting
    if (($fields['_qf_Activity_next_'] ?? NULL) === 'Delete') {
      return TRUE;
    }
    $errors = [];
    if ((array_key_exists('activity_type_id', $fields) || !$self->_single) && empty($fields['activity_type_id'])) {
      $errors['activity_type_id'] = ts('Activity Type is a required field');
    }

    $activity_type_id = $fields['activity_type_id'] ?? NULL;
    $activity_status_id = $fields['status_id'] ?? NULL;
    $scheduled_status_id = CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'status_id', 'Scheduled');

    if ($activity_type_id && $activity_status_id == $scheduled_status_id) {
      if ($activity_type_id == CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'activity_type_id', 'Email')) {
        $errors['status_id'] = ts('You cannot record scheduled email activity.');
      }
      elseif ($activity_type_id == CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'activity_type_id', 'SMS')) {
        $errors['status_id'] = ts('You cannot record scheduled SMS activity');
      }
    }

    if (!empty($fields['followup_activity_type_id']) && empty($fields['followup_date'])) {
      $errors['followup_date'] = ts('Followup date is a required field.');
    }
    // Activity type is mandatory if subject or follow-up date is specified for an Follow-up activity, CRM-4515.
    if ((!empty($fields['followup_activity_subject']) || !empty($fields['followup_date'])) && empty($fields['followup_activity_type_id'])) {
      $errors['followup_activity_subject'] = ts('Follow-up Activity type is a required field.');
    }

    // Check that a value has been set for the "activity separation" field if needed
    $separationIsPossible = $self->supportsActivitySeparation;
    $actionIsAdd = $self->_action == CRM_Core_Action::ADD;
    $hasMultipleTargetContacts = !empty($fields['target_contact_id']) && str_contains($fields['target_contact_id'], ',');
    $separationFieldIsEmpty = empty($fields['separation']);
    if ($separationIsPossible && $actionIsAdd && $hasMultipleTargetContacts && $separationFieldIsEmpty) {
      $errors['separation'] = ts('Activity Separation is a required field.');
    }

    return $errors;
  }

  /**
   * Process the form submission.
   *
   *
   * @param array $params
   *
   * @return array|null
   * @throws \CRM_Core_Exception
   */
  public function postProcess($params = NULL) {
    if ($this->_action & CRM_Core_Action::DELETE) {
      // Look up any repeat activities to be deleted.
      $activityIds = array_column(CRM_Core_BAO_RecurringEntity::getEntitiesFor($this->_activityId, 'civicrm_activity', TRUE, NULL), 'id');
      if (!$activityIds) {
        // There are no repeat activities to delete - just this one.
        $activityIds = [$this->_activityId];
      }

      // Delete each activity.
      foreach ($activityIds as $activityId) {
        $deleteParams = ['id' => $activityId];
        $moveToTrash = CRM_Case_BAO_Case::isCaseActivity($activityId);
        CRM_Activity_BAO_Activity::deleteActivity($deleteParams, $moveToTrash);

        // delete tags for the entity
        $tagParams = [
          'entity_table' => 'civicrm_activity',
          'entity_id' => $activityId,
        ];

        CRM_Core_BAO_EntityTag::del($tagParams);
      }

      CRM_Core_Session::setStatus(
        ts("Selected Activity has been deleted successfully.", ['plural' => '%count Activities have been deleted successfully.', 'count' => count($activityIds)]),
        ts('Record Deleted', ['plural' => 'Records Deleted', 'count' => count($activityIds)]), 'success'
      );

      return NULL;
    }

    // store the submitted values in an array
    if (!$params) {
      $params = $this->getSubmittedValues();
    }
    else {
      CRM_Core_Error::deprecatedWarning('passing params into postProcess is deprecated. Match parent function');
    }

    // Set activity type id.
    if (empty($params['activity_type_id'])) {
      $params['activity_type_id'] = $this->_activityTypeId;
    }

    $params['custom'] = CRM_Core_BAO_CustomField::postProcess($this->getSubmittedValues(),
      $this->_activityId,
      'Activity'
    );

    // format params as arrays
    foreach (['target', 'assignee', 'followup_assignee'] as $name) {
      if (!empty($params["{$name}_contact_id"])) {
        $params["{$name}_contact_id"] = explode(',', $params["{$name}_contact_id"]);
      }
      else {
        $params["{$name}_contact_id"] = [];
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

    $params['is_multi_activity'] = ($params['separation'] ?? NULL) === 'separate';

    $activity = [];
    if (!empty($params['is_multi_activity']) &&
      !CRM_Utils_Array::crmIsEmptyArray($params['target_contact_id'])
    ) {
      $targetContacts = $params['target_contact_id'];
      foreach ($targetContacts as $targetContactId) {
        $params['target_contact_id'] = [$targetContactId];
        // save activity
        $activity[] = $this->processActivity($params);
      }
    }
    else {
      // save activity
      $activity = $this->processActivity($params);
    }

    // Redirect to contact page or activity view in standalone mode
    if ($this->_context === 'standalone') {
      if (count($params['target_contact_id']) == 1) {
        $url = CRM_Utils_System::url('civicrm/contact/view', ['cid' => CRM_Utils_Array::first($params['target_contact_id']), 'selectedChild' => 'activity']);
      }
      else {
        $url = CRM_Utils_System::url('civicrm/activity', ['action' => 'view', 'reset' => 1, 'id' => $this->_activityId]);
      }
      CRM_Core_Session::singleton()->pushUserContext($url);
    }

    $activityIds = empty($this->_activityIds) ? [$this->_activityId] : $this->_activityIds;
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
      $params['dateColumns'] = ['activity_date_time'];

      // Set default repetition start if it was not provided.
      if (empty($params['repetition_start_date'])) {
        $params['repetition_start_date'] = $params['activity_date_time'];
      }

      // unset activity id
      unset($params['id']);
      $linkedEntities = [
        [
          'table' => 'civicrm_activity_contact',
          'findCriteria' => [
            'activity_id' => $activityId,
          ],
          'linkedColumns' => ['activity_id'],
          'isRecurringEntityRecord' => FALSE,
        ],
      ];
      CRM_Core_Form_RecurringEntity::postProcess($params, 'civicrm_activity', $linkedEntities);
    }

    return ['activity' => $activity];
  }

  /**
   * Process activity creation.
   *
   * @param array $params
   *   Associated array of submitted values.
   *
   * @return self|null|object
   * @throws \CRM_Core_Exception
   */
  protected function processActivity(&$params) {
    $activityAssigned = [];
    $activityContacts = CRM_Activity_BAO_ActivityContact::buildOptions('record_type_id', 'validate');
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
    $tagParams = [];
    if (!empty($params['tag'])) {
      if (!is_array($params['tag'])) {
        $params['tag'] = explode(',', $params['tag']);
      }

      $tagParams = array_fill_keys($params['tag'], 1);
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

    if (Civi::settings()->get('activity_assignee_notification')
      && !in_array($activity->activity_type_id, Civi::settings()
        ->get('do_not_notify_assignees_for'))) {
      $activityIDs = [$activity->id];
      if ($followupActivity) {
        $activityIDs = array_merge($activityIDs, [$followupActivity->id]);
      }
      $assigneeContacts = CRM_Activity_BAO_ActivityAssignment::getAssigneeNames($activityIDs, TRUE, FALSE);

      if (!CRM_Utils_Array::crmIsEmptyArray($params['assignee_contact_id'])) {
        $mailToContacts = [];

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
        $mailToFollowupContacts = [];
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
      [
        1 => $subject,
        2 => $followupStatus,
        3 => $mailStatus,
      ]
    ), ts('Saved'), 'success');

    return $activity;
  }

  /**
   * Shorthand for getting id by display name (makes code more readable)
   *
   * @param string $displayName
   *
   * @return null|string
   * @throws \CRM_Core_Exception
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
   *
   * @param int $id
   *
   * @return null|string
   * @throws \CRM_Core_Exception
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

  /**
   * For the moment keeping this the same as the original pulled from preProcess(). Also note the "s" at the end of the function name - planning to change that but in baby steps.
   *
   * @return string[]
   */
  public function getActivityTypeDisplayLabels() {
    return CRM_Core_OptionGroup::values('activity_type', FALSE, FALSE, FALSE, 'AND v.value = ' . $this->_activityTypeId, 'label');
  }

  /**
   * For the moment this is just pulled from preProcess
   */
  public function assignActivityType() {
    // Default array with required key for Smarty template
    $activityTypeNameAndLabel = ['machineName' => FALSE];

    if ($this->_activityTypeId) {
      $activityTypeDisplayLabels = $this->getActivityTypeDisplayLabels();
      if ($activityTypeDisplayLabels[$this->_activityTypeId]) {
        $this->_activityTypeName = $activityTypeDisplayLabels[$this->_activityTypeId];

        // At the moment this is duplicating other code in this section, but refactoring in small steps.
        $activityTypeObj = new CRM_Activity_BAO_ActivityType($this->_activityTypeId);
        $activityTypeNameAndLabel = $activityTypeObj->getActivityType();
      }
      // Set title.
      if (isset($activityTypeDisplayLabels)) {
        // FIXME - it's not clear why the if line just above is needed here and why we can't just set this once above and re-use. What is interesting, but can't possibly be the reason, is that the first if block will fail if the label is the string '0', whereas this one won't. But who would have an activity type called '0'?
        $activityTypeDisplayLabel = $activityTypeDisplayLabels[$this->_activityTypeId] ?? NULL;

        if ($this->_currentlyViewedContactId) {
          $displayName = CRM_Contact_BAO_Contact::displayName($this->_currentlyViewedContactId);
          // Check if this is default domain contact CRM-10482.
          if (CRM_Contact_BAO_Contact::checkDomainContact($this->_currentlyViewedContactId)) {
            $displayName .= ' (' . ts('default organization') . ')';
          }
          $this->setTitle($displayName . ' - ' . $activityTypeDisplayLabel);
        }
        else {
          $this->setTitle(ts('%1 Activity', [1 => $activityTypeDisplayLabel]));
        }
      }
    }
    $this->assign('activityTypeNameAndLabel', $activityTypeNameAndLabel);
  }

  /**
   * Get the activity ID.
   *
   * @api This function will not change in a minor release and is supported for
   * use outside of core. This annotation / external support for properties
   * is only given where there is specific test cover.
   *
   * @noinspection PhpUnhandledExceptionInspection
   */
  public function getActivityID(): ?int {
    // CRM-6957
    // When we come from contact search, activity id never comes.
    // So don't try to get from object, it might gives you wrong one.
    if ($this->controller instanceof \CRM_Contact_Controller_Search) {
      return NULL;
    }
    if (!isset($this->_activityId) && $this->_action != CRM_Core_Action::ADD) {
      $this->_activityId = CRM_Utils_Request::retrieve('id', 'Positive', $this);
    }
    return $this->_activityId ? (int) $this->_activityId : FALSE;
  }

}
