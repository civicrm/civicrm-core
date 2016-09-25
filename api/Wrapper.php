<?php

/**
 * Interface API_Wrapper
 */
interface API_Wrapper {

  /**
   * Interface for interpreting api input.
   *
   * @param array $apiRequest
   *
   * @return array
   *   modified $apiRequest
   */
  public function fromApiInput($apiRequest);

  /**
   * Interface for interpreting api output.
   *
   * @param array $apiRequest
   * @param array $result
   *
   * @return array
   *   modified $result
   */
  public function toApiOutput($apiRequest, $result);

}
