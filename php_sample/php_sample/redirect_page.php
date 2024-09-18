<?php
error_reporting(E_ERROR | E_PARSE);

$echo = "";
if (isset($_GET['client_txn_id'])) {
	$key = "";	// Your Api Token https://merchant.upigateway.com/user/api_credentials
	$post_data = new stdClass();
	$post_data->key = $key;
	$post_data->client_txn_id = $_GET['client_txn_id']; // you will get client_txn_id in GET Method
	$post_data->txn_date = date("d-m-Y"); // date of transaction

	$curl = curl_init();
	curl_setopt_array($curl, array(
		CURLOPT_URL => 'https://api.ekqr.in/api/check_order_status',
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
		// Txn Status = 'created', 'scanning', 'success','failure'

		if ($result['data']['status'] == 'success') {
			$echo = '<div class="alert alert-danger"> Transaction Status : Success</div>';
			$txn_data = $result['data'];
			// All the Process you want to do after successfull payment
			// Please also check the txn is already success in your database.
		}
		$txn_data = $result['data'];
		$echo = '<div class="alert alert-danger"> Transaction Status : ' . $result['data']['status'] . '</div>';
	}
}
?>
<!DOCTYPE html>
<html>

<head>
	<title>Payment Gateway - Test Response</title>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>

<body>
	<div class="container p-5">
		<div class="row">
			<div class="col-md-8 mb-2">
				<h2>Response</h2>
				<p>Payment Gateway - Test Response</p>
				<?php echo $echo;
				 // show table of response
				 if (isset($txn_data)) {
					echo '<table class="table table-bordered">
					<thead>
					  <tr>
						<th>Key</th>
						<th>Value</th>
					  </tr>
					</thead>
					<tbody>';
					foreach ($txn_data as $key => $value) {
						echo '<tr>
						<td>' . $key . '</td>
						<td>' . @$value . '</td>
					  </tr>';
					}
					echo '</tbody>
				  </table>';
				 }
				?>

			</div>
		</div>

	</div>
</body>

</html>