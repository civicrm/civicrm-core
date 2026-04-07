{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}

<tr>
{include file="CRM/Core/DatePickerRangeWrapper.tpl" fieldName="receive_date" to='' from='' colspan="2" class='' hideRelativeLabel=0}
</tr>
<tr>
  <td><label>{ts}Contribution Amounts{/ts}</label> <br />
  {$form.contribution_amount_low.label}
  {$form.contribution_amount_low.html} &nbsp;&nbsp;
  {$form.contribution_amount_high.label}
  {$form.contribution_amount_high.html} </td>
  <td><label>{$form.contribution_status_id.label}</label> <br />
  {$form.contribution_status_id.html} </td>
</tr>
<tr>
  <td>
    <label>{ts}Currency{/ts}</label> <br />
    {$form.contribution_currency_type.html|crmAddClass:twenty}
  </td>
  {if $form.contribution_batch_id}
    <td>
      {$form.contribution_batch_id.label}<br />
      {$form.contribution_batch_id.html}
    </td>
  {/if}
</tr>
<tr>
  <td>
    <div class="float-left">
      <label>{$form.contribution_payment_instrument_id.label}</label> <br />
      {$form.contribution_payment_instrument_id.html|crmAddClass:twenty}
    </div>
    <div class="float-left" id="contribution_check_number_wrapper">
      {$form.contribution_check_number.label} <br />
      {$form.contribution_check_number.html}
    </div>
    <div class="float-left" id="financial_trxn_card_type_id_wrapper">
      {$form.financial_trxn_card_type_id.label} <br />
      {$form.financial_trxn_card_type_id.html}
    </div>
    <div class="float-left" id="pan_truncation_wrapper">
      {$form.financial_trxn_pan_truncation.label} <br />
      {$form.financial_trxn_pan_truncation.html}
    </div>
  </td>
  <td>
    {$form.contribution_trxn_id.label} <br />
    {$form.contribution_trxn_id.html}
  </td>
</tr>
<tr>
  <td>
    {if $form.contribution_or_softcredits}
      {$form.contribution_or_softcredits.label} <br />
      {$form.contribution_or_softcredits.html}<br />
      <div class="float-left" id="contribution_soft_credit_type_wrapper">
        {$form.contribution_soft_credit_type_id.label} <br />
        {$form.contribution_soft_credit_type_id.html|crmAddClass:twenty}
      </div>
    {/if}
  </td>
  <td>
    {$form.invoice_number.label} <br />
    {$form.invoice_number.html}
  </td>
</tr>
<tr>
  <td>
    <table style="width:auto">
      <tbody>
      <tr>
        <td>{$form.contribution_thankyou_date_is_not_null.label}</td>
        <td>
          {$form.contribution_thankyou_date_is_not_null.html}
        </td>
      </tr>
      <tr>
        <td>{$form.contribution_receipt_date_is_not_null.label}</td>
        <td>
          {$form.contribution_receipt_date_is_not_null.html}
        </td>
      </tr>
      <tr>
        <td>{$form.contribution_test.label} {help id="is_test" file="CRM/Contact/Form/Search/Advanced" title=$form.contribution_test.textLabel}</td>
        <td>
          {$form.contribution_test.html}
        </td>
      </tr>
      </tbody>
    </table>
  </td>
  <td>
    <table style="width:auto">
      <tbody>
      <tr>
        <td>{$form.contribution_pay_later.label}</td>
        <td>
          {$form.contribution_pay_later.html}
        </td>
      </tr>
      <tr>
        <td>{$form.contribution_recurring.label}</td>
        <td>
          {$form.contribution_recurring.html}
        </td>
      </tr>
      <tr>
        <td>{$form.is_template.label} {help id="is_template" file="CRM/Contact/Form/Search/Advanced"}</td>
        <td>
          {$form.is_template.html}
        </td>
      </tr>
      </tbody>
    </table>
  </td>
</tr>
<tr>
  <td>
    <label>{ts}Financial Type{/ts}</label> <br />
    {$form.financial_type_id.html|crmAddClass:twenty}
  </td>
  <td>
    <label>{$form.contribution_page_id.label}</label> <br />
    {$form.contribution_page_id.html|crmAddClass:twenty}
  </td>
</tr>
<tr>
  <td>
    {$form.contribution_source.label} <br />
    {$form.contribution_source.html|crmAddClass:twenty}
  </td>
  <td>
    {if $form.contribution_product_id}
      {$form.contribution_product_id.label} <br />
      {$form.contribution_product_id.html|crmAddClass:twenty}
    {/if}
  </td>
</tr>
<tr>
  <td>
    {$form.contribution_pcp_made_through_id.label} <br />
    {$form.contribution_pcp_made_through_id.html}
    {include file="CRM/Contribute/Form/PCP.js.tpl"}
  </td>
  <td>&nbsp;</td>
</tr>
<tr>
  <td>
    {$form.contribution_pcp_display_in_roll.label}
    {$form.contribution_pcp_display_in_roll.html}
  </td>
</tr>
<tr>
  {include file="CRM/Core/DatePickerRangeWrapper.tpl" fieldName="contribution_cancel_date" to='' from='' colspan="2" class='' hideRelativeLabel=0}
</tr>
<tr>
  <td>
    {$form.cancel_reason.label}<br />
    {$form.cancel_reason.html}
  </td>
</tr>
<tr>
  <td><label>{$form.contribution_id.label}</label> {$form.contribution_id.html}</td>
</tr>

{* campaign in contribution search *}
{include file="CRM/Campaign/Form/addCampaignToSearch.tpl"
campaignTrClass='' campaignTdClass=''}

{* contribution recurring search *}
<tr>
  <td colspan="2">
    {include file="CRM/Contribute/Form/Search/ContributionRecur.tpl"}
  </td>
</tr>

{if $contributionGroupTree}
<tr>
  <td colspan="2">
  {include file="CRM/Custom/Form/Search.tpl" groupTree=$contributionGroupTree showHideLinks=false}</td>
</tr>
{/if}

{literal}
<script type="text/javascript">
  cj('#contribution_payment_instrument_id').change(function() {
    if (cj(this).val() == '4') {
      cj('#contribution_check_number_wrapper').show();
    }
    else {
      cj('#contribution_check_number_wrapper').hide();
      cj('#contribution_check_number').val('');
    }
  }).change();
  cj('#contribution_or_softcredits').change(function() {
    if (cj(this).val() == 'only_contribs') {
      cj('#contribution_soft_credit_type_wrapper').hide();
      cj('#contribution_soft_credit_type_id').val('');
    }
    else {
      cj('#contribution_soft_credit_type_wrapper').show();
    }
  }).change();
</script>
{/literal}
