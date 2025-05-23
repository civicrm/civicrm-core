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

namespace Civi\Api4\Service\Spec\Provider;

use Civi\Api4\Query\Api4SelectQuery;
use Civi\Api4\Service\Spec\FieldSpec;
use Civi\Api4\Service\Spec\RequestSpec;
use Civi\Api4\Utils\CoreUtil;

/**
 * @service
 * @internal
 */
class ContactGetSpecProvider extends \Civi\Core\Service\AutoService implements Generic\SpecProviderInterface {

  /**
   * @param \Civi\Api4\Service\Spec\RequestSpec $spec
   */
  public function modifySpec(RequestSpec $spec) {
    // Groups field
    $field = new FieldSpec('groups', $spec->getEntity(), 'Array');
    $field->setLabel(ts('In Groups'))
      ->setTitle(ts('Groups'))
      ->setColumnName('id')
      ->setDescription(ts('Groups (or sub-groups of groups) to which this contact belongs'))
      ->setType('Filter')
      ->setInputType('Select')
      ->setOperators(['IN', 'NOT IN'])
      ->addSqlFilter([__CLASS__, 'getContactGroupSql'])
      ->setSuffixes(['name', 'label'])
      ->setOptionsCallback([__CLASS__, 'getGroupList']);
    $spec->addFieldSpec($field);

    // The following fields are specific to Individuals
    if (!$spec->getValue('contact_type') || $spec->getValue('contact_type') === 'Individual') {
      // Age field
      $field = new FieldSpec('age_years', $spec->getEntity(), 'Integer');
      $field->setLabel(ts('Age (years)'))
        ->setTitle(ts('Age (years)'))
        ->setColumnName('birth_date')
        ->setInputType('Number')
        ->setDescription(ts('Age of individual (in years)'))
        ->setType('Extra')
        ->setReadonly(TRUE)
        ->setSqlRenderer([__CLASS__, 'calculateAge']);
      $spec->addFieldSpec($field);

      // Birthday field
      $field = new FieldSpec('next_birthday', $spec->getEntity(), 'Integer');
      $field->setLabel(ts('Next Birthday in (days)'))
        ->setTitle(ts('Next Birthday in (days)'))
        ->setColumnName('birth_date')
        ->setInputType('Number')
        ->setDescription(ts('Number of days until next birthday'))
        ->setType('Extra')
        ->setReadonly(TRUE)
        ->setSqlRenderer([__CLASS__, 'calculateBirthday']);
      $spec->addFieldSpec($field);
    }

    // Address, Email, Phone, IM primary/billing virtual fields
    // This exposes the joins created by
    // \Civi\Api4\Event\Subscriber\ContactSchemaMapSubscriber::onSchemaBuild()
    $entities = [
      'Address' => [
        'primary' => [
          'title' => ts('Primary Address ID'),
          'label' => ts('Primary Address'),
        ],
        'billing' => [
          'title' => ts('Billing Address ID'),
          'label' => ts('Billing Address'),
        ],
      ],
      'Email' => [
        'primary' => [
          'title' => ts('Primary Email ID'),
          'label' => ts('Primary Email'),
        ],
        'billing' => [
          'title' => ts('Billing Email ID'),
          'label' => ts('Billing Email'),
        ],
      ],
      'Phone' => [
        'primary' => [
          'title' => ts('Primary Phone ID'),
          'label' => ts('Primary Phone'),
        ],
        'billing' => [
          'title' => ts('Billing Phone ID'),
          'label' => ts('Billing Phone'),
        ],
      ],
      'IM' => [
        'primary' => [
          'title' => ts('Primary IM ID'),
          'label' => ts('Primary IM'),
        ],
        'billing' => [
          'title' => ts('Billing IM ID'),
          'label' => ts('Billing IM'),
        ],
      ],
    ];
    foreach ($entities as $entity => $types) {
      foreach ($types as $type => $info) {
        $name = strtolower($entity) . '_' . $type;
        $field = new FieldSpec($name, $spec->getEntity(), 'Integer');
        $field->setLabel($info['label'])
          ->setTitle($info['title'])
          ->setColumnName('id')
          ->setType('Extra')
          ->setFkEntity($entity)
          ->setSqlRenderer(['\Civi\Api4\Service\Schema\Joiner', 'getExtraJoinSql']);
        $spec->addFieldSpec($field);
      }
    }

  }

  /**
   * @param string $entity
   * @param string $action
   *
   * @return bool
   */
  public function applies($entity, $action) {
    // Applies to 'Contact' plus pseudo-entities 'Individual', 'Organization', 'Household'
    return CoreUtil::isContact($entity) && $action === 'get';
  }

  /**
   * @param array $field
   * @param string $fieldAlias
   * @param string $operator
   * @param mixed $value
   * @param \Civi\Api4\Query\Api4SelectQuery $query
   * @param int $depth
   * return string
   */
  public static function getContactGroupSql(array $field, string $fieldAlias, string $operator, $value, Api4SelectQuery $query, int $depth): string {
    $tempTable = \CRM_Utils_SQL_TempTable::build();
    $tempTable->createWithColumns('contact_id INT');
    $tableName = $tempTable->getName();
    \CRM_Contact_BAO_GroupContactCache::populateTemporaryTableWithContactsInGroups((array) $value, $tableName);
    // SQL optimization - use INNER JOIN if the base table is Contact & this clause is not nested
    if ($fieldAlias === '`a`.`id`' && $operator === "IN" && !$depth) {
      $query->getQuery()->join($tableName, "INNER JOIN `$tableName` ON $fieldAlias = `$tableName`.contact_id");
      return '1';
    }
    // Else use IN or NOT IN (this filter only supports those 2 operators)
    else {
      return "$fieldAlias $operator (SELECT contact_id FROM `$tableName`)";
    }
  }

  /**
   * Callback function to build option lists groups pseudo-field.
   *
   * @param array $field
   * @param array $values
   * @param bool|array $returnFormat
   * @param bool $checkPermissions
   * @return array
   */
  public static function getGroupList($field, $values, $returnFormat, $checkPermissions) {
    $groups = $checkPermissions ? \CRM_Core_PseudoConstant::group() : \CRM_Core_PseudoConstant::allGroup(NULL, FALSE);
    $options = \CRM_Utils_Array::makeNonAssociative($groups, 'id', 'label');
    if ($options && is_array($returnFormat) && in_array('name', $returnFormat)) {
      $groupIndex = array_flip(array_keys($groups));
      $dao = \CRM_Core_DAO::executeQuery('SELECT id, name FROM civicrm_group WHERE id IN (%1)', [
        1 => [implode(',', array_keys($groups)), 'CommaSeparatedIntegers'],
      ]);
      while ($dao->fetch()) {
        $options[$groupIndex[$dao->id]]['name'] = $dao->name;
      }
    }
    return $options;
  }

  /**
   * Generate SQL for age field
   * @param array $field
   * @return string
   */
  public static function calculateAge(array $field): string {
    return "TIMESTAMPDIFF(YEAR, {$field['sql_name']}, CURDATE())";
  }

  /**
   * Generate SQL for upcoming birthday field
   *
   * Calculates the number of days until the next birthday
   *
   * @param array $field
   * @return string
   */
  public static function calculateBirthday(array $field): string {
    $anniversarySql = \CRM_Utils_Date::getAnniversarySql($field['sql_name']);
    return "DATEDIFF($anniversarySql, CURDATE())";
  }

}
