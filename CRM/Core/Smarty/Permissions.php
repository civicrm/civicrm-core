<?php

/**
 * Class CRM_Core_Smarty_Permissions
 */
class CRM_Core_Smarty_Permissions {

  /**
   * non-static adapter for CRM_Core_Permission::check
   * @param string|array $offset
   *
   * @return bool
   */
  public function check($offset) {
    return CRM_Core_Permission::check($offset);
  }

}
