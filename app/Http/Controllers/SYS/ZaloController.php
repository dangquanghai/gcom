<?php

namespace App\Http\Controllers\SYS;

use Illuminate\Http\Request;
use App\Http\Controllers\SYS\SysController;
use Zalo\Zalo;
use Zalo\ZaloEndPoint;

use Validator;
use DateTime;
use GuzzleHttp\Client;



class ZaloController extends SysController
{
    protected static $instance;
    protected $zalo;
    public $ZaloAppId= "785881563324472798";
    public $AppSecret="V2E6nJ3I8i6ENB4qKYLf";
   
    protected $cookie_name = "zalo_cookie";
    protected $link_default = "https://jinod.com/zalo/index";
    protected $callBackUrl = "https://jinod.com/zalo/auth";

    protected $urlGetZaloFriends = "https://jinod.com/zalo/friends";

    protected $config = array(
        'app_id' => '785881563324472798',
        'app_secret' => 'V2E6nJ3I8i6ENB4qKYLf',
        'callback_url' => 'https://jinod.com/zalo/auth'
    );

    public function __construct() {
        $this->zalo = new Zalo($this->config);
    }


    // ----------------gọi tới link login zalo + xin cap phép -----------------
    public function index() {
        if (!isset($_COOKIE[$this->cookie_name])) {
            $helper = $this->zalo->getRedirectLoginHelper();
            $loginUrl = $helper->getLoginUrl($this->callBackUrl); // This is login ur
            //echo("Dang login </br>");
           // header("Location: {$loginUrl}");
            return redirect($loginUrl);
        }
        else
        {
            // echo("Login Thành Công </br>");
            //$this->auth();// lấy access token ghi vào cookie
            $this->getAllFriends();
        }
    }

    // ----------------lấy access_token, được gọi từ route với link callback /zalo/auth
    public function auth() {
    try {
        $helper = $this->zalo -> getRedirectLoginHelper();
        $oauthCode = isset($_GET['code']) ? $_GET['code'] : "THIS NOT CALLBACK PAGE !!!"; // get oauthoauth code from url params
        $accessToken = $helper->getAccessToken($this->callBackUrl); // get access token
        if ($accessToken != null) {
            $expr = $accessToken->getExpiresAt(); // get expires time
            // store the Access Token as a HTTP only cookie
            setcookie($this->cookie_name, $accessToken, time()+3600, '/', '', true, true );

            return redirect($this->urlGetZaloFriends);
            //xin cấp quyền thành công, bước tiếp theo chy về đâu đós
            //$this->getMe();
        }
        } catch (ZaloResponseException $e) {
            // When Graph returns an error
            echo 'Graph returned an error: ' . $e->getMessage();
            exit;
        } catch (ZaloSDKException $e) {
            // When validation fails or other local issues
            echo 'Zalo SDK returned an error: ' . $e->getMessage();
            exit;
        }
    }

    //-------- goi tin nhắn cho bạn bè
    public function sendMessage($id_friend,$Message) 
    {
        //$Message ="Dear Sir/Madam, Mr Dang Quang Hai has change price of sku KGWK from  115 usd to 150 usd on channel WM MKP at 8h45 13/07/2021"  ;
        
        if (!isset($_COOKIE[$this->cookie_name])) {
            echo "Cookie named '" . $this->cookie_name . "' is not set!";
            header("Location: {$this->link_default}");
        } else {
            $accessToken = $_COOKIE[$this->cookie_name];
            //$params = ['message' => $Message, 'to' => $id_friend, 'link' => 'https://jinod.com/Sales/SalesProductInforController/4171/edit'];

            $params = ['message' => $Message, 'to' => $id_friend, 'link' => 'https://jinod.com/Sales/SalesProductInforController/4171/edit'];
            $response = $this->zalo->post(ZaloEndPoint::API_GRAPH_MESSAGE, $accessToken, $params);
            echo '<br><br>';
            print_r($response->getDecodedBody());
            echo '<br><br>';
        }
    }
//---------- lấy thông tin của người dùng app
public function getMe() {
        
    if (!isset($_COOKIE[$this->cookie_name])) {
        echo "Cookie named '" . $this->cookie_name . "' is not set!";
        header("Location: {$this->link_default}");
    } else {
        $accessToken = $_COOKIE[$this->cookie_name];
        $response = $this->zalo->get(ZaloEndPoint::API_GRAPH_ME, $accessToken, ['fields' => 'id,name,birthday,gender,picture']);
        echo '<br><br>';
        print_r($response->getDecodedBody());
        echo '<br><br>';
    }
}
//----------get list friend dang su dung app 
public function getFriendsUsedApp() {
    if (!isset($_COOKIE[$this->cookie_name])) {
        echo "Cookie named '" . $this->cookie_name . "' is not set!";
        header("Location: {$this->link_default}");
    } else {
        $accessToken = $_COOKIE[$this->cookie_name];
        $params = ['offset' => 0, 'limit' => 100, 'fields' => "id, name"];
        $response = $this->zalo->get(ZaloEndPoint::API_GRAPH_FRIENDS, $accessToken, $params);
        echo '<br><br>';
        print_r($response->getDecodedBody());
        echo '<br><br>';
    }
}
//----------get list all friends chua dung app
public function getAllFriends() {
    $data;
    if (!isset($_COOKIE[$this->cookie_name])) {
        echo "Cookie named '" . $this->cookie_name . "' is not set!";
        header("Location: {$this->link_default}");
    } else {
        $accessToken = $_COOKIE[$this->cookie_name];
        $params = ['offset' => 0, 'limit' => 100, 'fields' => "id, name"];
        $response = $this->zalo->get(ZaloEndPoint::API_GRAPH_INVITABLE_FRIENDS, $accessToken, $params);
        $data = $response->getDecodedBody();
        
        // echo '<br><br>';
        // print_r($response->getDecodedBody());
        // echo '<br><br>';
        return view('SYS.zalo',compact(['data']));
    }
}
//----------mời bạn bè xài app, phi xài app thì mới gủi tin nhắn được.
public function sendAppRequest($FriendID) {
    if (!isset($_COOKIE[$this->cookie_name])) {
        echo "Cookie named '" . $this->cookie_name . "' is not set!";
         header("Location: {$this->link_default}");
    } else {
        $accessToken = $_COOKIE[$this->cookie_name];
        $params = ['message' => 'Test function moi su dung ung dung https://jinod.com/zalo/index', 'to' => $FriendID];
        $response = $this->zalo->post(ZaloEndPoint::API_GRAPH_APP_REQUESTS, $accessToken, $params);
        echo '<br><br>';
        print_r($response->getDecodedBody());
        echo '<br><br>';
    }
}
}
