{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
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
<style>
  {literal}
  #mainTabContainer {
    background: transparent;
    border: 0 none;
  }
  #mainTabContainer pre {
    line-height: 14px;
    font-size: 11px;
    margin: 0;
    border: 0 none;
  }
  #mainTabContainer ul.ui-tabs-nav {
    font-size: 1.1em;
    margin-bottom: .6em;
  }
  pre#api-result {
    max-height: 50em;
  }
  pre#api-result,
  div#doc-result,
  pre#example-result {
    padding:1em;
    border: 1px solid lightgrey;
    margin-top: 1em;
    overflow: auto;
  }
  #api-params-table th:first-child,
  #api-params-table td:first-child {
    width: 35%;
    min-width: 190px;
  }
  #api-params-table td[colspan] {
    width: 100%;
  }
  #api-params-table td:first-child + td,
  #api-params-table th:first-child + th {
    width: 140px;
  }
  #api-params-table td:first-child + td select {
    width: 132px;
  }
  #api-params-table td:first-child + td + td,
  #api-params-table th:first-child + th + th {
    width: 65%
  }
  #api-generated td:first-child {
    width: 60px;
  }
  #api-params {
    min-height: 1em;
  }
  #api-params .red-icon {
    margin-top: .5em;
  }
  .api-param-remove {
    float: right;
  }
  #mainTabContainer label {
    display: inline;
    font-weight: bold;
  }
  #mainTabContainer label.api-checkbox-label {
    font-weight: normal;
  }
  #mainTabContainer h4 {
    font-weight: bold;
    font-size: 1.2em;
    margin: .2em .2em 0.5em;
  }
  #api-join {
    margin-top: 1em;
    font-size: .8em;
  }
  #api-join ul {
    margin: 0;
    padding: 0 0 0.25em 2.5em;
  }
  #api-join li > i {
    opacity: .5;
  }
  #api-join li.join-enabled > i {
    opacity: 1;
  }
  #api-generated-wraper,
  #api-result {
    overflow: auto;
  }
  #api-explorer .api-options-row + .api-options-row label {
    display: none;
  }
  .api-options-row td:first-child {
    text-align: right;
  }
  .select2-choice .icon {
    margin-top: .2em;
    background-image: url("{/literal}{$config->resourceBase}{literal}/i/icons/jquery-ui-2786C2.png");
  }
  .select2-default .icon {
    background-image: url("{/literal}{$config->resourceBase}{literal}/i/icons/jquery-ui-52534D.png");
    opacity: .8;
  }
  .api-field-desc {
    font-size: .8em;
    color: #828282;
    line-height: 1.3em;
  }
  .select2-highlighted .api-field-desc,
  .select2-highlighted .crm-marker {
    color: #fcfcfc;
  }
  .api-param-op[readonly] {
    width: 4em;
  }
  pre ol.linenums li {
    list-style-type: decimal;
    color: #CFCFCF;
  }
  pre ol.linenums li:hover {
    color: #828282;
    background-color: #f2f2f2;
  }
  pre li.L1, pre li.L3, pre li.L5, pre li.L7, pre li.L9,
  #api-generated td + td,
  #mainTabContainer pre {
    background-color: #f9f9f9;
  }
  .api-doc-code {
    margin-top: 1em;
    border-top: 1px solid #d3d3d3;
  }
  .api-doc-code .collapsible-title {
    font-weight: bold;
    margin-top: .5em;
  }
  .doc-filename {
    text-align: right;
    font-style: italic;
  }
  {/literal}
</style>

