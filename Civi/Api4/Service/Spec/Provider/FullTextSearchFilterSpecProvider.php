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

use Civi\Api4\Service\Spec\FieldSpec;
use Civi\Api4\Service\Spec\RequestSpec;
use Civi\Api4\Utils\CoreUtil;

/**
 * @service
 * @internal
 */
class FullTextSearchFilterSpecProvider extends \Civi\Core\Service\AutoService implements Generic\SpecProviderInterface {

  /**
   * @param \Civi\Api4\Service\Spec\RequestSpec $spec
   */
  public function modifySpec(RequestSpec $spec) {
    $entity = $dbEntity = $spec->getEntity();
    if (CoreUtil::isContact($entity)) {
      $dbEntity = 'Contact';
    }
    $indices = \Civi::service('civi.schema.fts')->getIndicesForEntity($dbEntity);

    foreach ($indices as $name => $index) {
      $field = new FieldSpec($name, $entity, 'String');
      $label = $index['label'];
      $description = $index['description'] ?: ts('Full text search on %1', [1 => $label]);
      $field->setLabel($label)
        ->setTitle(ts('Full Text Search: %1', [1 => $label]))
        ->setColumnName('id')
        ->setDescription($description)
        ->setType('Filter')
        ->setInputType('Text')
        ->setOperators(['CONTAINS', 'LIKE', 'MATCHES'])
        ->addSqlFilter(fn (array $field, string $fieldAlias, string $operator, $value) => self::getMatchFilterSql($index['columns'], $fieldAlias, $operator, $value));
      $spec->addFieldSpec($field);
    }
  }

  /**
   * @param string $entity
   * @param string $action
   *
   * @return bool
   */
  public function applies($entity, $action): bool {
    if (!\Civi::settings()->get('search_mysql_fts')) {
      return FALSE;
    }
    if ($action !== 'get') {
      return FALSE;
    }
    // TO CHECK: is checking here helpful? we have to look up indexNames again in modifySpec
    // in order to loop over them -- if there are none modifySpec will be a no-op
    if (CoreUtil::isContact($entity)) {
      $entity = 'Contact';
    }
    return !!\Civi::service('civi.schema.fts')->getIndicesForEntity($entity);
  }

  /**
   * @param array $columns
   * @param string $fieldAlias
   * @param string $operator
   * @param string $value
   * return string
   */
  public static function getMatchFilterSql(array $columns, string $fieldAlias, string $operator, string $value): string {
    $columns = array_map(fn ($column) => \str_replace('id', $column, $fieldAlias), $columns);
    $columnList = implode(',', $columns);

    // escape user entered value
    $value = \CRM_Core_DAO::escapeString($value);

    switch ($operator) {
      // CONTAINS = full word matches
      case 'CONTAINS':
        return "MATCH({$columnList}) AGAINST ('$value' IN NATURAL LANGUAGE MODE)";

      // MATCHES = raw FTS boolean expression
      case 'MATCHES':
        return "MATCH({$columnList}) AGAINST ('$value' IN BOOLEAN MODE)";

      // LIKE = we aim to provide interop with Mysql LIKE syntax though
      // this only works with trailing wildcard
      case 'LIKE':
        $words = explode(' ', $value);

        $words = array_filter(array_map(function ($word) use ($fieldAlias) {
          $wildcardPos = strpos($word, '%');
          // if no wildcards leave as is
          if ($wildcardPos === FALSE) {
            return '+' . $word;
          }
          // trailing wildcard replace with FTS wildcard *
          if ($wildcardPos === (strlen($word) - 1)) {
            return '+' . rtrim($word, '%') . '*';
          }
          // if non-trailing wildcards then FTS can't handle
          // Q: should we just log?
          throw new \CRM_Core_Exception("Invalid wildcard in FTS query expression {$fieldAlias} LIKE {$word} - ignoring");
        }, $words));

        $value = implode(' ', $words);

        return "MATCH({$columnList}) AGAINST ('$value' IN BOOLEAN MODE)";
    }

    throw new \CRM_Core_Exception('Unrecognised operator for Full Text Search field');
  }

}
