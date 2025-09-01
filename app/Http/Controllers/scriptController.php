<?php

namespace App\Http\Controllers;

use App\Models\earningLogsModel;
use App\Models\packageTransaction;
use App\Models\levelEarningLogsModel;
use App\Models\levelRoiModel;
use App\Models\myTeamModel;
use App\Models\rankingModel;
use App\Models\rewardBonusModel;
use App\Models\userPlansModel;
use App\Models\usersModel;
use App\Models\withdrawModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx\Rels;

use function App\Helpers\findRankBonusIncome;
use function App\Helpers\findUplineRank;
use function App\Helpers\getRefferer;
use function App\Helpers\getIncome;
use function App\Helpers\getUserMaxReturn;
use function App\Helpers\getRoiMaxReturn;
use function App\Helpers\getTeamRoi;
use function App\Helpers\isUserActive;
use function App\Helpers\rtxPrice;
use function App\Helpers\updateActiveTeam;
use function App\Helpers\updateReverseBusiness;
use function App\Helpers\unstakedAmount;
use function App\Helpers\getUserStakeAmount;
use function App\Helpers\reverseBusiness;

class scriptController extends Controller
{
    public function checkLevel(Request $request)
    {
        $rtxPrice = rtxPrice();
        $investment = userPlansModel::where(['isCount' => 0])->orderBy('id', 'asc')->get()->toArray();

        $ids_updated = array();

        foreach ($investment as $key => $value) {
            updateReverseBusiness($value['user_id'], $value['amount']);

            $checkIfFirstPackage = userPlansModel::where('user_id', $value['user_id'])->get()->toArray();

            if (count($checkIfFirstPackage) == 1) {
                updateActiveTeam($value['user_id']);
            }

            userPlansModel::where(['id' => $value['id']])->update(['isCount' => 1]);
        }

        $investmentReverse = withdrawModel::where(['isReverse' => 0])->orderBy('id', 'asc')->get()->toArray();

        foreach ($investmentReverse as $key => $value) {
            reverseBusiness($value['user_id'], $value['amount']);

            withdrawModel::where(['id' => $value['id']])->update(['isReverse' => 1]);
        }

        $excludedIds = [
            2, 3, 4, 5, 6, 7, 8, 9, 13, 14, 15, 16, 18, 19, 20, 21, 23, 25, 40, 53,
            238, 256, 260, 348, 350, 352, 1039, 1828, 2772, 2792, 5163, 5340,
            5401, 5402, 5404, 5659, 5660, 5661, 5707, 56, 65, 68, 18785
        ];

        $users = usersModel::where('status', 1)
            ->whereNotIn('id', $excludedIds)
            ->orderBy('id', 'asc')
            ->get()
            ->toArray();

        foreach ($users as $key => $value) {
            $activeUser = userPlansModel::where(['user_id' => $value['id']])->get()->toArray();
            // $unstakeByUser = withdrawModel::where(['user_id' => $value['id'], 'isReverse' => 0])->get()->toArray();
            if(count($activeUser) > 0)
            {
                $activeDirectCount = DB::select("select SUM(amount + compound_amount) AS cs, user_id from `users` inner join `user_plans` on `user_plans`.`user_id` = `users`.`id` where (`users`.`sponser_id` = ".$value['id'].") GROUP BY user_id HAVING cs >= (".(100 / $rtxPrice).")");

                $userInvestment = 0;

                foreach ($activeUser as $keyValue => $userValue) {
                    $userInvestment += ($userValue['amount'] + $userValue['compound_amount']);
                }

                $unstake1 = unstakedAmount($value['id'], 1);
                $unstake2 = unstakedAmount($value['id'], 2);
                $unstake3 = unstakedAmount($value['id'], 3);

                $userInvestment = ($userInvestment - $unstake1 - $unstake2 - $unstake3);

                $userInvestment = ($rtxPrice * $userInvestment);

                $activeDirectCount = array_map(function ($value) {
                    return (array) $value;
                }, $activeDirectCount);

                $countDirect = count($activeDirectCount);

                $levelsOpen = levelRoiModel::select('id', 'direct', 'business')
                    ->where('direct', '<=', $countDirect)
                    ->whereRaw('CAST(business AS DECIMAL(15,2)) <= CAST(? AS DECIMAL(15,2))', [$userInvestment])
                    ->orderBy('id', 'desc')
                    ->get()
                    ->toArray();
                    
                if (count($levelsOpen) > 0) {
                    // echo $value['id'].' - '.$userInvestment.' - '.count($levelsOpen).' - '.$levelsOpen['0']['id'].' - direct - '.$levelsOpen[0]['direct'].' - business - '.$levelsOpen[0]['business'].' - activeDirectCount - '.$activeDirectCount['0']['count'].' - userInvestment - '.$userInvestment.PHP_EOL;
                    usersModel::where(['id' => $value['id']])->update(['level' => $levelsOpen['0']['id']]);
                }else
                {
                    usersModel::where(['id' => $value['id']])->update(['level' => 0]);
                }
            }else
            {
                usersModel::where(['id' => $value['id']])->update(['level' => 0]);
            }

        }
    }

    public function activeTeamCalculate(Request $request)
    {
        usersModel::where(['status' => 1])->update(['active_team' => 0]);

        $userPlans = userPlansModel::select('user_id')->groupBy('user_id')->get()->toArray();

        foreach ($userPlans as $key => $value) {
            updateActiveTeam($value['user_id']);
        }
    }

    public function reverseInvestment(Request $request)
    {
        // $investment = userPlansModel::where(['status' => 2])->orderBy('id', 'asc')->get()->toArray();

        // foreach ($investment as $key => $value) {
        //     reverseBusiness($value['user_id'], $value['amount']);

        //     userPlansModel::where(['id' => $value['id']])->update(['status' => 3]);
        // }
    }

    public function checkUserRank(Request $request)
    {
        $type = $request->input('type');

        $user = usersModel::where(['status' => 1])->get()->toArray();

        foreach ($user as $key => $value) {
            $business_amount = 0;
            $investment_amount = 0;

            usersModel::where('id', $value['id'])->update([
                'rank' => null,
                'rank_id' => 0
            ]);

            $getSelfInvestment = userPlansModel::where(['user_id' => $value['id']])->get()->toArray();

            foreach ($getSelfInvestment as $gsik => $gsiv) {
                $investment_amount += $gsiv['amount'] + $gsiv['compound_amount'];
            }

            $unstake1 = unstakedAmount($value['id'], 1);
            $unstake2 = unstakedAmount($value['id'], 2);
            $unstake3 = unstakedAmount($value['id'], 3);

            $investment_amount = $investment_amount - ($unstake1 - $unstake2 - $unstake3);

            $rewardDate = $value['created_on'];

            $getLastRewardDate = earningLogsModel::where('user_id', $value['id'])->where('tag', 'REWARD-BONUS')->orderBy('id', 'desc')->get()->toArray();

            if(count($getLastRewardDate))
            {
                $rewardDate = $getLastRewardDate['0']['created_on'];
            }

            $userJoiningDate = \Carbon\Carbon::parse($rewardDate);

            $rtxPrice = rtxPrice();

            $investment_amount = ($rtxPrice * $investment_amount);

            $otherLegs = usersModel::selectRaw("(my_business + strong_business) + IFNULL(SUM(user_plans.amount), 0) as legbusiness, users.id")->leftjoin('user_plans', 'user_plans.user_id', '=', 'users.id')->where(['sponser_id' => $value['id']])->groupBy("users.id")->get()->toArray();

            // dd($otherLegs);

            foreach ($otherLegs as $olk => $olv) {
                $business_amount += $olv['legbusiness'];
            }

            $business_amount = ($rtxPrice * $business_amount);

            $checkLevel = rankingModel::whereRaw("eligible <= (".($business_amount).") and account_balance <= $investment_amount")->orderByRaw('CAST(eligible as unsigned) desc')->get()->toArray();
            // dd($checkLevel);
            if (count($checkLevel) > 0) {
                foreach ($checkLevel as $clk => $clv) {

                    $getRewardRanking = rewardBonusModel::where(['id' => $clv['id']])->get()->toArray();

                    $isEligible = 0;
                    $countBusiness = 0;
                    $remaingBusines = 0;
                    $eligible = $clv['eligible'];
                    $eligiblePerLeg = $eligible / 2;
                    $rewardAmount = $getRewardRanking['0']['income'];
                    $durationDays = $getRewardRanking['0']['days'];


                    $deadline = $userJoiningDate->copy()->addDays($durationDays);
                    $now = \Carbon\Carbon::now();
                    $finalReward = $now->lte($deadline) ? $rewardAmount : $rewardAmount / 2;

                    $otherLegs = usersModel::selectRaw("IFNULL((my_business + strong_business),0) as my_business, users.id")->leftjoin('user_plans', 'user_plans.user_id', '=', 'users.id')->where(['sponser_id' => $value['id']])->groupBy('users.id')->get()->toArray();

                    // dd($otherLegs);

                    foreach ($otherLegs as $kl => $vl) {
                        $userPlansAmount = userPlansModel::selectRaw("IFNULL(SUM(amount),0) as amount")->where(['user_id' => $vl['id']])->whereRaw("roi > 0")->get()->toArray();

                        $claimedRewards = withdrawModel::selectRaw("IFNULL(SUM(amount), 0) as amount")->where('withdraw_type', '=', 'UNSTAKE')->where('user_id', '=', $vl['id'])->get()->toArray();

                        $vl['my_business'] = (($vl['my_business'] + $userPlansAmount['0']['amount']) - $claimedRewards['0']['amount']) * $rtxPrice;

                        if($vl['my_business'] < 0)
                        {
                            $vl['my_business'] = 0;
                        }

                        // echo  $vl['id'] . " -> Business " . $vl['my_business'] . "/" . $eligiblePerLeg . " - Remaining Business" . $remaingBusines .PHP_EOL;

                        if ($vl['my_business'] >= $eligiblePerLeg) {
                            $countBusiness += $eligiblePerLeg;
                            $remaingBusines += ($vl['my_business'] - $eligiblePerLeg);
                            $isEligible = 1;
                        } else {
                            $countBusiness += $vl['my_business'];
                        }

                        // if($value['strong_business'] > 0)
                        // {
                        //         $countBusiness += $vl['my_business'];
                        // }else
                        // {
                            
                        // }
                    }

                    // echo "->" . "is" . $isEligible.PHP_EOL;;

                    // if ($isEligible == 0) {
                    //     if (($countBusiness + $remaingBusines) >= $eligible) {
                    //         $countBusiness = ($countBusiness + $remaingBusines);
                    //     } else {
                    //         $countBusiness = 0;
                    //     }
                    // }

                    // echo $countBusiness . ' - ' . $clv['id'] . ' - ' . $isEligible . PHP_EOL;

                    if ($countBusiness >= $eligible && $isEligible == 1) {

                        usersModel::where('id', $value['id'])->update([
                            'rank' => $clv['name'],
                            'rank_id' => $clv['id']
                        ]);

                        $userRank = [
                            'user_id' => $value['id'],
                            'rank'    => $clv['id'],
                            'amount'  => $clv['income'],
                            'week'    => $clv['week'],
                            'date'    => date('Y-m-d'),
                        ];

                        // Check if already exists
                        $exists = DB::table('user_ranks')
                            ->where('user_id', $userRank['user_id'])
                            ->where('rank', $userRank['rank'])
                            ->exists();

                        if (!$exists) {
                            usersModel::where('id', $value['id'])->update([
                                'rank' => $clv['name'],
                                'rank_id' => $clv['id'],
                                'rank_date' => date('Y-m-d')
                            ]);

                            DB::table('user_ranks')->insert($userRank);
                        }

                        $existing = earningLogsModel::where('user_id', $value['id'])
                            ->where('refrence_id', $clv['id'])
                            ->where('tag', 'REWARD-BONUS')
                            ->first();

                        if (!$existing) {
                            $roi = array();
                            $roi['user_id'] = $value['id'];
                            $roi['amount'] = ($finalReward / $rtxPrice);
                            $roi['tag'] = "REWARD-BONUS";
                            $roi['isCount'] = 1;
                            $roi['refrence'] = $rtxPrice;
                            $roi['refrence_id'] = $clv['id'];
                            $roi['created_on'] = date('Y-m-d H:i:s');

                            earningLogsModel::insert($roi);

                            DB::statement("UPDATE users set reward_bonus = (IFNULL(reward_bonus,0) + (".$roi['amount'].")) where id = '" . $value['id'] . "'");
                        }

                        break;
                    }
                }
            }
        }
    }

