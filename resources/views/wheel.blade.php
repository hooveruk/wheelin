<!doctype html>
<html lang="{{ app()->getLocale() }}">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Laravel</title>

        <script src="https://cdn.jsdelivr.net/npm/vue"></script>
        <script src="https://code.jquery.com/jquery-3.3.1.min.js"></script>
        <script src="http://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>
        <script src="https://cdn.datatables.net/1.10.16/js/jquery.dataTables.min.js"></script>
        <script src="https://cdn.datatables.net/1.10.16/js/dataTables.jqueryui.js"></script>
        <script src="{{asset('toast/jquery.toast.min.js')}}"></script>
        <script src="{{asset('js/wheel.js')}}"></script>


        <link rel="stylesheet" href="https://cdn.datatables.net/1.10.16/css/dataTables.jqueryui.min.css"/>


        <!-- Fonts -->
        <link href="https://fonts.googleapis.com/css?family=Raleway:100,600" rel="stylesheet" type="text/css">
        <link rel="stylesheet" href="{{asset('wheel/wheel.css')}}" type="text/css" />
        <link rel="stylesheet" href="{{asset('toast/jquery.toast.min.css')}}" type="text/css" />
        <link rel="stylesheet" href="http://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css" type="text/css" />

        <script type="text/javascript" src="{{asset('wheel/Winwheel.min.js')}}"></script>
        <script src="http://cdnjs.cloudflare.com/ajax/libs/gsap/latest/TweenMax.min.js"></script>


        <!-- Styles -->
        <style>
            html, body {
                background-color: #fff;
                color: #636b6f;
                font-family: 'Raleway', sans-serif;
                font-weight: 100;
                height: 100vh;
                margin: 0;
            }

            .full-height {
                height: 100vh;
            }

            .flex-center {
                align-items: center;
                justify-content: center;
            }

            .position-ref {
                position: relative;
            }

            .top-right {
                position: absolute;
                right: 10px;
                top: 18px;
            }

            .content {
                text-align: center;
            }

            .title {
                font-size: 84px;
            }

            .links > a {
                color: #636b6f;
                padding: 0 25px;
                font-size: 12px;
                font-weight: 600;
                letter-spacing: .1rem;
                text-decoration: none;
                text-transform: uppercase;
            }

            .m-b-md {
                margin-bottom: 30px;
            }
        </style>
    </head>
    <body>
        <div class="flex-center position-ref full-height">
            <div class="content">
                <div class="title m-b-md">
                    Wheel of fate
                </div>

                <div>
                    <p>
                        <script>
                            $( function() {
                            } );
                        </script>
                        <input placeholder="click to select date" type='text' id="selecteddate">
                        <button name="Fetch Schedule" onclick="fetchData();">Fetch Schedule</button>
                        <button id="autodraw">Auto Draw (server side)</button>
                        <button id="clearshift">Clear Shift</button>
                        <button id="drawshift">Draw Shift</button>


                    </p>
                    <br />
                    <table id="datatable" class="display" cellspacing="0" width="100%">
                        <thead>
                        <tr>
                            <th>Date</th>
                            <th>Shift</th>
                            <th>Employee</th>
                            <th>Id</th>
                        </tr>
                        </thead>
                        <tfoot>
                        <tr>
                            <th>Date</th>
                            <th>Shift</th>
                            <th>Employee</th>
                            <th>Id</th>
                        </tr>
                        </tfoot>
                        <tbody>
                        </tbody>
                    </table>
                </div>
                <script>

                </script>

            <div id="table-container">
                Select a date by clicking on the input field. Or use fetch for current date.
            </div>

                <script>

                </script>

        </div>
            <div align="center" id="dialog" style="display: none;">
                <table cellpadding="0" cellspacing="0" border="0">
                    <tr>
                        <td width="300" height="300" class="" align="center" valign="center">
                            <canvas id="canvas" width="300" height="300">
                                <p style="{color: white}" align="center">Sorry, your browser doesn't support canvas. Please try another.</p>
                            </canvas>
                        </td>
                    </tr>
                </table>
            </div>
            <div align="center" id="no-shifts" style="display: none;">
                <table cellpadding="0" cellspacing="0" border="0">
                    <tr>
                        <td width="250" height="250" class="" align="center" valign="center">
                           Shift is already taken or no available employees
                        </td>
                    </tr>
                </table>
            </div>
    </body>
</html>
