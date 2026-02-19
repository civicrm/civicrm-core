<?php

namespace Civi\Connect;

/**
 * A list of URLs (actions) that you can use to generate an API key for a remote service.
 *
 * The canonical "initiator" action would be to have the web-user request
 * an "Authorization Grant" on a remote server using OAuth2. However, the
 * exact management of OAuth2 clients (credentials, lifecycle, etc) can vary
 * by service+topology. This mechanism is not specifically tied to OAuth2.
 */
class Initiators {

  public static function create(array $context): Initiators {
    $instance = new Initiators($context);
    \CRM_Utils_Hook::initiators($instance->context, $instance->available, $instance->default);
    $instance->normalize();
    return $instance;
  }

  /**
   * Descriptor for the context/purpose for which we want an API key.
   *
   * @var array
   *   Some combination of:
   *     - for: string (REQUIRED), a symbol that identifies the kind of context. Possible values:
   *         - "PaymentProcessor" (v6.7+): Add or reset the API key for a PaymentProcessor
   *     - payment_processor_type: string (OPTIONAL), a symbol like "PayPal" or "Stripe" which identifies the type of payment-processor
   *     - payment_processor_id: int (OPTIONAL), unique id for the PaymentProcessor record
   *     - is_test: bool (OPTIONAL), whether this payproc is for testing
   * @readonly
   */
  public array $context;

  /**
   * Identifier for the suggested/default initiator.
   *
   * @var string|null
   *   The symbolic-key (match the index of $this->available).
   */
  public ?string $default = NULL;

  /**
   * List of available actions. Each item has a symbolic-key, and it has the properties:
   *
   * @var array
   *     - title: string
   *     - render: callback to generate the UI widget
   *        Signature: function(CRM_Core_Region $region, array $context, array $initiator):
   *     - name: string (COMPUTED; same as array-key)
   *     - url: string (COMPUTED; HTTP address for this initiator)
   *     - is_default: bool (COMPUTED)
   */
  public array $available = [];

  /**
   * @param array $context
   */
  public function __construct(array $context) {
    if (empty($context['for']) || !is_string($context['for'])) {
      throw new \CRM_Core_Exception('Initiators: Missing required context value \"for\"');
    }
    $this->context = $context;
  }

  public function normalize() {
    foreach ($this->available as $key => &$initiator) {
      $initiator['name'] = $key;
      $initiator['is_default'] = ($key === $this->default);
    }

    $lcMessages = \CRM_Core_I18n::getLocale();
    $collator = new \Collator($lcMessages . '.utf8');
    uasort($this->available, fn($a, $b) => $collator->compare($a['title'], $b['title']));

    return $this;
  }

  public function get(string $name): ?array {
    return $this->available[$name] ?? NULL;
  }

}
