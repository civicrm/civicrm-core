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
{capture assign=infoTitle}{ts}Preview Mode{/ts}{/capture}
{assign var="infoType" value="info"}
{if $previewField}
  {capture assign=infoMessage}<strong>{ts}Profile Field Preview{/ts}</strong>{/capture}
{else}
  {capture assign=infoMessage}<strong>{ts}Profile Preview{/ts}</strong>{/capture}
{/if}
{include file="CRM/common/info.tpl"}
<div class="crm-form-block">

{if ! empty( $fields )}
  {if $viewOnly}
  {* wrap in crm-container div so crm styles are used *}
    <div id="crm-container-inner" lang="{$config->lcMessages|truncate:2:"":true}" xml:lang="{$config->lcMessages|truncate:2:"":true}">
    {include file="CRM/common/CMSUser.tpl"}
      {strip}
        {if $help_pre && $action neq 4}<div class="messages help">{$help_pre}</div>{/if}
        {assign var=zeroField value="Initial Non Existent Fieldset"}
        {assign var=fieldset  value=$zeroField}
        {foreach from=$fields item=field key=fieldName}
          {if $field.skipDisplay}
            {continue}
          {/if}
          {if $field.groupTitle != $fieldset}
            {if $fieldset != $zeroField}
              </table>
              {if $groupHelpPost}
                <div class="messages help">{$groupHelpPost}</div>
              {/if}
              {if $mode ne 8}
                </fieldset>
              {/if}
            {/if}
            {if $mode ne 8}
              <h3>{$field.groupTitle}</h3>
            {/if}
            {assign var=fieldset  value=`$field.groupTitle`}
            {assign var=groupHelpPost  value=`$field.groupHelpPost`}
            {if $field.groupHelpPre}
              <div class="messages help">{$field.groupHelpPre}</div>
            {/if}
          <table class="form-layout-compressed" id="table-1">
          {/if}
          {* Show explanatory text for field if not in 'view' mode *}
          {if $field.help_pre && $action neq 4 && $field.field_type neq "Formatting"}
            <tr><td>&nbsp;</td><td class="description">{$field.help_pre}</td></tr>
          {/if}
          {assign var=n value=$field.name}
          {if $field.field_type eq "Formatting"}
            <tr><td colspan="2">{$field.help_pre}</td></tr>
          {elseif $field.options_per_line }
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
                      {if $index < 10}
                        {assign var="index" value=`$index+1`}
                      {else}
                        <td class="labels font-light">{$form.$n.$key.html}</td>
                        {if $count == $field.options_per_line}
                        </tr>
                          {assign var="count" value="1"}
                        {else}
                          {assign var="count" value=`$count+1`}
                        {/if}
                      {/if}
                    {/foreach}
                  </table>
                  {if $field.html_type eq 'Radio' and $form.formName eq 'Preview'}
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
            <td>
              {if $n eq 'group' && $form.group || ( $n eq 'tag' && $form.tag )}
                {include file="CRM/Contact/Form/Edit/TagsAndGroups.tpl" type=$n}
              {elseif $n eq 'email_greeting' or  $n eq 'postal_greeting' or $n eq 'addressee'}
                {include file="CRM/Profile/Form/GreetingType.tpl"}
              {elseif ( $field.data_type eq 'Date' AND $element.skip_calendar NEQ true ) or
                ( $n|substr:-5:5 eq '_date' ) or ( $field.name eq 'activity_date_time' )  }
                {include file="CRM/common/jcalendar.tpl" elementName=$form.$n.name}
              {elseif $n|substr:0:5 eq 'phone'}
                {assign var="phone_ext_field" value=$n|replace:'phone':'phone_ext'}
                {$form.$n.html}
                {if $form.$phone_ext_field.html}
                  &nbsp;{$form.$phone_ext_field.html}
                {/if}
              {else}
                {if $n|substr:0:4 eq 'url-'}
                  {assign var="websiteType" value=$n|cat:"-website_type_id"}
                  {$form.$websiteType.html}&nbsp;
                  {elseif $n|substr:0:3 eq 'im-'}
                  {assign var="provider" value=$n|cat:"-provider_id"}
                  {$form.$provider.html}&nbsp;
                {/if}
                {$form.$n.html}
                {if $field.is_view eq 0}
                  {if ( $field.html_type eq 'Radio' or  $n eq 'gender') and $form.formName eq 'Preview'}
                    <span class="crm-clear-link">(<a href="#" title="unselect" onclick="unselectRadio('{$n}', '{$form.formName}'); return false;">{ts}clear{/ts}</a>)</span>
                  {elseif $field.html_type eq 'Autocomplete-Select'}
                    {if $field.data_type eq 'ContactReference'}
                    {include file="CRM/Custom/Form/ContactReference.tpl" element_name = $n}
                      {else}
                    {include file="CRM/Custom/Form/AutoComplete.tpl" element_name = $n}
                    {/if}
                  {/if}
                {/if}
              {/if}
            </td>
            </tr>
          {/if}
        {* Show explanatory text for field if not in 'view' mode *}
          {if $field.help_post && $action neq 4}
            <tr><td>&nbsp;</td><td class="description">{$field.help_post}</td></tr>
          {/if}
        {/foreach}

        {if $addCAPTCHA }
          {include file='CRM/common/ReCAPTCHA.tpl'}
        {/if}
      </table>
        {if $field.groupHelpPost}
          <div class="messages help">{$field.groupHelpPost}</div>
        {/if}
      {/strip}
    </div> {* end crm-container div *}
  {else}
    {capture assign=infoMessage}{ts}This CiviCRM profile field is view only.{/ts}{/capture}
  {include file="CRM/common/info.tpl"}
  {/if}
{/if} {* fields array is not empty *}

  <div class="crm-submit-buttons">
  {include file="CRM/common/formButtons.tpl"}
  </div>
</div>
