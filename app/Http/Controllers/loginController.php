<?php

namespace App\Http\Controllers;

use App\Models\levelRoiModel;
use App\Models\packageTransaction;
use App\Models\rankingModel;
use App\Models\userPlansModel;
use App\Models\usersModel;
use App\Models\earningLogsModel;
use App\Models\user_stablebond_details;
use App\Models\rewardBonusModel;
use App\Models\withdrawModel;
use App\Models\loginLogsModel;
use App\Models\myTeamModel;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

use function App\Helpers\is_mobile;
use function App\Helpers\getBalance;
use function App\Helpers\getIncome;
use function App\Helpers\getTeamRoi;
use function App\Helpers\rtxPrice;
use function App\Helpers\getTreasuryBalance;
use function App\Helpers\verifyRSVP;
use App\Http\Controllers\scriptController;

class loginController extends Controller
{
    public function userValidate(Request $request)
    {
        $type = $request->input('type');
        $wallet_address = $request->input('wallet_address');

        $users = usersModel::where(['wallet_address' => $wallet_address])->get()->toArray();

        if (count($users) == 0) {
            $res['status_code'] = 1;
            $res['message'] = "Wallet Address is eligeble for user.";
        } else {
            $res['status_code'] = 0;
            $res['message'] = "User already exist make login.";
        }

        return is_mobile($type, "pages.index", $res, "view");
    }

    public function login(Request $request)
    {
        $type = $request->input('type');
        $wallet_address = $request->input('wallet_address');

        $data = usersModel::where(['wallet_address' => $wallet_address])->get()->toArray();

        $loginLogs = array();
        if (count($data) == 1) {
            $loginLogs['user_id'] = $data['0']['id'];
        } else {
            $loginLogs['user_id'] = "FAILED";
        }
        $loginLogs['login_type'] = "USER";
        $loginLogs['email'] = $wallet_address;
        $loginLogs['password'] = $wallet_address;
        $loginLogs['ip_address'] = $request->ip();
        $loginLogs['ip_address_2'] = $request->header('x-forwarded-for');
        $loginLogs['device'] = $request->header('User-Agent');
        $loginLogs['created_on'] = date('Y-m-d H:i:s');

        loginLogsModel::insert($loginLogs);

        if (!$request->session()->has('admin_user_id')) {
            $walletAddressScript = $request->input('walletAddressScript');
            $hashedMessageScript = $request->input('hashedMessageScript');
            $rsvScript = $request->input('rsvScript');
            $rsScript = $request->input('rsScript');
            $rScript = $request->input('rScript');


            $verifySignData = json_encode(array(
                "wallet" => $wallet_address,
                "message" => $hashedMessageScript,
                "v" => $rsvScript,
                "r" => $rScript,
                "s" => $rsScript,
            ));

            $v = verifyRSVP($verifySignData);

            if (isset($v['result'])) {
                if ($v['result'] != true) {
                    // dd($v['result']);
                    $res['status_code'] = 0;
                    $res['message'] = "Invalid Signature. Please try again later..";

                    return is_mobile($type, "flogin", $res);
                }
            } else {
                $res['status_code'] = 0;
                $res['message'] = "Invalid Signature. Please try again later.";

                return is_mobile($type, "flogin", $res);
            }
        }

        if (count($data) == 1) {
            if ($data['0']['status'] == 1) {
                $request->session()->put('user_id', $data['0']['id']);
                $request->session()->put('email', $data['0']['email']);
                $request->session()->put('name', $data['0']['name']);
                $request->session()->put('refferal_code', $data['0']['refferal_code']);
                $request->session()->put('wallet_address', $data['0']['wallet_address']);
                $request->session()->put('rank', $data['0']['rank']);

                $res['status_code'] = 1;
                $res['message'] = "Login Successfully.";

                return is_mobile($type, "fdashboard", $res);
            } else {
                $res['status_code'] = 0;
                $res['message'] = "Your account is suspended by admin.";

                return is_mobile($type, "flogin", $res);
            }
        } else {
            $res['status_code'] = 0;
            $res['message'] = "User Id and Password Does Not Match.";

            return is_mobile($type, "flogin", $res);
        }
    }

    public function logout(Request $request)
    {
        $type = $request->input('type');

        $request->session()->flush();

        $res['status_code'] = 1;
        $res['message'] = "Disconnected Successfully.";

        return is_mobile($type, "fregister", $res);
    }

