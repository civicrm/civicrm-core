<?php

/**
 *  Include dataProvider for tests
 */
class CRM_Member_BAO_QueryTest extends CiviUnitTestCase {

  /**
   * Set up function.
   *
   * Ensure CiviCase is enabled.
   */
  public function setUp() {
    parent::setUp();
    CRM_Core_BAO_ConfigSetting::enableComponent('CiviCase');
  }

  /**
   * Check that membership type is handled.
   *
   * We want to see the following syntaxes for membership_type_id field handled:
   *   1) membership_type_id => 1
   */
  public function testConvertEntityFieldSingleValue() {
    $formValues = array('membership_type_id' => 2);
    $params = CRM_Contact_BAO_Query::convertFormValues($formValues, 0, FALSE, NULL, array('membership_type_id'));
    $this->assertEquals(array('membership_type_id', '=', 2, 0, 0), $params[0]);
    $obj = new CRM_Contact_BAO_Query($params);
    $this->assertEquals(array('civicrm_membership.membership_type_id = 2'), $obj->_where[0]);
  }

  /**
   * Check that membership type is handled.
   *
   * We want to see the following syntaxes for membership_type_id field handled:
   *   2) membership_type_id => 5,6
   *
   * The last of these is the format used prior to converting membership_type_id to an entity reference field.
   */
  public function testConvertEntityFieldMultipleValueEntityRef() {
    $formValues = array('membership_type_id' => '1,2');
    $params = CRM_Contact_BAO_Query::convertFormValues($formValues, 0, FALSE, NULL, array('membership_type_id'));
    $this->assertEquals(array('membership_type_id', 'IN', array(1, 2), 0, 0), $params[0]);
    $obj = new CRM_Contact_BAO_Query($params);
    $this->assertEquals(array('civicrm_membership.membership_type_id IN ("1", "2")'), $obj->_where[0]);
  }

  /**
   * Check that membership type is handled.
   *
   * We want to see the following syntaxes for membership_type_id field handled:
   *   3) membership_type_id => array(5,6)
   *
   * The last of these is the format used prior to converting membership_type_id to an entity reference field. It will
   * be used by pre-existing smart groups.
   */
  public function testConvertEntityFieldMultipleValueLegacy() {
    $formValues = array('membership_type_id' => array(1, 2));
    $params = CRM_Contact_BAO_Query::convertFormValues($formValues, 0, FALSE, NULL, array('membership_type_id'));
    $this->assertEquals(array('membership_type_id', 'IN', array(1, 2), 0, 0), $params[0]);
    $obj = new CRM_Contact_BAO_Query($params);
    $this->assertEquals(array('civicrm_membership.membership_type_id IN ("1", "2")'), $obj->_where[0]);
  }

  /**
   * Check that running convertFormValues more than one doesn't mangle the array.
   *
   * Unfortunately the convertFormValues & indeed much of the query code is run in pre-process AND post-process.
   *
   * The convertFormValues function should cope with this until such time as we can rationalise that.
   */
  public function testConvertEntityFieldMultipleValueEntityRefDoubleRun() {
    $formValues = array('membership_type_id' => '1,2');
    $params = CRM_Contact_BAO_Query::convertFormValues($formValues, 0, FALSE, NULL, array('membership_type_id'));
    $this->assertEquals(array('membership_type_id', 'IN', array(1, 2), 0, 0), $params[0]);
    $params = CRM_Contact_BAO_Query::convertFormValues($params, 0, FALSE, NULL, array('membership_type_id'));
    $this->assertEquals(array('membership_type_id', 'IN', array(1, 2), 0, 0), $params[0]);
    $obj = new CRM_Contact_BAO_Query($params);
    $this->assertEquals(array('civicrm_membership.membership_type_id IN ("1", "2")'), $obj->_where[0]);
  }

}
