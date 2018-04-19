<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
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
 * Class CRM_Report_Form_Instance
 */
class CRM_Report_Form_Instance {

  /**
   * Build form.
   *
   * @param CRM_Core_Form $form
   */
  public static function buildForm(&$form) {
    // We should not build form elements in dashlet mode.
    if ($form->_section) {
      return;
    }

    // Check role based permission.
    $instanceID = $form->getVar('_id');
    if ($instanceID && !CRM_Report_Utils_Report::isInstanceGroupRoleAllowed($instanceID)) {
      $url = CRM_Utils_System::url('civicrm/report/list', 'reset=1');
      CRM_Core_Error::statusBounce(ts('You do not have permission to access this report.'),
        $url
      );
    }

    $attributes = CRM_Core_DAO::getAttribute('CRM_Report_DAO_ReportInstance');

    $form->add('text',
      'title',
      ts('Report Title'),
      $attributes['title']
    );

    $form->add('text',
      'description',
      ts('Report Description'),
      $attributes['description']
    );

    $form->add('text',
      'email_subject',
      ts('Subject'),
      $attributes['email_subject']
    );

    $form->add('text',
      'email_to',
      ts('To'),
      $attributes['email_to']
    );

    $form->add('text',
      'email_cc',
      ts('CC'),
      $attributes['email_subject']
    );

    $form->add('number',
      'row_count',
      ts('Limit Dashboard Results'),
      array('class' => 'four', 'min' => 1)
    );

    $form->add('textarea',
      'report_header',
      ts('Report Header'),
      $attributes['header']
    );

    $form->add('textarea',
      'report_footer',
      ts('Report Footer'),
      $attributes['footer']
    );

    $form->addElement('checkbox', 'is_navigation', ts('Include Report in Navigation Menu?'), NULL,
      array('onclick' => "return showHideByValue('is_navigation','','navigation_menu','table-row','radio',false);")
    );

    $form->addElement('select', 'view_mode', ts('Configure link to...'), array(
      'view' => ts('View Results'),
      'criteria' => ts('Show Criteria'),
    ));

    $form->addElement('checkbox', 'addToDashboard', ts('Available for Dashboard?'));
    $form->add('number', 'cache_minutes', ts('Cache dashlet for'), array('class' => 'four', 'min' => 1));
    $form->addElement('checkbox', 'add_to_my_reports', ts('Add to My Reports?'), NULL);

    $form->addElement('checkbox', 'is_reserved', ts('Reserved Report?'));
    if (!CRM_Core_Permission::check('administer reserved reports')) {
      $form->freeze('is_reserved');
    }

    $config = CRM_Core_Config::singleton();
    if ($config->userFramework != 'Joomla' ||
      $config->userFramework != 'WordPress'
    ) {
      $form->addElement('select',
        'permission',
        ts('Permission'),
        array('0' => ts('Everyone (includes anonymous)')) + CRM_Core_Permission::basicPermissions()
      );

      // prepare user_roles to save as names not as ids
      if (function_exists('user_roles')) {
        $user_roles_array = user_roles();
        foreach ($user_roles_array as $key => $value) {
          $user_roles[$value] = $value;
        }
        $grouprole = &$form->addElement('advmultiselect',
          'grouprole',
          ts('ACL Group/Role'),
          $user_roles,
          array(
            'size' => 5,
            'style' => 'width:240px',
            'class' => 'advmultiselect',
          )
        );
        $grouprole->setButtonAttributes('add', array('value' => ts('Add >>')));
        $grouprole->setButtonAttributes('remove', array('value' => ts('<< Remove')));
      }
    }

    // navigation field
    $parentMenu = CRM_Core_BAO_Navigation::getNavigationList();

    $form->add('select', 'parent_id', ts('Parent Menu'), array('' => ts('- select -')) + $parentMenu);

    // For now we only providing drilldown for one primary detail report only. In future this could be multiple reports
    foreach ($form->_drilldownReport as $reportUrl => $drillLabel) {
      $instanceList = CRM_Report_Utils_Report::getInstanceList($reportUrl);
      if (count($instanceList) > 1) {
        $form->add('select', 'drilldown_id', $drillLabel, array('' => ts('- select -')) + $instanceList);
      }
      break;
    }

    $form->addButtons(array(
        array(
          'type' => 'submit',
          'name' => ts('Save Report'),
          'isDefault' => TRUE,
        ),
        array(
          'type' => 'cancel',
          'name' => ts('Cancel'),
        ),
      )
    );

    $form->addFormRule(array('CRM_Report_Form_Instance', 'formRule'), $form);
  }

