{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* this template is used for adding a contact to a group (from view context) *}

<div class="form-item">
    {$form.group_id.html} {$form.buttons.html}
    {include file="CRM/Form/validate.tpl"}
</div>
