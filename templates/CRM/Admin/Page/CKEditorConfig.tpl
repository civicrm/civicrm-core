{*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
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
<style>{literal}
  .select2-results .ui-icon,
  .select2-results .crm-i,
  .select2-container .ui-icon,
  .select2-container .crm-i,
  .select2-results img,
  .select2-container img {
    display: inline-block;
    position: relative;
    top: 2px;
  }
  #toolbarModifierWrapper .toolbar button:last-child,
  #toolbarModifierWrapper .toolbar button[data-group=config] {
    display: none;
  }
  .api-field-desc {
    font-size: .8em;
    color: #828282;
    line-height: 1.3em;
  }
  .select2-highlighted .api-field-desc {
    color: #fcfcfc;
  }
  #crm-custom-config-options > div {
    margin: .5em .2em;
  }
  #crm-container .ui-tabs-nav {
    padding-bottom: 0;
  }
  #crm-container .ui-tabs-nav li {
    margin-left: .3em;
  }
  #crm-container .ui-tabs-nav a {
    font-size: 1.1em;
    border-bottom: 0 none;
    padding: 3px 10px 1px !important;
  }
  .crm-config-option-row span.crm-error:after {
    font-family: FontAwesome;
    content: " \f071 Invalid JSON"
  }
{/literal}</style>
{* Force the custom config file to reload by appending a new query string *}
<script type="text/javascript">
  {if $configUrl}CKEDITOR.config.customConfig = '{$configUrl}?{php}print str_replace(array(' ', '.'), array('', '='), microtime());{/php}'{/if};
</script>

<div class="ui-tabs">
  <ul class="ui-tabs-nav ui-helper-reset ui-helper-clearfix ui-widget-header">
    <li>{ts}Preset:{/ts}</li>
    {foreach from=$presets key='k' item='p'}
      <li class="ui-tabs-tab ui-corner-top ui-state-default ui-tab {if $k == $preset}ui-tabs-active ui-state-active{/if}">
        <a class="ui-tabs-anchor" href="{crmURL q="preset=$k"}">{$p}</a>
      </li>
    {/foreach}
  </ul>
</div>
<form method="post" action="{crmURL}" id="toolbarModifierForm">
  <fieldset>
    <div class="crm-block crm-form-block">
      <label for="skin">{ts}Skin{/ts}</label>
      <select id="skin" name="config_skin" class="crm-select2 eight config-param">
        {foreach from=$skins item='s'}
          <option value="{$s}" {if $s == $skin}selected{/if}>{$s|ucfirst}</option>
        {/foreach}
      </select>
      &nbsp;&nbsp;
      <label for="extraPlugins">{ts}Plugins{/ts}</label>
      <input id="extraPlugins" name="config_extraPlugins" class="huge config-param" value="{$extraPlugins}" placeholder="{ts}Select optional extra features{/ts}">
    </div>

    <div class="editors-container">
      <div id="editor-basic"></div>
      <div id="editor-advanced"></div>
    </div>

    <div class="configurator">
      <div>
        <div id="toolbarModifierWrapper" class="active"></div>
      </div>
    </div>


    <div class="crm-block crm-form-block">
      <fieldset>
        <legend>{ts}Advanced Options{/ts}</legend>
        <div class="description">{ts 1='href="https://docs.ckeditor.com/ckeditor4/latest/api/CKEDITOR_config.html" target="_blank"'}Refer to the <a %1>CKEditor Api Documentation</a> for details.{/ts}</div>
        <div id="crm-custom-config-options"></div>
      </fieldset>
    </div>

    <div class="crm-submit-buttons">
      <span class="crm-button crm-i-button">
        <i class="crm-i fa-wrench"></i> <input type="submit" value="{ts}Save{/ts}" name="save" class="crm-form-submit" accesskey="S"/>
      </span>
      <span class="crm-button crm-i-button">
        <i class="crm-i fa-times"></i> <input type="submit" value="{ts}Revert to Default{/ts}" name="revert" class="crm-form-submit" onclick="return confirm('{$revertConfirm}');"/>
      </span>
    </div>
    <input type="hidden" value="{$preset}" name="preset" />
  </fieldset>
</form>
<script type="text/template" id="config-row-tpl">
  <div class="crm-config-option-row">
    <input class="huge crm-config-option-name" placeholder="{ts}Option{/ts}"/>
  </div>
</script>