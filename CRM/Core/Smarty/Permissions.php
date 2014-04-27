<?php

class CRM_Core_Smarty_Permissions {
  // non-static adapter for CRM_Core_Permission::check
  function check($offset) {
    return CRM_Core_Permission::check($offset);
  }

}