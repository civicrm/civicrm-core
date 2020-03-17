{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* this template is used for adding/editing Additional Detail *}
{if $isOnline}{assign var=valueStyle value=" class='view-value'"}{else}{assign var=valueStyle value=""}{/if}
<div id="id-additionalDetail" class="section-shown">
    <table class="form-layout-compressed">
        <tr  class="crm-contribution-form-block-contribution_page"><td class="label">{$form.contribution_page_id.label}</td><td{$valueStyle}>{$form.contribution_page_id.html|crmAddClass:twenty}</td></tr>
        <tr class="crm-contribution-form-block-note"><td class="label" style="vertical-align:top;">{$form.note.label}</td><td>{$form.note.html}</td></tr>
        <tr class="crm-contribution-form-block-non_deductible_amount"><td class="label">{$form.non_deductible_amount.label}</td><td{$valueStyle}>{$form.non_deductible_amount.html|crmMoney:$currency:'':1}<br />
            <span class="description">{ts}Non-deductible portion of this contribution.{/ts}</span></td></tr>
        <tr class="crm-contribution-form-block-fee_amount"><td class="label">{$form.fee_amount.label}</td><td{$valueStyle}>{$form.fee_amount.html|crmMoney:$currency:'XXX':'YYY'}<br />
            <span class="description">{ts}Processing fee for this transaction (if applicable).{/ts}</span></td></tr>
            <span class="description">{ts}Net value of the contribution (Total Amount minus Fee).{/ts}</span></td></tr>
        <tr class="crm-contribution-form-block-invoice_id"><td class="label">{$form.invoice_id.label}</td><td{$valueStyle}>{$form.invoice_id.html}<br />
            <span class="description">{ts}Unique internal reference ID for this contribution.{/ts}</span></td></tr>
        <tr class="crm-contribution-form-block-creditnote_id"><td class="label">{$form.creditnote_id.label}</td><td{$valueStyle}>{$form.creditnote_id.html}<br />
            <span class="description">{ts}Unique internal Credit Note ID for this contribution.{/ts}</span></td></tr>
        <tr class="crm-contribution-form-block-thankyou_date"><td class="label">{$form.thankyou_date.label}</td><td>{$form.thankyou_date.html}<br />
            <span class="description">{ts}Date that a thank-you message was sent to the contributor.{/ts}</span></td></tr>
    </table>
</div>
