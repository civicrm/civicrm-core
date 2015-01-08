<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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
 * @copyright CiviCRM LLC (c) 2004-2014
 * $Id$
 *
 */

/**
 * Business object for managing price sets
 *
 */
class CRM_Upgrade_Snapshot_V4p2_Price_BAO_Set extends CRM_Upgrade_Snapshot_V4p2_Price_DAO_Set {

  /**
   * class constructor
   */
  function __construct() {
    parent::__construct();
  }

  /**
   * takes an associative array and creates a price set object
   *
   * @param array $params (reference) an assoc array of name/value pairs
   *
   * @return object CRM_Upgrade_Snapshot_V4p2_Price_DAO_Set object
   * @access public
   * @static
   */
  static function create(&$params) {
    $priceSetBAO = new CRM_Upgrade_Snapshot_V4p2_Price_BAO_Set();
    $priceSetBAO->copyValues($params);
    if (self::eventPriceSetDomainID()) {
      $priceSetBAO->domain_id = CRM_Core_Config::domainID();
    }
    return $priceSetBAO->save();
  }

  /**
   * Takes a bunch of params that are needed to match certain criteria and
   * retrieves the relevant objects. Typically the valid params are only
   * contact_id. We'll tweak this function to be more full featured over a period
   * of time. This is the inverse function of create. It also stores all the retrieved
   * values in the default array
   *
   * @param array $params   (reference ) an assoc array of name/value pairs
   * @param array $defaults (reference ) an assoc array to hold the flattened values
   *
   * @return object CRM_Upgrade_Snapshot_V4p2_Price_DAO_Set object
   * @access public
   * @static
   */
  static function retrieve(&$params, &$defaults) {
    return CRM_Core_DAO::commonRetrieve('CRM_Upgrade_Snapshot_V4p2_Price_DAO_Set', $params, $defaults);
  }

  /**
   * update the is_active flag in the db
   *
   * @param  int $id id of the database record
   * @param $isActive
   *
   * @internal param bool $is_active value we want to set the is_active field
   *
   * @return Object             DAO object on sucess, null otherwise
   * @static
   * @access public
   */
  static function setIsActive($id, $isActive) {
    return CRM_Core_DAO::setFieldValue('CRM_Upgrade_Snapshot_V4p2_Price_DAO_Set', $id, 'is_active', $isActive);
  }

  /**
   * Calculate the default price set id
   * assigned to the contribution/membership etc
   *
   * @param string $entity
   *
   * @return id $priceSetID
   *
   * @access public
   * @static
   *
   */
  public static function getDefaultPriceSet($entity = 'contribution') {
    if ($entity == 'contribution') {
      $entityName = 'default_contribution_amount';
    }
    else if ($entity == 'membership') {
      $entityName = 'default_membership_type_amount';
    }

    $sql = "
SELECT      ps.id AS setID, pfv.price_field_id AS priceFieldID, pfv.id AS priceFieldValueID, pfv.name, pfv.label
FROM        civicrm_price_set ps
LEFT JOIN   civicrm_price_field pf ON pf.`price_set_id` = ps.id
LEFT JOIN   civicrm_price_field_value pfv ON pfv.price_field_id = pf.id
WHERE       ps.name = '{$entityName}'
";

    $dao = CRM_Core_DAO::executeQuery($sql);
    $defaultPriceSet = array();
    while ($dao->fetch()) {
        $defaultPriceSet[$dao->priceFieldValueID]['setID'] = $dao->setID;
        $defaultPriceSet[$dao->priceFieldValueID]['priceFieldID'] = $dao->priceFieldID;
        $defaultPriceSet[$dao->priceFieldValueID]['name'] = $dao->name;
        $defaultPriceSet[$dao->priceFieldValueID]['label'] = $dao->label;
    }


    return $defaultPriceSet;
  }

  /**
   * Get the price set title.
   *
   * @param int $id   id of price set
   *
   * @return string   title
   *
   * @access public
   * @static
   *
   */
  public static function getTitle($id) {
    return CRM_Core_DAO::getFieldValue('CRM_Upgrade_Snapshot_V4p2_Price_DAO_Set', $id, 'title');
  }

