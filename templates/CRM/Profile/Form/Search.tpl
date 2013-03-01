{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
*}
{if ! empty( $fields )}
  {if $groupId }
  <div class="crm-accordion-wrapper crm-group-{$groupId}-accordion {if $rows}collapsed{/if}">
    <div class="crm-accordion-header crm-master-accordion-header">
      {ts}Edit Search Criteria{/ts}
    </div>
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
      {if $field.is_search_range}
        {assign var=from value=$field.name|cat:'_from'}
        {assign var=to value=$field.name|cat:'_to'}
        {if $field.data_type neq 'Date'}
          <tr>
            <td class="label">{$form.$from.label}</td>
            <td class="description">{$form.$from.html}&nbsp;&nbsp;{$form.$to.label}&nbsp;&nbsp;{$form.$to.html}</td>
          </tr>
        {else}
          <tr>
            <td class="label">{$form.$from.label}</td>
            <td class="description">{include file="CRM/common/jcalendar.tpl" elementName=$from}
              &nbsp;&nbsp;{$form.$to.label}&nbsp;&nbsp;{include file="CRM/common/jcalendar.tpl" elementName=$to}</td>
          </tr>
        {/if}
      {elseif $field.options_per_line}
        <tr>
          <td class="option-label">{$form.$n.label}</td>
          <td>
            {assign var="count" value="1"}
            {strip}
              <table class="form-layout-compressed">
              <tr>
              {* sort by fails for option per line. Added a variable to iterate through the element array*}
                {assign var="index" value="1"}
                {foreach name=outer key=key item=item from=$form.$n}
                  {if $index < 10} {* Hack to skip QF field properties that are not checkbox elements. *}
                    {assign var="index" value=`$index+1`}
                  {else}
                    {if $field.html_type EQ 'CheckBox' AND  $smarty.foreach.outer.last EQ 1} {* Put 'match ANY / match ALL' checkbox in separate row. *}
                    </tr>
                    <tr>
                      <td class="op-checkbox" colspan="{$field.options_per_line}" style="padding-top: 0px;">{$form.$n.$key.html}</td>
                      {else}
                      <td class="labels font-light">{$form.$n.$key.html}</td>
                      {if $count EQ $field.options_per_line}
                      </tr>
                      <tr>
                        {assign var="count" value="1"}
                      {else}
                        {assign var="count" value=`$count+1`}
                      {/if}
                    {/if}
                  {/if}
                {/foreach}
              </tr>
              </table>
              {if $field.html_type eq 'Radio' and $form.formName eq 'Search'}
                <span class="crm-clear-link">(<a href="#" title="unselect" onclick="unselectRadio('{$n}', '{$form.formName}'); return false;">{ts}clear{/ts}</a>)</span>
              {/if}
            {/strip}
          </td>
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
              {if ( $field.data_type eq 'Date' or
              ( ( ( $n eq 'birth_date' ) or ( $n eq 'deceased_date' ) ) ) ) }
                {include file="CRM/common/jcalendar.tpl" elementName=$n}
              {elseif $n|substr:0:5 eq 'phone'}
                {assign var="phone_ext_field" value=$n|replace:'phone':'phone_ext'}
                {$form.$n.html}
                {if $form.$phone_ext_field.html}
                  &nbsp;{$form.$phone_ext_field.html}
                {/if}
              {else}
                {$form.$n.html}
              {/if}
              {if ($n eq 'gender') or ($field.html_type eq 'Radio' and $form.formName eq 'Search')}
                <span class="crm-clear-link">(<a href="#" title="unselect" onclick="unselectRadio('{$n}', '{$form.formName}'); return false;">{ts}clear{/ts}</a>)</span>
              {elseif $field.html_type eq 'Autocomplete-Select'}
                {if $field.data_type eq 'ContactReference'}
                  {include file="CRM/Custom/Form/ContactReference.tpl" element_name = $n}
                {else}
                  {include file="CRM/Custom/Form/AutoComplete.tpl" element_name = $n}
                {/if}
              {/if}
            </td>
          {/if}
        </tr>
      {/if}
    {/foreach}

    {if $proximity_search}
      <tr><td colspan="2">{include file="CRM/Contact/Form/Task/ProximityCommon.tpl"}</td></tr>
    {/if}

    <tr><td></td><td>{include file="CRM/common/formButtons.tpl"}</td></tr>
  </table>

  {if $groupId}
  </div><!-- /.crm-accordion-body -->
  </div><!-- /.crm-accordion-wrapper -->

    {literal}
      <script type="text/javascript">
        cj(function() {
          cj().crmAccordions();
        });
      </script>
    {/literal}

  {/if}

{elseif $statusMessage}
  <div class="messages status no-popup">
    <div class="icon inform-icon"></div>
    {$statusMessage}
  </div>
{else} {* empty fields *}
  <div class="messages status no-popup">
    <div class="icon inform-icon"></div>
    {ts}No fields in this Profile have been configured as searchable. Ask the site administrator to check the Profile setup.{/ts}
  </div>
{/if}
{literal}
  <script type="text/javascript">
    cj(function(){
      cj('#selector tr:even').addClass('odd-row ');
      cj('#selector tr:odd ').addClass('even-row');
    });
  </script>
{/literal}
