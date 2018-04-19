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
 * Business object for managing price sets.
 *
 */
class CRM_Price_BAO_PriceSet extends CRM_Price_DAO_PriceSet {

  /**
   * Static field for default price set details.
   *
   * @var array
   */
  static $_defaultPriceSet = NULL;

  /**
   * Class constructor.
   */
  public function __construct() {
    parent::__construct();
  }

  /**
   * Takes an associative array and creates a price set object.
   *
   * @param array $params
   *   (reference) an assoc array of name/value pairs.
   *
   * @return CRM_Price_DAO_PriceSet
   */
  public static function create(&$params) {
    if (empty($params['id']) && empty($params['name'])) {
      $params['name'] = CRM_Utils_String::munge($params['title'], '_', 242);
    }
    $priceSetID = NULL;
    $validatePriceSet = TRUE;
    if (!empty($params['extends']) && is_array($params['extends'])) {
      if (!array_key_exists(CRM_Core_Component::getComponentID('CiviEvent'), $params['extends'])
        || !array_key_exists(CRM_Core_Component::getComponentID('CiviMember'), $params['extends'])
      ) {
        $validatePriceSet = FALSE;
      }
      $params['extends'] = CRM_Utils_Array::implodePadded($params['extends']);
    }
    else {
      $priceSetID = CRM_Utils_Array::value('id', $params);
    }
    $priceSetBAO = new CRM_Price_BAO_PriceSet();
    $priceSetBAO->copyValues($params);
    if (self::eventPriceSetDomainID()) {
      $priceSetBAO->domain_id = CRM_Core_Config::domainID();
    }
    return $priceSetBAO->save();
  }

  /**
   * Fetch object based on array of properties.
   *
   * @param array $params
   *   (reference ) an assoc array of name/value pairs.
   * @param array $defaults
   *   (reference ) an assoc array to hold the flattened values.
   *
   * @return CRM_Price_DAO_PriceSet
   */
  public static function retrieve(&$params, &$defaults) {
    return CRM_Core_DAO::commonRetrieve('CRM_Price_DAO_PriceSet', $params, $defaults);
  }

  /**
   * Update the is_active flag in the db.
   *
   * @param int $id
   *   Id of the database record.
   * @param $isActive
   *
   * @internal param bool $is_active value we want to set the is_active field
   *
   * @return Object
   *   DAO object on success, null otherwise
   */
  public static function setIsActive($id, $isActive) {
    return CRM_Core_DAO::setFieldValue('CRM_Price_DAO_PriceSet', $id, 'is_active', $isActive);
  }

  /**
   * Calculate the default price set id
   * assigned to the contribution/membership etc
   *
   * @param string $entity
   *
   * @return array
   *   default price set
   *
   */
  public static function getDefaultPriceSet($entity = 'contribution') {
    if (!empty(self::$_defaultPriceSet[$entity])) {
      return self::$_defaultPriceSet[$entity];
    }
    $entityName = 'default_contribution_amount';
    if ($entity == 'membership') {
      $entityName = 'default_membership_type_amount';
    }

    $sql = "
SELECT      ps.id AS setID, pfv.price_field_id AS priceFieldID, pfv.id AS priceFieldValueID, pfv.name, pfv.label, pfv.membership_type_id, pfv.amount, pfv.financial_type_id
FROM        civicrm_price_set ps
LEFT JOIN   civicrm_price_field pf ON pf.`price_set_id` = ps.id
LEFT JOIN   civicrm_price_field_value pfv ON pfv.price_field_id = pf.id
WHERE       ps.name = '{$entityName}'
";

    $dao = CRM_Core_DAO::executeQuery($sql);
    self::$_defaultPriceSet[$entity] = array();
    while ($dao->fetch()) {
      self::$_defaultPriceSet[$entity][$dao->priceFieldValueID] = array(
        'setID' => $dao->setID,
        'priceFieldID' => $dao->priceFieldID,
        'name' => $dao->name,
        'label' => $dao->label,
        'priceFieldValueID' => $dao->priceFieldValueID,
        'membership_type_id' => $dao->membership_type_id,
        'amount' => $dao->amount,
        'financial_type_id' => $dao->financial_type_id,
      );
    }

    return self::$_defaultPriceSet[$entity];
  }

  /**
   * Get the price set title.
   *
   * @param int $id
   *   Id of price set.
   *
   * @return string
   *   title
   *
   */
  public static function getTitle($id) {
    return CRM_Core_DAO::getFieldValue('CRM_Price_DAO_PriceSet', $id, 'title');
  }

  /**
   * Return a list of all forms which use this price set.
   *
   * @param int $id
   *   Id of price set.
   * @param bool|string $simpleReturn - get raw data. Possible values: 'entity', 'table'
   *
   * @return array
   */
  public static function getUsedBy($id, $simpleReturn = FALSE) {
    $usedBy = array();
    $forms = self::getFormsUsingPriceSet($id);
    $tables = array_keys($forms);
    // @todo - this is really clumsy overloading the signature like this. Instead
    // move towards having a function that does not call reformatUsedByFormsWithEntityData
    // and call that when that data is not used.
    if ($simpleReturn == 'table') {
      return $tables;
    }
    // @todo - this is painfully slow in some cases.
    if (empty($forms)) {
      $queryString = "
SELECT    cli.entity_table, cli.entity_id
FROM      civicrm_line_item cli
LEFT JOIN civicrm_price_field cpf ON cli.price_field_id = cpf.id
WHERE     cpf.price_set_id = %1";
      $params = array(1 => array($id, 'Integer'));
      $crmFormDAO = CRM_Core_DAO::executeQuery($queryString, $params);
      while ($crmFormDAO->fetch()) {
        $forms[$crmFormDAO->entity_table][] = $crmFormDAO->entity_id;
        $tables[] = $crmFormDAO->entity_table;
      }
      if (empty($forms)) {
        return $usedBy;
      }
    }
    // @todo - this is really clumsy overloading the signature like this. See above.
    if ($simpleReturn == 'entity') {
      return $forms;
    }
    $usedBy = self::reformatUsedByFormsWithEntityData($forms, $usedBy);

    return $usedBy;
  }

  /**
   * Delete the price set, including the fields.
   *
   * @param int $id
   *   Price Set id.
   *
   * @return bool
   *   false if fields exist for this set, true if the
   *   set could be deleted
   *
   */
  public static function deleteSet($id) {
    // delete price fields
    $priceField = new CRM_Price_DAO_PriceField();
    $priceField->price_set_id = $id;
    $priceField->find();
    while ($priceField->fetch()) {
      // delete options first
      CRM_Price_BAO_PriceField::deleteField($priceField->id);
    }

    $set = new CRM_Price_DAO_PriceSet();
    $set->id = $id;
    return $set->delete();
  }

  /**
   * Link the price set with the specified table and id.
   *
   * @param string $entityTable
   * @param int $entityId
   * @param int $priceSetId
   *
   * @return bool
   */
  public static function addTo($entityTable, $entityId, $priceSetId) {
    // verify that the price set exists
    $dao = new CRM_Price_DAO_PriceSet();
    $dao->id = $priceSetId;
    if (!$dao->find()) {
      return FALSE;
    }
    unset($dao);

    $dao = new CRM_Price_DAO_PriceSetEntity();
    // find if this already exists
    $dao->entity_id = $entityId;
    $dao->entity_table = $entityTable;
    $dao->find(TRUE);

    // add or update price_set_id
    $dao->price_set_id = $priceSetId;
    return $dao->save();
  }

  /**
   * Delete price set for the given entity and id.
   *
   * @param string $entityTable
   * @param int $entityId
   *
   * @return mixed
   */
  public static function removeFrom($entityTable, $entityId) {
    $dao = new CRM_Price_DAO_PriceSetEntity();
    $dao->entity_table = $entityTable;
    $dao->entity_id = $entityId;
    return $dao->delete();
  }

