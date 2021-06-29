<?php

namespace App\Http\Controllers\SYS;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class Social_ZaloController extends Controller
{
    public $ZaloAppId= "785881563324472798";
    public $AppSecret="V2E6nJ3I8i6ENB4qKYLf";
   
    // If the User Access Token is not available perform an authorization request
    public function index()
    {
     

        if ( !isset($_COOKIE["user_access_token"]) ) 
        {
            $auth_uri = "https://oauth.zaloapp.com/v3/auth?"
                    . http_build_query(array(
                            "app_id" => "785881563324472798", // <- App ID
                            "redirect_uri" =>"https://jinod.com/zalo/auth",
                            'state' => "whatever"
                        ));
                        //redirect('$auth_uri');
                        // dd($auth_uri);
            header("Location: {$auth_uri}");
        exit;
        }
        else 
        {
            $TheAccessToken ='';
            $From = 0; // Vị trí đầu tiên trong danh sách bạn bè
            $Limit = 100; // Mỗi trang một trăm người, đây cũng là giới hạn một lần lấy tối đa mà zalo cho phép
            $TheAccessToken = $_REQUEST['access_token'];
            $friend_uri= "https://graph.zalo.me/v2.0/me/invitable_friends?"
            . http_build_query(array(
                "access_token" => "{$TheAccessToken }", 
                "from" =>"{ $From }",
                "limit" =>"{ $Limit }",
                'fields' => "id,name,gender,picture"
            ));
            header("Location: {$friend_uri}");
            // List danh sách bạn bè
            
            //echo "Authentication Success!";
        }
    }
         
    //--------------------------------------------------------------------
    public function auth()
    { 
        $headers = getallheaders();
        // Just to ensure this is a request from Zalo!
   
            // Received callback data from oauth.zaloapp.com/v3/auth
            if ( isset($_REQUEST['uid']) && isset($_REQUEST['code']) && isset($_REQUEST['scope']) ) 
            {
                // Obtain the Access Token by performing a GET request to
                // https://oauth.zalo.com/v3/access_token
                $url = "https://oauth.zaloapp.com/v3/access_token?"
                    . http_build_query( array(
                            "app_id" => "785881563324472798", // <- App ID
                            "app_secret" => "V2E6nJ3I8i6ENB4qKYLf", // <- App Secret
                            "code" =>$_REQUEST['code'] // <- oAuthCode
                    ) );
                  // dd($url);
                   // redirect( $url );
                header('Location: ' . $url );
                exit;
            }
            elseif ( isset($_REQUEST['access_token']) && isset($_REQUEST['expires_in']) ) 
            {
                $expr = time() + $_REQUEST['expires_in'];
                // store the Access Token as a HTTP only cookie
                setcookie("user_access_token", $_REQUEST['access_token'], $expr, '/', '', true, true );
                // Go back to index.php
                //dd($_REQUEST['access_token']);
                // redirect('https://jinod.com/zalo/index');
                //header("Location: /zalo/index.php");
                header("Location: https://jinod.com/zalo/index");
                exit;
            }
            else 
            {
                die( "Bad request!" );
            }
        }  
}