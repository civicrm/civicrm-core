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
 * This class provides the register functionality from a search context.
 *
 * Originally the functionality was all munged into the main Participant form.
 *
 * Ideally it would be entirely separated but for now this overrides the main form,
 * just providing a better separation of the functionality for the search vs main form.
 */
class CRM_Event_Form_Task_Register extends CRM_Event_Form_Participant {


  /**
   * Are we operating in "single mode", i.e. adding / editing only
   * one participant record, or is this a batch add operation
   *
   * ote the goal is to disentangle all the non-single stuff
   * into this form and discontinue this param.
   *
   * @var bool
   */
  public $_single = FALSE;

  /**
   * Assign the url path to the template.
   */
  protected function assignUrlPath() {
    //set the appropriate action
    $context = $this->get('context');
    $urlString = 'civicrm/contact/search';
    $this->_action = CRM_Core_Action::BASIC;
    switch ($context) {
      case 'advanced':
        $urlString = 'civicrm/contact/search/advanced';
        $this->_action = CRM_Core_Action::ADVANCED;
        break;

      case 'builder':
        $urlString = 'civicrm/contact/search/builder';
        $this->_action = CRM_Core_Action::PROFILE;
        break;

      case 'basic':
        $urlString = 'civicrm/contact/search/basic';
        $this->_action = CRM_Core_Action::BASIC;
        break;

      case 'custom':
        $urlString = 'civicrm/contact/search/custom';
        $this->_action = CRM_Core_Action::COPY;
        break;
    }
    self::preProcessCommonCopy($this);

    $this->_contactId = NULL;

    //set ajax path, this used for custom data building
    $this->assign('urlPath', $urlString);
    $this->assign('urlPathVar', "_qf_Participant_display=true&qfKey={$this->controller->_key}");
  }

