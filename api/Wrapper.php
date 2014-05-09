<?php

interface API_Wrapper {

  /**
   * @param $apiRequest
   *
   * @return modified $apiRequest
   */
  function fromApiInput($apiRequest);

  /**
   * @param $apiRequest
   * @param $result
   *
   * @return modified $result
   */
  function toApiOutput($apiRequest, $result);
}
