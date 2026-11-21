<?php
declare(strict_types = 1);

use CRM_Postbox_ExtensionUtil as E;

final class CRM_Postbox_BAO_EmailMessageRecipient extends CRM_Postbox_DAO_EmailMessageRecipient {

  public static function getTypes(): array {
    return [
      'to' => E::ts('To'),
      'cc' => E::ts('CC'),
      'bcc' => E::ts('BCC'),
    ];
  }

}
