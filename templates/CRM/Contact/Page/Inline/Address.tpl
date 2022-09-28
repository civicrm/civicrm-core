{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* template for a single address block*}
<div id="address-block-{$locationIndex}" class="address {if $add}crm-address_type_{$add.location_type}{else}add-new{/if}{if $permission EQ 'edit'} crm-inline-edit" data-dependent-fields='["#crm-contactinfo-content", ".crm-inline-edit.address:not(.add-new)"]' data-edit-params='{ldelim}"cid": "{$contactId}", "class_name": "CRM_Contact_Form_Inline_Address", "locno": "{$locationIndex}", "aid": "{if $add}{$add.id}{else}0{/if}"{rdelim}' data-location-type-id="{if $add}{$add.location_type_id}{else}0{/if}{/if}">
  <div class="crm-clear crm-inline-block-content" {if $permission EQ 'edit'}title="{if $add}{ts}Edit address{/ts}{else}{ts}Add address{/ts}{/if}"{/if}>
    {if $permission EQ 'edit'}
      <div class="crm-edit-help">
        <span class="crm-i fa-pencil" aria-hidden="true"></span> {if $add}{ts}Edit address{/ts}{else}{ts}Add address{/ts}{/if}
      </div>
    {/if}
    {if !$add}
      <div class="crm-summary-row">
        <div class="crm-label">{ts}Address{/ts}</div>
        <div class="crm-content"></div>
      </div>
    {else}
      <div class="crm-summary-row {if $add.is_primary eq 1} primary{/if}">
        <div class="crm-label">
          {ts 1=$add.location_type}%1 Address{/ts}
          {privacyFlag field=do_not_mail condition=$privacy.do_not_mail}
          {if $config->mapProvider AND
              !empty($add.geo_code_1) AND
              is_numeric($add.geo_code_1) AND
              !empty($add.geo_code_2) AND
              is_numeric($add.geo_code_2)
          }
          <br /><a href="{crmURL p='civicrm/contact/map' q="reset=1&cid=`$contactId`&lid=`$add.location_type_id`"}" title="{ts 1=`$add.location_type`}Map %1 Address{/ts}"><i class="crm-i fa-map-marker" aria-hidden="true"></i> {ts}Map{/ts}</a>
          {/if}
        </div>
        <div class="crm-content">
          {if !empty($sharedAddresses.$locationIndex.shared_address_display.name)}
            <strong>{ts 1=$sharedAddresses.$locationIndex.shared_address_display.name}Address belongs to %1{/ts}</strong><br />
          {/if}
          {$add.display|nl2br}
        </div>
      </div>

    <!-- add custom data -->
    {foreach from=$add.custom item=customGroup key=cgId} {* start of outer foreach *}
      {assign var="isAddressCustomPresent" value=1}
      {foreach from=$customGroup item=customValue key=cvId}
        <div id="address_custom_{$cgId}_{$locationIndex}"
        class="crm-collapsible crm-address-custom-{$cgId}-{$locationIndex}-accordion
        {if $customValue.collapse_display}collapsed{/if}">
        <div class="collapsible-title">
          {$customValue.title}
        </div>
        <div class="crm-summary-block">
          {foreach from=$customValue.fields item=customField key=cfId}
          <div class="crm-summary-row">
            <div class="crm-label">
              {$customField.field_title}
            </div>
            <div class="crm-content">
              {$customField.field_value}
            </div>
          </div>
          {/foreach}
          </div>
        </div>
      {/foreach}
    {/foreach} {* end of outer custom group foreach *}
    <!-- end custom data -->
    {/if}
  </div>
</div>
