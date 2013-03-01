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
class CRM_Core_I18n_Form extends CRM_Core_Form {
  function buildQuickForm() {
    $config = CRM_Core_Config::singleton();
    $this->_locales = array_keys($config->languageLimit);

    // get the part of the database we want to edit and validate it
    $table            = CRM_Utils_Request::retrieve('table', 'String', $this);
    $field            = CRM_Utils_Request::retrieve('field', 'String', $this);
    $id               = CRM_Utils_Request::retrieve('id', 'Int', $this);
    $this->_structure = CRM_Core_I18n_SchemaStructure::columns();
    if (!isset($this->_structure[$table][$field])) {
      CRM_Core_Error::fatal("$table.$field is not internationalized.");
    }

    $this->addElement('hidden', 'table', $table);
    $this->addElement('hidden', 'field', $field);
    $this->addElement('hidden', 'id', $id);

    $cols = array();
    foreach ($this->_locales as $locale) {
      $cols[] = "{$field}_{$locale} {$locale}";
    }
    $query = 'SELECT ' . implode(', ', $cols) . " FROM $table WHERE id = $id";

    $dao = new CRM_Core_DAO();
    $dao->query($query, FALSE);
    $dao->fetch();

    // we want TEXTAREAs for long fields and INPUTs for short ones
    $this->_structure[$table][$field] == 'text' ? $type = 'textarea' : $type = 'text';

    $languages = CRM_Core_I18n::languages(TRUE);
    foreach ($this->_locales as $locale) {
      $this->addElement($type, "{$field}_{$locale}", $languages[$locale], array('cols' => 60, 'rows' => 3));
      $this->_defaults["{$field}_{$locale}"] = $dao->$locale;
    }

    $this->addButtons(array(array('type' => 'next', 'name' => ts('Save'), 'isDefault' => TRUE)));

    global $tsLocale;
    $this->assign('tsLocale', $tsLocale);
    $this->assign('locales', $this->_locales);
    $this->assign('field', $field);
    $this->assign('context', CRM_Utils_Request::retrieve('context', 'String', $this));
  }

  function setDefaultValues() {
    return $this->_defaults;
  }

  function postProcess() {
    $values = $this->exportValues();
    $table  = $values['table'];
    $field  = $values['field'];

    // validate table and field
    if (!isset($this->_structure[$table][$field])) {
      CRM_Core_Error::fatal("$table.$field is not internationalized.");
    }

    $cols   = array();
    $params = array(array($values['id'], 'Int'));
    $i      = 1;
    foreach ($this->_locales as $locale) {
      $cols[] = "{$field}_{$locale} = %$i";
      $params[$i] = array($values["{$field}_{$locale}"], 'String');
      $i++;
    }
    $query = "UPDATE $table SET " . implode(', ', $cols) . " WHERE id = %0";
    $dao   = new CRM_Core_DAO();
    $query = CRM_Core_DAO::composeQuery($query, $params, TRUE);
    $dao->query($query, FALSE);

    CRM_Utils_System::civiExit();
  }
}