<div id="mainTabContainer">
  <ul>
    <li class="ui-corner-all" title="GUI to build and execute API calls">
      <a href="#explorer-tab">{ts}Explorer{/ts}</a>
    </li>
    <li class="ui-corner-all" title="Auto-generated examples from the test suite">
      <a href="#examples-tab">{ts}Examples{/ts}</a>
    </li>
    <li class="ui-corner-all" title="API source-code and code-level documentation">
      <a href="#docs-tab">{ts}Code Docs{/ts}</a>
    </li>
  </ul>

  <div id="explorer-tab">

    <form id="api-explorer">
      <label for="api-entity">{ts}Entity{/ts}:</label>
      <select class="crm-form-select big required" id="api-entity" name="entity">
        <option value="" selected="selected">{ts}Choose{/ts}...</option>
        {crmAPI entity="Entity" var="entities"}
        {foreach from=$entities.values item=entity}
          <option value="{$entity}" {if !empty($entities.deprecated) && in_array($entity, $entities.deprecated)}class="strikethrough"{/if}>
            {$entity}
          </option>
        {/foreach}
      </select>
      &nbsp;&nbsp;
      <label for="api-action">{ts}Action{/ts}:</label>
      <input class="crm-form-text" id="api-action" name="action" value="get">
      &nbsp;&nbsp;

      <label for="debug-checkbox" class="api-checkbox-label" title="{ts}Display debug output with results.{/ts}">
        <input type="checkbox" class="crm-form-checkbox api-param-checkbox api-input" id="debug-checkbox" name="debug" value="1" >debug
      </label>
      &nbsp;|&nbsp;

      <label for="sequential-checkbox" class="api-checkbox-label" title="{ts}Sequential is more compact format, well-suited for json and smarty.{/ts}">
        <input type="checkbox" class="crm-form-checkbox api-param-checkbox api-input" id="sequential-checkbox" name="sequential" checked="checked" value="1">sequential
      </label>

      <div id="api-join" class="crm-form-block crm-collapsible collapsed" style="display:none;">
        <h4 class="collapsible-title">{ts}Join on:{/ts} {help id='api-join'}</h4>
        <div></div>
      </div>

      <table id="api-params-table">
        <thead style="display: none;">
          <tr>
            <th>{ts}Name{/ts} {help id='param-name'}</th>
            <th>{ts}Operator{/ts} {help id='param-op'}</th>
            <th>{ts}Value{/ts} {help id='param-value'}</th>
          </tr>
        </thead>
        <tbody id="api-params"></tbody>
      </table>
      <div id="api-param-buttons" style="display: none;">
        <a href="#" class="crm-hover-button" id="api-params-add"><i class="crm-i fa-plus"></i> {ts}Add Parameter{/ts}</a>
        <a href="#" class="crm-hover-button" id="api-option-add"><i class="crm-i fa-cog"></i> {ts}Add Option{/ts}</a>
        <a href="#" class="crm-hover-button" id="api-chain-add"><i class="crm-i fa-link"></i> {ts}Chain API Call{/ts}</a>
        {help id="api-chain"}
      </div>
      <div id="api-generated-wraper">
        <table id="api-generated" border=1>
          <caption>{ts}Code{/ts}</caption>
          <tr><td>Rest</td><td><pre id="api-rest"></pre></td></tr>
          <tr><td>Smarty</td><td><pre class="linenums" id="api-smarty" title='smarty syntax (for get actions)'></pre></td></tr>
          <tr><td>Php</td><td><pre class="linenums" id="api-php" title='php syntax'></pre></td></tr>
          <tr><td>Javascript</td><td><pre class="linenums" id="api-json" title='javascript syntax'></pre></td></tr>
          {if $config->userSystem->is_drupal}
            <tr><td>Drush</td><td><pre id="api-drush" title='drush syntax'></pre></td></tr>
          {/if}
          {if $config->userSystem->is_wordpress}
            <tr><td>WP-CLI</td><td><pre id="api-wpcli" title='wp-cli syntax'></pre></td></tr>
          {/if}
        </table>
      </div>
      <div class="crm-submit-buttons">
        <span class="crm-button crm-i-button">
          <i class="crm-i fa-bolt"></i><input type="submit" value="{ts}Execute{/ts}" class="crm-form-submit" accesskey="S" title="{ts}Execute API call and display results{/ts}"/>
        </span>
      </div>
<pre id="api-result" class="linenums">
{ts}Results are displayed here.{/ts}
</pre>
    </form>
  </div>

  <div id="examples-tab">
    <form id="api-examples">
      <label for="example-entity">{ts}Entity{/ts}:</label>
      <select class="crm-form-select big required" id="example-entity" name="entity">
        <option value="" selected="selected">{ts}Choose{/ts}...</option>
        {foreach from=$examples item=entity}
          <option value="{$entity}" {if !empty($entities.deprecated) && in_array($entity, $entities.deprecated)}class="strikethrough"{/if}>
            {$entity}
          </option>
        {/foreach}
      </select>
      &nbsp;&nbsp;
      <label for="example-action">{ts}Example{/ts}:</label>
      <select class="crm-form-select big crm-select2" id="example-action" name="action">
        <option value="" selected="selected">{ts}Choose{/ts}...</option>
      </select>
<pre id="example-result" class="linenums lang-php" placeholder="{ts escape='html'}Results are displayed here.{/ts}">
{ts}Results are displayed here.{/ts}
</pre>
    </form>
  </div>

  <div id="docs-tab">
    <form id="api-docs">
      <label for="doc-entity">{ts}Entity{/ts}:</label>
      <select class="crm-form-select big required" id="doc-entity" name="entity">
        <option value="" selected="selected">{ts}Choose{/ts}...</option>
        {foreach from=$entities.values item=entity}
          <option value="{$entity}" {if !empty($entities.deprecated) && in_array($entity, $entities.deprecated)}class="strikethrough"{/if}>
            {$entity}
          </option>
        {/foreach}
      </select>
      &nbsp;&nbsp;
      <label for="doc-action">{ts}Action{/ts}:</label>
      <select class="crm-form-select big crm-select2" id="doc-action" name="action">
        <option value="" selected="selected">{ts}Choose{/ts}...</option>
      </select>
      <div id="doc-result">
        {ts}Results are displayed here.{/ts}
      </div>
    </form>
  </div>
