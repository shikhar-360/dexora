<?php

namespace App\Http\Controllers;

use App\Models\myTeamModel;
use App\Models\userPlansModel;
use App\Models\usersModel;
use Illuminate\Http\Request;

use function App\Helpers\getLevelTeam;
use function App\Helpers\is_mobile;
use function App\Helpers\rtxPrice;
use function App\Helpers\getUserStakeAmount;
use function App\Helpers\unstakedAmount;

class teamController extends Controller
{
    public function my_team(Request $request)
    {
        $type = $request->input('type');
        $wallet_address = $request->input('wallet_address');
        $user_id = $request->session()->get('user_id');
        $perPage = 50; // You can make this dynamic from request

        if(!empty($wallet_address))
        {
            $teamMembers = myTeamModel::where('my_team.user_id', $user_id)
                ->join('users', 'my_team.team_id', '=', 'users.id')
                ->select('users.*', 'my_team.id as team_relation_id')
                ->where(function ($query) use ($wallet_address) {
                    $query->where('users.wallet_address', $wallet_address)
                          ->orWhere('users.refferal_code', $wallet_address);
                })
                ->orderByDesc('my_team.id')
                ->paginate($perPage);

            $res['wallet_address'] = $wallet_address;
        }else
        {
            $teamMembers = myTeamModel::where('my_team.user_id', $user_id)
                ->join('users', 'my_team.team_id', '=', 'users.id')
                ->select('users.*', 'my_team.id as team_relation_id')
                ->where('daily_roi', '>', '0')
                ->orderByDesc('my_team.id')
                ->paginate($perPage);
        }


        foreach ($teamMembers as $member) {
            $userId = $member->id;

            $packages = userPlansModel::where('user_id', $userId)
                ->whereRaw('roi > 0 and isSynced != 2')
                ->get();

            $unstake1 = unstakedAmount($userId, 1);
            $unstake2 = unstakedAmount($userId, 2);
            $unstake3 = unstakedAmount($userId, 3);

            $currentPackage = 0;
            $matchingDistributed = 0;
            $totalPackage = 0;
            $allPackages = [];
            $currentPackageDate = '-';
            $otherPackageLeft = 0;

            foreach ($packages as $package) {
                if ($package->status == 1) {
                    $currentPackage = $package->amount;
                    $matchingDistributed = $package->isSynced;
                    $currentPackageDate = $package->created_on;
                } else {
                    $otherPackageLeft += $package->amount;
                }
                $totalPackage += ($package->amount);
                $allPackages[] = $package->amount;
            }

            $member->matchingDistributed = $matchingDistributed;
            $member->currentPackage = $currentPackage;
            $member->otherPackageLeft = $otherPackageLeft;
            $member->otherPackageRight = 0; // No logic provided
            $member->currentPackageDate = $currentPackageDate;
            $member->totalPackage = ($totalPackage - $unstake1 - $unstake2 - $unstake3) < 0 ? 0 : ($totalPackage - $unstake1 - $unstake2 - $unstake3);
            $member->allPackages = implode(',', $allPackages);
        }

        $user = usersModel::find($user_id);
        $res['rtxPrice'] = rtxPrice();
        $res['status_code'] = 1;
        $res['message'] = "My Team";
        $res['data'] = $teamMembers;
        $res['user'] = $user;

        return is_mobile($type, "pages.total_team", $res, "view");
    }

    // public function my_team(Request $request)
    // {
    //     $type = $request->input('type');
    //     $user_id = $request->session()->get('user_id');

    //     $data = myTeamModel::selectRaw('users.*')->join('users', 'users.id', '=', 'my_team.team_id')->where(['my_team.user_id' => $user_id])->orderBy('my_team.id', 'desc')->get()->toArray();

    //     $otherPackageLeft = 0;
    //     $otherPackageRight = 0;

    //     foreach ($data as $key => $value) {
    //         $currentPackage = 0;
    //         $matchingDistributed = 0;
    //         $allPackages = '';
    //         $currentPackageDate = '-';
    //         $package = userPlansModel::where(['user_id' => $value['id']])->whereRaw("roi > 0 and isSynced != 2")->get()->toArray();
            
    //         $unstake1 = unstakedAmount($value['id'], 1);
    //         $unstake2 = unstakedAmount($value['id'], 2);
    //         $unstake3 = unstakedAmount($value['id'], 3);

    //         $totalPackage = 0;
    //         foreach ($package as $k => $v) {
    //             if ($v['status'] == 1) {
    //                 $currentPackage = $v['amount'];
    //                 $matchingDistributed = $v['isSynced'];
    //                 $currentPackageDate = $v['created_on'];
    //             } else {

    //                 $otherPackageLeft += $v['amount'];
    //             }
    //             $totalPackage += ($v['amount'] + $v['compound_amount']);
    //             $allPackages .= $v['amount'] . ",";
    //         }

