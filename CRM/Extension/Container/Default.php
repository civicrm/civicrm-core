<?php

/**
 * The default container is just a basic container which can be configured via the
 * web UI.
 */
class CRM_Extension_Container_Default extends CRM_Extension_Container_Basic {

  /**
   * {@inheritdoc}
   */
  public function checkRequirements() {
    $errors = array();

    // In current configuration, we don't construct the default container unless baseDir is set,
    // so this error condition is more theoretical.
    if (empty($this->baseDir) || !is_dir($this->baseDir)) {
      $civicrmDestination = urlencode(CRM_Utils_System::url('civicrm/admin/extensions', 'reset=1'));
      $url = CRM_Utils_System::url('civicrm/admin/setting/path', "reset=1&civicrmDestination=${civicrmDestination}");
      $errors[] = array(
        'title' => ts('Invalid Base Directory'),
        'message' => ts('The extensions directory is not properly set. Please go to the <a href="%1">path setting page</a> and correct it.<br/>',
          array(
            1 => $url,
          )
        )
      );
    }
    if (empty($this->baseUrl)) {
      $civicrmDestination = urlencode(CRM_Utils_System::url('civicrm/admin/extensions', 'reset=1'));
      $url = CRM_Utils_System::url('civicrm/admin/setting/url', "reset=1&civicrmDestination=${civicrmDestination}");
      $errors[] = array(
        'title' => ts('Invalid Base URL'),
        'message' => ts('The extensions URL is not properly set. Please go to the <a href="%1">URL setting page</a> and correct it.<br/>',
          array(
            1 => $url,
          )
        )
      );
    }

    return $errors;
  }
}