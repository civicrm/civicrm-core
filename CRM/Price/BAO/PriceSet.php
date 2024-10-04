<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */

use Civi\Api4\PriceField;
use Civi\Api4\PriceFieldValue;
use Civi\Api4\PriceSet;

/**
 * Business object for managing price sets.
 *
 */
class CRM_Price_BAO_PriceSet extends CRM_Price_DAO_PriceSet implements \Civi\Core\HookInterface {

  /**
   * Static field for default price set details.
   *
   * @var array
   */
  public static $_defaultPriceSet = NULL;

  /**
   * Takes an associative array and creates a price set object.
   *
   * @deprecated
   *   Use writeRecord
   *
   * @param array $params
   *
   * @return CRM_Price_BAO_PriceSet
   */
  public static function create($params) {
    return self::writeRecord($params);
  }

  /**
   * Event fired before an action is taken on an ACL record.
   * @param \Civi\Core\Event\PreEvent $event
   */
  public static function self_hook_civicrm_pre(\Civi\Core\Event\PreEvent $event) {
    if ($event->action === 'create' || $event->action === 'edit') {
      if (self::eventPriceSetDomainID()) {
        $event->params['domain_id'] = CRM_Core_Config::domainID();
      }
    }
    unset(\Civi::$statics['CRM_Core_PseudoConstant']);
  }

  /**
   * @deprecated
   * @param array $params
   * @param array $defaults
   * @return self|null
   */
  public static function retrieve($params, &$defaults) {
    return self::commonRetrieve(self::class, $params, $defaults);
  }