    //         $data[$key]['matchingDistributed'] = $matchingDistributed;
    //         $data[$key]['currentPackage'] = $currentPackage;
    //         $data[$key]['otherPackageLeft'] = $otherPackageLeft;
    //         $data[$key]['otherPackageRight'] = $otherPackageRight;
    //         $data[$key]['currentPackageDate'] = $currentPackageDate;
    //         $data[$key]['totalPackage'] = ($totalPackage - $unstake1 - $unstake2 - $unstake3);
    //         $data[$key]['allPackages'] = rtrim($allPackages, ",");
    //     }

    //     $user = usersModel::where(['id' => $user_id])->get()->toArray();
    //     $res['rtxPrice'] = rtxPrice();

    //     $res['status_code'] = 1;
    //     $res['message'] = "My Team";
    //     $res['data'] = $data;
    //     $res['user'] = $user['0'];

    //     return is_mobile($type, "pages.total_team", $res, "view");
    // }

    public function my_directs(Request $request)
    {
        $type = $request->input('type');
        $user_id = $request->session()->get('user_id');
        $rtxPrice = rtxPrice();

        $data = usersModel::where(['sponser_id' => $user_id])->orderBy('id', 'desc')->get()->toArray();

        foreach ($data as $key => $value) {
            $totalPackage = 0;
            $currentPackage = 0;
            $allPackages = '';
            $currentPackageDate = '-';
            $unstake1 = unstakedAmount($value['id'], 1);
            $unstake2 = unstakedAmount($value['id'], 2);
            $unstake3 = unstakedAmount($value['id'], 3);
            $package = userPlansModel::where(['user_id' => $value['id']])->whereRaw('roi > 0 and isSynced != 2')->get()->toArray();

            foreach ($package as $k => $v) {
                if ($v['status'] == 1) {
                    $currentPackage = $v['amount'];
                    $currentPackageDate = $v['created_on'];
                } else {
                    $allPackages .= $v['amount'] . ",";
                }
                $totalPackage += ($v['amount']);
            }
            $data[$key]['totalPackage'] = ($totalPackage - $unstake1 - $unstake2 - $unstake3) < 0 ? 0 : ($totalPackage - $unstake1 - $unstake2 - $unstake3);
            $data[$key]['currentPackage'] = $currentPackage;
            $data[$key]['currentPackageDate'] = $currentPackageDate;
            $data[$key]['allPackages'] = rtrim($allPackages, ",");
        }

        $user = usersModel::where(['id' => $user_id])->get()->toArray();
        $res['rtxPrice'] = $rtxPrice;

        $res['status_code'] = 1;
        $res['message'] = "My Team";
        $res['data'] = $data;
        $res['user'] = $user['0'];

        $uplineBonusUsers = array();

        $users = usersModel::where(['id' => $user_id])->whereRaw(" active_direct >= 8 and direct_business >= ".(8000 / $rtxPrice))->get()->toArray();
        
        foreach ($users as $key => $value) {
            $getActiveDirects = usersModel::selectRaw("IFNULL(SUM(user_plans.amount) ,0) as db, users.id, users.refferal_code")->join('user_plans', 'user_plans.user_id', '=', 'users.id')->where(['sponser_id' => $value['id']])->groupBy("users.id")->get()->toArray();

            $criteriaMatch = 0;

            foreach ($getActiveDirects as $gadk => $gadv) {
                $stakeAmount = getUserStakeAmount($gadv['id']);
                if (($stakeAmount * $rtxPrice) >= 1000) {
                    // echo $gadv['refferal_code'] . " - " . $stakeAmount . " - " . ($stakeAmount * $rtxPrice) . "<br>";
                    $criteriaMatch++;
                }else
                {
                    // echo $gadv['refferal_code'] . " - " . $stakeAmount . " - " . ($stakeAmount * $rtxPrice) . " X <br>";
                }
            }

            if ($criteriaMatch >= 8) {
                $checkInvestment = userPlansModel::selectRaw("SUM(amount) as investment")->where(['user_id' => $value['id']])->get()->toArray();
                $stakeAmount = getUserStakeAmount($value['id']);
                if(($stakeAmount * $rtxPrice) >= 3000)
                {
                    $uplineBonusUsers[$value['sponser_id']][] = $value['id'];
                }
            }
        }

        return is_mobile($type, "pages.directs_team", $res, "view");
    }

    public function genealogy_level_team(Request $request)
    {
        $type = $request->input('type');
        if ($type == "API") {
            $refferal_code = $request->input('refferal_code');
            $getUserId = usersModel::where(['refferal_code' => $refferal_code])->get()->toArray();
            $user_id = $getUserId['0']['id'];
        } else {
            $user_id = $request->session()->get('user_id');
        }

        $data = getLevelTeam($user_id);

        foreach ($data as $key => $value) {
            $dataL2 = getLevelTeam($value['id']);

            if (count($dataL2) > 0) {
                $data[$key][$value['refferal_code']] = $dataL2;
            }
        }
        $res['rtxPrice'] = rtxPrice();

        $res['status_code'] = 1;
        $res['message'] = "Fetched Successfully.";
        $res['data'] = $data;

        return is_mobile($type, "pages.genealogy", $res, "view");
    }
}
