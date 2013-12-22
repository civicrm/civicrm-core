/**
* Copyright (C) 2012 Xavier Dutoit
* Licensed to CiviCRM under the Academic Free License version 3.0.
*
*
* This offers two features:
* - crmEditable() edit in place of a single field
*  (mostly a wrapper that binds jeditable features with the ajax api and replies on crm-entity crmf-{field} html conventions)
*  if you want to add an edit in place on a template:
*  - add a class crm-entity and id {EntityName}-{Entityid} higher in the dom
*  - add a class crm-editable and crmf-{FieldName} around the field (you can add a span if needed)
*  - add data-action=create if you need to specify the api action to call (default setvalue)
*  crmf- stands for crm field
* - crmForm()
*   this embed a civicrm form and make it in place (load+ajaxForm) 
*   to make it easier to customize the form (eg. hide a button...) it triggers a 'load' event on the form. you can then catch the load on your code (using the $('#id_of_the_form').on(function(){//do something
*/

(function($) {

  $.fn.crmEditableEntity = function() {
    var
      el = this[0],
      ret = {},
      $row = this.first().closest('.crm-entity');
    ret.entity = $row.data('entity');
    ret.id = $row.data('id');
    if (!ret.entity || !ret.id) {
      ret.entity = $row[0].id.split('-')[0];
      ret.id = $row[0].id.split('-')[1];
    }
    if (!ret.entity || !ret.id) {
      return false;
    }
    $('.crm-editable', $row).each(function() {
      var fieldName = $(this).data('field') || this.className.match(/crmf-(\S*)/)[1];
      if (fieldName) {
        ret[fieldName] = $(this).text();
        if (this === el) {
          ret.field = fieldName;
        }
      }
    });
    return ret;
  };

    $.fn.crmEditable = function (options) {
      var checkable = function () {
        $(this).change (function() {
          var info = $(this).crmEditableEntity();
          if (!info.field) {
            return false;
          }
          var checked = $(this).is(':checked');
          var params = {
            sequential: 1,
            id: info.id,
            field: info.field,
            value: checked ? 1 : 0
          };
          CRM.api(info.entity, 'setvalue', params, {
            context: this,
            error: function (data) {
              editableSettings.error.call(this, info.entity, info.field, checked, data);
            },
            success: function (data) {
              editableSettings.success.call(this, info.entity, info.field, checked, data);
            }
          });
        });
      };

      var defaults = {
        form:{},
        callBack:function(data){
          if (data.is_error) {
            editableSettings.error.call (this,data);
          } else {
             return editableSettings.success.call (this,data);
          }
        },
        error: function(entity,field,value,data) {
          $(this).crmError(data.error_message, ts('Error'));
          $(this).removeClass('crm-editable-saving');
        },
        success: function(entity,field,value,data) {
          var $i = $(this);
          CRM.alert('', ts('Saved'), 'success');
          $i.removeClass ('crm-editable-saving crm-error');
          $i.html(value);
        }
      }

      var editableSettings = $.extend({}, defaults, options);
      return this.each(function() {
        var $i = $(this);
        var fieldName = "";
      
        if (this.nodeName == "INPUT" && this.type=="checkbox") {
          checkable.call(this,this);
          return;
        }

        if (this.nodeName == 'A') {
          if (this.className.indexOf('crmf-') == -1) { // it isn't a jeditable field
            var formSettings= $.extend({}, editableSettings.form ,
              {source: $i.attr('href')
              ,success: function (result) {
                if ($i.hasClass('crm-dialog')) {
                  $('.ui-dialog').dialog('close').remove();
                } else 
                  $i.next().slideUp().remove();
                $i.trigger('success',result);
              }
              });
            var id= $i.closest('.crm-entity').attr('id');
            if (id) {
              var e=id.match(/(\S*)-(\S*)/);
               if (!e)
                 console && console.log && console.log("Couldn't get the entity id. You need to set class='crm-entity' id='{entityName}-{id}'");
              formSettings.entity=e[1];
              formSettings.id=e[2];
            }
            if ($i.hasClass('crm-dialog')) {
              $i.click (function () {
                var $n=$('<div>Loading</div>').appendTo('body');
                $n.dialog ({modal:true,width:500});
                $n.crmForm (formSettings);
                return false;
              });
            } else {
              $i.click (function () {
                var $n=$i.next();
                if (!$n.hasClass('crm-target')) {
                  $n=$i.after('<div class="crm-target"></div>').next();
                } else {
                  $n.slideToggle();
                  return false;
                };
                $n.crmForm (formSettings);
                return false;
              });
            }
            return;
          }
        }


        var settings = {
          tooltip   : 'Click to edit...',
          placeholder  : '<span class="crm-editable-placeholder">Click to edit</span>',
          data: function(value, settings) {
            return value.replace(/<(?:.|\n)*?>/gm, '');
          }
        };
        if ($i.data('placeholder')) {
          settings.placeholder = $i.data('placeholder');
        } else {
          settings.placeholder  = '<span class="crm-editable-placeholder">Click to edit</span>';
        }
        if ($i.data('tooltip')) {
          settings.placeholder = $i.data('tooltip')
        } else {
          settings.tooltip   = 'Click to edit...';
        }
        if ($i.data('type')) {
          settings.type = $i.data('type');
          settings.onblur = 'submit';
        }
        if ($i.data('options')){
          settings.data = $i.data('options');
        }
        if(settings.type == 'textarea'){
          $i.addClass ('crm-editable-textarea-enabled');
        }
        else{
          $i.addClass ('crm-editable-enabled');
        }

        $i.editable(function(value,settings) {
          $i.addClass ('crm-editable-saving');
          var
            info = $i.crmEditableEntity(),
            params= {},
            action = $i.data('action') || 'setvalue';
          if (!info.field) {
            return false;
          }
          if (info.id && info.id !== 'new') {
            params.id = info.id;
          }
          if (action === 'setvalue') {
            params.field = info.field;
            params.value = value;
          }
          else {
            params[info.field] = value;
          }
          CRM.api(info.entity, action, params, {
              context: this,
              error: function (data) {
                editableSettings.error.call(this, info.entity, info.field, value, data);
              },
              success: function (data) {
                if ($i.data('options')){
                  value = $i.data('options')[value];
                }
                editableSettings.success.call(this, info.entity, info.field, value, data);
              }
            });
           },settings);
    });
  }

  $.fn.crmForm = function (options ) {
    var settings = $.extend( {
      'title':'',
      'entity':'',
      'action':'get',
      'id':0,
      'sequential':1,
      'dialog': false,
      'load' : function (target){},
      'success' : function (result) {
        $(this).html(ts('Saved'));
       }
    }, options);


    return this.each(function() {
      var formLoaded = function (target) {
        var $this =$(target);
        var destination="<input type='hidden' name='civicrmDestination' value='"+CRM.url('civicrm/ajax/rest',{
          'sequential':settings.sequential,
          'json':'html',
          'entity':settings.entity,
          'action':settings.action,
          'id':settings.id
          })+"' />";
        $this.find('form').ajaxForm({
          beforeSubmit :function () {
            $this.html("<div class='crm-editable-saving'>Saving...</div>");
            return true;
          },
          success:function(response) {
            if (response.indexOf('crm-error') >= 0) { // we got an error, re-display the page
              $this.html(response);
              formLoaded(target);
            } else {
              if (response[0] == '{')
                settings.success($.parseJSON (response));
              else
                settings.success(response);
            }
          }
        }).append('<input type="hidden" name="snippet" value="1"/>'+destination).trigger('load');

        settings.load(target);
      };

      var $this = $(this);
       if (settings.source && settings.source.indexOf('snippet') == -1) {
         if (settings.source.indexOf('?') >= 0)
           settings.source = settings.source + "&snippet=1";
         else
           settings.source = settings.source + "?snippet=1";
       }


       $this.html ("Loading...");
       if (settings.dialog)
         $this.dialog({width:'auto',minWidth:600});
       $this.load (settings.source ,function (){formLoaded(this)});

    });
  };

})(jQuery);