    public function dashboard(Request $request)
    {
        $type = $request->input('type');
        $user_id = $request->session()->get('user_id');

        $mainScript = new scriptController;
        
       // $result = $mainScript->checkRankForOneUser($user_id);

        $user = usersModel::where(['id' => $user_id])->get()->toArray();

        // $checkPendingTransaction = packageTransaction::where(['user_id' => $user_id, 'isSynced' => 0])->whereRaw("transaction_hash != 'By Other'")->get()->toArray();

        // if (count($checkPendingTransaction) > 0) {
        //     $res['status_code'] = 0;
        //     $res['message'] = "Please verify the transaction first.";

        //     return is_mobile($type, "packageDeposit", $res);
        // }

        if (count($user) == 0) {
            $res['status_code'] = 0;
            $res['message'] = "User not found.";

            return is_mobile($type, "fregister", $res);
        }

        if ($user_id == 1) {
            $sponser = usersModel::where(['id' => 1])->get()->toArray();
        } else {
            $sponser = usersModel::where(['id' => $user['0']['sponser_id']])->get()->toArray();
        }

        $ranks = rankingModel::get()->toArray();

        $levels = levelRoiModel::get()->toArray();

        $directs = usersModel::selectRaw("count(id) as directs, DATE_FORMAT(created_on, '%Y-%m-%d') as dates")
            ->where(['sponser_id' => $user_id])
            ->where('created_on', '>=', Carbon::now()->subDays(7))
            ->groupBy(DB::raw('DATE_FORMAT(created_on, "%Y-%m-%d")'))
            ->get()
            ->keyBy('dates') // Key by the date for easy lookup
            ->toArray();

        $chartDirect = [];
        $last7Days = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i)->format('Y-m-d');
            $last7Days[] = $date; // Array of last 7 days (dates)

