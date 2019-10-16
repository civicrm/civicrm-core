<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
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
