{*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
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
<div class="crm-profile-name-{$ufGroupName}">
{crmRegion name=profile-form-`$ufGroupName`}

{* Profile forms when embedded in CMS account create (mode=1) or
    cms account edit (mode=8) or civicrm/profile (mode=4) pages *}
{if $deleteRecord}
<div class="messages status no-popup">
  <div class="icon inform-icon"></div>&nbsp;
        {ts}Are you sure you want to delete this record?{/ts}
  </div>

  <div class="crm-submit-buttons">
    <span class="crm-button">{$form._qf_Edit_upload_delete.html}</span>
    {if $includeCancelButton}
      <a class="button cancel" href="{$cancelURL}">{$cancelButtonText}</a>
    {/if}
  </div>
{else}
{if ! empty( $fields )}
{* Wrap in crm-container div so crm styles are used.*}
{* Replace div id "crm-container" only when profile is not loaded in civicrm container, i.e for profile shown in my account and in profile standalone mode otherwise id should be "crm-profile-block" *}

  {if $action eq 1 or $action eq 2 or $action eq 4 }
  <div id="crm-profile-block" class="crm-container crm-public">
    {else}
  <div id="crm-container" class="crm-container crm-public" lang="{$config->lcMessages|truncate:2:"":true}" xml:lang="{$config->lcMessages|truncate:2:"":true}">
  {/if}

  {if $isDuplicate and ( ($action eq 1 and $mode eq 4 ) or ($action eq 2) or ($action eq 8192) ) }
    <div class="crm-submit-buttons">
      <span class="crm-button">{$form._qf_Edit_upload_duplicate.html}</span>
    </div>
  {/if}
  {if $mode eq 1 || $activeComponent neq "CiviCRM"}
    {include file="CRM/Form/body.tpl"}
  {/if}
  {strip}
    {if $help_pre && $action neq 4}
      <div class="messages help">{$help_pre}</div>
    {/if}

    {include file="CRM/common/CMSUser.tpl"}

    {if $action eq 2 and $multiRecordFieldListing}
      <h1>{ts}Edit Details{/ts}</h1>
      <div class="crm-submit-buttons" style='float:right'>
      {include file="CRM/common/formButtons.tpl"}{if $isDuplicate}<span class="crm-button">{$form._qf_Edit_upload_duplicate.html}</span>{/if}
      </div>
    {/if}

    {assign var=zeroField value="Initial Non Existent Fieldset"}
    {assign var=fieldset  value=$zeroField}
    {foreach from=$fields item=field key=fieldName}
      {if $field.skipDisplay}
        {continue}
      {/if}
      {assign var="profileID" value=$field.group_id}
      {assign var=n value=$field.name}
      {if $field.groupTitle != $fieldset}
        {if $mode neq 8 && $mode neq 4}
          <div {if $context neq 'dialog'}id="profilewrap{$field.group_id}"{/if}>
          <fieldset><legend>{$field.groupTitle}</legend>
        {/if}
        {assign var=fieldset  value=`$field.groupTitle`}
        {assign var=groupHelpPost  value=`$field.groupHelpPost`}
        {if $field.groupHelpPre}
          <div class="messages help">{$field.groupHelpPre}</div>
        {/if}
      {/if}
      {if $field.field_type eq "Formatting"}
        {$field.help_pre}
      {elseif $n}
        {if $field.groupTitle != $fieldset}
          {if $fieldset != $zeroField}
            {if $groupHelpPost}
              <div class="messages help">{$groupHelpPost}</div>
            {/if}
            {if $mode neq 8 && $mode neq 4}
            </div><!-- end form-layout-compressed-div -->
              </fieldset>
            </div>
            {/if}
          {/if}
        <div class="form-layout-compressed">
        {/if}
        {if $field.help_pre && $action neq 4 && $form.$n.html}
          <div class="crm-section helprow-{$n}-section helprow-pre" id="helprow-{$n}">
            <div class="content description">{$field.help_pre}</div>
          </div>
        {/if}
        {if $field.options_per_line}
          <div class="crm-section editrow_{$n}-section form-item" id="editrow-{$n}">
            <div class="label">{$form.$n.label}</div>
            <div class="content edit-value">
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
            </div>
            <div class="clear"></div>
          </div>{* end of main edit section div*}
          {else}
          <div id="editrow-{$n}" class="crm-section editrow_{$n}-section form-item">
            <div class="label">
              {$form.$n.label}
            </div>
            <div class="edit-value content">
              {if $n|substr:0:3 eq 'im-'}
                {assign var="provider" value=$n|cat:"-provider_id"}
                {$form.$provider.html}&nbsp;
              {/if}
              {if $n eq 'email_greeting' or  $n eq 'postal_greeting' or $n eq 'addressee'}
                {include file="CRM/Profile/Form/GreetingType.tpl"}
              {elseif ( $n eq 'group' && $form.group ) || ( $n eq 'tag' && $form.tag )}
                {include file="CRM/Contact/Form/Edit/TagsAndGroups.tpl" type=$n context="profile" tableLayout=1}
              {elseif ( $form.$n.name eq 'image_URL' )}
                {$form.$n.html}
                {if !empty($imageURL)}
                  <div class="crm-section contact_image-section">
                    <div class="content">
                    {include file="CRM/Contact/Page/ContactImage.tpl"}
                    </div>
                  </div>
                 {/if}
              {elseif $n|substr:0:5 eq 'phone'}
                {assign var="phone_ext_field" value=$n|replace:'phone':'phone_ext'}
                {$form.$n.html}
                {if $form.$phone_ext_field.html}
                &nbsp;{$form.$phone_ext_field.html}
                {/if}
              {else}
                {$form.$n.html}
                {if $field.html_type eq 'Autocomplete-Select'}
                  {if $field.data_type eq 'ContactReference'}
                    {include file="CRM/Custom/Form/ContactReference.tpl" element_name = $n}
                  {/if}
                {/if}
              {/if}
            </div>
            <div class="clear"></div>
          </div>

          {if $form.$n.type eq 'file'}
            <div class="crm-section file_displayURL-section file_displayURL{$n}-section"><div class="content">{$customFiles.$n.displayURL}</div></div>
            <div class="crm-section file_deleteURL-section file_deleteURL{$n}-section"><div class="content">{$customFiles.$n.deleteURL}</div></div>
          {/if}
        {/if}

      {* Show explanatory text for field if not in 'view' mode *}
        {if $field.help_post && $action neq 4 && $form.$n.html}
          <div class="crm-section helprow-{$n}-section helprow-post" id="helprow-{$n}">
            <div class="content description">{$field.help_post}</div>
          </div>
        {/if}
      {/if}{* end of main if field name if *}
    {/foreach}

    {if $isCaptcha && ( $mode eq 8 || $mode eq 4 || $mode eq 1 ) }
      {include file='CRM/common/ReCAPTCHA.tpl'}
      <script type="text/javascript">cj('.recaptcha_label').attr('width', '140px');</script>
    {/if}

    {if $field.groupHelpPost}
      <div class="messages help">{$field.groupHelpPost}</div>
    {/if}

    {if $mode neq 8 && $mode neq 4}
      </fieldset>
      </div>
    {/if}

    {if ($action eq 1 and $mode eq 4 ) or ($action eq 2) or ($action eq 8192)}
      {if $action eq 2 and $multiRecordFieldListing}
      {include file="CRM/Profile/Page/MultipleRecordFieldsListing.tpl" showListing=true}
        {assign var=floatStyle value='float:right'}
      {/if}
      <div class="crm-submit-buttons" style='{$floatStyle}'>
        {include file="CRM/common/formButtons.tpl"}{if $isDuplicate}<span class="crm-button">{$form._qf_Edit_upload_duplicate.html}</span>{/if}
        {if $includeCancelButton}
          <a class="button cancel" href="{$cancelURL}">
            <span>
              <i class="crm-i fa-times"></i>
              {$cancelButtonText}
            </span>
          </a>
        {/if}
      </div>
    {/if}
    {if $help_post && $action neq 4}<br /><div class="messages help">{$help_post}</div>{/if}
  {/strip}

</div> {* end crm-container div *}

<script type="text/javascript">
  {if $drupalCms}
    {literal}
    if ( document.getElementsByName("cms_create_account")[0].checked ) {
      cj('#details').show();
    }
    else {
      cj('#details').hide();
    }
    {/literal}
  {/if}
</script>
{/if} {* fields array is not empty *}
{if $multiRecordFieldListing and empty($fields)}
  {include file="CRM/Profile/Page/MultipleRecordFieldsListing.tpl" showListing=true}
{/if}
{if $drupalCms}
{include file="CRM/common/showHideByFieldValue.tpl"
trigger_field_id    ="create_account"
trigger_value       =""
target_element_id   ="details"
target_element_type ="block"
field_type          ="radio"
invert              = 0
}
{elseif $statusMessage}
<div class="messages status no-popup">
  <div class="icon inform-icon"></div>
  {$statusMessage}
</div>
{/if}
{/if} {*end of if for $deleteRecord*}
{literal}
<script type="text/javascript">

CRM.$(function($) {
  cj('#selector tr:even').addClass('odd-row ');
  cj('#selector tr:odd ').addClass('even-row');
});
{/literal}
</script>

{/crmRegion}
</div> {* end crm-profile-NAME *}
