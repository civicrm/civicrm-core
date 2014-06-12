<?php
interface CRM_Core_FileSearchInterface {
  const DEFAULT_SEARCH_LIMIT = 20;
  const DEFAULT_SEARCH_OFFSET = 0;

  /**
   * @param array $query any of the following:
   *  - text: string, plain text to search for
   *  - parent_table: string - entity to which file is directly attached
   *  - parent_id: int - entity to which file is directly attached
   *  - xparent_table: string - business-entity to which file is attached (directly or indirectly)
   *  - xparent_id: int - business-entity to which file is attached (directly or indirectly)
   * @param int $limit
   * @param int $offset
   * @return array each item has keys:
   *  - file_id: int
   *  - parent_table: string - entity to which file is directly attached
   *  - parent_id: int - entity to which file is directly attached
   *  - xparent_table: string - business-entity to which file is attached (directly or indirectly)
   *  - xparent_id: int - business-entity to which file is attached (directly or indirectly)
   */
  function search($query, $limit = self::DEFAULT_SEARCH_LIMIT, $offset = self::DEFAULT_SEARCH_OFFSET);
}