<?php

/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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


require_once 'CiviTest/CiviUnitTestCase.php';

/**
 * Test class for UFGroup API - civicrm_uf_*
 * @todo Split UFGroup and UFJoin tests
 *
 * @package   CiviCRM
 */
class api_v3_UFFieldTest extends CiviUnitTestCase {
  // ids from the uf_group_test.xml fixture
  protected $_ufGroupId = 11;
  protected $_ufFieldId;
  protected $_contactId = 69;
  protected $_apiversion;
  protected $_params;
  protected $_entity = 'uf_field';
  public $_eNoticeCompliant = TRUE;

  protected function setUp() {
    parent::setUp();
    $this->quickCleanup(
      array(
        'civicrm_group',
        'civicrm_contact',
        'civicrm_uf_group',
        'civicrm_uf_field',
        'civicrm_uf_join',
        'civicrm_uf_match',
      )
    );

    $this->_apiversion = 3;
    $op = new PHPUnit_Extensions_Database_Operation_Insert;
    $op->execute(
      $this->_dbconn,
      new PHPUnit_Extensions_Database_DataSet_FlatXMLDataSet(dirname(__FILE__) . '/dataset/uf_group_test.xml')
    );
    $this->_sethtmlGlobals();

    civicrm_api('uf_field', 'getfields', array('version' => 3, 'cache_clear' => 1));

    $this->_params = array(
      'field_name' => 'phone',
      'field_type' => 'Contact',
      'visibility' => 'Public Pages and Listings',
      'weight' => 1,
      'label' => 'Test Phone',
      'is_searchable' => 1,
      'is_active' => 1,
      'location_type_id' => 1,
      'phone_type_id' => 1,
      'version' => $this->_apiversion,
      'uf_group_id' => $this->_ufGroupId,
    );
  }

  function tearDown() {
    $this->quickCleanup(
      array(
        'civicrm_group',
        'civicrm_contact',
        'civicrm_uf_group',
        'civicrm_uf_join',
        'civicrm_uf_match',
      )
    );
  }

  /**
   * create / updating field
   */
  public function testCreateUFField() {
    $params = $this->_params; // copy
    $ufField = civicrm_api('uf_field', 'create', $params);
    $this->documentMe($params, $ufField, __FUNCTION__, __FILE__);
    unset($params['version']);
    unset($params['uf_group_id']);
    $this->assertAPISuccess($ufField, " in line " . __LINE__);
    $this->_ufFieldId = $ufField['id'];
    foreach ($params as $key => $value) {
      $this->assertEquals($ufField['values'][$ufField['id']][$key], $params[$key]);
    }
  }

  public function testCreateUFFieldWithBadFieldName() {
    $params = $this->_params; // copy
    $params['field_name'] = 'custom_98789'; // invalid field
    $this->callAPIFailure('uf_field', 'create', $params);
  }

  function testCreateUFFieldWithWrongParams() {
    $this->callAPIFailure('uf_field', 'create', array('field_name' => 'test field'));
    $this->callAPIFailure('uf_field', 'create', array('label' => 'name-less field'));
  }
  /**
   * Create a field with 'weight=1' and then a second with 'weight=1'. The second field
   * winds up with weight=1, and the first field gets bumped to 'weight=2'.
   */
  public function testCreateUFFieldWithDefaultAutoWeight() {
    $params1 = $this->_params; // copy
    $ufField1 = $this->callAPISuccess('uf_field', 'create', $params1);
    $this->assertEquals(1, $ufField1['values'][$ufField1['id']]['weight']);
    $this->assertDBQuery(1, 'SELECT weight FROM civicrm_uf_field WHERE id = %1', array(
      1 => array($ufField1['id'], 'Int'),
    ));

    $params2 = $this->_params; // copy
    $params2['location_type_id'] = 2; // needs to be a different field
    $ufField2 = $this->callAPISuccess('uf_field', 'create', $params2);
    $this->assertEquals(1, $ufField2['values'][$ufField2['id']]['weight']);
    $this->assertDBQuery(1, 'SELECT weight FROM civicrm_uf_field WHERE id = %1', array(
      1 => array($ufField2['id'], 'Int'),
    ));
    $this->assertDBQuery(2, 'SELECT weight FROM civicrm_uf_field WHERE id = %1', array(
      1 => array($ufField1['id'], 'Int'),
    ));
  }