  /**
   * Add form rules.
   *
   * @param array $fields
   * @param array $errors
   * @param CRM_Report_Form_Instance $self
   *
   * @return array|bool
   */
  public static function formRule($fields, $errors, $self) {
    // Validate both the "next" and "save" buttons for creating/updating a report.
    $nextButton = $self->controller->getButtonName();
    $saveButton = str_replace('_next', '_save', $nextButton);
    $clickedButton = $self->getVar('_instanceButtonName');

    $errors = array();
    if ($clickedButton == $nextButton || $clickedButton == $saveButton) {
      if (empty($fields['title'])) {
        $errors['title'] = ts('Title is a required field.');
        $self->assign('instanceFormError', TRUE);
      }
    }

    return empty($errors) ? TRUE : $errors;
  }

  /**
   * Set default values.
   *
   * @param CRM_Core_Form $form
   * @param array $defaults
   */
  public static function setDefaultValues(&$form, &$defaults) {
    // we should not build form elements in dashlet mode.
    if ($form->_section) {
      return;
    }

    $instanceID = $form->getVar('_id');
    $navigationDefaults = array();

    if (!isset($defaults['permission'])) {
      $defaults['permission'] = 'access CiviReport';
    }

    $userFrameworkResourceURL = CRM_Core_Config::singleton()->userFrameworkResourceURL;

    // Add a special region for the default HTML header of printed reports.  It
    // won't affect reports with customized headers, just ones with the default.
    $printHeaderRegion = CRM_Core_Region::instance('default-report-header', FALSE);
    $htmlHeader = ($printHeaderRegion) ? $printHeaderRegion->render('', FALSE) : '';

    $defaults['report_header'] = $report_header = "<html>
  <head>
    <title>CiviCRM Report</title>
    <meta http-equiv='Content-Type' content='text/html; charset=utf-8' />
    <style type=\"text/css\">@import url({$userFrameworkResourceURL}css/print.css);</style>
    {$htmlHeader}
  </head>
  <body><div id=\"crm-container\">";

    $defaults['report_footer'] = $report_footer = "<p><img src=\"{$userFrameworkResourceURL}i/powered_by.png\" /></p></div></body>
</html>
";

    // CRM-17225 view_mode currently supports 'view' or 'criteria'.
    // Prior to 4.7 'view' meant reset=1 in the url & if not set
    // then show criteria.
    // From 4.7 we will pro-actively set 'force=1' but still respect the old behaviour.
    // we may look to add pdf, print_view, csv & various charts as these could simply
    // be added to the url allowing us to conceptualise 'view right now' vs saved view
    // & using a multiselect (option value?) could help here.
    // Note that accessing reports without reset=1 in the url turns out to be
    // dangerous as it seems to carry actions like 'delete' from one report to another.
    $defaults['view_mode'] = 'view';
    $output = CRM_Utils_Request::retrieve('output', 'String');
    if ($output == 'criteria') {
      $defaults['view_mode'] = 'criteria';
    }

    if (empty($defaults['cache_minutes'])) {
      $defaults['cache_minutes'] = '60';
    }

    if ($instanceID) {
      // this is already retrieved via Form.php
      $defaults['description'] = CRM_Utils_Array::value('description', $defaults);
      if (!empty($defaults['header'])) {
        $defaults['report_header'] = $defaults['header'];
      }
      if (!empty($defaults['footer'])) {
        $defaults['report_footer'] = $defaults['footer'];
      }

      // CRM-17310 private reports option.
      $defaults['add_to_my_reports'] = 0;
      if (CRM_Utils_Array::value('owner_id', $defaults) != NULL) {
        $defaults['add_to_my_reports'] = 1;
      }

      if (!empty($defaults['navigation_id'])) {
        // Get the default navigation parent id.
        $params = array('id' => $defaults['navigation_id']);
        CRM_Core_BAO_Navigation::retrieve($params, $navigationDefaults);
        $defaults['is_navigation'] = 1;
        $defaults['parent_id'] = CRM_Utils_Array::value('parent_id', $navigationDefaults);
        if (!empty($navigationDefaults['is_active'])) {
          $form->assign('is_navigation', TRUE);
        }
        // A saved view mode will over-ride any url assumptions.
        if (strpos($navigationDefaults['url'], 'output=criteria')) {
          $defaults['view_mode'] = 'criteria';
        }

        if (!empty($navigationDefaults['id'])) {
          $form->_navigation['id'] = $navigationDefaults['id'];
          $form->_navigation['parent_id'] = !empty($navigationDefaults['parent_id']) ?
          $navigationDefaults['parent_id'] : NULL;
        }
      }

      if (!empty($defaults['grouprole'])) {
        foreach (explode(CRM_Core_DAO::VALUE_SEPARATOR, $defaults['grouprole']) as $value) {
          $groupRoles[] = $value;
        }
        $defaults['grouprole'] = $groupRoles;
      }
    }
    elseif (property_exists($form, '_description')) {
      $defaults['description'] = $form->_description;
    }
  }

