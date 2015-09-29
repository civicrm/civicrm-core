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
    {if $field.groupTitle != $fieldset}
        {if $fieldset != $zeroField}
           {if $groupHelpPost && $action neq 4}
              <div class="messages help">{$groupHelpPost}</div>
           {/if}
           {if $mode ne 8}
              </fieldset>
           {/if}
        {/if}


        {assign var=fieldset  value=`$field.groupTitle`}
        {assign var=groupHelpPost  value=`$field.groupHelpPost`}
        {if $field.groupHelpPre && $action neq 4 && $action neq 1028}
            <div class="messages help">{$field.groupHelpPre}</div>
        {/if}
    {/if}

    {assign var=n value=$field.name}

    {if $field.options_per_line != 0}
        <div class="crm-section {$form.$n.id}-section">
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
        <div class="crm-section {$form.$n.id}-section">
    {* Show explanatory text for field if not in 'view' or 'preview' modes *}
          {if $field.help_pre && $action neq 4 && $action neq 1028}
            &nbsp;&nbsp;<span class="description">{$field.help_pre}</span>
          {/if}
           <div class="label">{$form.$n.label}</div>
           <div class="content">
             {if $n|substr:0:3 eq 'im-'}
               {assign var="provider" value=$n|cat:"-provider_id"}
               {$form.$provider.html}&nbsp;
             {/if}
             {if $n eq 'email_greeting' or  $n eq 'postal_greeting' or $n eq 'addressee'}
                {include file="CRM/Profile/Form/GreetingType.tpl"}
             {elseif $n eq 'group'}
        <table id="selector" class="selector" style="width:auto;">
          <tr><td>{$form.$n.html}{* quickform add closing </td> </tr>*}
        </table>
             {elseif ( $field.data_type eq 'Date' or
                      ( ( ( $n eq 'birth_date' ) or ( $n eq 'deceased_date' ) ) ) ) }
                      {include file="CRM/common/jcalendar.tpl" elementName=$n}
            {else}
               {$form.$n.html}
             {/if}
             {*CRM-4564*}
             {if $field.html_type eq 'Autocomplete-Select'}
           {if $field.data_type eq 'ContactReference'}
                     {include file="CRM/Custom/Form/ContactReference.tpl" element_name = $n}
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


    {if $help_post && $action neq 4}<br /><div class="messages help">{$help_post}</div>{/if}
    {/strip}

{/if} {* fields array is not empty *}

{literal}
  <script type="text/javascript">

CRM.$(function($) {
  $('#selector tr:even').addClass('odd-row ');
  $('#selector tr:odd ').addClass('even-row');
});

  </script>
{/literal}
