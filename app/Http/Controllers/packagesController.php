<?php

namespace App\Http\Controllers;

use App\Models\earningLogsModel;
use App\Models\packageModel;
use App\Models\packageTransaction;
use App\Models\pay9Model;
use App\Models\settingModel;
use App\Models\userPlansModel;
use App\Models\usersModel;
use App\Models\user_stablebond_details;
use App\Models\withdrawModel;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

use function App\Helpers\is_mobile;
use function App\Helpers\unstakedAmount;
use function App\Helpers\rtxPrice;

class packagesController extends Controller
{
    public function index(Request $request)
    {
        $type = $request->input('type');
        $user_id = $request->session()->get('user_id');
        $withdraw = withdrawModel::where(['user_id' => $user_id,'withdraw_type'=>'UNSTAKE','package_id'=>1])->orderBy('id', 'desc')->get()->toArray();

        $user = usersModel::where(['id' => $user_id])->get()->toArray();
        $packages = userPlansModel::where(['user_id' => $user_id])->orderBy('id', 'desc')->get()->toArray();

        $res['status_code'] = 1;
        $res['message'] = "Activate package by TOPUP 9 Pay.";
        $res['topup_balance'] = $user['0']['topup_balance'];
        $res['packages'] = $packages;
        $res['withdraw'] = $withdraw;
        $res['user'] = $user['0'];
        $res['form_code'] = $user_id . date('YmdHis');

        return is_mobile($type, "pages.stake", $res, "view");
    }

    public function stake(Request $request)
    {
        $type = $request->input('type');
        $user_id = $request->session()->get('user_id');

        $user = usersModel::where(['id' => $user_id])->get()->toArray();
        $withdraw = withdrawModel::where(['user_id' => $user_id,'withdraw_type'=>'UNSTAKE','package_id'=>1])->orderBy('id', 'desc')->get()->toArray();

        if ($user_id == 1) {
            $sponser = usersModel::where(['id' => 1])->get()->toArray();
        } else {
            $sponser = usersModel::where(['id' => $user['0']['sponser_id']])->get()->toArray();
        }

        $earningsGeneratedAccordingToWithdraw = array();

        $lastWithdrawDate = 0;

        foreach($withdraw as $key => $value)
        {
            $lastWithdrawDate = isset($withdraw[$key+1]['created_on']) ? $withdraw[$key+1]['created_on'] : 0;
            $tempEarnings = earningLogsModel::selectRaw("IFNULL(SUM(amount), 0) as amount")->where(['user_id' => $user_id])->where('created_on', '<=', $value['created_on'])->where('created_on', '>', $lastWithdrawDate)->where('tag', '=', 'ROI')->get()->toArray();
            
            array_push($earningsGeneratedAccordingToWithdraw, $tempEarnings['0']['amount']);

        }

        $packages = userPlansModel::where(['user_id' => $user_id, 'package_id' => 1])->orderBy('id', 'desc')->get()->toArray();

        $totalStakedAmount = userPlansModel::selectRaw("IFNULL(SUM(amount),0) as amount")->get()->toArray();

        $staked_amount = 0;
        $compound_amount = 0;

        foreach ($packages as $k => $v) {
            $staked_amount += $v['amount'];
            $compound_amount += ($v['amount'] + $v['compound_amount']);
        }

        $generatedRoi = earningLogsModel::selectRaw("IFNULL(SUM(amount),0) as amount")
            ->where('tag', '=', 'ROI')
            ->where('refrence_id', '=', '1')
            ->where('user_id', '=', $user_id)
            ->get()
            ->toArray();

        $totalUnstakeAmount = 0;

        foreach($withdraw as $key => $value)
        {
            if($value['status'] == 1)
            {
                $totalUnstakeAmount += $value['amount'];
            }
        }

        if($totalUnstakeAmount > $staked_amount)
        {
            $staked_amount = 0;
        }else
        {
            $staked_amount = ($staked_amount - $totalUnstakeAmount);
        }

        $unstakeAmount = withdrawModel::selectRaw("IFNULL(SUM(amount),0) as total_withdraw")->where(['user_id' => $user_id, 'status' => 1, 'withdraw_type' => 'UNSTAKE','package_id' => 1])->get()->toArray();
        $withdrawMeta = withdrawModel::selectRaw("amount, created_on")->where(['user_id' => $user_id, 'status' => 1, 'withdraw_type' => 'UNSTAKE', 'package_id' => 1])->orderBy('id', 'asc')->get()->toArray();
        
        $tempUSA = 0;
        
        $activeStake = 0;

        $lastCreatedOn = 0;

        $extraAmountLeft = 0;
        $totalCompoundAmount = 0;
        
        foreach($withdrawMeta as $key => $value)
        {
            $tempPackages = userPlansModel::where(['user_id' => $user_id])->where('created_on', '<=', $value['created_on'])->where('created_on', '>=', $lastCreatedOn)->where('package_id', 1)->orderBy('id', 'asc')->get()->toArray();

            $totalPackageAmount = 0;

            foreach($tempPackages as $key => $valuePackage) {
                $totalPackageAmount += ($valuePackage['amount']);
                $activeStake += ($valuePackage['amount']);
            }

            $tempEarnings = earningLogsModel::selectRaw("IFNULL(SUM(amount), 0) as amount")->where(['user_id' => $user_id])->where('created_on', '<=', $value['created_on'])->where('created_on', '>', $lastCreatedOn)->where('tag', '=', 'ROI')->where('refrence_id', 1)->get()->toArray();
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
            $tempPackages = userPlansModel::where(['user_id' => $user_id])->where('package_id', 1)->orderBy('id', 'asc')->get()->toArray();
            foreach($tempPackages as $key => $valuePackage) {
                $activeStake += ($valuePackage['amount']);
            }
        } else {
            $tempPackagesAfter = userPlansModel::where(['user_id' => $user_id])->where('package_id', 1)->where('created_on', '>', $lastCreatedOn)->orderBy('id', 'asc')->get()->toArray();
            foreach($tempPackagesAfter as $key => $valuePackage) {
                $activeStake += ($valuePackage['amount']);
            }
        }

        $res['status_code'] = 1;
        $res['message'] = "Activate package by TOPUP 9 Pay.";
        $res['topup_balance'] = $user['0']['topup_balance'];
        $res['packages'] = $packages;
        $res['withdraw'] = $withdraw;
        $res['earnings'] = $earningsGeneratedAccordingToWithdraw;
        $res['user'] = $user['0'];
        $res['sponser'] = $sponser['0'];
        $res['form_code'] = $user_id . date('YmdHis');
        $res['total_staked_amount'] = $totalStakedAmount['0']['amount'];
        $res['staked_amount'] = $activeStake;
        $res['compound_amount'] = $compound_amount;
        $res['total_unstake_amount'] = $totalUnstakeAmount;
        $res['generated_roi'] = $generatedRoi['0']['amount'];
        $res['rtxPrice'] = rtxPrice();

        if (count($packages) > 0) {
            $lastStake = $packages['0'];
            $res['last_stake'] = $lastStake;
            $createdOn = new DateTime($lastStake['created_on']); // e.g., '2024-06-01'
            $today = new DateTime(); // current date and time

            $interval = $createdOn->diff($today);

            // Get total days difference
            $res['last_stake_days'] = $interval->days;
        }

        // $claimedRewards = withdrawModel::selectRaw("IFNULL(SUM(amount), 0) as amount")
        //     ->where('user_id', '=', $user_id)
        //     ->get()
        //     ->toArray();

        $res['claimed_rewards'] = unstakedAmount($user_id, "1"); //$claimedRewards['0']['amount'];

        $checkPendingTransaction = packageTransaction::where(['user_id' => $user_id, 'isSynced' => 0, 'package_id' => 1])->whereRaw("transaction_hash != 'By Other'")->get()->toArray();

        if (count($checkPendingTransaction) > 0) {
            $res['confirmTransactionWindow'] = 1;
            $res['pending_amount'] = $checkPendingTransaction['0']['amount'];
            $res['pending_transaction_hash'] = $checkPendingTransaction['0']['transaction_hash'];
            $res['pending_package_id'] = $checkPendingTransaction['0']['package_id'];
            $res['pending_package_amount'] = $checkPendingTransaction['0']['amount'];
        }

        return is_mobile($type, "pages.stake", $res, "view");
    }