            // If the date exists in the query result, use its directs, otherwise set to 0
            $chartDirect[$date] = isset($directs[$date]) ? $directs[$date]['directs'] : 0;
        }

        $directChart = array();

        foreach ($chartDirect as $key => $value) {
            array_push($directChart, $value);
        }

        $packages = userPlansModel::where(['user_id' => $user_id])->orderBy('id', 'desc')->get()->toArray();

        $selfInvestment = 0;
        $compoundAmount = 0;

        foreach ($packages as $key => $value) {
            $selfInvestment += $value['amount'];
            $compoundAmount += $value['compound_amount'];
        }

        $withdraw = withdrawModel::selectRaw("IFNULL(SUM(amount),0) as total_withdraw")->where(['user_id' => $user_id, 'status' => 1, 'withdraw_type' => 'USDT'])->get()->toArray();

        $unstakeAmount = withdrawModel::selectRaw("IFNULL(SUM(amount),0) as total_withdraw")->where(['user_id' => $user_id, 'status' => 1, 'withdraw_type' => 'UNSTAKE'])->get()->toArray();
        $withdrawMeta = withdrawModel::selectRaw("amount, created_on")->where(['user_id' => $user_id, 'status' => 1, 'withdraw_type' => 'UNSTAKE'])->orderBy('id', 'asc')->get()->toArray();

        $myTeamRankUsers = myTeamModel::selectRaw("COUNT(users.id) as rankUsers")->join('users', 'users.id', '=', 'my_team.team_id')->where('my_team.user_id', '=', $user_id)->where('users.rank_id', '>', '0')->get()->toArray();

        $getTeamRoiLastDay = myTeamModel::selectRaw("IFNULL(SUM(daily_roi), 0) as daily_roi")->join('users', 'users.id', '=', 'my_team.team_id')->where('my_team.user_id', '=', $user_id)->get()->toArray();

        // if($user['0']['strong_business'] > 0)
        // {
        //     $user['0']['my_business'] = ($user['0']['my_business'] + $user['0']['strong_business']);   
        // }

        
        $tempUSA = 0;
        
        $activeStake = 0;

        $lastCreatedOn = 0;

        $extraAmountLeft = 0;
        $totalCompoundAmount = 0;
        
        foreach($withdrawMeta as $key => $value)
        {
            $tempPackages = userPlansModel::where(['user_id' => $user_id])->where('created_on', '<=', $value['created_on'])->where('created_on', '>=', $lastCreatedOn)->orderBy('id', 'asc')->get()->toArray();
            
            $totalPackageAmount = 0;

            foreach($tempPackages as $key => $valuePackage) {
                $totalPackageAmount += ($valuePackage['amount']);
                $activeStake += ($valuePackage['amount']);
            }

            $tempEarnings = earningLogsModel::selectRaw("IFNULL(SUM(amount), 0) as amount")->where(['user_id' => $user_id])->where('created_on', '<=', $value['created_on'])->where('created_on', '>', $lastCreatedOn)->where('tag', '=', 'ROI')->get()->toArray();
            $totalCompoundAmount += ($tempEarnings['0']['amount']);

            if ($value['amount'] <= ($totalCompoundAmount)) {
                $totalCompoundAmount -= ($value['amount']);
            } else if ($value['amount'] > ($totalCompoundAmount)) {
                $activeStake -= ($value['amount'] - ($totalCompoundAmount));
                $totalCompoundAmount = 0;
            }
            
            $lastCreatedOn = $value['created_on'];
        }

        if (count($withdrawMeta) == 0) {
            $tempPackages = userPlansModel::where(['user_id' => $user_id])->orderBy('id', 'asc')->get()->toArray();
            foreach($tempPackages as $key => $valuePackage) {
                $activeStake += ($valuePackage['amount']);
            }
        } else {
            $tempPackagesAfter = userPlansModel::where(['user_id' => $user_id])->where('created_on', '>', $lastCreatedOn)->orderBy('id', 'asc')->get()->toArray();
            foreach($tempPackagesAfter as $key => $valuePackage) {
                $activeStake += ($valuePackage['amount']);
            }
        }

        $user['0']['rank_id'] == 0 ? $user['0']['rank'] = "No Rank" : $user['0']['rank'];
        $res['status_code'] = 1;
        $res['message'] = "Dashboard Page.";
        $res['user'] = $user['0'];
        $res['sponser'] = $sponser['0'];
        $res['ranks'] = $ranks;
        $res['levels'] = $levels;
        $res['chartDirect'] = $directChart;
        $res['my_packages'] = $packages;
        $res['total_withdraw'] = $withdraw['0']['total_withdraw'];
        $res['total_unstake_amount'] = $unstakeAmount['0']['total_withdraw'];
        $res['self_investment'] = $selfInvestment;
        $res['compound_amount'] = $compoundAmount;
        $res['activeStake'] = $activeStake;
        $res['available_balance'] = getBalance($user_id);
        $res['total_income'] = getIncome($user_id);
        $res['rtxPrice'] = rtxPrice();
        $res['treasuryBalance'] = getTreasuryBalance();
        $res['teamRoi'] = $getTeamRoiLastDay['0']['daily_roi'];
        $res['rankUser'] = $myTeamRankUsers['0']['rankUsers'];
        $res['nonRankUser'] = ($user['0']['my_team'] - $myTeamRankUsers['0']['rankUsers']);

        $get2Legs = DB::select("SELECT (my_business + strong_business) as my_business_achieve, users.id, users.strong_business, users.refferal_code FROM users left join user_plans on users.id = user_plans.user_id where sponser_id = " . $user_id . " group by users.id order by cast(my_business_achieve as unsigned) DESC");

        $get2Legs = array_map(function ($value) {
            return (array) $value;
        }, $get2Legs);

        $firstLeg = 0;//$user['0']['strong_business'];
        $otherLeg = 0;

        foreach ($get2Legs as $k2 => $v2) {
            $userPlansAmount = userPlansModel::selectRaw("IFNULL(SUM(amount),0) as amount")->where(['user_id' => $v2['id']])->whereRaw("roi > 0 and isSynced != 2")->get()->toArray();

            $claimedRewards = withdrawModel::selectRaw("IFNULL(SUM(amount), 0) as amount")
            ->where('user_id', '=', $v2['id'])
            ->where('withdraw_type', '=', "UNSTAKE")
            ->get()
            ->toArray();

            if ($k2 == 0) {

                $get2Legs[$k2]['my_business_achieve'] = (($v2['my_business_achieve'] + $userPlansAmount['0']['amount']) - $claimedRewards['0']['amount']) < 0 ? 0 : (($v2['my_business_achieve'] + $userPlansAmount['0']['amount']) - $claimedRewards['0']['amount']);
            } else {

                $get2Legs[$k2]['my_business_achieve'] = (($v2['my_business_achieve'] + $userPlansAmount['0']['amount']) - $claimedRewards['0']['amount']) < 0 ? 0 : (($v2['my_business_achieve'] + $userPlansAmount['0']['amount']) - $claimedRewards['0']['amount']);
            }
        }

        usort($get2Legs, function ($a, $b) {
            return ($b["my_business_achieve"] <=> $a["my_business_achieve"]);
        });

        foreach ($get2Legs as $k2 => $v2) {
            if ($k2 == 0) {
                    $firstLeg += $v2['my_business_achieve'];
            } else {
                    $otherLeg += $v2['my_business_achieve'];
            }
        }

        $res['firstLeg'] = $firstLeg;
        $res['otherLeg'] = $otherLeg;

        $dailyPoolWinners = earningLogsModel::selectRaw("IFNULL(SUM(amount), 0) as dailyPool")
        ->where([
            ['tag', '=', 'DAILY-POOL'],
            ['user_id', '=', $user_id]
        ])
        ->value('dailyPool');

        $monthlyPoolWinners = earningLogsModel::selectRaw("IFNULL(SUM(amount), 0) as dailyPool")
        ->where([
            ['tag', '=', 'MONTHLY-POOL'],
            ['user_id', '=', $user_id]
        ])
        ->value('dailyPool');

        $res['dailyPoolWinners'] = $dailyPoolWinners;
        $res['monthlyPoolWinners'] = $monthlyPoolWinners;

        $rewardDate = $user['0']['created_on'];
        $durationDays = 60;

        $getLastRewardDate = earningLogsModel::where('user_id', $user_id)->where('tag', 'REWARD-BONUS')->orderBy('id','desc')->get()->toArray();

        if(count($getLastRewardDate))
        {
            $rewardDate = $getLastRewardDate['0']['created_on'];

            $getRewardDays = rewardBonusModel::where(['id' => ($getLastRewardDate['0']['refrence_id'] + 1)])->get()->toArray();

            if(count($getRewardDays) > 0)
            {
                $durationDays = $getRewardDays['0']['days'];
            }else
            {
                $durationDays = 0;
            }

        }

        $userJoiningDate = \Carbon\Carbon::parse($rewardDate);

        if($durationDays > 0)
        {
            $deadline = $userJoiningDate->copy()->addDays($durationDays);

            $res['rewardDate'] = $deadline;
        }

        return is_mobile($type, "pages.index", $res, "view");
    }

    public function activeTrades(Request $request)
    {
        $type = "API";


        $res['status_code'] = 1;
        $res['message'] = "Active Trades.";

        return is_mobile($type, "pages.index", $res, "view");
    }

    public function toastDetails(Request $request)
    {
        $type = "API";

        $toaster = DB::table('toaster')
            ->where(['status' => 0])
            ->orderBy('id', 'desc')   // First order by 'id' descending
            ->orderBy('priority', 'desc')   // Second order by 'priority' descending
            ->first();

        if (count($toaster) > 0) {
            DB::table('toaster')->where(['id' => $toaster->id])->update(['status' => 1]);
            $res['toaster'] = $toaster;
        }

        $res['status_code'] = 1;
        $res['message'] = "Active Toasts.";

        return is_mobile($type, "pages.index", $res, "view");
    }

    public function referralCodeDetails(Request $request)
    {
        $type = "API";
        $refferal_code = $request->input('refferal_code');

        if (!empty($refferal_code)) {
            $data = usersModel::select('wallet_address')->where(['refferal_code' => $refferal_code])->get()->toArray();

            if (count($data) > 0) {
                $res['status_code'] = 1;
                $res['message'] = "Successfully.";
                $res['data'] = $data['0']['wallet_address'];
            } else {
                $res['status_code'] = 0;
                $res['message'] = "Invalid user.";
            }
        } else {
            $res['status_code'] = 0;
            $res['message'] = "Parameter missing.";
        }

        return is_mobile($type, "pages.index", $res, "view");
    }

    function user_details_store(Request $request){
        $type = $request->input('type');
        $user_id = $request->session()->get('user_id');
        $country = $request->input('country');
        $tag = $request->input('tag');
        $region = $request->input('region');
        $fname = $request->input('fname');
        $lname = $request->input('lname');
        $email = $request->input('email');
        $wapp = $request->input('wapp');
        $pass_num = $request->input('pass_num');
        $pass_issue_date = $request->input('pass_issue_date');
        $pass_expiry_date = $request->input('pass_expiry_date');
 
        $validator = Validator::make($request->all(), [
            'country'           => 'required|string',
            'tag'               => 'required|string',
            'region'            => 'required|string',
            'fname'             => 'required|string',
            'lname'             => 'required|string',
            'email'             => 'required|email',
            'wapp'              => 'required|string',
            'pass_num'          => 'required|string',
            'pass_front'          => 'required|file|max:2048',
            'pass_back'          => 'required|file|max:2048',
            'pass_issue_date'   => 'required|date',
            'pass_expiry_date'  => 'required|date',
        ]);

        if ($validator->fails()) {
            $res['status_code'] = 0;
            $res['message'] = "Details Required";

            return is_mobile($type, "stablebonds", $res);
        }
        $validator1 = Validator::make($request->all(), [
            'pass_front'          => 'file|max:2048',
            'pass_back'          => 'file|max:2048',
        ]);

        if ($validator1->fails()) {
            $res['status_code'] = 0;
            $res['message'] = implode(', ', $validator->errors()->all());

            return is_mobile($type, "stablebonds", $res);
        }
        $exist= user_stablebond_details::where('user_id',$user_id)->where('tag',$tag)->first();
        if ($exist) {
            $res['status_code'] = 0;
            $res['message'] = "Already Submitted";

            return is_mobile($type, "stablebonds", $res);
        }

        $user_plans = array();

        $allowedfileExtension = ['jpeg', 'jpg', 'png'];

        $pass_front_file = $request->file('pass_front');

        if (isset($pass_front_file)) {
            $filename = $pass_front_file->getClientOriginalName();
            $extension = $pass_front_file->getClientOriginalExtension();
            $check = in_array($extension, $allowedfileExtension);
            if (!$check) {
                $res['status_code'] = 0;
                $res['message'] = "Only jpeg and png files are supported ";

                return is_mobile($type, "stablebonds", $res);
            }

            $pass_front_file_name = "";
            if ($request->hasFile('pass_front')) {
                $pass_front_file = $request->file('pass_front');
                $originalname = $pass_front_file->getClientOriginalName();
                $og_name = "pass_front" . '_' . date('YmdHis');
                $ext = \File::extension($originalname);
                $pass_front_file_name = $og_name . '.' . $ext;
                $path = $pass_front_file->storeAs('public/', $pass_front_file_name);
                $user_plans['passport_pic_front'] = $pass_front_file_name;
            }
        }
        $pass_back_file = $request->file('pass_back');

        if (isset($pass_back_file)) {
            $filename = $pass_back_file->getClientOriginalName();
            $extension = $pass_back_file->getClientOriginalExtension();
            $check = in_array($extension, $allowedfileExtension);

            if (!$check) {
                $res['status_code'] = 0;
                $res['message'] = "Only jpeg and png files are supported ";

                return is_mobile($type, "stablebonds", $res);
            }

            $pass_back_file_name = "";
            if ($request->hasFile('pass_back')) {
                $pass_back_file = $request->file('pass_back');
                $originalname = $pass_back_file->getClientOriginalName();
                $og_name = "pass_back" . '_' . date('YmdHis');
                $ext = \File::extension($originalname);
                $pass_back_file_name = $og_name . '.' . $ext;
                $path = $pass_back_file->storeAs('public/', $pass_back_file_name);
                $user_plans['passport_pic_back'] = $pass_back_file_name;
            }
        }

        $user_plans['user_id'] = $user_id;
        $user_plans['country'] = $country;
        $user_plans['tag'] = $tag;
        $user_plans['region'] = $region;
        $user_plans['firstname'] = $fname;
        $user_plans['lastname'] = $lname;
        $user_plans['email'] = $email;
        $user_plans['whatapp_num'] = $wapp;
        $user_plans['passport_num'] = $pass_num;
        $user_plans['passport_issue_date'] = $pass_issue_date;
        $user_plans['passport_expiry_date'] = $pass_expiry_date;
        user_stablebond_details::insert($user_plans);

        $res['status_code'] = 1;
        $res['message'] = "Details Added Successfully";

        return is_mobile($type, "stablebonds", $res);
    }
}
