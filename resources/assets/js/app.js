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

    var downUrl="";

    if (window.navigator.userAgent.match(/MicroMessenger/i)) {
        landing.style.display = "block";
    } else if (navigator.userAgent.match(/(iPhone|iPod|iPad);?/i)) {
        var loadDateTime = new Date();
        window.setTimeout(function() {
                var timeOutDateTime = new Date();
                if (timeOutDateTime - loadDateTime < 5000) {
                    if(downUrl) {
//                        alert(downUrl);
                        window.open(downUrl,"_blank");
                    }
                } else {
//                    window.close();
                }
            },
            25);

        var url = "<?php echo $channel['url_schema'];?>www.tuo3.com?roomId=<?php echo $_GET['roomId'];?>";
        window.location.href = url

    } else if (navigator.userAgent.match(/android/i)) {

        window.location.href = "drip://";
        window.setTimeout(function () {
            if(downUrl) {
                window.open(downUrl,"_blank");
            }
        }, 2000);
    }



});