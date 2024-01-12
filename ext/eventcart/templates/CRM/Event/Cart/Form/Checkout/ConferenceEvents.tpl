<h3>{ts 1=$mer_participant->display_name 2=$mer_participant->email}Choose Events For %1 (%2){/ts}</h3>

{foreach from=$slot_fields key=slot_name item=field_name}
  <fieldset>
    <legend>
      {$slot_name}
    </legend>
    <div class="slot_options">
      <ul class="indented">
        {$form.$field_name.html}
      </ul>
    </div>
  </fieldset>
{/foreach}

<script type="text/javascript">
var session_options = {$session_options};
{literal}
for (var radio_id in session_options)
{
  var info = session_options[radio_id];
  var label_sel = "label[for=" + radio_id + "]";
  cj("#"+radio_id +","+ label_sel).wrapAll("<li>");
  if (info.session_full) {
    cj("#"+radio_id).prop('disabled', true);
    cj("#"+radio_id).after('<span class="error">{/literal}{ts escape='js'}Session is Full{/ts}{literal}: </span>');
  }
  var more = cj('<a href="#">{/literal}{ts escape='js'}more info{/ts}{literal}</a>').click(function(event) {
    event.preventDefault();
    var nfo = cj(this).data("session_info");//F-!
    cj("<div style='font-size: 90%;'>" + (nfo.session_description || "-{/literal}{ts escape='js'}No description available for this event{/ts}{literal}-") + "</div>").dialog({
      title: nfo.session_title,
      resizable: false,
      draggable: false,
      width: 600,
      modal: true
    });
  });
  more.data("session_info", info);
  cj(label_sel).append(" ", more);
}
{/literal}
</script>

<div id="crm-submit-buttons" class="crm-submit-buttons">
  {include file="CRM/common/formButtons.tpl" location="bottom"}
</div>

{include file="CRM/Event/Cart/Form/viewCartLink.tpl"}
