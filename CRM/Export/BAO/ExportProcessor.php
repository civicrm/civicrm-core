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
   * Requested output fields.
   *
   * If set to NULL then it is 'primary fields only'
   * which actually means pretty close to all fields!
   *
   * @var array|null
   */
  protected $requestedFields;

  /**
   * CRM_Export_BAO_ExportProcessor constructor.
   *
   * @param int $exportMode
   * @param array|NULL $requestedFields
   * @param string $queryOperator
   */
  public function __construct($exportMode, $requestedFields, $queryOperator) {
    $this->setExportMode($exportMode);
    $this->setQueryMode();
    $this->setQueryOperator($queryOperator);
    $this->setRequestedFields($requestedFields);
  }

  /**
   * @return array|null
   */
  public function getRequestedFields() {
    return $this->requestedFields;
  }

  /**
   * @param array|null $requestedFields
   */
  public function setRequestedFields($requestedFields) {
    $this->requestedFields = $requestedFields;
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
   * Get array of fields to return, over & above those defined in the main contact exportable fields.
   *
   * These include export mode specific fields & some fields apparently required as 'exportableFields'
   * but not returned by the function of the same name.
   *
   * @return array
   *   Array of fields to return in the format ['field_name' => 1,...]
   */
  public function getAdditionalReturnProperties() {

    $missing = [
      'location_type',
      'im_provider',
      'phone_type_id',
      'provider_id',
      'current_employer',
    ];
    if ($this->getQueryMode() === CRM_Contact_BAO_Query::MODE_CONTACTS) {
      $componentSpecificFields = [];
    }
    else {
      $componentSpecificFields = CRM_Contact_BAO_Query::defaultReturnProperties($this->getQueryMode());
    }
    if ($this->getQueryMode() === CRM_Contact_BAO_Query::MODE_PLEDGE) {
      $componentSpecificFields = array_merge($componentSpecificFields, CRM_Pledge_BAO_Query::extraReturnProperties($this->getQueryMode()));
    }
    if ($this->getQueryMode() === CRM_Contact_BAO_Query::MODE_CASE) {
      $componentSpecificFields = array_merge($componentSpecificFields, CRM_Case_BAO_Query::extraReturnProperties($this->getQueryMode()));
    }
    if ($this->getQueryMode() === CRM_Contact_BAO_Query::MODE_CONTRIBUTE) {
      $componentSpecificFields = array_merge($componentSpecificFields, CRM_Contribute_BAO_Query::softCreditReturnProperties(TRUE));
    }
    return array_merge(array_fill_keys($missing, 1), $componentSpecificFields);
  }

  /**
   * Should payment fields be appended to the export.
   *
   * (This is pretty hacky so hopefully this function won't last long - notice
   * how obviously it should be part of the above function!).
   */
  public function isExportPaymentFields() {
    if ($this->getRequestedFields() === NULL
      &&  in_array($this->getExportMode(), [
        CRM_Contact_BAO_Query::MODE_EVENT,
        CRM_Contact_BAO_Query::MODE_MEMBER,
        CRM_Contact_BAO_Query::MODE_PLEDGE,
      ])) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Get the name of the id field in the table that connects contributions to the export entity.
   */
  public function getPaymentTableID() {
    if ($this->getRequestedFields() === NULL) {
      $mapping = [
        CRM_Contact_BAO_Query::MODE_EVENT => 'participant_id',
        CRM_Contact_BAO_Query::MODE_MEMBER => 'membership_id',
        CRM_Contact_BAO_Query::MODE_PLEDGE => 'pledge_payment_id',
      ];
      return isset($mapping[$this->getQueryMode()]) ? $mapping[$this->getQueryMode()] : '';
    }
    return FALSE;
  }

  /**
   * Get the default properties when not specified.
   *
   * In the UI this appears as 'Primary fields only' but in practice it's
   * most of the kitchen sink and the hallway closet thrown in.
   *
   * Since CRM-952 custom fields are excluded, but no other form of mercy is shown.
   *
   * @return array
   */
  public function getDefaultReturnProperties() {
    $returnProperties = [];
    $fields = CRM_Contact_BAO_Contact::exportableFields('All', TRUE, TRUE);
    $skippedFields = ($this->getQueryMode() === CRM_Contact_BAO_Query::MODE_CONTACTS) ? [] : ['groups', 'tags', 'notes'];

    foreach ($fields as $key => $var) {
      if ($key && (substr($key, 0, 6) != 'custom') && !in_array($key, $skippedFields)) {
        $returnProperties[$key] = 1;
      }
    }
    $returnProperties = array_merge($returnProperties, $this->getAdditionalReturnProperties());
    return $returnProperties;
  }

}
