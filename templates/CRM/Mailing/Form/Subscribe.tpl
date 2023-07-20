{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* this template is used for web-based subscriptions to mailing list type groups  *}
<div class="crm-block crm-mailing-subscribe-form-block">
  {if $single}
    <div class="help">{ts}Enter your email address and click <strong>Subscribe</strong>. You will receive a confirmation request via email shortly. Your subscription will be activated after you respond to that email.{/ts}</div>
  {else}
    <div class="help">{ts}Enter your email address and check the box next to each mailing list you want to join. Then click the <strong>Subscribe</strong> button. You will receive a confirmation request via email for each selected list. Activate your subscription to each list by responding to the corresponding confirmation email.{/ts}</div>
  {/if}
  <table class="form-layout-compressed">
    <tr class="crm-mailing-subscribe-form-block-email"><td style="width: 10%;">{$form.email.label}</td><td>{$form.email.html}</td></tr>
    <tr><td colspan="2">
        <div class="spacer"></div>

        {if ! $single} {* Show all public mailing list groups. Page was loaded w/o a specific group param (gid=N not in query string). *}
            <table class="selector" style="width: auto;">
            {counter start=0 skip=1 print=false}
            {foreach from=$rows item=row}
            <tr id='rowid{$row.id}' class="{cycle values="odd-row,even-row"}">
                {assign var=cbName value=$row.checkbox}
                <td class="crm-mailing-subscribe-form-block-{$cbName}">{$form.$cbName.html}</td>
                <td class="crm-mailing-subscribe-form-block-title"><label for="{$cbName}"><strong>{$row.title}</strong></label></td>
                <td class="crm-mailing-subscribe-form-block-description">{$row.description}</td>
            </tr>
            {/foreach}
            </table>
        {/if}
        </td>
    </tr>
  </table>
  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>
