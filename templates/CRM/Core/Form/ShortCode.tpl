{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
<div class="help">
  {ts}Can't find your form? Make sure it is active.{/ts}
</div>

<div class="crm-field-wrapper">
  <span class="shortcode-param">{$form.component.html}</span>&nbsp;&nbsp;
  <span class="shortcode-param" data-components='{$selects|@json_encode}'>{$form.entity.html}</span>
</div>

{foreach from=$options key='item' item='option'}
  <div class="crm-field-wrapper shortcode-param" data-components='{$option.components|@json_encode}'>
    {if $form.$item.label}
      <p>{$form.$item.label}</p>
    {/if}
    {$form.$item.html}
  </div>

{/foreach}

{* Hack to prevent WP toolbars from popping up above the dialog *}
{literal}<style type="text/css">
  #wpadminbar,
  .wp-editor-expand #wp-content-editor-tools,
  .wp-editor-expand div.mce-toolbar-grp {
    z-index: 100 !important;
  }
</style>{/literal}
