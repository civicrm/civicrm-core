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
 * Trait PriceSetTrait
 *
 * Trait for working with Price Sets in tests
 */
trait CRMTraits_Profile_ProfileTrait {

  /**
   * Add a profile to a contribution page.
   *
   * @param array $joinParams
   *   Must contain entity_id at minimum.
   * @param array $ufGroupParams
   */
  protected function createJoinedProfile($joinParams, $ufGroupParams = []) {
    $profileID = $this->createProfile($ufGroupParams);
    $joinParams = array_merge([
      'uf_group_id' => $profileID,
      'entity_table' => 'civicrm_contribution_page',
      'weight' => 1,
    ], $joinParams);
    if (empty($joinParams['module'])) {
      $joinParams['module'] = $joinParams['entity_table'] === 'civicrm_event' ? 'CiviEvent' : 'CiviContribute';
    }
    if ($joinParams['module'] !== 'CiviContribute' && empty($joinParams['module_data'])) {
      $params['module_data'] = [$joinParams['module'] => []];
    }
    $this->callAPISuccess('UFJoin', 'create', $joinParams);
  }

  /**
   * Create a profile.
   *
   * @param $ufGroupParams
   *
   * @return int
   */
  protected function createProfile($ufGroupParams) {
    $profile = $this->callAPISuccess('UFGroup', 'create', array_merge([
      'group_type' => 'Contact',
      'title' => 'Back end title',
      'frontend_title' => 'Public title',
      'name' => 'our profile',

    ], $ufGroupParams));
    $this->ids['UFGroup'][$profile['values'][$profile['id']]['name']] = $profile['id'];

    $this->callAPISuccess('UFField', 'create', [
      'uf_group_id' => $profile['id'],
      'field_name' => 'first_name',
    ]);
    return $profile['id'];
  }

  /**
   * Ensure we don't have a profile with the id or one to ensure that we are not casting an array to it.
   */
  protected function eliminateUFGroupOne() {
    $profileID = $this->createProfile(['name' => 'dummy_for_removing']);
    CRM_Core_DAO::executeQuery("UPDATE civicrm_uf_join SET uf_group_id = $profileID WHERE uf_group_id = 1");
    CRM_Core_DAO::executeQuery("UPDATE civicrm_uf_field SET uf_group_id = $profileID WHERE uf_group_id = 1");
    CRM_Core_DAO::executeQuery('UPDATE civicrm_uf_group SET id = 900 WHERE id = 1');
    $this->ids['UFGroup']['dummy'] = $profileID;
  }

  /**
   * Bring back UF group one.
   */
  protected function restoreUFGroupOne() {
    if (!isset($this->ids['UFGroup']['dummy'])) {
      return;
    }
    $profileID = $this->ids['UFGroup']['dummy'];
    CRM_Core_DAO::executeQuery('UPDATE civicrm_uf_group SET id = 1 WHERE id = 900');
    CRM_Core_DAO::executeQuery("UPDATE civicrm_uf_join SET uf_group_id = 1 WHERE uf_group_id = $profileID");
    CRM_Core_DAO::executeQuery("UPDATE civicrm_uf_field SET uf_group_id = 1 WHERE uf_group_id = $profileID");
  }

}
