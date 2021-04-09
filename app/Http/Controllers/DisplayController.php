<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class DisplayController extends Controller
{
    public function index()
    {
        $rows = DB::table('actions')->whereDate('time', DB::raw('CURDATE()'))->where('type', '=', 'hitBudget')->orderBy('time', 'desc')->get();

        $last = DB::table('actions')->whereDate('time', DB::raw('CURDATE()'))->where('type', '=', 'hitBudget')->orderBy('time', 'desc')->value("id");

        foreach($rows as $row) {
            $date = Carbon::parse($row->time);
            $row->time = $date->toDayDateTimeString();
        }

        return view('readouts.budgetHits')->with('rows', $rows)->with('last', $last);
    }

    public function addOn(Request $request)
    {
        $id = $request->last;
        $id = (int)$id;

        $rows = DB::table('actions')->where([
            ['type', '=', 'hitBudget'],
            ['id', '>', $id]])
            ->get();

        $row = $rows->last();
        $last = $row->id;

        return json_encode(array('data'=>$rows, 'last'=>$last));
    }

    public function accountList(Request $request)
    {
        $rows = DB::table('actions')->where([
            ['type', '=', 'hitBudget'],
            ['accountId', '=', $request->id],
        ])->orderBy('time', 'desc')->get();

        return view('readouts.accountBudgetHits')->with('rows', $rows);
    }

    public function dates(Request $request)
    {
        $date = $request->date;

        switch ($date){
            case "yesterday":
                $date = Carbon::yesterday();
                $rows = DB::table('actions')->whereDate('time', $date)->where('type', '=', 'hitBudget')->orderBy('time', 'desc')->get();
                foreach($rows as $row) {
                    $date = Carbon::parse($row->time);
                    $row->time = $date->toDayDateTimeString();
                }
                return view('readouts.budgetHits')->with('rows', $rows);
            break;
            case "lastseven":
                $end = Carbon::today();
                $start = Carbon::now()->subDays(7);
                $rows = DB::table('actions')->whereBetween('time', [$start, $end])->where('type', '=', 'hitBudget')->orderBy('time', 'desc')->get();
                foreach($rows as $row) {
                    $date = Carbon::parse($row->time);
                    $row->time = $date->toDayDateTimeString();
                }
                return view('readouts.budgetHits')->with('rows', $rows);
            break;
            case "thismonth":
                $today = Carbon::now();

                $end = $today->addMonths(1)->firstOfMonth()->subMinute()->format('Y-m-d\ H:i:s');
                $start = $today->firstOfMonth()->format('Y-m-d\ H:i:s');
                $rows = DB::table('actions')->whereBetween('time', [$start, $end])->where('type', '=', 'hitBudget')->orderBy('time', 'desc')->get();
                foreach($rows as $row) {
                    $date = Carbon::parse($row->time);
                    $row->time = $date->toDayDateTimeString();
                }
                return view('readouts.budgetHits')->with('rows', $rows);
            break;
            case "lastmonth":
                $today = Carbon::now()->subMonth();

                $end = Carbon::now()->firstOfMonth()->subMinute()->format('Y-m-d\ H:i:s');
                $start = $today->firstOfMonth()->format('Y-m-d\ H:i:s');
                $rows = DB::table('actions')->whereBetween('time', [$start, $end])->where('type', '=', 'hitBudget')->orderBy('time', 'desc')->get();
                foreach($rows as $row) {
                    $date = Carbon::parse($row->time);
                    $row->time = $date->toDayDateTimeString();
                }
                return view('readouts.budgetHits')->with('rows', $rows);
            break;
        }
    }

    public function dateRange(Request $request)
    {
        $from = $request->dateFrom;
        $to = $request->dateTo;


    }
}
