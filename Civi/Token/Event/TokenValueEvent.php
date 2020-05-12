<?php
namespace Civi\Token\Event;

/**
 * Class TokenValueEvent
 * @package Civi\Token\Event
 *
 * A TokenValueEvent is fired to convert raw query data into mergeable
 * tokens. For example:
 *
 * ```
 * $event = new TokenValueEvent($myContext, 'text/html', array(
 *   array('contact_id' => 123),
 *   array('contact_id' => 456),
 * ));
 *
 * // Compute tokens one row at a time.
 * foreach ($event->getRows() as $row) {
 *   $row->setTokens('contact', array(
 *     'profileUrl' => CRM_Utils_System::url('civicrm/profile/view', 'reset=1&gid=456&id=' . $row['contact_id']'),
 *   ));
 * }
 *
 * // Compute tokens with a bulk lookup.
 * $ids = implode(',', array_filter(CRM_Utils_Array::collect('contact_id', $event->getRows()), 'is_numeric'));
 * $dao = CRM_Core_DAO::executeQuery("SELECT contact_id, foo, bar FROM foobar WHERE contact_id in ($ids)");
 * while ($dao->fetch) {
 *   $row->setTokens('oddball', array(
 *     'foo' => $dao->foo,
 *     'bar' => $dao->bar,
 * ));
 * }
 * @encode
 *
 * Event name: 'civi.token.eval'
 */
class TokenValueEvent extends TokenEvent {

  /**
   * @return \Traversable<TokenRow>
   */
  public function getRows() {
    return $this->tokenProcessor->getRows();
  }

}
