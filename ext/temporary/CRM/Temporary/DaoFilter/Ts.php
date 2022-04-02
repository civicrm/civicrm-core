<?php

class CRM_Temporary_DaoFilter_Ts {

  /**
   * Change to "ts" schema.
   *
   * Specifically: do nothing. That's the current baseline.
   *
   * @param string $className
   * @param array $fields
   */
  public static function filter(string $className, array &$fields): void {
    // This is currently the default schema.
  }

}
