{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
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
{/literal}</style>
{* Force the custom config file to reload by appending a new query string *}
<script type="text/javascript">
  {if $configUrl}CKEDITOR.config.customConfig = '{$configUrl}?{$smarty.now}'{/if};
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
<div id="toolbarModifierForm">
  <fieldset>
    <div class="crm-block crm-form-block">
      <label for="skin">{ts}Skin{/ts}</label>
      <select id="skin" name="config_skin" class="crm-select2 eight config-param">
        {foreach from=$skins item='s'}
          <option value="{$s}" {if $s == $skin}selected{/if}>{$s|capitalize}</option>
        {/foreach}
      </select>
      &nbsp;&nbsp;
      <label for="extraPlugins">{ts}Plugins{/ts}</label>
      <input id="extraPlugins" name="config_extraPlugins" class="huge config-param" value="{$extraPlugins}" placeholder="{ts escape='htmlattribute'}Select optional extra features{/ts}">
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

    <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
  </fieldset>
</div>
<script type="text/template" id="config-row-tpl">
  <div class="crm-config-option-row">
    <input class="huge crm-config-option-name" placeholder="{ts escape='htmlattribute'}Option{/ts}"/>
  </div>
</script>
