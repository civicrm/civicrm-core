<?php

namespace Civi\Postbox;

use Civi\Api4\Service\Spec\FieldSpec;
use Civi\Api4\Service\Spec\Provider\Generic\SpecProviderInterface;
use Civi\Api4\Service\Spec\RequestSpec;
use Civi\Core\Event\PostEvent;
use Civi\Core\Service\AutoService;
use CRM_Postbox_BAO_EmailMessageRecipient;
use CRM_Postbox_ExtensionUtil as E;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @service
 * @internal
 */
class EmailMessageSpecProvider extends AutoService implements SpecProviderInterface, EventSubscriberInterface {

  public static function getSubscribedEvents(): array {
    return [
      'hook_civicrm_post::EmailMessage' => 'saveRecipients',
    ];
  }

  public function applies(string $entity, string $action): bool {
    return ($entity === 'EmailMessage') && \in_array($action, ['get', 'create']);
  }

  public function modifySpec(RequestSpec $spec): void {
    foreach (CRM_Postbox_BAO_EmailMessageRecipient::getTypes() as $recipientType => $label) {
      $field = new FieldSpec("{$recipientType}_contact_id", 'EmailMessage', 'Array');
      $field->setTitle($label);
      $field->setLabel($label);
      $field->setColumnName('id');

      $field->setDataType('Integer');
      $field->setFkEntity('Contact');
      $field->setInputType('EntityRef');

      // cc and bcc can have multiple contacts
      // we don't support multiple contacts in TO yet
      // because CRM_Utils_Mail::send doesn't
      if ($recipientType !== 'to') {
        $field->setSerialize(\CRM_Core_DAO::SERIALIZE_COMMA);
        $field->setInputAttrs(['multiple' => TRUE]);
      }

      $field->setSqlRenderer([__CLASS__, 'getRecipientsSql']);
      // $field->addSqlFilter([__CLASS__, 'filterRecipientsSql']);

      $spec->addFieldSpec($field);
    }
  }

  public static function getRecipientsSql(array $field): string {
    $recipientType = strtok($field['name'], '_');
    // This will be used in a SELECT clause, but in WHERE clauses it's overridden by self::filterRecipientSql.
    return "(SELECT GROUP_CONCAT(`civicrm_email_message_recipient`.`contact_id`)
              FROM `civicrm_email_message_recipient`
              WHERE `civicrm_email_message_recipient`.`emaiL_message_id` = {$field['sql_name']}
              AND `civicrm_email_message_recipient`.`recipient_type` = '{$recipientType}')";
  }

  public function saveRecipients(PostEvent $e): void {
    if ($e->action !== 'create') {
      return;
    }

    foreach (CRM_Postbox_BAO_EmailMessageRecipient::getTypes() as $recipientType => $label) {
      $fieldName = "{$recipientType}_contact_id";

      $value = $e->getValue($fieldName);

      if ($value) {

        $value = is_array($value) ? $value : explode(',', (string) $value);

        \Civi\Api4\EmailMessageRecipient::save(FALSE)
          ->setRecords(array_map(fn ($contactId) => [
            'email_message_id' => $e->id,
            'contact_id' => $contactId,
            'recipient_type' => $recipientType,
          ], $value))
          ->execute();
      }
    }
  }

}
