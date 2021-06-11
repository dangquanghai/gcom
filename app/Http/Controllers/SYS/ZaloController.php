<?php

namespace App\Http\Controllers\SYS;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class ZaloController extends Controller
{
    /*
    zalo id: 785881563324472798
    zalo access: V2E6nJ3I8i6ENB4qKYLf
    Home URL:https://example.com
    callback URL: https://example.com/zalo
    */
    
    public $api_url ="https://example.com";
    public $access_token ="V2E6nJ3I8i6ENB4qKYLf";

    

    // Perform a GET request to Zalo
    function zalo_get( $api_url, $access_token, $params = array(), &$res_hdr = null ) 
    {
        $cli_hdr = getallheaders();
        $req_hdr = array(
            'Accept: application/json',
            'Content-Type: application/json',
        );
        $res_hdr = array();
        $url = $api_url . "?access_token=" . $access_token;
        if ( !empty($params) )
            $url .= '&'. http_build_query($params);
        $curl = curl_init();
        $curl_params = array(
            CURLOPT_URL => $url,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => $req_hdr,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_SSL_VERIFYPEER => true,
        );
        curl_setopt_array($curl, $curl_params);
        curl_setopt( $curl, CURLOPT_HEADERFUNCTION,
            function( $curl, $header ) use(&$res_hdr) {
                $parts = explode( ": ", $header, 2 );
                if ( count($parts) > 1 )
                    $res_hdr[$parts[0]] = $parts[1];
                return strlen( $header );
            }
        );
        $output = curl_exec($curl);
        curl_close($curl);
        return $output;
    }

//----------------------------------------------------------------------------
// Perform a POST request to Zalo
function zalo_post( $api_url, $access_token, $data, &$res_hdr = null ) 
{
    $cli_hdr = getallheaders();
    $req_hdr = array(
        'Accept: application/json',
        'Content-Type: application/json',
    );
    $res_hdr = array();
    $url = $api_url . "?access_token=" . $access_token;
    $curl = curl_init();
    $curl_params = array(
        CURLOPT_URL => $url,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_HTTPHEADER => $req_hdr,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_POSTFIELDS => $data,
        CURLOPT_SSL_VERIFYPEER => true,
    );
    curl_setopt_array($curl, $curl_params);
    curl_setopt( $curl, CURLOPT_HEADERFUNCTION,
        function( $curl, $header ) use(&$res_hdr) {
            $parts = explode( ": ", $header, 2 );
            if ( count($parts) > 1 )
                $res_hdr[$parts[0]] = $parts[1];
            return strlen( $header );
        }
    );
    $output = curl_exec($curl);
    curl_close($curl);
    return $output;
}
//----------------------------------------------------------------------------
// Upload a file to Zalo
    function zalo_upload( $api_url, $access_token, $path, &$res_hdr = null ) 
    {
        $cli_hdr = getallheaders();
        $req_hdr = array(
            'Accept: application/json',
            'Content-Type: multipart/form-data',
        );
        $res_hdr = array();
        $url = $api_url . "?access_token=" . $access_token;
        $type = mime_content_type($path); // MIME type
        $size = filesize($path);
        $name = basename($path);
        $curl = curl_init();
        $curl_params = array(
            CURLOPT_URL => $url,
            CURLOPT_HTTPHEADER => $req_hdr,
            CURLOPT_POST => 1,
            CURLOPT_POSTFIELDS => array(
                'file' => new \CurlFile($path, $type, $name)
            ),
            CURLOPT_INFILESIZE => $size,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_SSL_VERIFYPEER => true,
        );
        curl_setopt_array($curl, $curl_params);
        curl_setopt( $curl, CURLOPT_HEADERFUNCTION,
            function( $curl, $header ) use(&$res_hdr) {
                $parts = explode( ": ", $header, 2 );
                if ( count($parts) > 1 )
                    $res_hdr[$parts[0]] = $parts[1];
                return strlen( $header );
            }
        );
        $output = curl_exec($curl);
        curl_close($curl);
        return $output;
    }
//----------------------------------------------------------------------------
// Zalo Rest API
function zalo_rest_api()
{
    if( !is_callable('curl_init') )
        die("CURL extension is not enabled");
    if ( isset($_GET['zapi_uri']) ) {
        $api_url = $_GET['zapi_uri'];
        unset( $_GET['zapi_uri'] );
    } else {
        die('No Zalo API');
    }
   
    if ( isset($_COOKIE["oa_access_token"]) ) {
        $access_token = $_COOKIE["oa_access_token"];
        if ( $_SERVER['REQUEST_METHOD'] === 'GET' ) {
            $response = zalo_get( $api_url, $access_token, $_GET, $res_hdr );
        } else if ( $_SERVER['REQUEST_METHOD'] === 'POST' ) {
            if ( isset($_FILES['media']) ) { // Assume the file <input> name is 'media'
                $response = zalo_upload( $api_url, $access_token, $_FILES['media']['tmp_name'], $res_hdr );
            } else {
                $data = file_get_contents("php://input");
                $response = zalo_post( $api_url, $access_token, $data, $res_hdr );
            }
        }
        // Some useful response headers from Zalo
        $response_headers = array(
            'Content-Type',
            'X-RateLimit-Limit',
            'X-RateLimit-Remain',
        );
        foreach ( $response_headers as $header )
            if ( isset($res_hdr[$header]) )
                header($header . ": " . $res_hdr[$header]);
        echo $response;
    } else {
        echo json_encode( array(
            'error' => -10000,
            'message' => 'Unauthorized access'
        ) );
    }
}
}
