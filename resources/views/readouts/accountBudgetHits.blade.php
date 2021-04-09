@extends('layout')
@section('content')
<div class="row">
    <div class="col-1">
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
