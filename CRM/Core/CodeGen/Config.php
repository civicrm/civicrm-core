<?php

/**
 * Generate configuration files
 */
class CRM_Core_CodeGen_Config extends CRM_Core_CodeGen_BaseTask {
  function run() {
    $this->generateTemplateVersion();

    $this->setupCms();
  }

  function generateTemplateVersion() {
    file_put_contents($this->config->tplCodePath . "/CRM/common/version.tpl", $this->config->db_version);
  }

  function setupCms() {
    if (!in_array(strtolower($this->config->cms), array(
      'drupal', 'joomla', 'wordpress'))) {
      echo "Config file for '{$this->config->cms}' not known.";
      exit();
    }
    elseif ($this->config->cms !== 'joomla') {
      $configTemplate = $this->findConfigTemplate($this->config->cms);
      if ($configTemplate) {
        copy($configTemplate, CRM_Utils_Path::join($this->config->civicrm_root_path, 'civicrm.config.php'));
      } else {
        throw new Exception("Failed to locate template for civicrm.config.php");
      }
    }

    $template = new CRM_Core_CodeGen_Util_Template($this->config, 'php');
    $template->assign('db_version', $this->config->db_version);
    $template->assign('cms', ucwords($this->config->cms));
    $template->run('civicrm_version.tpl', CRM_Utils_Path::join($this->config->phpCodePath, "civicrm-version.php"));
  }

  /**
   * @param string $cms "drupal"|"wordpress"
   * @return null|string path to config template
   */
  public function findConfigTemplate($cms) {
    $cms = strtolower($cms);
    $candidates = array();
    switch ($cms) {
      case 'drupal':
        $candidates[] = "drupal/civicrm.config.php.drupal";
        break;
      case 'wordpress':
        $candidates[] = "WordPress/civicrm.config.php.wordpress";
        break;
    }
    foreach ($candidates as $candidate) {
      $candidate = CRM_Utils_Path::join($this->config->civicrm_root_path, $candidate);
      if (file_exists($candidate)) {
        return $candidate;
        break;
      }
    }
    return NULL;
  }
}
