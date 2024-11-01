function disableCopy(element) {
    if(typeof element.onselectstart !== 'undefined') {
        element.onselectstart = function () {
            return false;
        };
    }
    else if(typeof element.style.MozUserSelect !== 'undefined') {
        element.style.MozUserSelect = 'none';
    }
    else {
        element.onmousedown = function () {
            return false;
        };
    }
    element.style.cursor='default';

    element.oncontextmenu = function(e) {
        var t = e || window.event;
        var n = t.target || t.srcElement;
        if(n.nodeName !== 'A') {
            return false;
        }
    };
    element.ondragstart = function(){return false;};
}

function disablePrint() {
    jQuery('body').prepend('<h1 class="qb-print-notification">The author does not allow printing for this story</h1>');
}

jQuery(document).ready( function() {
    var drm = {};
    drm = DRM_DATA || {}; // jshint ignore:line

    if(!drm.allow_copy) {
        disableCopy(document.body);
    }

    if(!drm.allow_print) {
        disablePrint();
    }
});