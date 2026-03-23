<?php

namespace Civi\Api4;

use Civi\Api4\Event\SchemaMapBuildEvent;
use Civi\Core\Event\GenericHookEvent;

/**
 * @since 6.14
 */
class MockSqlView extends Generic\SqlView {

  /**
   *  Override event callback to only build this view when it's actually needed.
   *  This prevents interference with other tests as creating a view breaks transactions.
   */
  public static function _on_civi_api4_entityTypes(GenericHookEvent $event): void {
    if (!empty($GLOBALS['enableMockSqlView'])) {
      parent::_on_civi_api4_entityTypes($event);
    }
  }

  /**
   * Override event callback to only schema when it's actually needed.
   */
  public static function _on_schema_map_build(SchemaMapBuildEvent $event): void {
    if (!empty($GLOBALS['enableMockSqlView'])) {
      parent::_on_schema_map_build($event);
    }
  }

  protected static function viewSelect(): array {
    return [
      [
        'select' => 'c.id',
        'name' => 'contact_id',
        'original_field' => 'Contact.id',
      ],
      [
        'select' => 'CONCAT(c.first_name, " ", c.last_name)',
        'name' => 'full_name',
        'data_type' => 'String',
      ],
      [
        'select' => 'e.email',
        'name' => 'email',
        'original_field' => 'Email.email',
      ],
      [
        'select' => 'e.id',
        'name' => 'email_id',
        'data_type' => 'Integer',
        'original_field' => 'Email.id',
      ],
      [
        'select' => 'e.location_type_id',
        'name' => 'email_location_type_id',
        'original_field' => 'Email.location_type_id',
      ],
    ];
  }

  protected static function viewFrom(): string {
    return <<<SQL
FROM civicrm_contact c
LEFT JOIN civicrm_email e ON e.contact_id = c.id AND e.is_primary = 1
WHERE c.contact_type = "Individual" AND last_name LIKE "Test%"
SQL;
  }

}