  /**
   * Find a price_set_id associated with the given table, id and usedFor
   * Used For value for events:1, contribution:2, membership:3
   *
   * @param string $entityTable
   * @param int $entityId
   * @param int $usedFor
   *   ( price set that extends/used for particular component ).
   *
   * @param null $isQuickConfig
   * @param null $setName
   *
   * @return int|false
   *   price_set_id, or false if none found
   */
  public static function getFor($entityTable, $entityId, $usedFor = NULL, $isQuickConfig = NULL, &$setName = NULL) {
    if (!$entityTable || !$entityId) {
      return FALSE;
    }

    $sql = 'SELECT ps.id as price_set_id, ps.name as price_set_name
                FROM civicrm_price_set ps
                INNER JOIN civicrm_price_set_entity pse ON ps.id = pse.price_set_id
                WHERE pse.entity_table = %1 AND pse.entity_id = %2 ';
    if ($isQuickConfig) {
      $sql .= ' AND ps.is_quick_config = 0 ';
    }
    $params = array(
      1 => array($entityTable, 'String'),
      2 => array($entityId, 'Integer'),
    );
    if ($usedFor) {
      $sql .= " AND ps.extends LIKE '%%3%' ";
      $params[3] = array($usedFor, 'Integer');
    }

    $dao = CRM_Core_DAO::executeQuery($sql, $params);
    $dao->fetch();
    $setName = (isset($dao->price_set_name)) ? $dao->price_set_name : FALSE;
    return (isset($dao->price_set_id)) ? $dao->price_set_id : FALSE;
  }

  /**
   * Find a price_set_id associated with the given option value or  field ID.
   *
   * @param array $params
   *   (reference) an assoc array of name/value pairs.
   *                      array may contain either option id or
   *                      price field id
   *
   * @return int|NULL
   *   price set id on success, null  otherwise
   */
  public static function getSetId(&$params) {
    $fid = NULL;

    if ($oid = CRM_Utils_Array::value('oid', $params)) {
      $fieldValue = new CRM_Price_DAO_PriceFieldValue();
      $fieldValue->id = $oid;
      if ($fieldValue->find(TRUE)) {
        $fid = $fieldValue->price_field_id;
      }
    }
    else {
      $fid = CRM_Utils_Array::value('fid', $params);
    }

    if (isset($fid)) {
      return CRM_Core_DAO::getFieldValue('CRM_Price_DAO_PriceField', $fid, 'price_set_id');
    }

    return NULL;
  }

  /**
   * Return an associative array of all price sets.
   *
   * @param bool $withInactive
   *   Whether or not to include inactive entries.
   * @param bool|string $extendComponentName name of the component like 'CiviEvent','CiviContribute'
   * @param string $column name of the column.
   *
   * @return array
   *   associative array of id => name
   */
  public static function getAssoc($withInactive = FALSE, $extendComponentName = FALSE, $column = 'title') {
    $query = "
    SELECT
       DISTINCT ( price_set_id ) as id, s.{$column}
    FROM
       civicrm_price_set s
       INNER JOIN civicrm_price_field f ON f.price_set_id = s.id
       INNER JOIN civicrm_price_field_value v ON v.price_field_id = f.id
    WHERE
       is_quick_config = 0 ";

    if (!$withInactive) {
      $query .= ' AND s.is_active = 1 ';
    }

    if (self::eventPriceSetDomainID()) {
      $query .= ' AND s.domain_id = ' . CRM_Core_Config::domainID();
    }

    $priceSets = array();

    if ($extendComponentName) {
      $componentId = CRM_Core_Component::getComponentID($extendComponentName);
      if (!$componentId) {
        return $priceSets;
      }
      $query .= " AND s.extends LIKE '%$componentId%' ";
    }
    // Check permissioned financial types
    CRM_Financial_BAO_FinancialType::getAvailableFinancialTypes($financialType, CRM_Core_Action::ADD);
    if ($financialType) {
      $types = implode(',', array_keys($financialType));
      $query .= ' AND s.financial_type_id IN (' . $types . ') AND v.financial_type_id IN (' . $types . ') ';
    }
    else {
      // Do not display any price sets
      $query .= " AND 0 ";
    }
    $query .= " GROUP BY s.id";
    $dao = CRM_Core_DAO::executeQuery($query);
    while ($dao->fetch()) {
      $priceSets[$dao->id] = $dao->$column;
    }
    return $priceSets;
  }

  /**
   * Get price set details.
   *
   * An array containing price set details (including price fields) is returned
   *
   * @param int $setID
   *   Price Set ID.
   * @param bool $required
   *   Appears to have no effect based on reading the code.
   * @param bool $validOnly
   *   Should only fields where today's date falls within the valid range be returned?
   *
   * @return array
   *   Array consisting of field details
   */
  public static function getSetDetail($setID, $required = TRUE, $validOnly = FALSE) {
    // create a new tree
    $setTree = array();

    $priceFields = array(
      'id',
      'name',
      'label',
      'html_type',
      'is_enter_qty',
      'help_pre',
      'help_post',
      'weight',
      'is_display_amounts',
      'options_per_line',
      'is_active',
      'active_on',
      'expire_on',
      'javascript',
      'visibility_id',
      'is_required',
    );
    if ($required == TRUE) {
      $priceFields[] = 'is_required';
    }

    // create select
    $select = 'SELECT ' . implode(',', $priceFields);
    $from = ' FROM civicrm_price_field';

    $params = array(
      1 => array($setID, 'Integer'),
    );
    $currentTime = date('YmdHis');
    $where = "
WHERE price_set_id = %1
AND is_active = 1
AND ( active_on IS NULL OR active_on <= {$currentTime} )
";
    $dateSelect = '';
    if ($validOnly) {
      $dateSelect = "
AND ( expire_on IS NULL OR expire_on >= {$currentTime} )
";
    }

    $orderBy = ' ORDER BY weight';

    $sql = $select . $from . $where . $dateSelect . $orderBy;

    $dao = CRM_Core_DAO::executeQuery($sql, $params);

    $isDefaultContributionPriceSet = FALSE;
    if ('default_contribution_amount' == CRM_Core_DAO::getFieldValue('CRM_Price_DAO_PriceSet', $setID)) {
      $isDefaultContributionPriceSet = TRUE;
    }

    $visibility = CRM_Core_PseudoConstant::visibility('name');
    while ($dao->fetch()) {
      $fieldID = $dao->id;

      $setTree[$setID]['fields'][$fieldID] = array();
      $setTree[$setID]['fields'][$fieldID]['id'] = $fieldID;

      foreach ($priceFields as $field) {
        if ($field == 'id' || is_null($dao->$field)) {
          continue;
        }

        if ($field == 'visibility_id') {
          $setTree[$setID]['fields'][$fieldID]['visibility'] = $visibility[$dao->$field];
        }
        $setTree[$setID]['fields'][$fieldID][$field] = $dao->$field;
      }
      $setTree[$setID]['fields'][$fieldID]['options'] = CRM_Price_BAO_PriceField::getOptions($fieldID, FALSE, FALSE, $isDefaultContributionPriceSet);
    }

    // also get the pre and post help from this price set
    $sql = "
SELECT extends, financial_type_id, help_pre, help_post, is_quick_config, min_amount
FROM   civicrm_price_set
WHERE  id = %1";
    $dao = CRM_Core_DAO::executeQuery($sql, $params);
    if ($dao->fetch()) {
      $setTree[$setID]['extends'] = $dao->extends;
      $setTree[$setID]['financial_type_id'] = $dao->financial_type_id;
      $setTree[$setID]['help_pre'] = $dao->help_pre;
      $setTree[$setID]['help_post'] = $dao->help_post;
      $setTree[$setID]['is_quick_config'] = $dao->is_quick_config;
      $setTree[$setID]['min_amount'] = $dao->min_amount;
    }
    return $setTree;
  }

