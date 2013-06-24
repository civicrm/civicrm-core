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

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id$
 *
 */

/**
 * This class generates form components for Component
 */
class CRM_Admin_Form_Setting_Component extends CRM_Admin_Form_Setting {
  protected $_components;

  /**
   * Function to build the form
   *
   * @return None
   * @access public
   */
  public function buildQuickForm() {
    CRM_Utils_System::setTitle(ts('Settings - Enable Components'));
    $components = $this->_getComponentSelectValues();
    $include = &$this->addElement('advmultiselect', 'enableComponents',
      ts('Components') . ' ', $components,
      array(
        'size' => 5,
        'style' => 'width:150px',
        'class' => 'advmultiselect',
      )
    );

    $include->setButtonAttributes('add', array('value' => ts('Enable >>')));
    $include->setButtonAttributes('remove', array('value' => ts('<< Disable')));

    $this->addFormRule(array('CRM_Admin_Form_Setting_Component', 'formRule'), $this);

    parent::buildQuickForm();
  }

  /**
   * global form rule
   *
   * @param array $fields  the input form values
   * @param array $files   the uploaded files if any
   * @param array $options additional user data
   *
   * @return true if no errors, else array of errors
   * @access public
   * @static
   */
  static function formRule($fields, $files, $options) {
    $errors = array();

    if (array_key_exists('enableComponents', $fields) && is_array($fields['enableComponents'])) {
      if (in_array('CiviPledge', $fields['enableComponents']) &&
        !in_array('CiviContribute', $fields['enableComponents'])
      ) {
        $errors['enableComponents'] = ts('You need to enable CiviContribute before enabling CiviPledge.');
      }
      if (in_array('CiviCase', $fields['enableComponents']) &&
        !CRM_Core_DAO::checkTriggerViewPermission(TRUE, FALSE)
      ) {
        $errors['enableComponents'] = ts('CiviCase requires CREATE VIEW and DROP VIEW permissions for the database.');
      }
    }

    return $errors;
  }

  private function _getComponentSelectValues() {
    $ret = array();
    $this->_components = CRM_Core_Component::getComponents();
    foreach ($this->_components as $name => $object) {
      $ret[$name] = $object->info['translatedName'];
    }

    return $ret;
  }

  public function postProcess() {
    $params = $this->controller->exportValues($this->_name);

    $params['enableComponentIDs'] = array();
    foreach ($params['enableComponents'] as $name) {
      $params['enableComponentIDs'][] = $this->_components[$name]->componentID;
    }

    // if CiviCase is being enabled,
    // load the case related sample data
    if (in_array('CiviCase', $params['enableComponents']) &&
      !in_array('CiviCase', $this->_defaults['enableComponents'])
    ) {
      $config = CRM_Core_Config::singleton();
      CRM_Admin_Form_Setting_Component::loadCaseSampleData($config->dsn, $config->sqlDir . 'case_sample.mysql');
      CRM_Admin_Form_Setting_Component::loadCaseSampleData($config->dsn, $config->sqlDir . 'case_sample1.mysql');
      if (!CRM_Case_BAO_Case::createCaseViews()) {
        $msg = ts("Could not create the MySQL views for CiviCase. Your mysql user needs to have the 'CREATE VIEW' permission");
        CRM_Core_Error::fatal($msg);
      }
    }
    parent::commonProcess($params);

    // reset navigation when components are enabled / disabled
    CRM_Core_BAO_Navigation::resetNavigation();
  }

  public function loadCaseSampleData($dsn, $fileName, $lineMode = FALSE) {
    global $crmPath;

    $db = &DB::connect($dsn);
    if (PEAR::isError($db)) {
      die("Cannot open $dsn: " . $db->getMessage());
    }

    if (!$lineMode) {
      $string = file_get_contents($fileName);

      // change \r\n to fix windows issues
      $string = str_replace("\r\n", "\n", $string);

      //get rid of comments starting with # and --

      $string = preg_replace("/^#[^\n]*$/m", "\n", $string);
      $string = preg_replace("/^(--[^-]).*/m", "\n", $string);

      $queries = preg_split('/;$/m', $string);
      foreach ($queries as $query) {
        $query = trim($query);
        if (!empty($query)) {
          $res = &$db->query($query);
          if (PEAR::isError($res)) {
            die("Cannot execute $query: " . $res->getMessage());
          }
        }
      }
    }
    else {
      $fd = fopen($fileName, "r");
      while ($string = fgets($fd)) {
        $string = preg_replace("/^#[^\n]*$/m", "\n", $string);
        $string = preg_replace("/^(--[^-]).*/m", "\n", $string);

        $string = trim($string);
        if (!empty($string)) {
          $res = &$db->query($string);
          if (PEAR::isError($res)) {
            die("Cannot execute $string: " . $res->getMessage());
          }
        }
      }
    }
  }
}

