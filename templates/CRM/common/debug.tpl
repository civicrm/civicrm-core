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
{if $debugging.smartyDebug}
{debug}
{/if}

{if $debugging.sessionReset}
{$session->reset($debugging.sessionReset)}
{/if}

{if $debugging.sessionDebug}
{$session->debug($debugging.sessionDebug)}
{/if}

{if $debugging.directoryCleanup}
{$config->cleanup($debugging.directoryCleanup)}
{/if}

{if $debugging.cacheCleanup}
{$config->clearDBCache()}
{/if}

{if $debugging.configReset}
{$config->reset()}
{/if}
