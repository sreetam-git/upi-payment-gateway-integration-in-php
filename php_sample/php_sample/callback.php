<?php

// This is the callback file which will be called after payment is done directly from UPI gateway server.
// you can set the webhook url at https://merchant.upigateway.com/user/api_credentials
// You can also use IP check to prevent unauthorized access.
// $ip = $_SERVER['REMOTE_ADDR'];
// if($ip != '101.53.134.70'){
// 	die('Unauthorized Access');
// }

if(isset($_POST['id']) && $_POST['client_txn_id']){
	$id = $_POST['id']; // upi gateway transaction id
	$customer_vpa = $_POST['customer_vpa']; // upi id from which payment is made
	$amount = $_POST['amount']; // 1
	$client_txn_id = $_POST['client_txn_id']; // client_txn_id set while creating order 
	$customer_name = $_POST['customer_name']; // 
	$customer_email = $_POST['customer_email']; // 
	$customer_mobile = $_POST['customer_mobile']; // 
	$p_info = $_POST['p_info']; // p_info set while creating order 
	$upi_txn_id = $_POST['upi_txn_id']; // UTR or Merchant App Transaction ID
	$status = $_POST['status']; // failure
	$remark = $_POST['remark']; // Remark of Transaction
	$udf1 = $_POST['udf1']; // user defined data added while creating order
	$udf2 = $_POST['udf2']; // user defined data added while creating order
	$udf3 = $_POST['udf3']; // user defined data added while creating order
	$redirect_url = $_POST['redirect_url']; // redirect_url added while creating order
	$txnAt = $_POST['txnAt']; // 2023-05-11 date of transaction
	$createdAt = $_POST['createdAt']; // 2023-05-11T12%3A15%3A23.000Z

	if($_POST['status']){
		echo "Transaction Successful";
		// All the Process you want to do after successfull payment
		// Please also check the txn is already success in your database.
	}

	if($_POST['status'] == 'failure'){
		echo "Transaction Failed";
	}
}
?>ok