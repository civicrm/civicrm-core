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
class CRM_Event_Form_ManageEvent_Registration extends CRM_Event_Form_ManageEvent {

  /**
   * What blocks should we show and hide.
   *
   * @var CRM_Core_ShowHideBlocks
   */
  protected $_showHide;

  protected $_profilePostMultiple = [];
  protected $_profilePostMultipleAdd = [];

  /**
   * Set variables up before form is built.
   */
  public function preProcess() {
    $this->_addProfileBottom = CRM_Utils_Array::value('addProfileBottom', $_GET, FALSE);
    $this->_profileBottomNum = CRM_Utils_Array::value('addProfileNum', $_GET, 0);
    $this->_addProfileBottomAdd = CRM_Utils_Array::value('addProfileBottomAdd', $_GET, FALSE);
    $this->_profileBottomNumAdd = CRM_Utils_Array::value('addProfileNumAdd', $_GET, 0);

    parent::preProcess();
    $this->setSelectedChild('registration');

    $this->assign('addProfileBottom', $this->_addProfileBottom);
    $this->assign('profileBottomNum', $this->_profileBottomNum);

    $urlParams = "id={$this->_id}&addProfileBottom=1&qfKey={$this->controller->_key}";
    $this->assign('addProfileParams', $urlParams);

    if ($addProfileBottom = CRM_Utils_Array::value('custom_post_id_multiple', $_POST)) {
      foreach (array_keys($addProfileBottom) as $profileNum) {
        self::buildMultipleProfileBottom($this, $profileNum);
      }
    }

    $this->assign('addProfileBottomAdd', $this->_addProfileBottomAdd);
    $this->assign('profileBottomNumAdd', $this->_profileBottomNumAdd);

    $urlParamsAdd = "id={$this->_id}&addProfileBottomAdd=1&qfKey={$this->controller->_key}";
    $this->assign('addProfileParamsAdd', $urlParamsAdd);

    if ($addProfileBottomAdd = CRM_Utils_Array::value('additional_custom_post_id_multiple', $_POST)) {
      foreach (array_keys($addProfileBottomAdd) as $profileNum) {
        self::buildMultipleProfileBottom($this, $profileNum, 'additional_', ts('Profile for Additional Participants'));
      }
    }
  }

  /**
   * Set default values for the form.
   *
   * The default values are retrieved from the database.
   */
  public function setDefaultValues() {
    if ($this->_addProfileBottom || $this->_addProfileBottomAdd) {
      return;
    }
    $eventId = $this->_id;

    $defaults = parent::setDefaultValues();

    $this->setShowHide($defaults);
    if (isset($eventId)) {
      $params = ['id' => $eventId];
      CRM_Event_BAO_Event::retrieve($params, $defaults);

      $ufJoinParams = [
        'entity_table' => 'civicrm_event',
        'module' => 'CiviEvent',
        'entity_id' => $eventId,
      ];

      list($defaults['custom_pre_id'],
        $defaults['custom_post']
        ) = CRM_Core_BAO_UFJoin::getUFGroupIds($ufJoinParams);

      // Get the id for the event registration profile
      $eventRegistrationIdParams = $eventRegistrationIdDefaults = [
        'name' => 'event_registration',
      ];
      CRM_Core_BAO_UFGroup::retrieve($eventRegistrationIdParams, $eventRegistrationIdDefaults);

      // Set event registration as the default profile if none selected
      if (!$defaults['custom_pre_id'] && count($defaults['custom_post']) == 0) {
        $defaults['custom_pre_id'] = CRM_Utils_Array::value('id', $eventRegistrationIdDefaults);
      }
      if (isset($defaults['custom_post']) && is_numeric($defaults['custom_post'])) {
        $defaults['custom_post_id'] = $defaults['custom_post'];
      }
      elseif (!empty($defaults['custom_post'])) {
        $defaults['custom_post_id'] = $defaults['custom_post'][0];
        unset($defaults['custom_post'][0]);
        $this->_profilePostMultiple = $defaults['custom_post'];
        foreach ($defaults['custom_post'] as $key => $value) {
          self::buildMultipleProfileBottom($this, $key);
          $defaults["custom_post_id_multiple[$key]"] = $value;
        }
      }

      $this->assign('profilePostMultiple', CRM_Utils_Array::value('custom_post', $defaults));

      // CRM-17745: Make max additional participants configurable
      if (empty($defaults['max_additional_participants'])) {
        $defaults['max_additional_participants'] = 9;
      }

      if (!empty($defaults['is_multiple_registrations'])) {
        // CRM-4377: set additional participants’ profiles – set to ‘none’ if explicitly unset (non-active)

        $ufJoinAddParams = [
          'entity_table' => 'civicrm_event',
          'module' => 'CiviEvent_Additional',
          'entity_id' => $eventId,
        ];

        list($defaults['additional_custom_pre_id'],
          $defaults['additional_custom_post']
          ) = CRM_Core_BAO_UFJoin::getUFGroupIds($ufJoinAddParams);

        if (isset($defaults['additional_custom_post']) && is_numeric($defaults['additional_custom_post'])) {
          $defaults['additional_custom_post_id'] = $defaults['additional_custom_post'];
        }
        elseif (!empty($defaults['additional_custom_post'])) {
          $defaults['additional_custom_post_id'] = $defaults['additional_custom_post'][0];
          unset($defaults['additional_custom_post'][0]);

          $this->_profilePostMultipleAdd = $defaults['additional_custom_post'];
          foreach ($defaults['additional_custom_post'] as $key => $value) {
            self::buildMultipleProfileBottom($this, $key, 'additional_', ts('Profile for Additional Participants'));
            $defaults["additional_custom_post_id_multiple[$key]"] = $value;
          }
        }
        $this->assign('profilePostMultipleAdd', CRM_Utils_Array::value('additional_custom_post', $defaults, []));
      }
    }
    else {
      $defaults['is_email_confirm'] = 0;
    }

    // provide defaults for required fields if empty (and as a 'hint' for approval message field)
    $defaults['registration_link_text'] = CRM_Utils_Array::value('registration_link_text', $defaults, ts('Register Now'));
    $defaults['confirm_title'] = CRM_Utils_Array::value('confirm_title', $defaults, ts('Confirm Your Registration Information'));
    $defaults['thankyou_title'] = CRM_Utils_Array::value('thankyou_title', $defaults, ts('Thank You for Registering'));
    $defaults['approval_req_text'] = CRM_Utils_Array::value('approval_req_text', $defaults, ts('Participation in this event requires approval. Submit your registration request here. Once approved, you will receive an email with a link to a web page where you can complete the registration process.'));

    return $defaults;
  }

