{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{if $outputMode eq 'html' && !$rows}
  <div class="messages status no-popup">
    <div class="icon inform-icon"></div>&nbsp; {ts}None found.{/ts}
  </div>
{/if}
