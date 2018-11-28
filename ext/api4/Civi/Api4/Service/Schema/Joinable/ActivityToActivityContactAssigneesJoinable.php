<?php

namespace Civi\Api4\Service\Schema\Joinable;

class ActivityToActivityContactAssigneesJoinable extends Joinable {
  /**
   * @var string
   */
  protected $baseTable = 'civicrm_activity';

  /**
   * @var string
   */
  protected $baseColumn = 'id';

  /**
   * @param $alias
   */
  public function __construct($alias) {
    $optionValueTable = 'civicrm_option_value';
    $optionGroupTable = 'civicrm_option_group';

    $subSubSelect = sprintf(
      'SELECT id FROM %s WHERE name = "%s"',
      $optionGroupTable,
      'activity_contacts'
    );

    $subSelect = sprintf(
      'SELECT value FROM %s WHERE name = "%s" AND option_group_id = (%s)',
      $optionValueTable,
      'Activity Assignees',
      $subSubSelect
    );

    $this->addCondition(sprintf('%s.record_type_id = (%s)', $alias, $subSelect));
    parent::__construct('civicrm_activity_contact', 'activity_id', $alias);
  }

}