  /**
   * deleting field
   */
  public function testDeleteUFField() {
    $ufField = $this->callAPISuccess('uf_field', 'create', $this->_params);
    $params = array(
      'field_id' => $ufField['id'],
    );
    $result = $this->callAPIAndDocument('uf_field', 'delete', $params, __FUNCTION__, __FILE__);
  }

  public function testGetUFFieldSuccess() {
    $this->callAPISuccess($this->_entity, 'create', $this->_params);
    $result = $this->callAPIAndDocument($this->_entity, 'get', array(), __FUNCTION__, __FILE__);
    $this->getAndCheck($this->_params, $result['id'], $this->_entity);
  }

  /**
   * create / updating field
   */
  public function testReplaceUFFields() {
    $baseFields = array();
    $baseFields[] = array(
      'field_name' => 'first_name',
      'field_type' => 'Contact',
      'visibility' => 'Public Pages and Listings',
      'weight' => 3,
      'label' => 'Test First Name',
      'is_searchable' => 1,
      'is_active' => 1,
    );
    $baseFields[] = array(
      'field_name' => 'country',
      'field_type' => 'Contact',
      'visibility' => 'Public Pages and Listings',
      'weight' => 2,
      'label' => 'Test Country',
      'is_searchable' => 1,
      'is_active' => 1,
      'location_type_id' => 1,
    );
    $baseFields[] = array(
      'field_name' => 'phone',
      'field_type' => 'Contact',
      'visibility' => 'Public Pages and Listings',
      'weight' => 1,
      'label' => 'Test Phone',
      'is_searchable' => 1,
      'is_active' => 1,
      'location_type_id' => 1,
      'phone_type_id' => 1,
    );

    $params = array(
      'version' => $this->_apiversion,
      'uf_group_id' => $this->_ufGroupId,
      'option.autoweight' => FALSE,
      'values' => $baseFields,
    );

    $result = civicrm_api('uf_field', 'replace', $params);
    $this->assertAPISuccess($result);
    $this->documentMe($params, $result, __FUNCTION__, __FILE__);
    $inputsByName = CRM_Utils_Array::index(array('field_name'), $params['values']);
    $this->assertEquals(count($params['values']), count($result['values']));
    foreach ($result['values'] as $outUfField) {
      $this->assertTrue(is_string($outUfField['field_name']));
      $inUfField = $inputsByName[$outUfField['field_name']];
      foreach ($inUfField as $key => $inValue) {
        $this->assertEquals($inValue, $outUfField[$key],
          sprintf("field_name=[%s] key=[%s] expected=[%s] actual=[%s]",
            $outUfField['field_name'],
            $key,
            $inValue,
            $outUfField[$key]
          )
        );
      }
    }
  }