  /**
   * Get the Price Field ID.
   *
   * We call this function when more than one being present would represent an error
   * starting format derived from current(CRM_Price_BAO_PriceSet::getSetDetail($priceSetId))
   * @param array $priceSet
   *
   * @throws CRM_Core_Exception
   * @return int
   */
  public static function getOnlyPriceFieldID(array $priceSet) {
    if (count($priceSet['fields']) > 1) {
      throw new CRM_Core_Exception(ts('expected only one price field to be in price set but multiple are present'));
    }
    return (int) implode('_', array_keys($priceSet['fields']));
  }

  /**
   * Get the Price Field Value ID. We call this function when more than one being present would represent an error
   * current(CRM_Price_BAO_PriceSet::getSetDetail($priceSetId))
   * @param array $priceSet
   *
   * @throws CRM_Core_Exception
   * @return int
   */
  public static function getOnlyPriceFieldValueID(array $priceSet) {
    $priceFieldID = self::getOnlyPriceFieldID($priceSet);
    if (count($priceSet['fields'][$priceFieldID]['options']) > 1) {
      throw new CRM_Core_Exception(ts('expected only one price field to be in price set but multiple are present'));
    }
    return (int) implode('_', array_keys($priceSet['fields'][$priceFieldID]['options']));
  }


  /**
   * Initiate price set such that various non-BAO things are set on the form.
   *
   * This function is not really a BAO function so the location is misleading.
   *
   * @param CRM_Core_Form $form
   * @param int $id
   *   Form entity id.
   * @param string $entityTable
   * @param bool $validOnly
   * @param int $priceSetId
   *   Price Set ID
   *
   * @return bool|false|int|null
   */
  public static function initSet(&$form, $id, $entityTable = 'civicrm_event', $validOnly = FALSE, $priceSetId = NULL) {
    if (!$priceSetId) {
      $priceSetId = self::getFor($entityTable, $id);
    }

    //check if price set is is_config
    if (is_numeric($priceSetId)) {
      if (CRM_Core_DAO::getFieldValue('CRM_Price_DAO_PriceSet', $priceSetId, 'is_quick_config') && $form->getVar('_name') != 'Participant') {
        $form->assign('quickConfig', 1);
      }
    }
    // get price info
    if ($priceSetId) {
      if ($form->_action & CRM_Core_Action::UPDATE) {
        $entityId = $entity = NULL;

        switch ($entityTable) {
          case 'civicrm_event':
            $entity = 'participant';
            if (CRM_Utils_System::getClassName($form) == 'CRM_Event_Form_Participant') {
              $entityId = $form->_id;
            }
            else {
              $entityId = $form->_participantId;
            }
            break;

          case 'civicrm_contribution_page':
          case 'civicrm_contribution':
            $entity = 'contribution';
            $entityId = $form->_id;
            break;
        }

        if ($entityId && $entity) {
          $form->_values['line_items'] = CRM_Price_BAO_LineItem::getLineItems($entityId, $entity);
        }
        $required = FALSE;
      }
      else {
        $required = TRUE;
      }

      $form->_priceSetId = $priceSetId;
      $priceSet = self::getSetDetail($priceSetId, $required, $validOnly);
      $form->_priceSet = CRM_Utils_Array::value($priceSetId, $priceSet);
      $form->_values['fee'] = CRM_Utils_Array::value('fields', $form->_priceSet);

      //get the price set fields participant count.
      if ($entityTable == 'civicrm_event') {
        //get option count info.
        $form->_priceSet['optionsCountTotal'] = self::getPricesetCount($priceSetId);
        if ($form->_priceSet['optionsCountTotal']) {
          $optionsCountDetails = array();
          if (!empty($form->_priceSet['fields'])) {
            foreach ($form->_priceSet['fields'] as $field) {
              foreach ($field['options'] as $option) {
                $count = CRM_Utils_Array::value('count', $option, 0);
                $optionsCountDetails['fields'][$field['id']]['options'][$option['id']] = $count;
              }
            }
          }
          $form->_priceSet['optionsCountDetails'] = $optionsCountDetails;
        }

        //get option max value info.
        $optionsMaxValueTotal = 0;
        $optionsMaxValueDetails = array();

        if (!empty($form->_priceSet['fields'])) {
          foreach ($form->_priceSet['fields'] as $field) {
            foreach ($field['options'] as $option) {
              $maxVal = CRM_Utils_Array::value('max_value', $option, 0);
              $optionsMaxValueDetails['fields'][$field['id']]['options'][$option['id']] = $maxVal;
              $optionsMaxValueTotal += $maxVal;
            }
          }
        }

        $form->_priceSet['optionsMaxValueTotal'] = $optionsMaxValueTotal;
        if ($optionsMaxValueTotal) {
          $form->_priceSet['optionsMaxValueDetails'] = $optionsMaxValueDetails;
        }
      }
      $form->set('priceSetId', $form->_priceSetId);
      $form->set('priceSet', $form->_priceSet);

      return $priceSetId;
    }
    return FALSE;
  }