  /**
   * Fix what blocks to show/hide based on the default values set
   *
   * @param array $defaults
   *   The array of default values.
   *
   * @return void
   */
  public function setShowHide($defaults) {
    $this->_showHide = new CRM_Core_ShowHideBlocks(['registration' => 1],
      ''
    );
    if (empty($defaults)) {
      $this->_showHide->addHide('registration');
      $this->_showHide->addHide('id-approval-text');
    }
    else {
      if (empty($defaults['requires_approval'])) {
        $this->_showHide->addHide('id-approval-text');
      }
    }
    $this->assign('defaultsEmpty', empty($defaults));
    $this->_showHide->addToTemplate();
  }

  /**
   * Build the form object.
   *
   * @return void
   */
  public function buildQuickForm() {
    if ($this->_addProfileBottom) {
      return self::buildMultipleProfileBottom($this, $this->_profileBottomNum);
    }

    if ($this->_addProfileBottomAdd) {
      return self::buildMultipleProfileBottom($this, $this->_profileBottomNumAdd, 'additional_', ts('Profile for Additional Participants'));
    }

    $this->applyFilter('__ALL__', 'trim');
    $attributes = CRM_Core_DAO::getAttribute('CRM_Event_DAO_Event');

    $this->addElement('checkbox',
      'is_online_registration',
      ts('Allow Online Registration'),
      NULL,
      [
        'onclick' => "return showHideByValue('is_online_registration'," .
        "''," .
        "'registration_blocks'," .
        "'block'," .
        "'radio'," .
        "false );",
      ]
    );

    $this->add('text', 'registration_link_text', ts('Registration Link Text'));

    if (!$this->_isTemplate) {
      $this->add('datepicker', 'registration_start_date', ts('Registration Start Date'), [], FALSE, ['time' => TRUE]);
      $this->add('datepicker', 'registration_end_date', ts('Registration End Date'), [], FALSE, ['time' => TRUE]);
    }

    $params = [
      'used' => 'Supervised',
      'contact_type' => 'Individual',
    ];
    $dedupeRuleFields = CRM_Dedupe_BAO_Rule::dedupeRuleFields($params);

    foreach ($dedupeRuleFields as $key => $fields) {
      $ruleFields[$key] = ucwords(str_replace('_', ' ', $fields));
    }

    $this->addElement('checkbox',
      'is_multiple_registrations',
      ts('Register multiple participants?')
    );

    // CRM-17745: Make maximum additional participants configurable
    $numericOptions = CRM_Core_SelectValues::getNumericOptions(1, 9);
    $this->add('select', 'max_additional_participants', ts('Maximum additional participants'), $numericOptions, FALSE, ['class' => 'required']);

    $this->addElement('checkbox',
      'allow_same_participant_emails',
      ts('Same email address?')
    );
    $this->assign('ruleFields', json_encode($ruleFields));

    $dedupeRules = [
      '' => '- Unsupervised rule -',
    ];
    $dedupeRules += CRM_Dedupe_BAO_RuleGroup::getByType('Individual');
    $this->add('select', 'dedupe_rule_group_id', ts('Duplicate matching rule'), $dedupeRules);

    $participantStatuses = CRM_Event_PseudoConstant::participantStatus();
    if (in_array('Awaiting approval', $participantStatuses) and in_array('Pending from approval', $participantStatuses) and in_array('Rejected', $participantStatuses)) {
      $this->addElement('checkbox',
        'requires_approval',
        ts('Require participant approval?'),
        NULL,
        ['onclick' => "return showHideByValue('requires_approval', '', 'id-approval-text', 'table-row', 'radio', false);"]
      );
      $this->add('textarea', 'approval_req_text', ts('Approval message'), $attributes['approval_req_text']);
    }

    $this->add('text', 'expiration_time', ts('Pending participant expiration (hours)'));
    $this->addRule('expiration_time', ts('Please enter the number of hours (as an integer).'), 'integer');
    $this->addField('allow_selfcancelxfer', ['label' => ts('Allow self-service cancellation or transfer?'), 'type' => 'advcheckbox']);
    $this->add('text', 'selfcancelxfer_time', ts('Cancellation or transfer time limit (hours)'));
    $this->addRule('selfcancelxfer_time', ts('Please enter the number of hours (as an integer).'), 'integer');
    self::buildRegistrationBlock($this);
    self::buildConfirmationBlock($this);
    self::buildMailBlock($this);
    self::buildThankYouBlock($this);

    parent::buildQuickForm();
  }

