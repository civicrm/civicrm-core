{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{if !empty($outputMode) && $outputMode eq 'html' && empty($rows)}
  <div class="messages status no-popup">
    {icon icon="fa-info-circle"}{/icon} {ts}None found.{/ts}
  </div>
{/if}
