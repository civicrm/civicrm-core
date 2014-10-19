<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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
 * @copyright CiviCRM LLC (c) 2004-2014
 * $Id$
 *
 */

/**
 * Choose include / exclude groups and mailings
 *
 */
class CRM_Mailing_Form_Group extends CRM_Contact_Form_Task {

  /**
   * the mailing ID of the mailing if we are resuming a mailing
   *
   * @var integer
   */
  protected $_mailingID;

  /**
   * Function to set variables up before form is built
   *
   * @return void
   * @access public
   */
  public function preProcess() {
    if (CRM_Core_BAO_MailSettings::defaultDomain() == "EXAMPLE.ORG") {
      CRM_Core_Error::fatal(ts('The <a href="%1">default mailbox</a> has not been configured. You will find <a href="%2">more info in our online user and administrator guide.</a>', array(1 => CRM_Utils_System::url('civicrm/admin/mailSettings', 'reset=1'), 2 => "http://book.civicrm.org/user/advanced-configuration/email-system-configuration/")));
    }

    $this->_mailingID = CRM_Utils_Request::retrieve('mid', 'Integer', $this, FALSE, NULL);

    // when user come from search context.
    $this->_searchBasedMailing = CRM_Contact_Form_Search::isSearchContext($this->get('context'));
    if ($this->_searchBasedMailing) {
      $searchParams = $this->controller->exportValues();
      // number of records that were selected - All or Few.
      $this->_resultSelectOption = $searchParams['radio_ts'];
      if (CRM_Utils_Array::value('task', $searchParams) == 20) {
        parent::preProcess();
      }
    }

    $session = CRM_Core_Session::singleton();
    if ($this->_searchBasedMailing) {
      $config = CRM_Core_Config::singleton();
      $path = CRM_Utils_Array::value($config->userFrameworkURLVar, $_GET);
      $qfKey = CRM_Utils_Array::value('qfKey', $_GET);
      if ($qfKey) {
        $session->pushUserContext(CRM_Utils_System::url($path, "qfKey=$qfKey"));
      }
      else {
        $session->pushUserContext(CRM_Utils_System::url('civicrm/mailing', 'reset=1'));
      }
    }
    elseif (strpos($session->readUserContext(), 'civicrm/mailing') === FALSE) {
      // use previous context unless mailing is not schedule, CRM-4290
      $session->pushUserContext(CRM_Utils_System::url('civicrm/mailing', 'reset=1'));
    }
  }

  /**
   * This function sets the default values for the form.
   * the default values are retrieved from the database
   *
   * @access public
   *
   * @return void
   */
  function setDefaultValues() {
    $continue = CRM_Utils_Request::retrieve('continue', 'String', $this, FALSE, NULL);

    $defaults = array();
    $defaults['dedupe_email'] = CRM_Core_BAO_Setting::getItem(CRM_Core_BAO_Setting::MAILING_PREFERENCES_NAME,
      'dedupe_email_default', NULL, FALSE
    );

    if ($this->_mailingID) {
      // check that the user has permission to access mailing id
      CRM_Mailing_BAO_Mailing::checkPermission($this->_mailingID);

      $mailing = new CRM_Mailing_DAO_Mailing();
      $mailing->id = $this->_mailingID;
      $mailing->addSelect('name', 'campaign_id');
      $mailing->find(TRUE);

      $defaults['name'] = $mailing->name;
      if (!$continue) {
        $defaults['name'] = ts('Copy of %1', array(1 => $mailing->name));
      }
      else {
        // CRM-7590, reuse same mailing ID if we are continuing
        $this->set('mailing_id', $this->_mailingID);
      }

      $defaults['campaign_id'] = $mailing->campaign_id;
      $defaults['dedupe_email'] = $mailing->dedupe_email;

      $dao = new CRM_Mailing_DAO_MailingGroup();

      $mailingGroups = array(
        'civicrm_group' => array( ),
        'civicrm_mailing' => array( )
      );
      $dao->mailing_id = $this->_mailingID;
      $dao->find();
      while ($dao->fetch()) {
        // account for multi-lingual
        // CRM-11431
        $entityTable = 'civicrm_group';
        if (substr($dao->entity_table, 0, 15) == 'civicrm_mailing') {
          $entityTable = 'civicrm_mailing';
        }
        $mailingGroups[$entityTable][$dao->group_type][] = $dao->entity_id;
      }

      $defaults['includeGroups'] = CRM_Utils_Array::value('include', $mailingGroups['civicrm_group']);
      $defaults['excludeGroups'] = CRM_Utils_Array::value('exclude', $mailingGroups['civicrm_group']);

      if (!empty($mailingGroups['civicrm_mailing'])) {
        $defaults['includeMailings'] = CRM_Utils_Array::value('Include', $mailingGroups['civicrm_mailing']);
        $defaults['excludeMailings'] = CRM_Utils_Array::value('Exclude', $mailingGroups['civicrm_mailing']);
      }
    }

    //when the context is search hide the mailing recipients.
    $showHide = new CRM_Core_ShowHideBlocks();
    $showGroupSelector = TRUE;
    if ($this->_searchBasedMailing) {
      $showGroupSelector = FALSE;
      $formElements      = array('includeGroups', 'excludeGroups', 'includeMailings', 'excludeMailings');
      $formValues        = $this->controller->exportValues($this->_name);
      foreach ($formElements as $element) {
        if (!empty($formValues[$element])) {
          $showGroupSelector = TRUE;
          break;
        }
      }
    }

    if ($showGroupSelector) {
      $showHide->addShow("id-additional");
      $showHide->addHide("id-additional-show");
    }
    else {
      $showHide->addShow("id-additional-show");
      $showHide->addHide("id-additional");
    }
    $showHide->addToTemplate();

    return $defaults;
  }