  /**
   * Build Registration Block.
   *
   * @param CRM_Core_Form $form
   *
   */
  public function buildRegistrationBlock(&$form) {
    $attributes = CRM_Core_DAO::getAttribute('CRM_Event_DAO_Event', 'intro_text') + ['class' => 'collapsed', 'preset' => 'civievent'];
    $form->add('wysiwyg', 'intro_text', ts('Introductory Text'), $attributes);
    $form->add('wysiwyg', 'footer_text', ts('Footer Text'), $attributes);

    extract(self::getProfileSelectorTypes());
    //CRM-15427
    $form->addProfileSelector('custom_pre_id', ts('Include Profile') . '<br />' . ts('(top of page)'), $allowCoreTypes, $allowSubTypes, $profileEntities, TRUE, $usedFor);
    $form->addProfileSelector('custom_post_id', ts('Include Profile') . '<br />' . ts('(bottom of page)'), $allowCoreTypes, $allowSubTypes, $profileEntities, TRUE, $usedFor);

    $form->addProfileSelector('additional_custom_pre_id', ts('Profile for Additional Participants') . '<br />' . ts('(top of page)'), $allowCoreTypes, $allowSubTypes, $profileEntities, TRUE, $usedFor);
    $form->addProfileSelector('additional_custom_post_id', ts('Profile for Additional Participants') . '<br />' . ts('(bottom of page)'), $allowCoreTypes, $allowSubTypes, $profileEntities, TRUE, $usedFor);
  }

  /**
   * Subroutine to insert a Profile Editor widget.
   * depends on getProfileSelectorTypes
   *
   * @param array &$form
   * @param int $count
   *   Unique index.
   * @param string $prefix
   *   Dom element ID prefix.
   * @param string $label
   *   Label.
   * @param array $configs
   *   Optional, for addProfileSelector(), defaults to using getProfileSelectorTypes().
   */
  public function buildMultipleProfileBottom(&$form, $count, $prefix = '', $label = 'Include Profile', $configs = NULL) {
    extract((is_null($configs)) ? self::getProfileSelectorTypes() : $configs);
    $element = $prefix . "custom_post_id_multiple[$count]";
    $label .= '<br />' . ts('(bottom of page)');
    $form->addProfileSelector($element, $label, $allowCoreTypes, $allowSubTypes, $profileEntities, TRUE, $usedFor);
  }

  /**
   * Create initializers for addprofileSelector.
   *
   * @return array
   *   ['allowCoreTypes' => array, 'allowSubTypes' => array, 'profileEntities' => array]
   */
  public static function getProfileSelectorTypes() {
    $configs = [
      'allowCoreTypes' => [],
      'allowSubTypes' => [],
      'profileEntities' => [],
      'usedFor' => [],
    ];

    $configs['allowCoreTypes'] = array_merge([
      'Contact',
      'Individual',
    ], CRM_Contact_BAO_ContactType::subTypes('Individual'));
    $configs['allowCoreTypes'][] = 'Participant';
    if (CRM_Core_Permission::check('manage event profiles') && !CRM_Core_Permission::check('administer CiviCRM')) {
      $configs['usedFor'][] = 'CiviEvent';
    }
    //CRM-15427
    $id = CRM_Utils_Request::retrieve('id', 'Integer');
    if ($id) {
      $participantEventType = CRM_Core_DAO::getFieldValue("CRM_Event_DAO_Event", $id, 'event_type_id', 'id');
      $participantRole = CRM_Core_DAO::getFieldValue('CRM_Event_DAO_Event', $id, 'default_role_id');
      $configs['allowSubTypes']['ParticipantEventName'] = [$id];
      $configs['allowSubTypes']['ParticipantEventType'] = [$participantEventType];
      $configs['allowSubTypes']['ParticipantRole'] = [$participantRole];
    }
    $configs['profileEntities'][] = ['entity_name' => 'contact_1', 'entity_type' => 'IndividualModel'];
    $configs['profileEntities'][] = [
      'entity_name' => 'participant_1',
      'entity_type' => 'ParticipantModel',
      'entity_sub_type' => '*',
    ];

    return $configs;
  }

