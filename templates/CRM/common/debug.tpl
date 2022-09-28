{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
<!-- .tpl file invoked: {$tplFile}. Call via form.tpl if we have a form in the page. -->
{if !empty($smarty.get.smartyDebug)}
{debug}
{/if}

{if !empty($smarty.get.sessionReset)}
{$session->reset($smarty.get.sessionReset)}
{/if}

{if !empty($smarty.get.sessionDebug)}
{$session->debug($smarty.get.sessionDebug)}
{/if}

{if !empty($smarty.get.directoryCleanup)}
{$config->cleanup($smarty.get.directoryCleanup)}
{/if}

{if !empty($smarty.get.cacheCleanup)}
{$config->clearDBCache()}
{/if}

{if !empty($smarty.get.configReset)}
{$config->reset()}
{/if}
