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
 * Choose include / exclude groups and mass sms.
 */
class CRM_SMS_Form_Group extends CRM_Contact_Form_Task {

  /**
   * Set variables up before form is built.
   */
  public function preProcess() {
    if (!CRM_SMS_BAO_Provider::activeProviderCount()) {
      CRM_Core_Error::fatal(ts('The <a href="%1">SMS Provider</a> has not been configured or is not active.', array(1 => CRM_Utils_System::url('civicrm/admin/sms/provider', 'reset=1'))));
    }

    $session = CRM_Core_Session::singleton();
    $session->replaceUserContext(CRM_Utils_System::url('civicrm/mailing/browse', 'reset=1&sms=1'));
  }

  /**
   * Set default values for the form.
   * The default values are retrieved from the database.
   */
  public function setDefaultValues() {
    $mailingID = CRM_Utils_Request::retrieve('mid', 'Integer', $this, FALSE, NULL);
    $continue = CRM_Utils_Request::retrieve('continue', 'String', $this, FALSE, NULL);

    $defaults = array();

    if ($mailingID) {
      $mailing = new CRM_Mailing_DAO_Mailing();
      $mailing->id = $mailingID;
      $mailing->addSelect('name');
      $mailing->find(TRUE);

      $defaults['name'] = $mailing->name;
      if (!$continue) {
        $defaults['name'] = ts('Copy of %1', array(1 => $mailing->name));
      }
      else {
        // CRM-7590, reuse same mailing ID if we are continuing
        $this->set('mailing_id', $mailingID);
      }

      $dao = new CRM_Mailing_DAO_MailingGroup();

      $mailingGroups = array();
      $dao->mailing_id = $mailingID;
      $dao->find();
      while ($dao->fetch()) {
        $mailingGroups[$dao->entity_table][$dao->group_type][] = $dao->entity_id;
      }

      $defaults['includeGroups'] = $mailingGroups['civicrm_group']['Include'];
      $defaults['excludeGroups'] = CRM_Utils_Array::value('Exclude', $mailingGroups['civicrm_group']);

      $defaults['includeMailings'] = CRM_Utils_Array::value('Include', CRM_Utils_Array::value('civicrm_mailing', $mailingGroups));
      $defaults['excludeMailings'] = CRM_Utils_Array::value('Exclude', CRM_Utils_Array::value('civicrm_mailing', $mailingGroups));
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

    // Get the mailing groups.
    $groups = CRM_Core_PseudoConstant::nestedGroup('Mailing');

    // Get the sms mailing list.
    $mailings = CRM_Mailing_PseudoConstant::completed('sms');
    if (!$mailings) {
      $mailings = array();
    }

    // run the groups through a hook so users can trim it if needed
    CRM_Utils_Hook::mailingGroups($this, $groups, $mailings);

    $select2style = array(
      'multiple' => TRUE,
      'style' => 'width: 100%; max-width: 60em;',
      'class' => 'crm-select2',
      'placeholder' => ts('- select -'),
    );

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

    $this->addFormRule(array('CRM_SMS_Form_Group', 'formRule'));

    $buttons = array(
      array(
        'type' => 'next',
        'name' => ts('Next'),
        'spacing' => '&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;',
        'isDefault' => TRUE,
      ),
      array(
        'type' => 'cancel',
        'name' => ts('Cancel'),
      ),
    );

    $this->addButtons($buttons);

    $this->assign('groupCount', count($groups));
    $this->assign('mailingCount', count($mailings));
  }

  public function postProcess() {
    $values = $this->controller->exportValues($this->_name);

    $groups = array();

    foreach (array(
      'name',
      'group_id',
      'is_sms',
    ) as $n) {
      if (!empty($values[$n])) {
        $params[$n] = $values[$n];
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

    $mailings = array();
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
    $ids = array();
    if ($this->get('mailing_id')) {

      // don't create a new mass sms if already exists
      $ids['mailing_id'] = $this->get('mailing_id');

      $groupTableName = CRM_Contact_BAO_Group::getTableName();
      $mailingTableName = CRM_Mailing_BAO_Mailing::getTableName();

      // delete previous includes/excludes, if mailing already existed
      foreach (array(
        'groups',
        'mailings',
      ) as $entity) {
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
    CRM_Mailing_BAO_Mailing::getRecipients($mailing->id,
      $mailing->id,
      TRUE,
      FALSE,
      'sms'
    );

    $count = CRM_Mailing_BAO_Recipients::mailingSize($mailing->id);
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
    $errors = array();
    if (isset($fields['includeGroups']) &&
      is_array($fields['includeGroups']) &&
      isset($fields['excludeGroups']) &&
      is_array($fields['excludeGroups'])
    ) {
      $checkGroups = array();
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
      $checkMailings = array();
      $checkMailings = array_intersect($fields['includeMailings'], $fields['excludeMailings']);
      if (!empty($checkMailings)) {
        $errors['excludeMailings'] = ts('Cannot have same sms in Include mailing(s) and Exclude mailing(s).');
      }
    }

    return empty($errors) ? TRUE : $errors;
  }

}
