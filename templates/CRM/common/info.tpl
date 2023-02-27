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
{if $infoMessage|smarty:nodefaults || $infoTitle|smarty:nodefaults}
  <div class="messages status {$infoType}"{if $infoOptions|smarty:nodefaults} data-options='{$infoOptions|smarty:nodefaults}'{/if}>
    {icon icon="fa-info-circle"}{/icon}
    <span class="msg-title">{$infoTitle}</span>
    <span class="msg-text">{$infoMessage|smarty:nodefaults|purify}</span>
  </div>
{/if}
