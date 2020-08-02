{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* Handles display of passed $infoMessage. *}
{if $infoMessage or $infoTitle}
  <div class="messages status {$infoType}"{if $infoOptions} data-options='{$infoOptions}'{/if}>
    {icon icon="fa-info-circle"}{/icon}
    <span class="msg-title">{$infoTitle}</span>
    <span class="msg-text">{$infoMessage}</span>
  </div>
{/if}