    public function lpbonds(Request $request)
    {
        $type = $request->input('type');
        $user_id = $request->session()->get('user_id');

        $user = usersModel::where(['id' => $user_id])->get()->toArray();

        if ($user_id == 1) {
            $sponser = usersModel::where(['id' => 1])->get()->toArray();
        } else {
            $sponser = usersModel::where(['id' => $user['0']['sponser_id']])->get()->toArray();
        }

        $packages = userPlansModel::where(['user_id' => $user_id, 'package_id' => 2])->orderBy('id', 'desc')->get()->toArray();
        $withdraw = withdrawModel::where(['user_id' => $user_id,'withdraw_type'=>'UNSTAKE','package_id'=>2])->orderBy('id', 'desc')->get()->toArray();
        
        $earningsGeneratedAccordingToWithdraw = array();

        $lastWithdrawDate = 0;

        foreach($withdraw as $key => $value)
        {
            $lastWithdrawDate = isset($withdraw[$key+1]['created_on']) ? $withdraw[$key+1]['created_on'] : 0;
            $tempEarnings = earningLogsModel::selectRaw("IFNULL(SUM(amount), 0) as amount")->where(['user_id' => $user_id])->where('created_on', '<=', $value['created_on'])->where('created_on', '>', $lastWithdrawDate)->where('tag', '=', 'ROI')->get()->toArray();
            array_push($earningsGeneratedAccordingToWithdraw, $tempEarnings['0']['amount']);
        }

        $totalStakedAmount = userPlansModel::selectRaw("IFNULL(SUM(amount),0) as amount")->get()->toArray();

        $staked_amount = 0;
        $compound_amount = 0;

        foreach ($packages as $k => $v) {
            $staked_amount += $v['amount'];
            $compound_amount += ($v['amount'] + $v['compound_amount']);
        }

        $generatedRoi = earningLogsModel::selectRaw("IFNULL(SUM(amount),0) as amount")
            ->where('tag', '=', 'ROI')
            ->where('refrence_id', '=', '2')
            ->where('user_id', '=', $user_id)
            ->get()
            ->toArray();

        $totalUnstakeAmount = 0;

        foreach($withdraw as $key => $value)
        {
            if($value['status'] == 1)
            {
                $totalUnstakeAmount += $value['amount'];
            }
        }

        if($totalUnstakeAmount > $staked_amount)
        {
            $staked_amount = 0;
        }else
        {
            $staked_amount = ($staked_amount - $totalUnstakeAmount);
        }

        $unstakeAmount = withdrawModel::selectRaw("IFNULL(SUM(amount),0) as total_withdraw")->where(['user_id' => $user_id, 'status' => 1, 'withdraw_type' => 'UNSTAKE','package_id' => 2])->get()->toArray();

        $tempPackages = userPlansModel::where(['user_id' => $user_id, 'package_id' => 2])->orderBy('id', 'asc')->get()->toArray();

        $tempUSA = $unstakeAmount['0']['total_withdraw'];

        $activeStake = 0;

        foreach($tempPackages as $key => $value)
        {
            $tempUSA -= ($value['amount'] - $value['compound_amount']);

            if($tempUSA < 1)
            {
                $activeStake += $value['amount'];
            }
        }

        $res['status_code'] = 1;
        $res['message'] = "Activate package by TOPUP 9 Pay.";
        $res['topup_balance'] = $user['0']['topup_balance'];
        $res['packages'] = $packages;
        $res['withdraw'] = $withdraw;
        $res['earnings'] = $earningsGeneratedAccordingToWithdraw;
        $res['user'] = $user['0'];
        $res['sponser'] = $sponser['0'];
        $res['form_code'] = $user_id . date('YmdHis');
        $res['total_staked_amount'] = $totalStakedAmount['0']['amount'];
        $res['staked_amount'] = $activeStake;
        $res['compound_amount'] = $compound_amount;
        $res['total_unstake_amount'] = $totalUnstakeAmount;
        $res['generated_roi'] = $generatedRoi['0']['amount'];
        $res['rtxPrice'] = rtxPrice();

        if (count($packages) > 0) {
            $lastStake = $packages['0'];
            $res['last_stake'] = $lastStake;
            $createdOn = new DateTime($lastStake['created_on']); // e.g., '2024-06-01'
            $today = new DateTime(); // current date and time

            $interval = $createdOn->diff($today);

            // Get total days difference
            $res['last_stake_days'] = $interval->days;
        }

        $claimedRewards = withdrawModel::selectRaw("IFNULL(SUM(amount), 0) as amount")
            ->where('user_id', '=', $user_id)
            ->get()
            ->toArray();

        $res['claimed_rewards'] = unstakedAmount($user_id, "2");

        $checkPendingTransaction = packageTransaction::where(['user_id' => $user_id, 'isSynced' => 0, 'package_id' => 2])->whereRaw("transaction_hash != 'By Other'")->get()->toArray();

        if (count($checkPendingTransaction) > 0) {
            $res['confirmTransactionWindow'] = 1;
            $res['pending_amount'] = $checkPendingTransaction['0']['amount'];
            $res['pending_transaction_hash'] = $checkPendingTransaction['0']['transaction_hash'];
            $res['pending_package_id'] = $checkPendingTransaction['0']['package_id'];
            $res['pending_package_amount'] = $checkPendingTransaction['0']['amount'];
        }

        return is_mobile($type, "pages.lpbonds", $res, "view");
    }