  /**
   * Build Confirmation Block.
   *
   * @param CRM_Core_Form $form
   *
   */
  public function buildConfirmationBlock(&$form) {
    $attributes = CRM_Core_DAO::getAttribute('CRM_Event_DAO_Event');
    // CRM-11182 - Optional confirmation page for free events
    $is_monetary = CRM_Core_DAO::getFieldValue('CRM_Event_DAO_Event', $form->_id, 'is_monetary');
    $form->assign('is_monetary', $is_monetary);
    if ($is_monetary == "0") {
      $form->addYesNo('is_confirm_enabled', ts('Use a confirmation screen?'), NULL, NULL, ['onclick' => "return showHideByValue('is_confirm_enabled','','confirm_screen_settings','block','radio',false);"]);
    }
    $form->add('text', 'confirm_title', ts('Title'), $attributes['confirm_title']);
    $form->add('wysiwyg', 'confirm_text', ts('Introductory Text'), $attributes['confirm_text'] + ['class' => 'collapsed', 'preset' => 'civievent']);
    $form->add('wysiwyg', 'confirm_footer_text', ts('Footer Text'), $attributes['confirm_text'] + ['class' => 'collapsed', 'preset' => 'civievent']);
  }

  /**
   * Build Email Block.
   *
   * @param CRM_Core_Form $form
   *
   */
  public function buildMailBlock(&$form) {
    $form->registerRule('emailList', 'callback', 'emailList', 'CRM_Utils_Rule');
    $attributes = CRM_Core_DAO::getAttribute('CRM_Event_DAO_Event');
    $form->addYesNo('is_email_confirm', ts('Send Confirmation Email?'), NULL, NULL, ['onclick' => "return showHideByValue('is_email_confirm','','confirmEmail','block','radio',false);"]);
    $form->add('textarea', 'confirm_email_text', ts('Text'), $attributes['confirm_email_text']);
    $form->add('text', 'cc_confirm', ts('CC Confirmation To'), CRM_Core_DAO::getAttribute('CRM_Event_DAO_Event', 'cc_confirm'));
    $form->addRule('cc_confirm', ts('Please enter a valid list of comma delimited email addresses'), 'emailList');
    $form->add('text', 'bcc_confirm', ts('BCC Confirmation To'), CRM_Core_DAO::getAttribute('CRM_Event_DAO_Event', 'bcc_confirm'));
    $form->addRule('bcc_confirm', ts('Please enter a valid list of comma delimited email addresses'), 'emailList');
    $form->add('text', 'confirm_from_name', ts('Confirm From Name'));
    $form->add('text', 'confirm_from_email', ts('Confirm From Email'));
    $form->addRule('confirm_from_email', ts('Email is not valid.'), 'email');
  }

  /**
   * @param CRM_Core_Form $form
   */
  public function buildThankYouBlock(&$form) {
    $attributes = CRM_Core_DAO::getAttribute('CRM_Event_DAO_Event');
    $form->add('text', 'thankyou_title', ts('Title'), $attributes['thankyou_title']);
    $form->add('wysiwyg', 'thankyou_text', ts('Introductory Text'), $attributes['thankyou_text'] + ['class' => 'collapsed', 'preset' => 'civievent']);
    $form->add('wysiwyg', 'thankyou_footer_text', ts('Footer Text'), $attributes['thankyou_text'] + ['class' => 'collapsed', 'preset' => 'civievent']);
  }

  /**
   * Add local and global form rules.
   *
   *
   * @return void
   */
  public function addRules() {
    if ($this->_addProfileBottom || $this->_addProfileBottomAdd) {
      return;
    }
    $this->addFormRule(['CRM_Event_Form_ManageEvent_Registration', 'formRule'], $this);
  }

