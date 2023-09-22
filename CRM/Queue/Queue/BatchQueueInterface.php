<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

/**
 * Variation on CRM_Queue_Queue which can claim/release/delete items in batches.
 */
interface CRM_Queue_Queue_BatchQueueInterface {

  /**
   * Get a batch of queue items.
   *
   * @param int $limit
   *   Maximum number of records to claim
   * @param int|null $lease_time
   *   Hold a lease on the claimed item for $X seconds.
   *   If NULL, inherit a default.
   * @return object
   *   with key 'data' that matches the inputted data
   */
  public function claimItems(int $limit, ?int $lease_time = NULL): array;

  /**
   * Remove items from the queue.
   *
   * @param array $items
   *   The item returned by claimItem.
   */
  public function deleteItems(array $items): void;

  /**
   * Get the full data for multiple items.
   *
   * This is a passive peek - it does not claim/steal/release anything.
   *
   * @param array $ids
   *   The unique IDs of the tasks within the queue.
   * @return array
   */
  public function fetchItems(array $ids): array;

  /**
   * Return an item that could not be processed.
   *
   * @param array $items
   *   The items returned by claimItem.
   */
  public function releaseItems(array $items): void;

}