  /**
   * Get line item purchase information.
   *
   * This function takes the input parameters and interprets out of it what has been purchased.
   *
   * @param $fields
   *   This is the output of the function CRM_Price_BAO_PriceSet::getSetDetail($priceSetID, FALSE, FALSE);
   *   And, it would make sense to introduce caching into that function and call it from here rather than
   *   require the $fields array which is passed from pillar to post around the form in order to pass it in here.
   * @param array $params
   *   Params reflecting form input e.g with fields 'price_5' => 7, 'price_8' => array(7, 8)
   * @param $lineItem
   *   Line item array to be altered.
   * @param string $component
   *   This parameter appears to only be relevant to determining whether memberships should be auto-renewed.
   *   (and is effectively a boolean for 'is_membership' which could be calculated from the line items.)
   * @param int $priceSetID
   */
  public static function processAmount($fields, &$params, &$lineItem, $component = '', $priceSetID = NULL) {
    // using price set
    $totalPrice = $totalTax = 0;
    // CRM-18701 Sometimes the amount in the price set is overridden by the amount on the form.
    // This is notably the case with memberships and we need to put this amount
    // on the line item rather than the calculated amount.
    // This seems to only affect radio link items as that is the use case for the 'quick config'
    // set up (which allows a free form field).
    $amount_override = NULL;

    if ($component) {
      $autoRenew = array();
      $autoRenew[0] = $autoRenew[1] = $autoRenew[2] = 0;
    }
    if ($priceSetID) {
      $priceFields = self::filterPriceFieldsFromParams($priceSetID, $params);
      if (count($priceFields) == 1) {
        $amount_override = CRM_Utils_Array::value('partial_payment_total', $params, CRM_Utils_Array::value('total_amount', $params));
      }
    }
    foreach ($fields as $id => $field) {
      if (empty($params["price_{$id}"]) ||
        (empty($params["price_{$id}"]) && $params["price_{$id}"] == NULL)
      ) {
        // skip if nothing was submitted for this field
        continue;
      }

      switch ($field['html_type']) {
        case 'Text':
          $firstOption = reset($field['options']);
          $params["price_{$id}"] = array($firstOption['id'] => $params["price_{$id}"]);
          CRM_Price_BAO_LineItem::format($id, $params, $field, $lineItem, CRM_Utils_Array::value('partial_payment_total', $params));
          $optionValueId = key($field['options']);

          if (CRM_Utils_Array::value('name', $field['options'][$optionValueId]) == 'contribution_amount') {
            $taxRates = CRM_Core_PseudoConstant::getTaxRates();
            if (array_key_exists($params['financial_type_id'], $taxRates)) {
              $field['options'][key($field['options'])]['tax_rate'] = $taxRates[$params['financial_type_id']];
              $taxAmount = CRM_Contribute_BAO_Contribution_Utils::calculateTaxAmount($field['options'][$optionValueId]['amount'], $field['options'][$optionValueId]['tax_rate']);
              $field['options'][$optionValueId]['tax_amount'] = round($taxAmount['tax_amount'], 2);
            }
          }
          if (CRM_Utils_Array::value('tax_rate', $field['options'][$optionValueId])) {
            $lineItem = self::setLineItem($field, $lineItem, $optionValueId, $totalTax);
          }
          $totalPrice += $lineItem[$firstOption['id']]['line_total'] + CRM_Utils_Array::value('tax_amount', $lineItem[key($field['options'])]);
          break;

        case 'Radio':
          //special case if user select -none-
          if ($params["price_{$id}"] <= 0) {
            continue;
          }
          $params["price_{$id}"] = array($params["price_{$id}"] => 1);
          $optionValueId = CRM_Utils_Array::key(1, $params["price_{$id}"]);

          CRM_Price_BAO_LineItem::format($id, $params, $field, $lineItem, $amount_override);
          if (CRM_Utils_Array::value('tax_rate', $field['options'][$optionValueId])) {
            $lineItem = self::setLineItem($field, $lineItem, $optionValueId, $totalTax);
            if ($amount_override) {
              $lineItem[$optionValueId]['line_total'] = $lineItem[$optionValueId]['unit_price'] = CRM_Utils_Rule::cleanMoney($lineItem[$optionValueId]['line_total'] - $lineItem[$optionValueId]['tax_amount']);
            }
          }
          $totalPrice += $lineItem[$optionValueId]['line_total'] + CRM_Utils_Array::value('tax_amount', $lineItem[$optionValueId]);
          if (
            $component &&
            // auto_renew exists and is empty in some workflows, which php treat as a 0
            // and hence we explicitly check to see if auto_renew is numeric
            isset($lineItem[$optionValueId]['auto_renew']) &&
            is_numeric($lineItem[$optionValueId]['auto_renew'])
          ) {
            $autoRenew[$lineItem[$optionValueId]['auto_renew']] += $lineItem[$optionValueId]['line_total'];
          }
          break;

        case 'Select':
          $params["price_{$id}"] = array($params["price_{$id}"] => 1);
          $optionValueId = CRM_Utils_Array::key(1, $params["price_{$id}"]);

          CRM_Price_BAO_LineItem::format($id, $params, $field, $lineItem, CRM_Utils_Array::value('partial_payment_total', $params));
          if (CRM_Utils_Array::value('tax_rate', $field['options'][$optionValueId])) {
            $lineItem = self::setLineItem($field, $lineItem, $optionValueId, $totalTax);
          }
          $totalPrice += $lineItem[$optionValueId]['line_total'] + CRM_Utils_Array::value('tax_amount', $lineItem[$optionValueId]);
          if (
            $component &&
            isset($lineItem[$optionValueId]['auto_renew']) &&
            is_numeric($lineItem[$optionValueId]['auto_renew'])
          ) {
            $autoRenew[$lineItem[$optionValueId]['auto_renew']] += $lineItem[$optionValueId]['line_total'];
          }
          break;

        case 'CheckBox':

          CRM_Price_BAO_LineItem::format($id, $params, $field, $lineItem, CRM_Utils_Array::value('partial_payment_total', $params));
          foreach ($params["price_{$id}"] as $optionId => $option) {
            if (CRM_Utils_Array::value('tax_rate', $field['options'][$optionId])) {
              $lineItem = self::setLineItem($field, $lineItem, $optionId, $totalTax);
            }
            $totalPrice += $lineItem[$optionId]['line_total'] + CRM_Utils_Array::value('tax_amount', $lineItem[$optionId]);
            if (
              $component &&
              isset($lineItem[$optionId]['auto_renew']) &&
              is_numeric($lineItem[$optionId]['auto_renew'])
            ) {
              $autoRenew[$lineItem[$optionId]['auto_renew']] += $lineItem[$optionId]['line_total'];
            }
          }
          break;
      }
    }

    $amount_level = array();
    $totalParticipant = 0;
    if (is_array($lineItem)) {
      foreach ($lineItem as $values) {
        $totalParticipant += $values['participant_count'];
        // This is a bit nasty. The logic of 'quick config' was because price set configuration was
        // (and still is) too difficult to replace the 'quick config' price set configuration on the contribution
        // page.
        //
        // However, because the quick config concept existed all sorts of logic was hung off it
        // and function behaviour sometimes depends on whether 'price set' is set - although actually it
        // is always set at the functional level. In this case we are dealing with the default 'quick config'
        // price set having a label of 'Contribution Amount' which could wind up creating a 'funny looking' label.
        // The correct answer is probably for it to have an empty label in the DB - the label is never shown so it is a
        // place holder.
        //
        // But, in the interests of being careful when capacity is low - avoiding the known default value
        // will get us by.
        // Crucially a test has been added so a better solution can be implemented later with some comfort.
        // @todo - stop setting amount level in this function & call the getAmountLevel function to retrieve it.
        if ($values['label'] != ts('Contribution Amount')) {
          $amount_level[] = $values['label'] . ' - ' . (float) $values['qty'];
        }
      }
    }

    $displayParticipantCount = '';
    if ($totalParticipant > 0) {
      $displayParticipantCount = ' Participant Count -' . $totalParticipant;
    }
    // @todo - stop setting amount level in this function & call the getAmountLevel function to retrieve it.
    if (!empty($amount_level)) {
      $params['amount_level'] = CRM_Utils_Array::implodePadded($amount_level);
      if (!empty($displayParticipantCount)) {
        $params['amount_level'] = CRM_Core_DAO::VALUE_SEPARATOR . implode(CRM_Core_DAO::VALUE_SEPARATOR, $amount_level) . $displayParticipantCount . CRM_Core_DAO::VALUE_SEPARATOR;
      }
    }

    $params['amount'] = $totalPrice;
    $params['tax_amount'] = $totalTax;
    if ($component) {
      foreach ($autoRenew as $dontCare => $eachAmount) {
        if (!$eachAmount) {
          unset($autoRenew[$dontCare]);
        }
      }
      if (count($autoRenew) > 1) {
        $params['autoRenew'] = $autoRenew;
      }
    }
  }

  /**
   * Get the text to record for amount level.
   *
   * @param array $params
   *   Submitted parameters
   *   - priceSetId is required to be set in the calling function
   *     (we don't e-notice check it to enforce that - all payments DO have a price set - even if it is the
   *     default one & this function asks that be set if it is the case).
   *
   * @return string
   *   Text for civicrm_contribution.amount_level field.
   */
  public static function getAmountLevelText($params) {
    $priceSetID = $params['priceSetId'];
    $priceFieldSelection = self::filterPriceFieldsFromParams($priceSetID, $params);
    $priceFieldMetadata = self::getCachedPriceSetDetail($priceSetID);
    $displayParticipantCount = NULL;

    $amount_level = array();
    foreach ($priceFieldMetadata['fields'] as $field) {
      if (!empty($priceFieldSelection[$field['id']])) {
        $qtyString = '';
        if ($field['is_enter_qty']) {
          $qtyString = ' - ' . (float) $params['price_' . $field['id']];
        }
        // We deliberately & specifically exclude contribution amount as it has a specific meaning.
        // ie. it represents the default price field for a contribution. Another approach would be not
        // to give it a label if we don't want it to show.
        if ($field['label'] != ts('Contribution Amount')) {
          $amount_level[] = $field['label'] . $qtyString;
        }
      }
    }
    return CRM_Core_DAO::VALUE_SEPARATOR . implode(CRM_Core_DAO::VALUE_SEPARATOR, $amount_level) . $displayParticipantCount . CRM_Core_DAO::VALUE_SEPARATOR;
  }

