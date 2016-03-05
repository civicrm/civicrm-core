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
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2015
 */

/**
 * Advanced search, extends basic search.
 */
class CRM_Contact_Form_Search_Advanced extends CRM_Contact_Form_Search {

  /**
   * Processing needed for buildForm and later.
   */
  public function preProcess() {
    $this->set('searchFormName', 'Advanced');

    parent::preProcess();
    $openedPanes = CRM_Contact_BAO_Query::$_openedPanes;
    $openedPanes = array_merge($openedPanes, $this->_openedPanes);
    $this->assign('openedPanes', $openedPanes);
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    $this->set('context', 'advanced');

    $this->_searchPane = CRM_Utils_Array::value('searchPane', $_GET);

    $this->_searchOptions = CRM_Core_BAO_Setting::valueOptions(CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
      'advanced_search_options'
    );

    if (!$this->_searchPane || $this->_searchPane == 'basic') {
      CRM_Contact_Form_Search_Criteria::basic($this);
    }

    $allPanes = array();
    $paneNames = array(
      ts('Address Fields') => 'location',
      ts('Custom Fields') => 'custom',
      ts('Activities') => 'activity',
      ts('Relationships') => 'relationship',
      ts('Demographics') => 'demographics',
      ts('Notes') => 'notes',
      ts('Change Log') => 'changeLog',
    );

    //check if there are any custom data searchable fields
    $extends = array_merge(array('Contact', 'Individual', 'Household', 'Organization'),
      CRM_Contact_BAO_ContactType::subTypes()
    );
    $groupDetails = CRM_Core_BAO_CustomGroup::getGroupDetail(NULL, TRUE,
      $extends
    );
    // if no searchable fields unset panel
    if (empty($groupDetails)) {
      unset($paneNames[ts('Custom Fields')]);
    }

    foreach ($paneNames as $name => $type) {
      if (!$this->_searchOptions[$type]) {
        unset($paneNames[$name]);
      }
    }

    $components = CRM_Core_Component::getEnabledComponents();

    $componentPanes = array();
    foreach ($components as $name => $component) {
      if (in_array($name, array_keys($this->_searchOptions)) &&
        $this->_searchOptions[$name] &&
        CRM_Core_Permission::access($component->name)
      ) {
        $componentPanes[$name] = $component->registerAdvancedSearchPane();
        $componentPanes[$name]['name'] = $name;
      }
    }

    usort($componentPanes, array('CRM_Utils_Sort', 'cmpFunc'));
    foreach ($componentPanes as $name => $pane) {
      // FIXME: we should change the use of $name here to keyword
      $paneNames[$pane['title']] = $pane['name'];
    }

    $hookPanes = array();
    CRM_Contact_BAO_Query_Hook::singleton()->registerAdvancedSearchPane($hookPanes);
    $paneNames = array_merge($paneNames, $hookPanes);

    $this->_paneTemplatePath = array();
    foreach ($paneNames as $name => $type) {
      if (!array_key_exists($type, $this->_searchOptions) && !in_array($type, $hookPanes)) {
        continue;
      }

      $allPanes[$name] = array(
        'url' => CRM_Utils_System::url('civicrm/contact/search/advanced',
          "snippet=1&searchPane=$type&qfKey={$this->controller->_key}"
        ),
        'open' => 'false',
        'id' => $type,
      );

      // see if we need to include this paneName in the current form
      if ($this->_searchPane == $type || !empty($_POST["hidden_{$type}"]) ||
        CRM_Utils_Array::value("hidden_{$type}", $this->_formValues)
      ) {
        $allPanes[$name]['open'] = 'true';

        if (!empty($components[$type])) {
          $c = $components[$type];
          $this->add('hidden', "hidden_$type", 1);
          $c->buildAdvancedSearchPaneForm($this);
          $this->_paneTemplatePath[$type] = $c->getAdvancedSearchPaneTemplatePath();
        }
        elseif (in_array($type, $hookPanes)) {
          CRM_Contact_BAO_Query_Hook::singleton()->buildAdvancedSearchPaneForm($this, $type);
          CRM_Contact_BAO_Query_Hook::singleton()->setAdvancedSearchPaneTemplatePath($this->_paneTemplatePath, $type);
        }
        else {
          CRM_Contact_Form_Search_Criteria::$type($this);
          $template = ucfirst($type);
          $this->_paneTemplatePath[$type] = "CRM/Contact/Form/Search/Criteria/{$template}.tpl";
        }
      }
    }

    $this->assign('allPanes', $allPanes);
    if (!$this->_searchPane) {
      parent::buildQuickForm();
    }
    else {
      $this->assign('suppressForm', TRUE);
    }
  }

  /**
   * Use the form name to create the tpl file name.
   *
   * @return string
   */
  /**
   * @return string
   */
  public function getTemplateFileName() {
    if (!$this->_searchPane) {
      return parent::getTemplateFileName();
    }
    else {
      if (isset($this->_paneTemplatePath[$this->_searchPane])) {
        return $this->_paneTemplatePath[$this->_searchPane];
      }
      else {
        $name = ucfirst($this->_searchPane);
        return "CRM/Contact/Form/Search/Criteria/{$name}.tpl";
      }
    }
  }

  /**
   * Set the default form values.
   *
   *
   * @return array
   *   the default array reference
   */
  public function setDefaultValues() {
    $defaults = $this->_formValues;
    $this->normalizeDefaultValues($defaults);

    if ($this->_context === 'amtg') {
      $defaults['task'] = CRM_Contact_Task::GROUP_CONTACTS;
    }

    $defaults['privacy_toggle'] = 1;
    $defaults['operator'] = 'AND';

    return $defaults;
  }

