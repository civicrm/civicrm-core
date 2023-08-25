<?php

/**
 * @mixinName phpstorm
 * @mixinVersion 1.0.0
 *
 * @param CRM_Extension_MixInfo $mixInfo
 *   On newer deployments, this will be an instance of MixInfo. On older deployments, Civix may polyfill with a work-a-like.
 * @param \CRM_Extension_BootCache $bootCache
 *   On newer deployments, this will be an instance of MixInfo. On older deployments, Civix may polyfill with a work-a-like.
 */
return function ($mixInfo, $bootCache) {

  // We want to register a late-stage listener for hook_civicrm_container, but... it's a special hook.
  // Therefore, we apply the Shenanigan technique.
  Civi::dispatcher()->addListener('&hook_civicrm_container', function($container) use ($mixInfo) {
    if ($mixInfo->isActive()) {
      \Civi\PhpStorm\Generator::generate($container);
    }
  }, -2000);

};
