<?php

class CRM_Utils_Cache_SerializeCache implements CRM_Utils_Cache_Interface {

  /**
   * The cache storage container, an array by default, stored in a file under templates
   */
  private $_cache;

  /**
   * Constructor
   *
   * @param array   $config  an array of configuration params
   *
   * @return void
   */
  function __construct($config) {
    $this->_cache = array();
  }

  function fileName ($key) {
    if (strlen($key) > 50)
      return CIVICRM_TEMPLATE_COMPILEDIR ."CRM_".md5($key).".php"; 
    return CIVICRM_TEMPLATE_COMPILEDIR .$key.".php";
  }

  function get ($key) {
    if (array_key_exists($key,$this->_cache))
      return $this->_cache[$key];

    if (!file_exists($this->fileName ($key))) {
      return;
    }
    $this->_cache[$key] = unserialize (substr (file_get_contents ($this->fileName ($key)),8));
    return $this->_cache[$key];
  }

  function set($key, &$value) {
    if (file_exists($this->fileName ($key))) {
      return;
    }
    $this->_cache[$key] = $value;
    file_put_contents ($this->fileName ($key),"<?php //".serialize ($value));   
  }

  function delete($key) {
    if (file_exists($this->fileName ($key))) {
      unlink ($this->fileName ($key));
    }
    unset($this->_cache[$key]);
  }

  function flush($key =null) {
    $prefix = "CRM_";
    if (!$handle = opendir(CIVICRM_TEMPLATE_COMPILEDIR)) {
      return; // die? Error?
    }
    while (false !== ($entry = readdir($handle))) {
      if (substr ($entry,0,4) == $prefix) {
        unlink (CIVICRM_TEMPLATE_COMPILEDIR.$entry);
      }
    }
    closedir($handle);
    unset($this->_cache);
    $this->_cache = array();
  }
}

