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
class EntityTagFilterSpecProvider extends \Civi\Core\Service\AutoService implements Generic\SpecProviderInterface {

  /**
   * @param \Civi\Api4\Service\Spec\RequestSpec $spec
   */
  public function modifySpec(RequestSpec $spec) {
    $field = new FieldSpec('tags', $spec->getEntity(), 'Array');
    $field->setLabel(ts('With Tags'))
      ->setTitle(ts('Tags'))
      ->setColumnName('id')
      ->setDescription(ts('Filter by tags (including child tags)'))
      ->setType('Filter')
      ->setInputType('Select')
      ->setOperators(['IN', 'NOT IN'])
      ->addSqlFilter([__CLASS__, 'getTagFilterSql'])
      ->setSuffixes(['name', 'label', 'description', 'color'])
      ->setOptionsCallback([__CLASS__, 'getTagList']);
    $spec->addFieldSpec($field);
  }

  /**
   * @param string $entity
   * @param string $action
   *
   * @return bool
   */
  public function applies($entity, $action) {
    if ($action !== 'get') {
      return FALSE;
    }
    if (CoreUtil::isContact($entity)) {
      return TRUE;
    }
    $usedFor = \CRM_Core_OptionGroup::values('tag_used_for', FALSE, FALSE, FALSE, NULL, 'name');
    return in_array($entity, $usedFor, TRUE);
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
  public static function getTagFilterSql(array $field, string $fieldAlias, string $operator, $value, Api4SelectQuery $query, int $depth): string {
    $tableName = CoreUtil::getTableName($field['entity']);
    $tagTree = \CRM_Core_BAO_Tag::getChildTags();
    $value = (array) ($value ?: NULL);
    foreach ($value as $tagID) {
      if (!empty($tagTree[$tagID])) {
        $value = array_unique(array_merge($value, $tagTree[$tagID]));
      }
    }
    $tags = implode(',', $value);
    $tags = $tags && \CRM_Utils_Rule::commaSeparatedIntegers($tags) ? $tags : '0';
    return "$fieldAlias $operator (SELECT entity_id FROM `civicrm_entity_tag` WHERE entity_table = '$tableName' AND tag_id IN ($tags))";
  }

  /**
   * Callback function to build option list for tags filters.
   *
   * @param array $field
   * @param array $values
   * @param bool|array $returnFormat
   * @param bool $checkPermissions
   * @return array
   */
  public static function getTagList($field, $values, $returnFormat, $checkPermissions) {
    $values = ['entity_table' => CoreUtil::getTableName($field['entity'])];
    return \Civi::entity('EntityTag')->getOptions('tag_id', $values, FALSE, $checkPermissions);
  }

}
