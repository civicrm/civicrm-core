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
 * This class generates form components for processing a survey.
 */
class CRM_Campaign_Form_Survey_Questions extends CRM_Campaign_Form_Survey {

  /**
   * Set default values for the form.
   *
   * Note that in edit/view mode the default values are retrieved from the database.
   *
   * @return array
   *   array of default values
   */
  public function setDefaultValues() {
    $defaults = [];

    $ufJoinParams = [
      'entity_table' => 'civicrm_survey',
      'module' => 'CiviCampaign',
      'entity_id' => $this->_surveyId,
    ];

    list($defaults['contact_profile_id'], $second)
      = CRM_Core_BAO_UFJoin::getUFGroupIds($ufJoinParams);
    $defaults['activity_profile_id'] = $second ? array_shift($second) : '';

    return $defaults;
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    $subTypeId = CRM_Core_DAO::getFieldValue('CRM_Campaign_DAO_Survey', $this->_surveyId, 'activity_type_id');
    if (!CRM_Core_BAO_CustomGroup::autoCreateByActivityType($subTypeId)) {
      // everything
      $activityTypes = CRM_Core_PseudoConstant::activityType(TRUE, TRUE, FALSE, 'label', TRUE, FALSE);
      // FIXME: Displays weird "/\ Array" message; doesn't work with tabs
      CRM_Core_Session::setStatus(
        ts(
          'There are no custom data sets for activity type "%1". To create one, <a href="%2" target="%3">click here</a>.',
          [
            1 => $activityTypes[$subTypeId],
            2 => CRM_Utils_System::url('civicrm/admin/custom/group', 'action=add&reset=1'),
            3 => '_blank',
          ]
        )
      );
    }

    $allowCoreTypes = CRM_Campaign_BAO_Survey::surveyProfileTypes();
    $allowSubTypes = [
      'ActivityType' => [$subTypeId],
    ];
    $entities = [
      ['entity_name' => 'contact_1', 'entity_type' => 'IndividualModel'],
      ['entity_name' => 'activity_1', 'entity_type' => 'ActivityModel', 'entity_sub_type' => $subTypeId],
    ];
    $this->addProfileSelector('contact_profile_id', ts('Contact Info'), $allowCoreTypes, $allowSubTypes, $entities);
    $this->addProfileSelector('activity_profile_id', ts('Questions'), $allowCoreTypes, $allowSubTypes, $entities);
    // Note: Because this is in a tab, we also preload the schema via CRM_Campaign_Form_Survey::preProcess

    parent::buildQuickForm();
  }

  /**
   * Process the form.
   */
  public function postProcess() {
    // store the submitted values in an array
    $params = $this->controller->exportValues($this->_name);

    // also update the ProfileModule tables
    $ufJoinParams = [
      'is_active' => 1,
      'module' => 'CiviCampaign',
      'entity_table' => 'civicrm_survey',
      'entity_id' => $this->_surveyId,
    ];

    // first delete all past entries
    CRM_Core_BAO_UFJoin::deleteAll($ufJoinParams);

    $uf = [];
    $wt = 2;
    if (!empty($params['contact_profile_id'])) {
      $uf[1] = $params['contact_profile_id'];
      $wt = 1;
    }
    if (!empty($params['activity_profile_id'])) {
      $uf[2] = $params['activity_profile_id'];
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

    parent::endPostProcess();
  }

}