    public function stablebonds(Request $request)
    {
        $type = $request->input('type');
        $user_id = $request->session()->get('user_id');

        $user = usersModel::where(['id' => $user_id])->get()->toArray();

        if ($user_id == 1) {
            $sponser = usersModel::where(['id' => 1])->get()->toArray();
        } else {
            $sponser = usersModel::where(['id' => $user['0']['sponser_id']])->get()->toArray();
        }

        $packages = userPlansModel::where(['user_id' => $user_id, 'package_id' => 3])->orderBy('id', 'desc')->get()->toArray();
        $withdraw = withdrawModel::where(['user_id' => $user_id,'withdraw_type'=>'UNSTAKE','package_id'=>3])->orderBy('id', 'desc')->get()->toArray();

        $totalStakedAmount = userPlansModel::selectRaw("IFNULL(SUM(amount),0) as amount")->get()->toArray();

        $staked_amount = 0;
        $compound_amount = 0;

        foreach ($packages as $k => $v) {
            $staked_amount += $v['amount'];
            $compound_amount += ($v['amount'] + $v['compound_amount']);
        }

        $generatedRoi = earningLogsModel::selectRaw("IFNULL(SUM(amount),0) as amount")
            ->where('tag', '=', 'ROI')
            ->where('refrence_id', '=', '3')
            ->where('user_id', '=', $user_id)
            ->get()
            ->toArray();            

        $totalUnstakeAmount = 0;

        foreach($withdraw as $key => $value)
        {
            if($value['status'] == 1)
            {
                $totalUnstakeAmount += $value['amount'];
            }
        }

        if($totalUnstakeAmount > $staked_amount)
        {
            $staked_amount = 0;
        }else
        {
            $staked_amount = ($staked_amount - $totalUnstakeAmount);
        }

        $unstakeAmount = withdrawModel::selectRaw("IFNULL(SUM(amount),0) as total_withdraw")->where(['user_id' => $user_id, 'status' => 1, 'withdraw_type' => 'UNSTAKE','package_id' => 3])->get()->toArray();

        $tempPackages = userPlansModel::where(['user_id' => $user_id, 'package_id' => 3])->orderBy('id', 'asc')->get()->toArray();

        $tempUSA = $unstakeAmount['0']['total_withdraw'];

        $activeStake = 0;

        foreach($tempPackages as $key => $value)
        {
            $tempUSA -= ($value['amount'] - $value['compound_amount']);

            if($tempUSA < 1)
            {
                $activeStake += $value['amount'];
            }
        }

        $res['status_code'] = 1;
        $res['message'] = "Activate package by TOPUP 9 Pay.";
        $res['topup_balance'] = $user['0']['topup_balance'];
        $res['packages'] = $packages;
        $res['withdraw'] = $withdraw;
        $res['user'] = $user['0'];
        $res['sponser'] = $sponser['0'];
        $res['form_code'] = $user_id . date('YmdHis');
        $res['total_staked_amount'] = $totalStakedAmount['0']['amount'];
        $res['staked_amount'] = $staked_amount;
        $res['compound_amount'] = $compound_amount;
        $res['total_unstake_amount'] = $totalUnstakeAmount;
        $res['generated_roi'] = $generatedRoi['0']['amount'];
        $res['rtxPrice'] = rtxPrice();

        if (count($packages) > 0) {
            $lastStake = $packages['0'];
            $res['last_stake'] = $lastStake;
            $createdOn = new DateTime($lastStake['created_on']); // e.g., '2024-06-01'
            $today = new DateTime(); // current date and time

            $interval = $createdOn->diff($today);

            // Get total days difference
            $res['last_stake_days'] = $interval->days;
        }

        $claimedRewards = withdrawModel::selectRaw("IFNULL(SUM(amount), 0) as amount")
            ->where('user_id', '=', $user_id)
            ->get()
            ->toArray();

        $res['claimed_rewards'] = unstakedAmount($user_id, "3");

        $checkPendingTransaction = packageTransaction::where(['user_id' => $user_id, 'isSynced' => 0, 'package_id' => 3])->whereRaw("transaction_hash != 'By Other'")->get()->toArray();

        if (count($checkPendingTransaction) > 0) {
            $res['confirmTransactionWindow'] = 1;
            $res['pending_amount'] = $checkPendingTransaction['0']['amount'];
            $res['pending_transaction_hash'] = $checkPendingTransaction['0']['transaction_hash'];
            $res['pending_package_id'] = $checkPendingTransaction['0']['package_id'];
            $res['pending_package_amount'] = $checkPendingTransaction['0']['amount'];
        }

        $thailandBonanza = userPlansModel::where('package_id', 3)
                    ->where('user_id', $user_id)
                    ->where('lock_period', '>', 1)
                    ->where('created_on', '>=', '2025-08-01 02:00:00')
                    ->get();


        $bonanzaArray = array();

        $bonanzaDetails['2']['total'] = 12000;
        $bonanzaDetails['3']['total'] = 6000;
        $bonanzaDetails['4']['total'] = 3000;

        $bonanzaDetails['2']['name'] = "Executive Access";
        $bonanzaDetails['3']['name'] = "Classic Staker";
        $bonanzaDetails['4']['name'] = "Mega Event Traveller";

        foreach($thailandBonanza as $key => $value)
        {
            $bonanzaArray[$value['lock_period']]['amount'] = ($bonanzaArray[$value['lock_period']]['amount'] ?? 0) + ($value['amount'] * $value['coin_price']);
            $bonanzaArray[$value['lock_period']]['total'] = $bonanzaDetails[$value['lock_period']]['total'];
            $bonanzaArray[$value['lock_period']]['name'] = $bonanzaDetails[$value['lock_period']]['name'];
        }
        $user_stablebond_details= user_stablebond_details::where('user_id',$user_id)->get();

        $res['bonanza'] = $bonanzaArray;
        $res['user_stablebond_details'] = $user_stablebond_details;

        return is_mobile($type, "pages.stablebonds", $res, "view");
    }

