<?php

require_once APPPATH . 'libraries/Upigateway.php';

class Upigateway_payment_response extends CI_Controller {

    function __construct() {
        parent::__construct();
        $this->load->model('subscription_model');
    }

    function fetch_info() {
        $transaction_id = $this->input->get_post("client_txn_id");
        log_message('error', $transaction_id);
        $this->db->where("transaction_id", $transaction_id);
        $this->db->limit(1);
        $transaction_row = $this->db->get("subscription")->row();

        $pg_settings = $this->db->get_where('config',['title'=>'upi_gateway_key'])->row();
        $obj = new Upigateway($pg_settings->value, $pg_settings->value);
        $obj->setServerMode(strtolower(PAYMENT_GATEWAY_MODE));
        //$obj->setTransactionid($item->transaction_id);
        $transaction_date = date("d-m-Y", ($transaction_row->timestamp_from));
        $response = $obj->getTransactionStatus($transaction_id, $transaction_date);
        return $response;
    }

    function index() {
        $deposit_transaction_id = $this->input->get_post("client_txn_id");
        $response = $this->fetch_info();
//        log_message('error', print_r($response, true));
        if ($response->data->status === "success") {

            if ($this->input->get_post("req_source") == "admin") {
                $this->subscription_model->mark_as_transaction_successful($deposit_transaction_id, $response->data->UTR);
            }
            else{
                log_message('error', 'transaction success');
                $this->subscription_model->mark_as_transaction_successful($deposit_transaction_id, $response->data->id, "", $response->data->remark);
            }

            redirect("upigateway_payment_response/success?client_txn_id=" . $deposit_transaction_id."&req_source=".$this->input->get_post("req_source"));
        } else if ($response->data->status === "failure") {
            $this->subscription_model->mark_as_transaction_faliure($deposit_transaction_id, $response->data->id, "", $response->data->remark);
            redirect("upigateway_payment_response/failure?client_txn_id=" . $deposit_transaction_id."&req_source=".$this->input->get_post("req_source"));
        } else if ($response->data->status === "scanning") {
            redirect("upigateway_payment_response/scanning?client_txn_id=" . $deposit_transaction_id."&req_source=".$this->input->get_post("req_source"));
        } else if ($response->data->status === "created") {
            redirect("upigateway_payment_response/created?client_txn_id=" . $deposit_transaction_id."&req_source=".$this->input->get_post("req_source"));
        } else {
            echo json_encode($response, JSON_PRETTY_PRINT);
        }
    }

    function success() {
//        $response = $this->fetch_info();
//        $this->data["payment_response"] = $response->data;
//        $this->load->view("upigateway_payment_info", $this->data);
        $this->session->set_flashdata('success', trans('Your_subscription_request_success'));
        redirect(base_url() . 'subscription/upgrade');
    }

    function failure() {
        $response = $this->fetch_info();
//        $this->data["payment_response"] = $response->data;
//        $this->load->view("upigateway_payment_info", $this->data);
        $this->session->set_flashdata('error', trans('Your_subscription_request_failed'));
        redirect(base_url() . 'subscription/upgrade');
    }

    function scanning() {
        $response = $this->fetch_info();
        $this->data["payment_response"] = $response->data;
        $this->load->view("upigateway_payment_info", $this->data);
    }

    function created() {
        $response = $this->fetch_info();
        $this->data["payment_response"] = $response->data;
        $this->load->view("upigateway_payment_info", $this->data);
    }
}
