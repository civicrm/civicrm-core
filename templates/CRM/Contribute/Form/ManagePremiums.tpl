{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* this template is used for adding/editing/deleting premium  *}
<div class="crm-block crm-form-block crm-contribution-manage_premiums-form-block">
   {if $action eq 8}
      <div class="messages status no-popup">
          {icon icon="fa-info-circle"}{/icon}
          {ts}Are you sure you want to delete this premium?{/ts} {ts}This action cannot be undone.{/ts} {ts}This will also remove the premium from any contribution pages that currently include it.{/ts}
      </div>
  {elseif $action eq 1024}
     {include file="CRM/Contribute/Form/Contribution/PremiumBlock.tpl" context="previewPremium"}
  {else}
  {crmRegion name="contribute-form-managepremiums-standard-fields"}
  <table class="form-layout-compressed">
     <tr class="crm-contribution-form-block-name">
  <td class="label">{$form.name.label} {if $action == 2}{include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_product' field='name' id=$productId}{/if}
  </td>
  <td class="html-adjust">{$form.name.html}<br />
     <span class="description">{ts}Name of the premium (product, service, subscription, etc.) as it will be displayed to contributors.{/ts}</span>
  </td>
     </tr>
     <tr>
        <td class="label">{$form.description.label} {if $action == 2}{include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_product' field='description' id=$productId}{/if}
  </td>
  <td class="html-adjust">{$form.description.html}
  </td>
     <tr class="crm-contribution-form-block-sku">
        <td class="label">{$form.sku.label}
  </td>
  <td class="html-adjust">{$form.sku.html}<br />
     <span class="description">{ts}Optional product SKU or code. If used, this value will be included in contributor receipts.{/ts}</span>
  </td>
     </tr>
     <tr class="crm-contribution-form-block-imageOption">
      <td class="label">{$form.imageOption.label}</td>
      <td>
        <div id="currentImage">
          {if $imageURL}
            <img src="{$imageURL}" alt="{ts escape="htmlattribute"}Current Image{/ts}"/>
          {/if}
        </div>
        <div class="hiddenElement" id="defaultImage">
          <img src="{$defaultImageURL}" alt="{ts escape="htmlattribute"}Default product image with the words "No image available".{/ts}">
        </div>
        <div class="description">
          <p>{ts 1="400x400"}You can give this premium a picture that will be displayed on the contribution page. The image displayed will be resized to a maximum of %1 pixels. Images must be in GIF, JPEG, or PNG format.{/ts}</p>
        </div>
  <table class="form-layout-compressed">
    <tr class="crm-contribution-form-block-imageOption">
      <td colspan="2">{$form.imageOption.image.html}</td>
    </tr>
    <tr id="uploadFileRow" class="hiddenElement">
      <td class="label"><label for="uploadFile">{ts}File{/ts}</label></td>
      <td>{$form.uploadFile.html}</td>
    </tr>
    <tr class="crm-contribution-form-block-imageOption-thumbnail">
      <td colspan="2">{$form.imageOption.thumbnail.html}</td>
    </tr>
    <tr id="imageURL"{if $action neq 2} class="hiddenElement"{/if}>
      <td class="label">{$form.imageUrl.label}</td><td>{$form.imageUrl.html|crmAddClass:huge}</td>
    </tr>
    <tr id="thumbnailURL"{if $action neq 2} class="hiddenElement"{/if}>
      <td class="label">{$form.thumbnailUrl.label}</td><td>{$form.thumbnailUrl.html|crmAddClass:huge}</td>
    </tr>
    <tr>
      <td colspan="2">{$form.imageOption.default_image.html}</td>
    </tr>
    <tr>
      <td colspan="2">{$form.imageOption.noImage.html}</td>
    </tr>
  </table>
        </td>
    </tr>
    <tr class="crm-contribution-form-block-min_contribution">
       <td class="label">{$form.min_contribution.label}</td>
       <td class="html-adjust">{$form.min_contribution.html}<br />
          <span class="description">{ts}The minimum contribution amount required to be eligible to select this premium. If you want to offer it to all contributors regardless of contribution amount, enter '0'. If display of minimum contribution amounts is enabled then this text is displayed:{/ts} <em>{ts}(Contribute at least X to be eligible for this gift.){/ts}</em></span>
       </td>
    </tr>
    <tr class="crm-contribution-form-block-price">
       <td class="label">{$form.price.label}</td>
       <td class="html-adjust">{$form.price.html}<br />
     <span class="description">{ts}The market value of this premium (e.g. retail price). For tax-deductible contributions, this amount will be used to set the non-deductible amount in the contribution record and receipt.{/ts}</span>
       </td>
    </tr>
      <tr class="crm-contribution-form-block-cost">
         <td class="label">{$form.cost.label}</td>
         <td class="html-adjust">{$form.cost.html}<br />
          <span class="description">{ts}You may optionally record the actual cost of this premium to your organization. This may be useful when evaluating net return for this incentive.{/ts}</span>
         </td>
      </tr>
     <tr class="crm-contribution-form-block-financial_type">
       <td class="label">{$form.financial_type_id.label}</td>
       <td class="html-adjust">
       {if empty($financialType)}
         {capture assign=ftUrl}{crmURL p='civicrm/admin/financial/financialType' q="reset=1"}{/capture}
         {ts 1=$ftUrl}There are no financial types configured with linked 'Cost of Sales Premiums' and 'Premiums Inventory Account' accounts. If you want to generate accounting transactions which track the cost of premiums used <a href='%1'>click here</a> to configure financial types and accounts.{/ts}
       {else}
         {$form.financial_type_id.html}<br />
   <span class="description">{ts}Select a financial type that is linked to both a 'Cost of Sales Premiums Account' and a 'Premiums Inventory Account' if you want to generate accounting transactions to track the cost of premiums used.{/ts}</span>
       {/if}
       </td>
    </tr>
    <tr class="crm-contribution-form-block-options">
       <td class="label">{$form.options.label} {if $action == 2}{include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_product' field='options' id=$productId}{/if}</td>
      <td class="html-adjust">{$form.options.html}<br />
          <span class="description">{ts}Enter a comma-delimited list of color, size, etc. options for the product if applicable. Contributors will be presented a drop-down menu of these options when they select this product.{/ts} {ts}You can also use the format key=label, where the key could be the SKU of the option, for example.{/ts}</span>
       </td>
    </tr>
    <tr class="crm-contribution-form-block-is_active">
       <td class="label">{$form.is_active.label}</td>
       <td class="html-adjust">{$form.is_active.html}</td>
    </tr>
  </table>
  {/crmRegion}
  {crmRegion name="contribute-form-managepremiums-other-fields"}
  <details id="time-delimited" class="crm-accordion-light" {if !empty($showSubscriptions)}open{/if}>
    <summary>{ts}Subscription or Service Settings{/ts}</summary>
    <div class="crm-accordion-body">
      <table class="form-layout-compressed">
        <tr class="crm-contribution-form-block-period_type">
           <td class="label">{$form.period_type.label}</td>
           <td class="html-adjust">{$form.period_type.html}<br />
              <span class="description">{ts}Select 'Rolling' if the subscription or service starts on the current day. Select 'Fixed' if the start date is a fixed month and day within the current year (set this value in the next field).{/ts}</span>
           </td>
        </tr>
        <tr class="crm-contribution-form-block-fixed_period_start_day">
           <td class="label">{$form.fixed_period_start_day.label}</td>
           <td class="html-adjust">{$form.fixed_period_start_day.html}<br />
              <span class="description">{ts}Month and day (MMDD) on which a fixed period subscription or service will start. EXAMPLE: A fixed period subscription with Start Day set to 0101 means that the subscription period would be 1/1/06 - 12/31/06 for anyone signing up during 2006.{/ts}</span>
           </td>
        </tr>
        <tr class="crm-contribution-form-block-duration_interval">
           <td class="label">{$form.duration_interval.label}</td>
           <td class="html-adjust">{$form.duration_interval.html} &nbsp; {$form.duration_unit.html}<br />
              <span class="description">{ts}Duration of subscription or service (e.g. 12-month subscription).{/ts}</span>
           </td>
        </tr>
        <tr class="crm-contribution-form-block-frequency_interval">
           <td class="label">{$form.frequency_interval.label}</td>
           <td class="html-adjust">{$form.frequency_interval.html} &nbsp; {$form.frequency_unit.html}<br />
              <span class="description">{ts}Frequency of subscription or service (e.g. journal delivered every two months).{/ts}</span>
        </td>
        </tr>
      </table>
    </div>
  </details>
  {/crmRegion}
 {/if}
  {include file="CRM/common/customDataBlock.tpl" customDataType='Product' entityID=$productId}
  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>
{if $action eq 1 or $action eq 2}

<script type="text/javascript">
{literal}
function add_upload_file_block(option) {
  if (option == 'thumbnail') {
    document.getElementById("imageURL").classList.remove('hiddenElement');
    document.getElementById("thumbnailURL").classList.remove('hiddenElement');
  }
  else {
    document.getElementById("imageURL").classList.add('hiddenElement');
    document.getElementById("thumbnailURL").classList.add('hiddenElement');
  }

  if (option == 'image') {
    document.getElementById("uploadFileRow").classList.remove('hiddenElement');
  }
  else {
    document.getElementById("uploadFileRow").classList.add('hiddenElement');
  }

  if (option == 'default_image') {
    document.getElementById("defaultImage").classList.remove('hiddenElement');
    document.getElementById("currentImage").classList.add('hiddenElement');
  }
  else {
    document.getElementById("defaultImage").classList.add('hiddenElement');
  }
}
{/literal}
</script>

{/if}
