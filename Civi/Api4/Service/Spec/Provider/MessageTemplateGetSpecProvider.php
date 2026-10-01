<?php

namespace Civi\Api4\Service\Spec\Provider;

use Civi\Api4\Service\Spec\FieldSpec;
use Civi\Api4\Service\Spec\RequestSpec;

/**
 * @service
 * @internal
 */
class MessageTemplateGetSpecProvider extends \Civi\Core\Service\AutoService implements Generic\SpecProviderInterface {

  /**
   * @param \Civi\Api4\Service\Spec\RequestSpec $spec
   */
  public function modifySpec(RequestSpec $spec) {
    $field = new FieldSpec('master_id', 'MessageTemplate', 'Integer');
    $field->setLabel(ts('Master ID'))
      ->setTitle(ts('Master ID'))
      ->setColumnName('id')
      ->setDescription(ts('MessageID that this could revert to'))
      ->setInputType('Select')
      ->setReadonly(TRUE)
      ->setFkEntity('MessageTemplate')
      ->setSqlRenderer(['\Civi\Api4\Service\Schema\Joiner', 'getExtraJoinSql']);
    $spec->addFieldSpec($field);

    $keyword = new FieldSpec('keyword', 'MessageTemplate', 'String');
    $keyword->setLabel(ts('Keyword'))
      ->setTitle(ts('Keyword'))
      ->setColumnName('id')
      ->setDescription(ts('Search by title or subject'))
      ->setType('Filter')
      ->setInputType('Text')
      ->setOperators(['CONTAINS'])
      ->addSqlFilter([__CLASS__, 'getKeywordFilterSql']);
    $spec->addFieldSpec($keyword);
  }

  /**
   * Matches `CONTAINS` against msg_title or msg_subject - a single search box covering
   * both, since a template's title and subject are often near-duplicates of each other.
   *
   * @param array $field
   * @param string $fieldAlias
   * @param string $operator
   * @param string $value
   * return string
   */
  public static function getKeywordFilterSql(array $field, string $fieldAlias, string $operator, $value): string {
    // $fieldAlias arrives fully backtick-quoted per identifier (e.g. `a`.`id`), not just
    // dotted, so the substring to replace is the quoted column name, not `.id`.
    $titleColumn = \str_replace('`id`', '`msg_title`', $fieldAlias);
    $subjectColumn = \str_replace('`id`', '`msg_subject`', $fieldAlias);
    $escaped = \CRM_Core_DAO::escapeString('%' . $value . '%');
    return "($titleColumn LIKE '$escaped' OR $subjectColumn LIKE '$escaped')";
  }

  /**
   * @param string $entity
   * @param string $action
   *
   * @return bool
   */
  public function applies($entity, $action) {
    return $entity === 'MessageTemplate' && $action === 'get';
  }

}
