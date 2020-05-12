<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Mockery\Exception;
use App\Models\User;

class UserController extends Controller {
    //
    protected $data = null;
    protected $msg = null;
    protected $wechat_api_status = 1;
    protected $openid_session_key;

    private function set_data (Request $request) {
        $mod = array(
            'code' => [
                'required',
                'regex:/^[a-zA-Z\d]+$/'//小程序调用验证code
            ],
            'nick_name' => ['required'],//昵称
            'avatarUrl' => ['required'],//头像地址
        );
        if (!$request->has(array_keys($mod))) {
            return $this->msg = error_msg(403,0);
        }
        $data = $request->only(array_keys($mod));
        if (Validator::make($data, $mod)->fails()) {
            return $this->msg = error_msg(403, 2);
        }
        return $this->data = $data;
    }

    public function login (Request $request) {
        $this->set_data($request);
        if ($this->data === null) {
            return $this->msg;
        }
//        try {
//            $this->wechat_api_status = $this->get_openid_sessionkey($this->data['code']);
//        } catch (Exception $wechat_error) {
//            return error_msg($wechat_error->getCode(), $wechat_error->getMessage());
//        }
//        dump($this->data);
        $user = new User();
        //$this->openid_session_key['openid']
        $user=$user->firstOrCreate(['open_id' => "oZ_AN5ISqFZoLFDVhP9DU4TqK-F0"],[
            'username' => $this->data['nick_name'],
            'open_id' => "oZ_AN5ISqFZoLFDVhP9DU4TqK-F0",//$this->openid_session_key['openid']
            'avatarUrl' => $this->data['avatarUrl']
        ]);
        session(['login' => true, 'uid' => $user->id]);
        return msg(200,1);
    }

    //访问微信服务器接口，根据小程序提供code获取open_id和session_key
    private function get_openid_sessionkey ($code) {
        //微信服务器api
        $openid_seeeion_key_api = "https://api.weixin.qq.com/sns/jscode2session?";
        $openid_seeeion_key_api = $openid_seeeion_key_api . "appid=" . config('vphere.appid') . "&secret=" . config('vphere.secret') . "&js_code={$code}&grant_type=authorization_code";
        //获取openid和session_key
        $openid_session_key = $this->httpWechatGet($openid_seeeion_key_api);
        if (empty($openid_session_key)) {
            //post 数据为空 抛出错误 0 参数缺失
            throw new Exception(11, 403);
        }
        //返回
        //访问微信服务器api并json格式化微信服务器返回
        $openid_sessionkey_json = json_decode($openid_session_key, true);
        //键名获取
        $openid_sessionkey_json_keys = array_keys($openid_sessionkey_json);
        switch ($openid_sessionkey_json_keys[0]) {
            case "session_key":
                return $this->openid_session_key = $openid_sessionkey_json;
            case "errcode":
                throw new Exception($openid_sessionkey_json['errmsg'], 403);
            default:
                throw new Exception(9, 403);
        }
    }
    private function httpWechatGet ($url) {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_TIMEOUT, 500);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_URL, $url);
        $result = curl_exec($curl);
        if (curl_errno($curl)) {
            return msg(500, 8);
        }
        curl_close($curl);
        return $result;
    }
}