  /**
   * Global validation rules for the form.
   *
   * @param array $values
   * @param $files
   * @param CRM_Core_Form $form
   *
   * @return array
   *   list of errors to be posted back to the form
   */
  public static function formRule($values, $files, $form) {
    if (!empty($values['is_online_registration'])) {

      if (!$values['confirm_title']) {
        $errorMsg['confirm_title'] = ts('Please enter a Title for the registration Confirmation Page');
      }
      if (!$values['thankyou_title']) {
        $errorMsg['thankyou_title'] = ts('Please enter a Title for the registration Thank-you Page');
      }
      if ($values['is_email_confirm']) {
        if (!$values['confirm_from_name']) {
          $errorMsg['confirm_from_name'] = ts('Please enter Confirmation Email FROM Name.');
        }

        if (!$values['confirm_from_email']) {
          $errorMsg['confirm_from_email'] = ts('Please enter Confirmation Email FROM Email Address.');
        }
      }

      if (isset($values['registration_start_date']) && isset($values['registration_end_date'])) {
        if ($values['registration_end_date'] < $values['registration_start_date']) {
          $errorMsg['registration_end_date'] = ts('Registration end date should be after Registration start date');
        }
      }

      //check that the selected profiles have either firstname+lastname or email required
      $profileIds = [
        CRM_Utils_Array::value('custom_pre_id', $values),
        CRM_Utils_Array::value('custom_post_id', $values),
      ];
      $additionalProfileIds = [
        CRM_Utils_Array::value('additional_custom_pre_id', $values),
        CRM_Utils_Array::value('additional_custom_post_id', $values),
      ];
      //additional profile fields default to main if not set
      if (!is_numeric($additionalProfileIds[0])) {
        $additionalProfileIds[0] = $profileIds[0];
      }
      if (!is_numeric($additionalProfileIds[1])) {
        $additionalProfileIds[1] = $profileIds[1];
      }
      //add multiple profiles if set
      self::addMultipleProfiles($profileIds, $values, 'custom_post_id_multiple');
      self::addMultipleProfiles($additionalProfileIds, $values, 'additional_custom_post_id_multiple');
      $isProfileComplete = self::isProfileComplete($profileIds);
      $isAdditionalProfileComplete = self::isProfileComplete($additionalProfileIds);

      //Check main profiles have an email address available if 'send confirmation email' is selected
      if ($values['is_email_confirm']) {
        $emailFields = self::getEmailFields($profileIds);
        if (!count($emailFields)) {
          $errorMsg['is_email_confirm'] = ts("Please add a profile with an email address if 'Send Confirmation Email?' is selected");
        }
      }
      $additionalCustomPreId = $additionalCustomPostId = NULL;
      $isPreError = $isPostError = TRUE;
      if (!empty($values['allow_same_participant_emails']) && !empty($values['is_multiple_registrations'])) {
        $types = array_merge(['Individual'], CRM_Contact_BAO_ContactType::subTypes('Individual'));
        $profiles = CRM_Core_BAO_UFGroup::getProfiles($types);

        //check for additional custom pre profile
        $additionalCustomPreId = CRM_Utils_Array::value('additional_custom_pre_id', $values);
        if (!empty($additionalCustomPreId)) {
          if (!($additionalCustomPreId == 'none')) {
            $customPreId = $additionalCustomPreId;
          }
          else {
            $isPreError = FALSE;
          }
        }
        else {
          $customPreId = !empty($values['custom_pre_id']) ? $values['custom_pre_id'] : NULL;
        }
        //check whether the additional custom pre profile is of type 'Individual' and its subtypes
        if (!empty($customPreId)) {
          $profileTypes = CRM_Core_BAO_UFGroup::profileGroups($customPreId);
          foreach ($types as $individualTypes) {
            if (in_array($individualTypes, $profileTypes)) {
              $isPreError = FALSE;
              break;
            }
          }
        }
        else {
          $isPreError = FALSE;
        }

        // We don't have required Individual fields in the pre-custom profile, so now check the post-custom profile
        if ($isPreError) {
          $additionalCustomPostId = CRM_Utils_Array::value('additional_custom_post_id', $values);
          if (!empty($additionalCustomPostId)) {
            if (!($additionalCustomPostId == 'none')) {
              $customPostId = $additionalCustomPostId;
            }
            else {
              $isPostError = FALSE;
            }
          }
          else {
            $customPostId = !empty($values['custom_post_id']) ? $values['custom_post_id'] : NULL;
          }
          //check whether the additional custom post profile is of type 'Individual' and its subtypes
          if (!empty($customPostId)) {
            $profileTypes = CRM_Core_BAO_UFGroup::profileGroups($customPostId);
            foreach ($types as $individualTypes) {
              if (in_array($individualTypes, $profileTypes)) {
                $isPostError = FALSE;
                break;
              }
            }
          }
          else {
            $isPostError = FALSE;
          }

          if (empty($customPreId) && empty($customPostId)) {
            $errorMsg['additional_custom_pre_id'] = ts("Allow multiple registrations from the same email address requires a profile of type 'Individual'");
          }
          if ($isPostError) {
            $errorMsg['additional_custom_post_id'] = ts("Allow multiple registrations from the same email address requires a profile of type 'Individual'");
          }
        }
      }
      if (!$isProfileComplete) {
        $errorMsg['custom_pre_id'] = ts("Please include a Profile for online registration that contains an Email Address field and / or First Name + Last Name fields.");
      }
      if (!$isAdditionalProfileComplete) {
        $errorMsg['additional_custom_pre_id'] = ts("Please include a Profile for online registration of additional participants that contains an Email Address field and / or First Name + Last Name fields.");
      }

      // // CRM-8485
      // $config = CRM_Core_Config::singleton();
      // if ( $config->doNotAttachPDFReceipt ) {
      //     if (!empty($values['custom_post_id_multiple'])) {
      //         foreach( $values['custom_post_id_multiple'] as $count => $customPostMultiple ) {
      //             if ( $customPostMultiple ) {
      //                 $errorMsg["custom_post_id_multiple[{$count}]"] = ts('Please disable PDF receipt as an attachment in <a href="%1">Miscellaneous Settings</a> if you want to add additional profiles.', array( 1 => CRM_Utils_System::url( 'civicrm/admin/setting/misc', 'reset=1' ) ) );
      //                 break;
      //             }
      //         }
      //     }
      //
      //     if (!empty($values['is_multiple_registrations']) &&
      //          CRM_Utils_Array::value('additional_custom_post_id_multiple',  $values) ) {
      //         foreach( $values['additional_custom_post_id_multiple'] as $count => $customPostMultiple ) {
      //             if ( $customPostMultiple ) {
      //                $errorMsg["additional_custom_post_id_multiple[{$count}]"] = ts('Please disable PDF receipt as an attachment in <a href="%1">Miscellaneous Settings</a> if you want to add additional profiles.', array( 1 => CRM_Utils_System::url( 'civicrm/admin/setting/misc', 'reset=1' ) ) );
      //                 break;
      //             }
      //         }
      //     }
      // }

      if (!empty($errorMsg)) {
        if (!empty($values['custom_post_id_multiple'])) {
          foreach ($values['custom_post_id_multiple'] as $count => $customPostMultiple) {
            self::buildMultipleProfileBottom($form, $count);
          }
          $form->assign('profilePostMultiple', $values['custom_post_id_multiple']);
        }
        if (!empty($values['additional_custom_post_id_multiple'])) {
          foreach ($values['additional_custom_post_id_multiple'] as $count => $customPostMultiple) {
            self::buildMultipleProfileBottom($form, $count, 'additional_', ts('Profile for Additional Participants'));
          }
          $form->assign('profilePostMultipleAdd', $values['additional_custom_post_id_multiple']);
        }
      }
    }

    if (!empty($errorMsg)) {
      return $errorMsg;
    }

    return TRUE;
  }

