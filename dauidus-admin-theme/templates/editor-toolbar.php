<div id="kaui-sticky-editor-toolbar">
	<div class="inner">
    <button id="kaui-sticky-publish"><?=__( 'Publish' );?></button>
    <button id="kaui-sticky-preview"><?=__( 'Preview' );?></button>
    <button id="kaui-sticky-save"><?=__( 'Save' );?></button>
  </div>
</div>
<script>
(function($){ $(document).ready(function(){
    var handler;
    handler = function(action) {
        $('#' + action).trigger('click');
    };

    var buttons = {
        'preview': {
            'target': 'post-preview',
        },
        'publish': {
            'target': 'publish',
        },
        'save': {
            'target': 'save-post',
        }
    };

    $.each(buttons, function(index, item) {
        var $item = $('#'+item.target);
        if($item.length > 0) {
            var label = ($item[0].tagName.toLowerCase()==="a") ? $item.text() : $item.val();
            $('#kaui-sticky-'+index).text(label).data('target', item.target).on('click', function(e) {
                handler($(this).data('target'));
                e.stopPropagation();
                e.preventDefault();
            });
        } else {
            $('#kaui-sticky-'+index).hide();
        }
    });

    var $toolbar = $('#kaui-sticky-editor-toolbar');
    var $pos = $toolbar.position().top;
    $(window).on('scroll', function() {
        if($(this).scrollTop() > ($pos+220)) {
            $toolbar.addClass('sticky');
        } else {
            $toolbar.removeClass('sticky');
        }
    });
}) })(jQuery);
</script>