  /**
   * Function to actually build the form
   *
   * @return void
   * @access public
   */
  public function buildQuickForm() {

    //get the context
    $context = $this->get('context');
    if ($this->_searchBasedMailing) {
      $context = 'search';
    }
    $this->assign('context', $context);

    $this->add('text', 'name', ts('Name Your Mailing'),
      CRM_Core_DAO::getAttribute('CRM_Mailing_DAO_Mailing', 'name'),
      TRUE
    );

    $hiddenMailingGroup = NULL;
    $campaignId = NULL;

    //CRM-7362 --add campaigns.
    if ($this->_mailingID) {
      $campaignId = CRM_Core_DAO::getFieldValue('CRM_Mailing_DAO_Mailing', $this->_mailingID, 'campaign_id');
      $hiddenMailingGroup = CRM_Mailing_BAO_Mailing::hiddenMailingGroup($this->_mailingID);
    }
    CRM_Campaign_BAO_Campaign::addCampaign($this, $campaignId);

    //dedupe on email option
    $this->addElement('checkbox', 'dedupe_email', ts('Remove duplicate emails?'));

    //get the mailing groups.
    $groups = CRM_Core_PseudoConstant::nestedGroup('Mailing');
    if ($hiddenMailingGroup) {
      $groups[$hiddenMailingGroup] =
        CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Group', $hiddenMailingGroup, 'title');
    }

    $mailings = CRM_Mailing_PseudoConstant::completed();
    if (!$mailings) {
      $mailings = array();
    }

    // run the groups through a hook so users can trim it if needed
    CRM_Utils_Hook::mailingGroups($this, $groups, $mailings);

    //when the context is search add base group's.
    if ($this->_searchBasedMailing) {
      //get the static groups
      $staticGroups = CRM_Core_PseudoConstant::staticGroup(FALSE, 'Mailing');
      $this->add('select', 'baseGroup',
        ts('Unsubscription Group'),
        array(
          '' => ts('- select -')) + CRM_Contact_BAO_Group::getGroupsHierarchy($staticGroups, NULL, '&nbsp;&nbsp;', TRUE),
        TRUE,
        array('class' => 'crm-select2 huge')
      );
    }

    $select2style = array(
      'multiple' => TRUE,
      'style' => 'width: 100%; max-width: 60em;',
      'class' => 'crm-select2',
      'placeholder' => ts('- select -'),
    );

    $this->add('select', 'includeGroups',
      ts('Include Group(s)'),
      $groups,
      !$this->_searchBasedMailing,
      $select2style
    );

    $this->add('select', 'excludeGroups',
      ts('Exclude Group(s)'),
      $groups,
      FALSE,
      $select2style
    );

    $this->add('select', 'includeMailings',
      ts('INCLUDE Recipients of These Mailing(s)') . ' ',
      $mailings,
      FALSE,
      $select2style
    );
    $this->add('select', 'excludeMailings',
      ts('EXCLUDE Recipients of These Mailing(s)') . ' ',
      $mailings,
      FALSE,
      $select2style
    );

    $this->addFormRule(array('CRM_Mailing_Form_Group', 'formRule'));

    $buttons = array(
      array('type' => 'next',
        'name' => ts('Next >>'),
        'spacing' => '&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;',
        'isDefault' => TRUE,
      ),
      array(
        'type' => 'submit',
        'name' => ts('Save & Continue Later'),
      ),
      array(
        'type' => 'cancel',
        'name' => ts('Cancel'),
      ),
    );

    $this->addButtons($buttons);

    $this->assign('groupCount', count($groups));
    $this->assign('mailingCount', count($mailings));
    if(count($groups) == 0 && count($mailings) == 0 && !$this->_searchBasedMailing) {
      CRM_Core_Error::statusBounce("To send a mailing, you must have a valid group of recipients - either at least one group that's a Mailing List or at least one previous mailing or start from a search");
    }
  }

