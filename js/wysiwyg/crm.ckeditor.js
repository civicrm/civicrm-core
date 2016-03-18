// https://civicrm.org/licensing
(function($, _) {
  var scriptLoaded = false;

  function getInstance(item) {
    var name = $(item).attr("name"),
      id = $(item).attr("id");
    if (name && window.CKEDITOR && CKEDITOR.instances[name]) {
      return CKEDITOR.instances[name];
    }
    if (id && window.CKEDITOR && CKEDITOR.instances[id]) {
      return CKEDITOR.instances[id];
    }
  }

  function loadScript(url) {
    var deferred = $.Deferred(),
      script = document.createElement('script');
    script.onload = function() {deferred.resolve();};
    script.src = url;
    document.getElementsByTagName("head")[0].appendChild(script);
    return deferred;
  }

  CRM.wysiwyg.supportsFileUploads =  true;

  CRM.wysiwyg.create = function(item) {
    var deferred = $.Deferred();

    function onReady() {
      var debounce,
        editor = this;

      editor.on('focus', function() {
        $(item).trigger('focus');
      });
      editor.on('blur', function() {
        editor.updateElement();
        $(item).trigger("blur");
        $(item).trigger("change");
      });
      editor.on('insertText', function() {
        $(item).trigger("keypress");
      });
      _.each(['key', 'pasteState'], function(evName) {
        editor.on(evName, function(evt) {
          if (debounce) clearTimeout(debounce);
          debounce = setTimeout(function() {
            editor.updateElement();
            $(item).trigger("change");
          }, 50);
        });
      });
      editor.on('pasteState', function() {
        $(item).trigger("paste");
      });
      // Hide CiviCRM menubar when editor is fullscreen
      editor.on('maximize', function (e) {
        $('#civicrm-menu').toggle(e.data === 2);
      });
      deferred.resolve();
    }
    
    function initialize() {
      var
        browseUrl = CRM.config.resourceBase + "packages/kcfinder/browse.php?cms=civicrm",
        uploadUrl = CRM.config.resourceBase + "packages/kcfinder/upload.php?cms=civicrm";

      $(item).addClass('crm-wysiwyg-enabled');

      var isFullPage = $(item).hasClass('crm-wysiwyg-fullpage');

      CKEDITOR.replace($(item)[0], {
        filebrowserBrowseUrl: browseUrl + '&type=files',
        filebrowserImageBrowseUrl: browseUrl + '&type=images',
        filebrowserFlashBrowseUrl: browseUrl + '&type=flash',
        filebrowserUploadUrl: uploadUrl + '&type=files',
        filebrowserImageUploadUrl: uploadUrl + '&type=images',
        filebrowserFlashUploadUrl: uploadUrl + '&type=flash',
        allowedContent: true, // For CiviMail!
        fullPage: isFullPage,
        customConfig: CRM.config.CKEditorCustomConfig,
        on: {
          instanceReady: onReady
        }
      });
    }

    if ($(item).hasClass('crm-wysiwyg-enabled')) {
      deferred.resolve();
    }
    else if ($(item).length) {
      // Lazy-load ckeditor.js
      if (window.CKEDITOR) {
        initialize();
      } else {
        if (scriptLoaded === false) {
          scriptLoaded = loadScript(CRM.config.resourceBase + 'bower_components/ckeditor/ckeditor.js');
        }
        scriptLoaded.done(initialize);
      }
    } else {
      deferred.reject();
    }
    return deferred;
  };

  CRM.wysiwyg.destroy = function(item) {
    $(item).removeClass('crm-wysiwyg-enabled');
    var editor = getInstance(item);
    if (editor) {
      editor.destroy();
    }
  };

  CRM.wysiwyg.updateElement = function(item) {
    var editor = getInstance(item);
    if (editor) {
      editor.updateElement();
    }
  };

  CRM.wysiwyg.getVal = function(item) {
    var editor = getInstance(item);
    if (editor) {
      return editor.getData();
    } else {
      return $(item).val();
    }
  };

  CRM.wysiwyg.setVal = function(item, val) {
    var editor = getInstance(item);
    if (editor) {
      return editor.setData(val);
    } else {
      return $(item).val(val);
    }
  };

  CRM.wysiwyg.insert = function(item, text) {
    var editor = getInstance(item);
    if (editor) {
      editor.insertText(text);
    } else {
      CRM.wysiwyg._insertIntoTextarea(item, text);
    }
  };

  CRM.wysiwyg.focus = function(item) {
    var editor = getInstance(item);
    if (editor) {
      editor.focus();
    } else {
      $(item).focus();
    }
  };

})(CRM.$, CRM._);
