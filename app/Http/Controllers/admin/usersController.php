<?php

namespace App\Http\Controllers\admin;

use App\Exports\CustomQueryExport;
use App\Models\usersModel;
use App\Models\supportTicketModel;
use App\Models\transferModel;
use App\Models\packageTransaction;
use App\Models\earningLogsModel;
use App\Models\marketExcelModel;
use App\Models\profitSharingModel;
use App\Models\userPlansModel;
use App\Models\user_stablebond_details;
use App\Models\withdrawModel;
use App\Models\myTeamModel;
use App\Models\levelEarningLogsModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;

use function App\Helpers\findUplineRank;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;

use function App\Helpers\is_mobile;

class usersController extends Controller
{
    public function index(Request $request)
    {
        $type = $request->input('type');
        $search = $request->input('search');
        $start_date = $request->input('start_date');
        $end_date = $request->input('end_date');
        $rank = $request->input('rank');

        $whereStartDate = '';
        $whereEndDate = '';
        $whereRank = '';

        if ($start_date != '') {
            $whereStartDate = " AND date_format(users.created_on, '%Y-%m-%d') >= '" . date('Y-m-d', strtotime($start_date)) . "'";
        }

        if ($end_date != '') {
            $whereEndDate = " AND date_format(users.created_on, '%Y-%m-%d') <= '" . date('Y-m-d', strtotime($end_date)) . "'";
        }

        $whereRawSearch = '';

        if ($search != '') {
            $whereRawSearch = " AND (refferal_code = '" . $search . "' or wallet_address like '" . $search . "' or name like '%" . $search . "%' or email like '%" . $search . "%') ";
            $res['search'] = $search;
        }else
        {
            $search = '0xCbD3b244B73D7DEd6373B013692ff858DCD3E9FB';
            $whereRawSearch = " AND (refferal_code = '" . $search . "' or wallet_address like '" . $search . "' or name like '%" . $search . "%' or email like '%" . $search . "%') ";
            $res['search'] = $search;
        }

        if ($rank != '') {
            $whereRank = " AND rank_id = '" . $rank . "'";
            if($search == '0xCbD3b244B73D7DEd6373B013692ff858DCD3E9FB')
            {
                $whereRawSearch = '';
                $search = '';
            }
        }

        $data = usersModel::selectRaw("users.*, IFNULL(SUM(user_plans.amount), 0) as amount,user_plans.roi, (users.roi_income + users.level_income + users.royalty + users.rank_bonus) as tincome,(users.reward_bonus + users.level_income + users.royalty + users.rank_bonus + users.direct_income) as total_income, (select IFNULL(sum(amount), 0) from withdraw where user_id = users.id and status = 1 and withdraw_type = 'USDT') as twithdraw ")->leftjoin('user_plans', 'user_plans.user_id', '=', 'users.id')->whereRaw(" 1 = 1 " . $whereRawSearch . $whereStartDate . $whereEndDate . $whereRank)->groupBy('users.id')->paginate(20)->toArray();

        $data = array_map(function ($value) {
            return (array) $value;
        }, $data);

        foreach ($data['data'] as $key => $value) {
            $stake = userPlansModel::where('user_id', $value['id'])
            ->selectRaw('SUM(amount + compound_amount) as total')
            ->value('total');
            $pkg_stake = userPlansModel::where('user_id', $value['id'])->sum('amount');

            $unstake= withdrawModel::where('withdraw_type','UNSTAKE')->where('user_id',$value['id'])->sum('amount');
            
            $claimed= withdrawModel::where('status','1')->where('withdraw_type','USDT')->where('user_id',$value['id'])->sum('amount');

            $dailyPoolWinners = earningLogsModel::where('tag', 'DAILY-POOL')->where('user_id', '=', $value['id'])->sum('amount');

            $monthlyPoolWinners = earningLogsModel::where('tag', 'MONTHLY-POOL')->where('user_id', '=', $value['id'])->sum('amount');

            $data['data'][$key]['unstake'] = $unstake;
            $data['data'][$key]['totalIncome'] = $value['total_income'] + $dailyPoolWinners + $monthlyPoolWinners;
            $data['data'][$key]['avl_stake'] = $stake-$unstake;
            $data['data'][$key]['claimed'] = $claimed;
            $data['data'][$key]['pkg_stake'] = $pkg_stake;
            $data['data'][$key]['available_balance'] = ($value['tincome'] - $value['twithdraw']);
        }

        $res['status_code'] = 1;
        $res['message'] = "Success";
        $res['data'] = $data;
        $res['start_date'] = $start_date;
        $res['end_date'] = $end_date;
        $res['rank'] = $rank;

        return is_mobile($type, "users", $res, 'view');
    }

    public function userExport(Request $request)
    {
        $type = $request->input('type');
        $search = $request->input('search');
        $start_date = $request->input('start_date');
        $end_date = $request->input('end_date');

        $whereStartDate = '';
        $whereEndDate = '';

        if ($start_date != '') {
            $whereStartDate = " AND date_format(users.created_on, '%Y-%m-%d') >= '" . date('Y-m-d', strtotime($start_date)) . "'";
        }

        if ($end_date != '') {
            $whereEndDate = " AND date_format(users.created_on, '%Y-%m-%d') <= '" . date('Y-m-d', strtotime($end_date)) . "'";
        }

        $whereRawSearch = '';

        if ($search != '') {
            $whereRawSearch = " AND (refferal_code = '" . $search . "' or wallet_address like '" . $search . "' or mt5 like '" . $search . "' or mt5_name like '%" . $search . "%' or name like '%" . $search . "%' or email like '%" . $search . "%') ";
            $res['search'] = $search;
        }

        $data = usersModel::selectRaw("users.*, IFNULL(user_plans.amount, 0) as amount, (users.referral_bonus + users.profit_sharing + users.profit_sharing_level + users.rank_bonus + users.brokerage + users.royalty_pool) as tincome, (select IFNULL(sum(amount), 0) from withdraw where user_id = users.id and status = 1) as twithdraw ")->leftjoin('user_plans', 'user_plans.user_id', '=', 'users.id')->whereRaw(" 1 = 1 " . $whereRawSearch . $whereStartDate . $whereEndDate)->groupBy('users.id')->get()->toArray();

        foreach ($data as $key => $value) {
            $data[$key]['available_balance'] = ($value['tincome'] - $value['twithdraw']);
        }

        $headings = [
            'Name',
            'Email',
            'Wallet Address',
            'User Id',
            'Package',
            'Total Income',
            'Total Withdraw',
            'Available Balance',
            'MT5 Id',
            'MT5 Status',
        ];

        $finalData = array();

        foreach ($data as $key => $value) {
            $finalData[$key]['name'] = $value['name'];
            $finalData[$key]['email'] = $value['email'];
            $finalData[$key]['wallet_address'] = $value['wallet_address'];
            $finalData[$key]['refferal_code'] = $value['refferal_code'];
            $finalData[$key]['amount'] = $value['amount'];
            $finalData[$key]['tincome'] = number_format($value['tincome'], 2);
            $finalData[$key]['twithdraw'] = number_format($value['twithdraw'], 2);
            $finalData[$key]['available_balance'] = number_format($value['available_balance'], 2);
            $finalData[$key]['mt5'] = $value['mt5'];
            if ($value['mt5_verify'] == 0) {
                $finalData[$key]['mt5_status'] = "Not Submitted";
            } else if ($value['mt5_verify'] == 1) {
                $finalData[$key]['mt5_status'] = "Verified";
            } else {
                $finalData[$key]['mt5_status'] = "Rejected";
            }
        }

        return Excel::download(new CustomQueryExport($finalData, $headings), 'userExport.xlsx');
    }

    public function mt5VerifyUsers(Request $request)
    {
        $type = $request->input('type');
        $search = $request->input('search');

        $whereRawSearch = '';

        if ($search != '') {
            $whereRawSearch = " AND (refferal_code = '" . $search . "' or wallet_address like '" . $search . "' or mt5 like '" . $search . "' or mt5_name like '%" . $search . "%' or name like '%" . $search . "%' or email like '%" . $search . "%') ";
            $res['search'] = $search;
        }

        $data = usersModel::selectRaw("users.*, IFNULL(user_plans.amount, 0) as amount, (users.referral_bonus + users.profit_sharing + users.profit_sharing_level + users.rank_bonus + users.brokerage + users.royalty_pool) as tincome, (select IFNULL(sum(amount), 0) from withdraw where user_id = users.id and status = 1) as twithdraw ")->leftjoin('user_plans', 'user_plans.user_id', '=', 'users.id')->whereRaw(" 1 = 1 AND mt5_verify = 0 and mt5 is not null " . $whereRawSearch)->groupBy('users.id')->orderBy('users.id', 'desc')->paginate(20)->toArray();

        $data = array_map(function ($value) {
            return (array) $value;
        }, $data);

        foreach ($data['data'] as $key => $value) {
            $data['data'][$key]['available_balance'] = ($value['tincome'] - $value['twithdraw']);
        }

        $res['status_code'] = 1;
        $res['message'] = "Success";
        $res['data'] = $data;

        return is_mobile($type, "users", $res, 'view');
    }

