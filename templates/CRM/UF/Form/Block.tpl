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

        {if $form.formName eq 'Confirm' OR $form.formName eq 'ThankYou'}
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
      {elseif $field.options_per_line != 0}
        <div class="crm-section {$n}-section">
        {* Show explanatory text for field if not in 'view' or 'preview' modes *}
          {if $field.help_pre && $action neq 4 && $action neq 1028}
            &nbsp;&nbsp;<span class="description">{$field.help_pre}</span>
          {/if}
          <div class="label option-label">{$form.$n.label}</div>
          <div class="content 3">
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
          {* Show explanatory text for field if not in 'view' or 'preview' modes *}
            {if $field.help_post && $action neq 4 && $action neq 1028}
              <span class="description">{$field.help_post}</span>
            {/if}
          </div>
          <div class="clear"></div>
        </div>
      {else}
        <div class="crm-section {$n}-section">
        {* Show explanatory text for field if not in 'view' or 'preview' modes *}
          {if $field.help_pre && $action neq 4 && $action neq 1028}
            &nbsp;&nbsp;<span class="description">{$field.help_pre}</span>
          {/if}
          <div class="label">
            {$form.$n.label}
          </div>
          <div class="content">
            {if $n|substr:0:3 eq 'im-'}
              {assign var="provider" value=$n|cat:"-provider_id"}
              {$form.$provider.html}&nbsp;
            {elseif $n|substr:0:4 eq 'url-'}
              {assign var="websiteType" value=$n|cat:"-website_type_id"}
              {$form.$websiteType.html}&nbsp;
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
              {$form.$n.html}
              {if $form.$phone_ext_field.html}
                &nbsp;{$form.$phone_ext_field.html}
              {/if}
            {else}
              {$form.$n.html}
              {if $n eq 'gender' && $form.$fieldName.frozen neq true}
                <span class="crm-clear-link">(<a href="#" title="unselect" onclick="unselectRadio('{$n}', '{$form.formName}');return false;">{ts}clear{/ts}</a>)</span>
              {/if}
            {/if}

          {*CRM-4564*}
            {if $field.html_type eq 'Radio' && $form.$fieldName.frozen neq true && $field.is_required neq 1}
              <span style="line-height: .75em; margin-top: 1px;">
                  <span class="crm-clear-link">(<a href="#" title="unselect" onclick="unselectRadio('{$n}', '{$form.formName}');return false;">{ts}clear{/ts}</a>)</span>
                 </span>
            {elseif $field.html_type eq 'Autocomplete-Select'}
              {if $field.data_type eq 'ContactReference'}
              {include file="CRM/Custom/Form/ContactReference.tpl" element_name = $n}
              {else}
              {include file="CRM/Custom/Form/AutoComplete.tpl" element_name = $n}
              {/if}
            {/if}

          {* Show explanatory text for field if not in 'view' or 'preview' modes *}
            {if $field.help_post && $action neq 4 && $action neq 1028}
              <br /><span class="description">{$field.help_post}</span>
            {/if}
          </div>
          <div class="clear"></div>
        </div>
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
  cj(function(){
    cj('#selector tr:even').addClass('odd-row ');
    cj('#selector tr:odd ').addClass('even-row');
  });
</script>
{/literal}