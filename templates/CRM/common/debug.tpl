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
{if isset($smarty.get.smartyDebug|smarty:nodefaults)}
{debug}
{/if}

{if isset($smarty.get.sessionReset|smarty:nodefaults)}
{$session->reset($smarty.get.sessionReset)}
{/if}

{if isset($smarty.get.sessionDebug|smarty:nodefaults)}
{$session->debug($smarty.get.sessionDebug)}
{/if}

{if isset($smarty.get.directoryCleanup|smarty:nodefaults)}
{$config->cleanup($smarty.get.directoryCleanup)}
{/if}

{if isset($smarty.get.cacheCleanup|smarty:nodefaults)}
{$config->clearDBCache()}
{/if}

{if isset($smarty.get.configReset|smarty:nodefaults)}
{$config->reset()}
{/if}
