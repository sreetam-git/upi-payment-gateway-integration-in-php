<!DOCTYPE html>
<html>

<head>
	<title>UPI Gateway - Payment Test Demo</title>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>

<body>
	<div class="container p-5">
		<div class="row">
			<div class="col-md-7 mb-2">
				<?php
				if (isset($_POST['payment'])) {
					$key = "";	// Your Api Token https://merchant.upigateway.com/user/api_credentials
					$post_data = new stdClass();
					$post_data->key = $key;
					$post_data->client_txn_id = (string) rand(100000, 999999); // you can use this field to store order id;
					$post_data->amount = $_POST['txnAmount'];
					$post_data->p_info = "product_name";
					$post_data->customer_name = $_POST['customerName'];
					$post_data->customer_email = $_POST['customerEmail'];
					$post_data->customer_mobile = $_POST['customerMobile'];
					$post_data->redirect_url = "https://yourdomain.com/redirect_page.php"; // automatically ?client_txn_id=xxxxxx&txn_id=xxxxx will be added on redirect_url
					$post_data->udf1 = "extradata";
					$post_data->udf2 = "extradata";
					$post_data->udf3 = "extradata";

					$curl = curl_init();
					curl_setopt_array($curl, array(
						CURLOPT_URL => 'https://api.ekqr.in/api/create_order',
						CURLOPT_RETURNTRANSFER => true,
						CURLOPT_ENCODING => '',
						CURLOPT_MAXREDIRS => 10,
						CURLOPT_TIMEOUT => 30,
						CURLOPT_FOLLOWLOCATION => true,
						CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
						CURLOPT_CUSTOMREQUEST => 'POST',
						CURLOPT_POSTFIELDS => json_encode($post_data),
						CURLOPT_HTTPHEADER => array(
							'Content-Type: application/json'
						),
					));
					$response = curl_exec($curl);
					curl_close($curl);

					$result = json_decode($response, true);
					if ($result['status'] == true) {
						echo '<script>location.href="' . $result['data']['payment_url'] . '"</script>';
						exit();
					}

					echo '<div class="alert alert-danger">' . $result['msg'] . '</div>';
				}
				?>
				<h2>Test Demo</h2>
				<span>Fill Payment Detail and Pay</span>
				<hr>
				<form action="" method="post">
					<h4>Txn Amount:</h4>
					<input type="text" name="txnAmount" value="1" class="form-control" placeholder="Enter Txn Amount" readonly><br>
					<h4>Customer Name:</h4>
					<input type="text" name="customerName" placeholder="Enter Customer Name" class="form-control" required><br>
					<h4>Customer Mobile:</h4>
					<input type="text" name="customerMobile" placeholder="Enter Customer Mobile" maxlength="10" class="form-control" required><br>
					<h4>Customer Email:</h4>
					<input type="email" name="customerEmail" placeholder="Enter Customer Email" class="form-control" required><br>
					<input type="submit" name="payment" value="Payment" class="btn btn-primary">
				</form>
			</div>
		</div>

	</div>
</body>

</html>