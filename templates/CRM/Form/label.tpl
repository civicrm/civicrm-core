{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{if $error}
  <span class="crm-error crm-error-label">
    {$label}
    {if $required}
       <span class="crm-marker" title="{ts escape='htmlattribute'}This field is required.{/ts}">*</span>
  {/if}
  </span>
{else}
  {$label}
  {if $required}
   <span class="crm-marker" title="{ts escape='htmlattribute'}This field is required.{/ts}">*</span>
{/if}
{/if}