  /**
   * Post process function.
   *
   * @param CRM_Core_Form $form
   * @param bool $redirect
   */
  public static function postProcess(&$form, $redirect = TRUE) {
    $params = $form->getVar('_params');
    $instanceID = $form->getVar('_id');

    if ($isNew = $form->getVar('_createNew')) {
      // set the report_id since base template is going to be same, and we going to unset $instanceID
      // which will make it difficult later on, to compute report_id
      $params['report_id'] = CRM_Report_Utils_Report::getValueFromUrl($instanceID);
      // Unset $instanceID so a new copy would be created.
      $instanceID = NULL;
    }
    $params['instance_id'] = $instanceID;
    if (!empty($params['is_navigation'])) {
      $params['navigation'] = $form->_navigation;
    }
    elseif ($instanceID) {
      // Delete navigation if exists.
      $navId = CRM_Core_DAO::getFieldValue('CRM_Report_DAO_ReportInstance', $instanceID, 'navigation_id', 'id');
      if ($navId) {
        CRM_Core_BAO_Navigation::processDelete($navId);
        CRM_Core_BAO_Navigation::resetNavigation();
      }
    }

    // make a copy of params
    $formValues = $params;

    // unset params from $formValues that doesn't match with DB columns of instance tables, and also not required in form-values for sure
    $unsetFields = array(
      'title',
      'to_emails',
      'cc_emails',
      'header',
      'footer',
      'qfKey',
      'id',
      '_qf_default',
      'report_header',
      'report_footer',
      'grouprole',
      'task',
    );
    foreach ($unsetFields as $field) {
      unset($formValues[$field]);
    }
    $view_mode = $formValues['view_mode'];

    // CRM-17310 my reports functionality - we should set owner if the checkbox is 1,
    // it seems to be not set at all if unchecked.
    if (!empty($formValues['add_to_my_reports'])) {
      $params['owner_id'] = CRM_Core_Session::getLoggedInContactID();
    }
    else {
      $params['owner_id'] = 'null';
    }
    unset($formValues['add_to_my_reports']);

    // pass form_values as string
    $params['form_values'] = serialize($formValues);

    $instance = CRM_Report_BAO_ReportInstance::create($params);
    $form->set('id', $instance->id);

    if ($instanceID && !$isNew) {
      // updating existing instance
      $statusMsg = ts('"%1" report has been updated.', array(1 => $instance->title));
    }
    elseif ($form->getVar('_id') && $isNew) {
      $statusMsg = ts('Your report has been successfully copied as "%1". You are currently viewing the new copy.', array(1 => $instance->title));
    }
    else {
      $statusMsg = ts('"%1" report has been successfully created. You are currently viewing the new report instance.', array(1 => $instance->title));
    }
    CRM_Core_Session::setStatus($statusMsg);

    if ($redirect) {
      $urlParams = array('reset' => 1);
      if ($view_mode == 'view') {
        $urlParams['force'] = 1;
      }
      else {
        $urlParams['output'] = 'criteria';
      }
      CRM_Utils_System::redirect(CRM_Utils_System::url("civicrm/report/instance/{$instance->id}", $urlParams));
    }
  }

}
