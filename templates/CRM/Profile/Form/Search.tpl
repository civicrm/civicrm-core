{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{if ! empty($fields)}
  {if $groupId}
  <details class="crm-accordion-light crm-group-{$groupId}-accordion" {if $rows}{else}open{/if}>
    <summary>
      {ts}Edit Search Criteria{/ts}
    </summary>
  <div class="crm-accordion-body">
    {else}
  <div>
  {/if}

  <table class="form-layout-compressed" id="profile">
    {foreach from=$fields item=field key=fieldName}
      {if $field.skipDisplay}
        {continue}
      {/if}
      {assign var=n value=$field.name}
      {assign var="operator_name" value=$n|cat:'_operator'}
      {if $field.is_search_range}
        {assign var=from value=$field.name|cat:'_from'}
        {assign var=to value=$field.name|cat:'_to'}
          <tr>
            <td class="label">{$form.$from.label}</td>
            <td class="description">{$form.$from.html}&nbsp;&nbsp;{$form.$to.label}&nbsp;&nbsp;{$form.$to.html}</td>
          </tr>
      {else}
        <tr>
          <td class="label">
            {$form.$n.label}
          </td>
          {if $n eq 'addressee' or $n eq 'email_greeting' or $n eq 'postal_greeting'}
            <td class="description">
            {include file="CRM/Profile/Form/GreetingType.tpl"}
            </td>
          {elseif $n eq 'group'}
            <td>
              <table id="selector" class="selector" style="width:auto;">
                <tr><td>{$form.$n.html}{* quickform add closing </td> </tr>*}
              </table>
            </td>
          {else}
            <td class="description">
              {if $n|str_starts_with:'phone'}
                {assign var="phone_ext_field" value=$n|replace:'phone':'phone_ext'}
                {$form.$n.html}
                {if $form.$phone_ext_field.html}
                  &nbsp;{$form.$phone_ext_field.html}
                {/if}
              {else}
                {$form.$n.html}
              {/if}
              {if $field.html_type eq 'Autocomplete-Select' and $field.data_type eq 'ContactReference'}
                {include file="CRM/Custom/Form/ContactReference.tpl" element_name = $n}
              {/if}
              {if !empty($form.$operator_name)}
                <span class="crm-multivalue-search-op" for="{$n}">{$form.$operator_name.html}</span>
                {assign var="add_multivalue_js" value=true}
              {/if}
            </td>
          {/if}
        </tr>
      {/if}
    {/foreach}

    {if $proximity_search}
      <tr><td colspan="2">{include file="CRM/Contact/Form/Task/ProximityCommon.tpl"}</td></tr>
    {/if}

    <tr><td></td><td>{include file="CRM/common/formButtons.tpl" location=''}</td></tr>
  </table>

  {if $groupId}
  {else}
  </div>
  {/if}
  {if $groupId}
  </div>
  </details>
  {/if}

{elseif $statusMessage}
  <div class="messages status no-popup">
    {icon icon="fa-info-circle"}{/icon}
    {$statusMessage}
  </div>
{else} {* empty fields *}
  <div class="messages status no-popup">
    {icon icon="fa-info-circle"}{/icon}
    {ts}No fields in this Profile have been configured as searchable. Ask the site administrator to check the Profile setup.{/ts}
  </div>
{/if}
{literal}
  <script type="text/javascript">
    CRM.$(function($) {
      $('#selector tr:even').addClass('odd-row ');
      $('#selector tr:odd ').addClass('even-row');
    });
  </script>
{/literal}

{if !empty($add_multivalue_js)}
  {include file="CRM/Custom/Form/MultiValueSearch.js.tpl"}
{/if}