  /**
   * Collect all email fields for an array of profile ids.
   *
   * @param $profileIds
   * @return bool
   */
  public static function getEmailFields($profileIds) {
    $emailFields = [];
    foreach ($profileIds as $profileId) {
      if ($profileId && is_numeric($profileId)) {
        $fields = CRM_Core_BAO_UFGroup::getFields($profileId);
        foreach ($fields as $field) {
          if (substr_count($field['name'], 'email')) {
            $emailFields[] = $field;
          }
        }
      }
    }
    return $emailFields;
  }

  /**
   * Check if a profile contains required fields.
   *
   * @param $profileIds
   * @return bool
   */
  public static function isProfileComplete($profileIds) {
    $profileReqFields = [];
    foreach ($profileIds as $profileId) {
      if ($profileId && is_numeric($profileId)) {
        $fields = CRM_Core_BAO_UFGroup::getFields($profileId);
        foreach ($fields as $field) {
          switch (TRUE) {
            case substr_count($field['name'], 'email'):
              $profileReqFields[] = 'email';
              break;

            case substr_count($field['name'], 'first_name'):
              $profileReqFields[] = 'first_name';
              break;

            case substr_count($field['name'], 'last_name'):
              $profileReqFields[] = 'last_name';
              break;
          }
        }
      }
    }
    $profileComplete = (in_array('email', $profileReqFields)
      || (in_array('first_name', $profileReqFields) && in_array('last_name', $profileReqFields))
    );
    return $profileComplete;
  }

