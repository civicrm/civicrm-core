{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{if $previewField}
  {capture assign=infoTitle}{ts}Profile Field Preview{/ts}{/capture}
{else}
  {capture assign=infoTitle}{ts}Profile Preview{/ts}{/capture}
{/if}
{include file="CRM/common/info.tpl" infoType="no-popup profile-preview-msg" infoMessage=" "}
<div class="crm-form-block">

  {if ! empty( $fields )}
    {if $viewOnly}
      {* wrap in crm-container div so crm styles are used *}
      <div id="crm-container-inner" lang="{$config->lcMessages|truncate:2:"":true}" xml:lang="{$config->lcMessages|truncate:2:"":true}">
        {strip}
          {include file="CRM/UF/Form/Block.tpl" prefix=false mode=false hideFieldset=false}
        {/strip}
      </div> {* end crm-container div *}
    {else}
      {capture assign=infoMessage}{ts}This CiviCRM profile field is view only.{/ts}{/capture}
      {include file="CRM/common/info.tpl"}
    {/if}
  {/if} {* fields array is not empty *}
  {if array_key_exists('buttons', $form)}
    <div class="crm-submit-buttons">
      {include file="CRM/common/formButtons.tpl" location=''}
    </div>
  {/if}
</div>
