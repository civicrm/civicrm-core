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
 * Choose include / exclude groups and mass sms.
 */
class CRM_SMS_Form_Group extends CRM_Contact_Form_Task {

  /**
   * Set variables up before form is built.
   */
  public function preProcess() {
    if (!CRM_SMS_BAO_SmsProvider::activeProviderCount()) {
      CRM_Core_Error::statusBounce(ts('The <a href="%1">SMS Provider</a> has not been configured or is not active.', [1 => CRM_Utils_System::url('civicrm/admin/sms/provider', 'reset=1')]));
    }

    $session = CRM_Core_Session::singleton();
    $session->replaceUserContext(CRM_Utils_System::url('civicrm/mailing/browse', 'reset=1&sms=1'));
    $this->assign('isAdmin', CRM_Core_Permission::check('administer CiviCRM'));
  }

  /**
   * Set default values for the form.
   * The default values are retrieved from the database.
   */
  public function setDefaultValues() {
    $mailingID = CRM_Utils_Request::retrieve('mid', 'Integer', $this, FALSE, NULL);
    $continue = CRM_Utils_Request::retrieve('continue', 'String', $this, FALSE, NULL);

    $defaults = [];

    if ($mailingID) {
      $mailing = new CRM_Mailing_DAO_Mailing();
      $mailing->id = $mailingID;
      $mailing->addSelect('name');
      $mailing->find(TRUE);

      $defaults['name'] = $mailing->name;
      if (!$continue) {
        $defaults['name'] = ts('Copy of %1', [1 => $mailing->name]);
      }
      else {
        // CRM-7590, reuse same mailing ID if we are continuing
        $this->set('mailing_id', $mailingID);
      }

      $dao = new CRM_Mailing_DAO_MailingGroup();

      $mailingGroups = [];
      $dao->mailing_id = $mailingID;
      $dao->find();
      while ($dao->fetch()) {
        $mailingGroups[$dao->entity_table][$dao->group_type][] = $dao->entity_id;
      }

      $defaults['includeGroups'] = $mailingGroups['civicrm_group']['Include'];
      $defaults['excludeGroups'] = $mailingGroups['civicrm_group']['Exclude'] ?? NULL;

      $defaults['includeMailings'] = $mailingGroups['civicrm_mailing']['Include'] ?? NULL;
      $defaults['excludeMailings'] = $mailingGroups['civicrm_mailing']['Exclude'] ?? NULL;
    }

    return $defaults;
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm() {

    // Get the context.
    $context = $this->get('context');

    $this->assign('context', $context);

    $this->add('text', 'name', ts('Name Your SMS'),
      CRM_Core_DAO::getAttribute('CRM_Mailing_DAO_Mailing', 'name'),
      TRUE
    );

    $this->add('select', 'sms_provider_id',
      ts('Select SMS Provider'),
      CRM_Utils_Array::collect('title', CRM_SMS_BAO_SmsProvider::getProviders(NULL, ['is_active' => 1])),
      TRUE
    );

    // Get the mailing groups.
    $groups = CRM_Core_PseudoConstant::nestedGroup(TRUE, 'Mailing');

    // Get the sms mailing list.
    $mailings = CRM_Mailing_PseudoConstant::completed('sms');
    if (!$mailings) {
      $mailings = [];
    }

    // run the groups through a hook so users can trim it if needed
    CRM_Utils_Hook::mailingGroups($this, $groups, $mailings);

    $select2style = [
      'multiple' => TRUE,
      'style' => 'width: 100%; max-width: 60em;',
      'class' => 'crm-select2',
      'placeholder' => ts('- select -'),
    ];

    $this->add('select', 'includeGroups',
      ts('Include Group(s)'),
      $groups,
      TRUE,
      $select2style
    );

    $this->add('select', 'excludeGroups',
      ts('Exclude Group(s)'),
      $groups,
      FALSE,
      $select2style
    );

    $this->add('select', 'includeMailings',
      ts('INCLUDE Recipients of These Message(s)'),
      $mailings,
      FALSE,
      $select2style
    );
    $this->add('select', 'excludeMailings',
      ts('EXCLUDE Recipients of These Message(s)'),
      $mailings,
      FALSE,
      $select2style
    );

    $this->addFormRule(['CRM_SMS_Form_Group', 'formRule']);

    $buttons = [
      [
        'type' => 'next',
        'name' => ts('Next'),
        'spacing' => '&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;',
        'isDefault' => TRUE,
      ],
      [
        'type' => 'cancel',
        'name' => ts('Cancel'),
      ],
    ];

    $this->addButtons($buttons);

    $this->assign('groupCount', count($groups));
    $this->assign('mailingCount', count($mailings));
  }

  public function postProcess() {
    $values = $this->controller->exportValues($this->_name);

    $groups = [];

    foreach (['name', 'group_id', 'is_sms', 'sms_provider_id'] as $n) {
      if (!empty($values[$n])) {
        $params[$n] = $values[$n];
        if ($n == 'sms_provider_id') {
          // Get the from Name.
          $params['from_name'] = CRM_Core_DAO::getFieldValue('CRM_SMS_DAO_SmsProvider', $params['sms_provider_id'], 'username');
        }
      }
    }

    $qf_Group_submit = $this->controller->exportValue($this->_name, '_qf_Group_submit');
    $this->set('name', $params['name']);

    $inGroups = $values['includeGroups'];
    $outGroups = $values['excludeGroups'];
    $inMailings = $values['includeMailings'];
    $outMailings = $values['excludeMailings'];

    if (is_array($inGroups)) {
      foreach ($inGroups as $key => $id) {
        if ($id) {
          $groups['include'][] = $id;
        }
      }
    }
    if (is_array($outGroups)) {
      foreach ($outGroups as $key => $id) {
        if ($id) {
          $groups['exclude'][] = $id;
        }
      }
    }

    $mailings = [];
    if (is_array($inMailings)) {
      foreach ($inMailings as $key => $id) {
        if ($id) {
          $mailings['include'][] = $id;
        }
      }
    }
    if (is_array($outMailings)) {
      foreach ($outMailings as $key => $id) {
        if ($id) {
          $mailings['exclude'][] = $id;
        }
      }
    }

    $session = CRM_Core_Session::singleton();
    $params['groups'] = $groups;
    $params['mailings'] = $mailings;
    $ids = [];
    if ($this->get('mailing_id')) {

      // don't create a new mass sms if already exists
      $ids['mailing_id'] = $this->get('mailing_id');

      $groupTableName = CRM_Contact_BAO_Group::getTableName();
      $mailingTableName = CRM_Mailing_BAO_Mailing::getTableName();

      // delete previous includes/excludes, if mailing already existed
      foreach (['groups', 'mailings'] as $entity) {
        $mg = new CRM_Mailing_DAO_MailingGroup();
        $mg->mailing_id = $ids['mailing_id'];
        $mg->entity_table = ($entity == 'groups') ? $groupTableName : $mailingTableName;
        $mg->find();
        while ($mg->fetch()) {
          $mg->delete();
        }
      }
    }
    else {
      // new mailing, so lets set the created_id
      $session = CRM_Core_Session::singleton();
      $params['created_id'] = $session->get('userID');
      $params['created_date'] = date('YmdHis');
    }

    $mailing = CRM_Mailing_BAO_Mailing::create($params, $ids);

    $this->set('mailing_id', $mailing->id);

    // also compute the recipients and store them in the mailing recipients table
    CRM_Mailing_BAO_Mailing::getRecipients($mailing->id);

    $count = CRM_Mailing_BAO_MailingRecipients::mailingSize($mailing->id);
    $this->set('count', $count);
    $this->assign('count', $count);
    $this->set('groups', $groups);
    $this->set('mailings', $mailings);

    if ($qf_Group_submit) {
      $status = ts("Your Mass SMS has been saved.");
      CRM_Core_Session::setStatus($status, ts('Saved'), 'success');
      $url = CRM_Utils_System::url('civicrm/mailing', 'reset=1&sms=1');
      return $this->controller->setDestination($url);
    }
  }

  /**
   * Display Name of the form.
   *
   *
   * @return string
   */
  public function getTitle() {
    return ts('Select Recipients');
  }

  /**
   * Global validation rules for the form.
   *
   * @param array $fields
   *   Posted values of the form.
   *
   * @return array
   *   list of errors to be posted back to the form
   */
  public static function formRule($fields) {
    $errors = [];
    if (isset($fields['includeGroups']) &&
      is_array($fields['includeGroups']) &&
      isset($fields['excludeGroups']) &&
      is_array($fields['excludeGroups'])
    ) {
      $checkGroups = [];
      $checkGroups = array_intersect($fields['includeGroups'], $fields['excludeGroups']);
      if (!empty($checkGroups)) {
        $errors['excludeGroups'] = ts('Cannot have same groups in Include Group(s) and Exclude Group(s).');
      }
    }

    if (isset($fields['includeMailings']) &&
      is_array($fields['includeMailings']) &&
      isset($fields['excludeMailings']) &&
      is_array($fields['excludeMailings'])
    ) {
      $checkMailings = [];
      $checkMailings = array_intersect($fields['includeMailings'], $fields['excludeMailings']);
      if (!empty($checkMailings)) {
        $errors['excludeMailings'] = ts('Cannot have same sms in Include mailing(s) and Exclude mailing(s).');
      }
    }

    return empty($errors) ? TRUE : $errors;
  }

}
