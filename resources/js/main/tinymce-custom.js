// Work around for displaying tinymce every time modal is opened
$(document).on('hide.bs.modal', '.modal', function() { 
    if ($(this).find('.tinymce').length) {
        tinymce.remove('.tinymce');
    }
});

window.initTinyMCE = function(settings) {
    var options = $.extend({
        height: 300
    }, settings);

    tinymce.init({
        selector: 'textarea.tinymce',
        height: options.height,
        menubar: false,
        statusbar: false,
        toolbar: 'undo redo | styles | bold italic underline | alignleft aligncenter alignright alignjustify',
        toolbar_mode: 'wrap',
        setup: function(ed)
	    {		            
            ed.on('change', function() {                                                                           
                tinymce.triggerSave();
			});
		}
    });
}

// Prevent bootstrap dialog from blocking focusin - fix with tinymce
document.addEventListener('focusin', function(e) {
    if (e.target.closest(".tox-tinymce-aux, .moxman-window, .tam-assetmanager-root") !== null) {
        e.stopImmediatePropagation();
    }
});