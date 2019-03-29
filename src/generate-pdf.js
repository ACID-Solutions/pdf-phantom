var fs = require('fs'),
    args = require('system').args,
    page = require('webpage').create();

// ============================================================================================
// ARGUMENTS
// ============================================================================================
//
// args[0] => JS file to run with Phantom JS (this file)
// args[1] => PDF File to write
// args[2] => Main contents
// args[3] => Header contents
// args[4] => Footer contents
// args[5] => Orientation
// args[6] => Header height
// args[7] => Footer height
// args[8] => Page format
// args[9] => DPI
// args[10] => Time to wait for generation in milliseconds (allow time for fade ins before taking snapshot)

var paperSize = {
    margin: '0.3cm',
    header: {
        height: args[6],
        contents: phantom.callback(function (pageNum, numPages) {
            var html = '';

            if (fs.isReadable(args[3])) {
                var file_contents = fs.open(args[3], 'r');
                html = file_contents.read();
            }

            return html;
        })
    },
    footer: {
        height: args[7],
        contents: phantom.callback(function (pageNum, numPages) {
            var html = '';

            if (fs.isReadable(args[4])) {
                var file_contents = fs.open(args[4], 'r');
                html = file_contents.read();
            }

            if (html == '') {
                html = '<div style="text-align: center;"><small>' + pageNum + '/' + numPages + '</small></div>';
            } else {
                html = html.replace('__PAGE_NUM__', pageNum);
                html = html.replace('__NUM_PAGES__', numPages);
            }

            return html;
        })
    }
};

if (args[8]) {
    paperSize.format = args[8];
    paperSize.orientation = args[5];
} else {
    var dpi  = args[9],
        dpcm = dpi / 2.54;

    var widthCm, heightCm;

    if (args[5] == 'portrait') {
        widthCm  = 21.0;
        heightCm = 29.7; // A4 portrait
    } else {
        widthCm  = 29.7;
        heightCm = 21.0; // A4 landscape
    }

    page.viewportSize = {
        width  : Math.round(widthCm * dpcm),
        height : Math.round(heightCm * dpcm)
    };

    paperSize.width = page.viewportSize.width + 'px';
    paperSize.height = page.viewportSize.height + 'px';
    page.settings.dpi = dpi;
}

page.paperSize = paperSize;
page.zoomFactor = 1.0;

function onPageReady(status) {
    if (status !== 'success') {
        console.log('Unable to load the file!');
        phantom.exit();
    } else {
        page.render(args[1], 'pdf', 90);
        phantom.exit(0);
    }
}

page.open(args[2], function (status) {
    function checkReadyState() {
        setTimeout(function () {
            var readyState = page.evaluate(function () {
                return document.readyState;
            });

            setTimeout(function () {
                if ("complete" === readyState) {
                    onPageReady(status);
                } else {
                    checkReadyState();
                }
            }, args[10]);
        });
    }

    checkReadyState();
});
