<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Redirect;
use App\Models\myTeamModel;
use App\Models\userPlansModel;
use App\Models\usersModel;
use App\Models\earningLogsModel;
use App\Models\withdrawModel;
use App\Models\settingModel;

if (!function_exists('is_mobile')) {
    function is_mobile($type, $url = null, $data = null, $redirect_type = "redirect")
    {
        if ($type == "API") {
            return json_encode($data);
        } else {
            if ($redirect_type == 'redirect') {
                //                return redirect($url)->with(['data' => $data]);
                return redirect()->route($url)->with(['data' => $data]);
                //                return redirect()->route( 'clients.show' )->with( [ 'id' => $id ] );
            } else if ($redirect_type == 'view') {
                return view($url, ['data' => $data]);
            }
        }
    }
}

if (!function_exists('checkReferralCode')) {
    function checkReferralCode($refferal_code)
    {
        $checkRefCode = usersModel::where(['refferal_code' => $refferal_code])->get()->toArray();

        if (count($checkRefCode) == 0) {
            return 1;
        } else {
            return 0;
        }
    }
}

if (!function_exists('updateReverseSize')) {
    function updateReverseSize($user_id, $og_user_id)
    {
        $data = usersModel::where(['id' => $user_id])->get()->toArray();
        $ogdata = usersModel::where(['id' => $og_user_id])->get()->toArray();

        if (count($data) > 0) {
            if ($data['0']['sponser_id'] > 0) {
                $myTeam = array();
                $myTeam['user_id'] = $data['0']['sponser_id'];
                $myTeam['team_id'] = $og_user_id;
                $myTeam['sponser_id'] = $ogdata['0']['sponser_id'];

                myTeamModel::insert($myTeam);

                DB::statement("UPDATE users set my_team = (my_team + 1) where id = '" . $data['0']['sponser_id'] . "'");

                updateReverseSize($data['0']['sponser_id'], $og_user_id);
            }
        }
    }
}

if (!function_exists('reverseBusiness')) {
    function reverseBusiness($user_id, $amount)
    {
        $data = usersModel::where(['id' => $user_id])->get()->toArray();

        if(count($data) > 0)
        {
            if($data['0']['sponser_id'] > 0)
            {
                // DB::statement("UPDATE users set my_business = (my_business - ".$amount.") where id = '".$data['0']['sponser_id']."'");
                DB::statement("UPDATE users set my_business = GREATEST(my_business - ".$amount.", 0) where id = '".$data['0']['sponser_id']."'");

                reverseBusiness($data['0']['sponser_id'], $amount);
            }
        }
    }
}

if (!function_exists('getBalance')) {
    function getBalance($user_id)
    {
        $investments = usersModel::selectRaw("(direct_income + roi_income + level_income + royalty + rank_bonus + club_bonus) as balance")->where(['id' => $user_id])->get()->toArray();

        $available_withdraw_balance = 0;
        $withdraw_balance = 0;

        foreach ($investments as $key => $value) {
            $available_withdraw_balance += $value['balance'];
        }

        $withdraw = withdrawModel::where(['user_id' => $user_id, 'status' => 1, 'withdraw_type' => 'USDT'])->get()->toArray();

        foreach ($withdraw as $key => $value) {
            $withdraw_balance += $value['amount'];
        }

        return ($available_withdraw_balance - $withdraw_balance);
    }
}