    public function updateUserDetails(Request $request)
    {
        $type = $request->input('type');
        $user_id = $request->input('user_id');
        $name = $request->input('name');
        $email = $request->input('email');
        $wallet_address = $request->input('wallet_address');
        $mt5 = $request->input('mt5');
        $mt5_verify = $request->input('mt5_verify');
        $mt5_name = $request->input('mt5_name');
        $status = $request->input('status');
        $canWithdraw = $request->input('canWithdraw');
        $password = $request->input('password');

        $updateUser = array();
        if ($email != '') {
            $updateUser['email'] = $email;
        }

        if ($mt5_name != '') {
            $updateUser['mt5_name'] = $mt5_name;
        }

        if ($name != '') {
            $updateUser['name'] = $name;
        }

        if ($mt5_verify != '') {
            $updateUser['mt5_verify'] = $mt5_verify;
        }

        if ($mt5 != '') {
            $updateUser['mt5'] = $mt5;
            $updateUser['mt5_verify'] = 1;
        }

        // if($wallet_address != '')
        // {
        //     $updateUser['wallet_address'] = $wallet_address;
        // }

        if ($status != '') {
            $updateUser['status'] = $status;
        }

        if ($canWithdraw != '') {
            $updateUser['canWithdraw'] = $canWithdraw;
        }

        if ($password != '') {
            $updateUser['password'] = md5($password);
        }

        if (count($updateUser) > 0) {
            usersModel::where(['id' => $user_id])->update($updateUser);
        }

        $res['status_code'] = 1;
        $res['message'] = "Member updated successfully";

        // return is_mobile($type, "searchMember", $res);
        return redirect()->back()->with('data', $res);
    }

    public function awardIncomeProcess(Request $request)
    {
        $type = $request->input('type');
        $user_id = $request->input('user_id');
        $award_income = $request->input('award_income');

        $updateUser = array();
        $updateUser['award_income'] = $award_income;

        usersModel::where(['id' => $user_id])->update($updateUser);

        $roi = array();
        $roi['user_id'] = $user_id;
        $roi['amount'] = $award_income;
        $roi['tag'] = "award_income";
        $roi['status'] = 1;
        $roi['refrence'] = "1";
        $roi['refrence_id'] = $award_income;
        $roi['flush_amount'] = 0;
        $roi['created_on'] = date('Y-m-d H:i:s');
        earningLogsModel::insert($roi);

        $res['status_code'] = 1;
        $res['message'] = "Award Income Updated Successfully";

        return redirect()->back()->with('data', $res);
    }

    public function replySupportTickets(Request $request)
    {
        $type = $request->input('type');
        $ticket_id = $request->input('ticket_id');
        $reply = $request->input('reply');

        if ($ticket_id != '') {
            supportTicketModel::where(['id' => $ticket_id])->update(['reply' => $reply, 'status' => 2, 'reply_on' => date('Y-m-d H:i:s')]);
        }

        $res['status_code'] = 1;
        $res['message'] = "Support ticket updated successfully";

        return is_mobile($type, "memberSupportTickets", $res);
    }

    public function memberSupportTickets(Request $request)
    {
        $type = $request->input('type');

        $data = supportTicketModel::where(['status' => 0])->get()->toArray();
        $closed = supportTicketModel::where(['status' => 2])->get()->toArray();

        $res['status_code'] = 1;
        $res['message'] = "Member updated successfully";
        $res['data'] = $data;
        $res['closed'] = $closed;

        return is_mobile($type, "support_tickets", $res, 'view');
    }

    public function membersReport(Request $request)
    {
        $type = $request->input('type');
        $report_type = $request->input('report_type');
        $start_date = $request->input('start_date');
        $end_date = $request->input('end_date');
        $refferal_code = $request->input('refferal_code');

        if ($report_type == 1) {
            $where = " tag = 'ROI'";
        }
        if ($report_type == 2) {
            $where = " tag = 'REFERRAL'";
        }
        if ($report_type == 3) {
            $where = " tag IN ('LEVEL1-ROI', 'LEVEL2-ROI', 'LEVEL3-ROI', 'LEVEL4-ROI','LEVEL5-ROI','LEVEL6-ROI','LEVEL7-ROI','LEVEL8-ROI','LEVEL9-ROI','LEVEL10-ROI')";
        }
        if ($report_type == 4) {
            $where = " tag = 'DIRECT-MATCHING'";
        }
        if ($report_type == 5) {
            $where = " tag IN ('LEVEL-1-MATCHING','LEVEL-2-MATCHING','LEVEL-3-MATCHING','LEVEL-4-MATCHING','LEVEL-5-MATCHING','LEVEL-6-MATCHING','LEVEL-7-MATCHING','LEVEL-8-MATCHING','LEVEL-9-MATCHING','LEVEL-10-MATCHING')";
        }

        $whereStartDate = '';
        $whereEndDate = '';
        $whereRC = '';

        if ($start_date != '') {
            $whereStartDate = " AND date_format(earning_logs.created_on, '%Y-%m-%d') >= '" . date('Y-m-d', strtotime($start_date)) . "'";
        }

        if ($end_date != '') {
            $whereEndDate = " AND date_format(earning_logs.created_on, '%Y-%m-%d') <= '" . date('Y-m-d', strtotime($end_date)) . "'";
        }

        if ($refferal_code != '') {
            $whereRC = "  AND users.refferal_code = '" . $refferal_code . "' ";
        }

        $data = DB::select("SELECT users.name, users.refferal_code, users.sponser_code, earning_logs.amount, earning_logs.flush_amount, earning_logs.created_on as dateofearning, users.created_on FROM earning_logs  INNER JOIN users on earning_logs.user_id = users.id WHERE " . $where . $whereStartDate . $whereEndDate . $whereRC);

        $data = array_map(function ($value) {
            return (array) $value;
        }, $data);

        $list = array(
            ['Member Name', 'Member Code', 'Sponsor Code', 'Amount', 'Flush Amount', 'Date', 'Joining Date']
        );

        // Open a file in write mode ('w')
        $fp = fopen('/var/www/ai/exports/export.csv', 'w');

        // Loop through file pointer and a line
        foreach ($list as $fields) {
            fputcsv($fp, $fields);
        }

        foreach ($data as $key => $value) {
            fputcsv($fp, $value);
            // fputcsv($fp, $value['refferal_code']);   
            // fputcsv($fp, $value['sponser_code']);   
            // fputcsv($fp, $value['amount']);   
            // fputcsv($fp, $value['flush_amount']);   
            // fputcsv($fp, date('d-m-Y', strtotime($value['dateofearning'])));   
            // fputcsv($fp, date('d-m-Y', strtotime($value['created_on'])));   
        }

        fclose($fp);

        $data = earningLogsModel::selectRaw("users.*,earning_logs.amount,earning_logs.flush_amount,earning_logs.created_on as dateofearning")->join('users', 'users.id', '=', 'earning_logs.user_id')->whereRaw($where . $whereStartDate . $whereEndDate . $whereRC)->paginate(20)->toArray();

        // dd($data);

        $res['status_code'] = 1;
        $res['message'] = "Report generated successfully";
        $res['data'] = $data;
        $res['report_type'] = $report_type;
        $res['start_date'] = $start_date;
        $res['end_date'] = $end_date;
        $res['refferal_code'] = $refferal_code;

        return is_mobile($type, "report", $res, 'view');
    }

    public function investmentReport(Request $request)
    {
        $type = $request->input('type');
        $start_date = $request->input('start_date');
        $end_date = $request->input('end_date');
        $refferal_code = $request->input('refferal_code');
        $txn_type = $request->input('txn_type');
        $isExport = $request->input('export') === 'yes';

        $whereStartDate = '';
        $whereEndDate = '';
        $whereRC = '';
        $wheretxc = '';

        if ($start_date != '') {
            $whereStartDate = " AND date_format(user_plans.created_on, '%Y-%m-%d') >= '" . date('Y-m-d', strtotime($start_date)) . "'";
        }

        if ($end_date != '') {
            $whereEndDate = " AND date_format(user_plans.created_on, '%Y-%m-%d') <= '" . date('Y-m-d', strtotime($end_date)) . "'";
        }

        if ($refferal_code != '') {
            $whereRC = "  AND users.wallet_address = '" . $refferal_code . "' ";
        }
        // if ($txn_type != '') {
        //     $wheretxc = "  AND package_transaction.wallet = '" . $txn_type . "' ";
        // }
        $query = "SELECT users.*, 
        user_plans.amount, 
        user_plans.created_on AS dateofearning, 
        user_plans.transaction_hash, 
        user_plans.isSynced, 
        user_plans.created_on
        FROM user_plans
        JOIN users ON users.id = user_plans.user_id
        WHERE 1 = 1  " . $whereStartDate . $whereEndDate . $whereRC;


        if ($isExport) {
            $data = DB::select($query);

            $data = array_map(function ($value) {
                return (array) $value;
            }, $data);
            $list = [
                ['Member Name', 'Member Code', 'Admin Remark', 'Sponsor Code', 'Amount', 'Date', 'Joining Date']
            ];
            // Export file path
            $filePath = '/var/www/html/exports/investment_reports.csv';
            // Open file for writing
            $fp = fopen($filePath, 'w');
            foreach ($list as $fields) {
                fputcsv($fp, $fields);
            }
            foreach ($data as $value) {
                if ($value['transaction_hash'] == "By Other") {
                    $packageTransaction = packageTransaction::whereRaw("date_format(created_on, '%Y-%m-%d') = '" . date('Y-m-d', strtotime($value['created_on'])) . "' AND transaction_hash = '" . $value['transaction_hash'] . "' and user_id = '" . $value['id'] . "'")->get()->toArray();

                    if (count($packageTransaction) > 0) {
                        $value['proof'] = $packageTransaction['0']['proof'];
                        $value['remarks'] = $packageTransaction['0']['remarks'];
                    } else {
                        $value['proof'] = "-";
                        $value['remarks'] = "-";
                    }
                } else {
                    $value['proof'] = "-";
                    $value['remarks'] = "-";
                }

                $value['amt'] = $value['amount'];
                

                $dataRows = [
                    $value['name'],
                    $value['refferal_code'],
                    $value['remarks'],
                    $value['sponser_code'],
                    $value['amt'],
                    // $value['wallet_amount'],
                    isset($value['dateofearning']) ? date('d-m-Y', strtotime($value['dateofearning'])) : '',
                    isset($value['created_on']) ? date('d-m-Y', strtotime($value['created_on'])) : '',
                ];
                fputcsv($fp, $dataRows);
            }

            fclose($fp);
            return response()->download($filePath)->deleteFileAfterSend(true);
        }

        $data = userPlansModel::selectRaw("users.*,user_plans.amount,user_plans.created_on as dateofearning, user_plans.transaction_hash, user_plans.isSynced, user_plans.created_on")->join('users', 'users.id', '=', 'user_plans.user_id')->whereRaw("1 = 1  " . $whereStartDate . $whereEndDate . $whereRC)->paginate(20)->toArray();

        foreach ($data['data'] as $key => $value) {
            if ($value['transaction_hash'] == "By Other") {
                $packageTransaction = packageTransaction::whereRaw("date_format(created_on, '%Y-%m-%d') = '" . date('Y-m-d', strtotime($value['created_on'])) . "' AND transaction_hash = '" . $value['transaction_hash'] . "' and user_id = '" . $value['id'] . "'")->get()->toArray();
                $transaction_hash = 'BANK';

                if (count($packageTransaction) > 0) {
                    $data['data'][$key]['proof'] = $packageTransaction['0']['proof'];
                    $data['data'][$key]['remarks'] = $packageTransaction['0']['remarks'];
                } else {
                    $data['data'][$key]['proof'] = "-";
                    $data['data'][$key]['remarks'] = "-";
                }
                $data['data'][$key]['txn_hash'] = $transaction_hash;
                // $data['data'][$key]['wallet_amount'] = $value['wallet_amount'];
            } else {
                if ($value['transaction_hash'] == "By Admin") {
                    $transaction_hash = 'ADMIN';
                } else {
                    if ($value['transaction_hash'] == "-") {
                        $transaction_hash = 'USDT';
                    } else {
                        // $packageTransaction = packageTransaction::whereRaw("transaction_hash = '" . $value['transaction_hash'] . "' and user_id = '" . $value['id'] . "'")->first();
                        // if ($packageTransaction->wallet == 'USDT') {
                        //     $transaction_hash = 'USDT';
                        // } else {
                        //     $transaction_hash = $packageTransaction->wallet;
                        // }
                        $transaction_hash = $value['transaction_hash'];
                    }
                }
                $data['data'][$key]['proof'] = "-";
                $data['data'][$key]['remarks'] = "-";
                $data['data'][$key]['txn_hash'] = $transaction_hash;
                // $data['data'][$key]['wallet_amount'] = $value['wallet_amount'];
            }
        }

        $res['status_code'] = 1;
        $res['message'] = "Report generated successfully";
        $res['data'] = $data;
        $res['start_date'] = $start_date;
        $res['end_date'] = $end_date;
        $res['refferal_code'] = $refferal_code;
        $res['txn_type'] = $txn_type;

        return is_mobile($type, "investment_report", $res, 'view');
    }