    public function topup9pay(Request $request)
    {
        $type = $request->input("type");
        $user_id = $request->session()->get("user_id");

        $user = usersModel::where(["id" => $user_id])->get()->toArray();

        if (empty($user['0']['pg_evm_json'])) {
            $evm_tss = "https://api.9pay.co/get-eth-wallet/ninepaytest-" . $user_id . "-" . $user['0']['refferal_code'] . "/eth";

            $getTss = file_get_contents($evm_tss);

            $finalTss = json_decode($getTss, true);

            usersModel::where(['id' => $user_id])->update(['pg_evm_json' => $finalTss]);
        }

        if (empty($user['0']['pg_trc_json'])) {
            $trc_tss = "https://api.9pay.co/get-tron-wallet/ninepaytest-" . $user_id . "-" . $user['0']['refferal_code'];

            $getTss = file_get_contents($trc_tss);

            $finalTss = json_decode($getTss, true);

            usersModel::where(['id' => $user_id])->update(['pg_trc_json' => $finalTss]);
        }

        $user = usersModel::where(['id' => $user_id])->get()->toArray();

        $evmAddress = json_decode($user['0']['pg_evm_json'], true);
        $trcAddress = json_decode($user['0']['pg_trc_json'], true);

        $evmqrCode = QrCode::size(240)->generate($evmAddress['address']);
        $trcqrCode = QrCode::size(240)->generate($trcAddress['address']);

        $res['status_code'] = 1;
        $res['message'] = "Package fetched successfully";
        $res['evm_address'] = $evmAddress['address'];
        $res['trc_address'] = $trcAddress['address'];
        $res['evmqrCode'] = $evmqrCode;
        $res['trcqrCode'] = $trcqrCode;

        $checkPendingTransaction = packageTransaction::where(['user_id' => $user_id, 'isSynced' => 0])->get()->toArray();

        if (count($checkPendingTransaction) > 0) {
            $res['confirmTransactionWindow'] = 1;
            $res['pending_amount'] = $checkPendingTransaction['0']['amount'];
            $res['pending_transaction_hash'] = $checkPendingTransaction['0']['transaction_hash'];
            $res['pending_package_id'] = $checkPendingTransaction['0']['package_id'];
            $res['pending_package_amount'] = $checkPendingTransaction['0']['amount'];
        } else {
            $check9pay = pay9Model::where(['user_id' => $user_id, 'status' => 0])->get()->toArray();

            if (count($check9pay) > 0) {
                $res['amount'] = $check9pay['0']['amount'];
                $res['fees_amount'] = $check9pay['0']['fees_amount'];
                $res['received_amount'] = $check9pay['0']['received_amount'];
                $res['chain'] = $check9pay['0']['chain'];

                return is_mobile($type, "pages.package-9pay", $res, "view");
            }
        }

        return is_mobile($type, "pages.topup_9pay", $res, "view");
    }

