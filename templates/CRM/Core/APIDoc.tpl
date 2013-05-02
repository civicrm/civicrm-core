<html>
<title>API Documentation</title>
<style>
{literal}
#result {background:lightgrey;}
#selector a {margin-right:10px;}
.required {font-weight:bold;}
.helpmsg {background:yellow;}
.docHidden {display:none;}
h2.entity {cursor:pointer}
{/literal}
</style>
<script>
if (!jQuery) {ldelim}  
var head= document.getElementsByTagName('head')[0];
var script= document.createElement('script');
script.type= 'text/javascript';
script.src= CRM.config.resourceBase + 'js/packages/jquery/jquery.js';
head.appendChild(script);
{rdelim} 
restURL = '{crmURL p="civicrm/api/json"}';
if (restURL.indexOf('?') == -1 )
restURL = restURL + '?';
else 
restURL = restURL + '&';
{literal}
if (typeof $ == "undefined") {
  $ = cj;
} 

function APIDoc(entity){
  $detail=$('#detail_'+entity);
  window.location.hash = entity;
  if ($detail.length == 1) {
    $detail.toggleClass('docHidden');
    return;
  } // else fetch the field list
  return function(entity){
  CRM.api (entity,'getFields',{version : 3}
      ,{ success:function (data){
        var h="<table id=detail_"+entity+"><tr><th>Attribute</th><th>Name</th><th>type</th></tr>";
        var type="";
        $.each(data.values, function(key, value) {
          if (typeof value.type != 'undefined') {
            var types={1:"integer",2:"string",4:"date",12:"date time",16:"boolean",32:"blob"}
            type=value.type
            if (type in types) type=types[value.type];
          }
          if(typeof value.title == 'undefined') {
            value.title="";
          }
          if (typeof value.label != 'undefined') {
            value.title = value.label + "&nbsp;<i>"+value.extends+"::"+value.groupTitle+"</i>";
            type = value.data_type;
          }
          h=h+"<tr><td>"+key+"</td><td>"+value.title+"</td><td>"+type+"</td></td>";
        });
        h=h+"</table>";
        $("#"+entity).after(h);
      }
    });
  }(entity);//closure so entity is available into success.
}


cj(function ($) {
    $('h2.entity').click ( function(){APIDoc($(this).attr('id'))} ); 
    entity=window.location.hash;
    if (entity.substring(0, 1) === '#') {
      $entity=$(entity);
      if ($entity.length == 1) {
         $entity.click();
      }
    }
    });
{/literal}
</script>
<body>
You can see the list of parameters for each entity by clicking on its name.<br>
You can <a href='{crmURL p="civicrm/api/explorer"}'>explore and try</a> the api directly on your install.

{crmAPI entity="Entity" action="get" var="entities" version=3}
{foreach from=$entities.values item=entity}
<h2 id="{$entity}" class="entity">{$entity}</option>
{/foreach}
</body>
</html>