  /**
   * Check if the profiles collect enough information to dedupe.
   *
   * @param $profileIds
   * @param int $rgId
   * @return bool
   */
  public static function canProfilesDedupe($profileIds, $rgId = 0) {
    // find the unsupervised rule
    $rgParams = [
      'used' => 'Unsupervised',
      'contact_type' => 'Individual',
    ];
    if ($rgId > 0) {
      $rgParams['id'] = $rgId;
    }
    $activeRg = CRM_Dedupe_BAO_RuleGroup::dedupeRuleFieldsWeight($rgParams);

    // get the combinations that could be a match for the rule
    $okCombos = $combos = [];
    CRM_Dedupe_BAO_RuleGroup::combos($activeRg[0], $activeRg[1], $combos);

    // create an index of what combinations involve each field
    $index = [];
    foreach ($combos as $comboid => $combo) {
      foreach ($combo as $cfield) {
        $index[$cfield][$comboid] = TRUE;
      }
      $combos[$comboid] = array_fill_keys($combo, 0);
      $okCombos[$comboid] = array_fill_keys($combo, 2);
    }

    // get profiles and see if they have the necessary combos
    $profileReqFields = [];
    foreach ($profileIds as $profileId) {
      if ($profileId && is_numeric($profileId)) {
        $fields = CRM_Core_BAO_UFGroup::getFields($profileId);

        // walk through the fields in the profile
        foreach ($fields as $field) {

          // check each of the fields in the index against the profile field
          foreach ($index as $ifield => $icombos) {
            if (strpos($field['name'], $ifield) !== FALSE) {

              // we found the field in the profile, now record it in the index
              foreach ($icombos as $icombo => $dontcare) {
                $combos[$icombo][$ifield] = ($combos[$icombo][$ifield] != 2 && !$field['is_required']) ? 1 : 2;

                if ($combos[$icombo] == $okCombos[$icombo]) {
                  // if any combo is complete with 2s (all fields are present and required), we can go home
                  return 2;
                }
              }
            }
          }
        }
      }
    }

    // check the combos to see if everything is > 0
    foreach ($combos as $comboid => $combo) {
      $complete = FALSE;
      foreach ($combo as $cfield) {
        if ($cfield > 0) {
          $complete = TRUE;
        }
        else {
          // this combo isn't complete--skip to the next combo
          continue 2;
        }
      }
      if ($complete) {
        return 1;
      }
    }

    // no combo succeeded
    return 0;
  }

  /**
   * Add additional profiles from the form to an array of profile ids.
   *
   * @param array $profileIds
   * @param array $values
   * @param string $field
   */
  public static function addMultipleProfiles(&$profileIds, $values, $field) {
    if ($multipleProfiles = CRM_Utils_Array::value($field, $values)) {
      foreach ($multipleProfiles as $profileId) {
        $profileIds[] = $profileId;
      }
    }
  }

