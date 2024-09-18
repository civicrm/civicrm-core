<?php
use CRM_Standaloneusers_ExtensionUtil as E;
use Civi\Standalone\MFA\Base as MFABase;

class CRM_Standaloneusers_Page_MFA extends CRM_Core_Page {

  public function run() {

    $mfas = ['TOTP'];
    // Create an event object with all the data you wan to pass in.
    $event = Civi\Core\Event\GenericHookEvent::create(['mfaClasses' => &$mfas]);
    Civi::dispatcher()->dispatch('civi.standalone.altermfaclasses', $event);
    // Check the list looks ok.
    $legit = [['name' => '', 'label' => E::ts("Disable MFA"), 'selected' => empty($configuredMfas)]];
    $configuredMfas = MFABase::getAvailableClasses(TRUE);
    foreach ($mfas as $shortClassName) {
      $mfaClass = "Civi\\Standalone\\MFA\\$shortClassName";
      if (is_subclass_of($mfaClass, 'Civi\\Standalone\\MFA\\MFAInterface') && class_exists($mfaClass)) {
        // The code is available, all good.
        $legit[] = ['name' => $shortClassName, 'label' => $shortClassName, 'selected' => in_array($shortClassName, $configuredMfas)];
      }
    }

    // Remove breadcrumb for login page.
    $this->assign('breadcrumb', NULL);
    $this->assign('mfas', $legit);
    // $this->assign('pageTitle', 'Configu');
    parent::run();
  }

}
