<?php

require_once 'CiviTest/CiviUnitTestCase.php';
require_once 'CiviTest/Contact.php';
require_once 'CiviTest/Custom.php';
class CRM_Core_BAO_CustomValueTableMultipleTest extends CiviUnitTestCase {
  function get_info() {
    return array(
      'name' => 'Custom Value Table BAOs (multipe value)',
      'description' => 'Test all Core_BAO_CustomValueTable methods. (for multiple values)',
      'group' => 'CiviCRM BAO Tests',
    );
  }

  function setUp() {
    parent::setUp();
  }

  function testCustomGroupMultipleSingle() {
    $params      = array();
    $contactID   = Contact::createIndividual();
    $customGroup = Custom::createGroup($params, 'Individual', TRUE);
    $fields      = array(
      'groupId' => $customGroup->id,
      'dataType' => 'String',
      'htmlType' => 'Text',
    );
    $customField = Custom::createField($params, $fields);

    $params = array(
      'entityID' => $contactID,
      "custom_{$customField->id}_-1" => 'First String',
    );
    $error = CRM_Core_BAO_CustomValueTable::setValues($params);

    $newParams = array(
      'entityID' => $contactID,
      "custom_{$customField->id}" => 1,
    );
    $result = CRM_Core_BAO_CustomValueTable::getValues($newParams);

    $this->assertEquals($params["custom_{$customField->id}_-1"], $result["custom_{$customField->id}_1"]);
    $this->assertEquals($params['entityID'], $result['entityID']);

    Custom::deleteField($customField);
    Custom::deleteGroup($customGroup);
    Contact::delete($contactID);
  }

  function testCustomGroupMultipleDouble() {
    $params      = array();
    $contactID   = Contact::createIndividual();
    $customGroup = Custom::createGroup($params, 'Individual', TRUE);
    $fields      = array(
      'groupId' => $customGroup->id,
      'dataType' => 'String',
      'htmlType' => 'Text',
    );
    $customField = Custom::createField($params, $fields);

    $params = array(
      'entityID' => $contactID,
      "custom_{$customField->id}_-1" => 'First String',
      "custom_{$customField->id}_-2" => 'Second String',
    );
    $error = CRM_Core_BAO_CustomValueTable::setValues($params);

    $newParams = array(
      'entityID' => $contactID,
      "custom_{$customField->id}" => 1,
    );
    $result = CRM_Core_BAO_CustomValueTable::getValues($newParams);

    $this->assertEquals($params["custom_{$customField->id}_-1"], $result["custom_{$customField->id}_1"]);
    $this->assertEquals($params["custom_{$customField->id}_-2"], $result["custom_{$customField->id}_2"]);
    $this->assertEquals($params['entityID'], $result['entityID']);

    Custom::deleteField($customField);
    Custom::deleteGroup($customGroup);
    Contact::delete($contactID);
  }

  function testCustomGroupMultipleUpdate() {
    $params      = array();
    $contactID   = Contact::createIndividual();
    $customGroup = Custom::createGroup($params, 'Individual', TRUE);
    $fields      = array(
      'groupId' => $customGroup->id,
      'dataType' => 'String',
      'htmlType' => 'Text',
    );
    $customField = Custom::createField($params, $fields);

    $params = array(
      'entityID' => $contactID,
      "custom_{$customField->id}_-1" => 'First String',
      "custom_{$customField->id}_-2" => 'Second String',
      "custom_{$customField->id}_-3" => 'Third String',
    );
    $error = CRM_Core_BAO_CustomValueTable::setValues($params);

    $newParams = array(
      'entityID' => $contactID,
      "custom_{$customField->id}_1" => 'Updated First String',
      "custom_{$customField->id}_3" => 'Updated Third String',
    );
    $result = CRM_Core_BAO_CustomValueTable::setValues($newParams);

    $getParams = array(
      'entityID' => $contactID,
      "custom_{$customField->id}" => 1,
    );
    $result = CRM_Core_BAO_CustomValueTable::getValues($getParams);

    $this->assertEquals($newParams["custom_{$customField->id}_1"], $result["custom_{$customField->id}_1"]);
    $this->assertEquals($params["custom_{$customField->id}_-2"], $result["custom_{$customField->id}_2"]);
    $this->assertEquals($newParams["custom_{$customField->id}_3"], $result["custom_{$customField->id}_3"]);
    $this->assertEquals($params['entityID'], $result['entityID']);

    Custom::deleteField($customField);
    Custom::deleteGroup($customGroup);
    Contact::delete($contactID);
  }

  function testCustomGroupMultipleOldFormate() {
    $params      = array();
    $contactID   = Contact::createIndividual();
    $customGroup = Custom::createGroup($params, 'Individual', TRUE);
    $fields      = array(
      'groupId' => $customGroup->id,
      'dataType' => 'String',
      'htmlType' => 'Text',
    );
    $customField = Custom::createField($params, $fields);

    $params = array(
      'entityID' => $contactID,
      "custom_{$customField->id}" => 'First String',
    );
    $error = CRM_Core_BAO_CustomValueTable::setValues($params);

    $newParams = array(
      'entityID' => $contactID,
      "custom_{$customField->id}" => 1,
    );
    $result = CRM_Core_BAO_CustomValueTable::getValues($newParams);

    $this->assertEquals($params["custom_{$customField->id}"], $result["custom_{$customField->id}_1"]);
    $this->assertEquals($params['entityID'], $result['entityID']);

    Custom::deleteField($customField);
    Custom::deleteGroup($customGroup);
    Contact::delete($contactID);
  }
}