    public function withdrawReport(Request $request)
    {
        $type = $request->input('type');
        $start_date = $request->input('start_date');
        $end_date = $request->input('end_date');
        $refferal_code = $request->input('refferal_code');
        $isExport = $request->input('export') === 'yes';

        $whereStartDate = '';
        $whereEndDate = '';
        $whereRC = '';

        if ($start_date != '') {
            $whereStartDate = " AND date_format(withdraw.created_on, '%Y-%m-%d') >= '" . date('Y-m-d', strtotime($start_date)) . "'";
        }

        if ($end_date != '') {
            $whereEndDate = " AND date_format(withdraw.created_on, '%Y-%m-%d') <= '" . date('Y-m-d', strtotime($end_date)) . "'";
        }

        if ($refferal_code != '') {
            $whereRC = "  AND users.wallet_address = '" . $refferal_code . "' ";
        }
        $query = "SELECT users.*, 
        withdraw.amount, 
        withdraw.created_on AS dateofearning, 
        withdraw.claim_hash, 
        withdraw.withdraw_type, 
        withdraw.package_id, 
        withdraw.status
        FROM withdraw
        JOIN users ON users.id = withdraw.user_id
        WHERE 1 = 1 " . $whereStartDate . $whereEndDate . $whereRC;


        if ($isExport) {
            $data = DB::select($query);
            $data = array_map(function ($value) {
                return (array) $value;
            }, $data);

            $list = [
                ['Member Name', 'Member Code', 'Sponsor Code', 'Amount', 'Withdraw Type', 'Transaction Hash', 'Date', 'Joining Date', 'Status']
            ];
            $filePath = '/var/www/html/exports/withdraw_report.csv';
            $fp = fopen($filePath, 'w');
            foreach ($list as $fields) {
                fputcsv($fp, $fields);
            }
            foreach ($data as $value) {
                if ($value['status'] == 1) {
                    $Type = 'Complete';
                } elseif ($value['status'] == 2) {
                    $Type = 'Failed';
                } elseif ($value['status'] == 0) {
                    $Type = 'Pending';
                }
                if ($value['withdraw_type'] == 'USDT') {
                    $wihtdraw_Type = 'USDT';
                } elseif ($value['withdraw_type'] == 'UNSTAKE' && $value['package_id'] == 1) {
                    $wihtdraw_Type = 'Stake';
                } elseif ($value['withdraw_type'] == 'UNSTAKE' && $value['package_id'] == 2) {
                    $wihtdraw_Type = 'Lpbonds';
                } elseif ($value['withdraw_type'] == 'UNSTAKE' && $value['package_id'] == 3) {
                    $wihtdraw_Type = 'Stablebond';
                } 
                $dataRows = [
                    $value['name'],
                    $value['refferal_code'],
                    $value['sponser_code'],
                    $value['amount'],
                    $wihtdraw_Type,
                    $value['claim_hash'],
                    isset($value['dateofearning']) ? date('d-m-Y', strtotime($value['dateofearning'])) : '',
                    isset($value['created_on']) ? date('d-m-Y', strtotime($value['created_on'])) : '',
                    $Type,

                ];
                fputcsv($fp, $dataRows);
            }

            fclose($fp);
            return response()->download($filePath)->deleteFileAfterSend(true);
        }


        //     $data = DB::select("SELECT users.name, users.refferal_code, users.sponser_code, withdraw.amount, withdraw.created_on as dateofearning, users.created_on,withdraw.withdraw_type, withdraw.transaction_hash, CASE
        //     WHEN withdraw.status = 2 THEN 'Failed'
        //     WHEN withdraw.status = 1 THEN 'Success'
        //     ELSE 'Pending'
        // END AS status_text FROM withdraw INNER JOIN users on withdraw.user_id = users.id WHERE 1 = 1 ".$whereStartDate.$whereEndDate.$whereRC);

        //     $data = array_map(function ($value) {
        //         return (array) $value;
        //     }, $data);

        //     $list = array(
        //         ['Member Name', 'Member Code', 'MT5', 'Sponsor Code', 'Amount', 'Date', 'Joining Date', 'Type', 'Wallet', 'Transaction Hash', 'Status']
        //     );

        //     $fp = fopen('/var/www/ai/exports/withdraw.csv', 'w');

        //     foreach ($list as $fields) {
        //         fputcsv($fp, $fields);
        //     }

        //     foreach($data as $key => $value)
        //     {
        //         fputcsv($fp, $value);  
        //     }

        //     fclose($fp);

        $data = withdrawModel::selectRaw("users.*,withdraw.amount,withdraw.package_id,withdraw.created_on as dateofearning,withdraw.claim_hash,withdraw.withdraw_type, withdraw.status")->join('users', 'users.id', '=', 'withdraw.user_id')->whereRaw("1 = 1  " . $whereStartDate . $whereEndDate . $whereRC)->orderBy('id','Desc')->paginate(20)->toArray();

        $res['status_code'] = 1;
        $res['message'] = "Report generated successfully";
        $res['data'] = $data;
        $res['start_date'] = $start_date;
        $res['end_date'] = $end_date;
        $res['refferal_code'] = $refferal_code;

        return is_mobile($type, "withdraw_report", $res, 'view');
    }

    public function userDetails(Request $request)
    {
        $type = $request->input('type');
        $refferal_code = $request->input('member_code');

        if ($refferal_code != null) {
            $data = usersModel::where(['refferal_code' => $refferal_code])->get()->toArray();

            $withdraw = withdrawModel::selectRaw('IFNULL(SUM(amount),0) as amount')->where(['user_id' => $data['0']['id']])->where(['status' => 1])->get()->toArray();

            $pendingWithdraw = withdrawModel::selectRaw('IFNULL(SUM(amount),0) as amount')->where(['user_id' => $data['0']['id']])->where(['status' => 0])->get()->toArray();

            $packages = userPlansModel::where(['user_id' => $data['0']['id']])->get()->toArray();

            $res['status_code'] = 1;
            $res['message'] = "User details successfully";
            $res['data'] = $data['0'];
            $res['withdraw'] = $withdraw['0']['amount'];
            $res['pending_withdraw'] = $pendingWithdraw['0']['amount'];
            $res['packages'] = $packages;
            $res['member_code'] = $refferal_code;
        } else {
            $res['status_code'] = 1;
            $res['message'] = "User details successfully";
        }


        return is_mobile($type, "user_details", $res, 'view');
    }

