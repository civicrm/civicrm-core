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
class api_v3_UFGroupTest extends CiviUnitTestCase {
  // ids from the uf_group_test.xml fixture
  protected $_ufGroupId = 11;
  protected $_ufFieldId;
  protected $_contactId = 69;
  protected $_apiversion;
  protected $params;
  public $_eNoticeCompliant = TRUE;

  protected function setUp() {
    parent::setUp();
    $this->_apiversion = 3;
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
    $this->params = array(
      'add_captcha' => 1,
      'add_contact_to_group' => 2,
      'cancel_URL' => 'http://example.org/cancel',
      'created_date' => '2009-06-27 00:00:00',
      'created_id' => 69,
      'group' => 2,
      'group_type' => 'Individual,Contact',
      'help_post' => 'help post',
      'help_pre' => 'help pre',
      'is_active' => 0,
      'is_cms_user' => 1,
      'is_edit_link' => 1,
      'is_map' => 1,
      'is_reserved' => 1,
      'is_uf_link' => 1,
      'is_update_dupe' => 1,
      'name' => 'Test_Group',
      'notify' => 'admin@example.org',
      'post_URL' => 'http://example.org/post',
      'title' => 'Test Group',
      'version' => $this->_apiversion,
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
   * updating group
   */
  public function testUpdateUFGroup() {
    $params = array(
      'title' => 'Edited Test Profile',
      'help_post' => 'Profile Pro help text.',
      'is_active' => 1,
      'id' => $this->_ufGroupId,
      'version' => $this->_apiversion,
    );

    $result = civicrm_api('uf_group', 'create', $params);
    unset($params['version']);
    foreach ($params as $key => $value) {
      $this->assertEquals($result['values'][$result['id']][$key], $value);
    }
  }

  function testUFGroupCreate() {

    $result = civicrm_api('uf_group', 'create', $this->params);
    $this->documentMe($this->params, $result, __FUNCTION__, __FILE__);
    $this->assertAPISuccess($result);
    $this->assertEquals($result['values'][$result['id']]['add_to_group_id'], $this->params['add_contact_to_group'], 'in line ' . __LINE__);
    $this->assertEquals($result['values'][$result['id']]['limit_listings_group_id'], $this->params['group'], 'in line ' . __LINE__);
    $this->params['created_date'] = date('YmdHis', strtotime($this->params['created_date']));
    foreach ($this->params as $key => $value) {
      if ($key == 'add_contact_to_group' or $key == 'group' or $key == 'version') {
        continue;
      }
      $expected = $this->params[$key];
      $received = $result['values'][$result['id']][$key];
      // group names are renamed to name_id by BAO
      if ($key == 'name') {
        $expected = $this->params[$key] . '_' . $result['id'];
      }
      $this->assertEquals($expected, $received, "The string '$received' does not equal '$expected' for key '$key' in line " . __LINE__);
    }
  }

  function testUFGroupCreateWithWrongParams() {
    $result = civicrm_api('uf_group', 'create', 'a string');
    $this->assertAPIFailure($result);
    $result = civicrm_api('uf_group', 'create', array('name' => 'A title-less group'));
    $this->assertAPIFailure($result);
  }

  function testUFGroupUpdate() {
    $params = array(
      'id' => $this->_ufGroupId,
      'add_captcha' => 1,
      'add_contact_to_group' => 2,
      'cancel_URL' => 'http://example.org/cancel',
      'created_date' => '2009-06-27',
      'created_id' => 69,
      'group' => 2,
      'group_type' => 'Individual,Contact',
      'help_post' => 'help post',
      'help_pre' => 'help pre',
      'is_active' => 0,
      'is_cms_user' => 1,
      'is_edit_link' => 1,
      'is_map' => 1,
      'is_reserved' => 1,
      'is_uf_link' => 1,
      'is_update_dupe' => 1,
      'name' => 'test_group',
      'notify' => 'admin@example.org',
      'post_URL' => 'http://example.org/post',
      'title' => 'Test Group',
      'version' => $this->_apiversion,
    );
    $result = civicrm_api('uf_group', 'create', $params);
    unset($params['version']);
    $params['created_date'] = date('YmdHis', strtotime($params['created_date']));
    foreach ($params as $key => $value) {
      if ($key == 'add_contact_to_group' or $key == 'group') {
        continue;
      }
      $this->assertEquals($result['values'][$result['id']][$key], $params[$key], $key . " doesn't match  " . $value);
    }

    $this->assertEquals($result['values'][$this->_ufGroupId]['add_to_group_id'], $params['add_contact_to_group'], 'in line ' . __LINE__);
    $this->assertEquals($result['values'][$result['id']]['limit_listings_group_id'], $params['group'], 'in line ' . __LINE__);
  }

  function testUFGroupGet() {

    $result = civicrm_api('uf_group', 'create', $this->params);
    $this->assertEquals(0, $result['is_error'], 'in line ' . __LINE__);

    $params = array('version' => 3, 'id' => $result['id']);
    $result = civicrm_api('uf_group', 'get', $params);
    $this->assertEquals(0, $result['is_error'], 'in line ' . __LINE__);
    $this->documentMe($this->params, $result, __FUNCTION__, __FILE__);
    $this->assertEquals($result['values'][$result['id']]['add_to_group_id'], $this->params['add_contact_to_group'], 'in line ' . __LINE__);
    $this->assertEquals($result['values'][$result['id']]['limit_listings_group_id'], $this->params['group'], 'in line ' . __LINE__);
    foreach ($this->params as $key => $value) {
      // skip created date because it doesn't seem to be working properly & fixing date handling is for another day
      if ($key == 'add_contact_to_group' or $key == 'group' or $key == 'version' or $key == 'created_date') {
        continue;
      }
      $expected = $this->params[$key];
      $received = $result['values'][$result['id']][$key];
      // group names are renamed to name_id by BAO
      if ($key == 'name') {
        $expected = $this->params[$key] . '_' . $result['id'];
      }
      $this->assertEquals($expected, $received, "The string '$received' does not equal '$expected' for key '$key' in line " . __LINE__);
    }
  }

  function testUFGroupUpdateWithEmptyParams() {
    $result = civicrm_api('uf_group', 'create', array(), $this->_ufGroupId);
    $this->assertAPIFailure($result);
  }

  function testUFGroupUpdateWithWrongParams() {
    $result = civicrm_api('uf_group', 'create', 'a string', $this->_ufGroupId);
    $this->assertAPIFailure($result);
    $result = civicrm_api('uf_group', 'create', array('title' => 'Title'), 'a string');
    $this->assertAPIFailure($result);
  }

  function testUFGroupDelete() {

    $ufGroup = civicrm_api('uf_group', 'create', $this->params);
    $this->assertAPISuccess($ufGroup);
    $params = array('version' => $this->_apiversion, 'id' => $ufGroup['id']);
    $this->assertEquals(1, civicrm_api('uf_group', 'getcount', $params), "in line " . __LINE__);
    $result = civicrm_api('uf_group', 'delete', $params, "in line " . __LINE__);
    $this->documentMe($this->params, $result, __FUNCTION__, __FILE__);
    $this->assertEquals(0, civicrm_api('uf_group', 'getcount', $params), "in line " . __LINE__);
    $this->assertAPISuccess($result, "in line " . __LINE__);
  }
}

