/* jshint ignore:start */
tinymce.init({
    selector: 'textarea',
    valid_elements: 'qb-protected',
    custom_valid_elements: 'qb-protected'
});
/* jshint ignore:end */

tinymce.PluginManager.add('qbeats_editor_buttons', function (editor) {
    editor.addButton('qb-protected', {
        text: 'qb-protected',
        icon: false,
        onclick: function() {
            var prefix = '<qb-protected>',
                postfix = '</qb-protected>',
                selection = editor.selection.getContent();
            if (selection.length) {
                editor.selection.setContent(prefix + selection + postfix);
            }
        }
    });
});
