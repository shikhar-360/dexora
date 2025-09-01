<?php

namespace App\Http\Controllers;

use App\Models\earningLogsModel;
use App\Models\levelEarningLogsModel;
use App\Models\rankingModel;
use App\Models\usersModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use function App\Helpers\is_mobile;
use function App\Helpers\rtxPrice;

class incomeOverviewController extends Controller
{
    public function index(Request $request)
    {
        ini_set('memory_limit', '512M');
        
        $type = $request->input("type");
        $og_start_date = $start_date = $request->input("start_date");
        $og_end_date = $end_date = $request->input("end_date");
        $user_id = $request->session()->get("user_id");

        $user = usersModel::where("id", $user_id)->get()->toArray();

        // Filter earningLogs if start_date and end_date are provided
        $earningLogsQuery = earningLogsModel::where("user_id", $user_id);
        if (!empty($start_date) && !empty($end_date)) {
            $start_date = date("Y-m-d", strtotime($request->input("start_date")));
            $end_date = date("Y-m-d", strtotime($request->input("end_date")));
            $earningLogsQuery->whereRaw("DATE_FORMAT(created_on, '%Y-%m-%d') BETWEEN ? AND ?", [$start_date, $end_date]);
        }
        $earningLogs = $earningLogsQuery->orderBy('id', 'desc')->get()->toArray();

        // Filter levelEarningLogs if start_date and end_date are provided
        $levelEarningLogsQuery = levelEarningLogsModel::selectRaw("SUM(amount) as amount, created_on")->where("user_id", $user_id);
        if (!empty($start_date) && !empty($end_date)) {
            $start_date = date("Y-m-d", strtotime($request->input("start_date")));
            $end_date = date("Y-m-d", strtotime($request->input("end_date")));
            $levelEarningLogsQuery->whereRaw("DATE_FORMAT(created_on, '%Y-%m-%d') BETWEEN ? AND ?", [$start_date, $end_date]);
        }
        $levelEarningLogs = $levelEarningLogsQuery->groupBy(DB::raw("DATE_FORMAT(created_on, '%Y-%m-%d %H')"))->orderBy('id', 'desc')->get()->toArray();

        $ranking = rankingModel::get()->toArray();

        $rankArray = array();
        $incomeRankArray = array();

        foreach ($ranking as $key => $value) {
            $rankArray[$value['id']] = $value['name'];
            $incomeRankArray[$value['income']] = $value['name'];
        }

        $res['status_code'] = 1;
        $res['message'] = "Success";
        $res['user'] = $user['0'];
        $res['earningLogs'] = $earningLogs;
        $res['levelEarningLogs'] = $levelEarningLogs;
        $res['start_date'] = $og_start_date;
        $res['end_date'] = $og_end_date;
        $res['ranks'] = $rankArray;
        $res['incomeRanks'] = $incomeRankArray;

        $dailyPoolWinners = earningLogsModel::selectRaw("IFNULL(SUM(amount), 0) as dailyPool")->where('tag', 'DAILY-POOL')->where('user_id', '=', $user_id)->get()->toArray();

        $monthlyPoolWinners = earningLogsModel::selectRaw("IFNULL(SUM(amount), 0) as monthlyPool")->where('tag', 'MONTHLY-POOL')->where('user_id', '=', $user_id)->get()->toArray();

        $res['dailyPoolWinners'] = $dailyPoolWinners['0']['dailyPool'];
        $res['monthlyPoolWinners'] = $monthlyPoolWinners['0']['monthlyPool'];
        $res['rtxPrice'] = rtxPrice();

        return is_mobile($type, "pages.income_overview", $res, "view");
    }

    public function levelIndex(Request $request)
    {
        ini_set('memory_limit', '512M');
        
        $type = $request->input("type");
        $og_start_date = $start_date = $request->input("start_date");
        $og_end_date = $end_date = $request->input("end_date");
        $user_id = $request->session()->get("user_id");

        // Fetch user details
        $user = usersModel::where("id", $user_id)->first();  // Use first() instead of get()->toArray() for a single user

        // Initialize levelEarningLogs query
        $levelEarningLogsQuery = levelEarningLogsModel::where("user_id", $user_id);

        // Apply date filter if both start_date and end_date are provided
        if (!empty($start_date) && !empty($end_date)) {
            $start_date = date("Y-m-d", strtotime($request->input("start_date")));
            $end_date = date("Y-m-d", strtotime($request->input("end_date")));
            $levelEarningLogsQuery->whereRaw("DATE_FORMAT(created_on, '%Y-%m-%d') BETWEEN ? AND ?", [$start_date, $end_date]);
        }

        // Set the number of records per page (use $perPage from request or default to 10)
        $perPage = $request->input('per_page', 100);

        // Paginate the results instead of get()
        $levelEarningLogs = $levelEarningLogsQuery->orderBy('id', 'desc')->paginate($perPage);

        // Prepare the response
        $res['status_code'] = 1;
        $res['message'] = "Success";
        $res['user'] = $user;
        $res['levelEarningLogs'] = $levelEarningLogs;
        $res['start_date'] = $og_start_date;
        $res['end_date'] = $og_end_date;
        $res['rtxPrice'] = rtxPrice();

        // Return response based on mobile view condition
        return is_mobile($type, "pages.level_income_overview", $res, "view");
    }
}