  /**
   * @deprecated - this bypasses hooks.
   * @param int $id
   * @param bool $isActive
   * @return bool
   */
  public static function setIsActive($id, $isActive) {
    CRM_Core_Error::deprecatedFunctionWarning('writeRecord');
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
    if (isset(\Civi::$statics[__CLASS__][$entity])) {
      return \Civi::$statics[__CLASS__][$entity];
    }
    $priceSetName = ($entity === 'membership') ? 'default_membership_type_amount' : 'default_contribution_amount';

    $sql = "
SELECT      ps.id AS setID, pfv.price_field_id AS priceFieldID, pfv.id AS priceFieldValueID, pfv.name, pfv.label, pfv.membership_type_id, pfv.amount, pfv.financial_type_id
FROM        civicrm_price_set ps
LEFT JOIN   civicrm_price_field pf ON pf.`price_set_id` = ps.id
LEFT JOIN   civicrm_price_field_value pfv ON pfv.price_field_id = pf.id
WHERE       ps.name = '{$priceSetName}'
";

    $dao = CRM_Core_DAO::executeQuery($sql);
    while ($dao->fetch()) {
      \Civi::$statics[__CLASS__][$entity][$dao->priceFieldValueID] = [
        'setID' => $dao->setID,
        'priceFieldID' => $dao->priceFieldID,
        'name' => $dao->name,
        'label' => $dao->label,
        'priceFieldValueID' => $dao->priceFieldValueID,
        'membership_type_id' => $dao->membership_type_id,
        'amount' => $dao->amount,
        'financial_type_id' => $dao->financial_type_id,
      ];
    }

    return \Civi::$statics[__CLASS__][$entity];
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
    $usedBy = [];
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
      $params = [1 => [$id, 'Integer']];
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
    $params = [
      1 => [$entityTable, 'String'],
      2 => [$entityId, 'Integer'],
    ];
    if ($usedFor) {
      $sql .= " AND ps.extends LIKE '%%3%' ";
      $params[3] = [$usedFor, 'Integer'];
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
   * @return int|null
   *   price set id on success, null  otherwise
   */
  public static function getSetId(&$params) {
    $fid = NULL;

    $oid = $params['oid'] ?? NULL;
    if ($oid) {
      $fieldValue = new CRM_Price_DAO_PriceFieldValue();
      $fieldValue->id = $oid;
      if ($fieldValue->find(TRUE)) {
        $fid = $fieldValue->price_field_id;
      }
    }
    else {
      $fid = $params['fid'] ?? NULL;
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

    $priceSets = [];

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
   *   Deprecated.
   * @param bool $doNotIncludeExpiredFields
   *   Should only fields where today's date falls within the valid range be returned?
   *
   * @return array
   *   Array consisting of field details
   */
  public static function getSetDetail($setID, $required = TRUE, $doNotIncludeExpiredFields = FALSE) {
    // create a new tree
    $setTree = [];

    $priceFields = [
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
    ];

    // create select
    $select = 'SELECT ' . implode(',', $priceFields);
    $from = ' FROM civicrm_price_field';

    $params = [
      1 => [$setID, 'Integer'],
    ];
    $currentTime = date('YmdHis');
    $where = "
WHERE price_set_id = %1
AND is_active = 1
";
    $dateSelect = '';
    if ($doNotIncludeExpiredFields) {
      $dateSelect = "
AND ( active_on IS NULL OR active_on <= {$currentTime} )
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
      $fieldID = (int) $dao->id;

      $setTree[$setID]['fields'][$fieldID] = [];
      $setTree[$setID]['fields'][$fieldID]['id'] = $fieldID;

      foreach ($priceFields as $field) {
        if ($field === 'id') {
          continue;
        }

        if ($field === 'visibility_id') {
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
      $setTree[$setID]['financial_type_id'] = (int) $dao->financial_type_id;
      $setTree[$setID]['help_pre'] = $dao->help_pre;
      $setTree[$setID]['help_post'] = $dao->help_post;
      $setTree[$setID]['is_quick_config'] = (bool) $dao->is_quick_config;
      $setTree[$setID]['min_amount'] = (float) $dao->min_amount;
    }
    return $setTree;
  }

  /**
   * Is the price set 'quick config'.
   *
   * Quick config price sets have a simplified configuration on
   * contribution and event pages.
   *
   * @param int $priceSetID
   *
   * @return bool
   */
  public static function isQuickConfig(int $priceSetID): bool {
    return (bool) self::getCachedPriceSetDetail($priceSetID)['is_quick_config'];
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
   * @param int $priceSetID
   *
   * @deprecated since 5.69 will be removed around 5.85. This function is still in use but marking deprecated to make it clear that
   * we are moving away from it. There is no function that has the guaranteed stable signature
   * that would allow us to support if from outside of core so if using this or the core alternative
   * from an extension you need to rely on unit tests to keep your code stable. Within core we
   * already have good test cover on code that calls this.
   *
   * The recommended approach within core is something like
   *
   * private function initializeOrder(): void {
   *  $this->order = new CRM_Financial_BAO_Order();
   *  $this->order->setForm($this);
   *  $this->order->setPriceSelectionFromUnfilteredInput($this->>getSubmittedValues());
   * }
   *
   * $lineItems = $this->order->getLineItems();
   *
   * @todo $priceSetID is a pseudoparam for permit override - we should stop passing it where we
   * don't specifically need it & find a better way where we do.
   */
  public static function processAmount($fields, &$params, &$lineItem = [], $priceSetID = NULL) {
    // using price set
    foreach ($fields as $id => $field) {
      if (empty($params["price_{$id}"]) ||
        (empty($params["price_{$id}"]) && $params["price_{$id}"] == NULL)
      ) {
        // skip if nothing was submitted for this field
        continue;
      }

      [$params, $lineItem] = self::getLine($params, $lineItem, $priceSetID, $field, $id);
    }
    $order = new CRM_Financial_BAO_Order();
    $order->setLineItems((array) $lineItem);
    $params['amount_level'] = $order->getAmountLevel();
    $params['amount'] = $order->getTotalAmount();
    $params['tax_amount'] = $order->getTotalTaxAmount();
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
  public static function getAmountLevelText($params): string {
    $priceSetID = $params['priceSetId'];
    $priceFieldSelection = self::filterPriceFieldsFromParams($priceSetID, $params);
    $priceFieldMetadata = self::getCachedPriceSetDetail($priceSetID);

    $amount_level = [];
    foreach ($priceFieldMetadata['fields'] as $field) {
      if (!empty($priceFieldSelection[$field['id']])) {
        // We deliberately & specifically exclude contribution amount as it has a specific meaning.
        // ie. it represents the default price field for a contribution. Another approach would be not
        // to give it a label if we don't want it to show.
        if ($field['label'] !== ts('Contribution Amount')) {
          $amount_level[] = $field['label'] . ($field['is_enter_qty'] ? ' - ' . (float) $params['price_' . $field['id']] : '');
        }
      }
    }
    return CRM_Core_DAO::VALUE_SEPARATOR . implode(CRM_Core_DAO::VALUE_SEPARATOR, $amount_level) . CRM_Core_DAO::VALUE_SEPARATOR;
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
    $return = [];
    foreach ($priceSet['fields'] as $field) {
      if (!empty($params['price_' . $field['id']])) {
        $return[$field['id']] = $params['price_' . $field['id']];
      }
    }
    return $return;
  }

  /**
   * Get PriceSet + Fields + FieldValues nested, with caching.
   *
   * This gets the same values as getSet but uses apiv4 for more
   * predictability & better variable typing.
   *
   * We seem to be passing this array around in a painful way - presumably to avoid the hit
   * of loading it - so lets make it callable with caching.
   *
   * @param int $priceSetID
   *
   * @return array
   *
   * @noinspection PhpUnhandledExceptionInspection
   */
  public static function getCachedPriceSetDetail(int $priceSetID): array {
    $cacheKey = __CLASS__ . __FUNCTION__ . '_' . $priceSetID;
    $cache = CRM_Utils_Cache::singleton();
    $data = $cache->get($cacheKey);
    if (empty($data)) {
      $data = PriceSet::get(FALSE)
        ->addWhere('id', '=', $priceSetID)
        ->addSelect('*', 'visibility_id:name', 'extends:name')
        ->execute()->first();
      $data['fields'] = (array) PriceField::get(FALSE)
        ->addWhere('price_set_id', '=', $priceSetID)
        ->addWhere('is_active', '=', TRUE)
        ->addSelect('*', 'visibility_id:name')
        ->addOrderBy('weight', 'ASC')
        ->execute()->indexBy('id');
      foreach ($data['fields'] as &$field) {
        $field['options'] = [];
        // Add in visibility because Smarty templates expect it and it is hard to adjust them to colon format.
        $field['visibility'] = $field['visibility_id:name'];
      }
      $select = ['*', 'visibility_id:name'];
      if (CRM_Core_Component::isEnabled('CiviMember')) {
        $select[] = 'membership_type_id.name';
      }
      $options = PriceFieldValue::get(FALSE)
        ->addWhere('price_field_id', 'IN', array_keys($data['fields']))
        ->addWhere('is_active', '=', TRUE)
        ->setSelect($select)
        ->addOrderBy('weight', 'ASC')
        ->execute();
      $taxRates = CRM_Core_PseudoConstant::getTaxRates();
      foreach ($options as $option) {
        // Add in visibility because Smarty templates expect it and it is hard to adjust them to colon format.
        $option['visibility'] = $option['visibility_id:name'];
        $option['tax_rate'] = (float) ($taxRates[$option['financial_type_id']] ?? 0);
        $option['tax_amount'] = (float) ($option['tax_rate'] ? CRM_Contribute_BAO_Contribution_Utils::calculateTaxAmount($option['amount'], $option['tax_rate'])['tax_amount'] : 0);
        $data['fields'][$option['price_field_id']]['options'][$option['id']] = $option;
      }
      $cache->set($cacheKey, $data);
    }
    return $data;
  }

  /**
   * Build the price set form.
   *
   * @param CRM_Core_Form $form
   * @param string|null $component
   * @param bool $validFieldsOnly
   *
   * @return void
   *
   * @deprecated since 5.68. Will be removed around 5.80.
   */
  public static function buildPriceSet(&$form, $component = NULL, $validFieldsOnly = TRUE) {
    CRM_Core_Error::deprecatedWarning('internal function');
    $priceSetId = $form->get('priceSetId');
    if (!$priceSetId) {
      return;
    }

    $className = CRM_Utils_System::getClassName($form);

    $priceSet = self::getSetDetail($priceSetId, TRUE, $validFieldsOnly);
    $form->_priceSet = $priceSet[$priceSetId] ?? NULL;
    $validPriceFieldIds = array_keys($form->_priceSet['fields']);

    // Mark which field should have the auto-renew checkbox, if any. CRM-18305
    if (!empty($form->_membershipTypeValues) && is_array($form->_membershipTypeValues)) {
      $autoRenewMembershipTypes = [];
      foreach ($form->_membershipTypeValues as $membershipTypeValue) {
        if ($membershipTypeValue['auto_renew']) {
          $autoRenewMembershipTypes[] = $membershipTypeValue['id'];
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
    $form->_priceSet['id'] ??= $priceSetId;
    $form->assign('priceSet', $form->_priceSet);

    if ($className == 'CRM_Contribute_Form_Contribution_Main') {
      $feeBlock = &$form->_values['fee'];
    }
    else {
      $feeBlock = &$form->_priceSet['fields'];
    }

    // Call the buildAmount hook.
    CRM_Utils_Hook::buildAmount($component ?? 'contribution', $form, $feeBlock);

    $hideAdminValues = !CRM_Core_Permission::check('edit contributions');
    // CRM-14492 Admin price fields should show up on event registration if user has 'administer CiviCRM' permissions
    $adminFieldVisible = CRM_Core_Permission::check('administer CiviCRM');
    $checklifetime = FALSE;
    foreach ($feeBlock as $id => $field) {
      if (($field['visibility'] ?? NULL) == 'public' ||
        (($field['visibility'] ?? NULL) == 'admin' && $adminFieldVisible == TRUE) ||
        !$validFieldsOnly
      ) {
        $options = $field['options'] ?? NULL;
        if ($className == 'CRM_Contribute_Form_Contribution_Main' && $component = 'membership') {
          $contactId = $form->getVar('_membershipContactID');
          if ($contactId && $options) {
            $contactsLifetimeMemberships = CRM_Member_BAO_Membership::getAllContactMembership($contactId, FALSE, TRUE);
            $contactsLifetimeMembershipTypes = array_column($contactsLifetimeMemberships, 'membership_type_id');
            $memTypeIdsInPriceField = array_column($options, 'membership_type_id');
            $isCurrentMember = (bool) array_intersect($memTypeIdsInPriceField, $contactsLifetimeMembershipTypes);
            $checklifetime = $checklifetime ?: $isCurrentMember;
          }
        }

        $formClasses = ['CRM_Contribute_Form_Contribution', 'CRM_Member_Form_Membership'];

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
            $field['is_required'] ?? FALSE,
            NULL,
            $options
          );
        }
      }
    }
    $form->assign('ispricelifetime', $checklifetime);

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
    $priceSet = civicrm_api3('PriceSet', 'getsingle', ['id' => $id]);

    $newTitle = preg_replace('/\[Copy id \d+\]$/', "", $priceSet['title']);
    $title = ts('[Copy id %1]', [1 => $maxId + 1]);
    $fieldsFix = [
      'replace' => [
        'title' => trim($newTitle) . ' ' . $title,
        'name' => substr($priceSet['name'], 0, 20) . 'price_set_' . ($maxId + 1),
      ],
    ];

    $copy = CRM_Core_DAO::copyGeneric('CRM_Price_DAO_PriceSet',
      ['id' => $id],
      NULL,
      $fieldsFix
    );

    //copying all the blocks pertaining to the price set
    $copyPriceField = CRM_Core_DAO::copyGeneric('CRM_Price_DAO_PriceField',
      ['price_set_id' => $id],
      ['price_set_id' => $copy->id]
    );
    if (!empty($copyPriceField)) {
      $price = array_combine(self::getFieldIds($id), self::getFieldIds($copy->id));

      //copy option group and values
      foreach ($price as $originalId => $copyId) {
        CRM_Core_DAO::copyGeneric('CRM_Price_DAO_PriceFieldValue',
          ['price_field_id' => $originalId],
          ['price_field_id' => $copyId]
        );
      }
    }
    $copy->save();

    CRM_Utils_Hook::copy('Set', $copy, $id);
    unset(\Civi::$statics['CRM_Core_PseudoConstant']);
    return $copy;
  }

  /**
   * check price set permission.
   *
   * @param int $sid
   *   The price set id.
   *
   * @return bool
   * @throws \CRM_Core_Exception
   */
  public static function checkPermission($sid) {
    if ($sid && self::eventPriceSetDomainID()) {
      $domain_id = CRM_Core_DAO::getFieldValue('CRM_Price_DAO_PriceSet', $sid, 'domain_id', 'id');
      if (CRM_Core_Config::domainID() != $domain_id) {
        CRM_Core_Error::statusBounce(ts('You do not have permission to access this page.'));
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

    static $pricesetFieldCount = [];
    if (!isset($pricesetFieldCount[$sid])) {
      $sql = "
    SELECT  sum(value.count) as totalCount
      FROM  civicrm_price_field_value  value
INNER JOIN  civicrm_price_field field ON ( field.id = value.price_field_id )
INNER JOIN  civicrm_price_set pset    ON ( pset.id = field.price_set_id )
     WHERE  pset.id = %1
            $where";

      $count = CRM_Core_DAO::singleValueQuery($sql, [1 => [$sid, 'Positive']]);
      $pricesetFieldCount[$sid] = ($count) ? $count : 0;
    }

    return $pricesetFieldCount[$sid];
  }

  /**
   * Return a count of priceFieldValueIDs that are memberships by organisation and membership type
   *
   * @param string $priceFieldValueIDs
   *   Comma separated string of priceFieldValue IDs
   *
   * @return array
   *   Returns an array of counts by membership organisation
   */
  public static function getMembershipCount($priceFieldValueIDs) {
    $queryString = "
SELECT       count( pfv.id ) AS count, mt.member_of_contact_id AS id
FROM         civicrm_price_field_value pfv
INNER JOIN    civicrm_membership_type mt ON mt.id = pfv.membership_type_id
WHERE        pfv.id IN ( $priceFieldValueIDs )
GROUP BY     mt.member_of_contact_id ";

    $crmDAO = CRM_Core_DAO::executeQuery($queryString);
    $count = [];

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

    $params = [1 => [$priceSetId, 'Integer']];

    $dao = CRM_Core_DAO::executeQuery($query, $params);

    //CRM-18050: Check count of price set fields which has been set with auto-renew option.
    //If price set field is already present with auto-renew option then, it will restrict for adding another price set field with auto-renew option.
    if ($dao->N == 0) {
      return 0;
    }

    $autoRenewOption = 2;
    $priceFields = [];
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
   * @return bool
   *   true if we found and updated the object, else false
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

    $params = [1 => [$id, 'Integer']];
    $dao = CRM_Core_DAO::executeQuery($query, $params);

    $membershipTypes = [
      'all' => [],
      'autorenew' => [],
      'autorenew_required' => [],
      'autorenew_optional' => [],
    ];
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
      if (self::isQuickConfig($priceSetId)) {
        $copyPriceSet = CRM_Price_BAO_PriceSet::copy($priceSetId);
        CRM_Price_BAO_PriceSet::addTo($baoName, $newId, $copyPriceSet->id);
      }
      else {
        $copyPriceSet = CRM_Core_DAO::copyGeneric('CRM_Price_DAO_PriceSetEntity',
          [
            'entity_id' => $id,
            'entity_table' => $baoName,
          ],
          ['entity_id' => $newId]
        );
      }
      // copy event discount
      if ($baoName == 'civicrm_event') {
        $discount = CRM_Core_BAO_Discount::getOptionGroup($id, 'civicrm_event');
        foreach ($discount as $discountId => $setId) {

          $copyPriceSet = &CRM_Price_BAO_PriceSet::copy($setId);

          CRM_Core_DAO::copyGeneric(
            'CRM_Core_DAO_Discount',
            [
              'id' => $discountId,
            ],
            [
              'entity_id' => $newId,
              'price_set_id' => $copyPriceSet->id,
            ]
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
    $priceSetValueIDs = [];
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
    $priceSetParams = [];
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
    $forms = [];
    $queryString = "
SELECT   entity_table, entity_id
FROM     civicrm_price_set_entity
WHERE    price_set_id = %1";
    $params = [1 => [$id, 'Integer']];
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
    $usedBy = [];
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
          throw new CRM_Core_Exception("$table is not supported in PriceSet::usedBy()");

      }
    }
    return $usedBy;
  }

  /**
   * Get the relevant line item.
   *
   * Note this is part of code being cleaned up / refactored & may change.
   *
   * @param array $params
   * @param array $lineItem
   * @param int $priceSetID
   * @param array $field
   * @param int $id
   *
   * @return array
   */
  public static function getLine(&$params, &$lineItem, $priceSetID, $field, $id): array {
    $totalTax = 0;
    switch ($field['html_type']) {
      case 'Text':
        $firstOption = reset($field['options']);
        $params["price_{$id}"] = [$firstOption['id'] => $params["price_{$id}"]];
        CRM_Price_BAO_LineItem::format($id, $params, $field, $lineItem);
        $optionValueId = key($field['options']);

        if (($field['options'][$optionValueId]['name'] ?? NULL) === 'contribution_amount') {
          $taxRates = CRM_Core_PseudoConstant::getTaxRates();
          if (array_key_exists($params['financial_type_id'], $taxRates)) {
            $field['options'][key($field['options'])]['tax_rate'] = $taxRates[$params['financial_type_id']];
            $taxAmount = CRM_Contribute_BAO_Contribution_Utils::calculateTaxAmount($field['options'][$optionValueId]['amount'], $field['options'][$optionValueId]['tax_rate']);
            $field['options'][$optionValueId]['tax_amount'] = round($taxAmount['tax_amount'], 2);
          }
        }
        if (!empty($field['options'][$optionValueId]['tax_rate'])) {
          $lineItem = self::setLineItem($field, $lineItem, $optionValueId, $totalTax);
        }
        break;

      case 'Radio':
        //special case if user select -none-
        if ($params["price_{$id}"] <= 0) {
          break;
        }
        $params["price_{$id}"] = [$params["price_{$id}"] => 1];
        $optionValueId = CRM_Utils_Array::key(1, $params["price_{$id}"]);

        // CRM-18701 Sometimes the amount in the price set is overridden by the amount on the form.
        // This is notably the case with memberships and we need to put this amount
        // on the line item rather than the calculated amount.
        // This seems to only affect radio link items as that is the use case for the 'quick config'
        // set up (which allows a free form field).
        // @todo $priceSetID is a pseudoparam for permit override - we should stop passing it where we
        // don't specifically need it & find a better way where we do.
        $amount_override = NULL;

        if ($priceSetID && count(self::filterPriceFieldsFromParams($priceSetID, $params)) === 1) {
          $amount_override = $params['total_amount'] ?? NULL;
        }
        CRM_Price_BAO_LineItem::format($id, $params, $field, $lineItem, $amount_override);
        if (!empty($field['options'][$optionValueId]['tax_rate'])) {
          $lineItem = self::setLineItem($field, $lineItem, $optionValueId, $totalTax);
          if ($amount_override) {
            $lineItem[$optionValueId]['line_total'] = $lineItem[$optionValueId]['unit_price'] = CRM_Utils_Rule::cleanMoney($lineItem[$optionValueId]['line_total'] - $lineItem[$optionValueId]['tax_amount']);
          }
        }
        break;

      case 'Select':
        $params["price_{$id}"] = [$params["price_{$id}"] => 1];
        $optionValueId = CRM_Utils_Array::key(1, $params["price_{$id}"]);

        CRM_Price_BAO_LineItem::format($id, $params, $field, $lineItem);
        if (!empty($field['options'][$optionValueId]['tax_rate'])) {
          $lineItem = self::setLineItem($field, $lineItem, $optionValueId, $totalTax);
        }
        break;

      case 'CheckBox':

        CRM_Price_BAO_LineItem::format($id, $params, $field, $lineItem);
        foreach ($params["price_{$id}"] as $optionId => $option) {
          if (!empty($field['options'][$optionId]['tax_rate'])) {
            $lineItem = self::setLineItem($field, $lineItem, $optionId, $totalTax);
          }
        }
        break;
    }
    return [$params, $lineItem];
  }

  /**
   * Pseudoconstant options for the `extends` field
   *
   * @return array
   */
  public static function getExtendsOptions(): array {
    $enabledComponents = CRM_Core_Component::getEnabledComponents();
    $allowedComponents = [
      'CiviEvent' => ts('Event'),
      'CiviContribute' => ts('Contribution'),
      'CiviMember' => ts('Membership'),
    ];
    $result = [];
    foreach (array_intersect_key($enabledComponents, $allowedComponents) as $component) {
      $result[] = [
        'id' => $component->componentID,
        'name' => $component->name,
        'label' => $allowedComponents[$component->name],
      ];
    }
    return $result;
  }

  public static function hook_civicrm_post($op, $objectName, $objectId, &$objectRef): void {
    if (in_array($objectName, ['PriceField', 'PriceFieldValue', 'PriceSet'], TRUE)) {
      self::flushPriceSets();
    }
  }

  public static function flushPriceSets(): void {
    $cache = CRM_Utils_Cache::singleton();
    foreach (PriceSet::get(FALSE)->addSelect('id')->execute() as $priceSet) {
      $cacheKey = 'CRM_Price_BAO_PriceSetgetCachedPriceSetDetail_' . $priceSet['id'];
      $cache->delete($cacheKey);
    }
  }

}