    public function checkRankForOneUser($user_id)
    {
        $user = usersModel::where(['status' => 1, 'id' => $user_id])->get()->toArray();

        foreach ($user as $key => $value) {
            $business_amount = 0;
            $investment_amount = 0;

            usersModel::where('id', $value['id'])->update([
                'rank' => null,
                'rank_id' => 0
            ]);

            $getSelfInvestment = userPlansModel::where(['user_id' => $value['id']])->get()->toArray();

            foreach ($getSelfInvestment as $gsik => $gsiv) {
                $investment_amount += $gsiv['amount'] + $gsiv['compound_amount'];
            }

            $unstake1 = unstakedAmount($value['id'], 1);
            $unstake2 = unstakedAmount($value['id'], 2);
            $unstake3 = unstakedAmount($value['id'], 3);

            $investment_amount = $investment_amount - ($unstake1 - $unstake2 - $unstake3);

            $rewardDate = $value['created_on'];

            $getLastRewardDate = earningLogsModel::where('user_id', $value['id'])->where('tag', 'REWARD-BONUS')->orderBy('id', 'desc')->get()->toArray();

            if(count($getLastRewardDate))
            {
                $rewardDate = $getLastRewardDate['0']['created_on'];
            }

            $userJoiningDate = \Carbon\Carbon::parse($rewardDate);

            $rtxPrice = rtxPrice();

            $investment_amount = ($rtxPrice * $investment_amount);

            $otherLegs = usersModel::selectRaw("(my_business + strong_business) + IFNULL(SUM(user_plans.amount), 0) as legbusiness, users.id")->leftjoin('user_plans', 'user_plans.user_id', '=', 'users.id')->where(['sponser_id' => $value['id']])->groupBy("users.id")->get()->toArray();

            // dd($otherLegs);

            foreach ($otherLegs as $olk => $olv) {
                $business_amount += $olv['legbusiness'];
            }

            $business_amount = ($rtxPrice * $business_amount);

            $checkLevel = rankingModel::whereRaw("eligible <= (".($business_amount).") and account_balance <= $investment_amount")->orderByRaw('CAST(eligible as unsigned) desc')->get()->toArray();
            // dd($checkLevel);
            if (count($checkLevel) > 0) {
                foreach ($checkLevel as $clk => $clv) {

                    $getRewardRanking = rewardBonusModel::where(['id' => $clv['id']])->get()->toArray();

                    $isEligible = 0;
                    $countBusiness = 0;
                    $remaingBusines = 0;
                    $eligible = $clv['eligible'];
                    $eligiblePerLeg = $eligible / 2;
                    $rewardAmount = $getRewardRanking['0']['income'];
                    $durationDays = $getRewardRanking['0']['days'];


                    $deadline = $userJoiningDate->copy()->addDays($durationDays);
                    $now = \Carbon\Carbon::now();
                    $finalReward = $now->lte($deadline) ? $rewardAmount : $rewardAmount / 2;

                    $otherLegs = usersModel::selectRaw("IFNULL((my_business + strong_business),0) as my_business, users.id")->leftjoin('user_plans', 'user_plans.user_id', '=', 'users.id')->where(['sponser_id' => $value['id']])->groupBy('users.id')->get()->toArray();

                    // dd($otherLegs);

                    foreach ($otherLegs as $kl => $vl) {
                        $userPlansAmount = userPlansModel::selectRaw("IFNULL(SUM(amount),0) as amount")->where(['user_id' => $vl['id']])->whereRaw("roi > 0")->get()->toArray();

                        $claimedRewards = withdrawModel::selectRaw("IFNULL(SUM(amount), 0) as amount")->where('withdraw_type', '=', 'UNSTAKE')->where('user_id', '=', $vl['id'])->get()->toArray();

                        $vl['my_business'] = (($vl['my_business'] + $userPlansAmount['0']['amount']) - $claimedRewards['0']['amount']) * $rtxPrice;

                        if($vl['my_business'] < 0)
                        {
                            $vl['my_business'] = 0;
                        }

                        // echo  $vl['id'] . " -> Business " . $vl['my_business'] . "/" . $eligiblePerLeg . " - Remaining Business" . $remaingBusines .PHP_EOL;

                        if ($vl['my_business'] >= $eligiblePerLeg) {
                            $countBusiness += $eligiblePerLeg;
                            $remaingBusines += ($vl['my_business'] - $eligiblePerLeg);
                            $isEligible = 1;
                        } else {
                            $countBusiness += $vl['my_business'];
                        }

                        // if($value['strong_business'] > 0)
                        // {
                        //         $countBusiness += $vl['my_business'];
                        // }else
                        // {
                            
                        // }
                    }

                    // echo "->" . "is" . $isEligible.PHP_EOL;;

                    // if ($isEligible == 0) {
                    //     if (($countBusiness + $remaingBusines) >= $eligible) {
                    //         $countBusiness = ($countBusiness + $remaingBusines);
                    //     } else {
                    //         $countBusiness = 0;
                    //     }
                    // }

                    // echo $countBusiness . ' - ' . $clv['id'] . ' - ' . $isEligible . PHP_EOL;

                    if ($countBusiness >= $eligible && $isEligible == 1) {

                        usersModel::where('id', $value['id'])->update([
                            'rank' => $clv['name'],
                            'rank_id' => $clv['id']
                        ]);

                        $userRank = [
                            'user_id' => $value['id'],
                            'rank'    => $clv['id'],
                            'amount'  => $clv['income'],
                            'week'    => $clv['week'],
                            'date'    => date('Y-m-d'),
                        ];

                        // Check if already exists
                        $exists = DB::table('user_ranks')
                            ->where('user_id', $userRank['user_id'])
                            ->where('rank', $userRank['rank'])
                            ->exists();

                        if (!$exists) {
                            usersModel::where('id', $value['id'])->update([
                                'rank' => $clv['name'],
                                'rank_id' => $clv['id'],
                                'rank_date' => date('Y-m-d')
                            ]);

                            DB::table('user_ranks')->insert($userRank);
                        }

                        $existing = earningLogsModel::where('user_id', $value['id'])
                            ->where('refrence_id', $clv['id'])
                            ->where('tag', 'REWARD-BONUS')
                            ->first();

                        if (!$existing) {
                            $roi = array();
                            $roi['user_id'] = $value['id'];
                            $roi['amount'] = ($finalReward / $rtxPrice);
                            $roi['tag'] = "REWARD-BONUS";
                            $roi['isCount'] = 1;
                            $roi['refrence'] = $rtxPrice;
                            $roi['refrence_id'] = $clv['id'];
                            $roi['created_on'] = date('Y-m-d H:i:s');

                            earningLogsModel::insert($roi);

                            DB::statement("UPDATE users set reward_bonus = (IFNULL(reward_bonus,0) + (".$roi['amount'].")) where id = '" . $value['id'] . "'");
                        }

                        break;
                    }
                }
            }
        }
    }

    // public function checkUserRank(Request $request)
    // {
    //     $type = $request->input('type');

    //     $user = usersModel::where(['status' => 1])->where('id', '>', 209)->get()->toArray();

    //     foreach ($user as $key => $value) {
    //         $business_amount = 0;
    //         $investment_amount = 0;

    //         echo '--------------------------------'.PHP_EOL;

    //         // usersModel::where('id', $value['id'])->update([
    //         //     'rank' => null,
    //         //     'rank_id' => 0,
    //         //     'rank_date' => null
    //         // ]);

    //         $getSelfInvestment = userPlansModel::where(['user_id' => $value['id']])->get()->toArray();

    //         foreach ($getSelfInvestment as $gsik => $gsiv) {
    //             $investment_amount += $gsiv['amount'] + $gsiv['compound_amount'];
    //         }

    //         $rtxPrice = rtxPrice();

    //         $investment_amount = ($rtxPrice * $investment_amount);

    //         $otherLegs = usersModel::selectRaw("IFNULL(my_business + SUM(user_plans.amount),0) as legbusiness")->join('user_plans', 'user_plans.user_id', '=', 'users.id')->where(['sponser_id' => $value['id']])->groupBy("users.id")->get()->toArray();

    //         echo $value['id'].' selfInvestment: '.$investment_amount.' - otherLegs: '.count($otherLegs).PHP_EOL;

    //         foreach ($otherLegs as $olk => $olv) {
    //             $business_amount += $olv['legbusiness'];
    //         }

    //         $business_amount = ($rtxPrice * $business_amount);

    //         $checkLevel = rankingModel::whereRaw("eligible <= (".($business_amount + $value['strong_business']).") and account_balance <= $investment_amount")->orderByRaw('CAST(eligible as unsigned) asc')->get()->toArray();

    //         // echo $value['id'].' business_amount: '.$business_amount+$value['strong_business'].' - checkLevel: '.count($checkLevel).PHP_EOL;
            
    //         if (count($checkLevel) > 0) {
    //             foreach ($checkLevel as $clk => $clv) {
    //                 $isEligible = 0;
    //                 $remaingBusines = 0;
    //                 $eligible = $clv['eligible'];
    //                 $eligiblePerLeg = $eligible / 2;

    //                 $otherLegs = usersModel::selectRaw("IFNULL((my_business),0) as my_business, users.id")->join('user_plans', 'user_plans.user_id', '=', 'users.id')->where(['sponser_id' => $value['id']])->groupBy('users.id')->get()->toArray();

    //                 dd($otherLegs);

    //                 $countBusiness = 0;
                    
    //                 // Handle strong business first - check if we need to add it to weak leg
    //                 $tempStrongBusiness = $value['strong_business'];
    //                 $tempWeakBusiness = $value['weak_business'];
                    
    //                 foreach ($otherLegs as $kl => $vl) {
    //                     $userPlansAmount = userPlansModel::selectRaw("IFNULL(SUM(amount),0) as amount")->where(['user_id' => $vl['id']])->whereRaw("roi > 0")->get()->toArray();
                        
    //                     $vl['my_business'] = ($vl['my_business'] + $userPlansAmount['0']['amount']) * $rtxPrice;
    //                     // Handle strong business transition logic
    //                     if($kl == 0) {
    //                         // First leg
    //                         if($tempStrongBusiness > 0) {
    //                             if($vl['my_business'] >= $tempStrongBusiness) {
    //                                 // Real business in first leg overtook fake business, keep real business
    //                                 // Strong business will be moved to weak leg
    //                                 $vl['my_business'] = $vl['my_business'];
    //                             } else {
    //                                 // Add fake business to first leg
    //                                 $vl['my_business'] = ($vl['my_business'] + $tempStrongBusiness);
    //                                 $tempStrongBusiness = 0; // Consumed
    //                             }
    //                         }
    //                     }

