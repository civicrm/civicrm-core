<?php

require_once 'CiviTest/CiviUnitTestCase.php';

class CRM_Profile_FormTest extends CiviUnitTestCase {
  function testDoesNotAddToGroupFromPost() {
    $contact_id = $this->individualCreate();
    $params = array(
      'domain_id' => 1,
      'description' => 'New Test Group Created',
      'is_active' => 1,
      'visibility' => 'Public Pages',
      'group_type' => array( 
        '1' => 1,
        '2' => 1,
      ),
      'version' => API_LATEST_VERSION,
    );
    $params['name'] = "Test Group" . time();
    $params['title'] = "Test Group" . time();
    $correct_group_id = $this->groupCreate($params);
    $params['name'] = "Test Group" . time() + 1;
    $params['title'] = "Test Group" . time() + 1;
    $bad_group_id = $this->groupCreate($params);
    $profile = new CRM_Core_BAO_UFGroup();
    $profile->title = "Test illegal group add";
    $profile->is_active = TRUE;
    $profile->group_type = 'Individual,Contact';
    $profile->add_to_group_id = $correct_group_id;
    $profile->save();
    $uf_join = new CRM_Core_BAO_UFJoin();
    $uf_join->is_active = 1;
    $uf_join->module = 'Profile';
    $uf_join->uf_group_id = $profile->id;
    $uf_join->weight = 9;
    $uf_join->save();
    $uf_field = new CRM_Core_BAO_UFField();
    $uf_field->uf_group_id =$profile->id;
    $uf_field->field_name = 'first_name';
    $uf_field->label = 'First Name';
    $uf_field->is_active = TRUE;
    $uf_field->field_type = 'Individual';
    $uf_field->save();
    $_POST['add_to_group'] = $_REQUEST['add_to_group'] = $bad_group_id;
    $_POST['_qf_Edit_next'] = $_REQUEST['_qf_Edit_next'] = 'Save';
    $_POST['first_name'] = $_REQUEST['first_name'] = 'Foo';
    $_POST['last_name'] = $_REQUEST['last_name'] = 'Bar';
    $args = array('civicrm', 'profile', 'edit');
    $session = CRM_Core_Session::singleton();
    $session->set('gid', $profile->id, 'CRM_Profile_Form_Edit_');
    $session->set('id', $contact_id, 'CRM_Profile_Form_Edit_');
    try {
      CRM_Core_Invoke::profile($args);
    } catch (CRM_Utils_System_UnitTests_CiviExitException $e) {
    }
    $group_contact = new CRM_Contact_BAO_GroupContact();
    $group_contact->group_id = $bad_group_id;
    $group_contact->contact_id = $contact_id;
    $group_contact->status = 'Added';
    $group_contact->find(TRUE);
    $this->assertNULL($group_contact->id);
  }
}
