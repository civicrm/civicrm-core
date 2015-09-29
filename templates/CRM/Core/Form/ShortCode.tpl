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