    public function userExportReport(Request $request)
    {
        $type = $request->input('type');
        $refferal_code = $request->input('refferal_code');
        $whereRC = '';

        if ($refferal_code != '') {
            $whereRC = "  AND users.refferal_code = '" . $refferal_code . "' ";
        }

        $data = DB::select("SELECT users.name, users.refferal_code, SUM(user_plans.amount) as amount, users.roi_income, users.binary_income, users.bonus_income, users.matching_income, (users.roi_income + users.binary_income + users.bonus_income + users.matching_income) as total_income,(select sum(amount) from withdraw where user_id = users.id and status = 1) as total_withdraw, users.created_on as dateofearning FROM `users` LEFT JOIN user_plans on users.id = user_plans.user_id WHERE 1 = 1  " . $whereRC . " GROUP by users.id");

        $data = array_map(function ($value) {
            return (array) $value;
        }, $data);

        $list = array(
            ['Member Name', 'Member Code', 'Invested Amount', 'Roi Income', 'Binary Income', 'Refferal Income', 'Matching Income', 'Total Income', 'Total Withdraw', 'Joining Date']
        );

        $fp = fopen('/var/www/ai/exports/userdataExport.csv', 'w');

        foreach ($list as $fields) {
            fputcsv($fp, $fields);
        }

        foreach ($data as $key => $value) {
            fputcsv($fp, $value);
        }

        fclose($fp);

        $data = usersModel::selectRaw('users.name, users.refferal_code, SUM(user_plans.amount) as amount, users.roi_income, users.binary_income, users.bonus_income, users.matching_income, (users.roi_income + users.binary_income + users.bonus_income + users.matching_income) as total_income,(select sum(amount) from withdraw where user_id = users.id and status = 1) as total_withdraw, users.created_on as dateofearning')->leftjoin('user_plans', 'user_plans.user_id', '=', 'users.id')->whereRaw("1 = 1  " . $whereRC)->groupBy('users.id')->paginate(20)->toArray();

        $res['status_code'] = 1;
        $res['message'] = "Report generated successfully";
        $res['data'] = $data;
        $res['refferal_code'] = $refferal_code;

        return is_mobile($type, "user_export_report", $res, 'view');
    }

    public function verifyIncomeAdmin(Request $request)
    {
        $type = $request->input('type');
        $incometype = $request->input('incometype');
        $responseIncomeType = '';

        $data = earningLogsModel::selectRaw("earning_logs.*, users.refferal_code as user_code, users.rank, users.sponser_code, '' as refferal_code")->join('users', 'users.id', '=', 'earning_logs.user_id')->where(['earning_logs.status' => 0])->orderBy('earning_logs.id', 'desc')->get()->toArray();

        foreach ($data as $key => $value) {
            if ($incometype == "PB") {
                $responseIncomeType = "Profit Bonus";
                if ($value['tag'] == "L-1-PB" || $value['tag'] == "L-2-PB" || $value['tag'] == "L-3-PB" || $value['tag'] == "L-4-PB" || $value['tag'] == "L-5-PB" || $value['tag'] == "L-6-PB" || $value['tag'] == "L-7-PB" || $value['tag'] == "L-8-PB" || $value['tag'] == "L-9-PB" || $value['tag'] == "L-10-PB" || $value['tag'] == "L-11-PB" || $value['tag'] == "L-12-PB" || $value['tag'] == "L-13-PB" || $value['tag'] == "L-14-PB" || $value['tag'] == "L-15-PB" || $value['tag'] == "L-16-PB" || $value['tag'] == "L-17-PB" || $value['tag'] == "L-18-PB" || $value['tag'] == "L-19-PB" || $value['tag'] == "L-20-PB") {
                    $getRef = usersModel::where(['id' => $value['refrence_id']])->get()->toArray();
                    if (count($getRef) > 0) {
                        $data[$key]['refferal_code'] = $getRef['0']['refferal_code'];
                    } else {
                        $data[$key]['refferal_code'] = '';
                    }
                } else {
                    unset($data[$key]);
                }
            }

            if ($incometype == "RP") {
                $responseIncomeType = "Royalty Pool";
                if ($value['tag'] == "pool") {
                } else {
                    unset($data[$key]);
                }
            }

            if ($incometype == "rankbonus") {
                $responseIncomeType = "Rank Bonus";
                if ($value['tag'] == "rank_bonus") {

                    $getRef = usersModel::where(['id' => $value['refrence_id']])->get()->toArray();
                    if (count($getRef) > 0) {
                        $data[$key]['refferal_code'] = $getRef['0']['refferal_code'];
                    } else {
                        $data[$key]['refferal_code'] = '';
                    }
                } else {
                    unset($data[$key]);
                }
            }

            if ($incometype == "profitsharing") {
                $responseIncomeType = "Profit Sharing";
                if ($value['tag'] == "profit_sharing") {
                    $getRef = usersModel::where(['id' => $value['refrence_id']])->get()->toArray();
                    if (count($getRef) > 0) {
                        $data[$key]['refferal_code'] = $getRef['0']['refferal_code'];
                    } else {
                        $data[$key]['refferal_code'] = '';
                    }
                } else {
                    unset($data[$key]);
                }
            }

            if ($incometype == "brokerage") {
                $responseIncomeType = "Brokerage";
                if ($value['tag'] == "brokerage") {
                    $getRef = usersModel::where(['id' => $value['refrence_id']])->get()->toArray();
                    if (count($getRef) > 0) {
                        $data[$key]['refferal_code'] = $getRef['0']['refferal_code'];
                    } else {
                        $data[$key]['refferal_code'] = '';
                    }
                } else {
                    unset($data[$key]);
                }
            }
        }

        $res['status_code'] = 1;
        $res['message'] = "Incomes fetched successfully";
        $res['data'] = $data;
        $res['responseIncomeType'] = $responseIncomeType;
        $res['incometype'] = $incometype;

        return is_mobile($type, "verify_income", $res, 'view');
    }

    public function verifyIncomeAdminExcel(Request $request)
    {
        $type = $request->input('type');
        $incometype = $request->input('incometype');
        $responseIncomeType = '';

        $data = earningLogsModel::selectRaw("users.refferal_code as user_code, earning_logs.tag, earning_logs.amount, earning_logs.flush_amount,users.sponser_code,  '' as refferal_code, users.rank,earning_logs.refrence_id")->join('users', 'users.id', '=', 'earning_logs.user_id')->where(['earning_logs.status' => 0])->orderBy('earning_logs.id', 'desc')->get()->toArray();

        foreach ($data as $key => $value) {
            if ($incometype == "PB") {
                $responseIncomeType = "Profit Bonus";
                if ($value['tag'] == "L-1-PB" || $value['tag'] == "L-2-PB" || $value['tag'] == "L-3-PB" || $value['tag'] == "L-4-PB" || $value['tag'] == "L-5-PB" || $value['tag'] == "L-6-PB" || $value['tag'] == "L-7-PB" || $value['tag'] == "L-8-PB" || $value['tag'] == "L-9-PB" || $value['tag'] == "L-10-PB" || $value['tag'] == "L-11-PB" || $value['tag'] == "L-12-PB" || $value['tag'] == "L-13-PB" || $value['tag'] == "L-14-PB" || $value['tag'] == "L-15-PB" || $value['tag'] == "L-16-PB" || $value['tag'] == "L-17-PB" || $value['tag'] == "L-18-PB" || $value['tag'] == "L-19-PB" || $value['tag'] == "L-20-PB") {
                    $getRef = usersModel::where(['id' => $value['refrence_id']])->get()->toArray();
                    if (count($getRef) > 0) {
                        $data[$key]['refferal_code'] = $getRef['0']['refferal_code'];
                    } else {
                        $data[$key]['refferal_code'] = '';
                    }
                } else {
                    unset($data[$key]);
                }
            }

            if ($incometype == "RP") {
                $responseIncomeType = "Royalty Pool";
                if ($value['tag'] == "pool") {
                } else {
                    unset($data[$key]);
                }
            }

            if ($incometype == "rankbonus") {
                $responseIncomeType = "Rank Bonus";
                if ($value['tag'] == "rank_bonus") {

                    $getRef = usersModel::where(['id' => $value['refrence_id']])->get()->toArray();
                    if (count($getRef) > 0) {
                        $data[$key]['refferal_code'] = $getRef['0']['refferal_code'];
                    } else {
                        $data[$key]['refferal_code'] = '';
                    }
                } else {
                    unset($data[$key]);
                }
            }

            if ($incometype == "profitsharing") {
                $responseIncomeType = "Profit Sharing";
                if ($value['tag'] == "profit_sharing") {
                    $getRef = usersModel::where(['id' => $value['refrence_id']])->get()->toArray();
                    if (count($getRef) > 0) {
                        $data[$key]['refferal_code'] = $getRef['0']['refferal_code'];
                    } else {
                        $data[$key]['refferal_code'] = '';
                    }
                } else {
                    unset($data[$key]);
                }
            }

            if ($incometype == "brokerage") {
                $responseIncomeType = "Brokerage";
                if ($value['tag'] == "brokerage") {
                    $getRef = usersModel::where(['id' => $value['refrence_id']])->get()->toArray();
                    if (count($getRef) > 0) {
                        $data[$key]['refferal_code'] = $getRef['0']['refferal_code'];
                    } else {
                        $data[$key]['refferal_code'] = '';
                    }
                } else {
                    unset($data[$key]);
                }
            }

            unset($data[$key]['refrence_id']);
        }

        $headings = [
            'User Id',
            'Tag',
            'Amount',
            'Flush Amount',
            'Sponser Code',
            'Refrence',
            'Rank',
        ];

        // return Excel::create('VerifyIncomes', function($excel) use ($data, $headings) {
        //     $excel->sheet('VerifyIncomes', function($sheet) use ($data, $headings) {
        //         // Set the headings
        //         $sheet->row(1, $headings);

        //         // Add data to the sheet
        //         $j = 1;
        //         foreach ($data as $row) {
        //             $sheet->appendRow([$j, $row['user_code'], $row['tag'], $row['amount'], $row['flush_amount'], $row['sponser_code'], $row['refferal_code'], $row['rank']]);
        //         }
        //     });
        // })->download('xlsx');

        return Excel::download(new CustomQueryExport($data, $headings), $incometype . '.xlsx');
    }

    public function mismatchedMt5(Request $request)
    {
        $type = $request->input('type');
        $excelType = "Balance Excel";

        $data = marketExcelModel::where(['found' => 0])->orderBy('date', 'desc')->get()->toArray();

        $res['status_code'] = 1;
        $res['message'] = "Incomes fetched successfully";
        $res['data'] = $data;
        $res['responseExcel'] = $excelType;

        return is_mobile($type, "mismatched_excel", $res, 'view');
    }