    //                     if($kl == 1) {
    //                         // Second leg
    //                         if($tempStrongBusiness > 0) {
    //                             // Strong business wasn't consumed by first leg, add to second leg
    //                             $vl['my_business'] = ($vl['my_business'] + $tempStrongBusiness);
    //                             $tempStrongBusiness = 0;   
    //                         } else {
    //                             // Add weak business if available
    //                             if($tempWeakBusiness > 0) {
    //                                 $vl['my_business'] = ($vl['my_business'] + $tempWeakBusiness);
    //                                 $tempWeakBusiness = 0;
    //                             }
    //                         }
    //                     }

                        
    //                     // Apply binary balancing logic
    //                     if ($vl['my_business'] >= $eligiblePerLeg) {
    //                         $countBusiness += $eligiblePerLeg;
    //                         $remaingBusines += ($vl['my_business'] - $eligiblePerLeg);
    //                         $isEligible = 1;
    //                     } else {
    //                         $countBusiness += $vl['my_business'];
    //                     }
    //                 }
    //                 echo $value['id'].' - countBusiness: '.$countBusiness.' - remaingBusines: '.$remaingBusines.' - eligiblePerLeg: '.$eligiblePerLeg.PHP_EOL;
                    
    //                 // Handle case where no legs exist but strong business should be considered
    //                 if (count($otherLegs) == 0 && $tempStrongBusiness > 0) {
    //                     // No direct legs, but strong business exists
    //                     if ($tempStrongBusiness >= $eligiblePerLeg) {
    //                         $countBusiness += $eligiblePerLeg;
    //                         $remaingBusines += ($tempStrongBusiness - $eligiblePerLeg);
    //                         $isEligible = 1;
    //                     } else {
    //                         $countBusiness += $tempStrongBusiness;
    //                     }
    //                 }

                    
    //                 // Final eligibility check
    //                 if ($isEligible == 0) {
    //                     if (($countBusiness + $remaingBusines) >= $eligible) {
    //                         $countBusiness = ($countBusiness + $remaingBusines);
    //                     } else {
    //                         $countBusiness = 0;
    //                     }
    //                 }
                    
    //                 echo $value['id'].' - isEligible: '.$isEligible.' - countBusiness: '.$countBusiness.' - remaingBusines: '.$remaingBusines.' - eligible: '.$eligible.PHP_EOL;
    //                 if ($countBusiness >= $eligible) {

    //                     usersModel::where('id', $value['id'])->update([
    //                         'rank' => $clv['name'],
    //                         'rank_id' => $clv['id']
    //                     ]);

    //                     $userRank = [
    //                         'user_id' => $value['id'],
    //                         'rank'    => $clv['id'],
    //                         'amount'  => $clv['income'],
    //                         'week'    => $clv['week'],
    //                         'date'    => date('Y-m-d'),
    //                     ];

    //                     // Check if already exists
    //                     $exists = DB::table('user_ranks')
    //                         ->where('user_id', $userRank['user_id'])
    //                         ->where('rank', $userRank['rank'])
    //                         ->exists();

    //                     if (!$exists) {
    //                         usersModel::where('id', $value['id'])->update([
    //                             'rank' => $clv['name'],
    //                             'rank_id' => $clv['id'],
    //                             'rank_date' => date('Y-m-d')
    //                         ]);

    //                         DB::table('user_ranks')->insert($userRank);
    //                     }

    //                     // break; // Uncomment if you want to stop at first qualifying rank
    //                 }

    //                 echo $value['id'].' rank: '.$clv['name'].' - '.$clv['id'].PHP_EOL;
    //             }
    //         }
    //     }
    // }

    // public function checkUserRank(Request $request)
    // {
    //     $type = $request->input('type');
    //     $user = usersModel::where(['status' => 1])->get()->toArray();

    //     foreach ($user as $key => $value) {
    //         $business_amount = 0;
    //         $investment_amount = 0;

    //         $getSelfInvestment = userPlansModel::where(['user_id' => $value['id']])->get()->toArray();

    //         foreach ($getSelfInvestment as $gsik => $gsiv) {
    //             $investment_amount += $gsiv['amount'] + $gsiv['compound_amount'];
    //         }

    //         $rtxPrice = rtxPrice();

    //         $investment_amount = ($rtxPrice * $investment_amount);

    //         $otherLegs = usersModel::selectRaw("IFNULL(my_business + SUM(user_plans.amount),0) as legbusiness")->join('user_plans', 'user_plans.user_id', '=', 'users.id')->where(['sponser_id' => $value['id']])->groupBy("users.id")->get()->toArray();

    //         foreach ($otherLegs as $olk => $olv) {
    //             $business_amount += $olv['legbusiness'];
    //         }

    //         $business_amount = ($rtxPrice * $business_amount);

    //         $checkLevel = rankingModel::whereRaw("eligible <= (".($business_amount + $value['strong_business']).") and account_balance <= $investment_amount")->orderByRaw('CAST(eligible as unsigned) asc')->get()->toArray();
            
    //         if (count($checkLevel) > 0) {
    //             foreach ($checkLevel as $clk => $clv) {
    //                 $eligible = $clv['eligible'] / 2;
    //                 $countBusiness = 0;

    //                 $otherLegs = usersModel::selectRaw("IFNULL((my_business),0) as my_business, users.id")->join('user_plans', 'user_plans.user_id', '=', 'users.id')->where(['sponser_id' => $value['id']])->groupBy('users.id')->get()->toArray();

    //                 if ($value['strong_business'] > 0) {
    //                     $strongLegBusiness = $value['strong_business'];
    //                     $otherLegsBusiness = 0;
    //                     $hasStrongerLeg = false;
                        
    //                     foreach ($otherLegs as $kl => $vl) {
    //                         $userPlansAmount = userPlansModel::selectRaw("IFNULL(SUM(amount),0) as amount")->where(['user_id' => $vl['id']])->whereRaw("roi > 0")->get()->toArray();
                            
    //                         $legBusiness = ($vl['my_business'] + $userPlansAmount['0']['amount']) * $rtxPrice;
                            
    //                         // Check if this leg business is greater than current strong business
    //                         if ($legBusiness > $strongLegBusiness && !$hasStrongerLeg) {
    //                             // This leg becomes the new strong leg
    //                             $otherLegsBusiness += $strongLegBusiness; // Add old strong business to weak side
    //                             $strongLegBusiness = $legBusiness; // This leg is now strong
    //                             $hasStrongerLeg = true;
    //                         } else {
    //                             // Add to other legs business (weak side)
    //                             $otherLegsBusiness += $legBusiness;
    //                         }
    //                     }

                        
    //                     // Calculate matching business: min(strong_leg, other_legs_total)
    //                     $countBusiness = min($strongLegBusiness, $otherLegsBusiness);                        
                        
    //                 } else {
    //                     // No strong business, calculate matching from all legs
    //                     $allLegsBusinesses = array();
                        
    //                     foreach ($otherLegs as $kl => $vl) {
    //                         $userPlansAmount = userPlansModel::selectRaw("IFNULL(SUM(amount),0) as amount")->where(['user_id' => $vl['id']])->whereRaw("roi > 0")->get()->toArray();
                            
    //                         $legBusiness = ($vl['my_business'] + $userPlansAmount['0']['amount']) * $rtxPrice;
    //                         $allLegsBusinesses[] = $legBusiness;
    //                     }
                        
    //                     if (count($allLegsBusinesses) >= 2) {
    //                         // Sort to get strongest leg and sum of others
    //                         rsort($allLegsBusinesses);
    //                         $strongestLeg = $allLegsBusinesses[0];
    //                         $otherLegsSum = array_sum(array_slice($allLegsBusinesses, 1));
    //                         $countBusiness = min($strongestLeg, $otherLegsSum);
    //                     } else if (count($allLegsBusinesses) == 1) {
    //                         // Only one leg, no matching possible
    //                         $countBusiness = 0;
    //                     } else {
    //                         // No legs
    //                         $countBusiness = 0;
    //                     }
    //                 }

    //                 // echo $value['id'].' - countBusiness: '.$countBusiness.' - eligible: '.$eligible.PHP_EOL;

    //                 if ($countBusiness >= ($eligible)) {

    //                     usersModel::where('id', $value['id'])->update([
    //                         'rank' => $clv['name'],
    //                         'rank_id' => $clv['id']
    //                     ]);

    //                     $userRank = [
    //                         'user_id' => $value['id'],
    //                         'rank'    => $clv['id'],
    //                         'amount'  => $clv['income'],
    //                         'week'    => $clv['week'],
    //                         'date'    => date('Y-m-d'),
    //                     ];

    //                     $exists = DB::table('user_ranks')
    //                         ->where('user_id', $userRank['user_id'])
    //                         ->where('rank', $userRank['rank'])
    //                         ->exists();

    //                     if (!$exists) {
    //                         usersModel::where('id', $value['id'])->update([
    //                             'rank' => $clv['name'],
    //                             'rank_id' => $clv['id'],
    //                             'rank_date' => date('Y-m-d')
    //                         ]);

    //                         DB::table('user_ranks')->insert($userRank);
    //                     }

    //                     break;
    //                 }
    //             }
    //         }
    //     }
    // }

    // public function checkUserRank(Request $request)
    // {
    //     $type = $request->input('type');
    //     $user = usersModel::where(['status' => 1])->get()->toArray();

    //     foreach ($user as $key => $value) {
    //         $business_amount = 0;
    //         $investment_amount = 0;

    //         $getSelfInvestment = userPlansModel::where(['user_id' => $value['id']])->get()->toArray();

    //         foreach ($getSelfInvestment as $gsik => $gsiv) {
    //             $investment_amount += $gsiv['amount'] + $gsiv['compound_amount'];
    //         }

    //         $rtxPrice = rtxPrice();

    //         $investment_amount = ($rtxPrice * $investment_amount);

    //         $otherLegs = usersModel::selectRaw("IFNULL(my_business + SUM(user_plans.amount),0) as legbusiness")->join('user_plans', 'user_plans.user_id', '=', 'users.id')->where(['sponser_id' => $value['id']])->groupBy("users.id")->get()->toArray();

    //         foreach ($otherLegs as $olk => $olv) {
    //             $business_amount += $olv['legbusiness'];
    //         }

    //         $business_amount = ($rtxPrice * $business_amount);

    //         $checkLevel = rankingModel::whereRaw("eligible <= (".($business_amount + $value['strong_business']).") and account_balance <= $investment_amount")->orderByRaw('CAST(eligible as unsigned) asc')->get()->toArray();
            
    //         if (count($checkLevel) > 0) {
    //             foreach ($checkLevel as $clk => $clv) {
    //                 $eligible = $clv['eligible'] / 2;
    //                 $countBusiness = 0;

    //                 $otherLegs = usersModel::selectRaw("IFNULL((my_business + strong_business),0) as my_business, users.id, users.strong_business")->join('user_plans', 'user_plans.user_id', '=', 'users.id')->where(['sponser_id' => $value['id']])->groupBy('users.id')->get()->toArray();

    //                 if ($value['strong_business'] > 0) {
    //                     $strongLegBusiness = 0;//$value['strong_business'];
    //                     $otherLegsBusiness = 0;
    //                     $hasStrongerLeg = false;
                        
    //                     foreach ($otherLegs as $kl => $vl) {
    //                         $userPlansAmount = userPlansModel::selectRaw("IFNULL(SUM(amount),0) as amount")->where(['user_id' => $vl['id']])->whereRaw("roi > 0")->get()->toArray();
                            
    //                         $legBusiness = ($vl['my_business'] + $userPlansAmount['0']['amount']) * $rtxPrice;
                            
    //                         // Check if this leg business is greater than current strong business
    //                         if (!$hasStrongerLeg) {
    //                             // This leg becomes the new strong leg
    //                             // $strongLegBusiness = ($strongLegBusiness); // Add old strong business to weak side
    //                             $strongLegBusiness += $legBusiness;
    //                             $hasStrongerLeg = true;
    //                         } else {
    //                             // Add to other legs business (weak side)
    //                             if($vl['strong_business'] > 0)
    //                             {
    //                                 $strongLegBusiness += $legBusiness;
    //                             }else
    //                             {
    //                                 $otherLegsBusiness += $legBusiness;
    //                             }
    //                         }
    //                     }

                        
    //                     // Calculate matching business: min(strong_leg, other_legs_total)
    //                     $countBusiness = min($strongLegBusiness, $otherLegsBusiness);  
    //                     // dd($countBusiness);                      
                        
