// https://civicrm.org/licensing
(function($, _) {
  function getInstance(item) {
    var name = $(item).attr("name");
    return CKEDITOR.instances[name];
  }

  CRM.wysiwyg.supportsFileUploads =  true;
  CRM.wysiwyg.create =  function(item) {
    //var browseUrl = CRM.config.userFrameworkResourceUrl + "packages/kcfinder/browse.php";
    var browseUrl = CRM.config.userFrameworkResourceURL + "packages/kcfinder/browse.php";
    var uploadUrl = CRM.config.userFrameworkResourceURL + "packages/kcfinder/upload.php";
    var editor = CKEDITOR.replace($(item)[0]);
    if (editor) {
      editor.config.filebrowserBrowseUrl = browseUrl+'?cms=civicrm&type=files';
      editor.config.filebrowserImageBrowseUrl = browseUrl+'?cms=civicrm&type=images';
      editor.config.filebrowserFlashBrowseUrl = browseUrl+'?cms=civicrm&type=flash';
      editor.config.filebrowserUploadUrl = uploadUrl+'?cms=civicrm&type=files';
      editor.config.filebrowserImageUploadUrl = uploadUrl+'?cms=civicrm&type=images';
      editor.config.filebrowserFlashUploadUrl = uploadUrl+'?cms=civicrm&type=flash';
      editor.on('blur', function(){
        $(item).trigger("blur");
      });
    }
  };
  CRM.wysiwyg.destroy = function(item) {
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
  CRM.wysiwyg.val = function(item) {
    var editor = getInstance(item);
    if (editor) {
      return editor.getData();
    } else {
      return $(item).val();
    }
  };
  CRM.wysiwyg.insertText = function(item, text) {
    var editor = getInstance(item);
    if (editor) {
      editor.insertText(text);
    }
  };
  CRM.wysiwyg.insertHTML = function(item, html) {
    var editor = getInstance(item);
    if (editor) {
      editor.insertText(html);
    }
  };
})(CRM.$, CRM._);
