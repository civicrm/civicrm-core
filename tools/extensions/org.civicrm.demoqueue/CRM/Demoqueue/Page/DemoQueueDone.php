<?php

require_once 'CRM/Core/Page.php';

/**
 * Class CRM_Demoqueue_Page_DemoQueueDone
 */
class CRM_Demoqueue_Page_DemoQueueDone extends CRM_Core_Page {
  function run() {
    // Example: Set the page-title dynamically; alternatively, declare a static title in xml/Menu/*.xml
    CRM_Utils_System::setTitle(ts('DemoQueueDone'));
    parent::run();
  }
}