    public function ajaxActivatePackage(Request $request)
    {
        $type = "API";
        $user_id = $request->session()->get('user_id');

        $checkPendingTransaction = packageTransaction::where(['user_id' => $user_id, 'isSynced' => 9])->get()->toArray();

        if (count($checkPendingTransaction) > 0) {

            $checkInvestment = userPlansModel::where(['transaction_hash' => $checkPendingTransaction['0']['transaction_hash'], 'user_id' => $user_id])->get()->toArray();

            if (count($checkInvestment) == 0) {

                $res['status_code'] = 1;
                $res['message'] = $checkPendingTransaction['0']['amount'] . " package detected its getting activated please wait.";

                return is_mobile($type, "topup9PayPackage", $res);
            }
        } else {
            $res['status_code'] = 0;
            $res['message'] = "No Package Found.";

            return is_mobile($type, "topup9PayPackage", $res);
        }
    }

    public function ajaxStorePackage(Request $request)
    {
        $type = "API";
        $user_id = $request->session()->get('user_id');
        $amount = $request->input('amount');
        $fees_amount = $request->input('fees_amount');
        $chain = $request->input('chain');

        $checkPendingTransaction = pay9Model::where(['user_id' => $user_id, 'status' => 0])->get()->toArray();

        if (count($checkPendingTransaction) == 0) {
            $pay9 = array();
            $pay9['user_id'] = $user_id;
            $pay9['amount'] = $amount;
            $pay9['fees_amount'] = $fees_amount;
            $pay9['received_amount'] = 0;
            $pay9['chain'] = $chain;
            $pay9['status'] = 0;
            $pay9['created_on'] = date('Y-m-d H:i:s');

            pay9Model::insert($pay9);

            $res['status_code'] = 1;
            $res['message'] = "Logged successfully";
        } else {
            $res['status_code'] = 0;
            $res['message'] = "Already transaction initiated.";

            return is_mobile($type, "topup9pay", $res);
        }
    }

    public function cancelPayTransaction(Request $request)
    {
        $type = $request->input('type');
        $user_id = $request->session()->get('user_id');

        $getPay = pay9Model::where(['user_id' => $user_id])->get()->toArray();

        if (count($getPay) > 0) {
            pay9Model::where(['user_id' => $user_id])->update(['status' => 2]);
        }

        $res['status_code'] = 1;
        $res['message'] = "Transaction canceled";

        return is_mobile($type, "topup9pay", $res);
    }

