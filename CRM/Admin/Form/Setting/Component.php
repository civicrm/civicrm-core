<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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
 * @copyright CiviCRM LLC (c) 2004-2015
 */

/**
 * This class generates form components for Component.
 */
class CRM_Admin_Form_Setting_Component extends CRM_Admin_Form_Setting {
  protected $_components;

  /**
   * Build the form object.
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
   * Global form rule.
   *
   * @param array $fields
   *   The input form values.
   * @param array $files
   *   The uploaded files if any.
   * @param array $options
   *   Additional user data.
   *
   * @return bool|array
   *   true if no errors, else array of errors
   */
  public static function formRule($fields, $files, $options) {
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

  /**
   * @return array
   */
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

    parent::commonProcess($params);

    // reset navigation when components are enabled / disabled
    CRM_Core_BAO_Navigation::resetNavigation();
  }

  /**
   * @param $dsn
   * @param string $fileName
   * @param bool $lineMode
   */
  public static function loadCaseSampleData($fileName, $lineMode = FALSE) {
    $dao = new CRM_Core_DAO();
    $db = $dao->getDatabaseConnection();

    $domain = new CRM_Core_DAO_Domain();
    $domain->find(TRUE);
    $multiLingual = (bool) $domain->locales;
    $smarty = CRM_Core_Smarty::singleton();
    $smarty->assign('multilingual', $multiLingual);
    $smarty->assign('locales', explode(CRM_Core_DAO::VALUE_SEPARATOR, $domain->locales));

    if (!$lineMode) {

      $string = $smarty->fetch($fileName);
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
