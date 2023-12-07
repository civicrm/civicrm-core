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
  <div id="crm-profile-block" class="crm-container crm-public">
    {else}
  <div id="crm-container" class="crm-container crm-public" lang="{$config->lcMessages|truncate:2:"":true}" xml:lang="{$config->lcMessages|truncate:2:"":true}">
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
      {assign var=n value=$field.name}
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
        {if array_key_exists('options_per_line', $field) && $field.options_per_line}
          <div class="crm-section editrow_{$n}-section form-item" id="editrow-{$n}">
            <div class="label">{$form.$n.label}</div>
            <div class="content edit-value">
              {assign var="count" value=1}
              {strip}
                <table class="form-layout-compressed">
                <tr>
                {* sort by fails for option per line. Added a variable to iterate through the element array*}
                  {foreach name=outer key=key item=item from=$form.$n}
                    {* There are both numeric and non-numeric keys mixed in here, where the non-numeric are metadata that aren't arrays with html members. *}
                    {if is_array($item) && array_key_exists('html', $item)}
                      <td class="labels font-light">{$form.$n.$key.html}</td>
                      {if $count == $field.options_per_line}
                      </tr>
                      <tr>
                        {assign var="count" value=1}
                        {else}
                        {assign var="count" value=$count+1}
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
                {assign var="provider" value="$n-provider_id"}
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
                {if $field.html_type neq 'File' || ($field.html_type eq 'File' && !$field.is_view)}
                   {$form.$n.html}
                {/if}
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
            {if !$fields.$n.is_view}
               <div class="crm-section file_deleteURL-section file_deleteURL{$n}-section"><div class="content">{$customFiles.$n.deleteURL}</div></div>
            {/if}
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
