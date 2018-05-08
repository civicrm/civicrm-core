<h3>Customer Information</h3>
<table>
<tr><th>Label</th><th>Value</th></tr>
{foreach from=$customer item=custValue key=label}
<tr><td>{$label}</td><td>{$custValue}</tr>
{/foreach}
</table>

<h3>Card Information</h3>
<table>
<tr><th>Label</th><th>Value</th></tr>
{foreach from=$card item=cardValue key=label}
<tr><td>{$label}</td><td>{$cardValue}</tr>
{/foreach}
</table>
