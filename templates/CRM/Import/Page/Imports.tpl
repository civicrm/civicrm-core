<p>{ts}You can use the following import options to import data into your CiviCRM System{/ts}</p>
<ul>
{foreach item=import from=$imports}
  <li><a href="{$import.url}" alt="{ts 1=$import.no_ts_label escape='htmlattribute'}%1{/ts}">{$import.label}</a></li>
{/foreach}
</ul>