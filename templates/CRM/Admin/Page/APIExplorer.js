CRM.$(function($) {
  var restURL = CRM.url("civicrm/ajax/rest");

  function toggleField (name, label, type) {
    var h = '<div>\
      <label for="' + name + '">'+label+'</label>: <input name="' + name + '" data-id="'+name+ '" />\
      <a href="#" class="remove-extra" title=' + ts('Remove Field') + '>X</a>\
    </div>';
    if ( $('#extra [name=' + name + ']').length > 0) {
      $('#extra [name=' + name + ']').parent().remove();
    }
    else {
      $('#extra').append (h);
    }
  }

  function buildForm (params) {
    var h = '<label>ID</label><input data-id="id" size="3" maxlength="20" />';
    if (params.action == 'delete') {
      $('#extra').html(h);
      return;
    }

    CRM.api(params.entity, 'getFields', {}, {
      success:function (data) {
        h = '<i>' + ts('Available fields (click to add/remove):') + '</i>';
        $.each(data.values, function(key, value) {
          var required = value.required ? " required" : "";
          h += "<a data-id='" + key + "' class='type_" + value.type + required + "'>" + value.title + "</a>";
        });
        $('#selector').html(h);
      }
    });
  }

  function generateQuery () {
    var params = {};
    $('#api-explorer input:checkbox:checked, #api-explorer select, #extra input').each(function() {
      var val = $(this).val();
      if (val) {
        params[$(this).data('id')] = val;
      }
    });
    query = CRM.url("civicrm/ajax/rest", params);
    $('#query').val(query);
    if (params.action == 'delete' && $('#selector a').length == 0) {
      buildForm (params);
      return;
    }
    if (params.action == 'create' && $('#selector a').length == 0) {
      buildForm (params);
      return;
    }
  }

  function runQuery() {
    var vars = [],
      hash,
      smarty = '',
      php = "$params = array(<br />&nbsp;&nbsp;'version' => 3,",
      json = "{",
      link = "",
      key,
      value,
      entity,
      action,
      query = $('#query').val(),
      hashes = query.slice(query.indexOf('?') + 1).split('&');
    for(var i = 0; i < hashes.length; i++) {
      hash = hashes[i].split('=');
      key = hash[0];
      value = hash[1];

      switch (key) {
        case 'version':
        case 'debug':
        case 'json':
          break;
        case 'action':
          action = value.toLowerCase();
          $('#action').val(action);
          break;
        case 'entity':
          entity = value.charAt(0).toUpperCase() + value.substr(1);
          $('#entity').val(entity);
          break;
        default:
          if (typeof value == 'undefined') {
            break;
          }
          value = isNaN(value) ? "'" + value + "'" : value;
          smarty += ' ' + key + '=' + value;
          php += "<br />&nbsp;&nbsp'" + key +"' => " + value + ",";
          json += "'" + key + "': " + value + ", ";
      }
    }

    if (!entity) {
      $('#query').val(ts('Choose an entity.'));
      $('#entity').val('');
      window.location.hash = 'explorer';
      return;
    }
    if (!action) {
      $('#query').val(ts('Choose an action.'));
      $('#action').val('');
      window.location.hash = 'explorer';
      return;
    }

    window.location.hash = query;
    $('#result').block();
    $.post(query,function(data) {
      $('#result').unblock().text(data);
    },'text');
    link="<a href='"+query+"' title='open in a new tab' target='_blank'>ajax query</a>&nbsp;";
    var RESTquery = CRM.config.resourceBase + "extern/rest.php?"+ query.substring(restURL.length,query.length) + "&api_key={yoursitekey}&key={yourkey}";
    $("#link").html(link+"|<a href='"+RESTquery+"' title='open in a new tab' target='_blank'>REST query</a>.");


    json = (json.length > 1 ? json.slice (0,-2) : '{') + '}';
    php += "<br />);<br />";
    $('#php').html(php + "$result = civicrm_api('" + entity + "', '" + action + "', $params);");
    $('#jQuery').html ("CRM.api('"+entity+"', '"+action+"', "+json+",<br />&nbsp;&nbsp;{success: function(data) {<br />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;cj.each(data, function(key, value) {// do something  });<br />&nbsp;&nbsp;&nbsp;&nbsp;}<br />&nbsp;&nbsp;}<br />);");

    if (action.substring(0, 3) == "get") {//using smarty only make sense for get actions
      $('#smarty').html("{crmAPI var='result' entity='" + entity + "' action='" + action + "' " + smarty + '}<br />{foreach from=$result.values item=' + entity + '}<br/>&nbsp;&nbsp;&lt;li&gt;{$' + entity +'.some_field}&lt;/li&gt;<br />{/foreach}');
    } else {
      $('#smarty').html("smarty uses only 'get' actions");
    }
    $('#generated').show();
  }

  var query = window.location.hash;
  if (query.substring(1, restURL.length + 1) === restURL) {
    $('#query').val (query.substring(1)).focus();
    runQuery();
  } else {
    window.location.hash="explorer"; //to be sure to display the result under the generated code in the viewport
  }
  $('#entity, #action').change (function() {
    $("#selector, #extra").empty();
    generateQuery();
    runQuery();
  });
  $('#api-explorer input:checkbox').change(function() {
    generateQuery(); runQuery();
  });
  $('#api-explorer').submit(function(e) {
    e.preventDefault();
    runQuery();
  });
  $('#extra').on('keyup', 'input', generateQuery);
  $('#extra').on('click', 'a.remove-extra', function() {
    $(this).parent().remove();
    generateQuery();
  });
  $('#selector').on('click', 'a', function() {
    toggleField($(this).data('id'), this.innerHTML, this.class);
  });
});
