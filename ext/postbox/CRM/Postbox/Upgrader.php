<?php

use CRM_Postbox_ExtensionUtil as E;

/**
 * Collection of upgrade steps.
 */
class CRM_Postbox_Upgrader extends \CRM_Extension_Upgrader_Base {

  public function upgrade_1000(): bool {
    E::schema()->createEntityTable('upgrade/schema/1000-EmailMessageRecipient.entityType.php');

    // migrate from to_contact_id => EmailMessageRecipient table
    CRM_Core_DAO::executeQuery('INSERT INTO civicrm_email_message_recipient (email_message_id, contact_id, recipient_type) SELECT id, to_contact_id, "to" FROM civicrm_email_message;');

    E::schema()->dropSchemaField('EmailMessage', 'to_contact_id');

    return TRUE;
  }

}
