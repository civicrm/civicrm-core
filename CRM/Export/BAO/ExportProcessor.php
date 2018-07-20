<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
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
 * @copyright CiviCRM LLC (c) 2004-2018
 */

/**
 * Class CRM_Export_BAO_ExportProcessor
 *
 * Class to handle logic of export.
 */
class CRM_Export_BAO_ExportProcessor {

  /**
   * @var int
   */
  protected $queryMode;

  /**
   * @var int
   */
  protected $exportMode;

  /**
   * Array of fields in the main query.
   *
   * @var array
   */
  protected $queryFields = [];

  /**
   * Either AND or OR.
   *
   * @var string
   */
  protected $queryOperator;

  /**
   * Array of properties to retrieve for relationships.
   *
   * @var array
   */
  protected $relationshipReturnProperties = [];

  /**
   * CRM_Export_BAO_ExportProcessor constructor.
   *
   * @param int $exportMode
   * @param string $queryOperator
   */
  public function __construct($exportMode, $queryOperator) {
    $this->setExportMode($exportMode);
    $this->setQueryMode();
    $this->setQueryOperator($queryOperator);
  }

  /**
   * @return string
   */
  public function getQueryOperator() {
    return $this->queryOperator;
  }

  /**
   * @param string $queryOperator
   */
  public function setQueryOperator($queryOperator) {
    $this->queryOperator = $queryOperator;
  }

  /**
   * @return array
   */
  public function getQueryFields() {
    return $this->queryFields;
  }

  /**
   * @param array $queryFields
   */
  public function setQueryFields($queryFields) {
    $this->queryFields = $queryFields;
  }

  /**
   * @return int
   */
  public function getQueryMode() {
    return $this->queryMode;
  }

  /**
   * Set the query mode based on the export mode.
   */
  public function setQueryMode() {

    switch ($this->getExportMode()) {
      case CRM_Export_Form_Select::CONTRIBUTE_EXPORT:
        $this->queryMode = CRM_Contact_BAO_Query::MODE_CONTRIBUTE;
        break;

      case CRM_Export_Form_Select::EVENT_EXPORT:
        $this->queryMode = CRM_Contact_BAO_Query::MODE_EVENT;
        break;

      case CRM_Export_Form_Select::MEMBER_EXPORT:
        $this->queryMode = CRM_Contact_BAO_Query::MODE_MEMBER;
        break;

      case CRM_Export_Form_Select::PLEDGE_EXPORT:
        $this->queryMode = CRM_Contact_BAO_Query::MODE_PLEDGE;
        break;

      case CRM_Export_Form_Select::CASE_EXPORT:
        $this->queryMode = CRM_Contact_BAO_Query::MODE_CASE;
        break;

      case CRM_Export_Form_Select::GRANT_EXPORT:
        $this->queryMode = CRM_Contact_BAO_Query::MODE_GRANT;
        break;

      case CRM_Export_Form_Select::ACTIVITY_EXPORT:
        $this->queryMode = CRM_Contact_BAO_Query::MODE_ACTIVITY;
        break;

      default:
        $this->queryMode = CRM_Contact_BAO_Query::MODE_CONTACTS;
    }
  }

  /**
   * @return int
   */
  public function getExportMode() {
    return $this->exportMode;
  }

  /**
   * @param int $exportMode
   */
  public function setExportMode($exportMode) {
    $this->exportMode = $exportMode;
  }

  /**
   * @param $params
   * @param $order
   * @param $returnProperties
   * @return array
   */
  public function runQuery($params, $order, $returnProperties) {
    $query = new CRM_Contact_BAO_Query($params, $returnProperties, NULL,
      FALSE, FALSE, $this->getQueryMode(),
      FALSE, TRUE, TRUE, NULL, $this->getQueryOperator()
    );

    //sort by state
    //CRM-15301
    $query->_sort = $order;
    list($select, $from, $where, $having) = $query->query();
    $this->setQueryFields($query->_fields);
    return array($query, $select, $from, $where, $having);
  }

  /**
   * Add the field to relationship return properties & return it.
   *
   * This function is doing both setting & getting which is yuck but it is an interim
   * refactor.
   *
   * @param array $value
   * @param string $relationshipKey
   *
   * @return array
   */
  public function setRelationshipReturnProperties($value, $relationshipKey) {
    $locationTypes = CRM_Core_PseudoConstant::get('CRM_Core_DAO_Address', 'location_type_id');
    $relPhoneTypeId = $relIMProviderId = NULL;
    if (!empty($value[2])) {
      $relationField = CRM_Utils_Array::value(2, $value);
      if (trim(CRM_Utils_Array::value(3, $value))) {
        $relLocTypeId = CRM_Utils_Array::value(3, $value);
      }
      else {
        $relLocTypeId = 'Primary';
      }

      if ($relationField == 'phone') {
        $relPhoneTypeId = CRM_Utils_Array::value(4, $value);
      }
      elseif ($relationField == 'im') {
        $relIMProviderId = CRM_Utils_Array::value(4, $value);
      }
    }
    elseif (!empty($value[4])) {
      $relationField = CRM_Utils_Array::value(4, $value);
      $relLocTypeId = CRM_Utils_Array::value(5, $value);
      if ($relationField == 'phone') {
        $relPhoneTypeId = CRM_Utils_Array::value(6, $value);
      }
      elseif ($relationField == 'im') {
        $relIMProviderId = CRM_Utils_Array::value(6, $value);
      }
    }
    if (in_array($relationField, $this->getValidLocationFields()) && is_numeric($relLocTypeId)) {
      if ($relPhoneTypeId) {
        $this->relationshipReturnProperties[$relationshipKey]['location'][$locationTypes[$relLocTypeId]]['phone-' . $relPhoneTypeId] = 1;
      }
      elseif ($relIMProviderId) {
        $this->relationshipReturnProperties[$relationshipKey]['location'][$locationTypes[$relLocTypeId]]['im-' . $relIMProviderId] = 1;
      }
      else {
        $this->relationshipReturnProperties[$relationshipKey]['location'][$locationTypes[$relLocTypeId]][$relationField] = 1;
      }
    }
    else {
      $this->relationshipReturnProperties[$relationshipKey][$relationField] = 1;
    }
    return $this->relationshipReturnProperties[$relationshipKey];
  }

  /**
   * Get the default location fields to request.
   *
   * @return array
   */
  public function getValidLocationFields() {
    return [
      'street_address',
      'supplemental_address_1',
      'supplemental_address_2',
      'supplemental_address_3',
      'city',
      'postal_code',
      'postal_code_suffix',
      'geo_code_1',
      'geo_code_2',
      'state_province',
      'country',
      'phone',
      'email',
      'im',
    ];
  }

}
