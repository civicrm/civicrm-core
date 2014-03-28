{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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
{capture assign=infoTitle}{ts}Preview Mode{/ts}{/capture}
{assign var="infoType" value="info"}
{if $preview_type eq 'group'}
  {capture assign=infoMessage}{ts}Showing price set as it will be displayed within a form.{/ts}{/capture}
{else}
  {capture assign=infoMessage}{ts}Showing field as it will be displayed in a form.{/ts}{/capture}
{/if}
{include file="CRM/common/info.tpl"}
<div class="crm-block crm-form-block crm-price-set-preview-block">
  {strip}
    {foreach from=$groupTree item=priceSet key=group_id}
      <fieldset>
        {if $preview_type eq 'group'}<legend>{$setTitle}</legend>{/if}
        {include file="CRM/Price/Form/PriceSet.tpl"}
      </fieldset>
    {/foreach}
  {/strip}
  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>