  /**
   * Return a list of all forms which use this price set.
   *
   * @param int $id id of price set
   * @param bool|\str $simpleReturn - get raw data. Possible values: 'entity', 'table'
   *
   * @return array
   */
  public static function &getUsedBy($id, $simpleReturn = FALSE) {
    $usedBy = $forms = $tables = array();
    $queryString = "
SELECT   entity_table, entity_id
FROM     civicrm_price_set_entity
WHERE    price_set_id = %1";
    $params = array(1 => array($id, 'Integer'));
    $crmFormDAO = CRM_Core_DAO::executeQuery($queryString, $params);

    while ($crmFormDAO->fetch()) {
      $forms[$crmFormDAO->entity_table][] = $crmFormDAO->entity_id;
      $tables[] = $crmFormDAO->entity_table;
    }
    // Return only tables
    if ($simpleReturn == 'table') {
      return $tables;
    }
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
    // Return only entity data
    if ($simpleReturn == 'entity') {
      return $forms;
    }
    foreach ($forms as $table => $entities) {
      switch ($table) {
        case 'civicrm_event':
          $ids = implode(',', $entities);
          $queryString = "SELECT ce.id as id, ce.title as title, ce.is_public as isPublic, ce.start_date as startDate, ce.end_date as endDate, civicrm_option_value.label as eventType
FROM       civicrm_event ce
LEFT JOIN  civicrm_option_value ON
           ( ce.event_type_id = civicrm_option_value.value )
LEFT JOIN  civicrm_option_group ON
           ( civicrm_option_group.id = civicrm_option_value.option_group_id )
WHERE
         civicrm_option_group.name = 'event_type' AND
           ( ce.is_template IS NULL OR ce.is_template = 0) AND
           ce.id IN ($ids) AND
           ce.is_active = 1;";
          $crmDAO = CRM_Core_DAO::executeQuery($queryString);
          while ($crmDAO->fetch()) {
            $usedBy[$table][$crmDAO->id]['title'] = $crmDAO->title;
            $usedBy[$table][$crmDAO->id]['eventType'] = $crmDAO->eventType;
            $usedBy[$table][$crmDAO->id]['startDate'] = $crmDAO->startDate;
            $usedBy[$table][$crmDAO->id]['endDate'] = $crmDAO->endDate;
            $usedBy[$table][$crmDAO->id]['isPublic'] = $crmDAO->isPublic;
          }
          break;

        case 'civicrm_contribution_page':
          $ids = implode(',', $entities);
          $queryString = "SELECT cp.id as id, cp.title as title, cp.start_date as startDate, cp.end_date as endDate,ct.name as type
FROM      civicrm_contribution_page cp, civicrm_contribution_type ct
WHERE     ct.id = cp.contribution_type_id AND
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

  /**
   * Delete the price set
   *
   * @param int $id Price Set id
   *
   * @return boolean false if fields exist for this set, true if the
   * set could be deleted
   *
   * @access public
   * @static
   */
  public static function deleteSet($id) {
    // remove from all inactive forms
    $usedBy = self::getUsedBy($id);
    if (isset($usedBy['civicrm_event'])) {
      foreach ($usedBy['civicrm_event'] as $eventId => $unused) {
        $eventDAO = new CRM_Event_DAO_Event();
        $eventDAO->id = $eventId;
        $eventDAO->find();
        while ($eventDAO->fetch()) {
          self::removeFrom('civicrm_event', $eventDAO->id);
        }
      }
    }

    // delete price fields
    $priceField = new CRM_Upgrade_Snapshot_V4p2_Price_DAO_Field();
    $priceField->price_set_id = $id;
    $priceField->find();
    while ($priceField->fetch()) {
      // delete options first
      CRM_Upgrade_Snapshot_V4p2_Price_BAO_Field::deleteField($priceField->id);
    }

    $set = new CRM_Upgrade_Snapshot_V4p2_Price_DAO_Set();
    $set->id = $id;
    return $set->delete();
  }

  /**
   * Link the price set with the specified table and id
   *
   * @param string $entityTable
   * @param integer $entityId
   * @param integer $priceSetId
   *
   * @return bool
   */
  public static function addTo($entityTable, $entityId, $priceSetId) {
    // verify that the price set exists
    $dao = new CRM_Upgrade_Snapshot_V4p2_Price_DAO_Set();
    $dao->id = $priceSetId;
    if (!$dao->find()) {
      return FALSE;
    }
    unset($dao);

    $dao = new CRM_Upgrade_Snapshot_V4p2_Price_DAO_SetEntity();
    // find if this already exists
    $dao->entity_id = $entityId;
    $dao->entity_table = $entityTable;
    $dao->find(TRUE);

    // add or update price_set_id
    $dao->price_set_id = $priceSetId;
    return $dao->save();
  }

  /**
   * Delete price set for the given entity and id
   *
   * @param string $entityTable
   * @param integer $entityId
   *
   * @return mixed
   */
  public static function removeFrom($entityTable, $entityId) {
    $dao               = new CRM_Upgrade_Snapshot_V4p2_Price_DAO_SetEntity();
    $dao->entity_table = $entityTable;
    $dao->entity_id    = $entityId;
    return $dao->delete();
  }

  /**
   * Find a price_set_id associatied with the given table, id and usedFor
   * Used For value for events:1, contribution:2, membership:3
   *
   * @param string $entityTable
   * @param int $entityId
   * @param int $usedFor ( price set that extends/used for particular component )
   *
   * @param null $isQuickConfig
   * @param null $setName
   *
   * @return integer|false price_set_id, or false if none found
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
      $sql .= " AND ps.is_quick_config = 0 ";
    }
    $params = array(1 => array($entityTable, 'String'),
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
   * Find a price_set_id associatied with the given option value or  field ID
   *
   * @param array $params (reference) an assoc array of name/value pairs
   *                      array may contain either option id or
   *                      price field id
   *
   * @return price set id on success, null  otherwise
   * @static
   * @access public
   */
  public static function getSetId(&$params) {
    $fid = NULL;

    if ($oid = CRM_Utils_Array::value('oid', $params)) {
      $fieldValue = new CRM_Upgrade_Snapshot_V4p2_Price_DAO_FieldValue();
      $fieldValue->id = $oid;
      if ($fieldValue->find(TRUE)) {
        $fid = $fieldValue->price_field_id;
      }
    }
    else {
      $fid = CRM_Utils_Array::value('fid', $params);
    }

    if (isset($fid)) {
      return CRM_Core_DAO::getFieldValue('CRM_Upgrade_Snapshot_V4p2_Price_DAO_Field', $fid, 'price_set_id');
    }

    return NULL;
  }

  /**
   * Return an associative array of all price sets
   *
   * @param bool $withInactive whether or not to include inactive entries
   * @param bool|string $extendComponentName name of the component like 'CiviEvent','CiviContribute'
   *
   * @return array associative array of id => name
   */
  public static function getAssoc($withInactive = FALSE, $extendComponentName = FALSE) {
    $query = "
    SELECT
       DISTINCT ( price_set_id ) as id, title
    FROM
       civicrm_price_field,
       civicrm_price_set
    WHERE
       civicrm_price_set.id = civicrm_price_field.price_set_id  AND is_quick_config = 0 ";

    if (!$withInactive) {
      $query .= " AND civicrm_price_set.is_active = 1 ";
    }

    if (self::eventPriceSetDomainID()) {
      $query .= " AND civicrm_price_set.domain_id = " . CRM_Core_Config::domainID();
    }

    $priceSets = array();

    if ($extendComponentName) {
      $componentId = CRM_Core_Component::getComponentID($extendComponentName);
      if (!$componentId) {
        return $priceSets;
      }
      $query .= " AND civicrm_price_set.extends LIKE '%$componentId%' ";
    }

    $dao = CRM_Core_DAO::executeQuery($query);
    while ($dao->fetch()) {
      $priceSets[$dao->id] = $dao->title;
    }
    return $priceSets;
  }

  /**
   * Get price set details
   *
   * An array containing price set details (including price fields) is returned
   *
   * @param $setID
   * @param bool $required
   * @param bool $validOnly
   *
   * @internal param int $setId - price set id whose details are needed
   *
   * @return array $setTree - array consisting of field details
   */
  public static function getSetDetail($setID, $required = TRUE, $validOnly = FALSE) {
    // create a new tree
    $setTree = array();
    $select = $from = $where = $orderBy = '';

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

    $params    = array();
    $params[1] = array($setID, 'Integer');
    $where     = '
WHERE price_set_id = %1
AND is_active = 1
';
    $dateSelect = '';
    if ($validOnly) {
      $currentTime = date('YmdHis');
      $dateSelect = "
AND ( active_on IS NULL OR active_on <= {$currentTime} )
AND ( expire_on IS NULL OR expire_on >= {$currentTime} )
";
    }

    $orderBy = ' ORDER BY weight';

    $sql = $select . $from . $where . $dateSelect . $orderBy;

    $dao = CRM_Core_DAO::executeQuery($sql, $params);

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
      $setTree[$setID]['fields'][$fieldID]['options'] = CRM_Upgrade_Snapshot_V4p2_Price_BAO_Field::getOptions($fieldID, FALSE);
    }

    // also get the pre and post help from this price set
    $sql = "
SELECT extends, contribution_type_id, help_pre, help_post, is_quick_config
FROM   civicrm_price_set
WHERE  id = %1";
    $dao = CRM_Core_DAO::executeQuery($sql, $params);
    if ($dao->fetch()) {
      $setTree[$setID]['extends'] = $dao->extends;
      $setTree[$setID]['contribution_type_id'] = $dao->contribution_type_id;
      $setTree[$setID]['help_pre'] = $dao->help_pre;
      $setTree[$setID]['help_post'] = $dao->help_post;
      $setTree[$setID]['is_quick_config'] = $dao->is_quick_config;
    }
    return $setTree;
  }

  /**
   * @param $form
   * @param $id
   * @param string $entityTable
   * @param bool $validOnly
   * @param null $priceSetId
   *
   * @return bool|false|int|null
   */
  static function initSet(&$form, $id, $entityTable = 'civicrm_event', $validOnly = FALSE, $priceSetId = NULL) {
    if (!$priceSetId) {
      $priceSetId = self::getFor($entityTable, $id);
    }

    //check if priceset is is_config
    if (is_numeric($priceSetId)) {
      if (CRM_Core_DAO::getFieldValue('CRM_Upgrade_Snapshot_V4p2_Price_DAO_Set', $priceSetId, 'is_quick_config') && $form->getVar('_name') != 'Participant') {
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
          $form->_values['line_items'] = CRM_Upgrade_Snapshot_V4p2_Price_BAO_LineItem::getLineItems($entityId, $entity);
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
          $optionsCountDeails = array();
          if (!empty($form->_priceSet['fields'])) {
            foreach ($form->_priceSet['fields'] as $field) {
              foreach ($field['options'] as $option) {
                $count = CRM_Utils_Array::value('count', $option, 0);
                $optionsCountDeails['fields'][$field['id']]['options'][$option['id']] = $count;
              }
            }
          }
          $form->_priceSet['optionsCountDetails'] = $optionsCountDeails;
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
   * @param $fields
   * @param $params
   * @param $lineItem
   */
  static function processAmount(&$fields, &$params, &$lineItem) {
    // using price set
    $totalPrice = 0;
    $radioLevel = $checkboxLevel = $selectLevel = $textLevel = array();

    foreach ($fields as $id => $field) {
      if (empty($params["price_{$id}"]) ||
        (empty($params["price_{$id}"]) && $params["price_{$id}"] == NULL)
      ) {
        // skip if nothing was submitted for this field
        continue;
      }

      switch ($field['html_type']) {
        case 'Text':
          $params["price_{$id}"] = array(key($field['options']) => $params["price_{$id}"]);
          CRM_Upgrade_Snapshot_V4p2_Price_BAO_LineItem::format($id, $params, $field, $lineItem);
          $totalPrice += $lineItem[key($field['options'])]['line_total'];
          break;

        case 'Radio':
          //special case if user select -none-
          if ($params["price_{$id}"] <= 0) {
            continue;
          }
        $params["price_{$id}"] = array($params["price_{$id}"] => 1);
        $optionValueId = CRM_Utils_Array::key(1, $params["price_{$id}"]);
        $optionLabel = CRM_Utils_Array::value('label', $field['options'][$optionValueId]);
        $params['amount_priceset_level_radio'] = array();
        $params['amount_priceset_level_radio'][$optionValueId] = $optionLabel;
        if (isset($radioLevel)) {
          $radioLevel = array_merge($radioLevel,
            array_keys($params['amount_priceset_level_radio'])
          );
        }
        else {
          $radioLevel = array_keys($params['amount_priceset_level_radio']);
        }
        CRM_Upgrade_Snapshot_V4p2_Price_BAO_LineItem::format($id, $params, $field, $lineItem);
        $totalPrice += $lineItem[$optionValueId]['line_total'];
        break;

        case 'Select':
          $params["price_{$id}"] = array($params["price_{$id}"] => 1);
          $optionValueId = CRM_Utils_Array::key(1, $params["price_{$id}"]);
          $optionLabel = $field['options'][$optionValueId]['label'];
          $params['amount_priceset_level_select'] = array();
          $params['amount_priceset_level_select'][CRM_Utils_Array::key(1, $params["price_{$id}"])] = $optionLabel;
          if (isset($selectLevel)) {
          $selectLevel = array_merge($selectLevel, array_keys($params['amount_priceset_level_select']));
        }
        else {
        $selectLevel = array_keys($params['amount_priceset_level_select']);
      }
      CRM_Upgrade_Snapshot_V4p2_Price_BAO_LineItem::format($id, $params, $field, $lineItem);
      $totalPrice += $lineItem[$optionValueId]['line_total'];
      break;
    case 'CheckBox':
      $params['amount_priceset_level_checkbox'] = $optionIds = array();
      foreach ($params["price_{$id}"] as $optionId => $option) {
        $optionIds[] = $optionId;
        $optionLabel = $field['options'][$optionId]['label'];
        $params['amount_priceset_level_checkbox']["{$field['options'][$optionId]['id']}"] = $optionLabel;
        if (isset($checkboxLevel)) {
          $checkboxLevel = array_unique(array_merge(
            $checkboxLevel,
            array_keys($params['amount_priceset_level_checkbox'])
          )
        );
        }
        else {
          $checkboxLevel = array_keys($params['amount_priceset_level_checkbox']);
        }
      }
      CRM_Upgrade_Snapshot_V4p2_Price_BAO_LineItem::format($id, $params, $field, $lineItem);
      foreach ($optionIds as $optionId) {
        $totalPrice += $lineItem[$optionId]['line_total'];
      }
      break;
      }
    }

    $amount_level = array();
    $totalParticipant = 0;
    if (is_array($lineItem)) {
      foreach ($lineItem as $values) {
        $totalParticipant += $values['participant_count'];
        if ($values['html_type'] == 'Text') {
          $amount_level[] = $values['label'] . ' - ' . $values['qty'];
          continue;
        }
        $amount_level[] = $values['label'];
      }
    }

    $displayParticipantCount = '';
    if ($totalParticipant > 0) {
      $displayParticipantCount = ' Participant Count -' . $totalParticipant;
    }

    $params['amount_level'] = CRM_Core_DAO::VALUE_SEPARATOR . implode(CRM_Core_DAO::VALUE_SEPARATOR, $amount_level) . $displayParticipantCount . CRM_Core_DAO::VALUE_SEPARATOR;
    $params['amount'] = $totalPrice;
  }

  /**
   * Function to build the price set form.
   *
   * @param $form
   *
   * @return void
   * @access public
   */
static function buildPriceSet(&$form) {
$priceSetId = $form->get('priceSetId');
$userid = $form->getVar('_userID');
if (!$priceSetId) {
  return;
}

$validFieldsOnly = TRUE;
$className = CRM_Utils_System::getClassName($form);
if (in_array($className, array(
  'CRM_Contribute_Form_Contribution', 'CRM_Member_Form_Membership'))) {
$validFieldsOnly = FALSE;
}

$priceSet           = self::getSetDetail($priceSetId, TRUE, $validFieldsOnly);
$form->_priceSet    = CRM_Utils_Array::value($priceSetId, $priceSet);
$form->_quickConfig = $quickConfig = 0;
if (CRM_Core_DAO::getFieldValue('CRM_Upgrade_Snapshot_V4p2_Price_DAO_Set', $priceSetId, 'is_quick_config')) {
  $quickConfig = 1;
}

$form->assign('quickConfig', $quickConfig);
if ($className == "CRM_Contribute_Form_Contribution_Main") {
$form->_quickConfig = $quickConfig;
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

// call the hook.
CRM_Utils_Hook::buildAmount($component, $form, $feeBlock);

foreach ($feeBlock as $field) {
if (CRM_Utils_Array::value('visibility', $field) == 'public' ||
!$validFieldsOnly
) {
$options = CRM_Utils_Array::value('options', $field);
if ($className == 'CRM_Contribute_Form_Contribution_Main' && $component = 'membership') {
$checklifetime = self::checkCurrentMembership($options, $userid);
if ($checklifetime) {
$form->assign('ispricelifetime', TRUE);
}
}
if (!is_array($options)) {
  continue;
}
CRM_Upgrade_Snapshot_V4p2_Price_BAO_Field::addQuickFormElement($form,
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

/**
 * Function to check the current Membership
 * having end date null.
 */
static function checkCurrentMembership(&$options, $userid) {
if (!$userid || empty($options)) {
return;
}
static $_contact_memberships = array();
$checklifetime = FALSE;
foreach ($options as $key => $value) {
if (!empty($value['membership_type_id'])) {
if (!isset($_contact_memberships[$userid][$value['membership_type_id']])) {
$_contact_memberships[$userid][$value['membership_type_id']] = CRM_Member_BAO_Membership::getContactMembership($userid, $value['membership_type_id'], FALSE);
}
$currentMembership = $_contact_memberships[$userid][$value['membership_type_id']];
if (!empty($currentMembership) && empty($currentMembership['end_date'])) {
unset($options[$key]);
$checklifetime = TRUE;
}
}
}
if ($checklifetime) {
return TRUE;
}
else {
return FALSE;
}
}

  /**
   * Function to set daefult the price set fields.
   *
   * @param $form
   * @param $defaults
   *
   * @return array $defaults
   * @access public
   */
static function setDefaultPriceSet(&$form, &$defaults) {
if (!isset($form->_priceSet) || empty($form->_priceSet['fields'])) {
return $defaults;
}

foreach ($form->_priceSet['fields'] as $key => $val) {
foreach ($val['options'] as $keys => $values) {
if ($values['is_default']) {
if ($val['html_type'] == 'CheckBox') {
$defaults["price_{$key}"][$keys] = 1;
}
else {
$defaults["price_{$key}"] = $keys;
}
}
}
}
return $defaults;
}

/**
 * Get field ids of a price set
 *
 * @param int id Price Set id
 *
 * @return array of the field ids
 *
 * @access public
 * @static
 */
public static function getFieldIds($id) {
$priceField = new CRM_Upgrade_Snapshot_V4p2_Price_DAO_Field();
$priceField->price_set_id = $id;
$priceField->find();
while ($priceField->fetch()) {
$var[] = $priceField->id;
}
return $var;
}

/**
 * This function is to make a copy of a price set, including
 * all the fields
 *
 * @param int $id the price set id to copy
 *
 * @return the copy object
 * @access public
 * @static
 */
static function copy($id) {
$maxId = CRM_Core_DAO::singleValueQuery("SELECT max(id) FROM civicrm_price_set");

$title = ts('[Copy id %1]', array(1 => $maxId + 1));
$fieldsFix = array(
  'suffix' => array('title' => ' ' . $title,
'name' => '__Copy_id_' . ($maxId + 1) . '_',
),
);

$copy = &CRM_Core_DAO::copyGeneric('CRM_Upgrade_Snapshot_V4p2_Price_DAO_Set',
array('id' => $id),
NULL,
$fieldsFix
);

//copying all the blocks pertaining to the price set
$copyPriceField = &CRM_Core_DAO::copyGeneric('CRM_Upgrade_Snapshot_V4p2_Price_DAO_Field',
array('price_set_id' => $id),
array('price_set_id' => $copy->id)
);
if (!empty($copyPriceField)) {
$price = array_combine(self::getFieldIds($id), self::getFieldIds($copy->id));

//copy option group and values
foreach ($price as $originalId => $copyId) {
CRM_Core_DAO::copyGeneric('CRM_Upgrade_Snapshot_V4p2_Price_DAO_FieldValue',
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
   * This function is to check price set permission
   *
   * @param int $sid the price set id
   *
   * @return bool
   */
function checkPermission($sid) {
if ($sid &&
self::eventPriceSetDomainID()
) {
$domain_id = CRM_Core_DAO::getFieldValue('CRM_Upgrade_Snapshot_V4p2_Price_DAO_Set', $sid, 'domain_id', 'id');
if (CRM_Core_Config::domainID() != $domain_id) {
CRM_Core_Error::fatal(ts('You do not have permission to access this page'));
}
}
return TRUE;
}

  /**
   * Get the sum of participant count
   * for all fields of given price set.
   *
   * @param int $sid the price set id
   *
   * @param bool $onlyActive
   *
   * @return int|null|string
   * @access public
   * @static
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

static $pricesetFieldCount;
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
   */public static function getMembershipCount($ids) {
$queryString = "
SELECT       count( pfv.id ) AS count, pfv.id AS id
FROM         civicrm_price_field_value pfv
INNER JOIN    civicrm_membership_type mt ON mt.id = pfv.membership_type_id
WHERE        pfv.id IN ( $ids )
GROUP BY     mt.member_of_contact_id";

$crmDAO = CRM_Core_DAO::executeQuery($queryString);
$count = array();

while ($crmDAO->fetch()) {
$count[$crmDAO->id] = $crmDAO->count;
}

return $count;
}

/**
 * Function to check if auto renew option should be shown
 *
 * @param int $priceSetId price set id
 *
 * @return int $autoRenewOption ( 0:hide, 1:optional 2:required )
 */
public static function checkAutoRenewForPriceSet($priceSetId) {
// auto-renew option should be visible if membership types associated with all the fields has
// been set for auto-renew option
// Auto renew checkbox should be frozen if for all the membership type auto renew is required

// get the membership type auto renew option and check if required or optional
$query = 'SELECT mt.auto_renew, mt.duration_interval, mt.duration_unit
            FROM civicrm_price_field_value pfv
            INNER JOIN civicrm_membership_type mt ON pfv.membership_type_id = mt.id
            INNER JOIN civicrm_price_field pf ON pfv.price_field_id = pf.id
            WHERE pf.price_set_id = %1
            AND   pf.is_active = 1
            AND   pfv.is_active = 1';

$params = array(1 => array($priceSetId, 'Integer'));

$dao             = CRM_Core_DAO::executeQuery($query, $params);
$autoRenewOption = 2;
$interval        = $unit = array();
while ($dao->fetch()) {
if (!$dao->auto_renew) {
$autoRenewOption = 0;
break;
}
if ($dao->auto_renew == 1) {
$autoRenewOption = 1;
}

$interval[$dao->duration_interval] = $dao->duration_interval;
$unit[$dao->duration_unit] = $dao->duration_unit;
}

if (count($interval) == 1 && count($unit) == 1 && $autoRenewOption > 0) {
return $autoRenewOption;
}
else {
return 0;
}
}

  /**
   * Function to retrieve auto renew frequency and interval
   *
   * @param int $priceSetId price set id
   *
   * @return array associate array of frequency interval and unit
   * @static
   * @access public
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
  static function eventPriceSetDomainID() {
    return CRM_Core_BAO_Setting::getItem(CRM_Core_BAO_Setting::MULTISITE_PREFERENCES_NAME,
           'event_price_set_domain_id',
           NULL, FALSE
           );
  }

  /**
   * update the is_quick_config flag in the db
   *
   * @param  int      $id             id of the database record
   * @param  boolean  $isQuickConfig  value we want to set the is_quick_config field
   *
   * @return Object                   DAO object on sucess, null otherwise
   * @static
   * @access public
   */
  static function setIsQuickConfig($id, $isQuickConfig) {
    return CRM_Core_DAO::setFieldValue('CRM_Upgrade_Snapshot_V4p2_Price_DAO_Set', $id, 'is_quick_config', $isQuickConfig);
  }
}