    //                 } else {
    //                     // No strong business, calculate matching from all legs
    //                     $allLegsBusinesses = array();
                        
    //                     foreach ($otherLegs as $kl => $vl) {
    //                         $userPlansAmount = userPlansModel::selectRaw("IFNULL(SUM(amount),0) as amount")->where(['user_id' => $vl['id']])->whereRaw("roi > 0")->get()->toArray();
                            
    //                         $legBusiness = ($vl['my_business'] + $userPlansAmount['0']['amount']) * $rtxPrice;
    //                         $allLegsBusinesses[] = $legBusiness;
    //                     }
                        
    //                     if (count($allLegsBusinesses) >= 2) {
    //                         // Sort to get strongest leg and sum of others
    //                         rsort($allLegsBusinesses);
    //                         $strongestLeg = $allLegsBusinesses[0];
    //                         $otherLegsSum = array_sum(array_slice($allLegsBusinesses, 1));
    //                         $countBusiness = min($strongestLeg, $otherLegsSum);
    //                     } else if (count($allLegsBusinesses) == 1) {
    //                         // Only one leg, no matching possible
    //                         $countBusiness = 0;
    //                     } else {
    //                         // No legs
    //                         $countBusiness = 0;
    //                     }
    //                 }

    //                 // echo $value['id'].' - countBusiness: '.$countBusiness.' - eligible: '.$eligible.PHP_EOL;

    //                 if ($countBusiness >= ($eligible)) {

    //                     usersModel::where('id', $value['id'])->update([
    //                         'rank' => $clv['name'],
    //                         'rank_id' => $clv['id']
    //                     ]);

    //                     $userRank = [
    //                         'user_id' => $value['id'],
    //                         'rank'    => $clv['id'],
    //                         'amount'  => $clv['income'],
    //                         'week'    => $clv['week'],
    //                         'date'    => date('Y-m-d'),
    //                     ];

    //                     $exists = DB::table('user_ranks')
    //                         ->where('user_id', $userRank['user_id'])
    //                         ->where('rank', $userRank['rank'])
    //                         ->exists();

    //                     if (!$exists) {
    //                         usersModel::where('id', $value['id'])->update([
    //                             'rank' => $clv['name'],
    //                             'rank_id' => $clv['id'],
    //                             'rank_date' => date('Y-m-d')
    //                         ]);

    //                         DB::table('user_ranks')->insert($userRank);
    //                     }

    //                     $existing = earningLogsModel::where('user_id', $value['id'])
    //                         ->where('refrence_id', $clv['id'])
    //                         ->where('tag', 'REWARD-BONUS')
    //                         ->first();

    //                     if (!$existing) {
    //                         $roi = array();
    //                         $roi['user_id'] = $value['id'];
    //                         $roi['amount'] = ($finalReward / $rtxPrice);
    //                         $roi['tag'] = "REWARD-BONUS";
    //                         $roi['isCount'] = 1;
    //                         $roi['refrence'] = $rtxPrice;
    //                         $roi['refrence_id'] = $clv['id'];
    //                         $roi['created_on'] = date('Y-m-d H:i:s');

    //                         earningLogsModel::insert($roi);

    //                         DB::statement("UPDATE users set reward_bonus = (IFNULL(reward_bonus,0) + (".$roi['amount'].")) where id = '" . $value['id'] . "'");
    //                     }

    //                     // break;
    //                 }
    //             }
    //         }
    //     }
    // }

    public function starBonus(Request $request)
    {
        // $rankPercentage = [
        //     1 => 3,
        //     2 => 5,
        //     3 => 7,
        //     4 => 9,
        //     5 => 11,
        //     6 => 13,
        //     7 => 15,
        //     8 => 20,
        // ];

        $rankPercentage = [
            1 => 5,
            2 => 8,
            3 => 11,
            4 => 14,
            5 => 17,
            6 => 20,
            7 => 23,
            8 => 26,
        ];

        $users = usersModel::where('rank_id', '>', 0)->get();

        foreach ($users as $user) {
            $userRank = $user->rank_id;
            $userPercent = $rankPercentage[$userRank];
            $teamRoi = ($user->my_business * 0.0035); //getTeamRoi($user->id);
            $distributeAmount = 0;

            $directs = usersModel::where('sponser_id', $user->id)->get();

            foreach ($directs as $direct) {
                $legRoi = ($direct->my_business * 0.0035); //getTeamRoi($direct->id);
                $remainingLegRoi = $legRoi;

                $deductedTeamIds = [];
                $directIncluded = false;

                // First, check the direct himself
                if ($direct->rank_id >= $userRank) {
                    // Direct is higher/equal ranked → subtract full ROI
                    $directRoi = ($direct->my_business * 0.0035); //getTeamRoi($direct->id);
                    $teamRoi -= $directRoi;
                    $remainingLegRoi -= $directRoi;
                    $directIncluded = true;
                } elseif ($direct->rank_id > 0) {
                    // Direct is lower ranked → give differential bonus
                    $directRoi = ($direct->my_business * 0.0035); //getTeamRoi($direct->id);
                    $effectiveRoi = min($directRoi, $remainingLegRoi);
                    $diff = $userPercent - $rankPercentage[$direct->rank_id];

                    if ($diff > 0) {
                        $distributeAmount += ($effectiveRoi * $diff / 100);
                    }

                    $remainingLegRoi -= $effectiveRoi;
                    $deductedTeamIds[] = $direct->id;
                }

                // Now go through downline ranked members
                $rankedMembers = usersModel::join('my_team', 'my_team.team_id', '=', 'users.id')
                    ->where('my_team.user_id', $direct->id)
                    ->where('users.rank_id', '>', 0)
                    ->orderBy('users.rank_id', 'desc')
                    ->get();

                foreach ($rankedMembers as $rankedUser) {
                    if (in_array($rankedUser->sponser_id, $deductedTeamIds)) {
                        continue;
                    }

                    $rankedUserRoi = ($rankedUser->my_business * 0.0035); //getTeamRoi($rankedUser->id);
                    $effectiveRoi = min($rankedUserRoi, $remainingLegRoi);

                    if ($rankedUser->rank_id >= $userRank) {
                        $teamRoi -= $effectiveRoi;
                        $remainingLegRoi -= $effectiveRoi;
                    } else {
                        $diff = $userPercent - $rankPercentage[$rankedUser->rank_id];
                        if ($diff > 0) {
                            $distributeAmount += ($effectiveRoi * $diff / 100);
                        }
                        $remainingLegRoi -= $effectiveRoi;
                    }

                    $deductedTeamIds[] = $rankedUser->id;
                }

                // Remaining ROI in leg → full % bonus
                if ($remainingLegRoi > 0) {
                    $distributeAmount += ($remainingLegRoi * $userPercent / 100);
                }

                $teamRoi -= $legRoi;
            }

            // Final remaining ROI (other than directs) → full % bonus
            if ($teamRoi > 0) {
                $distributeAmount += ($teamRoi * $userPercent / 100);
            }

            $roi = [
                'user_id' => $user->id,
                'amount' => round($distributeAmount, 6),
                'tag' => "STAR-BONUS",
                'refrence' => $user->rank_id,
                'refrence_id' => $teamRoi,
                'created_on' => now(),
            ];

            earningLogsModel::insert($roi);

            DB::statement("UPDATE users SET rank_bonus = IFNULL(rank_bonus, 0) + {$roi['amount']} WHERE id = {$user->id}");
        }

    }


    public function uplineBonus(Request $request)
    {
        $rtxPrice = rtxPrice();

        $uplineBonusUsers = array();

        $users = usersModel::whereRaw(" active_direct >= 8 and direct_business >= ".(8000 / $rtxPrice))->get()->toArray();
        
        foreach ($users as $key => $value) {
            $getActiveDirects = usersModel::selectRaw("IFNULL(SUM(user_plans.amount) ,0) as db, users.id")->join('user_plans', 'user_plans.user_id', '=', 'users.id')->where(['sponser_id' => $value['id']])->groupBy("users.id")->get()->toArray();

            $criteriaMatch = 0;

            foreach ($getActiveDirects as $gadk => $gadv) {
                // $unstake1 = unstakedAmount($gadv['id'], 1);
                // $unstake2 = unstakedAmount($gadv['id'], 2);
                // $unstake3 = unstakedAmount($gadv['id'], 3);
                // $stakeAmount = ($gadv['db'] - $unstake1 - $unstake2 - $unstake3);
                $stakeAmount = getUserStakeAmount($gadv['id']);
                if (($stakeAmount * $rtxPrice) >= 1000) {
                    $criteriaMatch++;
                }
            }

            if ($criteriaMatch >= 8) {
                $checkInvestment = userPlansModel::selectRaw("SUM(amount) as investment")->where(['user_id' => $value['id']])->get()->toArray();
                // $unstake1 = unstakedAmount($value['id'], 1);
                // $unstake2 = unstakedAmount($value['id'], 2);
                // $unstake3 = unstakedAmount($value['id'], 3);
                // $stakeAmount = ($checkInvestment['0']['investment'] - $unstake1 - $unstake2 - $unstake3);
                $stakeAmount = getUserStakeAmount($value['id']);
                if(($stakeAmount * $rtxPrice) >= 3000)
                {
                    $uplineBonusUsers[$value['sponser_id']][] = $value['id'];
                }
            }
        }

        foreach($uplineBonusUsers as $sponser => $users) {

            $getEarnings = earningLogsModel::selectRaw("IFNULL(SUM(amount), 0) as earnings")
                ->where('user_id', $sponser)
                ->where('isCount', 0)
                ->where('tag', '!=', 'UPLINE-BONUS')
                ->first();

            $getLevelEarnings = levelEarningLogsModel::selectRaw("IFNULL(SUM(amount), 0) as earnings")
                ->where('user_id', $sponser)
                ->where('isCount', 0)
                ->where('tag', '!=', 'UPLINE-BONUS')
                ->first();

            $totalEarnings = $getEarnings->earnings + $getLevelEarnings->earnings;

            if ($totalEarnings > 0) {
                $bonusPool = $totalEarnings * 0.05;
                $userCount = count($users);
                $bonusPerUser = $userCount > 0 ? ($bonusPool / $userCount) : 0;

                foreach ($users as $userId) {
                    if($bonusPerUser > 0)
                    {
                        $roi = [
                            'user_id' => $userId,
                            'amount' => $bonusPerUser,
                            'tag' => 'UPLINE-BONUS',
                            'refrence' => $totalEarnings,
                            'refrence_id' => $sponser,
                            'isCount' => 1,
                            'created_on' => now()
                        ];

                        earningLogsModel::insert($roi);

                        DB::statement("UPDATE users SET direct_income = IFNULL(direct_income, 0) + {$bonusPerUser} WHERE id = '{$userId}'");
                    }
                }
            }
        }

    }

    public function dailyPoolRelease(Request $request)
    {
        $rtxPrice = rtxPrice();

        $qualifiedUsers = DB::table('user_plans')
            ->whereDate('created_on', date('Y-m-d'))
            ->whereRaw('(amount * ?) >= 120', [$rtxPrice])
            ->pluck('user_id')
            ->unique()
            ->toArray();


        $getPoolAmount = withdrawModel::selectRaw("IFNULL(SUM(daily_pool_amount), 0) as daily_pool")
            ->whereRaw("DATE_FORMAT(created_on, '%Y-%m-%d') = ?", [date('Y-m-d', strtotime('-1 day'))])
            ->get()
            ->toArray();

        $poolAmount = $getPoolAmount['0']['daily_pool']; // Example daily pool amount

        if ($poolAmount > 0) {
            if (count($qualifiedUsers) > 11) {
                $winners = collect($qualifiedUsers)->random(11);
                $amountPerWinner = $poolAmount / 11;
            } else {
                $winners = $qualifiedUsers;
                $amountPerWinner = $poolAmount / count($qualifiedUsers);
            }

            foreach ($winners as $winnerId) {
                $roi = array();
                $roi['user_id'] = $winnerId;
                $roi['amount'] = $amountPerWinner;
                $roi['tag'] = "DAILY-POOL";
                $roi['refrence'] = "-";
                $roi['refrence_id'] = "-";
                $roi['isCount'] = "1";
                $roi['created_on'] = date('Y-m-d H:i:s');

                earningLogsModel::insert($roi);

                DB::statement("UPDATE users set royalty = (IFNULL(royalty,0) + (" . $roi['amount'] . ")) where id = '" . $winnerId . "'");
            }
        }
    }

