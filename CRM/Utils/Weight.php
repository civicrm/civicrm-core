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
 * Class CRM_Utils_Weight
 */
class CRM_Utils_Weight {
  /**
   * List of GET fields which must be validated
   *
   * To reduce the size of this patch, we only sign the exploitable fields
   * which make up "$baseURL" in addOrder() (eg 'filter' or 'dao').
   * Less-exploitable fields (eg 'dir') are left unsigned.
   * 'id','src','dst','dir'
   * @var array
   */
  public static $SIGNABLE_FIELDS = ['reset', 'dao', 'idName', 'url', 'filter'];

  /**
   * Correct duplicate weight entries by putting them (duplicate weights) in sequence.
   *
   * @param string $daoName
   *   Full name of the DAO.
   * @param array $fieldValues
   *   Field => value to be used in the WHERE.
   * @param string $weightField
   *   Field which contains the weight value.
   *   defaults to 'weight'
   *
   * @return bool
   */
  public static function correctDuplicateWeights($daoName, $fieldValues = NULL, $weightField = 'weight') {
    $selectField = "MIN(id) AS dupeId, count(id) as dupeCount, $weightField as dupeWeight";
    $groupBy = "$weightField having count(id)>1";

    $minDupeID = CRM_Utils_Weight::query('SELECT', $daoName, $fieldValues, $selectField, NULL, NULL, $groupBy);

    // return early if query returned empty
    // CRM-8043
    if (!$minDupeID->fetch()) {
      return TRUE;
    }

    if ($minDupeID->dupeId) {
      $additionalWhere = "id !=" . $minDupeID->dupeId . " AND $weightField >= " . $minDupeID->dupeWeight;
      $update = "$weightField = $weightField + 1";
      $status = CRM_Utils_Weight::query('UPDATE', $daoName, $fieldValues, $update, $additionalWhere);
    }

    if ($minDupeID->dupeId && $status) {
      // recursive call to correct all duplicate weight entries.
      return CRM_Utils_Weight::correctDuplicateWeights($daoName, $fieldValues, $weightField);
    }
    elseif (!$minDupeID->dupeId) {
      // case when no duplicate records are found.
      return TRUE;
    }
    elseif (!$status) {
      // case when duplicate records are found but update status is false.
      return FALSE;
    }
  }

  /**
   * Remove a row from the specified weight, and shift all rows below it up
   *
   * @param string $daoName
   *   Full name of the DAO.
   * $param integer $weight the weight to be removed
   * @param int $fieldID
   * @param array $fieldValues
   *   Field => value to be used in the WHERE.
   * @param string $weightField
   *   Field which contains the weight value.
   *   defaults to 'weight'
   *
   * @return bool
   */
  public static function delWeight($daoName, $fieldID, $fieldValues = NULL, $weightField = 'weight') {
    $object = new $daoName();
    $object->id = $fieldID;
    if (!$object->find(TRUE)) {
      return FALSE;
    }

    $weight = (int) $object->weight;
    if ($weight < 1) {
      return FALSE;
    }

    // fill the gap
    $additionalWhere = "$weightField > $weight";
    $update = "$weightField = $weightField - 1";
    $status = CRM_Utils_Weight::query('UPDATE', $daoName, $fieldValues, $update, $additionalWhere);

    return $status;
  }

