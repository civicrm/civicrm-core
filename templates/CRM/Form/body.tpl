{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
*}
{include file="CRM/common/stateCountry.tpl"}

{if $form.javascript}
  {$form.javascript}
{/if}

{if $form.hidden}
  <div>{$form.hidden}</div>
{/if}

{if ! $suppressForm and count($form.errors) gt 0}
   <div class="messages crm-error">
       <div class="icon red-icon alert-icon"></div>
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

