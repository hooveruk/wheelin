// wheel object
var theWheel;
// datatable object
var datatable;
// dialog for the wheel display
var dialog;
// sometimes we need to force table reload
var forceReload = false;

var overlay;

function setOverlay() {
    overlay = $('<div></div>').prependTo('body').attr('id', 'overlay');
}
function removeOverlay() {
    overlay.remove();
}


function setupWheel(data) {
    var colors = [
        '#eae56f',
        '#89f26e',
        '#7de6ef',
        '#e7706f',
        '#eae56f',
        '#89f26e',
        '#7de6ef',
        '#2ba6ef',
        '#3ab6ef',
        '#7dc6ef',
        '#e7706f'
    ];
    var x = 0;
    var segments = [];
    data.forEach(function(item,key) {
        segments.push({
            'fillStyle': colors[x],
            'text': item.name,
            'id': item.id
        });
        x++;
    });
    theWheel = new Winwheel({
        'numSegments'  : x,     // Specify number of segments.
        'outerRadius'  : 130,   // Set outer radius so wheel fits inside the background.
        'textFontSize' : 20,    // Set font size as desired.
        'segments'     : segments,
        'animation' :           // Specify the animation to use.
            {
                'type'     : 'spinToStop',
                'duration' : 15,
                'spins'    : 8,
                'callbackFinished' : drawEmployee,
                'callbackSound'    : playSound,   // Function to call when the tick sound is to be triggered.
                'soundTrigger'     : 'pin'        // Specify pins are to trigger the sound, the other option is 'segment'.
            },
        'pins' :
            {
                'number' : 16   // Number of pins. They space evenly around the wheel.
            }
    });


}


// -----------------------------------------------------------------
// This function is called when the segment under the prize pointer changes
// we can play a small tick sound here like you would expect on real prizewheels.
// -----------------------------------------------------------------
var audio = new Audio('/wheel/tick.mp3');  // Create audio object and load tick.mp3 file.

function playSound()
{
    // Stop and rewind the sound if it already happens to be playing.
    audio.pause();
    audio.currentTime = 0;

    // Play the sound.
    playPromise = audio.play();

}

// -------------------------------------------------------
// Called when the spin animation has finished by the callback feature of the wheel because I specified callback in the parameters
// note the indicated segment is passed in as a parmeter as 99% of the time you will want to know this to inform the user of their prize.
// -------------------------------------------------------
function drawEmployee(indicatedSegment)
{
    // Do basic alert of the segment text.
    // You would probably want to do something more interesting with this information.

    var item = datatable.row('.selected').data();
    item[2] = indicatedSegment.text;
    item[3] = indicatedSegment.id;
    $.ajax({url: '/api/schedule_employee/'+item[0]+'/'+item[1]+'/'+item[3]})
        .then( function(result) {
            if (forceReload) {
                forceReload = false;
                fetchData();
            } else {
                datatable.row('.selected').data(item);
            }
        }, handleError);
    removeOverlay();
    dialog.dialog("close");
}

// =======================================================================================================================
// Code below for the power controls etc which is entirely optional. You can spin the wheel simply by
// calling theWheel.startAnimation();
// =======================================================================================================================
var wheelPower    = 0;
var wheelSpinning = false;

// -------------------------------------------------------
// Spin the wheeeeeel
// -------------------------------------------------------
function startSpin(data)
{
    setOverlay();
    dialog.dialog("open");
    resetWheel(data);

    // Ensure that spinning can't be clicked again while already running.
    if (wheelSpinning == false)
    {
        // Based on the power level selected adjust the number of spins for the wheel, the more times is has
        // to rotate with the duration of the animation the quicker the wheel spins.
        if (wheelPower == 1)
        {
            theWheel.animation.spins = 3;
        }
        else if (wheelPower == 2)
        {
            theWheel.animation.spins = 8;
        }
        else if (wheelPower == 3)
        {
            theWheel.animation.spins = 15;
        }

        // Disable the spin button so can't click again while wheel is spinning.

        // Begin the spin animation by calling startAnimation on the wheel object.
        theWheel.startAnimation();

        // Set to true so that power can't be changed and spin button re-enabled during
        // the current animation. The user will have to reset before spinning again.
        wheelSpinning = true;
    }
}

