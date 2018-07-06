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
    {include file="CRM/common/CMSUser.tpl"}
      {strip}
        {if $help_pre && $action neq 4}<div class="messages help">{$help_pre}</div>{/if}
        {assign var=zeroField value="Initial Non Existent Fieldset"}
        {assign var=fieldset  value=$zeroField}
        {include file="CRM/UF/Form/Fields.tpl"}
        {if $addCAPTCHA }
          {include file='CRM/common/ReCAPTCHA.tpl'}
        {/if}
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
