
<style>
{literal}
#result {background:lightgrey;}
#selector a {margin-right:10px;}
.required {font-weight:bold;}
.helpmsg {background:yellow;}
#explorer label {display:inline;}
code {line-height:1em;}
{/literal}
</style>

<body>
<form id="explorer">
<label>entity</label>
<select id="entity" data-id="entity">
  <option value="" selected="selected">Choose...</option>
{crmAPI entity="Entity" action="get" var="entities" version=3}
{foreach from=$entities.values item=entity}
  <option value="{$entity}">{$entity}</option>
{/foreach}
</select>
&nbsp;|&nbsp;

<label>action</label>
<select id="action" data-id="action">
  <option value="" selected="selected">Choose...</option>
  <option value="get">get</option>
  <option value="create" title="used to update as well, if id is set">create</option>
  <option value="delete">delete</option>
  <option value="getfields">getfields</option>
  <option value="getactions">getactions</option>
  <option value="getcount">getcount</option>
  <option value="getsingle">getsingle</option>
  <option value="getvalue">getvalue</option>
  <option value="getoptions">getoptions</option>
  <option value="getlist">getlist</option>
</select>
&nbsp;|&nbsp;

<label for="debug-checkbox">
  <input type="checkbox" id="debug-checkbox" data-id="debug" checked="checked" value="1">debug
</label>
&nbsp;|&nbsp;

<label for="sequential-checkbox" title="{ts}sequential is a more compact format, that is nicer and general and easier to use for json and smarty.{/ts}">
  <input type="checkbox" id="sequential-checkbox" data-id="sequential" checked="checked" value="1">sequential
</label>
&nbsp;|&nbsp;

<label for="json-checkbox">
  <input type="checkbox" id="json-checkbox" data-id="json" checked="checked" value="1">json
</label>

<div id="selector"></div>
<div id="extra"></div>
<input size="90" maxsize=300 id="query" value="{crmURL p="civicrm/ajax/rest" q="json=1&debug=on&entity=Contact&action=get&sequential=1&return=display_name,email,phone"}"/>
<input type="submit" value="GO" title="press to run the API query"/>
<table id="generated" border=1 style="display:none;">
  <caption>Generated codes for this api call</caption>
  <tr><td>URL</td><td><div id="link"></div></td></tr>
  <tr><td>smarty</td><td><code id="smarty" title='smarty syntax (mostly works for get actions)'></code></td></tr>
  <tr><td>php</td><td><code id="php" title='php syntax'></code></td></tr>
  <tr><td>javascript</td><td><code id="jQuery" title='javascript syntax'></code></td></tr>
</table>
<pre id="result">
You can choose an entity and an action (eg Tag Get to retrieve a list of the tags)
Or your can directly modify the url in the field above and press enter.

When you use the create method, it displays the list of existing fields for this entity.
click on the name of the fields you want to populate, fill the value(s) and press enter

The result of the ajax calls are displayed in this grey area.
</pre>
