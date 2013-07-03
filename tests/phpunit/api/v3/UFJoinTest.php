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
 *  @package   CiviCRM
 */
class api_v3_UFJoinTest extends CiviUnitTestCase {
  // ids from the uf_group_test.xml fixture
  protected $_ufGroupId = 11;
  protected $_ufFieldId;
  protected $_contactId = 69;
  protected $_apiversion;
  public $_eNoticeCompliant = TRUE;
  protected function setUp() {
    parent::setUp();
    //  Truncate the tables
    $this->quickCleanup(
      array(
        'civicrm_group',
        'civicrm_contact',
        'civicrm_uf_group',
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

    // FIXME: something NULLs $GLOBALS['_HTML_QuickForm_registered_rules'] when the tests are ran all together
    $GLOBALS['_HTML_QuickForm_registered_rules'] = array(
      'required' => array('html_quickform_rule_required', 'HTML/QuickForm/Rule/Required.php'),
      'maxlength' => array('html_quickform_rule_range', 'HTML/QuickForm/Rule/Range.php'),
      'minlength' => array('html_quickform_rule_range', 'HTML/QuickForm/Rule/Range.php'),
      'rangelength' => array('html_quickform_rule_range', 'HTML/QuickForm/Rule/Range.php'),
      'email' => array('html_quickform_rule_email', 'HTML/QuickForm/Rule/Email.php'),
      'regex' => array('html_quickform_rule_regex', 'HTML/QuickForm/Rule/Regex.php'),
      'lettersonly' => array('html_quickform_rule_regex', 'HTML/QuickForm/Rule/Regex.php'),
      'alphanumeric' => array('html_quickform_rule_regex', 'HTML/QuickForm/Rule/Regex.php'),
      'numeric' => array('html_quickform_rule_regex', 'HTML/QuickForm/Rule/Regex.php'),
      'nopunctuation' => array('html_quickform_rule_regex', 'HTML/QuickForm/Rule/Regex.php'),
      'nonzero' => array('html_quickform_rule_regex', 'HTML/QuickForm/Rule/Regex.php'),
      'callback' => array('html_quickform_rule_callback', 'HTML/QuickForm/Rule/Callback.php'),
      'compare' => array('html_quickform_rule_compare', 'HTML/QuickForm/Rule/Compare.php'),
    );
    // FIXME: …ditto for $GLOBALS['HTML_QUICKFORM_ELEMENT_TYPES']
    $GLOBALS['HTML_QUICKFORM_ELEMENT_TYPES'] = array(
      'group' => array('HTML/QuickForm/group.php', 'HTML_QuickForm_group'),
      'hidden' => array('HTML/QuickForm/hidden.php', 'HTML_QuickForm_hidden'),
      'reset' => array('HTML/QuickForm/reset.php', 'HTML_QuickForm_reset'),
      'checkbox' => array('HTML/QuickForm/checkbox.php', 'HTML_QuickForm_checkbox'),
      'file' => array('HTML/QuickForm/file.php', 'HTML_QuickForm_file'),
      'image' => array('HTML/QuickForm/image.php', 'HTML_QuickForm_image'),
      'password' => array('HTML/QuickForm/password.php', 'HTML_QuickForm_password'),
      'radio' => array('HTML/QuickForm/radio.php', 'HTML_QuickForm_radio'),
      'button' => array('HTML/QuickForm/button.php', 'HTML_QuickForm_button'),
      'submit' => array('HTML/QuickForm/submit.php', 'HTML_QuickForm_submit'),
      'select' => array('HTML/QuickForm/select.php', 'HTML_QuickForm_select'),
      'hiddenselect' => array('HTML/QuickForm/hiddenselect.php', 'HTML_QuickForm_hiddenselect'),
      'text' => array('HTML/QuickForm/text.php', 'HTML_QuickForm_text'),
      'textarea' => array('HTML/QuickForm/textarea.php', 'HTML_QuickForm_textarea'),
      'fckeditor' => array('HTML/QuickForm/fckeditor.php', 'HTML_QuickForm_FCKEditor'),
      'tinymce' => array('HTML/QuickForm/tinymce.php', 'HTML_QuickForm_TinyMCE'),
      'dojoeditor' => array('HTML/QuickForm/dojoeditor.php', 'HTML_QuickForm_dojoeditor'),
      'link' => array('HTML/QuickForm/link.php', 'HTML_QuickForm_link'),
      'advcheckbox' => array('HTML/QuickForm/advcheckbox.php', 'HTML_QuickForm_advcheckbox'),
      'date' => array('HTML/QuickForm/date.php', 'HTML_QuickForm_date'),
      'static' => array('HTML/QuickForm/static.php', 'HTML_QuickForm_static'),
      'header' => array('HTML/QuickForm/header.php', 'HTML_QuickForm_header'),
      'html' => array('HTML/QuickForm/html.php', 'HTML_QuickForm_html'),
      'hierselect' => array('HTML/QuickForm/hierselect.php', 'HTML_QuickForm_hierselect'),
      'autocomplete' => array('HTML/QuickForm/autocomplete.php', 'HTML_QuickForm_autocomplete'),
      'xbutton' => array('HTML/QuickForm/xbutton.php', 'HTML_QuickForm_xbutton'),
      'advmultiselect' => array('HTML/QuickForm/advmultiselect.php', 'HTML_QuickForm_advmultiselect'),
    );
  }

  function tearDown() {
    //  Truncate the tables
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
   * find uf join group id
   */
  public function testFindUFGroupId() {
    $params = array(
      'module' => 'CiviContribute',
      'entity_table' => 'civicrm_contribution_page',
      'entity_id' => 1,
      'weight' => 1,
      'uf_group_id' => $this->_ufGroupId,
      'is_active' => 1,
      'version' => $this->_apiversion,
    );
    $ufJoin = civicrm_api('uf_join', 'create', $params);

    $searchParams = array(
      'entity_table' => 'civicrm_contribution_page',
      'entity_id' => 1,
      'version' => $this->_apiversion,
    );
    $result = civicrm_api('uf_join', 'get', $searchParams);

    foreach ($result['values'] as $key => $value) {
      $this->assertEquals($value['uf_group_id'], $this->_ufGroupId, 'In line ' . __LINE__);
    }
  }


  public function testUFJoinEditWrongParamsType() {
    $params = 'a string';
    $result = $this->callAPIFailure('uf_join', 'create', $params);
    $this->assertEquals($result['error_message'], 'Input variable `params` is not an array', 'In line ' . __LINE__);
  }

  public function testUFJoinEditEmptyParams() {
    $params = array();
    $result = $this->callAPIFailure('uf_join', 'create', $params);
    $this->assertEquals($result['error_message'], 'Mandatory key(s) missing from params array: module, weight, uf_group_id', 'In line ' . __LINE__);
  }

  public function testUFJoinEditWithoutUFGroupId() {
    $params = array(
      'module' => 'CiviContribute',
      'entity_table' => 'civicrm_contribution_page',
      'entity_id' => 1,
      'weight' => 1,
      'is_active' => 1,
      'version' => $this->_apiversion,
    );
    $result = $this->callAPIFailure('uf_join', 'create', $params);
    $this->assertEquals($result['error_message'], 'Mandatory key(s) missing from params array: uf_group_id', 'In line ' . __LINE__);
  }

  /**
   * create/update uf join
   */
  public function testCreateUFJoin() {
    $params = array(
      'module' => 'CiviContribute',
      'entity_table' => 'civicrm_contribution_page',
      'entity_id' => 1,
      'weight' => 1,
      'uf_group_id' => $this->_ufGroupId,
      'is_active' => 1,
      'version' => $this->_apiversion,
      'sequential' => 1,
    );
    $ufJoin = civicrm_api('uf_join', 'create', $params);
    $this->documentMe($params, $ufJoin, __FUNCTION__, __FILE__);
    $this->assertEquals($ufJoin['values'][0]['module'], $params['module'], 'In line ' . __LINE__);
    $this->assertEquals($ufJoin['values'][0]['uf_group_id'], $params['uf_group_id'], 'In line ' . __LINE__);
    $this->assertEquals($ufJoin['values'][0]['is_active'], $params['is_active'], 'In line ' . __LINE__);

    $params = array(
      'id' => $ufJoin['id'],
      'module' => 'CiviContribute',
      'entity_table' => 'civicrm_contribution_page',
      'entity_id' => 1,
      'weight' => 1,
      'uf_group_id' => $this->_ufGroupId,
      'is_active' => 0,
      'version' => $this->_apiversion,
      'sequential' => 1,
    );
    $ufJoinUpdated = civicrm_api('uf_join', 'create', $params);
    $this->assertEquals($ufJoinUpdated['values'][0]['module'], $params['module'], 'In line ' . __LINE__);
    $this->assertEquals($ufJoinUpdated['values'][0]['uf_group_id'], $params['uf_group_id'], 'In line ' . __LINE__);
    $this->assertEquals($ufJoinUpdated['values'][0]['is_active'], $params['is_active'], 'In line ' . __LINE__);
  }


  public function testFindUFJoinWrongParamsType() {
    $params = 'a string';
    $result = $this->callAPIFailure('uf_join', 'create', $params);
    $this->assertEquals($result['error_message'], 'Input variable `params` is not an array', 'In line ' . __LINE__);
  }

  public function testFindUFJoinEmptyParams() {
    $result = $this->callAPIFailure('uf_join', 'create', array());
    $this->assertEquals($result['error_message'], 'Mandatory key(s) missing from params array: module, weight, uf_group_id', 'In line ' . __LINE__);
  }

  public function testFindUFJoinWithoutUFGroupId() {
    $params = array(
      'module' => 'CiviContribute',
      'entity_table' => 'civicrm_contribution_page',
      'entity_id' => 1,
      'weight' => 1,
      'is_active' => 1,
    );
    $result = $this->callAPIFailure('uf_join', 'create', $params);
    $this->assertEquals($result['error_message'], 'Mandatory key(s) missing from params array: uf_group_id', 'In line ' . __LINE__);
  }

  /**
   * find uf join id
   */
  public function testGetUFJoinId() {
    $params = array(
      'module' => 'CiviContribute',
      'entity_table' => 'civicrm_contribution_page',
      'entity_id' => 1,
      'weight' => 1,
      'uf_group_id' => $this->_ufGroupId,
      'is_active' => 1,
      'version' => $this->_apiversion,
    );

    $ufJoin = civicrm_api('uf_join', 'create', $params);
    $searchParams = array(
      'entity_table' => 'civicrm_contribution_page',
      'entity_id' => 1,
      'version' => $this->_apiversion,
      'sequential' => 1,
    );

    $result = civicrm_api('uf_join', 'get', $searchParams);
    $this->documentMe($searchParams, $result, __FUNCTION__, __FILE__);
    $this->assertEquals($result['values'][0]['module'], $params['module'], 'In line ' . __LINE__);
    $this->assertEquals($result['values'][0]['uf_group_id'], $params['uf_group_id'], 'In line ' . __LINE__);
    $this->assertEquals($result['values'][0]['entity_id'], $params['entity_id'], 'In line ' . __LINE__);
  }

  /**
   *  Test civicrm_activity_create() using example code
   */
  function testUFJoinCreateExample() {
    require_once 'api/v3/examples/UFJoinCreate.php';
    $result = UF_join_create_example();
    $expectedResult = UF_join_create_expectedresult();
    $this->assertEquals($result, $expectedResult);
  }
}