if (!function_exists('getLevelTeam')) {
    function getLevelTeam($user_id)
    {
        $users = usersModel::where(['sponser_id' => $user_id])->get()->toArray();

        foreach ($users as $key => $value) {
            $currentPackage = 0;
            $matchingDistributed = 0;
            $allPackages = '';
            $currentPackageDate = '-';
            $package = userPlansModel::where(['user_id' => $value['id']])->whereRaw('roi > 0 and isSynced != 2')->get()->toArray();
            $otherPackageLeft = 0;
            $otherPackageRight = 0;
            $totalInvestment = 0;

            $claimedRewards = withdrawModel::selectRaw("IFNULL(SUM(amount), 0) as amount")
            ->where('user_id', '=', $value['id'])
            ->where('withdraw_type', '=', 'UNSTAKE')
            ->get()
            ->toArray();

            foreach ($package as $k => $v) {
                $totalInvestment += ($v['amount'] + $v['compound_amount']);

                if ($v['status'] == 1) {
                    $currentPackage = $v['amount'];
                    $matchingDistributed = $v['isSynced'];
                    $currentPackageDate = $v['created_on'];
                } else {
                    $allPackages .= $v['amount'] . ",";

                    $otherPackageLeft += $v['amount'];
                }
            }

            $users[$key]['matchingDistributed'] = $matchingDistributed;
            $users[$key]['currentPackage'] = $currentPackage;
            $users[$key]['otherPackageLeft'] = $otherPackageLeft;
            $users[$key]['otherPackageRight'] = $otherPackageRight;
            $users[$key]['currentPackageDate'] = $currentPackageDate;
            $users[$key]['totalInvestment'] = ($totalInvestment - $claimedRewards['0']['amount']);
            $users[$key]['allPackages'] = rtrim($allPackages, ",");

            $users[$key]['team_investment'] = $value['my_business']; //$finalTeamActiveAmount;
            $users[$key]['direct_investment'] = $value['direct_business']; //$finalDirectActiveAmount;
            $users[$key]['team_active'] = $value['active_team']; //count($my_team_active);
            $users[$key]['direct_active'] = $value['active_direct'];
        }

        return $users;
    }
}

if (!function_exists('updateReverseBusiness')) {
    function updateReverseBusiness($user_id, $amount)
    {
        $data = usersModel::where(['id' => $user_id])->get()->toArray();

        if (count($data) > 0) {
            if ($data['0']['sponser_id'] > 0) {
                DB::statement("UPDATE users set my_business = (my_business + " . $amount . ") where id = '" . $data['0']['sponser_id'] . "'");

                updateReverseBusiness($data['0']['sponser_id'], $amount);
            }
        }
    }
}

if (!function_exists('updateActiveTeam')) {
    function updateActiveTeam($user_id)
    {
        $data = myTeamModel::where(['team_id' => $user_id])->get()->toArray();

        foreach ($data as $key => $value) {
            usersModel::where('id', $value['user_id'])->update(['active_team' => DB::raw('active_team + 1')]);
        }
    }
}


if (!function_exists('getRefferer')) {
    function getRefferer($user_id)
    {
        $checkRefferal = usersModel::selectRaw("IFNULL(sponser_id, 0) as sponser_id")->where(['id' => $user_id])->get()->toArray();

        if (isset($checkRefferal['0']['sponser_id'])) {
            if ($checkRefferal['0']['sponser_id'] == 0) {
                return 0;
            } else {
                $getLevel = usersModel::select('level')->where(['id' => $checkRefferal['0']['sponser_id']])->get()->toArray();

                $returnArray = array();
                $returnArray['sponser_id'] = $checkRefferal['0']['sponser_id'];
                $returnArray['level'] = $getLevel['0']['level'];

                return $returnArray;
            }
        } else {
            return 0;
        }
    }
}

if (!function_exists('isUserActive')) {
    function isUserActive($user_id)
    {
        $userActive = userPlansModel::where(['user_id' => $user_id])->get()->toArray();

        if (count($userActive) > 0) {
            return 1;
        } else {
            return 0;
        }
    }
}

if (!function_exists('findUplineRank')) {
    function findUplineRank($user_id, $findRank)
    {
        $getUser = usersModel::whereRaw("id = '" . $user_id . "' and rank_id > " . $findRank)->get()->toArray();

        $isEligible = 1;
        if (count($getUser) > 0) {
            $checkEligible = DB::table('user_ranks')->select('*', DB::raw('TIMESTAMPDIFF(HOUR, created_on, NOW()) AS hours_difference'))->where(['user_id' => $user_id, 'rank' => $getUser['0']['rank_id']])->get()->toArray();

            if ($checkEligible['0']->hours_difference > 23) {
                $isEligible = 1;
            } else {
                $isEligible = 0;
            }
        }


        if (count($getUser) > 0 && $isEligible == 1) {
            $data = array();
            $data['user_id'] = $user_id;
            $data['rank'] = $getUser['0']['rank'];
            $data['rank_id'] = $getUser['0']['rank_id'];

            return $data;
        } else {
            $getSponser = usersModel::where(['id' => $user_id])->get()->toArray();

            if (count($getSponser) > 0) {
                return findUplineRank($getSponser['0']['sponser_id'], $findRank);
            } else {
                $data = array();
                $data['user_id'] = $user_id;
                $data['rank'] = 0;
                $data['rank_id'] = 0;

                return $data;
            }
        }
    }
}