    public function psMismatchedMt5(Request $request)
    {
        $type = $request->input('type');
        $excelType = "Profit Sharing Excel";

        $data = profitSharingModel::where(['found' => 0])->orderBy('date', 'desc')->get()->toArray();

        $res['status_code'] = 1;
        $res['message'] = "Incomes fetched successfully";
        $res['data'] = $data;
        $res['responseExcel'] = $excelType;

        return is_mobile($type, "mismatched_excel", $res, 'view');
    }

    public function inactiveMT5UserBalance(Request $request)
    {
        $type = $request->input('type');

        $data = usersModel::whereRaw('mt5_verify != 1 and balance > 0')->get()->toArray();

        $res['status_code'] = 1;
        $res['message'] = "Incomes fetched successfully";
        $res['data'] = $data;

        return is_mobile($type, "inactive_mt5", $res, 'view');
    }

    public function verifiedNoBalanceMt5(Request $request)
    {
        $type = $request->input('type');

        $data = marketExcelModel::where(['found' => 0])->orderBy('date', 'desc')->limit(1)->get()->toArray();

        $users = usersModel::whereRaw("mt5_verify = 1")->get()->toArray();

        foreach ($users as $key => $value) {
            $excel = marketExcelModel::where(['date' => $data['0']['date'], 'mt_acc' => $value['mt5']])->get()->toArray();

            if (count($excel) > 0) {
                unset($users[$key]);
            }
        }


        $res['status_code'] = 1;
        $res['message'] = "Incomes fetched successfully";
        $res['data'] = $users;

        return is_mobile($type, "inactive_mt5", $res, 'view');
    }

    public function findUplineRankView(Request $request)
    {
        $type = $request->input('type');
        $member_code = $request->input('member_code');

        $res['status_code'] = 1;
        $res['message'] = "Search member here";

        return is_mobile($type, 'upline_rank', $res, "view");
    }