  /**
   * Process the form submission.
   *
   *
   * @return void
   */
  public function postProcess() {
    $params = $this->exportValues();

    $params['id'] = $this->_id;

    // format params
    $params['is_online_registration'] = CRM_Utils_Array::value('is_online_registration', $params, FALSE);
    // CRM-11182
    $params['is_confirm_enabled'] = CRM_Utils_Array::value('is_confirm_enabled', $params, FALSE);
    $params['is_multiple_registrations'] = CRM_Utils_Array::value('is_multiple_registrations', $params, FALSE);
    $params['allow_same_participant_emails'] = CRM_Utils_Array::value('allow_same_participant_emails', $params, FALSE);
    $params['requires_approval'] = CRM_Utils_Array::value('requires_approval', $params, FALSE);

    // reset is_email confirm if not online reg
    if (!$params['is_online_registration']) {
      $params['is_email_confirm'] = FALSE;
    }
    if (!empty($params['allow_selfcancelxfer'])) {
      $params['selfcancelxfer_time'] = !empty($params['selfcancelxfer_time']) ? $params['selfcancelxfer_time'] : 0;
    }

    CRM_Event_BAO_Event::add($params);

    // also update the ProfileModule tables
    $ufJoinParams = [
      'is_active' => 1,
      'module' => 'CiviEvent',
      'entity_table' => 'civicrm_event',
      'entity_id' => $this->_id,
    ];

    // first delete all past entries
    CRM_Core_BAO_UFJoin::deleteAll($ufJoinParams);

    $uf = [];
    $wt = 2;
    if (!empty($params['custom_pre_id'])) {
      $uf[1] = $params['custom_pre_id'];
      $wt = 1;
    }

    if (!empty($params['custom_post_id'])) {
      $uf[2] = $params['custom_post_id'];
    }

    if (!empty($params['custom_post_id_multiple'])) {
      $uf = array_merge($uf, $params['custom_post_id_multiple']);
    }
    $uf = array_values($uf);
    if (!empty($uf)) {
      foreach ($uf as $weight => $ufGroupId) {
        $ufJoinParams['weight'] = $weight + $wt;
        $ufJoinParams['uf_group_id'] = $ufGroupId;
        CRM_Core_BAO_UFJoin::create($ufJoinParams);
        unset($ufJoinParams['id']);
      }
    }
    // also update the ProfileModule tables
    $ufJoinParamsAdd = [
      'is_active' => 1,
      'module' => 'CiviEvent_Additional',
      'entity_table' => 'civicrm_event',
      'entity_id' => $this->_id,
    ];

    // first delete all past entries
    CRM_Core_BAO_UFJoin::deleteAll($ufJoinParamsAdd);
    if (!empty($params['is_multiple_registrations'])) {
      $ufAdd = [];
      $wtAdd = 2;

      if (array_key_exists('additional_custom_pre_id', $params)) {
        if (empty($params['additional_custom_pre_id'])) {
          $ufAdd[1] = $params['custom_pre_id'];
          $wtAdd = 1;
        }
        elseif (CRM_Utils_Array::value('additional_custom_pre_id', $params) == 'none') {
        }
        else {
          $ufAdd[1] = $params['additional_custom_pre_id'];
          $wtAdd = 1;
        }
      }

      if (array_key_exists('additional_custom_post_id', $params)) {
        if (empty($params['additional_custom_post_id'])) {
          $ufAdd[2] = $params['custom_post_id'];
        }
        elseif (CRM_Utils_Array::value('additional_custom_post_id', $params) == 'none') {
        }
        else {
          $ufAdd[2] = $params['additional_custom_post_id'];
        }
      }

      if (!empty($params['additional_custom_post_id_multiple'])) {
        $additionalPostMultiple = [];
        foreach ($params['additional_custom_post_id_multiple'] as $key => $value) {
          if (is_null($value) && !empty($params['custom_post_id'])) {
            $additionalPostMultiple[$key] = $params['custom_post_id'];
          }
          elseif ($value == 'none') {
            continue;
          }
          elseif ($value) {
            $additionalPostMultiple[$key] = $value;
          }
        }
        $ufAdd = array_merge($ufAdd, $additionalPostMultiple);
      }

      $ufAdd = array_values($ufAdd);
      if (!empty($ufAdd)) {
        foreach ($ufAdd as $weightAdd => $ufGroupIdAdd) {

          $ufJoinParamsAdd['weight'] = $weightAdd + $wtAdd;
          $ufJoinParamsAdd['uf_group_id'] = $ufGroupIdAdd;

          CRM_Core_BAO_UFJoin::create($ufJoinParamsAdd);
          unset($ufJoinParamsAdd['id']);
        }
      }
    }

    // get the profiles to evaluate what they collect
    $profileIds = [
      CRM_Utils_Array::value('custom_pre_id', $params),
      CRM_Utils_Array::value('custom_post_id', $params),
    ];
    $additionalProfileIds = [
      CRM_Utils_Array::value('additional_custom_pre_id', $params),
      CRM_Utils_Array::value('additional_custom_post_id', $params),
    ];
    // additional profile fields default to main if not set
    if (!is_numeric($additionalProfileIds[0])) {
      $additionalProfileIds[0] = $profileIds[0];
    }
    if (!is_numeric($additionalProfileIds[1])) {
      $additionalProfileIds[1] = $profileIds[1];
    }
    //add multiple profiles if set
    self::addMultipleProfiles($profileIds, $params, 'custom_post_id_multiple');
    self::addMultipleProfiles($additionalProfileIds, $params, 'additional_custom_post_id_multiple');

    $cantDedupe = FALSE;
    $rgId = CRM_Utils_Array::value('dedupe_rule_group_id', $params, 0);

    switch (self::canProfilesDedupe($profileIds, $rgId)) {
      case 0:
        $dedupeTitle = 'Duplicate Matching Impossible';
        $cantDedupe = ts("The selected profiles do not contain the fields necessary to match registrations with existing contacts.  This means all anonymous registrations will result in a new contact.");
        break;

      case 1:
        $dedupeTitle = 'Duplicate Contacts Possible';
        $cantDedupe = ts("The selected profiles can collect enough information to match registrations with existing contacts, but not all of the relevant fields are required.  Anonymous registrations may result in duplicate contacts.");
    }
    if (!empty($params['is_multiple_registrations'])) {
      switch (self::canProfilesDedupe($additionalProfileIds, $rgId)) {
        case 0:
          $dedupeTitle = 'Duplicate Matching Impossible';
          if ($cantDedupe) {
            $cantDedupe = ts("The selected profiles do not contain the fields necessary to match registrations with existing contacts.  This means all anonymous registrations will result in a new contact.");
          }
          else {
            $cantDedupe = ts("The selected profiles do not contain the fields necessary to match additional participants with existing contacts.  This means all additional participants will result in a new contact.");
          }
          break;

        case 1:
          if (!$cantDedupe) {
            $dedupeTitle = 'Duplicate Contacts Possible';
            $cantDedupe = ts("The selected profiles can collect enough information to match additional participants with existing contacts, but not all of the relevant fields are required.  This may result in duplicate contacts.");
          }
      }
    }
    if ($cantDedupe) {
      CRM_Core_Session::setStatus($cantDedupe, $dedupeTitle, 'alert dedupenotify', ['expires' => 0]);
    }

    // Update tab "disabled" css class
    $this->ajaxResponse['tabValid'] = !empty($params['is_online_registration']);

    parent::endPostProcess();
  }

  /**
   * Return a descriptive name for the page, used in wizard header
   *
   * @return string
   */
  public function getTitle() {
    return ts('Online Registration');
  }

}