    public function monthlyPoolRelease(Request $request)
    {
        $rtxPrice = rtxPrice();
        $month = date('Y-m', strtotime('-1 month'));

        $poolAmount = withdrawModel::whereRaw("DATE_FORMAT(created_on, '%Y-%m') = ?", [$month])
            ->sum('monthly_pool_amount');

        if ($poolAmount > 0) {

            // Inner query: Top 50 investments for the month
            $innerQuery = userPlansModel::select([
                    'user_id',
                    DB::raw("(amount * {$rtxPrice}) as investment"),
                    DB::raw("(SELECT wallet_address FROM users WHERE users.id = user_plans.user_id) as wallet_address"),
                ])
                ->whereRaw("DATE_FORMAT(created_on, '%Y-%m') = ?", [$month])
                ->orderByRaw("CAST((amount * {$rtxPrice}) AS UNSIGNED) DESC")
                ->limit(50);

            // Outer query: Group by wallet_address, order by investment, limit 31
            $data = DB::table(DB::raw("({$innerQuery->toSql()}) as monthly_pool"))
                ->mergeBindings($innerQuery->getQuery())
                ->select('*')
                ->groupBy('wallet_address')
                ->orderByDesc('investment')
                ->limit(31)
                ->get();

            // If no valid users found, return
            if ($data->isEmpty()) {
                return response()->json(['message' => 'No eligible users found.'], 200);
            }



            $seventyPercent = $poolAmount * 0.7;
            $thirtyPercent = $poolAmount * 0.3;
            $thirtyUserShare = $seventyPercent / 30;

            // Distribute 70% to the top user
            $topUser = $data->first();

            earningLogsModel::insert([
                'user_id' => $topUser->user_id,
                'amount' => $thirtyPercent,
                'tag' => 'MONTHLY-POOL',
                'refrence' => '70',
                'refrence_id' => '-',
                'isCount' => 1,
                'created_on' => now(),
            ]);

            DB::statement("UPDATE users SET royalty = IFNULL(royalty, 0) + ? WHERE id = ?", [
                $thirtyPercent, $topUser->user_id
            ]);

            // Distribute 30% equally among next 30 users
            $otherUsers = $data->slice(1); // Exclude the top user

            foreach ($otherUsers as $user) {
                earningLogsModel::insert([
                    'user_id' => $user->user_id,
                    'amount' => $thirtyUserShare,
                    'tag' => 'MONTHLY-POOL',
                    'refrence' => '30',
                    'refrence_id' => '-',
                    'isCount' => 1,
                    'created_on' => now(),
                ]);

                DB::statement("UPDATE users SET royalty = IFNULL(royalty, 0) + ? WHERE id = ?", [
                    $thirtyUserShare, $user->user_id
                ]);
            }

            return response()->json(['message' => 'Monthly pool released successfully.'], 200);
        }

        return response()->json(['message' => 'No pool amount to distribute.'], 200);
    }


    // public function checkOrbitXBonus(Request $request)
    // {
    //     $type = $request->input('type');

    //     $user = usersModel::where(['status' => 1])->get()->toArray();

    //     foreach ($user as $key => $value) {
    //         $rewardDate = $value['created_on'];
    //         $business_amount = ($value['strong_business'] + $value['weak_business']);

    //         $rtxPrice = rtxPrice();

    //         $otherLegs = usersModel::selectRaw("IFNULL(my_business + SUM(user_plans.amount),0) as legbusiness")->join('user_plans', 'user_plans.user_id', '=', 'users.id')->where(['sponser_id' => $value['id']])->groupBy("users.id")->get()->toArray();

    //         foreach ($otherLegs as $olk => $olv) {
    //             $business_amount += $olv['legbusiness'];
    //         }

    //         $business_amount = ($rtxPrice * $business_amount);

    //         $getLastRewardDate = earningLogsModel::where('user_id', $value['id'])->where('tag', 'REWARD-BONUS')->get()->toArray();

    //         if(count($getLastRewardDate))
    //         {
    //             $rewardDate = $getLastRewardDate['0']['created_on'];
    //         }
            
    //         $userJoiningDate = \Carbon\Carbon::parse($rewardDate);

    //         $checkLevel = rewardBonusModel::whereRaw("eligible <= $business_amount")->orderByRaw('CAST(eligible as unsigned) desc')->get()->toArray();
    //         if (count($checkLevel) > 0) {
    //             foreach ($checkLevel as $clk => $clv) {
    //                 $isEligible = 0;
    //                 $remaingBusines = 0;
    //                 $eligible = $clv['eligible'];
    //                 $eligiblePerLeg = $eligible / 2;
    //                 $rewardAmount = $clv['income'];
    //                 $durationDays = $clv['days'];

    //                 $otherLegs = usersModel::selectRaw("IFNULL((my_business),0) as my_business,users.strong_business,users.weak_business, users.id")->join('user_plans', 'user_plans.user_id', '=', 'users.id')->where(['sponser_id' => $value['id']])->groupBy('users.id')->get()->toArray();

    //                 $countBusiness = 0;
    //                 foreach ($otherLegs as $kl => $vl) {
    //                     $userPlansAmount = userPlansModel::selectRaw("IFNULL(SUM(amount),0) as amount")->where(['user_id' => $vl['id']])->whereRaw("roi > 0")->get()->toArray();

    //                     $vl['my_business'] = ($vl['my_business'] + $userPlansAmount['0']['amount']) * $rtxPrice;

    //                     if($kl == 0)
    //                     {
    //                         if($vl['my_business'] < $value['strong_business'])
    //                         {
    //                             $vl['my_business'] = ($vl['my_business'] + $value['strong_business']);
    //                             $value['strong_business'] = 0;
    //                         }
    //                     }

    //                     if($kl == 1)
    //                     {
    //                         if($vl['strong_business'] > 0)
    //                         {
    //                             $vl['my_business'] = ($vl['my_business'] + $value['strong_business']);
    //                             $value['strong_business'] = 0;   
    //                         }else
    //                         {
    //                             if($vl['weak_business'] > 0)
    //                             {
    //                                 $vl['my_business'] = ($vl['my_business'] + $value['weak_business']);
    //                                 $value['weak_business'] = 0;
    //                             }
    //                         }
    //                     }


    //                     if ($vl['my_business'] >= $eligiblePerLeg) {
    //                         $countBusiness += $eligiblePerLeg;
    //                         $remaingBusines += ($vl['my_business'] - $eligiblePerLeg);
    //                         $isEligible = 1;
    //                     } else {
    //                         $countBusiness += $vl['my_business'];
    //                     }
    //                 }

    //                 if ($isEligible == 0) {
    //                     if (($countBusiness + $remaingBusines) >= $eligible) {
    //                         $countBusiness = ($countBusiness + $remaingBusines);
    //                     } else {
    //                         $countBusiness = 0;
    //                     }
    //                 }

    //                 $deadline = $userJoiningDate->copy()->addDays($durationDays);
    //                 $now = \Carbon\Carbon::now();
    //                 $finalReward = $now->lte($deadline) ? $rewardAmount : $rewardAmount / 2;


    //                 if ($countBusiness >= $eligible) {
    //                     $existing = earningLogsModel::where('user_id', $value['id'])
    //                         ->where('refrence', $clv['id'])
    //                         ->where('tag', 'REWARD-BONUS')
    //                         ->first();

    //                     if (!$existing) {
    //                         $roi = array();
    //                         $roi['user_id'] = $value['id'];
    //                         $roi['amount'] = ($clv['income'] / $rtxPrice);
    //                         $roi['tag'] = "REWARD-BONUS";
    //                         $roi['isCount'] = 1;
    //                         $roi['refrence'] = $rtxPrice;
    //                         $roi['refrence_id'] = $clv['id'];
    //                         $roi['created_on'] = date('Y-m-d H:i:s');

    //                         earningLogsModel::insert($roi);

    //                         DB::statement("UPDATE users set reward_bonus = (IFNULL(reward_bonus,0) + (".$roi['amount'].")) where id = '" . $value['id'] . "'");
    //                     }
    //                     // break;
    //                 }
    //             }
    //         }
    //     }
    // }

    // public function checkOrbitXBonus(Request $request)
    // {
    //     $type = $request->input('type');

    //     $user = usersModel::where(['status' => 1])->get()->toArray();

    //     foreach ($user as $key => $value) {
    //         $rewardDate = $value['created_on'];
    //         $business_amount = ($value['strong_business'] + $value['weak_business']);

    //         $rtxPrice = rtxPrice();

    //         $otherLegs = usersModel::selectRaw("IFNULL(my_business + SUM(user_plans.amount),0) as legbusiness")->join('user_plans', 'user_plans.user_id', '=', 'users.id')->where(['sponser_id' => $value['id']])->groupBy("users.id")->get()->toArray();

    //         foreach ($otherLegs as $olk => $olv) {
    //             $business_amount += $olv['legbusiness'];
    //         }

    //         $business_amount = ($rtxPrice * $business_amount);

    //         $getLastRewardDate = earningLogsModel::where('user_id', $value['id'])->where('tag', 'REWARD-BONUS')->get()->toArray();

    //         // echo 'Last reward date for user ' . $value['id'] . ': ' . json_encode($getLastRewardDate) . PHP_EOL;

    //         if(count($getLastRewardDate))
    //         {
    //             $rewardDate = $getLastRewardDate['0']['created_on'];
    //         }
            
    //         $userJoiningDate = \Carbon\Carbon::parse($rewardDate);

    //         // Changed from DESC to ASC - start from lowest rank requirements
    //         $checkLevel = rewardBonusModel::whereRaw("eligible <= $business_amount")->orderByRaw('CAST(eligible as unsigned) asc')->get()->toArray();
            
    //         if (count($checkLevel) > 0) {
    //             foreach ($checkLevel as $clk => $clv) {
    //                 $isEligible = 0;
    //                 $remaingBusines = 0;
    //                 $eligible = $clv['eligible'];
    //                 $eligiblePerLeg = $eligible / 2;
    //                 $rewardAmount = $clv['income'];
    //                 $durationDays = $clv['days'];

    //                 $otherLegs = usersModel::selectRaw("IFNULL((my_business),0) as my_business,users.strong_business,users.weak_business, users.id")->join('user_plans', 'user_plans.user_id', '=', 'users.id')->where(['sponser_id' => $value['id']])->groupBy('users.id')->get()->toArray();

    //                 $countBusiness = 0;
                    
    //                 // Handle strong business transition - use temporary variables
    //                 $tempStrongBusiness = $value['strong_business'];
    //                 $tempWeakBusiness = $value['weak_business'];
                    
    //                 foreach ($otherLegs as $kl => $vl) {
    //                     $userPlansAmount = userPlansModel::selectRaw("IFNULL(SUM(amount),0) as amount")->where(['user_id' => $vl['id']])->whereRaw("roi > 0")->get()->toArray();

    //                     $vl['my_business'] = ($vl['my_business'] + $userPlansAmount['0']['amount']) * $rtxPrice;

