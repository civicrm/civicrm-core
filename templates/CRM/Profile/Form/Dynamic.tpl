{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
<div class="crm-profile-name-{$ufGroupName}">
{crmRegion name="profile-form-`$ufGroupName`"}

{* Profile forms when embedded in CMS account create (mode=1) or
    cms account edit (mode=8) or civicrm/profile (mode=4) pages *}
{if $deleteRecord}
<div class="messages status no-popup">
  {icon icon="fa-info-circle"}{/icon}
        {ts}Are you sure you want to delete this record?{/ts}
  </div>

  <div class="crm-submit-buttons">
    {$form._qf_Edit_upload_delete.html}
    {if $includeCancelButton}
      <a class="button cancel" href="{$cancelURL}">{$cancelButtonText}</a>
    {/if}
  </div>
{else}
{if ! empty( $fields )}
{* Wrap in crm-container div so crm styles are used.*}
{* Replace div id "crm-container" only when profile is not loaded in civicrm container, i.e for profile shown in my account and in profile standalone mode otherwise id should be "crm-profile-block" *}

  {if $action eq 1 or $action eq 2 or $action eq 4}
  <div id="crm-profile-block" class="crm-container{if $urlIsPublic} crm-public{/if}">
    {else}
  <div id="crm-container" class="crm-container{if $urlIsPublic} crm-public{/if}" lang="{$config->lcMessages|truncate:2:"":true}" xml:lang="{$config->lcMessages|truncate:2:"":true}">
  {/if}

  {if $showSaveDuplicateButton}
    <div class="crm-submit-buttons">
      {$form._qf_Edit_upload_duplicate.html}
    </div>
  {/if}
  {if $mode eq 1 || $activeComponent neq "CiviCRM"}
    {include file="CRM/Form/body.tpl"}
  {/if}
  {strip}

    {include file="CRM/common/CMSUser.tpl"}

    {if $action eq 2 and $multiRecordFieldListing}
      <h1>{ts}Edit Details{/ts}</h1>
      <div class="crm-submit-buttons" style='float:right'>
      {include file="CRM/common/formButtons.tpl" location=''}{if $showSaveDuplicateButton}{$form._qf_Edit_upload_duplicate.html}{/if}
      </div>
    {/if}

    {assign var=zeroField value="Initial Non Existent Fieldset"}
    {assign var=fieldset  value=$zeroField}
    {foreach from=$fields item=field key=fieldName}
      {if $field.skipDisplay}
        {continue}
      {/if}
      {assign var="profileID" value=$field.group_id}
      {assign var="profileFieldName" value=$field.name}
      {assign var="formElement" value=$form.$profileFieldName}
      {assign var="rowIdentifier" value=$field.name}

      {if $field.groupTitle != $fieldset}
        {if $mode neq 8 && $mode neq 4}
          <div {if $context neq 'dialog'}id="profilewrap{$field.group_id}"{/if}>
          <fieldset><legend>{$field.groupTitle}</legend>
        {/if}
        {assign var=fieldset  value=$field.groupTitle}
        {assign var=groupHelpPost  value=$field.groupHelpPost}
        {if $field.groupHelpPre}
          <div class="messages help">{$field.groupHelpPre}</div>
        {/if}
      {/if}
      {if $field.field_type eq "Formatting"}
        {$field.help_pre}
      {elseif $profileFieldName}
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
        {if $field.help_pre && $action neq 4 && $form.$profileFieldName.html}
          <div class="crm-section helprow-{$profileFieldName}-section helprow-pre" id="helprow-{$profileFieldName}">
            <div class="content description">{$field.help_pre}</div>
          </div>
        {/if}
        {if array_key_exists('options_per_line', $field) && $field.options_per_line}
          <div class="crm-section editrow_{$profileFieldName}-section form-item" id="editrow-{$profileFieldName}">
            <div class="label">{$form.$profileFieldName.label}</div>
            <div class="content edit-value">
              {$formElement.html}
             </div>
            <div class="clear"></div>
          </div>
          {else}
          <div id="editrow-{$profileFieldName}" class="crm-section editrow_{$profileFieldName}-section form-item">
            <div class="label">
              {$form.$profileFieldName.label}
            </div>
            <div class="edit-value content">
              {if $profileFieldName|substr:0:3 eq 'im-'}
                {assign var="provider" value="$profileFieldName-provider_id"}
                {$form.$provider.html}&nbsp;
              {/if}
              {if $profileFieldName eq 'email_greeting' or  $profileFieldName eq 'postal_greeting' or $profileFieldName eq 'addressee'}
                {include file="CRM/Profile/Form/GreetingType.tpl"}
              {elseif ( $profileFieldName eq 'group' && $form.group ) || ( $profileFieldName eq 'tag' && $form.tag )}
                {include file="CRM/Contact/Form/Edit/TagsAndGroups.tpl" type=$profileFieldName context="profile" tableLayout=1}
              {elseif ( $form.$profileFieldName.name eq 'image_URL' )}
                {$form.$profileFieldName.html}
                {if !empty($imageURL)}
                  <div class="crm-section contact_image-section">
                    <div class="content">
                    {include file="CRM/Contact/Page/ContactImage.tpl"}
                    </div>
                  </div>
                 {/if}
              {elseif $profileFieldName|substr:0:5 eq 'phone'}
                {assign var="phone_ext_field" value=$profileFieldName|replace:'phone':'phone_ext'}
                {$form.$profileFieldName.html}
                {if $form.$phone_ext_field.html}
                &nbsp;{$form.$phone_ext_field.html}
                {/if}
              {else}
                {if $field.html_type neq 'File' || ($field.html_type eq 'File' && !$field.is_view)}
                   {$form.$profileFieldName.html}
                {/if}
                {if $field.html_type eq 'Autocomplete-Select'}
                  {if $field.data_type eq 'ContactReference'}
                    {include file="CRM/Custom/Form/ContactReference.tpl" element_name = $profileFieldName}
                  {/if}
                {/if}
              {/if}
            </div>
            <div class="clear"></div>
          </div>

          {if $form.$profileFieldName.type eq 'file'}
            <div class="crm-section file_displayURL-section file_displayURL{$profileFieldName}-section"><div class="content">{$customFiles.$profileFieldName.displayURL}</div></div>
            {if !$fields.$profileFieldName.is_view}
               <div class="crm-section file_deleteURL-section file_deleteURL{$profileFieldName}-section"><div class="content">{$customFiles.$profileFieldName.deleteURL}</div></div>
            {/if}
          {/if}
        {/if}

      {* Show explanatory text for field if not in 'view' mode *}
        {if $field.help_post && $action neq 4 && $form.$profileFieldName.html}
          <div class="crm-section helprow-{$profileFieldName}-section helprow-post" id="helprow-{$profileFieldName}">
            <div class="content description">{$field.help_post}</div>
          </div>
        {/if}
      {/if}{* end of main if field name if *}
    {/foreach}

    {if $field.groupHelpPost}
      <div class="messages help">{$field.groupHelpPost}</div>
    {/if}

    {if $mode neq 8 && $mode neq 4}
      </fieldset>
      </div>
    {/if}

    {if ($action eq 1 and $mode eq 4 ) or ($action eq 2) or ($action eq 8192)}
      {assign var=floatStyle value=''}
      {if $action eq 2 and $multiRecordFieldListing}
        <div class="crm-multi-record-custom-field-listing">
          {include file="CRM/Profile/Page/MultipleRecordFieldsListing.tpl" showListing=true}
          {assign var=floatStyle value='float:right'}
        </div>
      {/if}
      <div class="crm-submit-buttons" style='{$floatStyle}'>
        {include file="CRM/common/formButtons.tpl" location=''}{if $showSaveDuplicateButton}{$form._qf_Edit_upload_duplicate.html}{/if}
        {if $includeCancelButton}
          <a class="button cancel" href="{$cancelURL}">
            <span>
              <i class="crm-i fa-times" aria-hidden="true"></i>
              {$cancelButtonText}
            </span>
          </a>
        {/if}
      </div>
    {/if}
  {/strip}

</div> {* end crm-container div *}

{/if} {* fields array is not empty *}
{if $multiRecordFieldListing and empty($fields)}
  {include file="CRM/Profile/Page/MultipleRecordFieldsListing.tpl" showListing=true}
{/if}
{if $statusMessage}
<div class="messages status no-popup">
  {icon icon="fa-info-circle"}{/icon}
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
