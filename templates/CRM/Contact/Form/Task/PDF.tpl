{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
<div class="crm-form-block crm-block crm-contact-task-pdf-form-block">
{if $single eq false}
    <div class="messages status no-popup">{include file="CRM/Contact/Form/Task.tpl"}</div>
{/if}
{include file="CRM/Contact/Form/Task/PDFLetterCommon.tpl"}
<div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>