    //                     // Handle strong business transition logic
    //                     if($kl == 0) {
    //                         // First leg
    //                         if($tempStrongBusiness > 0) {
    //                             if($vl['my_business'] >= $tempStrongBusiness) {
    //                                 // Real business in first leg overtook fake business
    //                                 $vl['my_business'] = $vl['my_business'];
    //                                 // Strong business will be available for second leg
    //                             } else {
    //                                 // Add fake business to first leg
    //                                 $vl['my_business'] = ($vl['my_business'] + $tempStrongBusiness);
    //                                 $tempStrongBusiness = 0; // Consumed
    //                             }
    //                         }
    //                     }

    //                     if($kl == 1) {
    //                         // Second leg
    //                         if($tempStrongBusiness > 0) {
    //                             // Strong business wasn't consumed by first leg, add to second leg
    //                             $vl['my_business'] = ($vl['my_business'] + $tempStrongBusiness);
    //                             $tempStrongBusiness = 0;
    //                         } else {
    //                             // Add weak business if available
    //                             if($tempWeakBusiness > 0) {
    //                                 $vl['my_business'] = ($vl['my_business'] + $tempWeakBusiness);
    //                                 $tempWeakBusiness = 0;
    //                             }
    //                         }
    //                     }

    //                     // Apply binary balancing logic
    //                     if ($vl['my_business'] >= $eligiblePerLeg) {
    //                         $countBusiness += $eligiblePerLeg;
    //                         $remaingBusines += ($vl['my_business'] - $eligiblePerLeg);
    //                         $isEligible = 1;
    //                     } else {
    //                         $countBusiness += $vl['my_business'];
    //                     }
    //                 }

    //                 // Handle case where no legs exist but strong business should be considered
    //                 if (count($otherLegs) == 0 && $tempStrongBusiness > 0) {
    //                     if ($tempStrongBusiness >= $eligiblePerLeg) {
    //                         $countBusiness += $eligiblePerLeg;
    //                         $remaingBusines += ($tempStrongBusiness - $eligiblePerLeg);
    //                         $isEligible = 1;
    //                     } else {
    //                         $countBusiness += $tempStrongBusiness;
    //                     }
    //                 }

    //                 // Final eligibility check
    //                 if ($isEligible == 0) {
    //                     if (($countBusiness + $remaingBusines) >= $eligible) {
    //                         $countBusiness = ($countBusiness + $remaingBusines);
    //                     } else {
    //                         $countBusiness = 0;
    //                     }
    //                 }

    //                 // Calculate deadline for full reward vs half reward
    //                 $deadline = $userJoiningDate->copy()->addDays($durationDays);
    //                 $now = \Carbon\Carbon::now();
    //                 $finalReward = $now->lte($deadline) ? $rewardAmount : $rewardAmount / 2;

    //                 if ($countBusiness >= $eligible) {
    //                     $existing = earningLogsModel::where('user_id', $value['id'])
    //                         ->where('refrence_id', $clv['id'])
    //                         ->where('tag', 'REWARD-BONUS')
    //                         ->first();

    //                     if (!$existing) {
    //                         $roi = array();
    //                         $roi['user_id'] = $value['id'];
    //                         $roi['amount'] = ($finalReward / $rtxPrice); // Use finalReward instead of clv['income']
    //                         $roi['tag'] = "REWARD-BONUS";
    //                         $roi['isCount'] = 1;
    //                         $roi['refrence'] = $rtxPrice;
    //                         $roi['refrence_id'] = $clv['id'];
    //                         $roi['created_on'] = date('Y-m-d H:i:s');

    //                         earningLogsModel::insert($roi);

    //                         DB::statement("UPDATE users set reward_bonus = (IFNULL(reward_bonus,0) + (".$roi['amount'].")) where id = '" . $value['id'] . "'");
    //                     }
    //                     // Continue to check for higher ranks (don't break)
    //                 }
    //             }
    //         }
    //     }
    // }

    // public function checkOrbitXBonus(Request $request)
    // {
    //     $type = $request->input('type');

    //     $user = usersModel::where(['status' => 1])->get()->toArray();

    //     foreach ($user as $key => $value) {
    //         $rewardDate = $value['created_on'];
    //         $business_amount = ($value['strong_business'] + $value['weak_business']);

    //         $rtxPrice = rtxPrice();

    //         $otherLegs = usersModel::selectRaw("IFNULL(my_business + SUM(user_plans.amount),0) as legbusiness")->join('user_plans', 'user_plans.user_id', '=', 'users.id')->where(['sponser_id' => $value['id']])->groupBy("users.id")->get()->toArray();

    //         foreach ($otherLegs as $olk => $olv) {
    //             $business_amount += $olv['legbusiness'];
    //         }

    //         $business_amount = ($rtxPrice * $business_amount);

    //         $getLastRewardDate = earningLogsModel::where('user_id', $value['id'])->where('tag', 'REWARD-BONUS')->get()->toArray();

    //         if(count($getLastRewardDate)) {
    //             $rewardDate = $getLastRewardDate['0']['created_on'];
    //         }
            
    //         $userJoiningDate = \Carbon\Carbon::parse($rewardDate);

    //         // Start from lowest rank requirements and work up
    //         $checkLevel = rewardBonusModel::whereRaw("eligible <= $business_amount")->orderByRaw('CAST(eligible as unsigned) asc')->get()->toArray();
            
    //         if (count($checkLevel) > 0) {
    //             foreach ($checkLevel as $clk => $clv) {
    //                 $eligible = $clv['eligible'] / 2;
    //                 $rewardAmount = $clv['income'];
    //                 $durationDays = $clv['days'];
    //                 $countBusiness = 0;

    //                 $otherLegs = usersModel::selectRaw("IFNULL((my_business + strong_business),0) as my_business, users.id, users.strong_business")->join('user_plans', 'user_plans.user_id', '=', 'users.id')->where(['sponser_id' => $value['id']])->groupBy('users.id')->get()->toArray();

    //                 if ($value['strong_business'] > 0) {
    //                     $strongLegBusiness = 0;//$value['strong_business'];
    //                     $otherLegsBusiness = 0;
    //                     $hasStrongerLeg = false;
                        
    //                     foreach ($otherLegs as $kl => $vl) {
    //                         $userPlansAmount = userPlansModel::selectRaw("IFNULL(SUM(amount),0) as amount")->where(['user_id' => $vl['id']])->whereRaw("roi > 0")->get()->toArray();

    //                         $legBusiness = ($vl['my_business'] + $userPlansAmount['0']['amount']) * $rtxPrice;
                            
    //                         // Check if this leg business is greater than current strong business
    //                         if (!$hasStrongerLeg) {
    //                             // This leg becomes the new strong leg
    //                             // $otherLegsBusiness += $strongLegBusiness; // Add old strong business to weak side
    //                             $strongLegBusiness += $legBusiness; // This leg is now strong
    //                             $hasStrongerLeg = true;
    //                         } else {
    //                             // Add to other legs business (weak side)
    //                             if($vl['strong_business'] > 0)
    //                             {
    //                                 $strongLegBusiness += $legBusiness;
    //                             }else
    //                             {
    //                                 $otherLegsBusiness += $legBusiness;
    //                             }
    //                         }
    //                     }
                        
    //                     // Calculate matching business: min(strong_leg, other_legs_total)
    //                     $countBusiness = min($strongLegBusiness, $otherLegsBusiness);
                        
    //                 } else {
    //                     // No strong business, calculate matching from all legs
    //                     $allLegsBusinesses = array();
                        
    //                     foreach ($otherLegs as $kl => $vl) {
    //                         $userPlansAmount = userPlansModel::selectRaw("IFNULL(SUM(amount),0) as amount")->where(['user_id' => $vl['id']])->whereRaw("roi > 0")->get()->toArray();

    //                         $legBusiness = ($vl['my_business'] + $userPlansAmount['0']['amount']) * $rtxPrice;
    //                         $allLegsBusinesses[] = $legBusiness;
    //                     }
                        
    //                     if (count($allLegsBusinesses) >= 2) {
    //                         // Sort to get strongest leg and sum of others
    //                         rsort($allLegsBusinesses);
    //                         $strongestLeg = $allLegsBusinesses[0];
    //                         $otherLegsSum = array_sum(array_slice($allLegsBusinesses, 1));
    //                         $countBusiness = min($strongestLeg, $otherLegsSum);
    //                     } else if (count($allLegsBusinesses) == 1) {
    //                         // Only one leg, no matching possible
    //                         $countBusiness = 0;
    //                     } else {
    //                         // No legs
    //                         $countBusiness = 0;
    //                     }
    //                 }

    //                 // Calculate deadline for full reward vs half reward
    //                 $deadline = $userJoiningDate->copy()->addDays($durationDays);
    //                 $now = \Carbon\Carbon::now();
    //                 $finalReward = $now->lte($deadline) ? $rewardAmount : $rewardAmount / 2;

    //                 if ($countBusiness >= $eligible) {
    //                     $existing = earningLogsModel::where('user_id', $value['id'])
    //                         ->where('refrence_id', $clv['id'])
    //                         ->where('tag', 'REWARD-BONUS')
    //                         ->first();

    //                     if (!$existing) {
    //                         $roi = array();
    //                         $roi['user_id'] = $value['id'];
    //                         $roi['amount'] = ($finalReward / $rtxPrice);
    //                         $roi['tag'] = "REWARD-BONUS";
    //                         $roi['isCount'] = 1;
    //                         $roi['refrence'] = $rtxPrice;
    //                         $roi['refrence_id'] = $clv['id'];
    //                         $roi['created_on'] = date('Y-m-d H:i:s');

    //                         earningLogsModel::insert($roi);

    //                         DB::statement("UPDATE users set reward_bonus = (IFNULL(reward_bonus,0) + (".$roi['amount'].")) where id = '" . $value['id'] . "'");
    //                     }
    //                     // Continue to check for higher ranks (don't break)
    //                 }
    //             }
    //         }
    //     }
    // }


