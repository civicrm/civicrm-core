<div class="crm-oembed-sharing">
  <details class="crm-accordion-wrapper crm-oembed-sharing-accordion">
    <summary class="crm-accordion-header">
      {ts}Cross-Site Sharing{/ts}
    </summary>
    <div class="crm-accordion-body">
      <div>
        <label for="oembed-url">
          {ts}oEmbed URL{/ts}:
        </label>
        <textarea readonly style="width: 80%" id="oembed-url">{$oembedSharingUrl}</textarea>
        <br/>
        {ts}<strong>Tip</strong>: You may optionally <code>&amp;maxwidth=PIXELS</code> and <code>&amp;maxheight=PIXELS</code>{/ts}
      </div>
      <br/>
      <div>
        <label for="oembed-iframe">
          {ts}IFrame HTML{/ts}:
        </label>
        <textarea readonly style="width: 80%" id="oembed-iframe">{$oembedSharingIframe}</textarea>
      </div>
    </div>
  </details>
</div>
