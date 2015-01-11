<?php

/**
 * Interface API_Wrapper
 */
interface API_Wrapper {

  /**
   * @param array $apiRequest
   *
   * @return array
   *   modified $apiRequest
   */
  function fromApiInput($apiRequest);

  /**
   * @param array $apiRequest
   * @param array $result
   *
   * @return array
   *   modified $result
   */
  function toApiOutput($apiRequest, $result);
}