    public function roiRelease(Request $request)
    {
        $entryDate = date('Y-m-d H:i:s');

        $packages = userPlansModel::where('status', 1)
        ->whereRaw('roi > 0')
        ->groupBy('user_id', 'package_id')
        ->select(
            'user_id',
            'package_id',
            DB::raw('SUM(amount) as amount'),
            DB::raw('SUM(compound_amount) as compound_amount'),
            DB::raw('MAX(id) as id'),
            DB::raw('MAX(status) as status'),
            DB::raw('MAX(roi) as roi')
        )
        ->orderByDesc('id')
        ->get()
        ->toArray();

        earningLogsModel::where(['isCount' => 0])->update(['isCount' => 1]);

        levelEarningLogsModel::where(['isCount' => 0])->update(['isCount' => 1]);

        DB::statement("UPDATE users set daily_roi = 0");

        foreach ($packages as $key => $value) {
            $ogRoi = $value['roi'];
    		$packageId = $value['package_id'];
            $unstakeAmount = unstakedAmount($value['user_id'], $packageId);
            $amount = ($value['amount'] + $value['compound_amount']);
            $amount = ($amount - $unstakeAmount);
            $user_id = $value['user_id'];
            $investment_id = $value['id'];

            $levelRoi = levelRoiModel::where(['status' => 1])->get()->toArray();

            $roiUser = usersModel::where(['id' => $user_id])->get()->toArray();

            $roiLevel = array();

            foreach ($levelRoi as $key => $value) {
                $roiLevel[$value['level']] = $value['percentage'];
            }

            $today = date('Y-m-d');

            if($amount < 1){
                continue;
            }

            $roi_amount = round($final_amount = ($amount * $ogRoi) / 100, 6);

            if ($roi_amount <= 0) {
                continue;
            }

            $roi = array();
            $roi['user_id'] = $user_id;
            $roi['amount'] = $roi_amount;
            $roi['tag'] = "ROI";
            $roi['refrence'] = $amount;
            $roi['refrence_id'] = $packageId;
            $roi['created_on'] = $entryDate;

            earningLogsModel::insert($roi);

            DB::statement("UPDATE users set roi_income = (IFNULL(roi_income,0) + ($roi_amount)), daily_roi = (IFNULL(daily_roi,0) + ($roi_amount)) where id = '" . $user_id . "'");

            userPlansModel::where(['id' => $investment_id])->update(['return' => DB::raw('`return` + ' . $roi_amount), 'compound_amount' => DB::raw('`compound_amount` + ' . $roi_amount)]);
            //roi calculation end

            //level roi distribution
            $level1 = getRefferer($user_id);
            if (isset($level1['sponser_id']) && $level1['sponser_id'] > 0) {
                $userLevel1 = isUserActive($level1['sponser_id']);
                if ($level1['level'] >= 1 && $userLevel1 == 1) {
                    $level1_amount = round($final_amount = ($roi_amount * $roiLevel['1']) / 100, 6);

                    if ($level1_amount > 0) {
                        $level1_roi = array();
                        $level1_roi['user_id'] = $level1['sponser_id'];
                        $level1_roi['amount'] = $level1_amount;
                        $level1_roi['tag'] = "LEVEL1-ROI";
                        $level1_roi['refrence'] = $roiUser['0']['refferal_code'];
                        $level1_roi['refrence_id'] = $investment_id;
                        $level1_roi['created_on'] = $entryDate;

                        levelEarningLogsModel::insert($level1_roi);
                        // 
                        DB::statement("UPDATE users set level_income = (IFNULL(level_income,0) + ($level1_amount)) where id = '" . $level1['sponser_id'] . "'");
                    }
                }

                $level2 = getRefferer($level1['sponser_id']);
                if (isset($level2['sponser_id']) && $level2['sponser_id'] > 0) {
                    $userLevel2 = isUserActive($level2['sponser_id']);
                    if ($level2['level'] >= 2 && $userLevel2 == 1) {
                        $level2_amount = round($final_amount = ($roi_amount * $roiLevel['2']) / 100, 6);

                        if ($level2_amount > 0) {
                            $level2_roi = array();
                            $level2_roi['user_id'] = $level2['sponser_id'];
                            $level2_roi['amount'] = $level2_amount;
                            $level2_roi['tag'] = "LEVEL2-ROI";
                            $level2_roi['refrence'] = $roiUser['0']['refferal_code'];
                            $level2_roi['refrence_id'] = $investment_id;
                            $level2_roi['created_on'] = $entryDate;

                            levelEarningLogsModel::insert($level2_roi);
                            // 
                            DB::statement("UPDATE users set level_income = (IFNULL(level_income,0) + ($level2_amount)) where id = '" . $level2['sponser_id'] . "'");
                        }
                    }

                    $level3 = getRefferer($level2['sponser_id']);
                    if (isset($level3['sponser_id']) && $level3['sponser_id'] > 0) {
                        $userLevel3 = isUserActive($level3['sponser_id']);
                        if ($level3['level'] >= 3 && $userLevel3 == 1) {
                            $level3_amount = round($final_amount = ($roi_amount * $roiLevel['3']) / 100, 6);

                            if ($level3_amount > 0) {
                                $level3_roi = array();
                                $level3_roi['user_id'] = $level3['sponser_id'];
                                $level3_roi['amount'] = $level3_amount;
                                $level3_roi['tag'] = "LEVEL3-ROI";
                                $level3_roi['refrence'] = $roiUser['0']['refferal_code'];
                                $level3_roi['refrence_id'] = $investment_id;
                                $level3_roi['created_on'] = $entryDate;

                                levelEarningLogsModel::insert($level3_roi);
                                // 
                                DB::statement("UPDATE users set level_income = (IFNULL(level_income,0) + ($level3_amount)) where id = '" . $level3['sponser_id'] . "'");
                            }
                        }

                        $level4 = getRefferer($level3['sponser_id']);
                        if (isset($level4['sponser_id']) && $level4['sponser_id'] > 0) {
                            $userLevel4 = isUserActive($level4['sponser_id']);
                            if ($level4['level'] >= 4 && $userLevel4 == 1) {
                                $level4_amount = round($final_amount = ($roi_amount * $roiLevel['4']) / 100, 6);

                                if ($level4_amount > 0) {
                                    $level4_roi = array();
                                    $level4_roi['user_id'] = $level4['sponser_id'];
                                    $level4_roi['amount'] = $level4_amount;
                                    $level4_roi['tag'] = "LEVEL4-ROI";
                                    $level4_roi['refrence'] = $roiUser['0']['refferal_code'];
                                    $level4_roi['refrence_id'] = $investment_id;
                                    $level4_roi['created_on'] = $entryDate;

                                    levelEarningLogsModel::insert($level4_roi);
                                    // 
                                    DB::statement("UPDATE users set level_income = (IFNULL(level_income,0) + ($level4_amount)) where id = '" . $level4['sponser_id'] . "'");
                                }
                            }

                            $level5 = getRefferer($level4['sponser_id']);
                            if (isset($level5['sponser_id']) && $level5['sponser_id'] > 0) {
                                $userLevel5 = isUserActive($level5['sponser_id']);
                                if ($level5['level'] >= 5 && $userLevel5 == 1) {
                                    $level5_amount = round($final_amount = ($roi_amount * $roiLevel['5']) / 100, 6);

                                    if ($level5_amount > 0) {
                                        $level5_roi = array();
                                        $level5_roi['user_id'] = $level5['sponser_id'];
                                        $level5_roi['amount'] = $level5_amount;
                                        $level5_roi['tag'] = "LEVEL5-ROI";
                                        $level5_roi['refrence'] = $roiUser['0']['refferal_code'];
                                        $level5_roi['refrence_id'] = $investment_id;
                                        $level5_roi['created_on'] = $entryDate;

                                        levelEarningLogsModel::insert($level5_roi);
                                        // 
                                        DB::statement("UPDATE users set level_income = (IFNULL(level_income,0) + ($level5_amount)) where id = '" . $level5['sponser_id'] . "'");
                                    }
                                }

                                $level6 = getRefferer($level5['sponser_id']);
                                if (isset($level6['sponser_id']) && $level6['sponser_id'] > 0) {
                                    $userLevel6 = isUserActive($level6['sponser_id']);
                                    if ($level6['level'] >= 6 && $userLevel6 == 1) {
                                        $level6_amount = round($final_amount = ($roi_amount * $roiLevel['6']) / 100, 6);

                                        if ($level6_amount > 0) {
                                            $level6_roi = array();
                                            $level6_roi['user_id'] = $level6['sponser_id'];
                                            $level6_roi['amount'] = $level6_amount;
                                            $level6_roi['tag'] = "LEVEL6-ROI";
                                            $level6_roi['refrence'] = $roiUser['0']['refferal_code'];
                                            $level6_roi['refrence_id'] = $investment_id;
                                            $level6_roi['created_on'] = $entryDate;

                                            levelEarningLogsModel::insert($level6_roi);
                                            // 
                                            DB::statement("UPDATE users set level_income = (IFNULL(level_income,0) + ($level6_amount)) where id = '" . $level6['sponser_id'] . "'");
                                        }
                                    }

                                    $level7 = getRefferer($level6['sponser_id']);
                                    if (isset($level7['sponser_id']) && $level7['sponser_id'] > 0) {
                                        $userLevel7 = isUserActive($level7['sponser_id']);
                                        if ($level7['level'] >= 7 && $userLevel7 == 1) {
                                            $level7_amount = round($final_amount = ($roi_amount * $roiLevel['7']) / 100, 6);

                                            if ($level7_amount > 0) {
                                                $level7_roi = array();
                                                $level7_roi['user_id'] = $level7['sponser_id'];
                                                $level7_roi['amount'] = $level7_amount;
                                                $level7_roi['tag'] = "LEVEL7-ROI";
                                                $level7_roi['refrence'] = $roiUser['0']['refferal_code'];
                                                $level7_roi['refrence_id'] = $investment_id;
                                                $level7_roi['created_on'] = $entryDate;

                                                levelEarningLogsModel::insert($level7_roi);
                                                // 
                                                DB::statement("UPDATE users set level_income = (IFNULL(level_income,0) + ($level7_amount)) where id = '" . $level7['sponser_id'] . "'");
                                            }
                                        }

                                        $level8 = getRefferer($level7['sponser_id']);
                                        if (isset($level8['sponser_id']) && $level8['sponser_id'] > 0) {
                                            $userLevel8 = isUserActive($level8['sponser_id']);
                                            if ($level8['level'] >= 8 && $userLevel8 == 1) {
                                                $level8_amount = round($final_amount = ($roi_amount * $roiLevel['8']) / 100, 6);

                                                if ($level8_amount > 0) {
                                                    $level8_roi = array();
                                                    $level8_roi['user_id'] = $level8['sponser_id'];
                                                    $level8_roi['amount'] = $level8_amount;
                                                    $level8_roi['tag'] = "LEVEL8-ROI";
                                                    $level8_roi['refrence'] = $roiUser['0']['refferal_code'];
                                                    $level8_roi['refrence_id'] = $investment_id;
                                                    $level8_roi['created_on'] = $entryDate;

                                                    levelEarningLogsModel::insert($level8_roi);
                                                    // 
                                                    DB::statement("UPDATE users set level_income = (IFNULL(level_income,0) + ($level8_amount)) where id = '" . $level8['sponser_id'] . "'");
                                                }
                                            }

                                            $level9 = getRefferer($level8['sponser_id']);
                                            if (isset($level9['sponser_id']) && $level9['sponser_id'] > 0) {
                                                $userLevel9 = isUserActive($level9['sponser_id']);
                                                if ($level9['level'] >= 9 && $userLevel9 == 1) {
                                                    $level9_amount = round($final_amount = ($roi_amount * $roiLevel['9']) / 100, 6);

                                                    if ($level9_amount > 0) {
                                                        $level9_roi = array();
                                                        $level9_roi['user_id'] = $level9['sponser_id'];
                                                        $level9_roi['amount'] = $level9_amount;
                                                        $level9_roi['tag'] = "LEVEL9-ROI";
                                                        $level9_roi['refrence'] = $roiUser['0']['refferal_code'];
                                                        $level9_roi['refrence_id'] = $investment_id;
                                                        $level9_roi['created_on'] = $entryDate;

                                                        levelEarningLogsModel::insert($level9_roi);
                                                        // 
                                                        DB::statement("UPDATE users set level_income = (IFNULL(level_income,0) + ($level9_amount)) where id = '" . $level9['sponser_id'] . "'");
                                                    }
                                                }

                                                $level10 = getRefferer($level9['sponser_id']);
                                                if (isset($level10['sponser_id']) && $level10['sponser_id'] > 0) {
                                                    $userLevel10 = isUserActive($level10['sponser_id']);
                                                    if ($level10['level'] >= 10 && $userLevel10 == 1) {
                                                        $level10_amount = round($final_amount = ($roi_amount * $roiLevel['10']) / 100, 6);

                                                        if ($level10_amount > 0) {
                                                            $level10_roi = array();
                                                            $level10_roi['user_id'] = $level10['sponser_id'];
                                                            $level10_roi['amount'] = $level10_amount;
                                                            $level10_roi['tag'] = "LEVEL10-ROI";
                                                            $level10_roi['refrence'] = $roiUser['0']['refferal_code'];
                                                            $level10_roi['refrence_id'] = $investment_id;
                                                            $level10_roi['created_on'] = $entryDate;

                                                            levelEarningLogsModel::insert($level10_roi);
                                                            // 
                                                            DB::statement("UPDATE users set level_income = (IFNULL(level_income,0) + ($level10_amount)) where id = '" . $level10['sponser_id'] . "'");
                                                        }
                                                    }

                                                    $level11 = getRefferer($level10['sponser_id']);
                                                    if (isset($level11['sponser_id']) && $level11['sponser_id'] > 0) {
                                                        $userLevel11 = isUserActive($level11['sponser_id']);
                                                        if ($level11['level'] >= 11 && $userLevel11 == 1) {
                                                            $level11_amount = round($final_amount = ($roi_amount * $roiLevel['11']) / 100, 6);

                                                            if ($level11_amount > 0) {
                                                                $level11_roi = array();
                                                                $level11_roi['user_id'] = $level11['sponser_id'];
                                                                $level11_roi['amount'] = $level11_amount;
                                                                $level11_roi['tag'] = "LEVEL11-ROI";
                                                                $level11_roi['refrence'] = $roiUser['0']['refferal_code'];
                                                                $level11_roi['refrence_id'] = $investment_id;
                                                                $level11_roi['created_on'] = $entryDate;

                                                                levelEarningLogsModel::insert($level11_roi);
                                                                // 
                                                                DB::statement("UPDATE users set level_income = (IFNULL(level_income,0) + ($level11_amount)) where id = '" . $level11['sponser_id'] . "'");
                                                            }
                                                        }

                                                        $level12 = getRefferer($level11['sponser_id']);
                                                        if (isset($level12['sponser_id']) && $level12['sponser_id'] > 0) {
                                                            $userLevel12 = isUserActive($level12['sponser_id']);
                                                            if ($level12['level'] >= 12 && $userLevel12 == 1) {
                                                                $level12_amount = round($final_amount = ($roi_amount * $roiLevel['12']) / 100, 6);

                                                                if ($level12_amount > 0) {
                                                                    $level12_roi = array();
                                                                    $level12_roi['user_id'] = $level12['sponser_id'];
                                                                    $level12_roi['amount'] = $level12_amount;
                                                                    $level12_roi['tag'] = "LEVEL12-ROI";
                                                                    $level12_roi['refrence'] = $roiUser['0']['refferal_code'];
                                                                    $level12_roi['refrence_id'] = $investment_id;
                                                                    $level12_roi['created_on'] = $entryDate;

                                                                    levelEarningLogsModel::insert($level12_roi);
                                                                    // 
                                                                    DB::statement("UPDATE users set level_income = (IFNULL(level_income,0) + ($level12_amount)) where id = '" . $level12['sponser_id'] . "'");
                                                                }
                                                            }

                                                            $level13 = getRefferer($level12['sponser_id']);
                                                            if (isset($level13['sponser_id']) && $level13['sponser_id'] > 0) {
                                                                $userLevel13 = isUserActive($level13['sponser_id']);
                                                                if ($level13['level'] >= 13 && $userLevel13 == 1) {
                                                                    $level13_amount = round($final_amount = ($roi_amount * $roiLevel['13']) / 100, 6);

                                                                    if ($level13_amount > 0) {
                                                                        $level13_roi = array();
                                                                        $level13_roi['user_id'] = $level13['sponser_id'];
                                                                        $level13_roi['amount'] = $level13_amount;
                                                                        $level13_roi['tag'] = "LEVEL13-ROI";
                                                                        $level13_roi['refrence'] = $roiUser['0']['refferal_code'];
                                                                        $level13_roi['refrence_id'] = $investment_id;
                                                                        $level13_roi['created_on'] = $entryDate;

                                                                        levelEarningLogsModel::insert($level13_roi);
                                                                        // 
                                                                        DB::statement("UPDATE users set level_income = (IFNULL(level_income,0) + ($level13_amount)) where id = '" . $level13['sponser_id'] . "'");
                                                                    }
                                                                }

                                                                $level14 = getRefferer($level13['sponser_id']);
                                                                if (isset($level14['sponser_id']) && $level14['sponser_id'] > 0) {
                                                                    $userLevel14 = isUserActive($level14['sponser_id']);
                                                                    if ($level14['level'] >= 14 && $userLevel14 == 1) {
                                                                        $level14_amount = round($final_amount = ($roi_amount * $roiLevel['14']) / 100, 6);

                                                                        if ($level14_amount > 0) {
                                                                            $level14_roi = array();
                                                                            $level14_roi['user_id'] = $level14['sponser_id'];
                                                                            $level14_roi['amount'] = $level14_amount;
                                                                            $level14_roi['tag'] = "LEVEL14-ROI";
                                                                            $level14_roi['refrence'] = $roiUser['0']['refferal_code'];
                                                                            $level14_roi['refrence_id'] = $investment_id;
                                                                            $level14_roi['created_on'] = $entryDate;

                                                                            levelEarningLogsModel::insert($level14_roi);
                                                                            // 
                                                                            DB::statement("UPDATE users set level_income = (IFNULL(level_income,0) + ($level14_amount)) where id = '" . $level14['sponser_id'] . "'");
                                                                        }
                                                                    }
                                                                    
                                                                    $level15 = getRefferer($level14['sponser_id']);
                                                                    if (isset($level15['sponser_id']) && $level15['sponser_id'] > 0) {
                                                                        $userLevel15 = isUserActive($level15['sponser_id']);
                                                                        if ($level15['level'] >= 15 && $userLevel15 == 1) {
                                                                            $level15_amount = round($final_amount = ($roi_amount * $roiLevel['15']) / 100, 6);

                                                                            if ($level15_amount > 0) {
                                                                                $level15_roi = array();
                                                                                $level15_roi['user_id'] = $level15['sponser_id'];
                                                                                $level15_roi['amount'] = $level15_amount;
                                                                                $level15_roi['tag'] = "LEVEL15-ROI";
                                                                                $level15_roi['refrence'] = $roiUser['0']['refferal_code'];
                                                                                $level15_roi['refrence_id'] = $investment_id;
                                                                                $level15_roi['created_on'] = $entryDate;

                                                                                levelEarningLogsModel::insert($level15_roi);
                                                                                // 
                                                                                DB::statement("UPDATE users set level_income = (IFNULL(level_income,0) + ($level15_amount)) where id = '" . $level15['sponser_id'] . "'");
                                                                            }
                                                                        }
                                                                    }
                                                                }
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    public function roiReleaseShikhar(Request $request)
    {
        $startTime = microtime(true);

        $entryDate = now()->format('Y-m-d H:i:s');
        $maxLevels = 15;

        $roiLevel = levelRoiModel::where('status', 1)
            ->pluck('percentage', 'level')
            ->toArray();

        // print_r($roiLevel);

        $allUsers = usersModel::select('id', 'sponser_id', 'status', 'refferal_code', 'level')
            ->get()
            ->keyBy('id')
            ->toArray();
        
        print_r($allUsers);
        // dd('allUsers stop');
        $packages = userPlansModel::where('status', 1)
            ->where('roi', '>', 0)
            ->groupBy('user_id', 'package_id')
            ->select(
                'user_id',
                'package_id',
                DB::raw('SUM(amount) as amount'),
                DB::raw('SUM(compound_amount) as compound_amount'),
                DB::raw('MAX(id) as id'),
                DB::raw('MAX(status) as status'),
                DB::raw('MAX(roi) as roi')
            )
            ->orderByDesc('id')
            ->get()
            ->toArray();

        // print_r($packages);

        $levelRoi = levelRoiModel::where(['status' => 1])->get()->toArray();

        // print_r($levelRoi);
        
        $roiLevel = array();
        foreach ($levelRoi as $key => $value) {
            $roiLevel[$value['level']] = $value['percentage'];
        }
        // print_r($roiLevel);

        // dd('stop');
        
        earningLogsModel::where('isCount', 0)->update(['isCount' => 1]);
        levelEarningLogsModel::where('isCount', 0)->update(['isCount' => 1]);
        DB::statement("UPDATE users SET daily_roi = 0");

        $earningLogs = [];
        $levelLogs = [];
        $userLevelIncome = [];
        $userRoiIncome = [];

        foreach ($packages as $pkg) {

            $ogRoi = $pkg['roi'];
    		$packageId = $pkg['package_id'];
            $unstakeAmount = unstakedAmount($pkg['user_id'], $packageId);
            $amount = ($pkg['amount'] + $pkg['compound_amount']);
            $amount = ($amount - $unstakeAmount);
            $user_id = $pkg['user_id'];
            $investment_id = $pkg['id'];

            $roiUser = usersModel::where(['id' => $pkg['user_id']])->get()->toArray();
            // print_r($roiUser);

            $roi_amount = round($final_amount = ($amount * $ogRoi) / 100, 6);
            // echo  $roi_amount.PHP_EOL;
            if ($roi_amount <= 0) {
                continue;
            }
            // print($roi_amount);

            $earningLogs[] = [
                'user_id'     => $user_id,
                'amount'      => $roi_amount,
                'tag'         => "ROI",
                'refrence'    => $amount,
                'refrence_id' => $packageId,
                'created_on'  => $entryDate
            ];

            $userRoiIncome[$user_id] = ($userRoiIncome[$user_id] ?? 0) + $roi_amount;

            userPlansModel::where('id', $investment_id)
                ->update([
                    'return'          => DB::raw("`return` + {$roi_amount}"),
                    'compound_amount' => DB::raw("`compound_amount` + {$roi_amount}")
                ]);

            // print_r($roi);
            $currentId = $user_id;
            
            for ($level = 1; $level <= $maxLevels; $level++) {

                if(empty($currentId)){
                    continue;
                }

                // echo "currentId: ".$currentId.PHP_EOL;
                
                $sponsorId = $allUsers[$currentId]['sponser_id'] ?? null;
                // echo "sponsorId: ".$sponsorId.PHP_EOL;

               
                if(isset($allUsers[$sponsorId])){

                    // echo "isset sponserid: ". $allUsers[$currentId]['sponser_id'];

                    if (!$sponsorId || empty($roiLevel[$level])) {
                        break;
                    }
                
                    // echo "ALL: ".$allUsers[$sponsorId].PHP_EOL;

                    // echo 'allUsers level : '.$allUsers[$sponserId]['level'].PHP_EOL;

                    if (((int)$allUsers[$sponsorId]['level'] >= $level) && (!empty($allUsers[$sponsorId]['status'])) && ($allUsers[$sponsorId]['status'] == 1)) {
                        $level_amount = round(($roi_amount * $roiLevel[$level]) / 100, 6);
                        if ($level_amount > 0) {
                            $levelLogs[] = [
                                'user_id'     => $sponsorId,
                                'amount'      => $level_amount,
                                'tag'         => "LEVEL{$level}-ROI",
                                'refrence'    => $allUsers[$user_id]['refferal_code'],
                                'refrence_id' => $investment_id,
                                'created_on'  => $entryDate
                            ];

                            $userLevelIncome[$sponsorId] = ($userLevelIncome[$sponsorId] ?? 0) + $level_amount;
                        }
                    }

                }

                $currentId = $sponsorId;

            }

        }
        
        // print_r($levelLogs);

        $currentId = $user_id;

        // print($userLevelIncome);

        // dd('stop');

        if (!empty($earningLogs)) {
            $chunks = array_chunk($earningLogs, 500); // 500 rows at a time
            foreach ($chunks as $chunk) {
                earningLogsModel::insert($chunk);
            }
            // print_r($earningLogs);
            // earningLogsModel::insert($earningLogs);
        }

        if (!empty($levelLogs)) {
            $chunks = array_chunk($levelLogs, 500); // 500 rows at a time
            foreach ($chunks as $chunk) {
                levelEarningLogsModel::insert($chunk);
            }

            // levelEarningLogsModel::insert($levelLogs);
        }

        foreach ($userRoiIncome as $uid => $amount) {         
            usersModel::where('id', $uid)
                ->update([
                    'roi_income' => DB::raw("IFNULL(roi_income,0) + {$amount}"),
                    'daily_roi'  => DB::raw("IFNULL(daily_roi,0) + {$amount}")
                ]);
        }

        foreach ($userLevelIncome as $uid => $amount) {
            usersModel::where('id', $uid)
                ->update([
                    'level_income' => DB::raw("IFNULL(level_income,0) + {$amount}")
                ]);
        }

        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) / 60;
        echo "roiReleaseShikhar tooks : " . number_format($executionTime, 4) . " mins";
        echo "Total records:".count($packages);
        // dd('stop');

    }
}
