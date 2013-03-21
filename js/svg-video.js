jQuery(document).ready(function($) {
  $('a.playVideo').each(function() {
    var link = $(this);
    var dialog = $('<div></div>')
      .load(svgAjax.ajaxurl + '?action=svg-request&video_id=' + link.attr('video'))
      .dialog({
        'dialogClass'   : 'wp-dialog',           
        'modal'         : true,
        'autoOpen'      : false, 
        'closeOnEscape' : true,
        'title'         : link.attr('title'),
        'width'         : 580,
        'height'        : 440,
        'buttons'       : {}
      });
      
      link.click(function() {
	dialog.dialog('open');
        return false;
      });
  });
});
