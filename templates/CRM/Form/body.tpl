{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{if $form.javascript}
  {$form.javascript}
{/if}

{if $form.hidden}
  <div>{$form.hidden}</div>
{/if}

{if ($snippet !== 'json') and !$suppressForm and count($form.errors) gt 0}
   <div class="messages crm-error">
       <i class="crm-i fa-exclamation-triangle crm-i-red"></i>
     {ts}Please correct the following errors in the form fields below:{/ts}
     <ul id="errorList">
     {foreach from=$form.errors key=errorName item=error}
        {if is_array($error)}
           <li>{$error.label} {$error.message}</li>
        {else}
           <li>{$error}</li>
        {/if}
     {/foreach}
     </ul>
   </div>
{/if}

{* Add all the form elements sent in by the hook *}
{if $beginHookFormElements}
  <table class="form-layout-compressed">
  {foreach from=$beginHookFormElements key=dontCare item=hookFormElement}
      <tr><td class="label nowrap">{$form.$hookFormElement.label}</td><td>{$form.$hookFormElement.html}</td></tr>
  {/foreach}
  </table>
{/if}
