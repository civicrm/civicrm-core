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
 * Advanced search, extends basic search.
 */
class CRM_Contact_Form_Search_Advanced extends CRM_Contact_Form_Search {

  /**
   * @var string
   * @internal
   */
  public $_searchPane;

  /**
   * @var array
   * @internal
   */
  public $_searchOptions = [];

  /**
   * @var array
   * @internal
   */
  public $_paneTemplatePath = [];

  /**
   * Processing needed for buildForm and later.
   */
  public function preProcess() {
    // SearchFormName is deprecated & to be removed - the replacement is for the task to
    // call $this->form->getSearchFormValues()
    // A couple of extensions use it.
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

    $this->_searchPane = $_GET['searchPane'] ?? NULL;

    $this->_searchOptions = CRM_Core_BAO_Setting::valueOptions(CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
      'advanced_search_options'
    );

    if (!$this->_searchPane || $this->_searchPane == 'basic') {
      CRM_Contact_Form_Search_Criteria::basic($this);
    }

    $allPanes = [];
    $paneNames = [
      ts('Address Fields') => 'location',
      ts('Custom Fields') => 'custom',
      ts('Activities') => 'activity',
      ts('Relationships') => 'relationship',
      ts('Demographics') => 'demographics',
      ts('Notes') => 'notes',
      ts('Change Log') => 'changeLog',
    ];

    //check if there are any custom data searchable fields
    $groupDetails = CRM_Core_BAO_CustomGroup::getAll(['extends' => 'Contact', 'is_active' => TRUE]);
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

    $componentPanes = [];
    foreach ($components as $name => $component) {
      if (array_key_exists($name, $this->_searchOptions) &&
        $this->_searchOptions[$name] &&
        CRM_Core_Permission::access($component->name)
      ) {
        $componentPanes[$name] = $component->registerAdvancedSearchPane();
        $componentPanes[$name]['name'] = $name;
      }
    }

    usort($componentPanes, ['CRM_Utils_Sort', 'cmpFunc']);
    foreach ($componentPanes as $name => $pane) {
      // FIXME: we should change the use of $name here to keyword
      $paneNames[$pane['title']] = $pane['name'];
    }

    $hookPanes = [];
    CRM_Contact_BAO_Query_Hook::singleton()->registerAdvancedSearchPane($hookPanes);
    $paneNames = array_merge($paneNames, $hookPanes);

    $this->_paneTemplatePath = [];
    foreach ($paneNames as $name => $type) {
      if (!array_key_exists($type, $this->_searchOptions) && !in_array($type, $hookPanes)) {
        continue;
      }

      $allPanes[$name] = [
        'url' => CRM_Utils_System::url('civicrm/contact/search/advanced',
          "snippet=1&searchPane=$type&qfKey={$this->controller->_key}"
        ),
        'open' => 'false',
        'id' => $type,
      ];

      // see if we need to include this paneName in the current form
      if ($this->_searchPane == $type || !empty($_POST["hidden_{$type}"]) ||
        !empty($this->_formValues["hidden_{$type}"])
      ) {
        $allPanes[$name]['open'] = 'true';

        if (!empty($components[$type])) {
          $c = $components[$type];
          $this->add('hidden', "hidden_$type", 1);
          $c->buildAdvancedSearchPaneForm($this);
          $this->_paneTemplatePath[$type] = $c->getAdvancedSearchPaneTemplatePath();
        }
        elseif (in_array($type, $hookPanes)) {
          $this->add('hidden', "hidden_$type", 1);
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
   * @return array
   *   the default array reference
   * @throws \Exception
   */
  public function setDefaultValues() {
    $defaults = parent::setDefaultValues();
    // Set ssID for unit tests.
    if (empty($this->_ssID)) {
      $this->_ssID = $this->get('ssID');
    }

    $defaults = array_merge((array) $this->_formValues, [
      'privacy_toggle' => 1,
      'operator' => 'AND',
    ], $defaults);
    $defaults = $this->normalizeDefaultValues($defaults);

    //991/Subtypes not respected when editing smart group criteria
    if (!empty($defaults['contact_type']) && !empty($this->_formValues['contact_sub_type'])) {
      foreach ($this->_formValues['contact_sub_type'] as $subtype) {
        $basicType = CRM_Contact_BAO_ContactType::getBasicType($subtype);
        $defaults['contact_type'][$subtype] = $basicType . '__' . $subtype;
      }
    }

    if ($this->_context === 'amtg') {
      $defaults['task'] = CRM_Contact_Task::GROUP_ADD;
    }
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

    $this->setFormValues();
    if (is_numeric($_POST['id'] ?? NULL)) {
      $this->_formValues['contact_id'] = (int) $_POST['id'];
    }
    $this->set('formValues', $this->_formValues);
    // get user submitted values
    // get it from controller only if form has been submitted, else preProcess has set this
    if (!empty($_POST)) {
      $this->normalizeFormValues();
      // FIXME: couldn't figure out a good place to do this,
      // FIXME: so leaving this as a dependency for now
      if (array_key_exists('contribution_amount_low', $this->_formValues)) {
        foreach (['contribution_amount_low', 'contribution_amount_high'] as $f) {
          $this->_formValues[$f] = CRM_Utils_Rule::cleanMoney($this->_formValues[$f]);
        }
      }

      $this->set('uf_group_id', $this->_formValues['uf_group_id'] ?? '');
    }

    // retrieve ssID values only if formValues is null, i.e. form has never been posted
    if (empty($this->_formValues) && isset($this->_ssID)) {
      $this->_formValues = CRM_Contact_BAO_SavedSearch::getFormValues($this->_ssID);
    }

    if (isset($this->_groupID) && empty($this->_formValues['group'])) {
      $this->_formValues['group'] = [$this->_groupID => 1];
    }

    //search for civicase
    if (is_array($this->_formValues)) {
      $allCases = FALSE;
      if (array_key_exists('case_owner', $this->_formValues) &&
        !$this->_formValues['case_owner'] &&
        !$this->_force
      ) {
        foreach (['case_type_id', 'case_status_id', 'case_deleted', 'case_tags'] as $caseCriteria) {
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
    $contactType = $this->_formValues['contact_type'] ?? NULL;

    if ($contactType && is_array($contactType)) {
      unset($this->_formValues['contact_type']);
      foreach ($contactType as $key => $value) {
        $this->_formValues['contact_type'][$value] = 1;
      }
    }

    $config = CRM_Core_Config::singleton();
    $specialParams = [
      'financial_type_id',
      'contribution_soft_credit_type_id',
      'contribution_status',
      'contribution_status_id',
      'membership_status_id',
      'participant_status_id',
      'contribution_trxn_id',
      'activity_type_id',
      'priority_id',
      'contribution_product_id',
      'payment_instrument_id',
      'group',
      'contact_tags',
      'preferred_communication_method',
    ];
    $changeNames = [
      'priority_id' => 'activity_priority_id',
    ];
    CRM_Contact_BAO_Query::processSpecialFormValue($this->_formValues, $specialParams, $changeNames);

    $taglist = $this->_formValues['contact_taglist'] ?? NULL;

    if ($taglist && is_array($taglist)) {
      unset($this->_formValues['contact_taglist']);
      foreach ($taglist as $value) {
        if ($value) {
          $value = !is_array($value) ? explode(',', $value) : $value;
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
  public function normalizeDefaultValues($defaults) {
    $this->loadDefaultCountryBasedOnState($defaults);
    if ($this->_ssID && empty($_POST)) {
      $defaults = array_merge($defaults, CRM_Contact_BAO_SavedSearch::getFormValues($this->_ssID));
    }

    /*
     * CRM-18656 - reverse the normalisation of 'contact_taglist' done in
     * self::normalizeFormValues(). Remove tagset tags from the default
     * 'contact_tags' and put them in 'contact_taglist[N]' where N is the
     * id of the tagset.
     */
    if (isset($defaults['contact_tags'])) {
      foreach ((array) $defaults['contact_tags'] as $key => $tagId) {
        if (!is_array($tagId)) {
          $parentId = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_Tag', $tagId, 'parent_id');
          $element = "contact_taglist[$parentId]";
          if ($this->elementExists($element)) {
            // This tag is a tagset
            unset($defaults['contact_tags'][$key]);
            if (!isset($defaults[$element])) {
              $defaults[$element] = [];
            }
            $defaults[$element][] = $tagId;
          }
        }
      }
      if (empty($defaults['contact_tags'])) {
        unset($defaults['contact_tags']);
      }
    }

    return $defaults;
  }

  /**
   * Set the default country for the form.
   *
   * For performance reasons country might be removed from the form CRM-18125
   * but we need to include it in our defaults or the state will not be visible.
   *
   * @param array $defaults
   */
  public function loadDefaultCountryBasedOnState(&$defaults) {
    if (!empty($defaults['state_province'])) {
      $defaults['country'] = CRM_Core_DAO::singleValueQuery(
        "SELECT country_id FROM civicrm_state_province
         WHERE id = %1",
        [1 => [$defaults['state_province'][0], 'Integer']]
      );
    }
  }

}
