{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}

{if !$gName}
  {include file="CRM/Admin/Page/OptionGroup.tpl"}

{elseif $action eq 1 or $action eq 2 or $action eq 8}
   {include file="CRM/Admin/Form/Options.tpl"}
{else}

{if $gName eq "acl_role"}
  {include file="CRM/ACL/Header.tpl" step=1}
{else}
<div class="help">
  {if $gName eq "gender"}
    {ts}CiviCRM is pre-configured with standard options for individual gender (Male, Female, Other). Modify these options as needed for your installation.{/ts}
  {elseif $gName eq "individual_prefix"}
      {ts}CiviCRM is pre-configured with standard options for individual contact prefixes (Ms., Mr., Dr. etc.). Customize these options and add new ones as needed for your installation.{/ts}
  {elseif $gName eq "mobile_provider"}
     {ts}When recording mobile phone numbers for contacts, it may be useful to include the Mobile Phone Service Provider (e.g. Cingular, Sprint, etc.). CiviCRM is installed with the most commonly encountered service providers. Administrators may define as many additional providers as needed.{/ts}
  {elseif $gName eq "instant_messenger_service"}
     {ts}When recording Instant Messenger (IM) 'screen names' for contacts, it is useful to include the IM Service Provider (e.g. AOL, Yahoo, etc.). CiviCRM is installed with the most commonly encountered service providers. Administrators may define as many additional providers as needed.{/ts}
  {elseif $gName eq "individual_suffix"}
     {ts}CiviCRM is pre-configured with standard options for individual contact name suffixes (Jr., Sr., II etc.). Customize these options and add new ones as needed for your installation.{/ts}
  {elseif $gName eq "activity_type"}
     {ts}Activities are 'interactions with contacts' which you want to record and track. This list is sorted by component and then by weight within the component.{/ts} {help id='id-activity-types'}
  {elseif $gName eq "payment_instrument"}
     {ts}You may choose to record the payment method used for each contribution and fee. Reserved payment methods are required - you may modify their labels but they can not be deleted (e.g. Check, Credit Card, Debit Card). If your site requires additional payment methods, you can add them here. You can associate each payment method with a Financial Account which specifies where the payment is going (e.g. a bank account for checks and cash).{/ts}
  {elseif $gName eq "accept_creditcard"}
    {ts}The following credit card options will be offered to contributors using Online Contribution pages. You will need to verify which cards are accepted by your chosen Payment Processor and update these entries accordingly.{/ts}<br /><br />
    {ts}IMPORTANT: This page does NOT control credit card/payment method choices for sites and/or contributors using the PayPal Express service (e.g. where billing information is collected on the Payment Processor's website).{/ts}
  {elseif $gName eq 'event_type'}
    {ts}Use Event Types to categorize your events. Event feeds can be filtered by Event Type and participant searches can use Event Type as a criteria.{/ts}
  {elseif $gName eq 'participant_role'}
    {ts}Define participant roles for events here (e.g. Attendee, Host, Speaker...). You can then assign roles and search for participants by role.{/ts}
  {elseif $gName eq 'participant_status'}
    {ts}Define statuses for event participants here (e.g. Registered, Attended, Cancelled...). You can then assign statuses and search for participants by status.{/ts} {ts}"Counted?" controls whether a person with that status is counted as participant for the purpose of controlling the Maximum Number of Participants.{/ts}
  {elseif $isLocked}
    {ts}This option group is reserved for system use. You cannot add or delete options in this list.{/ts}
  {else}
    {ts 1=$gLabel}The existing option choices for %1 group are listed below. You can add, edit or delete them from this screen.{/ts}
  {/if}
</div>
{/if}

<div class="crm-content-block crm-block">
{if $rows}
{foreach from=$rows item=row}
  {if !empty($row.icon)}{assign var='hasIcons' value=true}{/if}
{/foreach}
<div id={$gName}>
        {strip}
  {* handle enable/disable actions*}
  {include file="CRM/common/enableDisableApi.tpl"}
        <table id="options" class="row-highlight">
         <thead>
         <tr>
            {if $hasIcons}
              <th><span class="sr-only">{ts}Icons{/ts}</span></th>
            {/if}
            {if $showComponent}
                <th>{ts}Component{/ts}</th>
            {/if}
            <th>
              {if $gName eq "redaction_rule"}
                  {ts}Match Value or Expression{/ts}
              {else}
                  {ts}Label{/ts}
              {/if}
            </th>
            {if $gName eq "case_status"}
              <th>
                {ts}Status Class{/ts}
              </th>
            {/if}
            <th>
                {if $gName eq "redaction_rule"}
                    {ts}Replacement{/ts}
                {elseif $gName eq "activity_type"}
                    {ts}Activity Type ID{/ts}
                {else}
                    {ts}Value{/ts}
                {/if}
            </th>
            {if $gName eq "payment_instrument"}<th>{ts}Account{/ts}</th>{/if}
            {if $showCounted}<th>{ts}Counted?{/ts}</th>{/if}
            <th id="nosort">{ts}Description{/ts}</th>
            <th>{ts}Order{/ts}</th>
            {if $showIsDefault}<th>{ts}Default{/ts}</th>{/if}
            <th>{ts}Reserved{/ts}</th>
            <th>{ts}Enabled?{/ts}</th>
            <th><span class="sr-only">{ts}Actions{/ts}</span></th>
          </tr>
          </thead>
          <tbody>
        {foreach from=$rows item=row}
          <tr id="option_value-{$row.id}" class="crm-admin-options crm-admin-options_{$row.id} crm-entity {cycle values="odd-row,even-row"}{if NOT $row.is_active} disabled{/if}">
            {if $hasIcons}
              <td class="crm-admin-options-icon"><i class="crm-i {$row.icon}" role="img" aria-hidden="true"></i></td>
            {/if}
            {if $showComponent}
              <td class="crm-admin-options-component_name">{$row.component_name}</td>
            {/if}
            <td class="crm-admin-options-label crm-editable" data-field="label" {if $row.color}style="background-color: {$row.color}; color: {$row.color|colorContrast};"{/if}>
              {$row.label}
            </td>
            {if $gName eq "case_status"}
              <td class="crm-admin-options-grouping">{$row.grouping}</td>
            {/if}
            <td class="crm-admin-options-value">{$row.value}</td>
            {if $gName eq "payment_instrument"}
              <td>{$row.financial_account}</td>
            {/if}
            {if $showCounted}
              <td class="center crm-admin-options-filter">{icon condition=$row.filter}{ts}Counted{/ts}{/icon}</td>
            {/if}
            <td class="crm-admin-options-description crm-editable" data-field="description" data-type="textarea">{$row.description}</td>
            <td class="nowrap crm-admin-options-order">{if $row.weight}{$row.weight|smarty:nodefaults}{/if}</td>
            {if $showIsDefault}
              <td class="crm-admin-options-is_default" align="center">{if $row.is_default}{icon}{ts}Default{/ts}{/icon}{/if}&nbsp;</td>
            {/if}
            <td class="crm-admin-options-is_reserved">{if $row.is_reserved eq 1} {ts}Yes{/ts} {else} {ts}No{/ts} {/if}</td>
            <td class="crm-admin-options-is_active" id="row_{$row.id}_status">{if $row.is_active eq 1} {ts}Yes{/ts} {else} {ts}No{/ts} {/if}</td>
            <td>{$row.action|smarty:nodefaults|replace:'xx':$row.id}</td>
          </tr>
        {/foreach}
        </tbody>
        </table>
        {/strip}

</div>
{else}
    <div class="messages status no-popup">
      {icon icon="fa-info-circle"}{/icon}
      {ts}None found.{/ts}
    </div>
{/if}
    <div class="action-link">
      {if $isLocked ne 1}
        {crmButton p="civicrm/admin/options/$gName" q='action=add&reset=1' class="new-option" icon="plus-circle"}{if !$gLabel}{ts}Add Option{/ts}{else}{ts 1=$gLabel}Add %1{/ts}{/if}{/crmButton}
      {/if}
      {crmButton p="civicrm/admin/options" q="action=browse&reset=1" class="cancel" icon="check"}{ts}Done{/ts}{/crmButton}
    </div>
</div>
{/if}
