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
 * $Id$
 *
 */
class CRM_Core_I18n_Form extends CRM_Core_Form {
  public function buildQuickForm() {
    $config = CRM_Core_Config::singleton();
    global $tsLocale;
    $this->_locales = array_keys($config->languageLimit);

    // get the part of the database we want to edit and validate it
    $table = CRM_Utils_Request::retrieve('table', 'String', $this);
    $field = CRM_Utils_Request::retrieve('field', 'String', $this);
    $id = CRM_Utils_Request::retrieve('id', 'Int', $this);
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

    // get html type and attributes for this field
    $widgets = CRM_Core_I18n_SchemaStructure::widgets();
    $widget = $widgets[$table][$field];

    // attributes
    $attributes = array('class' => '');
    if (isset($widget['rows'])) {
      $attributes['rows'] = $widget['rows'];
    }
    if (isset($widget['cols'])) {
      $attributes['cols'] = $widget['cols'];
    }
    $required = !empty($widget['required']);

    if ($widget['type'] == 'RichTextEditor') {
      $widget['type'] = 'wysiwyg';
      $attributes['class'] .= ' collapsed';
    }

    $languages = CRM_Core_I18n::languages(TRUE);
    foreach ($this->_locales as $locale) {
      $attr = $attributes;
      $name = "{$field}_{$locale}";
      if ($locale == $tsLocale) {
        $attr['class'] .= ' default-lang';
      }
      $this->add($widget['type'], $name, $languages[$locale], $attr, $required);

      $this->_defaults[$name] = $dao->$locale;
    }

    $this->addDefaultButtons(ts('Save'), 'next', NULL);

    CRM_Utils_System::setTitle(ts('Languages'));

    $this->assign('locales', $this->_locales);
    $this->assign('field', $field);
  }

  /**
   * This virtual function is used to set the default values of
   * various form elements
   *
   * access        public
   *
   * @return array
   *   reference to the array of default values
   */
  public function setDefaultValues() {
    return $this->_defaults;
  }

  public function postProcess() {
    $values = $this->exportValues();
    $table = $values['table'];
    $field = $values['field'];

    // validate table and field
    if (!isset($this->_structure[$table][$field])) {
      CRM_Core_Error::fatal("$table.$field is not internationalized.");
    }

    $cols = array();
    $params = array(array($values['id'], 'Int'));
    $i = 1;
    foreach ($this->_locales as $locale) {
      $cols[] = "{$field}_{$locale} = %$i";
      $params[$i] = array($values["{$field}_{$locale}"], 'String');
      $i++;
    }
    $query = "UPDATE $table SET " . implode(', ', $cols) . " WHERE id = %0";
    $dao = new CRM_Core_DAO();
    $query = CRM_Core_DAO::composeQuery($query, $params, TRUE);
    $dao->query($query, FALSE);
  }

}
