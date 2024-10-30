<?php

/*
 * This is the same as symfony 4 EventDispatcherInterface
 * except the Event parameter to dispatch() can be an object, to support
 * symfony 5+ because there is no Event definition anymore.
 *
 * Also some style changes to make it pass style checking.
 */

namespace Civi\Core;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * The EventDispatcherInterface is the central point of Symfony's event listener system.
 * Listeners are registered on the manager and events are dispatched through the
 * manager.
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
interface CiviEventDispatcherInterface {

  /**
   * Dispatches an event to all registered listeners.
   *
   * @param string $eventName The name of the event to dispatch. The name of
   *   the event is the name of the method that is invoked on listeners.
   * @param object|null $event The event to pass to the event handlers/listeners
   *   The dispatcher technically accepts any kind of event-object.
   *
   *   CiviCRM typically uses GenericHookEvent. Specifically:
   *   - For `hook_civicrm_*`, GenericHookEvent is strongly required.
   *   - For `civi.*`, GenericHookEvent is typical but not strongly required.
   *
   *   Symfony also defines classes for event-objects (`Event`, `GenericEvent`).
   *   Historically, these types -were- mandatory, but they have had poor
   *   interoperability (across versions/environments). `GenericHookEvent` has
   *   provided easier interoperability.
   *
   *   Looking forward, the current dispatcher does not specifically require any
   *   single type, and there may be use-cases where libraries or extensions
   *   define other types. The suitability of this is left as an exercise to the reader.
   *
   *   If $event is a null, then an empty placeholder (`GenericHookEvent`) is used.
   * @return object
   *   The final event object.
   */
  public function dispatch($eventName, $event = NULL);

  /**
   * Adds an event listener that listens on the specified events.
   *
   * @param string $eventName The event to listen on
   * @param callable $listener The listener
   * @param int $priority  The higher this value, the earlier an event
   *   listener will be triggered in the chain (defaults to 0)
   */
  public function addListener($eventName, $listener, $priority = 0);

  /**
   * Adds an event subscriber.
   *
   * The subscriber is asked for all the events it is
   * interested in and added as a listener for these events.
   */
  public function addSubscriber(EventSubscriberInterface $subscriber);

  /**
   * Removes an event listener from the specified events.
   *
   * @param string $eventName The event to remove a listener from
   * @param callable $listener The listener to remove
   */
  public function removeListener($eventName, $listener);

  public function removeSubscriber(EventSubscriberInterface $subscriber);

  /**
   * Gets the listeners of a specific event or all listeners sorted by descending priority.
   *
   * @param string|null $eventName The name of the event
   *
   * @return array The event listeners for the specified event, or all event listeners by event name
   */
  public function getListeners($eventName = NULL);

  /**
   * Gets the listener priority for a specific event.
   *
   * Returns null if the event or the listener does not exist.
   *
   * @param string $eventName The name of the event
   * @param callable $listener The listener
   *
   * @return int|null The event listener priority
   */
  public function getListenerPriority($eventName, $listener);

  /**
   * Checks whether an event has any registered listeners.
   *
   * @param string|null $eventName The name of the event
   *
   * @return bool true if the specified event has any listeners, false otherwise
   */
  public function hasListeners($eventName = NULL);

}