    public function findUplineRankViewResult(Request $request)
    {
        $type = $request->input('type');

        $member_code = $request->input('member_code');

        $data = usersModel::where(['refferal_code' => $member_code])->get()->toArray();

        $lastRank = 0;
        $lastRankArray = array();
        foreach ($data as $k => $v) {
            $rankDetails = findUplineRank($v['sponser_id'], 0);

            if ($rankDetails['rank_id'] > 0) {
                $lastRankArray[$rankDetails['rank_id']] = $rankDetails;
                $lastRank = $rankDetails['rank_id'];

                if ($rankDetails['rank_id'] < 8) {
                    $rankDetails = findUplineRank($rankDetails['user_id'], $lastRank);
                    if ($rankDetails['rank_id'] > 0) {
                        $lastRankArray[$rankDetails['rank_id']] = $rankDetails;
                        $lastRank = $rankDetails['rank_id'];

                        if ($rankDetails['rank_id'] < 8) {
                            $rankDetails = findUplineRank($rankDetails['user_id'], $lastRank);
                            if ($rankDetails['rank_id'] > 0) {
                                $lastRankArray[$rankDetails['rank_id']] = $rankDetails;
                                $lastRank = $rankDetails['rank_id'];

                                if ($rankDetails['rank_id'] < 8) {
                                    $rankDetails = findUplineRank($rankDetails['user_id'], $lastRank);
                                    if ($rankDetails['rank_id'] > 0) {
                                        $lastRankArray[$rankDetails['rank_id']] = $rankDetails;
                                        $lastRank = $rankDetails['rank_id'];

                                        if ($rankDetails['rank_id'] < 8) {
                                            $rankDetails = findUplineRank($rankDetails['user_id'], $lastRank);
                                            if ($rankDetails['rank_id'] > 0) {
                                                $lastRankArray[$rankDetails['rank_id']] = $rankDetails;
                                                $lastRank = $rankDetails['rank_id'];

                                                if ($rankDetails['rank_id'] < 8) {
                                                    $rankDetails = findUplineRank($rankDetails['user_id'], $lastRank);
                                                    if ($rankDetails['rank_id'] > 0) {
                                                        $lastRankArray[$rankDetails['rank_id']] = $rankDetails;
                                                        $lastRank = $rankDetails['rank_id'];

                                                        if ($rankDetails['rank_id'] < 8) {
                                                            $rankDetails = findUplineRank($rankDetails['user_id'], $lastRank);
                                                            if ($rankDetails['rank_id'] > 0) {
                                                                $lastRankArray[$rankDetails['rank_id']] = $rankDetails;
                                                                $lastRank = $rankDetails['rank_id'];

                                                                if ($rankDetails['rank_id'] < 8) {
                                                                    $rankDetails = findUplineRank($rankDetails['user_id'], $lastRank);
                                                                    $lastRankArray[$rankDetails['rank_id']] = $rankDetails;
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

        foreach ($lastRankArray as $key => $value) {
            $data = usersModel::where(['id' => $value['user_id']])->get()->toArray();
            $lastRankArray[$key]['refferal_code'] = $data['0']['refferal_code'];
            $lastRankArray[$key]['name'] = $data['0']['name'];
            $lastRankArray[$key]['mt5_name'] = $data['0']['mt5_name'];
            $lastRankArray[$key]['wallet_address'] = $data['0']['wallet_address'];
            $lastRankArray[$key]['my_team'] = $data['0']['my_team'];
            $lastRankArray[$key]['my_business'] = $data['0']['my_business'];
            $lastRankArray[$key]['sponser_code'] = $data['0']['sponser_code'];
        }

        $res['status_code'] = 1;
        $res['message'] = "Incomes fetched successfully";
        $res['data'] = $lastRankArray;
        $res['member_code'] = $member_code;

        return is_mobile($type, "upline_rank", $res, 'view');
    }

    public function witdhrawBankRequest(Request $request)
    {
        $type = $request->input('type');

        $data = DB::select("SELECT users.name, users.refferal_code, users.mt5, users.sponser_code, withdraw.amount, withdraw.created_on as dateofearning, users.created_on,withdraw.withdraw_type, users.account_holder_name, users.bank_name, users.account_number, users.ifsc_code, withdraw.id FROM withdraw INNER JOIN users on withdraw.user_id = users.id WHERE withdraw.status = 0 and withdraw_type = 'BANKWIRE' ");

        $data = array_map(function ($value) {
            return (array) $value;
        }, $data);

        $res['status_code'] = 1;
        $res['message'] = "Successfully";
        $res['data'] = $data;

        return is_mobile($type, "withdraw_request", $res, 'view');
    }

    public function withdrawBankProcess(Request $request)
    {
        $type = $request->input('type');
        $wid = $request->input('wid');
        $decision = $request->input('decision');

        $data = withdrawModel::where(['id' => $wid])->get()->toArray();

        if (count($data) > 0) {
            if ($decision == 0) {
                withdrawModel::where(['id' => $wid])->update(['status' => 2]);
            } else {
                withdrawModel::where(['id' => $wid])->update(['status' => 1]);
            }

            if ($data['0']['wallet'] == "PROFIT-SHARING") {
                if ($decision != 0) {
                    $user = usersModel::where(['id' => $data['0']['user_id']])->get()->toArray();

                    $mt5 = $user['0']['mt5'];

                    $curl = curl_init();

                    curl_setopt_array($curl, array(
                        CURLOPT_URL => 'https://mt5apis.strelasoft.com/smartbulls/api/accountwithdraw',
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_ENCODING => '',
                        CURLOPT_MAXREDIRS => 10,
                        CURLOPT_TIMEOUT => 0,
                        CURLOPT_FOLLOWLOCATION => true,
                        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                        CURLOPT_CUSTOMREQUEST => 'POST',
                        CURLOPT_POSTFIELDS => array('mt5id' => $mt5, 'amount' => number_format($data['0']['amount'], 2), 'comment' => 'Made withdraw of ' . $data['0']['amount']),
                        CURLOPT_HTTPHEADER => array(
                            'Authorization: Sf5u9pxS7iho3AoC6CaDklbkmTwRCOyYhzRPWiz37UBODjIE7Zx9OxK2PMRIc'
                        ),
                    ));

                    $response = curl_exec($curl);

                    curl_close($curl);

                    withdrawModel::where(['id' => $wid])->update(['mt5Response' => $response]);
                }
            }
        }


        $res['status_code'] = 1;
        $res['message'] = "Withdraw Processed Successfully";

        return is_mobile($type, "witdhrawBankRequest", $res);
    }

    public function awardIncomeRequest(Request $request)
    {
        $type = $request->input('type');

        $data = DB::select("SELECT u.name, u.mobile_number, u.sponser_code, u.email, u.refferal_code, u.mt5, a.* FROM award_income_form a inner join users u on a.user_id = u.id WHERE a.status = 0");

        $data = array_map(function ($value) {
            return (array) $value;
        }, $data);

        $res['status_code'] = 1;
        $res['message'] = "Successfully";
        $res['data'] = $data;

        return is_mobile($type, "award_withdraw_request", $res, 'view');
    }

    public function awardIncomeProcessAdmin(Request $request)
    {
        $type = $request->input('type');
        $wid = $request->input('wid');
        $decision = $request->input('decision');

        if ($decision == 0) {
            DB::table("award_income_form")->where(['id' => $wid])->update(['status' => 2]);
        } else {
            DB::table("award_income_form")->where(['id' => $wid])->update(['status' => 1]);
        }

        $res['status_code'] = 1;
        $res['message'] = "Withdraw Processed Successfully";

        return is_mobile($type, "awardIncomeRequest", $res);
    }

    public function rankSortDate(Request $request)
    {
        $type = $request->input('type');

        $data = DB::select("SELECT * FROM users WHERE rank_id > 0 order by rank_date asc");

        $data = array_map(function ($value) {
            return (array) $value;
        }, $data);

        $res['status_code'] = 1;
        $res['message'] = "Successfully";
        $res['data'] = $data;

        return is_mobile($type, "rank_sort_date", $res, 'view');
    }

    public function fundTransferReport(Request $request)
    {
        $type = $request->input('type');

        $data = DB::select("SELECT p.pin, p.amount, p.status, u.name, u.refferal_code, u.mt5, uf.name as for_user_name, uf.refferal_code as for_user_refferal_code, uf.mt5 as for_user_mt5, p.for_user_id, p.for_created_on FROM `pins` p inner join users u on p.user_id = u.id inner join users as uf on uf.id = p.for_user_id");

        $data = array_map(function ($value) {
            return (array) $value;
        }, $data);

        foreach ($data as $key => $value) {
            if ($value['status'] == 1) {
                if (empty($value['created_on'])) {
                    $packageTransaction = packageTransaction::whereRaw("amount = '" . $value['amount'] . "' and user_id = '" . $value['for_user_id'] . "'")->get()->toArray();

                    if (count($packageTransaction) > 0) {
                        $data[$key]['for_created_on'] = date('d-m-Y', strtotime($packageTransaction['0']['created_on']));
                    } else {
                        $data[$key]['for_created_on'] = '-';
                    }
                } else {
                    $data[$key]['for_created_on'] = date('d-m-Y', strtotime($value['for_created_on']));
                }
            } else {
                $data[$key]['for_created_on'] = '-';
            }
        }

        $res['status_code'] = 1;
        $res['message'] = "Successfully";
        $res['data'] = $data;

        return is_mobile($type, "fund_transfer_report", $res, 'view');
    }

    public function verifyReferralIncome(Request $request)
    {
        $type = $request->input('type');

        $data = DB::select("SELECT e.*, u.refferal_code, u.name, u.wallet_address, u.mt5, ur.mt5 as imt5, ur.name as iname, ur.wallet_address as iwallet_address, ur.refferal_code as irefferal_code FROM earning_logs e inner join users u on e.user_id = u.id inner join users ur on e.refrence_id = ur.id WHERE e.status = 0 and e.tag = 'REFERRAL' order by e.id desc");

        $data = array_map(function ($value) {
            return (array) $value;
        }, $data);

        $res['status_code'] = 1;
        $res['message'] = "Successfully";
        $res['data'] = $data;

        return is_mobile($type, "verify_referral_income", $res, 'view');
    }

    public function activeReferralIncome(Request $request)
    {
        $type = $request->input('type');

        $data = earningLogsModel::where(['status' => 0, 'tag' => "REFERRAL"])->get()->toArray();

        foreach ($data as $key => $value) {
            if ($value['tag'] == "REFERRAL") {
                DB::statement("UPDATE users set referral_bonus = (IFNULL(referral_bonus,0) + (" . $value['amount'] . ")) where id = '" . $value['user_id'] . "'");
            }

            earningLogsModel::where(['id' => $value['id']])->update(['status' => 1]);
        }

        $res['status_code'] = 1;
        $res['message'] = "Successfully";
        $res['data'] = $data;

        return is_mobile($type, "verifyReferralIncome", $res);
    }

    function withdraw_stop(Request $request)
    {
        $type = $request->input('type');
        $search = $request->input('search');

        $whereRawSearch = '';

        if ($search != '') {
            $whereRawSearch = " AND (refferal_code = '%" . $search . "%' or wallet_address like '%" . $search . "%') ";
            $res['search'] = $search;
        }

        $data = usersModel::selectRaw("users.*")->whereRaw(" 1 = 1 " . $whereRawSearch)->groupBy('users.id')->paginate(20)->toArray();

        $data = array_map(function ($value) {
            return (array) $value;
        }, $data);

        $res['status_code'] = 1;
        $res['message'] = "Success";
        $res['data'] = $data;

        return is_mobile($type, "withdraw_stop", $res, 'view');
    }

    function update_roi_stop_date(Request $request)
    {
        $type = $request->input('type');
        $roi_stop_date = date('Y-m-d');
        $user_id = $request->input('user_id');

        $user = usersModel::where('id', $user_id)->first();
        if ($user->roi_stop_date == NULL) {
            usersModel::where('id', $user_id)->update([
                'roi_stop_date' => $roi_stop_date
            ]);
            $res['status_code'] = 1;
            $res['message'] = "Success";
        } else {
            $res['status_code'] = 0;
            $res['message'] = "Already Updated";
        }

        return is_mobile($type, "withdraw_stop", $res);
    }

    function update_level_stop_date(Request $request)
    {
        $type = $request->input('type');
        $level_stop_date = date('Y-m-d');
        $user_id = $request->input('user_id');

        $user = usersModel::where('id', $user_id)->first();
        if ($user->level_stop_date == NULL) {
            usersModel::where('id', $user_id)->update([
                'level_stop_date' => $level_stop_date
            ]);
            $res['status_code'] = 1;
            $res['message'] = "Success";
        } else {
            $res['status_code'] = 0;
            $res['message'] = "Already Updated";
        }

        return is_mobile($type, "withdraw_stop", $res);
    }

    function website_popup(Request $request)
    {
        $type = $request->input('type');
        $data = DB::table('website_popup')->get();

        $res['status_code'] = 1;
        $res['message'] = "Success";
        $res['data'] = $data;
        return is_mobile($type, "website_popup", $res, 'view');
    }

    function add_website_popup(Request $request)
    {
        $type = $request->input('type');
        $count = DB::table('website_popup')->count();
        $title = $request->title;
        if ($request->title == '') {
            $title = 'Web Popup';
        }
        $file_name = "";
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $originalname = $file->getClientOriginalName();
            $name = "popup" . '_' . date('Y-m-d-His');
            $ext = \File::extension($originalname);
            $file_name = $name . '.' . $ext;
            $path = $file->storeAs('public/', $file_name);
        }
        if ($count == 1) {
            DB::table('website_popup')->limit(1)->update([
                'title' => $title,
                'image' => $file_name,
            ]);
        } else {
            DB::table('website_popup')->insert([
                'title' => $title,
                'image' => $file_name,
            ]);
        }
        $res['status_code'] = 1;
        $res['message'] = "Added Successfully";
        return is_mobile($type, "website_popup", $res);
    }

    function process_business(Request $request){
        $type = $request->input('type');
        $admin_user_id = $request->session()->get('admin_user_id');
        $user_id = $request->input('user_id');
        $power_leg_amt = $request->input('power_leg_amt');
        $weak_leg_amt = $request->input('weak_leg_amt');

        $user = usersModel::where('id',$user_id)->first();
        $member_code=  $user->refferal_code;

        $strong_business=(int)$user->strong_business+$power_leg_amt;
        $weak_business=(int)$user->weak_business+$weak_leg_amt;
        $user = usersModel::where('id',$user_id)->update([
            'strong_business'=>$strong_business,
            'weak_business'=>$weak_business
        ]);

        $res['status_code'] = 1;
        $res['message'] = "Business Added Successfully";
        $res['member_code'] = $member_code;
        return is_mobile($type, 'searchMember', $res);
    }

    public function teamStrongBusiness(Request $request)
    {
        $type = $request->input('type');

        $res['status_code'] = 1;
        $res['message'] = "Team Strong Business Loaded";
        
        return is_mobile($type, "team-strong-business", $res, "view");
    }

    public function processTeamStrongBusiness(Request $request)
    {
        $type = $request->input('type');
        $business = $request->input('business');
        $first_wallet_address = $request->input('first_wallet_address');
        $last_wallet_address = $request->input('last_wallet_address');
        $isForced = $request->input('isForced');

        $checkRefferalValid = usersModel::where('wallet_address', $first_wallet_address)->first();
        if (!$checkRefferalValid) {
            return is_mobile($type, "team_strong_business", [
                'status_code' => 0,
                'message' => "Invalid First Wallet Address.",
            ]);
        }

        $checkLastRefferalValid = usersModel::where('wallet_address', $last_wallet_address)->first();
        if (!$checkLastRefferalValid) {
            return is_mobile($type, "team_strong_business", [
                'status_code' => 0,
                'message' => "Invalid Last Wallet Address.",
            ]);
        }

        // Check if both are in same team
        $checkDownline = myTeamModel::where([
            'user_id' => $checkRefferalValid->id,
            'team_id' => $checkLastRefferalValid->id
        ])->exists();

        if (!$checkDownline) {
            return is_mobile($type, "team_strong_business", [
                'status_code' => 0,
                'message' => "Both are not in one team. Please check.",
            ]);
        }

        // Update strong_business for the last user
        usersModel::where('id', $checkLastRefferalValid->id)->update(['strong_business' => $business, 'power_date' => date('Y-m-d')]);

        // Start looping up the sponsor chain until first user is reached
        $currentUserId = $checkLastRefferalValid->sponser_id;

        while ($currentUserId && $currentUserId != $checkRefferalValid->id) {
            $sponsor = usersModel::find($currentUserId);

            if (!$sponsor) {
                break; // Stop if sponsor not found
            }

            usersModel::where('id', $sponsor->id)->update(['strong_business' => $business, 'power_date' => date('Y-m-d')]);

            // Move to next sponsor up the chain
            $currentUserId = $sponsor->sponser_id;
        }

        // Finally, if direct sponsor is first user, update them too
        if ($currentUserId == $checkRefferalValid->id) {
            // usersModel::where('id', $checkRefferalValid->id)->update(['strong_business' => $business]);
        }

        return is_mobile($type, "team-strong-business", [
            'status_code' => 1,
            'message' => "Team Strong Business Loaded",
        ], "view");
    }
      function level_income_report(Request $request){
        $type = $request->input('type');
        $tag = $request->input('tag');
        $refferal_code = $request->input('refferal_code');
        $startDate = $request->input('startDate');
        $endDate = $request->input('endDate');
        $rank = $request->input('rank');
        $whererf='';
        $whereCH='';
        $whereStartDate='';
        $whereEndDate='';
        $whereRank='';

        
        $data=[];

        if ($startDate != '') {
            $whereStartDate = " AND date_format(earning_logs.created_on, '%Y-%m-%d') >= '" . date('Y-m-d', strtotime($startDate)) . "'";
        }

        if ($endDate != '') {
            $whereEndDate = " AND date_format(earning_logs.created_on, '%Y-%m-%d') <= '" . date('Y-m-d', strtotime($endDate)) . "'";
        }
        if ($tag=='') {
            $data=earningLogsModel::selectRaw('users.*,earning_logs.amount,earning_logs.tag,earning_logs.refrence_id')->join('users','users.id','=','earning_logs.user_id');

            $amt = $data->sum('amount');
            $data=$data->paginate(20)->toArray();
        }
        if ($rank!='') {
            $whereRank = " AND earning_logs.tag ='REWARD-BONUS' AND earning_logs.refrence_id = '".$rank."'";

            $data=earningLogsModel::selectRaw('users.*,earning_logs.amount,earning_logs.tag,earning_logs.refrence_id')->join('users','users.id','=','earning_logs.user_id')->whereRaw("1 = 1  " . $whereStartDate .$whereEndDate.$whereRank);
            
            $amt = $data->sum('amount');
            $data=$data->paginate(20)->toArray();
        }
        if ($refferal_code!='') {
          
            if ($rank != '') {
                $whereRank = " AND earning_logs.tag ='REWARD-BONUS' AND earning_logs.refrence_id = '".$rank."'";
            }
            $whererf = "  AND users.wallet_address = '".$refferal_code."' ";

            $data=earningLogsModel::selectRaw('users.*,earning_logs.amount,earning_logs.tag,earning_logs.refrence_id')->join('users','users.id','=','earning_logs.user_id')->whereRaw("1 = 1  " . $whererf. $whereStartDate .$whereEndDate.$whereRank)->orderBy('earning_logs.created_on', 'desc');
            if ($refferal_code != '' && $tag == 'REWARD-BONUS') {
                $data = $data->paginate(20);
                $data->setCollection($data->getCollection()->take(1));
            } else {
                $data = $data->paginate(20);
            }

            $amt = $data->sum('amount');
            $data = $data->toArray();
            // $data=$data->paginate(20)->toArray();
        }
        
        if ($tag=='Level') {
            $whereCH = "  AND level_earning_logs.tag LIKE '%".$tag."%' ";

            if ($startDate != '') {
                $whereStartDate = " AND date_format(level_earning_logs.created_on, '%Y-%m-%d') >= '" . date('Y-m-d', strtotime($startDate)) . "'";
            }

            if ($endDate != '') {
                $whereEndDate = " AND date_format(level_earning_logs.created_on, '%Y-%m-%d') <= '" . date('Y-m-d', strtotime($endDate)) . "'";
            }

            $data=levelEarningLogsModel::selectRaw('users.*,level_earning_logs.amount,level_earning_logs.tag,level_earning_logs.created_on,level_earning_logs.refrence_id')->join('users','users.id','=','level_earning_logs.user_id')->whereRaw("1 = 1  " . $whererf . $whereCH. $whereStartDate .$whereEndDate);

            $amt = $data->sum('amount');
            $data=$data->paginate(20)->toArray();
        }
        if ($tag!='Level' && $tag!='') {
            $whereCH = "  AND earning_logs.tag = '".$tag."' ";

            if ($rank != '' && $tag=='REWARD-BONUS') {
                $whereRank = " AND earning_logs.refrence_id = '".$rank."'";
            }
            
            $data=earningLogsModel::selectRaw('users.*,earning_logs.amount,earning_logs.tag,earning_logs.created_on,earning_logs.refrence_id')->join('users','users.id','=','earning_logs.user_id')->whereRaw("1 = 1  " . $whererf . $whereCH. $whereStartDate .$whereEndDate.$whereRank)->orderBy('earning_logs.created_on', 'desc');

            $amt = $data->sum('amount');
            if ($refferal_code != '' && $tag == 'REWARD-BONUS') {
                $data = $data->paginate(20);
                $data->setCollection($data->getCollection()->take(1));
            } else {
                $data = $data->paginate(20);
            }

            $data = $data->toArray();
        }

        $res['data'] = $data;
        $res['amt'] = $amt;
        $res['refferal_code'] = $refferal_code;
        $res['startDate'] = $startDate;
        $res['endDate'] = $endDate;
        $res['tag'] = $tag;
        $res['rank'] = $rank;
        $res['status_code'] = 1;
        $res['message'] = "Team Strong Business Loaded";
        return is_mobile($type, "level_income_report", $res, "view");
    }

    public function investmentReportt(Request $request)
    {
        $type = $request->input('type');
        $start_date = $request->input('start_date');
        $end_date = $request->input('end_date');
        $refferal_code = $request->input('refferal_code');
        $isExport = $request->input('export') === 'yes';

        $whereStartDate = '';
        $whereEndDate = '';
        $whereRC = '';

        if($start_date != '')
        {
            $whereStartDate = " AND date_format(user_plans.created_on, '%Y-%m-%d') >= '".date('Y-m-d', strtotime($start_date))."'";
        }

        if($end_date != '')
        {
            $whereEndDate = " AND date_format(user_plans.created_on, '%Y-%m-%d') <= '".date('Y-m-d', strtotime($end_date))."'";
        }

        if($refferal_code != '')
        {
            $whereRC = "  AND users.wallet_address = '".$refferal_code."' ";
        }

        $data = userPlansModel::selectRaw("users.*,user_plans.amount,user_plans.created_on as dateofearning, user_plans.transaction_hash, user_plans.isSynced, user_plans.created_on")->join('users','users.id','=','user_plans.user_id')->whereRaw("1 = 1  ".$whereStartDate.$whereEndDate.$whereRC)->orderBy('user_plans.id','Desc');

        $total = $data->sum('amount');

        $data = $data->paginate(20)->toArray();

        if ($isExport) {

            $investment = userPlansModel::selectRaw("users.*,user_plans.amount,user_plans.created_on as dateofearning, user_plans.transaction_hash, user_plans.isSynced, user_plans.created_on")->join('users','users.id','=','user_plans.user_id')->whereRaw("1 = 1  ".$whereStartDate.$whereEndDate.$whereRC)->orderBy('id','Desc')->get()->toArray();

            $investment = array_map(function ($value) {
                return (array) $value;
            }, $investment);


            $list = [
                ['Member Code','Wallet Address', 'Sponsor Code', 'Transaction Hash','Amount','Date','Joining Date']
            ];
            $filePath='/var/www/html/exports/investment.csv';
            $fp = fopen($filePath, 'w');
            foreach ($list as $fields) {
                fputcsv($fp, $fields);
            }
            foreach ($investment as $value) {
                $txn_hash=$value['transaction_hash'];
                // if ($value['transaction_hash']=='By Topup') {
                //     $package_date= date('d-m-Y', strtotime($value['dateofearning']));
                //     $package_txn= packageTransaction::where('user_id',$value['id'])->where('amount',$value['amount'])->whereDate('created_on',$package_date)->first();
                //     $txn_hash=$package_txn->transaction_hash;
                // }
                $dataRows = [
                    // $value['name'],
                    $value['refferal_code'],
                    $value['wallet_address'],
                    $value['sponser_code'],
                    $txn_hash,
                    $value['amount'],
                    date('d-m-Y', strtotime($value['dateofearning'])),
                    date('d-m-Y', strtotime($value['created_on'])),
                ];
                fputcsv($fp, $dataRows);
            }
            
            fclose($fp);
            return response()->download($filePath)->deleteFileAfterSend(true);
        }
        foreach ($data['data'] as $key => $value){
            $txn_hash=$value['transaction_hash'];
            // if ($value['transaction_hash']=='By Topup') {
            //     $package_date= date('d-m-Y', strtotime($value['dateofearning']));
            //     $package_txn= packageTransaction::where('user_id',$value['id'])->where('amount',$value['amount'])->whereDate('created_on',$package_date)->first();
            //     $txn_hash=$package_txn->transaction_hash;
            // }
            $data['data'][$key]['transaction_hash'] = $txn_hash;
        }

        $res['status_code'] = 1;
        $res['message'] = "Report generated successfully";
        $res['data'] = $data;
        $res['start_date'] = $start_date;
        $res['end_date'] = $end_date;
        $res['refferal_code'] = $refferal_code;
        $res['total'] = $total;

        return is_mobile($type, "investment_reportt", $res, 'view');
    }
    public function power_business_report(Request $request)
    {
        $type = $request->input('type');
        $start_date = $request->input('start_date');
        $end_date = $request->input('end_date');
        $refferal_code = $request->input('refferal_code');
        $isExport = $request->input('export') === 'yes';

        $whereStartDate = '';
        $whereEndDate = '';
        $whereRC = '';

        if($start_date != '')
        {
            $whereStartDate = " AND date_format(users.created_on, '%Y-%m-%d') >= '".date('Y-m-d', strtotime($start_date))."'";
        }

        if($end_date != '')
        {
            $whereEndDate = " AND date_format(users.created_on, '%Y-%m-%d') <= '".date('Y-m-d', strtotime($end_date))."'";
        }

        if($refferal_code != '')
        {
            $whereRC = "  AND users.wallet_address = '".$refferal_code."' ";
        }

        $data = usersModel::whereRaw("strong_business > 0 ".$whereStartDate.$whereEndDate.$whereRC)->orderBy('id','Desc')->paginate(20)->toArray();

        if ($isExport) {

            $investment = usersModel::whereRaw("strong_business > 0 ".$whereStartDate.$whereEndDate.$whereRC)->orderBy('id','Desc')->get()->toArray();

            $investment = array_map(function ($value) {
                return (array) $value;
            }, $investment);


            $list = [
                ['Name','User ID','Wallet Address', 'Sponsor Code','Strong Business','Rank','Joining Date']
            ];
            $filePath='/var/www/html/exports/power_business_report.csv';
            $fp = fopen($filePath, 'w');
            foreach ($list as $fields) {
                fputcsv($fp, $fields);
            }
            foreach ($investment as $value) {

                $dataRows = [
                    $value['name'],
                    $value['refferal_code'],
                    $value['wallet_address'],
                    $value['sponser_code'],
                    $value['strong_business'],
                    $value['rank'],
                    date('d-m-Y', strtotime($value['created_on'])),
                ];
                fputcsv($fp, $dataRows);
            }
            
            fclose($fp);
            return response()->download($filePath)->deleteFileAfterSend(true);
        }
    
        $res['status_code'] = 1;
        $res['message'] = "Report generated successfully";
        $res['data'] = $data;
        $res['start_date'] = $start_date;
        $res['end_date'] = $end_date;
        $res['refferal_code'] = $refferal_code;

        return is_mobile($type, "power_business_report", $res, 'view');
    }
    function remove_power_business(Request $request)
    {
        $type = $request->input('type');
        $user_id = $request->input('user_id');

        usersModel::where('id',$user_id)->update([
            'strong_business'=>0
        ]);

        $res['status_code'] = 1;
        $res['message'] = "Power Business removed successfully";

        return is_mobile($type, "power_business_report", $res);
    }
    function orbitx_pool_report(Request $request)
    {
        $type = $request->input('type');
        $isExport = $request->input('export') === 'yes';
        $start_date = $request->input('start_date');
        $end_date = $request->input('end_date');
        $hash = $request->input('hash');
        $type = $request->input('type');
        
        $whereStartDate = '';
        $whereEndDate = '';
        $whereHash = '';
        $wheretype = '';
        $whereType = '';

        if($start_date != '')
        {
            $whereStartDate = " AND date_format(other_pools.created_on, '%Y-%m-%d') >= '".date('Y-m-d', strtotime($start_date))."'";
        }

        if($end_date != '')
        {
            $whereEndDate = " AND date_format(other_pools.created_on, '%Y-%m-%d') <= '".date('Y-m-d', strtotime($end_date))."'";
        }

        if($hash != '')
        {
            $whereHash = "AND (other_pools.transaction_hash = '".$hash."' OR other_pools.wallet_address = '".$hash."')";
        }
        if($type != '')
        {
            $whereType = "AND other_pools.pool = '".$type."'";
        }

        // $data = DB::table('other_pools')->selectRaw("other_pools.*,withdraw.amount,withdraw.created_on as dateofearning,withdraw.claim_hash,withdraw.withdraw_type, withdraw.status")->join('users','users.id','=','withdraw.user_id')->where('withdraw_type', 'PRINCIPAL')->whereRaw("isSynced =5 AND isRequestSynced=5 ".$whereStartDate.$whereEndDate.$whereHash.$whereType)->paginate(20)->toArray();
        $data = DB::table('other_pools')->select('*')->whereRaw("1=1 ".$whereStartDate.$whereEndDate.$whereHash.$whereType)->paginate(20)->toArray();

        $total_pool_amount = DB::table('other_pools')
        ->select('pool', DB::raw('SUM(amount) as total_amount'))
        ->groupBy('pool')
        ->get();

        $founder_pool_amt=DB::table('other_pools')->where('pool', 'FOUNDER')->whereDate('created_on', Carbon::today())->sum('amount');
        $gic_pool_amt=DB::table('other_pools')->where('pool', 'GIC')->whereDate('created_on', Carbon::today())->sum('amount');
        $lic_pool_amt=DB::table('other_pools')->where('pool', 'LIC')->whereDate('created_on', Carbon::today())->sum('amount');
        $marketting_pool_amt=DB::table('other_pools')->where('pool', 'MARKETING')->whereDate('created_on', Carbon::today())->sum('amount');
        $promoter_pool_amt=DB::table('other_pools')->where('pool', 'PROMOTER')->whereDate('created_on', Carbon::today())->sum('amount');

        // $todaySum = DB::table('other_pools')->select('pool', DB::raw('SUM(amount) as total_amount'))->whereDate('created_on', Carbon::today())->groupBy('pool')->get();

        $res['status_code'] = 1;
        $res['message'] = "Report generated successfully";
        $res['data'] = $data;
        $res['start_date'] = $start_date;
        $res['end_date'] = $end_date;
        $res['hash'] = $hash;
        $res['type'] = $type;
        $res['total_pool_amount'] = $total_pool_amount;
        $res['founder_pool_amt'] = $founder_pool_amt;
        $res['gic_pool_amt'] = $gic_pool_amt;
        $res['lic_pool_amt'] = $lic_pool_amt;
        $res['marketting_pool_amt'] = $marketting_pool_amt;
        $res['promoter_pool_amt'] = $promoter_pool_amt;

        return is_mobile($type, "orbitx_pool", $res, 'view');

    }
    function orbitx_api_pool_report(Request $request)
    {
        $type = $request->input('type');
        $isExport = $request->input('export') === 'yes';
        $start_date = $request->input('start_date');
        $formattedDate = date("Y-m-d", strtotime($start_date));
        
        $total_pool_amount = DB::table('other_pools')
        ->select('pool', DB::raw('SUM(amount) as total_amount'))
        ->groupBy('pool')
        ->get();

        $founder_pool_amt=DB::table('other_pools')->where('pool', 'FOUNDER')->whereDate('created_on', Carbon::today())->sum('amount');
        $gic_pool_amt=DB::table('other_pools')->where('pool', 'GIC')->whereDate('created_on', Carbon::today())->sum('amount');
        $lic_pool_amt=DB::table('other_pools')->where('pool', 'LIC')->whereDate('created_on', Carbon::today())->sum('amount');
        $marketting_pool_amt=DB::table('other_pools')->where('pool', 'MARKETING')->whereDate('created_on', Carbon::today())->sum('amount');
        $promoter_pool_amt=DB::table('other_pools')->where('pool', 'PROMOTER')->whereDate('created_on', Carbon::today())->sum('amount');


        $curl = curl_init();

        curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://defi.orbitx.world/founder-pool/api.php',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => array('pool' => 'FOUNDER','date' => $formattedDate),
        ));

        $founder_response = curl_exec($curl);

        curl_close($curl);
        $founder_data = json_decode($founder_response, true);

        $curl1 = curl_init();

        curl_setopt_array($curl1, array(
        CURLOPT_URL => 'https://defi.orbitx.world/founder-pool/api.php',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => array('pool' => 'MARKETING','date' => $formattedDate),
        ));

        $marketting_response = curl_exec($curl1);

        curl_close($curl1);
        $marketting_data = json_decode($marketting_response, true);

        $curl2 = curl_init();

        curl_setopt_array($curl2, array(
        CURLOPT_URL => 'https://defi.orbitx.world/founder-pool/api.php',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => array('pool' => 'PROMOTER','date' => $formattedDate),
        ));

        $promoter_response = curl_exec($curl2);

        curl_close($curl2);
        $promoter_data = json_decode($promoter_response, true);

        $curl3 = curl_init();

        curl_setopt_array($curl3, array(
        CURLOPT_URL => 'https://defi.orbitx.world/founder-pool/api.php',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => array('pool' => 'LIC','date' => $formattedDate),
        ));

        $llc_response = curl_exec($curl3);

        curl_close($curl3);
        $lic_data = json_decode($llc_response, true);

        $curl4 = curl_init();

        curl_setopt_array($curl4, array(
        CURLOPT_URL => 'https://defi.orbitx.world/founder-pool/api.php',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => array('pool' => 'GIC','date' => $formattedDate),
        ));

        $gic_response = curl_exec($curl4);

        curl_close($curl4);
        $glc_data = json_decode($gic_response, true);


        $res['status_code'] = 1;
        $res['message'] = "Report generated successfully";
        $res['start_date'] = $start_date;
        $res['glc_data'] = $glc_data;
        $res['lic_data'] = $lic_data;
        $res['promoter_data'] = $promoter_data;
        $res['marketting_data'] = $marketting_data;
        $res['founder_data'] = $founder_data;

        $res['total_pool_amount'] = $total_pool_amount;
        $res['founder_pool_amt'] = $founder_pool_amt;
        $res['gic_pool_amt'] = $gic_pool_amt;
        $res['lic_pool_amt'] = $lic_pool_amt;
        $res['marketting_pool_amt'] = $marketting_pool_amt;
        $res['promoter_pool_amt'] = $promoter_pool_amt;

        return is_mobile($type, "orbitx_api_pool", $res, 'view');

    }
    function stablebonnds_userdetail_report_process(Request $request)
    {
        $type = $request->input('type');
        $isExport = $request->input('export') === 'yes';
        $start_date = $request->input('start_date');
        $end_date = $request->input('end_date');
        $refferal_code = $request->input('refferal_code');
        
        $whereStartDate = '';
        $whereEndDate = '';
        $whereHash = '';

        if($start_date != '')
        {
            $whereStartDate = " AND date_format(user_stablebond_details.created_at, '%Y-%m-%d') >= '".date('Y-m-d', strtotime($start_date))."'";
        }
        
        if($end_date != '')
        {
            $whereEndDate = " AND date_format(user_stablebond_details.created_at, '%Y-%m-%d') <= '".date('Y-m-d', strtotime($end_date))."'";
        }

        if($refferal_code != '')
        {
            $whereHash = "AND users.wallet_address = '".$refferal_code."'";
        }

        $data = user_stablebond_details::select('users.wallet_address','user_stablebond_details.*')->join('users','user_stablebond_details.user_id','=','users.id')->whereRaw("1=1 ".$whereStartDate.$whereEndDate.$whereHash)->paginate(20)->toArray();

        $res['status_code'] = 1;
        $res['message'] = "Report generated successfully";
        $res['data'] = $data;
        $res['start_date'] = $start_date;
        $res['end_date'] = $end_date;
        $res['refferal_code'] = $refferal_code;

        return is_mobile($type, "stablebonds_userdetail_report", $res, 'view');

    }
}