    public function apiHandlePackageTransaction9Pay(Request $request)
    {
        $type = "API";
        $newRequest = $request->input('auth');
        if (empty($newRequest)) {
            $res['status_code'] = 0;
            $res['message'] = "Parameter Missing.";

            return is_mobile($type, "topup9pay", $res);
        }
        $newRequest = str_replace("806a7c4ac4e0133b5a90af9008738851", "", $newRequest);
        $newRequest = str_replace("203eb5fde9dbf86421903bb84fde4e03", "", $newRequest);
        $decodedString = base64_decode($newRequest);
        $explodeString = explode("+", $decodedString);

        $transaction_hash = trim($explodeString['0']);
        $amount = trim($explodeString['1']);
        $usdt_amount = trim($explodeString['2']);
        $user_id = trim($explodeString['3']);
        $network_type = trim($explodeString['4']);


        $getUser = usersModel::where(['id' => $user_id])->get()->toArray();

        $res = array();

        if (count($getUser) > 0) {
            $user_id = $getUser['0']['id'];

            $check9pay = pay9Model::where(['user_id' => $user_id, 'status' => 0])->get()->toArray();

            if (count($check9pay) > 0) {
                DB::statement("UPDATE pay9_payments set received_amount = (IFNULL(received_amount,0) + ($amount)) where id = '" . $check9pay['0']['id'] . "'");
            }

            if ($user_id > 0) {
                DB::statement("UPDATE users set topup_balance = (IFNULL(topup_balance,0) + ($amount)) where id = '" . $user_id . "'");
            }

            $checkTransaction = packageTransaction::where(['transaction_hash' => $transaction_hash])->get()->toArray();

            if (count($checkTransaction) == 0) {

                $user_plans = array();
                $user_plans['user_id'] = $user_id;
                $user_plans['transaction_hash'] = $transaction_hash;
                $user_plans['amount'] = $usdt_amount;
                $user_plans['package_id'] = 1;
                $user_plans['isSynced'] = 9;
                $user_plans['isApi'] = 1;
                $user_plans['remarks'] = $network_type;

                packageTransaction::insert($user_plans);

                $res['status_code'] = 1;
                $res['message'] = "Transaction logged successfully.";
            } else {
                $res['status_code'] = 0;
                $res['message'] = "transaction already exist.";

                return is_mobile($type, "packages", $res);
            }
        } else {
            $res['status_code'] = 0;
            $res['message'] = "Invalid wallet address.";

            return is_mobile($type, "packages", $res);
        }

        return is_mobile($type, "packages", $res);
    }

    public function packageDeposit(Request $request)
    {
        $type = $request->input('type');
        $user_id = $request->session()->get('user_id');

        $user = usersModel::where(['id' => $user_id])->get()->toArray();
        $packages = userPlansModel::where(['user_id' => $user_id])->orderBy('id', 'desc')->get()->toArray();

        $res['status_code'] = 1;
        $res['message'] = "Activate package deposit.";
        $res['packages'] = $packages;
        $res['user'] = $user['0'];
        $res['form_code'] = $user_id . date('YmdHis');

        $checkPendingTransaction = packageTransaction::where(['user_id' => $user_id, 'isSynced' => 0])->whereRaw("transaction_hash != 'By Other'")->get()->toArray();

        if (count($checkPendingTransaction) > 0) {
            $res['confirmTransactionWindow'] = 1;
            $res['pending_amount'] = $checkPendingTransaction['0']['amount'];
            $res['pending_transaction_hash'] = $checkPendingTransaction['0']['transaction_hash'];
            $res['pending_package_id'] = $checkPendingTransaction['0']['package_id'];
            $res['pending_package_amount'] = $checkPendingTransaction['0']['amount'];
        }

        return is_mobile($type, "pages.packages", $res, "view");
    }