  /**
   * FIXME: something NULLs $GLOBALS['_HTML_QuickForm_registered_rules'] when the tests are ran all together
   * (NB unclear if this is still required)
   */
  function _sethtmlGlobals() {
    $GLOBALS['_HTML_QuickForm_registered_rules'] = array(
      'required' => array(
        'html_quickform_rule_required',
        'HTML/QuickForm/Rule/Required.php'
      ),
      'maxlength' => array(
        'html_quickform_rule_range',
        'HTML/QuickForm/Rule/Range.php'
      ),
      'minlength' => array(
        'html_quickform_rule_range',
        'HTML/QuickForm/Rule/Range.php'
      ),
      'rangelength' => array(
        'html_quickform_rule_range',
        'HTML/QuickForm/Rule/Range.php'
      ),
      'email' => array(
        'html_quickform_rule_email',
        'HTML/QuickForm/Rule/Email.php'
      ),
      'regex' => array(
        'html_quickform_rule_regex',
        'HTML/QuickForm/Rule/Regex.php'
      ),
      'lettersonly' => array(
        'html_quickform_rule_regex',
        'HTML/QuickForm/Rule/Regex.php'
      ),
      'alphanumeric' => array(
        'html_quickform_rule_regex',
        'HTML/QuickForm/Rule/Regex.php'
      ),
      'numeric' => array(
        'html_quickform_rule_regex',
        'HTML/QuickForm/Rule/Regex.php'
      ),
      'nopunctuation' => array(
        'html_quickform_rule_regex',
        'HTML/QuickForm/Rule/Regex.php'
      ),
      'nonzero' => array(
        'html_quickform_rule_regex',
        'HTML/QuickForm/Rule/Regex.php'
      ),
      'callback' => array(
        'html_quickform_rule_callback',
        'HTML/QuickForm/Rule/Callback.php'
      ),
      'compare' => array(
        'html_quickform_rule_compare',
        'HTML/QuickForm/Rule/Compare.php'
      )
    );
    // FIXME: …ditto for $GLOBALS['HTML_QUICKFORM_ELEMENT_TYPES']
    $GLOBALS['HTML_QUICKFORM_ELEMENT_TYPES'] = array(
      'group' => array(
        'HTML/QuickForm/group.php',
        'HTML_QuickForm_group'
      ),
      'hidden' => array(
        'HTML/QuickForm/hidden.php',
        'HTML_QuickForm_hidden'
      ),
      'reset' => array(
        'HTML/QuickForm/reset.php',
        'HTML_QuickForm_reset'
      ),
      'checkbox' => array(
        'HTML/QuickForm/checkbox.php',
        'HTML_QuickForm_checkbox'
      ),
      'file' => array(
        'HTML/QuickForm/file.php',
        'HTML_QuickForm_file'
      ),
      'image' => array(
        'HTML/QuickForm/image.php',
        'HTML_QuickForm_image'
      ),
      'password' => array(
        'HTML/QuickForm/password.php',
        'HTML_QuickForm_password'
      ),
      'radio' => array(
        'HTML/QuickForm/radio.php',
        'HTML_QuickForm_radio'
      ),
      'button' => array(
        'HTML/QuickForm/button.php',
        'HTML_QuickForm_button'
      ),
      'submit' => array(
        'HTML/QuickForm/submit.php',
        'HTML_QuickForm_submit'
      ),
      'select' => array(
        'HTML/QuickForm/select.php',
        'HTML_QuickForm_select'
      ),
      'hiddenselect' => array(
        'HTML/QuickForm/hiddenselect.php',
        'HTML_QuickForm_hiddenselect'
      ),
      'text' => array(
        'HTML/QuickForm/text.php',
        'HTML_QuickForm_text'
      ),
      'textarea' => array(
        'HTML/QuickForm/textarea.php',
        'HTML_QuickForm_textarea'
      ),
      'fckeditor' => array(
        'HTML/QuickForm/fckeditor.php',
        'HTML_QuickForm_FCKEditor'
      ),
      'tinymce' => array(
        'HTML/QuickForm/tinymce.php',
        'HTML_QuickForm_TinyMCE'
      ),
      'dojoeditor' => array(
        'HTML/QuickForm/dojoeditor.php',
        'HTML_QuickForm_dojoeditor'
      ),
      'link' => array(
        'HTML/QuickForm/link.php',
        'HTML_QuickForm_link'
      ),
      'advcheckbox' => array(
        'HTML/QuickForm/advcheckbox.php',
        'HTML_QuickForm_advcheckbox'
      ),
      'date' => array(
        'HTML/QuickForm/date.php',
        'HTML_QuickForm_date'
      ),
      'static' => array(
        'HTML/QuickForm/static.php',
        'HTML_QuickForm_static'
      ),
      'header' => array(
        'HTML/QuickForm/header.php',
        'HTML_QuickForm_header'
      ),
      'html' => array(
        'HTML/QuickForm/html.php',
        'HTML_QuickForm_html'
      ),
      'hierselect' => array(
        'HTML/QuickForm/hierselect.php',
        'HTML_QuickForm_hierselect'
      ),
      'autocomplete' => array(
        'HTML/QuickForm/autocomplete.php',
        'HTML_QuickForm_autocomplete'
      ),
      'xbutton' => array(
        'HTML/QuickForm/xbutton.php',
        'HTML_QuickForm_xbutton'
      ),
      'advmultiselect' => array(
        'HTML/QuickForm/advmultiselect.php',
        'HTML_QuickForm_advmultiselect'
      )
    );
  }
}
