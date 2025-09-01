<?php

namespace App\Http\Controllers;

use App\Models\supportTicketModel;
use App\Models\usersModel;
use Illuminate\Http\Request;

use function App\Helpers\is_mobile;

class supportTicketController extends Controller
{
    public function index(Request $request)
    {
        $type = $request->input('type');
        $user_id = $request->session()->get('user_id');

        $data = supportTicketModel::where(['user_id' => $user_id])->orderBy('id', 'desc')->get()->toArray();

        $res['status_code'] = 1;
        $res['message'] = "Support tickets";
        $res['data'] = $data;

        return is_mobile($type, "pages.support_tickets", $res, "view");
    }

    public function support_ticket_process(Request $request)
    {
        $type = $request->input('type');
        $user_id = $request->session()->get('user_id');
        $updateData = $request->except('_method', '_token', 'submit');

        $request->validate([
            'file' => 'file|max:1024', // max file size in kilobytes (1 MB = 1024 KB)
        ]);

        $allowedfileExtension = ['jpeg', 'jpg', 'png'];

        $file = $request->file('file');

        $filename = $file->getClientOriginalName();
        $extension = $file->getClientOriginalExtension();
        $check = in_array($extension, $allowedfileExtension);

        if (!$check) {
            $res['status_code'] = 0;
            $res['message'] = "Only jpeg and png files are supported ";

            return is_mobile($type, "supportticketsview", $res);
        }

        $checkSupportTicket = supportTicketModel::where(['user_id' => $user_id])->whereRaw("DATE_FORMAT(created_on,'%Y-%m-%d') = '" . date('Y-m-d') . "'")->get()->toArray();

        if (count($checkSupportTicket) >= 2) {
            $res['status_code'] = 0;
            $res['message'] = "You can't create more then two tickets in a day";

            return is_mobile($type, "supportticketsview", $res);
        }

        $updateData['user_id'] = $user_id;

        $getRefCode = usersModel::where(['id' => $user_id])->get()->toArray();

        $updateData['refferal_code'] = $getRefCode['0']['refferal_code'];

        $file_name = "";
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $originalname = $file->getClientOriginalName();
            $name = "file" . '_' . date('YmdHis');
            $ext = \File::extension($originalname);
            $file_name = $name . '.' . $ext;
            $path = $file->storeAs('public/', $file_name);
            $updateData['file'] = $file_name;
        }
        $updateData['created_on'] = date('Y-m-d H:i:s');

        supportTicketModel::insert($updateData);

        $res['status_code'] = 1;
        $res['message'] = "Support tickets created successfully";

        return is_mobile($type, "supportTicket", $res);
    }
}