  /**
   * Get the fields relevant to the price field from the parameters.
   *
   * E.g we are looking for price_5 => 7 out of a big array of input parameters.
   *
   * @param int $priceSetID
   * @param array $params
   *
   * @return array
   *   Price fields found in the params array
   */
  public static function filterPriceFieldsFromParams($priceSetID, $params) {
    $priceSet = self::getCachedPriceSetDetail($priceSetID);
    $return = array();
    foreach ($priceSet['fields'] as $field) {
      if (!empty($params['price_' . $field['id']])) {
        $return[$field['id']] = $params['price_' . $field['id']];
      }
    }
    return $return;
  }

  /**
   * Wrapper for getSetDetail with caching.
   *
   * We seem to be passing this array around in a painful way - presumably to avoid the hit
   * of loading it - so lets make it callable with caching.
   *
   * Why not just add caching to the other function? We could do - it just seemed a bit unclear the best caching pattern
   * & the function was already pretty fugly. Also, I feel like we need to migrate the interaction with price-sets into
   * a more granular interaction - ie. retrieve specific data using specific functions on this class & have the form
   * think less about the price sets.
   *
   * @param int $priceSetID
   *
   * @return array
   */
  public static function getCachedPriceSetDetail($priceSetID) {
    $cacheKey = __CLASS__ . __FUNCTION__ . '_' . $priceSetID;
    $cache = CRM_Utils_Cache::singleton();
    $values = $cache->get($cacheKey);
    if (empty($values)) {
      $data = self::getSetDetail($priceSetID);
      $values = $data[$priceSetID];
      $cache->set($cacheKey, $values);
    }
    return $values;
  }

  /**
   * Build the price set form.
   *
   * @param CRM_Core_Form $form
   *
   * @return void
   */
  public static function buildPriceSet(&$form) {
    $priceSetId = $form->get('priceSetId');
    if (!$priceSetId) {
      return;
    }

    $validFieldsOnly = TRUE;
    $className = CRM_Utils_System::getClassName($form);
    if (in_array($className, array(
      'CRM_Contribute_Form_Contribution',
      'CRM_Member_Form_Membership',
    ))) {
      $validFieldsOnly = FALSE;
    }

    $priceSet = self::getSetDetail($priceSetId, TRUE, $validFieldsOnly);
    $form->_priceSet = CRM_Utils_Array::value($priceSetId, $priceSet);
    $validPriceFieldIds = array_keys($form->_priceSet['fields']);
    $form->_quickConfig = $quickConfig = 0;
    if (CRM_Core_DAO::getFieldValue('CRM_Price_DAO_PriceSet', $priceSetId, 'is_quick_config')) {
      $quickConfig = 1;
    }

    $form->assign('quickConfig', $quickConfig);
    if ($className == 'CRM_Contribute_Form_Contribution_Main') {
      $form->_quickConfig = $quickConfig;
    }

    // Mark which field should have the auto-renew checkbox, if any. CRM-18305
    if (!empty($form->_membershipTypeValues) && is_array($form->_membershipTypeValues)) {
      $autoRenewMembershipTypes = array();
      foreach ($form->_membershipTypeValues as $membershiptTypeValue) {
        if ($membershiptTypeValue['auto_renew']) {
          $autoRenewMembershipTypes[] = $membershiptTypeValue['id'];
        }
      }
      foreach ($form->_priceSet['fields'] as $field) {
        if (array_key_exists('options', $field) && is_array($field['options'])) {
          foreach ($field['options'] as $option) {
            if (!empty($option['membership_type_id'])) {
              if (in_array($option['membership_type_id'], $autoRenewMembershipTypes)) {
                $form->_priceSet['auto_renew_membership_field'] = $field['id'];
                // Only one field can offer auto_renew memberships, so break here.
                break;
              }
            }
          }
        }
      }
    }
    $form->assign('priceSet', $form->_priceSet);

    $component = 'contribution';
    if ($className == 'CRM_Member_Form_Membership') {
      $component = 'membership';
    }

    if ($className == 'CRM_Contribute_Form_Contribution_Main') {
      $feeBlock = &$form->_values['fee'];
      if (!empty($form->_useForMember)) {
        $component = 'membership';
      }
    }
    else {
      $feeBlock = &$form->_priceSet['fields'];
    }
    if (CRM_Financial_BAO_FinancialType::isACLFinancialTypeStatus()) {
      foreach ($feeBlock as $key => $value) {
        foreach ($value['options'] as $k => $options) {
          if (!CRM_Core_Permission::check('add contributions of type ' . CRM_Contribute_PseudoConstant::financialType($options['financial_type_id']))) {
            unset($feeBlock[$key]['options'][$k]);
          }
        }
        if (empty($feeBlock[$key]['options'])) {
          unset($feeBlock[$key]);
        }
      }
    }
    // call the hook.
    CRM_Utils_Hook::buildAmount($component, $form, $feeBlock);

    // CRM-14492 Admin price fields should show up on event registration if user has 'administer CiviCRM' permissions
    $adminFieldVisible = FALSE;
    if (CRM_Core_Permission::check('administer CiviCRM')) {
      $adminFieldVisible = TRUE;
    }

    $hideAdminValues = TRUE;
    if (CRM_Core_Permission::check('edit contributions')) {
      $hideAdminValues = FALSE;
    }

    foreach ($feeBlock as $id => $field) {
      if (CRM_Utils_Array::value('visibility', $field) == 'public' ||
        (CRM_Utils_Array::value('visibility', $field) == 'admin' && $adminFieldVisible == TRUE) ||
        !$validFieldsOnly
      ) {
        $options = CRM_Utils_Array::value('options', $field);
        if ($className == 'CRM_Contribute_Form_Contribution_Main' && $component = 'membership') {
          $userid = $form->getVar('_membershipContactID');
          $checklifetime = self::checkCurrentMembership($options, $userid);
          if ($checklifetime) {
            $form->assign('ispricelifetime', TRUE);
          }
        }

        $formClasses = array('CRM_Contribute_Form_Contribution', 'CRM_Member_Form_Membership');

        if (!is_array($options) || !in_array($id, $validPriceFieldIds)) {
          continue;
        }
        elseif ($hideAdminValues && !in_array($className, $formClasses)) {
          foreach ($options as $key => $currentOption) {
            if ($currentOption['visibility_id'] == CRM_Price_BAO_PriceField::getVisibilityOptionID('admin')) {
              unset($options[$key]);
            }
          }
        }
        if (!empty($options)) {
          CRM_Price_BAO_PriceField::addQuickFormElement($form,
            'price_' . $field['id'],
            $field['id'],
            FALSE,
            CRM_Utils_Array::value('is_required', $field, FALSE),
            NULL,
            $options
          );
        }
      }
    }
  }