  /**
   * Makes space for a moved or inserted row by updating the weight of other rows as needed.
   *
   * @param string $daoName
   *   Full name of the DAO class.
   * @param int|null $oldWeight
   *   Previous weight if the row was moved within the same parent-grouping
   *   Null if the item is new or being moved from a different grouping.
   * @param int $newWeight
   *   The desired weight of the moved/inserted row
   * @param array $fieldValues
   *   Defines the group, e.g. [parent_id => 8, domain_id => 1]
   * @param string $weightField
   *   Field which contains the weight value, defaults to 'weight'
   *
   * @return int
   *   Adjusted new weight
   */
  public static function updateOtherWeights($daoName, $oldWeight, $newWeight, $fieldValues = NULL, $weightField = 'weight') {
    $newWeight = (int) $newWeight;

    // max weight is the highest current weight
    $maxWeight = self::getMax($daoName, $fieldValues, $weightField) ?: 1;

    if ($newWeight > $maxWeight) {
      // calculate new weight, CRM-4133
      $calNewWeight = CRM_Utils_Weight::getNewWeight($daoName, $fieldValues, $weightField);

      // no need to update weight for other fields.
      if ($calNewWeight > $maxWeight) {
        return $calNewWeight;
      }
      $newWeight = $maxWeight;

      if (!isset($oldWeight)) {
        return $newWeight + 1;
      }
    }
    elseif ($newWeight < 1) {
      $newWeight = 1;
    }

    // if they're the same, nothing to do
    if ($oldWeight == $newWeight) {
      return $newWeight;
    }

    // Check for an existing record with this weight
    $existing = self::query('SELECT', $daoName, $fieldValues, "id", "$weightField = $newWeight");
    // Nothing to do if no existing record has this weight
    if (empty($existing->N)) {
      return $newWeight;
    }

    // if oldWeight not present, indicates new weight is to be added. So create a gap for a new row to be inserted.
    if (!isset($oldWeight)) {
      $additionalWhere = "$weightField >= $newWeight";
      $update = "$weightField = ($weightField + 1)";
      CRM_Utils_Weight::query('UPDATE', $daoName, $fieldValues, $update, $additionalWhere);
    }
    else {
      $oldWeight = (int) $oldWeight;
      if ($newWeight > $oldWeight) {
        $additionalWhere = "$weightField > $oldWeight AND $weightField <= $newWeight";
        $update = "$weightField = ($weightField - 1)";
      }
      elseif ($newWeight < $oldWeight) {
        $additionalWhere = "$weightField >= $newWeight AND $weightField < $oldWeight";
        $update = "$weightField = ($weightField + 1)";
      }
      CRM_Utils_Weight::query('UPDATE', $daoName, $fieldValues, $update, $additionalWhere);
    }
    return $newWeight;
  }

  /**
   * Returns the new calculated weight.
   *
   * @param string $daoName
   *   Full name of the DAO.
   * @param array $fieldValues
   *   Field => value to be used in the WHERE.
   * @param string $weightField
   *   Field which used to get the wt, default to 'weight'.
   *
   * @return int
   */
  public static function getNewWeight($daoName, $fieldValues = NULL, $weightField = 'weight') {
    $selectField = "id AS fieldID, $weightField AS weight";
    $field = CRM_Utils_Weight::query('SELECT', $daoName, $fieldValues, $selectField);
    $sameWeightCount = 0;
    $weights = [];
    while ($field->fetch()) {
      if (in_array($field->weight, $weights)) {
        $sameWeightCount++;
      }
      $weights[$field->fieldID] = $field->weight;
    }

    $newWeight = 1;
    if ($sameWeightCount) {
      $newWeight = max($weights) + 1;

      // check for max wt, should not greater than cal max wt.
      $calMaxWt = min($weights) + count($weights) - 1;
      if ($newWeight > $calMaxWt) {
        $newWeight = $calMaxWt;
      }
    }
    elseif (!empty($weights)) {
      $newWeight = max($weights);
    }

    return $newWeight;
  }

  /**
   * Returns the highest weight.
   *
   * @param string $daoName
   *   Full name of the DAO.
   * @param array $fieldValues
   *   Field => value to be used in the WHERE.
   * @param string $weightField
   *   Field which contains the weight value.
   *   defaults to 'weight'
   *
   * @return int
   */
  public static function getMax($daoName, $fieldValues = NULL, $weightField = 'weight') {
    if (empty($weightField)) {
      Civi::log()->warning('Missing weight field name for ' . $daoName);
      return 0;
    }

    $selectField = "MAX(ROUND($weightField)) AS max_weight";
    $weightDAO = CRM_Utils_Weight::query('SELECT', $daoName, $fieldValues, $selectField);
    $weightDAO->fetch();
    if ($weightDAO->max_weight) {
      return $weightDAO->max_weight;
    }
    return 0;
  }

  /**
   * Returns the default weight ( highest weight + 1 ) to be used.
   *
   * @param string $daoName
   *   Full name of the DAO.
   * @param array $fieldValues
   *   Field => value to be used in the WHERE.
   * @param string $weightField
   *   Field which contains the weight value.
   *   defaults to 'weight'
   *
   * @return int
   */
  public static function getDefaultWeight($daoName, $fieldValues = NULL, $weightField = 'weight') {
    $maxWeight = CRM_Utils_Weight::getMax($daoName, $fieldValues, $weightField);
    return $maxWeight + 1;
  }

