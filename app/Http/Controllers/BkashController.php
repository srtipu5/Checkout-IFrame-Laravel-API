<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Session;
use Illuminate\Support\Str;

class BkashController extends Controller
{
    private $base_url;

    public function __construct()
    {
        // Sandbox Base URL
          $this->base_url = 'https://checkout.sandbox.bka.sh/v1.2.0-beta';

        // Live Base URL
        //$this->base_url = 'https://checkout.pay.bka.sh/v1.2.0-beta'; 
    }

    public function authHeaders(){
        return array(
            'Content-Type:application/json',
            'Authorization:' .$this->grant(),
            'X-APP-Key:'.env('BKASH_CHECKOUT_APP_KEY')
        );
    }
         
    public function curlWithBody($url,$header,$method,$body_data_json){
        $curl = curl_init($this->base_url.$url);
        curl_setopt($curl,CURLOPT_HTTPHEADER, $header);
        curl_setopt($curl,CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($curl,CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl,CURLOPT_POSTFIELDS, $body_data_json);
        curl_setopt($curl,CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($curl, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        $response = curl_exec($curl);
        curl_close($curl);
        return $response;
    }

    public function curlWithoutBody($url,$header,$method){
        $curl = curl_init($this->base_url.$url);
        curl_setopt($curl,CURLOPT_HTTPHEADER, $header);
        curl_setopt($curl,CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($curl,CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl,CURLOPT_FOLLOWLOCATION, 1);
        $response = curl_exec($curl);
        curl_close($curl);
        return $response;
    }

    public function grant()
    {
        $header = array(
                'Content-Type:application/json',
                'username:'.env('BKASH_CHECKOUT_USER_NAME'),
                'password:'.env('BKASH_CHECKOUT_PASSWORD')
                );

        $header_data_json=json_encode($header);

        $body_data = array('app_key'=> env('BKASH_CHECKOUT_APP_KEY'), 'app_secret'=>env('BKASH_CHECKOUT_APP_SECRET'));
        $body_data_json=json_encode($body_data);
        
        $response = $this->curlWithBody('/checkout/token/grant',$header,'POST',$body_data_json);


        $token = json_decode($response)->id_token;

        return $token;
    }

    public function createPayment(Request $request)
    {    
        $header =$this->authHeaders();

        $body_data = array(
            'amount' => 1,
            'currency' => 'BDT',
            'intent' => 'sale',
            'merchantInvoiceNumber' => "Inv".Str::random(8)
        );
        $body_data_json=json_encode($body_data);

        $response = $this->curlWithBody('/checkout/payment/create',$header,'POST',$body_data_json);
        
        Session::put('paymentID', json_decode($response)->paymentID);

        return $response;
    }

    public function executePayment(Request $request)
    {
        $paymentID = Session::get('paymentID');

        $header =$this->authHeaders();

        $response = $this->curlWithoutBody('/checkout/payment/execute/'.$paymentID,$header,'POST');
        
        $arr = json_decode($response,true);

        if(array_key_exists("errorCode",$arr) && $arr['errorCode'] != '0000'){
            Session::put('errorMessage', $arr['errorMessage']);
        }else if(array_key_exists("message",$arr)){
            // if execute api failed to response
            sleep(1);
            $response = $this->queryIframe($paymentID);
        }
        
        Session::put('response',$response);

        $response_arr = json_decode($response,true);

        if(isset($response_arr['trxID'])){
            // your database operation
            // save $response
        }

        return $response;
    }

    public function queryIframe($paymentID){

        $header =$this->authHeaders();

        $response = $this->curlWithoutBody('/checkout/payment/query/'.$paymentID,$header,'GET');

        return $response;
    }

    public function refundPayment(Request $request)
    {
        $header =$this->authHeaders();

        $body_data = array(
            'paymentID' => $request->paymentID,
            'amount' => $request->amount,
            'trxID' => $request->trxID,
            'sku' => 'sku',
            'reason' => 'Quality issue'
        );
     
        $body_data_json=json_encode($body_data);

        $response = $this->curlWithBody('/checkout/payment/refund',$header,'POST',$body_data_json);
        
       // your database operation
       // save $response

        return $response;
    }

}

