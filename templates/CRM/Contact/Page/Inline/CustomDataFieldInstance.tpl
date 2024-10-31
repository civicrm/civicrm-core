{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* template adding custom field instances. *}
<div class="crm-label">
  {$instance.field_title}
</div>
<div class="crm-content crm-custom_data">
  {if $instance.field_input_type === 'RichTextEditor' || $instance.field_input_type === 'Link'}
    {$instance.field_value|purify nofilter}
  {else}
    {* This would be too strict for some types - eg. contact reference.
    Fortunately the Address custom data is currently not creating links for them
    as it should so we can address when we fix that.
    *}
    {$instance.field_value|escape:html nofilter}
  {/if}
</div>
