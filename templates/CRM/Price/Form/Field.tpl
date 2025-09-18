{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{*Javascript function controls showing and hiding of form elements based on html type.*}
{literal}
<script type="text/Javascript">
  function option_html_type(form) {
    var html_type_name = cj('#html_type').val();

    if (html_type_name == "Text") {
      cj("#price-block").show();
      cj("#showoption").hide();

    }
    else {
      cj("#price-block").hide();
      cj("#showoption").show();
    }

    if (html_type_name == 'Radio' || html_type_name == 'CheckBox') {
      cj("#optionsPerLine").show( );
    }
    else {
      cj("#optionsPerLine").hide( );
      cj("#optionsPerLineDef").hide( );
    }

    var radioOption, checkBoxOption;

    for (var i=1; i<=15; i++) {
      radioOption = '#radio'+i;
      checkBoxOption = '#checkbox'+i;
      if (html_type_name == 'Radio' || html_type_name == 'CheckBox' || html_type_name == 'Select') {
        if (html_type_name == "CheckBox") {
          cj(checkBoxOption).show();
          cj(radioOption).hide();
        }
        else {
          cj(radioOption).show();
          cj(checkBoxOption).hide();
        }
      }
    }

  }

  var adminVisibilityID = 0;
  cj('#visibility_id').on('change', function () {
    if (adminVisibilityID == 0) {
      CRM.api3('OptionValue', 'getvalue', {
        'sequential': 1,
        'return': 'value',
        'option_group_id': 'visibility',
        'name': 'admin'
      }).done(function(result) {
        adminVisibilityID = result.result;
        if (cj('#visibility_id').val() == adminVisibilityID) {
          updateVisibilitySelects(adminVisibilityID);
        }
      });
    } else {
      if (cj('#visibility_id').val() == adminVisibilityID) {
        updateVisibilitySelects(adminVisibilityID);
      }
    }
  });

  function updateVisibilitySelects(value) {
    for (var i=1; i<=15; i++) {
      cj('#option_visibility_id_' + i).val(value);
    }
  }
</script>
{/literal}
<div class="crm-block crm-form-block crm-price-field-form-block">
  <table class="form-layout">
    <tr class="crm-price-field-form-block-label">
      <td class="label">{$form.label.label|smarty:nodefaults}</td>
      <td>{if $action == 2}{include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_price_field' field='label' id=$fid}{/if}{$form.label.html}
      </td>
    </tr>
    <tr class="crm-price-field-form-block-html_type">
      <td class="label">{$form.html_type.label|smarty:nodefaults}</td>
      <td>{$form.html_type.html}
      </td>
    </tr>
  </table>
  <div class="spacer"></div>
  <div id="price-block" {if $action eq 2 && $form.html_type.value.0 eq 'Text'} class="show-block" {else} class="hiddenElement" {/if}>
    <table class="form-layout">
      <tr class="crm-price-field-form-block-price">
        <td class="label">{$form.price.label|smarty:nodefaults} <span class="crm-marker" title="{ts escape='htmlattribute'}This field is required.{/ts}">*</span> {help id="price"}</td>
        <td>{$form.price.html}</td>
      </tr>
      <tr class="crm-price-field-form-block-non-deductible-amount">
        <td class="label">{$form.non_deductible_amount.label|smarty:nodefaults}</td>
        <td>{$form.non_deductible_amount.html}</td>
      </tr>
    {if $useForEvent}
      <tr class="crm-price-field-form-block-count">
        <td class="label">{$form.count.label|smarty:nodefaults}</td>
        <td>{$form.count.html}<br />
          <span class="description">{ts}Enter a value here if you want to increment the number of registered participants per unit against the maximum number of participants allowed for this event.{/ts}</span>
          {help id="id-participant-count"}
        </td>
      </tr>
      <tr class="crm-price-field-form-block-max_value">
        <td class="label">{$form.max_value.label|smarty:nodefaults}</td>
        <td>{$form.max_value.html}
        </td>
      </tr>
    {/if}
      <tr class="crm-price-field-form-block-financial_type">
        <td class="label">{$form.financial_type_id.label|smarty:nodefaults}<span class="crm-marker" title="{ts escape='htmlattribute'}This field is required.{/ts}">*</span></td></td>
        <td>
        {if !$financialType}
          {capture assign=ftUrl}{crmURL p='civicrm/admin/financial/financialType' q="reset=1"}{/capture}
          {ts 1=$ftUrl}There is no Financial Type configured of Account Relation Revenue. <a href='%1'>Click here</a> if you want to configure financial type for your site.{/ts}
          {else}
          {$form.financial_type_id.html}
        {/if}
        </td>
      </tr>
    </table>
  </div>

{if $action eq 1}
{* Conditionally show table for setting up selection options - for field types = radio, checkbox or select *}
  <div id='showoption' class="hiddenElement">{include file="CRM/Price/Form/OptionFields.tpl"}</div>
{/if}
  <table class="form-layout">
    <tr id="optionsPerLine" class="crm-price-field-form-block-options_per_line">
      <td class="label">{$form.options_per_line.label}</td>
      <td>{$form.options_per_line.html|crmAddClass:two}</td>
    </tr>
    <tr class="crm-price-field-form-block-is_display_amounts">
      <td class="label">{$form.is_display_amounts.label}</td>
      <td>{$form.is_display_amounts.html}
      {if $action neq 4}
        <div class="description">{ts}Display amount next to each option?{/ts}</div>
      {/if}
      </td>
    </tr>
    <tr class="crm-price-field-form-block-weight">
      <td class="label">{$form.weight.label}</td>
      <td>{$form.weight.html|crmAddClass:two}
      {if $action neq 4}
        <div class="description">{ts}Weight controls the order in which fields are displayed in a group. Enter a positive or negative integer - lower numbers are displayed ahead of higher numbers.{/ts}</div>
      {/if}
      </td>
    </tr>

    <tr class="crm-price-field-form-block-help_pre">
      <td class="label">{$form.help_pre.label|smarty:nodefaults}</td>
      <td>{if $action == 2}{include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_price_field' field='help_pre' id=$fid}{/if}{$form.help_pre.html|crmAddClass:huge}&nbsp;
      {if $action neq 4}
        <div class="description">{ts}Explanatory text displayed to users at the beginning of this field.{/ts}</div>
      {/if}
      </td>
    </tr>

    <tr class="crm-price-field-form-block-help_post">
      <td class="label">{$form.help_post.label|smarty:nodefaults}</td>
      <td>{if $action == 2}{include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_price_field' field='help_post' id=$fid}{/if}{$form.help_post.html|crmAddClass:huge}&nbsp;
      {if $action neq 4}
        <div class="description">{ts}Explanatory text displayed to users below this field.{/ts}</div>
      {/if}
      </td>
    </tr>

    <tr class="crm-price-field-form-block-active_on">
      <td class="label">{$form.active_on.label}</td>
      <td>{$form.active_on.html}
      {if $action neq 4}
        <br /><span class="description">{ts}Date this field becomes effective (optional).  Used for price set fields that are made available starting on a specific date.{/ts}</span>
      {/if}
      </td>
    </tr>

    <tr class="crm-price-field-form-block-expire_on">
      <td class="label">{$form.expire_on.label}</td>
      <td>{$form.expire_on.html}
      {if $action neq 4}
        <br /><span class="description">{ts}Date this field expires (optional).  Used for price set fields that are no longer available after a specific date (e.g. early-bird pricing).{/ts}</span>
      {/if}
      </td>
    </tr>

    <tr class="crm-price-field-form-block-is_required">
      <td class="label">{$form.is_required.label}</td>
      <td>&nbsp;{$form.is_required.html}</td>
    </tr>
    <tr class="crm-price-field-form-block-visibility_id">
      <td class="label">{$form.visibility_id.label} {help id="visibility_id"}</td>
      <td>&nbsp;{$form.visibility_id.html}</td>
    </tr>
    <tr class="crm-price-field-form-block-is_active">
      <td class="label">{$form.is_active.label}</td>
      <td>{$form.is_active.html}</td>
    </tr>
  </table>
  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>

{literal}
<script type="text/javascript">
  option_html_type(this.form);
  function calculateRowValues( row ) {
    var mtype = cj("#membership_type_id_"+row).val();
    var postUrl = "{/literal}{crmURL p='civicrm/ajax/memType' h=0}{literal}";

    cj.post( postUrl, {mtype: mtype}, function(data) {
      cj("#option_amount_"+ row).val(data.total_amount);
      cj("#option_label_"+ row).val(data.name);
      cj("#option_financial_type_id_"+ row).val(data.financial_type_id);
      if (data.name) {
        cj("#membership_num_terms_"+ row).val('1');
      }
      else {
        cj("#membership_num_terms_"+ row).val('');
      }
    }, 'json');
  }
</script>
{/literal}

{* Give link to view/edit choice options if in edit mode and html_type is one of the multiple choice types *}
{if $action eq 2 AND array_key_exists('data_type', $form) && ($form.data_type.value.1.0 eq 'CheckBox' OR $form.data_type.value.1.0 eq 'Radio' OR $form.data_type.value.1.0 eq 'Select')}
<div class="action-link">
  <a href="{crmURL p="civicrm/admin/event/field/option" q="reset=1&action=browse&fid=`$fid`"}" class="button"><span>{ts}Multiple Choice Options{/ts}</span></a>
</div>
{/if}

