<?php

/**
 * Class CRM_Core_Smarty_Permissions
 */
class CRM_Core_Smarty_Permissions {
  // non-static adapter for CRM_Core_Permission::check
  /**
   * @param $offset
   *
   * @return bool
   */
  function check($offset) {
    return CRM_Core_Permission::check($offset);
  }

}
