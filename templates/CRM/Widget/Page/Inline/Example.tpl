<h2>This is an example of a template you can call inline</h2>
<p>If you call it from an <a href='#' id="dialog">ajax call</a>, it's ready to be embeded in your page. </p><p>You can pass an <a href="?id=42">id as param</a> and access it in {ldelim}$id{rdelim}. 
{if $id}
<p><b>Well done, you have an id as param ( {$id} )</b></p>
{/if}

<br>You can also have extra params (?example_param=dummy) and access it in {ldelim}$request.example_param{rdelim}.
<br>These are the param I could find :
</p>
<ul>
{foreach from=$request key=k item=p}
 <li>{ldelim}$request{rdelim}.{$k} = <b>{$p}</b></li>
{/foreach}
</ul>
<p>It's done to work with the smarty api, so you can fetch more data, and update or create using the ajax API.</p>
<p>

If load the page directly from your browser, it automatically adds all the page template, including the header with all the jquery plugins. This isn't usual way of calling it, but convenient for debugging purpose.
</p>

<script>
{literal}
CRM.$(function($) {
  $("#dialog").click (function () {
    var $n=$('<div>Loading '+window.location+'</div>').appendTo('body');
    $n.load(''+window.location, function(){
      alert ("loaded. might initialise more javascript");
    });
    $n.dialog ({modal:true,width:500});
    return false;
  });
  $(".ui-dialog-content #dialog").on ('click', function () { // only triggered on the dialog link in the popup
    alert ("one level of dialog is enough");
    return false;
  });
  
});

{/literal}
</script>
