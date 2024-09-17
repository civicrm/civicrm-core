<?php
namespace Civi\Standalone\MFA;

interface MFAInterface {

  public function getFormUrl(): string;

  public function checkMFAData($data):bool;

}