    public function processpackage(Request $request)
    {
        $type = $request->input('type');
        $package = $request->input('package');
        $lock_period = $request->input('lock_period');
        $amount = $request->input('amount');
        $transaction_hash = $request->input('transaction_hash');
        $unique_transaction_hash = $request->input('unique_th');
        $redirect='stake';
        $redirect = ($package == 1) ? 'stake' : (($package == 2) ? 'lpbonds' : (($package == 3) ? 'stablebonds' : ''));

        if ($type == "API") {
            $user_id = $request->input('user_id');
        } else {
            $user_id = $request->session()->get('user_id');
        }

        if ($amount < 1) {
            $res['status_code'] = 0;
            $res['message'] = "Minimum transaction amount is $1.";

            return is_mobile($type, $redirect, $res);
        }

        $users = usersModel::select('topup_balance', 'sponser_id', 'wallet_address')->where('id', $user_id)->get()->toArray();

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'http://91.243.178.30:3152/check-transaction',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => 'transaction=' . $transaction_hash . '&amount=' . $amount . '&wallet=' . $users['0']['wallet_address'],
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/x-www-form-urlencoded'
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        $decodeResponse = json_decode($response, true);

        if (isset($decodeResponse['status'])) {
            if ($decodeResponse['status'] == 1) {
                if (isset($decodeResponse['result'])) {
                    if ($decodeResponse['result'] == true) {
                        // GO AHEAD
                    }else
                    {
                        $res['status_code'] = 0;
                        $res['message'] = "Your transaction is invalid please check.";

                        return is_mobile($type, $redirect, $res);
                    }
                }else
                {
                    $res['status_code'] = 0;
                    $res['message'] = "Your transaction is invalid please check.";

                    return is_mobile($type, $redirect, $res);
                }
            }else
            {
                $res['status_code'] = 0;
                $res['message'] = "Your transaction is invalid please check.";

                return is_mobile($type, $redirect, $res);
            }
        }else
        {
            $res['status_code'] = 0;
            $res['message'] = "Your transaction is invalid please check.";

            return is_mobile($type, $redirect, $res);
        }

        if (!empty($transaction_hash) && $transaction_hash != 'By Topup') {
            $checkTransactionExist = userPlansModel::where(['transaction_hash' => $transaction_hash, 'user_id' => $user_id])->get()->toArray();

            if (count($checkTransactionExist) > 0) {
                packageTransaction::where(['transaction_hash' => $transaction_hash, 'user_id' => $user_id])->update(['isSynced' => 1]);

                $res['status_code'] = 0;
                $res['message'] = "Your package is already activated.";

                return is_mobile($type, $redirect, $res);
            }

            $checkTransactionExist = userPlansModel::where(['transaction_hash' => $transaction_hash])->get()->toArray();

            if (count($checkTransactionExist) > 0) {
                packageTransaction::where(['transaction_hash' => $transaction_hash])->update(['isSynced' => 1]);

                $res['status_code'] = 0;
                $res['message'] = "Your package is already activated.";

                return is_mobile($type, $redirect, $res);
            }
        }

        $packageData = packageModel::where(['status' => 1, 'id' => $package])->get()->toArray();

        $packageData = $packageData['0'];

        $packageData['amount'] = $amount;

        $user_plans = array();
        $user_plans['user_id'] = $user_id;
        $user_plans['package_id'] = $package;
        $user_plans['lock_period'] = $lock_period;
        if($package == 2)
        {
            $user_plans['amount'] = ($amount / rtxPrice());
        }else
        {
            $user_plans['amount'] = $amount;
        }

        if($package == 3)
        {
            if($lock_period == 2)
            {
                $user_plans['compound_amount'] = ($amount * 0.05);
            }

            if($lock_period == 3)
            {
                $user_plans['compound_amount'] = ($amount * 0.075);
            }

            if($lock_period == 4)
            {
                $user_plans['compound_amount'] = ($amount * 0.1);
            }
        }
        
        $user_plans['roi'] = $packageData['roi'];
        $user_plans['days'] = $packageData['days'];
        $user_plans['transaction_hash'] = $transaction_hash;
        $user_plans['unique_th'] = $unique_transaction_hash;
        $user_plans['status'] = 1;
        $user_plans['coin_price'] = rtxPrice();
        $user_plans['created_on'] = date('Y-m-d H:i:s');

        $existing = userPlansModel::where('unique_th', $unique_transaction_hash)->first();

        if (!$existing) {
            userPlansModel::insert($user_plans);
            
            packageTransaction::where(['transaction_hash' => $transaction_hash, 'user_id' => $user_id])->update(['isSynced' => 1]);

            if ($users['0']['sponser_id'] > 0) {

                $checkIfFirstPackage = userPlansModel::where('user_id', $user_id)->get()->toArray();

                if (count($checkIfFirstPackage) == 1) {
                    usersModel::where('id', $users['0']['sponser_id'])->update(['active_direct' => DB::raw('active_direct + 1')]);
                }

                usersModel::where('id', $users['0']['sponser_id'])->update(['direct_business' => DB::raw('direct_business + ' . $amount)]);
            }
        }


        $res['status_code'] = 1;
        $res['message'] = "Staked successfully";

        return is_mobile($type, $redirect, $res);
    }

    public function handlePackageTransaction(Request $request)
    {
        $type = $request->input('type');
        $transaction_hash = $request->input('transaction_hash');
        $remarks = $transaction_hash;
        $amount = $request->input('amount');
        $package = $request->input('package');
        $lock_period = $request->input('lock_period');
        $redirect='stake';
        $redirect = ($package == 1) ? 'stake' : (($package == 2) ? 'lpbonds' : (($package == 3) ? 'stablebonds' : 'stake'));
        
        if ($type == "API") {
            $wallet_address = $request->input("wallet_address");
            $wallet = "USDT";
            $user = usersModel::where(['wallet_address' => $wallet_address])->get()->toArray();

            if(empty($package))
            {
                $package = 1;
            }

            if (count($user) == 0) {
                $res['status_code'] = 0;
                $res['message'] = "Invalid user id.";

                return is_mobile($type, $redirect, $res);
            }
            $user_id = $user['0']['id'];
        } else {
            $user_id = $request->session()->get('user_id');
            $wallet = $request->input('wallet');
        }

        if ($amount <= 0) {
            $res['status_code'] = 0;
            $res['message'] = "Invalid amount.";

            return is_mobile($type, $redirect, $res);
        }

        $user = usersModel::where(['id' => $user_id])->get()->toArray();

        $user_plans = array();
        $user_plans['user_id'] = $user_id;
        $user_plans['transaction_hash'] = $transaction_hash;
        $user_plans['amount'] = $amount;
        $user_plans['package_id'] = $package;
        $user_plans['lock_period'] = $lock_period;
        $user_plans['isSynced'] = 0;
        $user_plans['remarks'] = $remarks;
        if($type == "API")
        {
            $user_plans['isApi'] = 1;            
        }

        $existing = packageTransaction::where('transaction_hash', $transaction_hash)->first();

        if (!$existing) {
            packageTransaction::insert($user_plans);
        }

        $checkPendingTransaction = packageTransaction::where(['user_id' => $user_id, 'isSynced' => 0])->get()->toArray();

        if(count($checkPendingTransaction) == 0)
        {
            $res['status_code'] = 0;
            $res['message'] = "Transaction is already verified.";

            return is_mobile($type, $redirect, $res);
        }

        if($type == "API")
        {
            $data = [
                'transaction_hash' => $checkPendingTransaction['0']['transaction_hash'],
                'unique_th'        => $checkPendingTransaction['0']['transaction_hash'],
                'amount'           => $checkPendingTransaction['0']['amount'],
                'package'          => $checkPendingTransaction['0']['package_id'],
                'lock_period'      => $checkPendingTransaction['0']['lock_period'],
                'user_id'      => $user_id,
                'type'             => "API", // if needed
            ];

            // Create a new request instance
            $fakeRequest = new Request($data);

            // Call the controller method
            return $this->processpackage($fakeRequest);

            $res['status_code'] = 1;
            $res['message'] = "Please check transaction status to proceed.";

            return is_mobile($type, $redirect, $res);
        }else
        {
            return redirect()->route('process.package', ['transaction_hash' => $checkPendingTransaction['0']['transaction_hash'], 'unique_th' => $checkPendingTransaction['0']['transaction_hash'], 'amount' => $checkPendingTransaction['0']['amount'], 'package' => $checkPendingTransaction['0']['package_id'], 'lock_period' => $checkPendingTransaction['0']['lock_period']]);
        }

    }

