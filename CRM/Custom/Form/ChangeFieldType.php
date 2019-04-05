<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
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
 * @copyright CiviCRM LLC (c) 2004-2019
 * $Id$
 *
 */

/**
 * This class is to build the form for Deleting Group
 */
class CRM_Custom_Form_ChangeFieldType extends CRM_Core_Form {

  /**
   * The field id
   *
   * @var int
   */
  protected $_id;

  /**
   * Array of custom field values
   */
  protected $_values;

  /**
   * Mapper array of valid field type
   */
  protected $_htmlTypeTransitions;

  /**
   * Set up variables to build the form.
   *
   * @return void
   * @access protected
   */
  public function preProcess() {
    $this->_id = CRM_Utils_Request::retrieve('id', 'Positive',
      $this, TRUE
    );

    $this->_values = [];
    $params = ['id' => $this->_id];
    CRM_Core_BAO_CustomField::retrieve($params, $this->_values);

    $this->_htmlTypeTransitions = self::fieldTypeTransitions(CRM_Utils_Array::value('data_type', $this->_values),
      CRM_Utils_Array::value('html_type', $this->_values)
    );

    if (empty($this->_values) || empty($this->_htmlTypeTransitions)) {
      CRM_Core_Error::fatal(ts("Invalid custom field or can't change input type of this custom field."));
    }

    $url = CRM_Utils_System::url('civicrm/admin/custom/group/field/update',
      "action=update&reset=1&gid={$this->_values['custom_group_id']}&id={$this->_id}"
    );
    $session = CRM_Core_Session::singleton();
    $session->pushUserContext($url);

    CRM_Utils_System::setTitle(ts('Change Field Type: %1',
      [1 => $this->_values['label']]
    ));
  }

  /**
   * Build the form object.
   *
   * @return void
   */
  public function buildQuickForm() {

    $srcHtmlType = $this->add('select',
      'src_html_type',
      ts('Current HTML Type'),
      [$this->_values['html_type'] => $this->_values['html_type']],
      TRUE
    );

    $srcHtmlType->setValue($this->_values['html_type']);
    $srcHtmlType->freeze();

    $this->assign('srcHtmlType', $this->_values['html_type']);

    $dstHtmlType = $this->add('select',
      'dst_html_type',
      ts('New HTML Type'),
      [
        '' => ts('- select -'),
      ] + $this->_htmlTypeTransitions,
      TRUE
    );

    $this->addButtons([
        [
          'type' => 'next',
          'name' => ts('Change Field Type'),
          'isDefault' => TRUE,
          'js' => ['onclick' => 'return checkCustomDataField();'],
        ],
        [
          'type' => 'cancel',
          'name' => ts('Cancel'),
        ],
      ]
    );
  }

  /**
   * Process the form when submitted.
   *
   * @return void
   */
  public function postProcess() {
    $params = $this->controller->exportValues($this->_name);

    $tableName = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_CustomGroup',
      $this->_values['custom_group_id'],
      'table_name'
    );

    $singleValueOps = [
      'Text',
      'Select',
      'Radio',
      'Autocomplete-Select',
    ];

    $mutliValueOps = [
      'CheckBox',
      'Multi-Select',
    ];

    $srcHtmlType = $this->_values['html_type'];
    $dstHtmlType = $params['dst_html_type'];

    $customField = new CRM_Core_DAO_CustomField();
    $customField->id = $this->_id;
    $customField->find(TRUE);

    if ($dstHtmlType == 'Text' && in_array($srcHtmlType, [
        'Select',
        'Radio',
        'Autocomplete-Select',
      ])
    ) {
      $customField->option_group_id = "NULL";
      CRM_Core_BAO_CustomField::checkOptionGroup($this->_values['option_group_id']);
    }

    if (in_array($srcHtmlType, $mutliValueOps) &&
      in_array($dstHtmlType, $singleValueOps)
    ) {
      $this->flattenToFirstValue($tableName, $this->_values['column_name']);
    }
    elseif (in_array($srcHtmlType, $singleValueOps) &&
      in_array($dstHtmlType, $mutliValueOps)
    ) {
      $this->firstValueToFlatten($tableName, $this->_values['column_name']);
    }

    $customField->html_type = $dstHtmlType;
    $customField->save();

    // Reset cache for custom fields
    CRM_Core_BAO_Cache::deleteGroup('contact fields');