</div>

{strip}
<script type="text/template" id="api-param-tpl">
  <tr class="api-param-row">
    <td><input style="width: 100%;" class="crm-form-text api-param-name api-input" value="<%= name %>" placeholder="{ts}Parameter{/ts}" /></td>
    <td>
      {literal}
      <% if (noOps) { %>
        <input class="crm-form-text api-param-op" value="=" readonly="true" title="{/literal}{ts}Other operators not available for this action.{/ts}{literal}" />
      <% } else { %>
      {/literal}
        <select class="crm-form-select api-param-op">
          {foreach from=$operators item='op'}
            <option value="{$op|htmlspecialchars}">{$op|htmlspecialchars}</option>
          {/foreach}
        </select>
      {literal}
      <% } %>
      {/literal}
    </td>
    <td>
      <input style="width: 85%;" class="crm-form-text api-param-value api-input" placeholder="{ts}Value{/ts}"/>
      <a class="crm-hover-button api-param-remove" href="#"><i class="crm-i fa-times"></i></a>
    </td>
  </tr>
</script>

<script type="text/template" id="api-return-tpl">
  <tr class="api-return-row">
    <td colspan="3">
      <label for="api-return-value">
        <%- title %>:
        <% if(required) {ldelim} %> <span class="crm-marker">*</span> <% {rdelim} %>
      </label> &nbsp;
      <input type="hidden" class="api-param-name" value="return" />
      <input style="width: 50%;" id="api-return-value" class="crm-form-text api-param-value api-input"/>
    </td>
  </tr>
</script>

<script type="text/template" id="api-options-tpl">
  <tr class="api-options-row">
    <td>
      <label>{ts}Options{/ts}: &nbsp;</label>
    </td>
    <td>
      <input class="crm-form-text api-option-name api-input" style="width: 12em;" placeholder="{ts}Option{/ts}"/>
    </td>
    <td>
      <input style="width: 85%;" class="crm-form-text api-option-value api-input" placeholder="{ts}Value{/ts}"/>
      <a class="crm-hover-button api-param-remove" href="#"><i class="crm-i fa-times"></i></a>
    </td>
  </tr>
</script>

<script type="text/template" id="api-chain-tpl">
  <tr class="api-chain-row">
    <td>
      <select style="width: 100%;" class="crm-form-select api-chain-entity">
        <option value=""></option>
        {foreach from=$entities.values item=entity}
          <option value="{$entity}" {if !empty($entities.deprecated) && in_array($entity, $entities.deprecated)}class="strikethrough"{/if}>
            {$entity}
          </option>
        {/foreach}
      </select>
    </td>
    <td>
      <select class="crm-form-select api-chain-action">
        <option value="get">get</option>
      </select>
    </td>
    <td>
      <input style="width: 85%;" class="crm-form-text api-param-value api-input" value="{ldelim}{rdelim}" placeholder="{ts}API Params{/ts}"/>
      <a class="crm-hover-button api-param-remove" href="#"><i class="crm-i fa-times"></i></a>
    </td>
  </tr>
</script>

<script type="text/template" id="doc-code-tpl">
  <div class="crm-collapsible collapsed api-doc-code">
    <div class="collapsible-title">{ts}Source Code{/ts}</div>
    <div>
      <div class="doc-filename"><%- file %></div>
      <pre class="lang-php linenums"><%- code %></pre>
    </div>
  </div>
</script>

<script type="text/template" id="join-tpl">
  {literal}
  <ul class="fa-ul">
    <% _.forEach(joins, function(join, name) { %>
      <li <% if(join.checked) { %>class="join-enabled"<% } %>>
        <i class="fa-li crm-i fa-reply fa-rotate-180"></i>
        <label for="select-join-<%= name %>" class="api-checkbox-label">
          <input type="checkbox" id="select-join-<%= name %>" value="<%= name %>" data-entity="<%= join.entity %>" <% if(join.checked) { %>checked<% } %>/>
          <%- join.title %>
        </label>
      </li>
      <% if(join.children) print(tpl({joins: join.children, tpl: tpl})); %>
    <% }); %>
  </ul>
  {/literal}
</script>
{/strip}