// -------------------------------------------------------
// Reset wheel to new values
// -------------------------------------------------------
function resetWheel(data)
{
    setupWheel(data);
    theWheel.stopAnimation(false);  // Stop the animation, false as param so does not call callback function.
    theWheel.rotationAngle = 0;
//                        theWheel.deleteSegment(1);
    theWheel.draw();                // Call draw to render changes to the wheel.
    wheelSpinning = false;          // Reset to false to power buttons and spin can be clicked again.
}


$(document).ready(function() {
    datatable = $('#datatable').DataTable();
    dialog = $( "#dialog" ).dialog({
        autoOpen: false,
        dialogClose: 'no-close',
        height: 400,
        width: 350,
        title: "Do you feel lucky today?"
    });

    // assigning datepicker to date field
    $( "#selecteddate" ).datepicker({ dateFormat: 'yy-mm-dd' });

    // we want datatable row to be selectable
    $('#datatable tbody').on( 'click', 'tr', function () {
        if ( $(this).hasClass('selected') ) {
            $(this).removeClass('selected');
        }
        else {
            datatable.$('tr.selected').removeClass('selected');
            $(this).addClass('selected');
        }
    } );

    // clearing schedule for a shift
    $('#clearshift').click( function () {
        var item = datatable.row('.selected').data();
        if (item[3] > 0) {
            $.ajax({
                url:'/api/un_schedule_employee/'+item[0]+'/'+item[1]+'/'+item[3]
            }).then(function(result) {
                console.log(result);
                $.toast("Shift cleared, you can draw again");
                item[3] = '';
                item[2] = '';
                datatable.row('.selected').data(item);
            }, handleError);
        } else {
            $.toast('You need to select a row with reserved shift first...');
        }
    });

    // for lazy people, let server do all the work, still random
    $('#autodraw').click( function () {
        var date = $('#selecteddate').val();
        if (date) {
            $.toast("Please wait, server is doing all random draws for you...");
            $.ajax({url: '/api/random_schedule/' + date})
                .then(function(result) {
                    reloadDatatable(result);
                }, handleError);
        } else {
            $.toast("Please select a date using date picker field first for autogenerate function.");
        }
    });

    // for gamblers, click for a lucky shift
    $('#drawshift').click(
        function () {
            var item = datatable.row('.selected').data();
            if (item && item[3] < 1) {
                $.ajax({url: '/api/get_available_employees/' + item[0] + '/' + item[1]})
                    .then(function(result){
                        // force reload
                        if (result.data.force_reload == true) {
                            forceReload = true;
                            $.toast({
                                text: [
                                    "Unfortunately, for this shift we found no compatible Employee.",
                                    "After a long period of thinking what to do we consulted the great Wizzard",
                                    "And he magically released one of the shifts you previously generated",
                                    "Thus, after you 'randomly' select this guy, go and seek for an empty shift that was just created!"
                                ],
                                heading: 'Important information',
                                icon: 'info', // Type of toast icon
                                showHideTransition: 'fade',
                                allowToastClose: true,
                                hideAfter: false,
                                stack: 3,
                                position: 'bottom-left',
                                textAlign: 'left',
                                loader: true,
                                loaderBg: '#9EC600'
                            });
                        }
                        startSpin(result.data.employees);
                    }, handleError);
            } else {
                $.toast('You need to select an empty shift from the list first....');
            }
        });

});

function reloadDatatable(result) {
    $.toast('Updating schedule data...');
    datatable.clear();
    result.data.schedules.forEach(function (item, index) {
        if (result.data.used[item.schedule_date] == 1) {
            var shift = (item.shift == 1) ? 2 : 1;
            datatable.row.add([item.schedule_date, shift,null, null]).draw();
        }
        datatable.row.add([item.schedule_date, item.shift, item.employee.name, item.employee_id]).draw();
    });
    for (var key in result.data.used) {
        if (result.data.used[key] == 0) {
            datatable.row.add([key, 1, null, null]).draw();
            datatable.row.add([key, 2, null, null]).draw();
        }
    }
}

function fetchData() {
    var date = $('#selecteddate').val();
    $.ajax({url: '/api/schedule/'+date})
        .then(
            function(result) {
                reloadDatatable(result);
            }, handleError);
}

function handleError(result) {
    $.toast("There seem to be a problem with API connection, an error occured... ");
    console.log(result);
}