  public function postProcess() {
    $values = $this->controller->exportValues($this->_name);

    //build hidden smart group. when user want to send  mailing
    //through search contact-> more action -> send Mailing. CRM-3711
    $groups = array();
    if ($this->_searchBasedMailing && $this->_contactIds) {
      $session = CRM_Core_Session::singleton();

      if ($this->_resultSelectOption == 'ts_sel') {
        // create a static grp if only a subset of result set was selected:

        $randID   = md5(time());
        $grpTitle = "Hidden Group {$randID}";
        $grpID    = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Group', $grpTitle, 'id', 'title');

        if (!$grpID) {
          $groupParams = array(
            'title' => $grpTitle,
            'is_active' => 1,
            'is_hidden' => 1,
            'group_type' => array('2' => 1),
          );

          $group = CRM_Contact_BAO_Group::create($groupParams);
          $grpID = $group->id;

          CRM_Contact_BAO_GroupContact::addContactsToGroup($this->_contactIds, $group->id);

          $newGroupTitle = "Hidden Group {$grpID}";
          $groupParams = array(
            'id'    => $grpID,
            'name'  => CRM_Utils_String::titleToVar($newGroupTitle),
            'title' => $newGroupTitle,
            'group_type' => array('2' => 1),
          );
          $group = CRM_Contact_BAO_Group::create($groupParams);
        }

        // note at this point its a static group
        $smartGroupId = $grpID;
      }
      else {
        //get the hidden smart group id.
        $ssId = $this->get('ssID');
        $hiddenSmartParams = array('group_type' => array('2' => 1),
          'form_values' => $this->get('formValues'),
          'saved_search_id' => $ssId,
          'search_custom_id' => $this->get('customSearchID'),
          'search_context' => $this->get('context'),
        );

        list($smartGroupId, $savedSearchId) = CRM_Contact_BAO_Group::createHiddenSmartGroup($hiddenSmartParams);

        //set the saved search id.
        if (!$ssId) {
          if ($savedSearchId) {
            $this->set('ssID', $savedSearchId);
          }
          else {
            CRM_Core_Error::fatal();
          }
        }
      }

      //get the base group for this mailing, CRM-3711
      $groups['base'] = array($values['baseGroup']);
      $values['includeGroups'][] = $smartGroupId;
    }

    foreach (
      array('name', 'group_id', 'search_id', 'search_args', 'campaign_id', 'dedupe_email') as $n
    ) {
      if (!empty($values[$n])) {
        $params[$n] = $values[$n];
      }
    }


    $qf_Group_submit = $this->controller->exportValue($this->_name, '_qf_Group_submit');
    $this->set('name', $params['name']);

    $inGroups    = $values['includeGroups'];
    $outGroups   = $values['excludeGroups'];
    $inMailings  = $values['includeMailings'];
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

    $session            = CRM_Core_Session::singleton();
    $params['groups']   = $groups;
    $params['mailings'] = $mailings;
    $ids = array();
    if ($this->get('mailing_id')) {

      // don't create a new mailing if already exists
      $ids['mailing_id'] = $this->get('mailing_id');

      $groupTableName = CRM_Contact_BAO_Group::getTableName();
      $mailingTableName = CRM_Mailing_BAO_Mailing::getTableName();

      // delete previous includes/excludes, if mailing already existed
      foreach (array('groups', 'mailings') as $entity) {
        $mg               = new CRM_Mailing_DAO_MailingGroup();
        $mg->mailing_id   = $ids['mailing_id'];
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

    $dedupeEmail = FALSE;
    if (isset($params['dedupe_email'])) {
      $dedupeEmail = $params['dedupe_email'];
    }

    // mailing id should be added to the form object
    $this->_mailingID = $mailing->id;

    // also compute the recipients and store them in the mailing recipients table
    CRM_Mailing_BAO_Mailing::getRecipients(
      $mailing->id,
      $mailing->id,
      NULL,
      NULL,
      TRUE,
      $dedupeEmail
    );

    $count = CRM_Mailing_BAO_Recipients::mailingSize($mailing->id);
    $this->set('count', $count);
    $this->assign('count', $count);
    $this->set('groups', $groups);
    $this->set('mailings', $mailings);

    if ($qf_Group_submit) {
      //when user perform mailing from search context
      //redirect it to search result CRM-3711.
      $ssID = $this->get('ssID');
      $context = $this->get('context');
      if ($ssID && $this->_searchBasedMailing) {
        if ($this->_action == CRM_Core_Action::BASIC) {
          $fragment = 'search';
        }
        elseif ($this->_action == CRM_Core_Action::PROFILE) {
          $fragment = 'search/builder';
        }
        elseif ($this->_action == CRM_Core_Action::ADVANCED) {
          $fragment = 'search/advanced';
        }
        else {
          $fragment = 'search/custom';
        }

        $context = $this->get('context');
        if (!CRM_Contact_Form_Search::isSearchContext($context)) {
          $context = 'search';
        }
        $urlParams = "force=1&reset=1&ssID={$ssID}&context={$context}";

        $qfKey = CRM_Utils_Request::retrieve('qfKey', 'String', $this);
        if (CRM_Utils_Rule::qfKey($qfKey)) {
          $urlParams .= "&qfKey=$qfKey";
        }

        $draftURL = CRM_Utils_System::url('civicrm/mailing/browse/unscheduled', 'scheduled=false&reset=1');
        $status = ts("You can continue later by clicking the 'Continue' action to resume working on it.<br />From <a href='%1'>Draft and Unscheduled Mailings</a>.", array(1 => $draftURL));

        // Redirect user to search.
        $url = CRM_Utils_System::url('civicrm/contact/' . $fragment, $urlParams);
      }
      else {
        $status = ts("Click the 'Continue' action to resume working on it.");
        $url = CRM_Utils_System::url('civicrm/mailing/browse/unscheduled', 'scheduled=false&reset=1');
      }
      CRM_Core_Session::setStatus($status, ts('Mailing Saved'), 'success');
      return $this->controller->setDestination($url);
    }
  }

  /**
   * Display Name of the form
   *
   * @access public
   *
   * @return string
   */
  public function getTitle() {
    return ts('Select Recipients');
  }

  /**
   * global validation rules for the form
   *
   * @param array $fields posted values of the form
   *
   * @return array list of errors to be posted back to the form
   * @static
   * @access public
   */
  static function formRule($fields) {
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
        $errors['excludeMailings'] = ts('Cannot have same mail in Include mailing(s) and Exclude mailing(s).');
      }
    }

    if (!empty($fields['search_id']) &&
      empty($fields['group_id'])
    ) {
      $errors['group_id'] = ts('You must select a group to filter on');
    }

    if (empty($fields['search_id']) &&
      !empty($fields['group_id'])
    ) {
      $errors['search_id'] = ts('You must select a search to filter');
    }

    return empty($errors) ? TRUE : $errors;
  }
}

