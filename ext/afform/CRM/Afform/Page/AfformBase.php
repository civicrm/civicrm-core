<?php
use CRM_Afform_ExtensionUtil as E;

class CRM_Afform_Page_AfformBase extends CRM_Core_Page {

  public function run() {
    //    echo '<pre>';print_r(func_get_args());exit();
    list ($pagePath, $pageArgs) = func_get_args();

    // Example: Set the page-title dynamically; alternatively, declare a static title in xml/Menu/*.xml
    CRM_Utils_System::setTitle(E::ts('AfformBase'));

    // Example: Assign a variable for use in a template
    $this->assign('currentTime', date('Y-m-d H:i:s'));

    $this->assign('afform', $pageArgs['afform']);

    parent::run();
  }

}