  /**
   * Check the current Membership having end date null.
   *
   * @param array $options
   * @param int $userid
   *   Probably actually contact ID.
   *
   * @return bool
   */
  public static function checkCurrentMembership(&$options, $userid) {
    if (!$userid || empty($options)) {
      return FALSE;
    }
    static $_contact_memberships = array();
    $checkLifetime = FALSE;
    foreach ($options as $key => $value) {
      if (!empty($value['membership_type_id'])) {
        if (!isset($_contact_memberships[$userid][$value['membership_type_id']])) {
          $_contact_memberships[$userid][$value['membership_type_id']] = CRM_Member_BAO_Membership::getContactMembership($userid, $value['membership_type_id'], FALSE);
        }
        $currentMembership = $_contact_memberships[$userid][$value['membership_type_id']];
        if (!empty($currentMembership) && empty($currentMembership['end_date'])) {
          unset($options[$key]);
          $checkLifetime = TRUE;
        }
      }
    }
    if ($checkLifetime) {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

  /**
   * Set daefult the price set fields.
   *
   * @param CRM_Core_Form $form
   * @param $defaults
   *
   * @return array
   */
  public static function setDefaultPriceSet(&$form, &$defaults) {
    if (!isset($form->_priceSet) || empty($form->_priceSet['fields'])) {
      return $defaults;
    }

    foreach ($form->_priceSet['fields'] as $val) {
      foreach ($val['options'] as $keys => $values) {
        // build price field index which is passed via URL
        // url format will be appended by "&price_5=11"
        $priceFieldName = 'price_' . $values['price_field_id'];
        $priceFieldValue = self::getPriceFieldValueFromURL($form, $priceFieldName);
        if (!empty($priceFieldValue)) {
          self::setDefaultPriceSetField($priceFieldName, $priceFieldValue, $val['html_type'], $defaults);
          // break here to prevent overwriting of default due to 'is_default'
          // option configuration. The value sent via URL get's higher priority.
          break;
        }
        elseif ($values['is_default']) {
          self::setDefaultPriceSetField($priceFieldName, $keys, $val['html_type'], $defaults);
        }
      }
    }
    return $defaults;
  }

  /**
   * Get the value of price field if passed via url
   *
   * @param string $priceFieldName
   * @param string $priceFieldValue
   * @param string $priceFieldType
   * @param array $defaults
   *
   * @return void
   */
  public static function setDefaultPriceSetField($priceFieldName, $priceFieldValue, $priceFieldType, &$defaults) {
    if ($priceFieldType == 'CheckBox') {
      $defaults[$priceFieldName][$priceFieldValue] = 1;
    }
    else {
      $defaults[$priceFieldName] = $priceFieldValue;
    }
  }

  /**
   * Get the value of price field if passed via url
   *
   * @param CRM_Core_Form $form
   * @param string $priceFieldName
   *
   * @return mixed $priceFieldValue
   */
  public static function getPriceFieldValueFromURL(&$form, $priceFieldName) {
    $priceFieldValue = CRM_Utils_Request::retrieve($priceFieldName, 'String', $form, FALSE, NULL, 'GET');
    if (!empty($priceFieldValue)) {
      return $priceFieldValue;
    }
  }

  /**
   * Supports event create function by setting up required price sets, not tested but expect
   * it will work for contribution page
   * @param array $params
   *   As passed to api/bao create fn.
   * @param CRM_Core_DAO $entity
   *   Object for given entity.
   * @param string $entityName
   *   Name of entity - e.g event.
   */
  public static function setPriceSets(&$params, $entity, $entityName) {
    if (empty($params['price_set_id']) || !is_array($params['price_set_id'])) {
      return;
    }
    // CRM-14069 note that we may as well start by assuming more than one.
    // currently the form does not pass in as an array & will be skipped
    // test is passing in as an array but I feel the api should have a metadata that allows
    // transform of single to array - seems good for managing transitions - in which case all api
    // calls that set price_set_id will hit this
    // e.g in getfields 'price_set_id' => array('blah', 'bao_type' => 'array') - causing
    // all separated values, strings, json half-separated values (in participant we hit this)
    // to be converted to json @ api layer
    $pse = new CRM_Price_DAO_PriceSetEntity();
    $pse->entity_table = 'civicrm_' . $entityName;
    $pse->entity_id = $entity->id;
    while ($pse->fetch()) {
      if (!in_array($pse->price_set_id, $params['price_set_id'])) {
        // note an even more aggressive form of this deletion currently happens in event form
        // past price sets discounts are made inaccessible by this as the discount_id is set to NULL
        // on the participant record
        if (CRM_Price_BAO_PriceSet::removeFrom('civicrm_' . $entityName, $entity->id)) {
          CRM_Core_BAO_Discount::del($entity->id, 'civicrm_' . $entityName);
        }
      }
    }
    foreach ($params['price_set_id'] as $priceSetID) {
      CRM_Price_BAO_PriceSet::addTo('civicrm_' . $entityName, $entity->id, $priceSetID);
      //@todo - how should we do this - copied from form
      //if (!empty($params['price_field_id'])) {
      //  $priceSetID = CRM_Core_DAO::getFieldValue('CRM_Price_DAO_PriceField', $params['price_field_id'], 'price_set_id');
      //  CRM_Price_BAO_PriceSet::setIsQuickConfig($priceSetID, 0);
      //}
    }
  }

  /**
   * Get field ids of a price set.
   *
   * @param int $id
   *   Price Set id.
   *
   * @return array
   *   Array of the field ids
   *
   */
  public static function getFieldIds($id) {
    $priceField = new CRM_Price_DAO_PriceField();
    $priceField->price_set_id = $id;
    $priceField->find();
    while ($priceField->fetch()) {
      $var[] = $priceField->id;
    }
    return $var;
  }

  /**
   * Copy a price set, including all the fields
   *
   * @param int $id
   *   The price set id to copy.
   *
   * @return CRM_Price_DAO_PriceSet
   */
  public static function copy($id) {
    $maxId = CRM_Core_DAO::singleValueQuery("SELECT max(id) FROM civicrm_price_set");
    $priceSet = civicrm_api3('PriceSet', 'getsingle', array('id' => $id));

    $newTitle = preg_replace('/\[Copy id \d+\]$/', "", $priceSet['title']);
    $title = ts('[Copy id %1]', array(1 => $maxId + 1));
    $fieldsFix = array(
      'replace' => array(
        'title' => trim($newTitle) . ' ' . $title,
        'name' => substr($priceSet['name'], 0, 20) . 'price_set_' . ($maxId + 1),
      ),
    );

    $copy = CRM_Core_DAO::copyGeneric('CRM_Price_DAO_PriceSet',
      array('id' => $id),
      NULL,
      $fieldsFix
    );

    //copying all the blocks pertaining to the price set
    $copyPriceField = CRM_Core_DAO::copyGeneric('CRM_Price_DAO_PriceField',
      array('price_set_id' => $id),
      array('price_set_id' => $copy->id)
    );
    if (!empty($copyPriceField)) {
      $price = array_combine(self::getFieldIds($id), self::getFieldIds($copy->id));

      //copy option group and values
      foreach ($price as $originalId => $copyId) {
        CRM_Core_DAO::copyGeneric('CRM_Price_DAO_PriceFieldValue',
          array('price_field_id' => $originalId),
          array('price_field_id' => $copyId)
        );
      }
    }
    $copy->save();

    CRM_Utils_Hook::copy('Set', $copy);
    return $copy;
  }

  /**
   * check price set permission.
   *
   * @param int $sid
   *   The price set id.
   *
   * @return bool
   */
  public static function checkPermission($sid) {
    if ($sid && self::eventPriceSetDomainID()) {
      $domain_id = CRM_Core_DAO::getFieldValue('CRM_Price_DAO_PriceSet', $sid, 'domain_id', 'id');
      if (CRM_Core_Config::domainID() != $domain_id) {
        CRM_Core_Error::fatal(ts('You do not have permission to access this page.'));
      }
    }
    return TRUE;
  }

  /**
   * Get the sum of participant count
   * for all fields of given price set.
   *
   * @param int $sid
   *   The price set id.
   *
   * @param bool $onlyActive
   *
   * @return int|null|string
   */
  public static function getPricesetCount($sid, $onlyActive = TRUE) {
    $count = 0;
    if (!$sid) {
      return $count;
    }

    $where = NULL;
    if ($onlyActive) {
      $where = 'AND  value.is_active = 1 AND field.is_active = 1';
    }

    static $pricesetFieldCount = array();
    if (!isset($pricesetFieldCount[$sid])) {
      $sql = "
    SELECT  sum(value.count) as totalCount
      FROM  civicrm_price_field_value  value
INNER JOIN  civicrm_price_field field ON ( field.id = value.price_field_id )
INNER JOIN  civicrm_price_set pset    ON ( pset.id = field.price_set_id )
     WHERE  pset.id = %1
            $where";

      $count = CRM_Core_DAO::singleValueQuery($sql, array(1 => array($sid, 'Positive')));
      $pricesetFieldCount[$sid] = ($count) ? $count : 0;
    }

    return $pricesetFieldCount[$sid];
  }

  /**
   * @param $ids
   *
   * @return array
   */
  public static function getMembershipCount($ids) {
    $queryString = "
SELECT       count( pfv.id ) AS count, mt.member_of_contact_id AS id
FROM         civicrm_price_field_value pfv
INNER JOIN    civicrm_membership_type mt ON mt.id = pfv.membership_type_id
WHERE        pfv.id IN ( $ids )
GROUP BY     mt.member_of_contact_id ";

    $crmDAO = CRM_Core_DAO::executeQuery($queryString);
    $count = array();

    while ($crmDAO->fetch()) {
      $count[$crmDAO->id] = $crmDAO->count;
    }

    return $count;
  }

  /**
   * Check if auto renew option should be shown.
   *
   * The auto-renew option should be visible if membership types associated with all the fields has
   * been set for auto-renew option.
   *
   * Auto renew checkbox should be frozen if for all the membership type auto renew is required
   *
   * @param int $priceSetId
   *   Price set id.
   *
   * @return int
   *   $autoRenewOption ( 0:hide, 1:optional 2:required )
   */
  public static function checkAutoRenewForPriceSet($priceSetId) {
    $query = 'SELECT DISTINCT mt.auto_renew, mt.duration_interval, mt.duration_unit,
             pf.html_type, pf.id as price_field_id
            FROM civicrm_price_field_value pfv
            INNER JOIN civicrm_membership_type mt ON pfv.membership_type_id = mt.id
            INNER JOIN civicrm_price_field pf ON pfv.price_field_id = pf.id
            WHERE pf.price_set_id = %1
            AND   pf.is_active = 1
            AND   pfv.is_active = 1
            ORDER BY price_field_id';

    $params = array(1 => array($priceSetId, 'Integer'));

    $dao = CRM_Core_DAO::executeQuery($query, $params);

    //CRM-18050: Check count of price set fields which has been set with auto-renew option.
    //If price set field is already present with auto-renew option then, it will restrict for adding another price set field with auto-renew option.
    if ($dao->N == 0) {
      return 0;
    }

    $autoRenewOption = 2;
    $priceFields = array();
    while ($dao->fetch()) {
      if (!$dao->auto_renew) {
        // If any one can't be renewed none can.
        return 0;
      }
      if ($dao->auto_renew == 1) {
        $autoRenewOption = 1;
      }

      if ($dao->html_type == 'Checkbox' && !in_array($dao->duration_interval . $dao->duration_unit, $priceFields[$dao->price_field_id])) {
        // Checkbox fields cannot support auto-renew if they have more than one duration configuration
        // as more than one can be selected. Radio and select are either-or so they can have more than one duration.
        return 0;
      }
      $priceFields[$dao->price_field_id][] = $dao->duration_interval . $dao->duration_unit;
      foreach ($priceFields as $priceFieldID => $durations) {
        if ($priceFieldID != $dao->price_field_id && !in_array($dao->duration_interval . $dao->duration_unit, $durations)) {
          // Another price field has a duration configuration that differs so we can't offer auto-renew.
          return 0;
        }
      }
    }

    return $autoRenewOption;
  }

  /**
   * Retrieve auto renew frequency and interval.
   *
   * @param int $priceSetId
   *   Price set id.
   *
   * @return array
   *   associate array of frequency interval and unit
   */
  public static function getRecurDetails($priceSetId) {
    $query = 'SELECT mt.duration_interval, mt.duration_unit
            FROM civicrm_price_field_value pfv
            INNER JOIN civicrm_membership_type mt ON pfv.membership_type_id = mt.id
            INNER JOIN civicrm_price_field pf ON pfv.price_field_id = pf.id
            WHERE pf.price_set_id = %1 LIMIT 1';

    $params = array(1 => array($priceSetId, 'Integer'));
    $dao = CRM_Core_DAO::executeQuery($query, $params);
    $dao->fetch();
    return array($dao->duration_interval, $dao->duration_unit);
  }

  /**
   * @return object
   */
  public static function eventPriceSetDomainID() {
    return Civi::settings()->get('event_price_set_domain_id');
  }

  /**
   * Update the is_quick_config flag in the db.
   *
   * @param int $id
   *   Id of the database record.
   * @param bool $isQuickConfig we want to set the is_quick_config field.
   *   Value we want to set the is_quick_config field.
   *
   * @return Object
   *   DAO object on success, null otherwise
   */
  public static function setIsQuickConfig($id, $isQuickConfig) {
    return CRM_Core_DAO::setFieldValue('CRM_Price_DAO_PriceSet', $id, 'is_quick_config', $isQuickConfig);
  }

  /**
   * Check if price set id provides option for user to select both auto-renew and non-auto-renew memberships
   *
   * @param int $id
   *
   * @return bool
   */
  public static function isMembershipPriceSetContainsMixOfRenewNonRenew($id) {
    $membershipTypes = self::getMembershipTypesFromPriceSet($id);
    if (!empty($membershipTypes['autorenew']) && !empty($membershipTypes['non_renew'])) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Get an array of the membership types in a price set.
   *
   * @param int $id
   *
   * @return array(
   *   Membership types in the price set
   */
  public static function getMembershipTypesFromPriceSet($id) {
    $query
      = "SELECT      pfv.id, pfv.price_field_id, pfv.name, pfv.membership_type_id, pf.html_type, mt.auto_renew
FROM        civicrm_price_field_value pfv
LEFT JOIN   civicrm_price_field pf ON pf.id = pfv.price_field_id
LEFT JOIN   civicrm_price_set ps ON ps.id = pf.price_set_id
LEFT JOIN   civicrm_membership_type mt ON mt.id = pfv.membership_type_id
WHERE       ps.id = %1
";

    $params = array(1 => array($id, 'Integer'));
    $dao = CRM_Core_DAO::executeQuery($query, $params);

    $membershipTypes = array(
      'all' => array(),
      'autorenew' => array(),
      'autorenew_required' => array(),
      'autorenew_optional' => array(),
    );
    while ($dao->fetch()) {
      if (empty($dao->membership_type_id)) {
        continue;
      }
      $membershipTypes['all'][] = $dao->membership_type_id;
      if (!empty($dao->auto_renew)) {
        $membershipTypes['autorenew'][] = $dao->membership_type_id;
        if ($dao->auto_renew == 2) {
          $membershipTypes['autorenew_required'][] = $dao->membership_type_id;
        }
        else {
          $membershipTypes['autorenew_optional'][] = $dao->membership_type_id;
        }
      }
      else {
        $membershipTypes['non_renew'][] = $dao->membership_type_id;
      }
    }
    return $membershipTypes;
  }

  /**
   * Copy priceSet when event/contibution page is copied
   *
   * @param string $baoName
   *   BAO name.
   * @param int $id
   *   Old event/contribution page id.
   * @param int $newId
   *   Newly created event/contribution page id.
   */
  public static function copyPriceSet($baoName, $id, $newId) {
    $priceSetId = CRM_Price_BAO_PriceSet::getFor($baoName, $id);
    if ($priceSetId) {
      $isQuickConfig = CRM_Core_DAO::getFieldValue('CRM_Price_DAO_PriceSet', $priceSetId, 'is_quick_config');
      if ($isQuickConfig) {
        $copyPriceSet = CRM_Price_BAO_PriceSet::copy($priceSetId);
        CRM_Price_BAO_PriceSet::addTo($baoName, $newId, $copyPriceSet->id);
      }
      else {
        $copyPriceSet = &CRM_Core_DAO::copyGeneric('CRM_Price_DAO_PriceSetEntity',
          array(
            'entity_id' => $id,
            'entity_table' => $baoName,
          ),
          array('entity_id' => $newId)
        );
      }
      // copy event discount
      if ($baoName == 'civicrm_event') {
        $discount = CRM_Core_BAO_Discount::getOptionGroup($id, 'civicrm_event');
        foreach ($discount as $discountId => $setId) {

          $copyPriceSet = &CRM_Price_BAO_PriceSet::copy($setId);

          CRM_Core_DAO::copyGeneric(
            'CRM_Core_DAO_Discount',
            array(
              'id' => $discountId,
            ),
            array(
              'entity_id' => $newId,
              'price_set_id' => $copyPriceSet->id,
            )
          );
        }
      }
    }
  }

  /**
   * Function to set tax_amount and tax_rate in LineItem.
   *
   * @param array $field
   * @param array $lineItem
   * @param int $optionValueId
   * @param float $totalTax
   *
   * @return array
   */
  public static function setLineItem($field, $lineItem, $optionValueId, &$totalTax) {
    // Here we round - i.e. after multiplying by quantity
    if ($field['html_type'] == 'Text') {
      $taxAmount = round($field['options'][$optionValueId]['tax_amount'] * $lineItem[$optionValueId]['qty'], 2);
    }
    else {
      $taxAmount = round($field['options'][$optionValueId]['tax_amount'], 2);
    }
    $taxRate = $field['options'][$optionValueId]['tax_rate'];
    $lineItem[$optionValueId]['tax_amount'] = $taxAmount;
    $lineItem[$optionValueId]['tax_rate'] = $taxRate;
    $totalTax += $taxAmount;
    return $lineItem;
  }

  /**
   * Get the first price set value IDs from a parameters array.
   *
   * In practice this is really used when we only expect one to exist.
   *
   * @param array $params
   *
   * @return array
   *   Array of the ids of the price set values.
   */
  public static function parseFirstPriceSetValueIDFromParams($params) {
    $priceSetValueIDs = self::parsePriceSetValueIDsFromParams($params);
    return reset($priceSetValueIDs);
  }

  /**
   * Get the price set value IDs from a set of parameters
   *
   * @param array $params
   *
   * @return array
   *   Array of the ids of the price set values.
   */
  public static function parsePriceSetValueIDsFromParams($params) {
    $priceSetParams = self::parsePriceSetArrayFromParams($params);
    $priceSetValueIDs = array();
    foreach ($priceSetParams as $priceSetParam) {
      foreach (array_keys($priceSetParam) as $priceValueID) {
        $priceSetValueIDs[] = $priceValueID;
      }
    }
    return $priceSetValueIDs;
  }

  /**
   * Get the price set value IDs from a set of parameters
   *
   * @param array $params
   *
   * @return array
   *   Array of price fields filtered from the params.
   */
  public static function parsePriceSetArrayFromParams($params) {
    $priceSetParams = array();
    foreach ($params as $field => $value) {
      $parts = explode('_', $field);
      if (count($parts) == 2 && $parts[0] == 'price' && is_numeric($parts[1]) && is_array($value)) {
        $priceSetParams[$field] = $value;
      }
    }
    return $priceSetParams;
  }

  /**
   * Get non-deductible amount from price options
   *
   * @param int $priceSetId
   * @param array $lineItem
   *
   * @return int
   *   calculated non-deductible amount.
   */
  public static function getNonDeductibleAmountFromPriceSet($priceSetId, $lineItem) {
    $nonDeductibleAmount = 0;
    if (!empty($lineItem[$priceSetId])) {
      foreach ($lineItem[$priceSetId] as $options) {
        $nonDeductibleAmount += $options['non_deductible_amount'] * $options['qty'];
      }
    }

    return $nonDeductibleAmount;
  }

  /**
   * Get an array of all forms using a given price set.
   *
   * @param int $id
   *
   * @return array
   *   Pages using the price set, keyed by type. e.g
   *   array('
   *     'civicrm_contribution_page' => array(2,5,6),
   *     'civicrm_event' => array(5,6),
   *     'civicrm_event_template' => array(7),
   *   )
   */
  public static function getFormsUsingPriceSet($id) {
    $forms = array();
    $queryString = "
SELECT   entity_table, entity_id
FROM     civicrm_price_set_entity
WHERE    price_set_id = %1";
    $params = array(1 => array($id, 'Integer'));
    $crmFormDAO = CRM_Core_DAO::executeQuery($queryString, $params);

    while ($crmFormDAO->fetch()) {
      $forms[$crmFormDAO->entity_table][] = $crmFormDAO->entity_id;
    }
    return $forms;
  }

  /**
   * @param array $forms
   *   Array of forms that use a price set keyed by entity. e.g
   *   array('
   *     'civicrm_contribution_page' => array(2,5,6),
   *     'civicrm_event' => array(5,6),
   *     'civicrm_event_template' => array(7),
   *   )
   *
   * @return mixed
   *   Array of entities suppliemented with per entity information.
   *   e.g
   *   array('civicrm_event' => array(7 => array('title' => 'x'...))
   *
   * @throws \Exception
   */
  protected static function reformatUsedByFormsWithEntityData($forms) {
    $usedBy = array();
    foreach ($forms as $table => $entities) {
      switch ($table) {
        case 'civicrm_event':
          $ids = implode(',', $entities);
          $queryString = "SELECT ce.id as id, ce.title as title, ce.is_public as isPublic, ce.start_date as startDate, ce.end_date as endDate, civicrm_option_value.label as eventType, ce.is_template as isTemplate, ce.template_title as templateTitle
FROM       civicrm_event ce
LEFT JOIN  civicrm_option_value ON
           ( ce.event_type_id = civicrm_option_value.value )
LEFT JOIN  civicrm_option_group ON
           ( civicrm_option_group.id = civicrm_option_value.option_group_id )
WHERE
         civicrm_option_group.name = 'event_type' AND
           ce.id IN ($ids) AND
           ce.is_active = 1;";
          $crmDAO = CRM_Core_DAO::executeQuery($queryString);
          while ($crmDAO->fetch()) {
            if ($crmDAO->isTemplate) {
              $usedBy['civicrm_event_template'][$crmDAO->id]['title'] = $crmDAO->templateTitle;
              $usedBy['civicrm_event_template'][$crmDAO->id]['eventType'] = $crmDAO->eventType;
              $usedBy['civicrm_event_template'][$crmDAO->id]['isPublic'] = $crmDAO->isPublic;
            }
            else {
              $usedBy[$table][$crmDAO->id]['title'] = $crmDAO->title;
              $usedBy[$table][$crmDAO->id]['eventType'] = $crmDAO->eventType;
              $usedBy[$table][$crmDAO->id]['startDate'] = $crmDAO->startDate;
              $usedBy[$table][$crmDAO->id]['endDate'] = $crmDAO->endDate;
              $usedBy[$table][$crmDAO->id]['isPublic'] = $crmDAO->isPublic;
            }
          }
          break;

        case 'civicrm_contribution_page':
          $ids = implode(',', $entities);
          $queryString = "SELECT cp.id as id, cp.title as title, cp.start_date as startDate, cp.end_date as endDate,ct.name as type
FROM      civicrm_contribution_page cp, civicrm_financial_type ct
WHERE     ct.id = cp.financial_type_id AND
          cp.id IN ($ids) AND
          cp.is_active = 1;";
          $crmDAO = CRM_Core_DAO::executeQuery($queryString);
          while ($crmDAO->fetch()) {
            $usedBy[$table][$crmDAO->id]['title'] = $crmDAO->title;
            $usedBy[$table][$crmDAO->id]['type'] = $crmDAO->type;
            $usedBy[$table][$crmDAO->id]['startDate'] = $crmDAO->startDate;
            $usedBy[$table][$crmDAO->id]['endDate'] = $crmDAO->endDate;
          }
          break;

        case 'civicrm_contribution':
        case 'civicrm_membership':
        case 'civicrm_participant':
          $usedBy[$table] = 1;
          break;

        default:
          CRM_Core_Error::fatal("$table is not supported in PriceSet::usedBy()");
          break;
      }
    }
    return $usedBy;
  }

}
