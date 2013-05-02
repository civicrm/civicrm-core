<?php

interface API_Wrapper {

  /**
   * @return modified $apiRequest
   */
  function fromApiInput($apiRequest);

  /**
   * @return modified $result
   */
  function toApiOutput($apiRequest, $result);
}
