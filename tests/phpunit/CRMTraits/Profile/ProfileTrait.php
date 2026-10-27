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

}
