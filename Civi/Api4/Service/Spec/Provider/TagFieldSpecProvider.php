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
use Civi\Api4\Service\Spec\Provider\Generic\SpecProviderInterface;
use Civi\Api4\Service\Spec\RequestSpec;
use Civi\Api4\Utils\CoreUtil;
use Civi\Core\Event\PostEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @service
 * @internal
 */
class TagFieldSpecProvider extends \Civi\Core\Service\AutoService implements SpecProviderInterface, EventSubscriberInterface {

  public static function getSubscribedEvents(): array {
    return [
      'hook_civicrm_post' => 'saveTags',
    ];
  }

  public function saveTags(PostEvent $e) {
    if (empty($e->params['tags'])) {
      return;
    }
    $apiAction = ($e->action === 'edit') ? 'update' : $e->action;
    if (!$this->applies($e->entity, $apiAction)) {
      return;
    }
    if (CoreUtil::isContact($e->entity)) {
      $entityTable = 'civicrm_contact';
    }
    else {
      $entityTable = array_flip($this->getTaggableEntities())[$e->entity] ?? NULL;
    }
    if (!$entityTable) {
      return;
    }
    $entityTagRecords = array_map(fn ($tagId) => [
      'tag_id' => $tagId,
      'entity_table' => $entityTable,
      'entity_id' => $e->id,
    ], $e->params['tags']);
    // get record ID
    \Civi\Api4\EntityTag::replace(FALSE)
      ->addRecord(...$entityTagRecords)
      ->addWhere('entity_table', '=', $entityTable)
      ->addWhere('entity_id', '=', $e->id)
      ->setMatch(['tag_id', 'entity_table', 'entity_id'])
      ->execute();
  }

  /**
   * @param \Civi\Api4\Service\Spec\RequestSpec $spec
   */
  public function modifySpec(RequestSpec $spec) {
    $field = new FieldSpec('tags', $spec->getEntity(), 'Array');
    $field->setLabel(ts('Tags'))
      ->setTitle(ts('Tags'))
      ->setColumnName('id')
      ->setDescription(ts('Tags applied to this record'))
      ->setType('Extra')
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
    if (!in_array($action, ['get', 'create', 'update'])) {
      return FALSE;
    }
    if (CoreUtil::isContact($entity)) {
      return TRUE;
    }
    return in_array($entity, $this->getTaggableEntities(), TRUE);
  }

  private function getTaggableEntities(): array {
    return \CRM_Core_OptionGroup::values('tag_used_for', FALSE, FALSE, FALSE, NULL, 'name');
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
