<?php


namespace Civi\Api4\Action\MessageTemplate;

use Civi\Api4\Generic\Result;
use Civi\Token\TokenProcessor;

/**
 * Class Render.
 *
 * Get the content of an email for the given template text, rendering tokens.
 *
 * @method int setMessageTemplateId(int $messageTemplateID) Set Message Template ID.
 * @method int getMessageTemplateId(int $messageTemplateID) Get Message Template ID.
 * @method string setMessageSubject(string $messageSubject) Set Message Subject
 * @method string getMessageSubject(string $messageSubject) Get Message Subject
 * @method string setMessageHtml(string $messageHtml) Set Message Html
 * @method string getMessageHtml string $messageHtml) Get Message Html
 * @method string setMessageText(string $messageHtml) Set Message Text
 * @method string getMessageText string $messageHtml) Get Message Text
 * @method string getMessages(array $stringToParse) Get array of adhoc strings to parse.
 * @method string setMessages(array $stringToParse) Set array of adhoc strings to parse.
 * @method array setEntity(string $entity) Set entity.
 * @method array getEntity(string $entity) Get entity.
 * @method array setEntityIds(array $entityIds) Set entity IDs
 * @method array getEntityIds(array $entityIds) Get entity IDs
 */
class Render extends \Civi\Api4\Generic\AbstractAction {

  /**
   * ID of message template.
   *
   * It is necessary to pass this or at least one string.
   *
   * @var int
   */
  protected $messageTemplateId;

  /**
   * Ad hoc html strings to parse.
   *
   * Array of adhoc strings arrays to pass e.g
   *  [
   *    ['string' => 'Dear {contact.first_name}', 'format' => 'text/html', 'key' => 'greeting'],
   *    ['string' => 'Also known as {contact.first_name}', 'format' => 'text/plain', 'key' => 'nick_name'],
   * ]
   *
   * If no provided the key will default to 'string' and the format will default to 'text'
   *
   * @var array
   */
  protected $messages = [];

  /**
   * String to be returned as the subject.
   *
   * @var string
   */
  protected $messageSubject;

  /**
   * String to be returned as the subject.
   *
   * @var string
   */
  protected $messageText;

  /**
   * String to be returned as the subject.
   *
   * @var string
   */
  protected $messageHtml;

  /**
   * Entity for which tokens need to be resolved.
   *
   * This is required if tokens related to the entity are to be parsed and the entity cannot
   * be derived from the message_template.
   *
   * Only Activity is currently supported in this initial implementation.
   *
   * @var string
   *
   * @options Activity
   *
   */
  protected $entity;

  /**
   * An array of one of more ids for which the html should be rendered.
   *
   * These will be the keys of the returned results.
   *
   * @var array
   */
  protected $entityIds = [];

  /**
   * @inheritDoc
   */
  public function _run(Result $result) {
    $tokenProcessor = new TokenProcessor(\Civi::dispatcher(), [
      'controller' => get_class(),
      'smarty' => FALSE,
      // Only activities, for now.... @todo - extend...
      'schema' => [$this->getEntity() => $this->getEntityKey()],
    ]);

    foreach ($this->getEntityIds() as $entity => $ids) {
      foreach ($this->getStringsToParse() as $fieldKey => $textField) {
        if (empty($textField['string'])) {
          continue;
        }
        foreach ($ids as $id) {
          $tokenProcessor->addRow()->context($this->getEntityKey(), $id);
          $tokenProcessor->addMessage($fieldKey, $textField['string'], $textField['format']);
          $tokenProcessor->evaluate();
          foreach ($tokenProcessor->getRows() as $row) {
            /* @var \Civi\Token\TokenRow $row */
            $result[$id][$fieldKey] = $row->render($fieldKey);
          }
        }
      }

    }
  }

  /**
   * Array holding
   *  - string String to parse, required
   *  - key Key to key by in results, defaults to 'string'
   *  - format - format passed to token providers.
   *
   * @param array $stringDetails
   *
   * @return \Civi\Api4\Action\MessageTemplate\Render
   */
  public function addMessage(array $stringDetails) {
    $this->messages[] = $stringDetails;
    return $this;
  }

  /**
   * Get the strings to render civicrm tokens for.
   *
   * @return array
   */
  protected function getStringsToParse(): array {
    $textFields = [
      'msg_html' => ['string' => $this->getMessageHtml(), 'format' => 'text/html', 'key' => 'msg_html'],
      'msg_subject' => ['string' => $this->getMessageSubject(), 'format' => 'text/plain', 'key' => 'msg_subject'],
      'msg_text' => ['string' => $this->getMessageText(), 'format' => 'text/plain', 'key' => 'msg_text'],
    ];
    foreach ($this->getMessages() as $message) {
      $message['key']  = $message['key'] ?? 'string';
      $message['format'] = $message['format'] ?? 'text/plain';
      $textFields[$message['key']] = $message;
    }
    return $textFields;
  }

  /**
   * Get the key to use for the entity ID field.
   *
   * @return string
   */
  protected function getEntityKey(): string {
    return strtolower($this->getEntity()) . '_id';
  }

}
