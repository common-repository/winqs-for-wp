/* exported SidebarAdapter */
var SidebarAdapter = ( function( $, document ) {
    'use strict';

    var headlineEditControl,
        headlineValue,
        contentValue,
        sidebarAPI,
        sidebarData,
        contentEditor,
        contentControl,
        showSidebarCheckbox;

    function onSidebarLoaded(API) {
        // 'sidebarAPI' is a pointer to sidebar object we need to save it somewhere
        sidebarAPI = API;

        if (isSidebarSwitchChecked()) {
            initSidebarData();
        }
    }

    function initSidebarData() {
        if(!sidebarAPI) {
            return false;
        }
        sidebarAPI.configure({
            token: sidebarData.token,
            data: {
                story_id: sidebarData.story_id,
                draft_id: sidebarData.draft_id,
                permalink: sidebarData.permalink,
                headline: headlineValue,
                content_html: contentValue
            }
        });
    }

    function onPublishResult(result/*, errors*/) {
        if(result) {
            //sidebar has successfully published story
            onSaveSidebarData();
            $('#publish').trigger( 'click' );
        } else {
            //show errors
        }
    }

    function onPublishButtonPressed(event) {
        if(typeof event.originalEvent === 'object') {
            if(!isSidebarSwitchChecked()) {
                return true;
            }
            // call sidebar to publish a post at qbeats
            onSaveSidebarData();
            if (sidebarAPI) {
                sidebarAPI.publish(onPublishResult, {
                    generate_public_content: true
                });
            }
            return false;
        }
    }

    function onSaveSidebarData () {
        // a draft is going to be saved, gather the data for sidebarAPI
        if (isSidebarSwitchChecked() && sidebarAPI) {
            $('#qb-draft-id').val(sidebarAPI.getDraftId());
            $('#qb-story-id').val(sidebarAPI.getStoryId());
            $('#qb-public-content').val(sidebarAPI.getPublicPart());
        }
    }

    function onTitleChanged(text) {
        if (headlineValue !== text) {
            headlineValue = text;
            if (isSidebarSwitchChecked() && sidebarAPI) {
                sidebarAPI.setProperty('headline', headlineValue);
            }
            checkSidebarSwitch();
        }
    }

    function onContentChanged() {
        var text;
        if ( ! contentEditor || contentEditor.isHidden() ) {
            text = contentControl.val();
        } else {
            text = contentEditor.getContent({format: 'raw'});
        }
        if (contentValue !== text) {
            contentValue = wrap(text);
            if (sidebarAPI && isSidebarSwitchChecked()) {
                sidebarAPI.setProperty('content_html', contentValue);
            }
            checkSidebarSwitch();
        }
    }

    function onSidebarEnableChanged() {
        var sidebarVisible = isSidebarSwitchChecked();
        setSidebarVisible(sidebarVisible);
        if(sidebarVisible) {
            initSidebarData();
        }
    }

    function setSidebarVisible(visible) {
        if (visible) {
            $('.qb-js-sidebar-panel').show();
        } else {
            $('.qb-js-sidebar-panel').hide();
        }
    }

    function setSidebarSwitchDisabled(disabled) {
        showSidebarCheckbox.prop('disabled', disabled);
    }

    function isSidebarSwitchDisabled() {
        return showSidebarCheckbox.prop('disabled');
    }

    function isSidebarSwitchChecked() {
        return showSidebarCheckbox.is(':checked');
    }

    function checkSidebarSwitch() {
        if (isSidebarSwitchDisabled() && contentValue && headlineValue) {
            setSidebarSwitchDisabled(false);
        }
    }

    function wrap(content) {
        return '<qb-protected>' + content + '</qb-protected>';
    }

    function initialize () {
        var publishButton,
            saveDraftButton,
            htmlDocument;

        sidebarData = window.SIDEBAR_DATA = window.SIDEBAR_DATA || {};

        showSidebarCheckbox = $('.qb-js-publish-with-qbeats-enabled');
        if (showSidebarCheckbox) {
            showSidebarCheckbox.on('click', onSidebarEnableChanged);
        }

        // set handlers for main controls
        publishButton = $('#publish');
        if (publishButton) {
            publishButton.on('click', onPublishButtonPressed);
        }

        saveDraftButton = $('#save-post');
        if (saveDraftButton) {
            saveDraftButton.on('click', onSaveSidebarData);
        }

        headlineEditControl = $('#title');
        if (headlineEditControl) {
            headlineEditControl.on('blur', function (){
                onTitleChanged(headlineEditControl.val());
            });
            onTitleChanged(headlineEditControl.val());
        }

        htmlDocument = $(document);
        if (htmlDocument) {
            htmlDocument.on('before-autosave', onSaveSidebarData);
        }
        contentControl = $('#content');
        contentControl.on('input keyup', _.debounce(onContentChanged, 1000));

        htmlDocument.on('tinymce-editor-init', function( event, editor ) {
            if ( editor.id !== 'content' ) {
                return;
            }
            contentEditor = editor;
            contentEditor.on('nodechange keyup', _.debounce(onContentChanged, 1000));
            onContentChanged();
        });

        if (!sidebarData.story_id && !sidebarData.draft_id) {
            setSidebarSwitchDisabled(true);
        }
        onContentChanged();

        onSidebarEnableChanged();
    }

    $(document).ready( initialize );

    return {
        onSidebarLoaded: onSidebarLoaded
    };
}( jQuery, document ));

window.onSidebarLoaded = SidebarAdapter.onSidebarLoaded;