if (!function_exists('findRankBonusIncome')) {
    function findRankBonusIncome($lastRank, $newRank)
    {
        $data = DB::select("SELECT SUM(income) as income FROM `ranking` where id > " . $lastRank . " and id <= " . $newRank);

        return $data['0']->income;
    }
}


if (!function_exists('verifyRSVP')) {
    function verifyRSVP($signature)
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'http://147.93.106.204:3154/verify-wallet-using-vrs',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $signature,
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json'
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        return $responseData = json_decode($response, true);
    }
}

if (!function_exists('getUserMaxReturn')) {
    function getUserMaxReturn($user_id)
    {
        $investments = userPlansModel::where(['user_id' => $user_id])->get()->toArray();

        $return = 0;

        foreach ($investments as $key => $value) {
            $return += ($value['amount'] * 5);
        }

        return $return;
    }
}

if (!function_exists('getRoiMaxReturn')) {
    function getRoiMaxReturn($user_id)
    {
        $investments = userPlansModel::where(['user_id' => $user_id])->get()->toArray();

        $return = 0;

        foreach ($investments as $key => $value) {
            $return += ($value['amount'] * 2);
        }

        return $return;
    }
}

if (!function_exists('getIncome')) {
    function getIncome($user_id)
    {
        $users = usersModel::selectRaw("(direct_income + roi_income + level_income + royalty + rank_bonus + club_bonus) as balance")->where(['id' => $user_id])->get()->toArray();

        return $users['0']['balance'];
    }
}

if (!function_exists('getTeamRoi')) {
    function getTeamRoi($user_id)
    {
        $teamRoi = myTeamModel::join('user_plans', 'user_plans.user_id', '=', 'my_team.team_id')
            ->where('my_team.user_id', $user_id)
            ->where('user_plans.status', 1)
            ->groupBy('my_team.team_id') // Prevents duplicates
            ->selectRaw('my_team.team_id, SUM(user_plans.amount + user_plans.compound_amount) as total_roi')
            ->get()
            ->toArray();

        if (count($teamRoi) > 0) {
            return $teamRoi['0']['total_roi'];
        } else {
            return 0;
        }
    }
}

if (!function_exists('rtxPrice')) {
    function rtxPrice()
    {
        $claimedRewards = settingModel::get()->toArray();

        return $claimedRewards['0']['rtx_price'];
    }
}

if (!function_exists('getTreasuryBalance')) {
    function getTreasuryBalance()
    {
        $claimedRewards = settingModel::get()->toArray();

        return $claimedRewards['0']['treasury_balance'];
    }
}

if (!function_exists('unstakedAmount')) {
    function unstakedAmount($user_id, $package_id)
    {
        $claimedRewards = withdrawModel::selectRaw("IFNULL(SUM(amount), 0) as amount")
            ->where('user_id', '=', $user_id)
            ->where('package_id', '=', $package_id)
            ->where('withdraw_type', '=', "UNSTAKE")
            ->get()
            ->toArray();

        return $claimedRewards['0']['amount'];
    }
}

if (!function_exists('getUserStakeAmount')) {
    function getUserStakeAmount($user_id)
    {
        $withdrawMeta = withdrawModel::selectRaw("amount, created_on")->where(['user_id' => $user_id, 'status' => 1, 'withdraw_type' => 'UNSTAKE'])->orderBy('id', 'asc')->get()->toArray();

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

        return $activeStake;
    }
}


// if (!function_exists('getReffererShikhar')) {
//     function getReffererShikhar($user_id, $allUsers = null)
//     {
//         if ($allUsers !== null) {
//             return $allUsers[$user_id]['sponser_id'] ?? null;
//         }
//         return usersModel::where('id', $user_id)->value('sponser_id');
//     }
// }

// if (!function_exists('isUserActiveShikhar')) {
//     function isUserActiveShikhar($user_id, $allUsers = null)
//     {
//         if ($allUsers !== null) {
//             return !empty($allUsers[$user_id]['status']); // fixed variable name
//         }
//         return usersModel::where('id', $user_id)->value('status') == 1; // fixed column name
//     }
// }