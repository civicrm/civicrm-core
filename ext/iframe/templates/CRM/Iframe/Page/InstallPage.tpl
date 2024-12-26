<p>{ts}You may need to manually create the file...{/ts}</p>

<div>
  <label>
      {ts}Script Path{/ts}
  </label>
  <input readonly value="{$filePath|escape}">
</div>

<div>
  <label>
      {ts}Script URL{/ts}
  </label>
  <input readonly value="{$fileUrl|escape}">
</div>

{if $isCurrent}<h3>{ts}Code{/ts}</h3>{/if}
{if !$isCurrent}<h3>{ts}New Code{/ts}</h3>{/if}

<textarea readonly cols="100" rows="20">{$newCode|escape}</textarea>

{if !$isCurrent}
  <h3>{ts}Old Code{/ts}</h3>
  <textarea readonly cols="100" rows="20">{$oldCode|escape}</textarea>
{/if}
