{if !$membershipMode}
  {if $accessContribution && ($action != 2 or (!$rows.0.contribution_id AND !$softCredit) or $onlinePendingContributionId)}
    <table>
      <tr class="crm-{$formClass}-form-block-contribution-contact">
        <td class="label">{$form.is_different_contribution_contact.label}</td>
        <td>{$form.is_different_contribution_contact.html}&nbsp;&nbsp;{help id="id-contribution_contact"}</td>
      </tr>
      <tr id="record-different-contact">
        <td>&nbsp;</td>
        <td>
          <table class="compressed">
            <tr class="crm-{$formClass}-form-block-soft-credit-type">
              <td class="label">{$form.soft_credit_type_id.label}</td>
              <td>{$form.soft_credit_type_id.html}</td>
            </tr>
            <tr class="crm-{$formClass}-form-block-soft-credit-contact-id">
              <td class="label">{$form.soft_credit_contact_id.label}</td>
              <td>{$form.soft_credit_contact_id.html}</td>
            </tr>
          </table>
        </td>
      </tr>

        <tr class="crm-{$formClass}-form-block-total_amount">
          <td class="label">{$form.total_amount.label}</td>
          <td>{$form.total_amount.html}<br />
            <span class="description">{ts}Membership payment amount. A contribution record will be created for this amount.{/ts}</span><div class="totaltaxAmount"></div></td>
        </tr>
        <tr class="crm-{$formClass}-form-block-receive_date">
          <td class="label">{$form.receive_date.label}</td>
          <td>{$form.receive_date.html}</td>
        </tr>
        <tr class="crm-{$formClass}-form-block-financial_type_id">
          <td class="label">{$form.financial_type_id.label}</td>
          <td>{$form.financial_type_id.html}<br/>
            <span class="description">{ts}Select the appropriate financial type for this payment.{/ts}</span>
          </td>
        </tr>
        <tr class="crm-{$formClass}-form-block-payment_instrument_id">
          <td class="label">{$form.payment_instrument_id.label}<span class='marker'>*</span></td>
          <td>{$form.payment_instrument_id.html} {help id="payment_instrument_id" file="CRM/Contribute/Page/Tab.hlp"}</td>
        </tr>

        {if $action neq 2 }
          <tr class="crm-{$formClass}-form-block-trxn_id">
            <td class="label">{$form.trxn_id.label}</td>
            <td>{$form.trxn_id.html}</td>
          </tr>
        {/if}
        <tr class="crm-{$formClass}-form-block-contribution_status_id">
          <td class="label">{$form.contribution_status_id.label}</td>
          <td>{$form.contribution_status_id.html}</td>
        </tr>

        <tr class="crm-membership-form-block-billing">
          <td colspan="2">
            {include file='CRM/Core/BillingBlockWrapper.tpl'}
          </td>
        </tr>
      </table>
    </fieldset></td></tr>
  {/if}

{else}
  {if !empty($form.auto_renew)}
    <tr id="autoRenew" class="crm-{$formClass}-form-block-auto_renew">
      <td class="label"> {$form.auto_renew.label} {help id="id-auto_renew" file="CRM/Member/Form/Membership.hlp" action=$action} </td>
      <td> {$form.auto_renew.html} </td>
    </tr>
  {/if}
  <tr class="crm-member-{$formClass}-form-block-financial_type_id">
    <td class="label">{$form.financial_type_id.label}</td>
    <td>{$form.financial_type_id.html}<br/>
      <span class="description">{ts}Select the appropriate financial type for this payment.{/ts}</span></td>
  </tr>
  <tr class="crm-{$formClass}-form-block-total_amount">
    <td class="label">{$form.total_amount.label}</td>
    <td>{$form.total_amount.html}<br />
      <span class="description">{ts}Membership payment amount.{/ts}</span><div class="totaltaxAmount"></div>
    </td>
  </tr>
  <tr class="crm-membership-form-block-contribution-contact">
    <td class="label">{$form.is_different_contribution_contact.label}</td>
    <td>{$form.is_different_contribution_contact.html}&nbsp;&nbsp;{help id="id-contribution_contact"}</td>
  </tr>
  <tr id="record-different-contact">
    <td>&nbsp;</td>
    <td>
      <table class="compressed">
        <tr class="crm-membership-form-block-soft-credit-type">
          {*CRM-15366*}
          <td class="label">{$form.soft_credit_type_id.label}</td>
          <td>{$form.soft_credit_type_id.html}</td>
        </tr>
        <tr class="crm-membership-form-block-soft-credit-contact-id">
          <td class="label">{$form.soft_credit_contact_id.label}</td>
          <td>{$form.soft_credit_contact_id.html}</td>
        </tr>
      </table>
    </td>
  </tr>

  <div class="spacer"></div>
{/if}
{if $membershipMode}
  <tr>
    <td class="label">{$form.payment_processor_id.label}</td>
    <td>{$form.payment_processor_id.html}</td>
  </tr>
  <tr class="crm-membership-form-block-billing">
    <td colspan="2">
      {include file='CRM/Core/BillingBlockWrapper.tpl'}
    </td>
  </tr>
{/if}
