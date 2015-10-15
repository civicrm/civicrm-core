{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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
{* Edit or display Profile fields, when embedded in an online contribution or event registration form. *}
{if ! empty( $fields )}
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
          {if $groupHelpPost && $action neq 4}
          <div class="messages help">{$groupHelpPost}</div>
          {/if}
          {if $mode ne 8}
          </fieldset>
          {/if}
        {/if}

        {if $mode ne 8 && $action ne 1028 && $action ne 4}
        <fieldset class="crm-profile crm-profile-id-{$field.group_id} crm-profile-name-{$field.groupName}"><legend>{$field.groupTitle}</legend>
        {/if}

        {if ($form.formName eq 'Confirm' OR $form.formName eq 'ThankYou') AND $prefix neq 'honor'}
          <div class="header-dark">{$field.groupTitle} </div>
        {/if}
        {assign var=fieldset  value=`$field.groupTitle`}
        {assign var=groupHelpPost  value=`$field.groupHelpPost`}
        {if $field.groupHelpPre && $action neq 4 && $action neq 1028}
          <div class="messages help">{$field.groupHelpPre}</div>
        {/if}
      {/if}

      {assign var=n value=$field.name}

      {if $field.field_type eq "Formatting"}
        {if $action neq 4 && $action neq 1028}
          {$field.help_pre}
        {/if}
      {elseif $n}
        {* Show explanatory text for field if not in 'view' or 'preview' modes *}
        {if $field.help_pre && $action neq 4 && $action neq 1028}
          <div class="crm-section helprow-{$n}-section helprow-pre" id="helprow-{$n}">
            <div class="content description">{$field.help_pre}</div>
          </div>
        {/if}
        {if $field.options_per_line != 0}
          <div class="crm-section editrow_{$n}-section form-item" id="editrow-{$n}">
            <div class="label option-label">{if $prefix}{$form.$prefix.$n.label}{else}{$form.$n.label}{/if}</div>
            <div class="content 3">
              {assign var="count" value="1"}
              {strip}
                <table class="form-layout-compressed">
                <tr>
                {* sort by fails for option per line. Added a variable to iterate through the element array*}
                  {assign var="index" value="1"}
                  {if $prefix}
                    {assign var="formElement" value=$form.$prefix.$n}
                  {else}
                    {assign var="formElement" value=$form.$n}
                  {/if}
                  {foreach name=outer key=key item=item from=$formElement}
                    {if $index < 10}
                      {assign var="index" value=`$index+1`}
                    {else}
                      <td class="labels font-light">{$formElement.$key.html}</td>
                      {if $count == $field.options_per_line}
                      </tr>
                      <tr>
                        {assign var="count" value="1"}
                      {else}
                        {assign var="count" value=`$count+1`}
                      {/if}
                    {/if}
                  {/foreach}
                </tr>
                </table>
              {/strip}
            </div>
            <div class="clear"></div>
          </div>
        {else}
          <div class="crm-section editrow_{$n}-section form-item" id="editrow-{$n}">
            <div class="label">
              {if $prefix}{$form.$prefix.$n.label}{else}{$form.$n.label}{/if}
            </div>
            <div class="content">
              {if $n|substr:0:3 eq 'im-'}
                {assign var="provider" value=$n|cat:"-provider_id"}
                {$form.$provider.html}&nbsp;
              {/if}

              {if $n eq 'email_greeting' or  $n eq 'postal_greeting' or $n eq 'addressee'}
                {include file="CRM/Profile/Form/GreetingType.tpl"}
              {elseif ($n eq 'group' && $form.group) || ($n eq 'tag' && $form.tag)}
                {include file="CRM/Contact/Form/Edit/TagsAndGroups.tpl" type=$n title=null context="profile"}
              {elseif ( ( $field.data_type eq 'Date' ) or
                ( $n|substr:-5:5 eq '_date' ) ) AND
              ( $form.formName neq 'Confirm' )  AND
              ( $form.formName neq 'ThankYou' ) }
                {include file="CRM/common/jcalendar.tpl" elementName=$n}
              {elseif $n|substr:0:5 eq 'phone'}
                {assign var="phone_ext_field" value=$n|replace:'phone':'phone_ext'}
                {if $prefix}{$form.$prefix.$n.html}{else}{$form.$n.html}{/if}
                {if $form.$phone_ext_field.html}
                  &nbsp;{$form.$phone_ext_field.html}
                {/if}
              {else}
                {if $prefix}
                  {if $n eq 'organization_name' && !empty($form.onbehalfof_id)}
                    {$form.onbehalfof_id.html}
                  {/if}
                  {$form.$prefix.$n.html}
		{else}
		  {$form.$n.html}
		{/if}
              {/if}

            {*CRM-4564*}
              {if $field.html_type eq 'Autocomplete-Select'}
                {if $field.data_type eq 'ContactReference'}
                {include file="CRM/Custom/Form/ContactReference.tpl" element_name = $n}
                {/if}
              {/if}
          </div>
          <div class="clear"></div>
        </div>
        {/if}
        {* Show explanatory text for field if not in 'view' or 'preview' modes *}
        {if $field.help_post && $action neq 4 && $action neq 1028}
          <div class="crm-section helprow-{$n}-section helprow-post" id="helprow-{$n}">
            <div class="content description">{$field.help_post}</div>
          </div>
        {/if}
      {/if}
    {/foreach}

    {if $field.groupHelpPost && $action neq 4  && $action neq 1028}
      <div class="messages help">{$field.groupHelpPost}</div>
    {/if}

    {if $mode eq 4}
      <div class="crm-submit-buttons">
        {$form.buttons.html}
      </div>
    {/if}

    {if $mode ne 8 && $action neq 1028}
    </fieldset>
    {/if}

    {if $help_post && $action neq 4}<br /><div class="messages help">{$help_post}</div>{/if}
  {/strip}

{/if} {* fields array is not empty *}

{literal}
<script type="text/javascript">
  CRM.$(function($) {
    $('#selector tr:even').addClass('odd-row');
    $('#selector tr:odd ').addClass('even-row');
  });
</script>
{/literal}
