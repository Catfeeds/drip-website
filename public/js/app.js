$(function () {
    'use strict'

    // $('[data-toggle="control-sidebar"]').controlSidebar()
    // $('[data-toggle="push-menu"]').pushMenu()

    var $pushMenu       = $('[data-toggle="push-menu"]').data('lte.pushmenu')
    var $controlSidebar = $('[data-toggle="control-sidebar"]').data('lte.controlsidebar')
    var $layout         = $('body').data('lte.layout')

    $('body').on('expanded.pushMenu collapsed.pushMenu', function() {

        // Add delay to trigger code only after the pushMenu animation completes
        setTimeout(function() {

            // Code ...
            // Example: refresh datatable column widths after collapse/expansion completes
            $.fn.dataTable.tables( {visible: true, api: true} ).columns.adjust();

            // Time depends on your settings
        }, 350);
    } );
});