  /**
   * The post processing of the form gets done here.
   *
   * Key things done during post processing are
   *      - check for reset or next request. if present, skip post processing.
   *      - now check if user requested running a saved search, if so, then
   *        the form values associated with the saved search are used for searching.
   *      - if user has done a submit with new values the regular post submitting is
   *        done.
   * The processing consists of using a Selector / Controller framework for getting the
   * search results.
   */
  public function postProcess() {
    $this->set('isAdvanced', '1');

    // get user submitted values
    // get it from controller only if form has been submitted, else preProcess has set this
    if (!empty($_POST)) {
      $this->_formValues = $this->controller->exportValues($this->_name);
      $this->normalizeFormValues();
      // FIXME: couldn't figure out a good place to do this,
      // FIXME: so leaving this as a dependency for now
      if (array_key_exists('contribution_amount_low', $this->_formValues)) {
        foreach (array(
                   'contribution_amount_low',
                   'contribution_amount_high',
                 ) as $f) {
          $this->_formValues[$f] = CRM_Utils_Rule::cleanMoney($this->_formValues[$f]);
        }
      }

      // set the group if group is submitted
      if (!empty($this->_formValues['uf_group_id'])) {
        $this->set('id', $this->_formValues['uf_group_id']);
      }
      else {
        $this->set('id', '');
      }
    }

    // retrieve ssID values only if formValues is null, i.e. form has never been posted
    if (empty($this->_formValues) && isset($this->_ssID)) {
      $this->_formValues = CRM_Contact_BAO_SavedSearch::getFormValues($this->_ssID);
    }

    if (isset($this->_groupID) && empty($this->_formValues['group'])) {
      $this->_formValues['group'] = array($this->_groupID => 1);
    }

    //search for civicase
    if (is_array($this->_formValues)) {
      $allCases = FALSE;
      if (array_key_exists('case_owner', $this->_formValues) &&
        !$this->_formValues['case_owner'] &&
        !$this->_force
      ) {
        foreach (array(
                   'case_type_id',
                   'case_status_id',
                   'case_deleted',
                   'case_tags',
                 ) as $caseCriteria) {
          if (!empty($this->_formValues[$caseCriteria])) {
            $allCases = TRUE;
            $this->_formValues['case_owner'] = 1;
            continue;
          }
        }
        if ($allCases) {
          if (CRM_Core_Permission::check('access all cases and activities')) {
            $this->_formValues['case_owner'] = 1;
          }
          else {
            $this->_formValues['case_owner'] = 2;
          }
        }
        else {
          $this->_formValues['case_owner'] = 0;
        }
      }
      if (array_key_exists('case_owner', $this->_formValues) && empty($this->_formValues['case_deleted'])) {
        $this->_formValues['case_deleted'] = 0;
      }
    }

    // we dont want to store the sortByCharacter in the formValue, it is more like
    // a filter on the result set
    // this filter is reset if we click on the search button
    if ($this->_sortByCharacter !== NULL && empty($_POST)) {
      if (strtolower($this->_sortByCharacter) == 'all') {
        $this->_formValues['sortByCharacter'] = NULL;
      }
      else {
        $this->_formValues['sortByCharacter'] = $this->_sortByCharacter;
      }
    }
    else {
      $this->_sortByCharacter = NULL;
    }

    CRM_Core_BAO_CustomValue::fixCustomFieldValue($this->_formValues);

    $this->_params = CRM_Contact_BAO_Query::convertFormValues($this->_formValues, 0, FALSE, NULL, $this->entityReferenceFields);
    $this->_returnProperties = &$this->returnProperties();
    parent::postProcess();
  }

  /**
   * Normalize the form values to make it look similar to the advanced form values.
   *
   * This prevents a ton of work downstream and allows us to use the same code for
   * multiple purposes (queries, save/edit etc)
   */
  public function normalizeFormValues() {
    $contactType = CRM_Utils_Array::value('contact_type', $this->_formValues);

    if ($contactType && is_array($contactType)) {
      unset($this->_formValues['contact_type']);
      foreach ($contactType as $key => $value) {
        $this->_formValues['contact_type'][$value] = 1;
      }
    }

    $config = CRM_Core_Config::singleton();
    $specialParams = array(
      'financial_type_id',
      'contribution_soft_credit_type_id',
      'contribution_status',
      'contribution_status_id',
      'contribution_source',
      'membership_status_id',
      'participant_status_id',
      'contribution_trxn_id',
      'activity_type_id',
      'status_id',
      'activity_subject',
      'contribution_page_id',
      'contribution_product_id',
      'payment_instrument_id',
      'group',
      'contact_tags',
      'preferred_communication_method',
    );
    $changeNames = array('status_id' => 'activity_status_id');
    CRM_Contact_BAO_Query::processSpecialFormValue($this->_formValues, $specialParams, $changeNames);

    $taglist = CRM_Utils_Array::value('contact_taglist', $this->_formValues);

    if ($taglist && is_array($taglist)) {
      unset($this->_formValues['contact_taglist']);
      foreach ($taglist as $value) {
        if ($value) {
          $value = explode(',', $value);
          foreach ($value as $tId) {
            if (is_numeric($tId)) {
              $this->_formValues['contact_tags'][] = $tId;
            }
          }
        }
      }
    }
  }

  /**
   * Normalize default values for multiselect plugins.
   *
   * @param array $defaults
   *
   * @return array
   */
  public function normalizeDefaultValues(&$defaults) {
    if (!is_array($defaults)) {
      $defaults = array();
    }

    if ($this->_ssID && empty($_POST)) {
      $defaults = array_merge($defaults, CRM_Contact_BAO_SavedSearch::getFormValues($this->_ssID));
    }
    return $defaults;
  }

}
