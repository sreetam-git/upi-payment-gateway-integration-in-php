<?php

defined('BASEPATH') OR exit('No direct script access allowed');

//Powered by Manikanta
class Upigateway {

//    private $MERCHANT_KEY = "A9JB3k";
//        private $SALT = "1uoCYHgv";
    // Merchant key here as provided by Cash free
    private $MERCHANT_KEY = "179193d6-810b-4b51-ae52-002203bb7b32"; //means client_id 
    // Merchant Salt as provided by Payu
    private $SALT; // = "fc49a78b02f3d132f0c75b1da9c8a03a0e241e7e"; //means client_secret_id
    private $HAODA_API_BASE_URL = '';
    private $CASH_FREE_BASE_STATUS_URL = '';

    function __construct($APP_ID, $SECRET_KEY) {
        $CI = & get_instance();
        $this->MERCHANT_KEY = $APP_ID; // jmBfVYKAvZQECsSWeNJH
        $this->SALT = $SECRET_KEY; //jmBfVYKAvZQECsSWeNJH
    }

    function setServerMode($servermode = 'live') {
        $this->UPI_API_BASE_URL = $servermode == 'test' ?
                'https://merchant.upigateway.com/api/' :
                'https://merchant.upigateway.com/api/';
    }

    private $action = '';
    private $posted = array();
    private $txnid;
    public $hash = '';
    private $amount = '';
    private $orderCurrency = '';
    private $phonenumber = '';
    private $productInfo = '';
    private $email = '';
    private $notifyUrl = '';
    private $returnUrl = '';
    private $consumername = '';
    private $customerId = '';

    function generate_auto_transaction_id() {
        if (empty($posted['txnid'])) {
            // Generate random transaction id
            $txnid = substr(hash('sha256', mt_rand() . microtime()), 0, 20);
        } else {
            $txnid = $posted['txnid'];
        }
    }

    function goto_collect_money() {
        $this->showForm();
        $this->action = $this->UPI_API_BASE_URL;
    }

    function setTransactionid($invoiceid) {
        $this->txnid = $invoiceid;
    }

    function setAmount($amount) {
        $this->amount = $amount;
    }

    function setCustomerId($customerId) {
        $this->customerId = $customerId;
    }

    function setOrderCurrency($currency) {
        $this->orderCurrency = $currency;
    }

    function setMerchantkey($key) {
        $this->MERCHANT_KEY = $key;
    }

    function setEmail($email) {
        $this->email = $email;
    }

    function setConsumerName($name) {
        $this->consumername = $name;
    }

    function setProductInfo($proInfo) {
        $this->productInfo = $proInfo;
    }

    function setPhoneNumber($phone) {
        $this->phonenumber = $phone;
    }

    function setNotify_url($surl) {
        $this->notifyUrl = $surl;
    }

    function setReturn_url($furl) {
        $this->returnUrl = $furl;
    }

    function createOrder() {

        $post = [
            "key" => $this->MERCHANT_KEY,
            "client_txn_id" => $this->txnid,
            "amount" => (string)$this->amount,
            "p_info" => 'SR Cinemas',
            "customer_name" => $this->consumername,
            "customer_email" => $this->email,
            "customer_mobile" => $this->phonenumber,
            "redirect_url" => $this->returnUrl
        ]; 

        $post_data = json_encode($post);
        
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $this->UPI_API_BASE_URL . 'create_order',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_POSTFIELDS => $post_data,
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_HTTPHEADER => array(
                'Content-Type:application/json'
            ),
        ));

        $response = curl_exec($curl);
        curl_close($curl);
//        print_r($response);
//        die;

        $response = json_decode($response, true);

        if ($response["status"] == true) {
            return $response["data"]["payment_url"];
            //return $response["data"]["upi_intent"]["bhim_link"];
            echo json_encode($arr);
            die;
        }else if(!$response["status"]){
            $arr = [
                "status" => "invalid",
                "title" => "Error",
                "message" => $response['msg'],
            ];
            echo json_encode($arr);
            die;
        }

        
        return $response["data"]["payment_url"];
    }

    function getTransactionStatus($orderId, $transaction_date) {
        $post = [
            "key" => $this->MERCHANT_KEY,
            "client_txn_id" => $orderId,
            "txn_date" => $transaction_date
        ];

        $post_data = json_encode($post);

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $this->UPI_API_BASE_URL . 'check_order_status',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_POSTFIELDS => $post_data,
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_HTTPHEADER => array(
                'Content-Type:application/json'
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        return json_decode($response);
    }

    function showForm() {
        $this->image_url = base_url() . 'assets/images/loading.gif';
        echo $html = <<<HTML

<html>
<head>

</head>
<body onLoad="submitPayuForm()">
<h2 style="text-align:center">Please wait connecting to payment gateway</h2>
<center><img src="$this->image_url" alt="Connecting..." align="absmiddle"/></center>
<form action="$this->CASH_FREE_BASE_URL" method="post" name="cashFreeForm">
  <input type="hidden" name="appId" value="$this->MERCHANT_KEY" />
    <input type="hidden" name="signature" value="$this->hash"/>
  <input type="hidden" name="orderId" value="$this->txnid" />
  <table  style="display:none">
    <tr>
      <td><b>Mandatory Parameters</b></td>
    </tr>
    <tr>
      <td>Amount: </td>
      <td><input type="hidden" name="orderAmount" value="$this->amount" />
      <input type="hidden" name="orderCurrency" value="$this->orderCurrency" /></td>
      <td>First Name: </td>
      <td><input type="hidden" name="customerName" id="customerName" value="$this->consumername" /></td>
    </tr>
    <tr>
      <td>Email: </td>
      <td><input type="hidden" name="customerEmail" id="email" value="$this->email" /></td>
      <td>Phone: </td>
      <td><input type="hidden" name="customerPhone" value="$this->phonenumber" /></td>
    </tr>
    <tr>
      <td>Product Info: </td>
      <td colspan="3"><textarea name="orderNote" style="display:none">$this->productInfo</textarea></td>
    </tr>
    <tr>
      <td>Notify URI: </td>
      <td colspan="3"><input type="hidden" name="notifyUrl" value="$this->notifyUrl" size="64" /></td>
    </tr>
    <tr>
      <td>Return URI: </td>
      <td colspan="3"><input type="hidden" name="returnUrl" value="$this->returnUrl" size="64" /></td>
    </tr>
    <tr>
                
      <td colspan="4">
   <input type="submit" value="Submit" style="display:none"/></td>
    </tr>
  </table>
</form>
</body>
</html>

HTML;
    }

}