  /**
   * Execute a weight-related query
   *
   * @param string $queryType
   *   SELECT, UPDATE, DELETE.
   * @param CRM_Core_DAO|string $daoName
   *   Full name of the DAO.
   * @param array $fieldValues
   *   Field => value to be used in the WHERE.
   * @param string $queryData
   *   Data to be used, dependent on the query type.
   * @param string|null $additionalWhere
   *   Optional WHERE field.
   * @param string|null $orderBy
   *   Optional ORDER BY field.
   * @param string|null $groupBy
   *   Optional GROU{} BY field.
   *
   * @return CRM_Core_DAO
   *   objet that holds the results of the query
   */
  public static function &query(
    $queryType,
    $daoName,
    $fieldValues,
    $queryData,
    $additionalWhere = NULL,
    $orderBy = NULL,
    $groupBy = NULL
  ) {
    $table = $daoName::getTablename();
    $fields = $daoName::getSupportedFields();
    $fieldlist = array_keys($fields);

    $whereConditions = [];
    if ($additionalWhere) {
      $whereConditions[] = $additionalWhere;
    }
    $params = [];
    $fieldNum = 0;
    if (is_array($fieldValues)) {
      foreach ($fieldValues as $fieldName => $value) {
        if (!in_array($fieldName, $fieldlist)) {
          // invalid field specified.  abort.
          throw new CRM_Core_Exception("Invalid field '$fieldName' for $daoName");
        }
        if (CRM_Utils_System::isNull($value)) {
          $whereConditions[] = "$fieldName IS NULL";
        }
        else {
          $fieldNum++;
          $whereConditions[] = "$fieldName = %$fieldNum";
          $fieldType = $fields[$fieldName]['type'];
          $params[$fieldNum] = [$value, CRM_Utils_Type::typeToString($fieldType)];
        }
      }
    }
    $where = implode(' AND ', $whereConditions);

    switch ($queryType) {
      case 'SELECT':
        $query = "SELECT $queryData FROM $table";
        if ($where) {
          $query .= " WHERE $where";
        }
        if ($groupBy) {
          $query .= " GROUP BY $groupBy";
        }
        if ($orderBy) {
          $query .= " ORDER BY $orderBy";
        }
        break;

      case 'UPDATE':
        $query = "UPDATE $table SET $queryData";
        if ($where) {
          $query .= " WHERE $where";
        }
        break;

      case 'DELETE':
        $query = "DELETE FROM $table WHERE $where AND $queryData";
        break;

      default:
        throw new CRM_Core_Exception("Invalid query operation for $daoName");
    }

    $resultDAO = CRM_Core_DAO::executeQuery($query, $params);
    return $resultDAO;
  }

  /**
   * @param array $rows
   * @param string $daoName
   * @param string $idName
   * @param string $returnURL
   * @param string|null $filter
   */
  public static function addOrder(&$rows, $daoName, $idName, $returnURL, $filter = NULL) {
    if (empty($rows)) {
      return;
    }

    $ids = array_keys($rows);
    $numIDs = count($ids);
    array_unshift($ids, 0);
    $ids[] = 0;
    $firstID = $ids[1];
    $lastID = $ids[$numIDs];
    if ($firstID == $lastID) {
      $rows[$firstID]['order'] = NULL;
      return;
    }
    $config = CRM_Core_Config::singleton();
    $imageURL = $config->userFrameworkResourceURL . 'i/arrow';

    $queryParams = [
      'reset' => 1,
      'dao' => $daoName,
      'idName' => $idName,
      'url' => $returnURL,
      'filter' => $filter,
    ];

    $signer = new CRM_Utils_Signer(CRM_Core_Key::privateKey(), self::$SIGNABLE_FIELDS);
    $queryParams['_sgn'] = $signer->sign($queryParams);
    $baseURL = CRM_Utils_System::url('civicrm/admin/weight', $queryParams);

    for ($i = 1; $i <= $numIDs; $i++) {
      $id = $ids[$i];
      $prevID = $ids[$i - 1];
      $nextID = $ids[$i + 1];

      $links = [];
      $url = "{$baseURL}&amp;src=$id";

      if ($prevID != 0) {
        $alt = ts('Move to top');
        $links[] = "<a class=\"crm-weight-arrow\" href=\"{$url}&amp;dst={$firstID}&amp;dir=first\"><img src=\"{$imageURL}/first.gif\" title=\"$alt\" alt=\"$alt\" class=\"order-icon\"></a>";

        $alt = ts('Move up one row');
        $links[] = "<a class=\"crm-weight-arrow\" href=\"{$url}&amp;dst={$prevID}&amp;dir=swap\"><img src=\"{$imageURL}/up.gif\" title=\"$alt\" alt=\"$alt\" class=\"order-icon\"></a>";
      }
      else {
        $links[] = "<span class=\"order-icon\"></span>";
        $links[] = "<span class=\"order-icon\"></span>";
      }

      if ($nextID != 0) {
        $alt = ts('Move down one row');
        $links[] = "<a class=\"crm-weight-arrow\" href=\"{$url}&amp;dst={$nextID}&amp;dir=swap\"><img src=\"{$imageURL}/down.gif\" title=\"$alt\" alt=\"$alt\" class=\"order-icon\"></a>";

        $alt = ts('Move to bottom');
        $links[] = "<a class=\"crm-weight-arrow\" href=\"{$url}&amp;dst={$lastID}&amp;dir=last\"><img src=\"{$imageURL}/last.gif\" title=\"$alt\" alt=\"$alt\" class=\"order-icon\"></a>";
      }
      else {
        $links[] = "<span class=\"order-icon\"></span>";
        $links[] = "<span class=\"order-icon\"></span>";
      }
      $rows[$id]['weight'] = implode('&nbsp;', $links);
    }
  }

