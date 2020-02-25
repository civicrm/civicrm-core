{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
<div id="crm-demographic-content" {if $permission EQ 'edit'} class="crm-inline-edit" data-dependent-fields='["#crm-contactname-content"]' data-edit-params='{ldelim}"cid": "{$contactId}", "class_name": "CRM_Contact_Form_Inline_Demographics"{rdelim}'{/if}>
  <div class="crm-clear crm-inline-block-content" {if $permission EQ 'edit'}title="{ts}Edit demographics{/ts}"{/if}>
    {if $permission EQ 'edit'}
    <div class="crm-edit-help">
      <span class="crm-i fa-pencil"></span> {ts}Edit demographics{/ts}
    </div>
    {/if}
    <div class="crm-summary-row">
      <div class="crm-label">{ts}Gender{/ts}</div>
      <div class="crm-content crm-contact-gender_display">{$gender_display}</div>
    </div>

    <div class="crm-summary-row">
      <div class="crm-label">{ts}Date of Birth{/ts}</div>
      <div class="crm-content crm-contact-birth_date_display">
         {assign var="date_format" value = $fields.birth_date.smarty_view_format}
         {$birth_date|crmDate:$date_format}
          &nbsp;
      </div>
    </div>
      {if $is_deceased eq 1}
        {if $deceased_date}
          <div class="crm-summary-row">
            <div class="crm-label">{ts}Date Deceased{/ts}</div>
            <div class="crm-content crm-contact-deceased_date_display">
              {$deceased_date}
            </div>
          </div>
        {else}
          <div class="crm-summary-row">
            <div class="crm-label"></div>
            <div class="crm-content crm-contact-deceased_message"><span class="font-red upper">{ts}Contact is Deceased{/ts}</span></div>
          </div>
        {/if}
      {else}
        <div class="crm-summary-row">
          <div class="crm-label">{ts}Age{/ts}</div>
          <div class="crm-content crm-contact-age_display">{if $age.y}{ts count=$age.y plural='%count years'}%count year{/ts}{elseif $age.m}{ts count=$age.m plural='%count months'}%count month{/ts}{/if}</div>
        </div>
      {/if}

      {* The block below is the only modification added by civicrm_engage *}
      {if isset($demographics_viewCustomData)}
        {foreach from=$demographics_viewCustomData item=customValues key=customGroupId}
            {foreach from=$customValues item=cd_edit key=cvID}
               {include file="CRM/Contact/Page/View/CustomDataFieldView.tpl"}
            {/foreach}
        {/foreach}
      {/if}
      {* We have to hide the edit link or we will get two of them for this block *}
      <script>
      cj("#custom-set-content-{$demographics_custom_group_id} .crm-edit-help").hide();
      </script>
      {* End of civicrm_engage modification *}

    </div>
  </div>
