<?php
namespace Civi\Token\Event;

/**
 * Class TokenRegisterEvent
 * @package Civi\Token\Event
 *
 * The TokenRegisterEvent is fired when constructing a list of available
 * tokens. Listeners may register by specifying the entity/field/label for the token.
 *
 * @code
 * $ev->entity('profile')
 *    ->register('viewUrl', ts('Default Profile URL (View Mode)')
 *    ->register('editUrl', ts('Default Profile URL (Edit Mode)');
 * $ev->register(array(
 *   'entity' => 'profile',
 *   'field' => 'viewUrl',
 *   'label' => ts('Default Profile URL (View Mode)'),
 * ));
 * @endcode
 */
class TokenRegisterEvent extends TokenEvent {

  /**
   * Default values to put in new registrations.
   *
   * @var array
   */
  protected $defaults;

  public function __construct($tokenProcessor, $defaults) {
    parent::__construct($tokenProcessor);
    $this->defaults = $defaults;
  }

  /**
   * Set the default entity name.
   *
   * @param string $entity
   * @return TokenRegisterEvent
   */
  public function entity($entity) {
    $defaults = $this->defaults;
    $defaults['entity'] = $entity;
    return new TokenRegisterEvent($this->tokenProcessor, $defaults);
  }

  /**
   * Register a new token.
   *
   * @param array|string $paramsOrField
   * @param NULL|string $label
   * @return TokenRegisterEvent
   */
  public function register($paramsOrField, $label = NULL) {
    if (is_array($paramsOrField)) {
      $params = $paramsOrField;
    }
    else {
      $params = array(
        'field' => $paramsOrField,
        'label' => $label,
      );
    }
    $params = array_merge($this->defaults, $params);
    $this->tokenProcessor->addToken($params);
    return $this;
  }

}
