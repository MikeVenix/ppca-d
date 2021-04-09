@extends('layout')
@section('content')

    <script>
        $( function() {
            $( "#datepicker" ).datepicker();
        } );

        // var $last = "" ;

        // function ajax() {
        //     // console.log($last);
        //     $.ajax({
        //         type: "post",
        //         data: {
        //             last: $last,
        //             _token:'{{ csrf_token() }}'
        //         },
        //         url:'/tableGet',
        //         dataType: 'json',
        //         success: function(data) {
        //             console.log(data);
        //             var returnData = data.data;
        //             var bodyData = '';
        //             var $last = data.last;
        //             $.each(returnData, function(index,row) {
        //                 var data = JSON.parse(row.data);
        //                 bodyData+="<tr>";
        //                 bodyData+="<td>"+row.time+"</td><td>"+ data.accountName+"</td><td>"+data.campaignName+"</td><td>"+ "--" +"</td><td>£"+data.budget+"</td><td>£"+data.spend+"</td><td>"+ "--" +"</td><td>";
        //                 bodyData+="</tr>";
        //             })
        //             $(bodyData).insertAfter('#tableHead')
        //         },
        //         error: function(xhr, data) {
        //             console.log(xhr.responseText);
        //             console.log(data);
        //         }
        //     });
        // };
        // ajax;
        // setInterval(ajax, 20000);
    </script>
        {{-- <p>Between : <input type="text" placeholder="Select date" name="dateFrom" id="datepicker"> and <input type="text" placeholder="Select date" name="dateTo" id="datepicker"></p> --}}
    <div class="row">
        <div class="col-1">
            <div class="row">
                <div class="dropdown mt-3">
                    <button class="btn btn-secondary dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        Select Dates
                    </button>
                    <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                    <a class="dropdown-item" href="/">Today</a>
                    <a class="dropdown-item" href="/date/yesterday">Yesterday</a>
                    <a class="dropdown-item" href="/date/lastseven">Last 7 Days</a>
                    <a class="dropdown-item" href="/date/thismonth">This Month</a>
                    <a class="dropdown-item" href="/date/lastmonth">Last Month</a>
                    {{-- <a class="dropdown-item" href="#">Custom</a> --}}
                    </div>
                </div>
            </div>
            {{-- <div class="row">
                <form action="/date/custom" method="post">
                    <input type="text" id="datepicker">
                    <input type="text" id="datepicker">
                    <input type="submit">
                </form>
            </div> --}}
        </div>
        <div class="col-10">
            <table class="mt-3 table-striped table-responsive">
                <tr class="w-100" id="tableHead">
                    <th class="pr-5 pl-5 pt-3"><h4>Time</h4></th>
                    <th class="pr-5 pl-5 pt-3"><h4>Account Name</h4></th>
                    <th class="pr-5 pl-5 pt-3"><h4>Campaign Name</h4></th>
                    <th class="pr-5 pl-5 pt-3"><h4>Ad Schedule</h4></th>
                    <th class="pr-2 pl-2 pt-3"><h4>Budget</h4></th>
                    <th class="pr-2 pl-2 pt-3"><h4>Spend</h4></th>
                    <th class="pr-2 pl-2 pt-3"><h4>Search Imp Share</h4></th>
                </tr>

                @foreach ($rows as $row)
                    <?php $object = json_decode($row->data) ?>
                    <tr>
                        <td>{{ $row->time }}</td>
                        <td>
                        <a href="/account/{{ $row->accountId }}"> {{ $object->accountName }} </a>
                        </td>
                        <td>{{ $object->campaignName }}</td>
                        <td>@php
                            try {
                                echo $object->scheduleStart;
                                echo " to ";
                                echo $object->scheduleEnd;
                            } catch (exception $e) {
                                echo "--";
                            }
                        @endphp</td>
                        <td>£{{ $object->budget }}
                            @php
                                try {
                                    $calc = round($object->suggestedBudget);
                                    echo "(£$calc)";
                                } catch (exception $e) {
                                    echo "--";
                                }
                            @endphp
                        </td>
                        <td>£{{ round($object->spend, 2) }}</td>
                        <td><p>{{ $row->searchImp }} Search and {{ $row->absoluteImp }} Absolute<p></td>
                    </tr>
                @endforeach

            </table>
        </div>
        <div class="col-1">
        </div>
    </div>
@endsection
