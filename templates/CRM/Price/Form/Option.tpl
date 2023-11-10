{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
<div class="crm-form-block">
  {if $action eq 8}
    <div class="messages status no-popup">
      {icon icon="fa-info-circle"}{/icon}
      {ts}WARNING: Deleting this option will result in the loss of all data.{/ts} {ts}This action cannot be undone.{/ts} {ts}Do you want to continue?{/ts}
    </div>
  {else}
    <table class="form-layout">
      {if $showMember}
        <tr class="crm-price-option-form-block-membership_type_id">
          <td class="label">{$form.membership_type_id.label}</td>
          <td>{$form.membership_type_id.html}
            <br /> <span class="description">{ts}If a membership type is selected, a membership will be created or renewed when users select this option. Leave this blank if you are using this for non-membership options (e.g. magazine subscription).{/ts} {help id="id-member-price-options" file="CRM/Price/Page/Field.hlp"}</span></td>
        </tr>
        <tr class="crm-price-option-form-block-membership_num_terms">
          <td class="label">{$form.membership_num_terms.label}</td>
          <td>{$form.membership_num_terms.html}
            <br /> <span class="description">{ts}You can set this to a number other than one to allow multiple membership terms.{/ts}</span></td>
        </tr>
      {/if}
      <tr class="crm-price-option-form-block-label">
        <td class="label">{$form.label.label|smarty:nodefaults|purify}</td>
        <td>{if $action == 2}{include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_price_field_value' field='label' id=$optionId}{/if}{$form.label.html}</td>
      </tr>
      <tr class="crm-price-option-form-block-amount">
        <td class="label">{$form.amount.label|smarty:nodefaults|purify}</td>
        <td>{$form.amount.html}</td>
      </tr>
      <tr class="crm-price-option-form-block-non-deductible-amount">
        <td class="label">{$form.non_deductible_amount.label}</td>
        <td>{$form.non_deductible_amount.html}</td>
      </tr>
      <tr class="crm-price-option-form-block-description">
        <td class="label">{$form.description.label}<br />{help id="description" file="CRM/Price/Page/Field.hlp"}</td>
        <td>{if $action == 2}{include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_price_field_value' field='description' id=$optionId}{/if}{$form.description.html}</td>
      </tr>
      <tr class="crm-price-option-form-block-help-pre">
        <td class="label">{$form.help_pre.label|smarty:nodefaults|purify}</td>
        <td>{if $action == 2}{include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_price_field_value' field='help_pre' id=$optionId}{/if}{$form.help_pre.html}</td>
      </tr>
      <tr class="crm-price-option-form-block-help-post">
        <td class="label">{$form.help_post.label|smarty:nodefaults|purify}</td>
        <td>{if $action == 2}{include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_price_field_value' field='help_post' id=$optionId}{/if}{$form.help_post.html}</td>
      </tr>
      <tr class="crm-price-option-form-block-financial-type">
        <td class="label">{$form.financial_type_id.label}</td>
        <td>
          {if !$financialType}
            {capture assign=ftUrl}{crmURL p='civicrm/admin/financial/financialType' q="reset=1"}{/capture}
            {ts 1=$ftUrl}There are no financial types configured with a linked 'Revenue Account of' account. <a href='%1'>Click here</a> if you want to configure financial types for your site.{/ts}
          {else}
            {$form.financial_type_id.html}
          {/if}
        </td>
      </tr>
      {* fix for CRM-10241 *}
      {if !empty($form.count.html)}
        <tr class="crm-price-option-form-block-count">
          <td class="label">{$form.count.label}</td>
          <td>{$form.count.html} {help id="id-participant-count" file="CRM/Price/Page/Field.hlp"}</td>
        </tr>
        {* 2 line fix for CRM-10241 *}
      {/if}
      {if !empty($form.max_value.html)}
        <tr class="crm-price-option-form-block-max_value">
          <td class="label">{$form.max_value.label}</td>
          <td>{$form.max_value.html} {help id="id-participant-max" file="CRM/Price/Page/Field.hlp"}</td>
        </tr>
        {* fix for CRM-10241 *}
      {/if}
      <tr class="crm-price-option-form-block-weight">
        <td class="label">{$form.weight.label}</td>
        <td>{$form.weight.html}</td>
      </tr>
      <tr class="crm-price-option-form-block-is_active">
        <td class="label">{$form.is_active.label}</td>
        <td>{$form.is_active.html}</td>
        {if !$hideDefaultOption}
      <tr class="crm-price-option-form-block-is_default">
        <td class="label">{$form.is_default.label}</td>
        <td>{$form.is_default.html}</td>
      </tr>
      {/if}
      <tr class="crm-price-field-form-block-visibility_id">
        <td class="label">{$form.visibility_id.label}</td>
        <td>&nbsp;{$form.visibility_id.html} {help id="id-visibility-options" file="CRM/Price/Page/Field.hlp"}</td>
      </tr>
    </table>

  {literal}
    <script type="text/javascript">

      function calculateRowValues( ) {
        var mtype = cj("#membership_type_id").val();
        var postUrl = "{/literal}{crmURL p='civicrm/ajax/memType' h=0}{literal}";
        cj.post( postUrl, {mtype: mtype}, function( data ) {
          cj("#amount").val( data.total_amount );
          cj("#label").val( data.name );

        }, 'json');
      }
      {/literal}
    </script>
  {/if}


  <div class="crm-submit-buttons">
    {include file="CRM/common/formButtons.tpl" location=''}
  </div>

</div>
