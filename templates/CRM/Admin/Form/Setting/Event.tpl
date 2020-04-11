{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{capture assign=docLink}{docURL page="CiviEvent Cart Checkout" text="CiviEvent Cart Checkout" resource="wiki"}{/capture}
<div class="crm-block crm-form-block">
<div class="help">
    {ts 1=$docLink}These settings are used to configure properties for the CiviEvent component. Please read the %1 documentation, and make sure you understand it before modifying default values.{/ts}
</div>
<div class="crm-block crm-form-block">
<div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div>
      <table class="form-layout-compressed">
        <tr class="crm-mail-form-block-enable_cart">
            <td class="label">{$form.enable_cart.label}</td><td>{$form.enable_cart.html}<br />
            <span class="description">{ts}Check to enable the Event Cart checkout workflow.{/ts}</span></td>
        </tr>
      </table>
<div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
<div class="spacer"></div>
</div>