  /**
   *
   * @throws CRM_Core_Exception
   */
  public static function fixOrder() {
    $signature = CRM_Utils_Request::retrieve('_sgn', 'String');
    $signer = new CRM_Utils_Signer(CRM_Core_Key::privateKey(), self::$SIGNABLE_FIELDS);

    // Validate $_GET values b/c subsequent code reads $_GET (via CRM_Utils_Request::retrieve)
    if (!$signer->validate($signature, $_GET)) {
      throw new CRM_Core_Exception('Request signature is invalid');
    }

    // Note: Ensure this list matches self::$SIGNABLE_FIELDS
    $daoName = CRM_Utils_Request::retrieve('dao', 'String');
    $id = CRM_Utils_Request::retrieve('id', 'Integer');
    $idName = CRM_Utils_Request::retrieve('idName', 'String');
    $url = CRM_Utils_Request::retrieve('url', 'String');
    $filter = CRM_Utils_Request::retrieve('filter', 'String');
    $src = CRM_Utils_Request::retrieve('src', 'Integer');
    $dst = CRM_Utils_Request::retrieve('dst', 'Integer');
    $dir = CRM_Utils_Request::retrieve('dir', 'String');
    $object = new $daoName();
    $srcWeight = CRM_Core_DAO::getFieldValue($daoName, $src, 'weight', $idName);
    $dstWeight = CRM_Core_DAO::getFieldValue($daoName, $dst, 'weight', $idName);
    if ($srcWeight == $dstWeight) {
      self::fixOrderOutput($url);
    }

    $tableName = $object->tableName();

    $query = "UPDATE $tableName SET weight = %1 WHERE $idName = %2";
    $params = [
      1 => [$dstWeight, 'Integer'],
      2 => [$src, 'Integer'],
    ];
    CRM_Core_DAO::executeQuery($query, $params);

    if ($dir == 'swap') {
      $params = [
        1 => [$srcWeight, 'Integer'],
        2 => [$dst, 'Integer'],
      ];
      CRM_Core_DAO::executeQuery($query, $params);
    }
    elseif ($dir == 'first') {
      // increment the rest by one
      $query = "UPDATE $tableName SET weight = weight + 1 WHERE $idName != %1 AND weight < %2";
      if ($filter) {
        $query .= " AND $filter";
      }
      $params = [
        1 => [$src, 'Integer'],
        2 => [$srcWeight, 'Integer'],
      ];
      CRM_Core_DAO::executeQuery($query, $params);
    }
    elseif ($dir == 'last') {
      // increment the rest by one
      $query = "UPDATE $tableName SET weight = weight - 1 WHERE $idName != %1 AND weight > %2";
      if ($filter) {
        $query .= " AND $filter";
      }
      $params = [
        1 => [$src, 'Integer'],
        2 => [$srcWeight, 'Integer'],
      ];
      CRM_Core_DAO::executeQuery($query, $params);
    }

    // This function is on its way out because the civicrm_admin_ui extension is replacing legacy screens that use it,
    // but some sortable items like Custom Fields get cached in metadata so let's clear that now:
    Civi::cache('metadata')->clear();

    self::fixOrderOutput($url);
  }

  /**
   * @param string $url
   */
  public static function fixOrderOutput($url) {
    if (empty($_GET['snippet']) || $_GET['snippet'] !== 'json') {
      CRM_Utils_System::redirect($url);
    }

    CRM_Core_Page_AJAX::returnJsonResponse([
      'userContext' => $url,
    ]);
  }

}
