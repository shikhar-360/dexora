<?php

namespace App\Http\Controllers;

use App\Models\usersModel;
use Illuminate\Http\Request;

use function App\Helpers\is_mobile;

class profileController extends Controller
{
    public function index(Request $request)
    {
        $type = $request->input('type');
        $user_id = $request->session()->get('user_id');

        if ($type == "API") {
            $user_id = $request->input('user_id');
        } else {
            $user_id = $request->session()->get('user_id');
        }

        $users = usersModel::where(['id' => $user_id])->get()->toArray();

        $res['status_code'] = 1;
        $res['message'] = "Fetched Successfully.";
        $res['data'] = $users['0'];

        return is_mobile($type, "pages.profile", $res, "view");
    }

    public function profile_update(Request $request)
    {
        $type = $request->input('type');
        $name = $request->input('name');
        $email = $request->input('email');
        $mobile_number = $request->input('mobile_number');
        $user_id = $request->session()->get('user_id');


        if ($email != '') {
            usersModel::where(['id' => $user_id])->update(['email' => $email]);
        }

        if ($name != '') {
            usersModel::where(['id' => $user_id])->update(['name' => $name]);
        }

        if ($mobile_number != '') {
            usersModel::where(['id' => $user_id])->update(['mobile_number' => $mobile_number]);
        }

        $allowedfileExtension = ['jpeg', 'jpg', 'png'];

        $request->validate([
            'file' => 'file|max:2048', // max file size in kilobytes (2 MB = 2048 KB)
        ]);

        $file = $request->file('file');

        if (isset($file)) {
            $filename = $file->getClientOriginalName();
            $extension = $file->getClientOriginalExtension();
            $check = in_array($extension, $allowedfileExtension);

            if (!$check) {
                $res['status_code'] = 0;
                $res['message'] = "Only jpeg and png files are supported ";

                return is_mobile($type, "fprofile", $res);
            }

            $file_name = "";
            if ($request->hasFile('file')) {
                $file = $request->file('file');
                $originalname = $file->getClientOriginalName();
                $og_name = "file" . '_' . date('YmdHis');
                $ext = \File::extension($originalname);
                $file_name = $og_name . '.' . $ext;
                $path = $file->storeAs('public/', $file_name);

                $users = usersModel::where(['id' => $user_id])->update(['image' => $file_name]);
            }
        }

        $res['status_code'] = 1;
        $res['message'] = "Profile Updated Successfully.";

        return is_mobile($type, "fprofile", $res);
    }

    public function password_update(Request $request)
    {
        $type = $request->input('type');
        $user_id = $request->session()->get('user_id');
        $old_password = $request->input('old_password');
        $new_password = $request->input('new_password');

        $checkPassword = usersModel::where(['id' => $user_id, 'password' => md5($old_password)])->get()->toArray();

        if (count($checkPassword) > 0) {
            $users = usersModel::where(['id' => $user_id])->update(['password' => md5($new_password)]);

            $res['status_code'] = 1;
            $res['message'] = "Password Successfully updated.";
        } else {
            $res['status_code'] = 0;
            $res['message'] = "Old password does not match.";
        }

        return is_mobile($type, "fprofile", $res);
    }
}