    CRM_Core_Session::setStatus(ts('Input type of custom field \'%1\' has been successfully changed to \'%2\'.',
      [1 => $this->_values['label'], 2 => $dstHtmlType]
    ), ts('Field Type Changed'), 'success');
  }

  /**
   * @param $dataType
   * @param $htmlType
   *
   * @return array|null
   */
  public static function fieldTypeTransitions($dataType, $htmlType) {
    // Text field is single value field,
    // can not be change to other single value option which contains option group
    if ($htmlType == 'Text') {
      return NULL;
    }

    $singleValueOps = [
      'Text' => 'Text',
      'Select' => 'Select',
      'Radio' => 'Radio',
      'Autocomplete-Select' => 'Autocomplete-Select',
    ];

    $mutliValueOps = [
      'CheckBox' => 'CheckBox',
      'Multi-Select' => 'Multi-Select',
    ];

    switch ($dataType) {
      case 'String':
        if (in_array($htmlType, array_keys($singleValueOps))) {
          unset($singleValueOps[$htmlType]);
          return array_merge($singleValueOps, $mutliValueOps);
        }
        elseif (in_array($htmlType, array_keys($mutliValueOps))) {
          unset($singleValueOps['Text']);
          foreach ($singleValueOps as $type => $label) {
            $singleValueOps[$type] = "{$label} ( " . ts('Not Safe') . " )";
          }
          unset($mutliValueOps[$htmlType]);
          return array_merge($mutliValueOps, $singleValueOps);
        }
        break;

      case 'Int':
      case 'Float':
      case 'Int':
      case 'Money':
        if (in_array($htmlType, array_keys($singleValueOps))) {
          unset($singleValueOps[$htmlType]);
          return $singleValueOps;
        }
        break;

      case 'Memo':
        $ops = [
          'TextArea' => 'TextArea',
          'RichTextEditor' => 'RichTextEditor',
        ];
        if (in_array($htmlType, array_keys($ops))) {
          unset($ops[$htmlType]);
          return $ops;
        }
        break;
    }

    return NULL;
  }

  /**
   * Take a single-value column (eg: a Radio or Select etc ) and convert
   * value to the multi listed value (eg:"^Foo^")
   *
   * @param string $table
   * @param string $column
   */
  public function firstValueToFlatten($table, $column) {
    $selectSql = "SELECT id, $column FROM $table WHERE $column IS NOT NULL";
    $updateSql = "UPDATE $table SET $column = %1 WHERE id = %2";
    $dao = CRM_Core_DAO::executeQuery($selectSql);
    while ($dao->fetch()) {
      if (!$dao->{$column}) {
        continue;
      }
      $value = CRM_Core_DAO::VALUE_SEPARATOR . $dao->{$column} . CRM_Core_DAO::VALUE_SEPARATOR;
      $params = [
        1 => [(string) $value, 'String'],
        2 => [$dao->id, 'Integer'],
      ];
      CRM_Core_DAO::executeQuery($updateSql, $params);
    }
  }

  /**
   * Take a multi-value column (e.g. a Multi-Select or CheckBox column), and convert
   * all values (of the form "^^" or "^Foo^" or "^Foo^Bar^") to the first listed value ("Foo")
   *
   * @param string $table
   * @param string $column
   */
  public function flattenToFirstValue($table, $column) {
    $selectSql = "SELECT id, $column FROM $table WHERE $column IS NOT NULL";
    $updateSql = "UPDATE $table SET $column = %1 WHERE id = %2";
    $dao = CRM_Core_DAO::executeQuery($selectSql);
    while ($dao->fetch()) {
      $values = self::explode($dao->{$column});
      $params = [
        1 => [(string) array_shift($values), 'String'],
        2 => [$dao->id, 'Integer'],
      ];
      CRM_Core_DAO::executeQuery($updateSql, $params);
    }
  }

  /**
   * @param $str
   *
   * @return array
   */
  public static function explode($str) {
    if (empty($str) || $str == CRM_Core_DAO::VALUE_SEPARATOR . CRM_Core_DAO::VALUE_SEPARATOR) {
      return [];
    }
    else {
      return explode(CRM_Core_DAO::VALUE_SEPARATOR, trim($str, CRM_Core_DAO::VALUE_SEPARATOR));
    }
  }

}
