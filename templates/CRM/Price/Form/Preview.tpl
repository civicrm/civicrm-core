{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
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
        {include file="CRM/Price/Form/PriceSet.tpl" hideTotal=false isShowAdminVisibilityFields=false}
      </fieldset>
    {/foreach}
  {/strip}
  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>