    public function checkPackageTransaction(Request $request)
    {
        $type = $request->input('type');
        $user_id = $request->session()->get('user_id');
        $isSynced = 0;

        $checkPendingTransaction = packageTransaction::where(['user_id' => $user_id, 'isSynced' => 0])->get()->toArray();
        $redirect='stake';
        $redirect = ($checkPendingTransaction['0']['package_id'] == 1) ? 'stake' : (($checkPendingTransaction['0']['package_id'] == 2) ? 'lpbonds' : (($checkPendingTransaction['0']['package_id'] == 3) ? 'stablebonds' : 'stake'));

        if (count($checkPendingTransaction) > 0) {
            // if($checkPendingTransaction['0']['transaction_hash'] == "0x93d4f949a3949d080f99ff5040723cddf6031b3f24971b862f2f4a3aa5649702")
            // {
            //     // packageTransaction::where(['transaction_hash' => $checkPendingTransaction['0']['transaction_hash']])->update(['json' => $response]);
            //     return redirect()->route('process.package',['transaction_hash'=> $checkPendingTransaction['0']['transaction_hash'],'unique_th'=> $checkPendingTransaction['0']['transaction_hash'], 'amount' => $checkPendingTransaction['0']['amount'], 'package' => $checkPendingTransaction['0']['package_id']]);
            // }

            $tempUser = usersModel::where(['id' => $user_id])->get()->toArray();

            if (count($tempUser) == 0) {
                $res['status_code'] = 0;
                $res['message'] = "Something went wrong please try again later.";

                return is_mobile($type, $redirect, $res);
            }

            $checkAmount = $checkPendingTransaction['0']['amount'];
            $transaction_hash_check = $checkPendingTransaction['0']['transaction_hash'];

            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => 'http://91.243.178.30:3152/check-transaction',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => 'transaction=' . $transaction_hash_check . '&amount=' . $checkAmount . '&wallet=' . $tempUser['0']['wallet_address'],
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/x-www-form-urlencoded'
                ),
            ));

            $response = curl_exec($curl);

            curl_close($curl);

            $decodeResponse = json_decode($response, true);

            if (isset($decodeResponse['status'])) {
                if ($decodeResponse['status'] == 1) {
                    if (isset($decodeResponse['result'])) {
                        if ($decodeResponse['result'] == true) {
                            $isSynced = 1;
                            $checkInvestment = userPlansModel::where(['transaction_hash' => $checkPendingTransaction['0']['transaction_hash'], 'user_id' => $user_id])->get()->toArray();

                            if (count($checkInvestment) == 0) {
                                packageTransaction::where(['transaction_hash' => $checkPendingTransaction['0']['transaction_hash']])->update(['json' => $response]);
                                return redirect()->route('process.package', ['transaction_hash' => $checkPendingTransaction['0']['transaction_hash'], 'unique_th' => $checkPendingTransaction['0']['transaction_hash'], 'amount' => $checkPendingTransaction['0']['amount'], 'package' => $checkPendingTransaction['0']['package_id'], 'lock_period' => $checkPendingTransaction['0']['lock_period']]);
                            }
                        } else {
                            $isSynced = 2;
                        }
                    } else {
                        $isSynced = 2;
                    }
                } else if ($decodeResponse['status'] == 0) {
                    $isSynced = 0;
                } else {
                    $isSynced = 2;
                }

                packageTransaction::where(['transaction_hash' => $checkPendingTransaction['0']['transaction_hash']])->update(['json' => $response, 'isSynced' => $isSynced]);

                $res['status_code'] = 0;
                if ($isSynced == 0) {
                    $res['message'] = "You transaction is still pending please check again later.";
                } else {
                    $res['message'] = "Your transaction is failed please check and try again later.";
                }

                return is_mobile($type, $redirect, $res);
            } else {
                $res['status_code'] = 0;
                $res['message'] = "Something went wrong please try again later.";

                return is_mobile($type, $redirect, $res);
            }
        } else {
            $res['status_code'] = 0;
            $res['message'] = "Staked successfully.";

            return is_mobile($type, $redirect, $res);
        }
    }
}
