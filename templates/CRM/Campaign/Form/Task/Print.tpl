{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
<div class="form-item">
     <span class="element-right">{$form.buttons.html}</span>
</div>

{if $rows}
<p></p>
{include file="CRM/Campaign/Form/Selector.tpl"}

<div class="form-item">
     <span class="element-right">{$form.buttons.html}</span>
</div>

{else}
   <div class="messages status no-popup">
     {icon icon="fa-info-circle"}{/icon}
        {ts}There are no records selected for Print.{/ts}
   </div>
{/if}