  /**
   * Copy of contact preProcessCommon copied here for the purpose of cleaning up what it does for this form.
   *
   * @param CRM_Core_Form $form
   *
   * @throws \CRM_Core_Exception
   */
  public static function preProcessCommonCopy(&$form) {
    $form->_contactIds = [];
    $form->_contactTypes = [];

    $isStandAlone = in_array('task', $form->urlPath) || in_array('standalone', $form->urlPath);
    if ($isStandAlone) {
      list($form->_task, $title) = CRM_Contact_Task::getTaskAndTitleByClass(get_class($form));
      if (!array_key_exists($form->_task, CRM_Contact_Task::permissionedTaskTitles(CRM_Core_Permission::getPermission()))) {
        CRM_Core_Error::statusBounce(ts('You do not have permission to access this page.'));
      }
      $form->_contactIds = explode(',', CRM_Utils_Request::retrieve('cids', 'CommaSeparatedIntegers', $form, TRUE));
      if (empty($form->_contactIds)) {
        CRM_Core_Error::statusBounce(ts('No Contacts Selected'));
      }
      $form->setTitle($title);
    }

    // get the submitted values of the search form
    // we'll need to get fv from either search or adv search in the future
    $fragment = 'search';
    if ($form->_action == CRM_Core_Action::ADVANCED) {
      self::$_searchFormValues = $form->controller->exportValues('Advanced');
      $fragment .= '/advanced';
    }
    elseif ($form->_action == CRM_Core_Action::PROFILE) {
      self::$_searchFormValues = $form->controller->exportValues('Builder');
      $fragment .= '/builder';
    }
    elseif ($form->_action == CRM_Core_Action::COPY) {
      self::$_searchFormValues = $form->controller->exportValues('Custom');
      $fragment .= '/custom';
    }
    elseif (!$isStandAlone) {
      self::$_searchFormValues = $form->controller->exportValues('Basic');
    }

    //set the user context for redirection of task actions
    $qfKey = CRM_Utils_Request::retrieve('qfKey', 'String', $form);
    $urlParams = 'force=1';
    if (CRM_Utils_Rule::qfKey($qfKey)) {
      $urlParams .= "&qfKey=$qfKey";
    }

    $cacheKey = "civicrm search {$qfKey}";

    $url = CRM_Utils_System::url('civicrm/contact/' . $fragment, $urlParams);
    $session = CRM_Core_Session::singleton();
    $session->replaceUserContext($url);

    $form->_task = self::$_searchFormValues['task'] ?? NULL;
    $crmContactTaskTasks = CRM_Contact_Task::taskTitles();
    $form->assign('taskName', CRM_Utils_Array::value($form->_task, $crmContactTaskTasks));

    // all contacts or action = save a search
    if ((CRM_Utils_Array::value('radio_ts', self::$_searchFormValues) == 'ts_all') ||
      ($form->_task == CRM_Contact_Task::SAVE_SEARCH)
    ) {
      // since we don't store all contacts in prevnextcache, when user selects "all" use query to retrieve contacts
      // rather than prevnext cache table for most of the task actions except export where we rebuild query to fetch
      // final result set
      $allCids[$cacheKey] = self::getContactIds($form);

      $form->_contactIds = [];
      if (empty($form->_contactIds)) {
        // filter duplicates here
        // CRM-7058
        // might be better to do this in the query, but that logic is a bit complex
        // and it decides when to use distinct based on input criteria, which needs
        // to be fixed and optimized.

        foreach ($allCids[$cacheKey] as $cid => $ignore) {
          $form->_contactIds[] = $cid;
        }
      }
    }
    elseif (CRM_Utils_Array::value('radio_ts', self::$_searchFormValues) == 'ts_sel') {
      // selected contacts only
      // need to perform action on only selected contacts
      $insertString = [];

      // refire sql in case of custom search
      if ($form->_action == CRM_Core_Action::COPY) {
        // selected contacts only
        // need to perform action on only selected contacts
        foreach (self::$_searchFormValues as $name => $value) {
          if (substr($name, 0, CRM_Core_Form::CB_PREFIX_LEN) == CRM_Core_Form::CB_PREFIX) {
            $form->_contactIds[] = substr($name, CRM_Core_Form::CB_PREFIX_LEN);
          }
        }
      }
      else {
        // fetching selected contact ids of passed cache key
        $selectedCids = Civi::service('prevnext')->getSelection($cacheKey);
        foreach ($selectedCids[$cacheKey] as $selectedCid => $ignore) {
          $form->_contactIds[] = $selectedCid;
        }
      }

      if (!empty($insertString)) {
        $string = implode(',', $insertString);
        $sql = "REPLACE INTO {$form->_componentTable} ( contact_id ) VALUES $string";
        CRM_Core_DAO::executeQuery($sql);
      }
    }

    //contact type for pick up profiles as per selected contact types with subtypes
    //CRM-5521
    if ($selectedTypes = CRM_Utils_Array::value('contact_type', self::$_searchFormValues)) {
      if (!is_array($selectedTypes)) {
        $selectedTypes = explode(' ', $selectedTypes);
      }
      foreach ($selectedTypes as $ct => $dontcare) {
        if (strpos($ct, CRM_Core_DAO::VALUE_SEPARATOR) === FALSE) {
          $form->_contactTypes[] = $ct;
        }
        else {
          $separator = strpos($ct, CRM_Core_DAO::VALUE_SEPARATOR);
          $form->_contactTypes[] = substr($ct, $separator + 1);
        }
      }
    }

    if (CRM_Utils_Array::value('radio_ts', self::$_searchFormValues) == 'ts_sel'
      && ($form->_action != CRM_Core_Action::COPY)
    ) {
      $sel = self::$_searchFormValues['radio_ts'] ?? NULL;
      $form->assign('searchtype', $sel);
      $result = self::getSelectedContactNames();
      $form->assign("value", $result);
    }

    if (!empty($form->_contactIds)) {
      $form->_componentClause = ' contact_a.id IN ( ' . implode(',', $form->_contactIds) . ' ) ';
      $form->assign('totalSelectedContacts', count($form->_contactIds));

      $form->_componentIds = $form->_contactIds;
    }
  }

}
