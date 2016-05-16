<?php

/**
 * Class PCPBlock
 */
class PCPBlock extends PHPUnit_Framework_Testcase {
  /**
   * Helper function to create a PCP Block for Contribution Page
   *
   * @param  int $contributionPageId - id of the Contribution Page
   * to be deleted
   * @return array of created pcp block
   *
   */
  function create($contributionPageId) {
    $profileParams = array(
      'group_type' => 'Individual,Contact',
      'title' => 'Test Supprorter Profile',
      'help_pre' => 'Profle to PCP Contribution',
      'is_active' => 1,
      'is_cms_user' => 2,
    );

    $ufGroup = civicrm_api('uf_group', 'create', $profileParams);
    $profileId = $ufGroup['id'];

    $fieldsParams = array(
      array(
        'field_name' => 'first_name',
        'field_type' => 'Individual',
        'visibility' => 'Public Pages and Listings',
        'weight' => 1,
        'label' => 'First Name',
        'is_required' => 1,
        'is_active' => 1,
      ),
      array(
        'field_name' => 'last_name',
        'field_type' => 'Individual',
        'visibility' => 'Public Pages and Listings',
        'weight' => 2,
        'label' => 'Last Name',
        'is_required' => 1,
        'is_active' => 1,
      ),
      array(
        'field_name' => 'email',
        'field_type' => 'Contact',
        'visibility' => 'Public Pages and Listings',
        'weight' => 3,
        'label' => 'Email',
        'is_required' => 1,
        'is_active' => 1,
      ),
    );

    foreach ($fieldsParams as $value) {
      // we assume api v3.
      $value['version'] = 3;
      $value['uf_group_id'] = $profileId;
      $ufField = civicrm_api('uf_field', 'create', $value);
    }

    $joinParams = array(
      'module' => 'Profile',
      'entity_table' => 'civicrm_contribution_page',
      'entity_id' => 1,
      'weight' => 1,
      'uf_group_id' => $profileId,
      'is_active' => 1,
    );
    $ufJoin = civicrm_api('uf_join', 'create', $joinParams);

    $params = array(
      'entity_table' => 'civicrm_contribution_page',
      'entity_id' => $contributionPageId,
      'supporter_profile_id' => $profileId,
      'is_approval_needed' => 0,
      'is_tellfriend_enabled' => 0,
      'tellfriend_limit' => 0,
      'link_text' => 'Create your own Personal Campaign Page!',
      'is_active' => 1,
      'notify_email' => 'info@civicrm.org',
    );
    require_once 'CRM/Contribute/BAO/PCP.php';
    $blockPCP = CRM_Contribute_BAO_PCP::add($params);
    return array('blockId' => $blockPCP->id, 'profileId' => $profileId);
  }

  /**
   * Helper function to delete a PCP related stuff viz. Profile, PCP Block Entry
   *
   * @param  array key value pair
   * pcpBlockId - id of the PCP Block Id, profileID - id of Supporter Profile
   * to be deleted
   * @return boolean true if success, false otherwise
   *
   */
  function delete($params) {

    $delete_params = array('id' => $params['profileId']);
    $resulProfile = civicrm_api('uf_group', 'delete', $delete_params);


    require_once 'CRM/Contribute/DAO/PCPBlock.php';
    $dao = new CRM_Contribute_DAO_PCPBlock();
    $dao->id = $params['blockId'];
    if ($dao->find(TRUE)) {
      $resultBlock = $dao->delete();
    }
    if ($id = CRM_Utils_Array::value('pcpId', $params)) {
      require_once 'CRM/Contribute/BAO/PCP.php';
      CRM_Contribute_BAO_PCP::delete($id);
    }
    if ($resulProfile && $resultBlock) {
      return TRUE;
    }
    return FALSE;
  }
}
