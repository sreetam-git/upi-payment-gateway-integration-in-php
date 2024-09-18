<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');


require(APPPATH . "third_party/razorpay/Razorpay.php");
require_once APPPATH . 'libraries/Upigateway.php';

use Razorpay\Api\Api;
use Razorpay\Api\Errors\SignatureVerificationError;

class Subscription extends Home_Core_Controller {

    function __construct() {
        parent::__construct();
        $this->load->model('subscription_model');
        $this->admin_is_login = $this->session->userdata('admin_is_login');
        $this->moderator_is_login = $this->session->userdata('moderator_is_login');
    }

    //default index function, redirects to login/dashboard
    public function index() {
        if ($this->admin_is_login != 1 && $this->moderator_is_login != 1)
            redirect(base_url() . 'login');
        if ($this->admin_is_login != 1 && $this->moderator_is_login != 1)
            redirect(base_url() . 'admin/dashboard');
    }

    function package($param1 = '', $param2 = '') {
        if ($this->admin_is_login != 1 && $this->moderator_is_login != 1)
            redirect(base_url());
        /* start menu active/inactive section */
        $this->session->unset_userdata('active_menu');
        $this->session->set_userdata('active_menu', '301');
        /* end menu active/inactive section */

        /* add new access */
        if ($param1 == 'add') {
            $data['name'] = $this->input->post('name');
            $data['day'] = $this->input->post('day');
            $data['price'] = $this->input->post('price');
            $data['ios_package_identifier'] = $this->input->post('ios_package_identifier');
            $data['status'] = $this->input->post('status');
            $this->db->insert('plan', $data);
            $this->session->set_flashdata('success', 'Package added successed.');
            redirect(base_url() . 'subscription/package/');
        }
        if ($param1 == 'update') {
            $data['name'] = $this->input->post('name');
            $data['day'] = $this->input->post('day');
            $data['price'] = $this->input->post('price');
            $data['ios_package_identifier'] = $this->input->post('ios_package_identifier');
            $data['status'] = $this->input->post('status');
            $this->db->where('plan_id', $param2);
            $this->db->update('plan', $data);
            $this->session->set_flashdata('success', 'Package update successed.');
            redirect(base_url() . 'subscription/package/');
        }
        $data['page_name'] = 'package';
        $data['page_title'] = 'Manage Package';
        $data['plans'] = $this->db->get('plan')->result_array();
        $this->load->view('admin/index', $data);
    }

    function pay_and_watch_package($param1 = '', $param2 = '') {
        if ($this->admin_is_login != 1 && $this->moderator_is_login != 1)
            redirect(base_url());
        /* start menu active/inactive section */
        $this->session->unset_userdata('active_menu');
        $this->session->set_userdata('active_menu', '3005');
        /* end menu active/inactive section */

        /* add new access */
        if ($param1 == 'add') {
            $data['name'] = $this->input->post('name');
            $data['day'] = $this->input->post('day');
            $data['price'] = $this->input->post('price');
            $data['no_of_movies'] = $this->input->post('no_of_movies');
            $data['status'] = $this->input->post('status');
            $data['created_at'] = time();
            $this->db->insert('pay_and_watch_packages', $data);
            $this->session->set_flashdata('success', 'Package added successed.');
            redirect(base_url() . 'subscription/pay_and_watch_package/');
        }
        if ($param1 == 'update') {
            $data['name'] = $this->input->post('name');
            $data['day'] = $this->input->post('day');
            $data['price'] = $this->input->post('price');
            $data['status'] = $this->input->post('status');
            $data['no_of_movies'] = $this->input->post('no_of_movies');
            $data['updated_at'] = time();
            $this->db->where('id', $param2);
            $this->db->update('pay_and_watch_packages', $data);
            $this->session->set_flashdata('success', 'Package update successed.');
            redirect(base_url() . 'subscription/pay_and_watch_package/');
        }
        $data['page_name'] = 'paywatch_package';
        $data['page_title'] = 'Manage Pay&Watch Packages';
        $data['plans'] = $this->db->get('pay_and_watch_packages')->result_array();
        $this->load->view('admin/index', $data);
    }

    function transaction_history($param1 = '', $param2 = '') {
        $this->load->model("api_v130_model");
        if ($this->admin_is_login != 1)
            redirect(base_url());
        /* start menu active/inactive section */
        $this->session->unset_userdata('active_menu');
        $this->session->set_userdata('active_menu', '3001');
        /* end menu active/inactive section */
        $data['page_name'] = 'transaction_history';
        $data['page_title'] = 'Transaction History';
        $from_date = $_GET['from_date'];
        if ($from_date != "" && $from_date != NULL) {
            $this->db->where("payment_timestamp >=", strtotime($from_date . " 00:00:00"));
        }
        $to_date = $_GET['to_date'];
        if ($to_date != "" && $to_date != NULL) {
            $this->db->where("payment_timestamp <=", strtotime($to_date . " 23:59:59"));
        }
        $device_type = $_GET['device_type'];
        if ($device_type != "" && $device_type != NULL) {
            $this->db->where("device_type", $device_type);
        }
        $expire_from = $_GET['expire_from'];
        if ($expire_from != "" && $expire_from != NULL) {
            $this->db->where("timestamp_to >=", strtotime($expire_from . " 00:00:00"));
        }
        $expire_to = $_GET['expire_to'];
        if ($expire_to != "" && $expire_to != NULL) {
            $this->db->where("timestamp_to <=", strtotime($expire_to . " 23:59:59"));
        }
        $this->db->where("payment_status", "Paid");
        $total_rows = $this->db->count_all_results('subscription');
//        echo $this->db->last_query(); die;
        $config = $this->common_model->pagination();

        $ger_arr = $_GET;
        unset($ger_arr["per_page"]);
        $QUERY = http_build_query($ger_arr);

        $config["base_url"] = base_url() . "subscription/transaction_history?" . $QUERY;
        $config["total_rows"] = $total_rows;
        $config["per_page"] = 10;
        $config["uri_segment"] = 3;
        //$config['use_page_numbers'] = TRUE;
        $config['page_query_string'] = TRUE;
        $this->pagination->initialize($config);
        $data['last_row_num'] = $this->uri->segment(3);
        //($this->uri->segment(3)) ? $this->uri->segment(3) : 0;
//        $page = $this->input->get('per_page'); //($this->uri->segment(3)) ? $this->uri->segment(3) : 0;
        $page = ($this->input->get('per_page') != "" || $this->input->get('per_page') != NULL) ? $this->input->get('per_page') : 0; //($this->uri->segment(3)) ? $this->uri->segment(3) : 0;


        $data["links"] = $this->pagination->create_links();
        $data['total_rows'] = $config["total_rows"];
        $this->db->order_by('subscription_id', "desc");
        $this->db->limit($config["per_page"], $page);
        $this->db->where("payment_status", "Paid");
        $from_date = $_GET['from_date'];
        if ($from_date != "" && $from_date != NULL) {
            $this->db->where("payment_timestamp >=", strtotime($from_date . " 00:00:00"));
        }
        $to_date = $_GET['to_date'];
        if ($to_date != "" && $to_date != NULL) {
            $this->db->where("payment_timestamp <=", strtotime($to_date . " 23:59:59"));
        }
        $device_type = $_GET['device_type'];
        if ($device_type != "" && $device_type != NULL) {
            $this->db->where("device_type", $device_type);
        }
        $expire_from = $_GET['expire_from'];
        if ($expire_from != "" && $expire_from != NULL) {
            $this->db->where("timestamp_to >=", strtotime($expire_from . " 00:00:00"));
        }
        $expire_to = $_GET['expire_to'];
        if ($expire_to != "" && $expire_to != NULL) {
            $this->db->where("timestamp_to <=", strtotime($expire_to . " 23:59:59"));
        }
        $data['subscriptions'] = $this->db->get('subscription')->result_array();
        foreach ($data['subscriptions'] as $key => $row) {
            if ($row["videos_id"] > 0) {
                $data['subscriptions'][$key]['plan_title'] = "Movie Purchase : " . $this->api_v130_model->get_movie_details_by_id($row['videos_id'])["title"];
            } else {
                $data['subscriptions'][$key]['plan_title'] = $this->api_v130_model->get_plan_name_by_id($row['plan_id']);
            }
        }
//        echo "hii"; die;
        $this->load->view('admin/index', $data);
    }

    function pay_and_watch_debit_history($param1) {
        $this->load->model("api_v130_model");
        if ($this->admin_is_login != 1)
            redirect(base_url());
        /* start menu active/inactive section */
        $this->session->unset_userdata('active_menu');
        $this->session->set_userdata('active_menu', '1415');
        /* end menu active/inactive section */
        $data['page_name'] = 'pay_and_watch_debit_transaction_history';
        $data['page_title'] = 'Pay & Watch Debit Transaction History';
        $data['subscriptions'] = $this->get_pay_and_watch_debits_history($param1);
        $this->load->view('admin/index', $data);
    }

    function get_pay_and_watch_debits_history($subscription_id) {
        $this->db->select('pwl.id,s.videos_id,s.timestamp_from,s.timestamp_to,s.status,pwl.balance_credits');
        $this->db->from('pay_and_watch_subscription_logs pwl');
        $this->db->join('subscription s', 's.subscription_id = pwl.subscription_id', 'left');
        $this->db->where('pwl.main_subscription_id', $subscription_id);
        $this->db->order_by('pwl.id', 'DESC');
        $result = $this->db->get()->result();
        if ($result[0]->id != "") {
            foreach ($result as $item) {
                $item->start_date = date('d-m-Y H:i:s a', $item->timestamp_from);
                $item->end_date = date('d-m-Y H:i:s a', $item->timestamp_to);
                $item->video_title = $this->db->get_where('videos', ['videos_id' => $item->videos_id])->row()->title;
                unset($item->timestamp_from);
                unset($item->timestamp_to);
                unset($item->videos_id);
                if ($item->status == 1) {
                    $item->plan_status = "active";
                } else {
                    $item->plan_status = "inactive";
                }
                unset($item->status);
            }
            return $result;
        }
        return [];
    }

    function pay_and_watch_subscriptions($param1 = '', $param2 = '') {
        $this->load->model("api_v130_model");
        if ($this->admin_is_login != 1)
            redirect(base_url());
        /* start menu active/inactive section */
        $this->session->unset_userdata('active_menu');
        $this->session->set_userdata('active_menu', '1415');
        /* end menu active/inactive section */
        $data['page_name'] = 'pay_and_watch_transaction_history';
        $data['page_title'] = 'Pay & Watch Transaction History';

        $this->db->where("payment_status", "Paid");
        $total_rows = $this->db->count_all_results('pay_and_watch_subscription');

        $config = $this->common_model->pagination();
        $config["base_url"] = base_url() . "subscription/pay_and_watch_subscriptions?";
        $config["total_rows"] = $total_rows;
        $config["per_page"] = 10;
        $config["uri_segment"] = 3;
        //$config['use_page_numbers'] = TRUE;
        $config['page_query_string'] = TRUE;
        $this->pagination->initialize($config);
        $data['last_row_num'] = $this->uri->segment(3);
        //($this->uri->segment(3)) ? $this->uri->segment(3) : 0;
        $page = $this->input->get('per_page'); //($this->uri->segment(3)) ? $this->uri->segment(3) : 0;
        $data["links"] = $this->pagination->create_links();
        $data['total_rows'] = $config["total_rows"];
        $this->db->order_by('id', "desc");
        $this->db->limit($config["per_page"], $page);
        $this->db->where("payment_status", "completed");
        $data['subscriptions'] = $this->db->get('pay_and_watch_subscription')->result_array();

        $this->load->view('admin/index', $data);
    }

    function upgrade($param1 = '', $param2 = '') {
        if (!$this->session->userdata("user_id"))
            redirect("user/login");
        $data['page_name'] = 'price_plan';
        $data['page_title'] = 'Upgrade Membership';
        $data['plans'] = $this->db->get_where('plan', ['status' => 1])->result_array();
        $data['upi_id'] = $this->db->get_where('config',['config_id'=>"255"])->row()->value;
        $image = $this->db->get_where('config',['config_id'=>"256"])->row()->value;
        $data['upi_qr_code'] = AWS_S3_FILE."uploads/".$image;
//        print_r($data['plans']); die;
        //$this->load->view('front_end/index', $data);

        $this->load->view('theme/' . $this->active_theme . '/index', $data);
    }

    function manage_subscription($param1 = '', $param2 = '') {
        if ($this->admin_is_login != 1 && $this->moderator_is_login != 1)
            redirect(base_url());
        /* start menu active/inactive section */
        $this->session->unset_userdata('active_menu');
        $this->session->set_userdata('active_menu', '15');
        /* end menu active/inactive section */

        /* add new access */
        if ($param1 == 'add') {
            $user_id = $this->input->post('user_id');
            $status = $this->input->post('status');
            //deactive previus plan
            if ($status == '1'):
                $sub_data['status'] = 0;
                $this->db->where('user_id', $user_id);
                $this->db->update('subscription', $sub_data);
            endif;
            $amount = $this->db->where('plan_id', $this->input->post('plan_id'))->get('plan')->row();
            // add subscription plan
            $data['user_id'] = $user_id;
            $data['plan_id'] = $this->input->post('plan_id');
            $data['payment_method'] = $this->input->post('payment_method');
            $data['paid_amount'] = $amount->price;
//            $data['price_amount'] = $amount->price;
            $data['payment_status'] = "Paid";
            $data['device_type'] = "Admin";
            $data['transaction_id'] = $this->input->post('transaction_id');
            $data['timestamp_from'] = strtotime($this->input->post('start_date'));
            $data['payment_timestamp'] = time();
            $data['comfirmed_date'] = date('Y-m-d');
            $day = $this->subscription_model->get_plan_day_by_id($data['plan_id']);
            $day_str = $day . " days";
            $data['timestamp_to'] = strtotime($day_str, $data['timestamp_from']);
            $data['status'] = $status;
            $data["local_transaction_id"] = $this->common_model->generate_local_transaction_id();
            $data['payment_info'] = json_encode($data);
            $this->db->insert('subscription', $data);

            $this->session->set_flashdata('success', 'Plan added successed.');
            redirect(base_url() . 'subscription/manage_subscription/' . $user_id);
        }
        if ($param1 == 'update') {
            $user_id = $this->input->post('user_id');
            $status = $this->input->post('status');
            //deactive previus plan
            if ($status == '1'):
                $sub_data['status'] = 0;
                $this->db->where('user_id', $user_id);
                $this->db->update('subscription', $sub_data);
            endif;
            $amount = $this->db->where('plan_id', $this->input->post('plan_id'))->get('plan')->row();
            // edit subscription plan
            $data['user_id'] = $user_id;
            $data['plan_id'] = $this->input->post('plan_id');
            $data['payment_method'] = $this->input->post('payment_method');
            $data['paid_amount'] = $amount->price;
            $data['transaction_id'] = $this->input->post('transaction_id');
            $data['payment_status'] = "Paid";
            $data['device_type'] = "Admin";

            //$data['payment_info']           = json_encode(array("Transaction ID"=>$this->input->post('transaction_id')));
            $data['timestamp_from'] = strtotime($this->input->post('start_date'));
            $day = $this->subscription_model->get_plan_day_by_id($data['plan_id']);
            $day_str = $day . " days";
            $data['timestamp_to'] = strtotime($day_str, $data['timestamp_from']);
            $data['status'] = $status;
            $data['payment_info'] = json_encode($data);
            $this->db->where('subscription_id', $param2);
            $this->db->update('subscription', $data);
            $this->session->set_flashdata('success', 'Plan added successed.');
            redirect(base_url() . 'subscription/manage_subscription/' . $user_id);
        }

        $query = $this->db->get_where('user', array('user_id' => $param1), 1);
        if ($query->num_rows() > 0):
            $data['page_name'] = 'subscription_manage';
            $data['page_title'] = 'Manage Subscription';
            $data['user_data'] = $query->row();
            $this->db->order_by('subscription_id', "DESC");
            $this->db->where("payment_status", "Paid");
            $data['subscriptions'] = $this->db->get_where('subscription', array('user_id' => $param1))->result_array();
            $this->load->view('admin/index', $data);
        else:
            $this->session->set_flashdata('error', 'User not found.');
            redirect(base_url() . 'subscription/manage_user/');
        endif;
    }

    function manage_pay_and_watch_package_subscription($param1 = '', $param2 = '') {
        if ($this->admin_is_login != 1 && $this->moderator_is_login != 1)
            redirect(base_url());
        /* start menu active/inactive section */
        $this->session->unset_userdata('active_menu');
        $this->session->set_userdata('active_menu', '1995');
        /* end menu active/inactive section */

        $query = $this->db->get_where('user', array('user_id' => $param1), 1);
        if ($query->num_rows() > 0):
            $data['page_name'] = 'pay_and_watch_package_manage';
            $data['page_title'] = 'Manage Pay and Watch Subscription Package';
            $data['user_data'] = $query->row();
            $this->db->order_by('id', "DESC");
            $this->db->where("payment_status", "completed");
            $data['subscriptions'] = $this->db->get_where('pay_and_watch_subscription', array('user_id' => $param1))->result_array();
            $this->load->view('admin/index', $data);
        else:
            $this->session->set_flashdata('error', 'User not found.');
            redirect(base_url() . 'subscription/manage_user/');
        endif;
    }

    function sub_setting($param1 = '', $param2 = '') {
        if ($this->admin_is_login != 1 && $this->moderator_is_login != 1)
            redirect(base_url());
        /* start menu active/inactive section */
        $this->session->unset_userdata('active_menu');
        $this->session->set_userdata('active_menu', '302');
        /* end menu active/inactive section */

        if ($param1 == 'update') {
            $data['value'] = $this->input->post('currency_symbol');
            $this->db->where('title', 'currency_symbol');
            $this->db->update('config', $data);

            $data['value'] = $this->input->post('currency');
            $this->db->where('title', 'currency');
            $this->db->update('config', $data);

            $data['value'] = $this->input->post('exchange_rate_update_by_cron');
            $this->db->where('title', 'exchange_rate_update_by_cron');
            $this->db->update('config', $data);

            $data['value'] = $this->input->post('trial_enable');
            $this->db->where('title', 'trial_enable');
            $this->db->update('config', $data);

            $data['value'] = $this->input->post('trial_period');
            $this->db->where('title', 'trial_period');
            $this->db->update('config', $data);

            $data['value'] = $this->input->post('enable_ribbon');
            $this->db->where('title', 'enable_ribbon');
            $this->db->update('config', $data);

            $this->common_model->exchange_rate_update_by_iso_code($this->input->post('currency'), $this->input->post('exchnage_rate'));

            $this->session->set_flashdata('success', 'Subscription setting update successed');
            redirect(base_url() . 'subscription/sub_setting/');
        }
        $data['page_name'] = 'sub_setting';
        $data['page_title'] = 'Subscription Setting';
        $data['currencies'] = $this->db->get('currency')->result_array();
        $this->load->view('admin/index', $data);
    }

    function payment_setting($param1 = '', $param2 = '') {
        if ($this->admin_is_login != 1 && $this->moderator_is_login != 1)
            redirect(base_url());
        /* start menu active/inactive section */
        $this->session->unset_userdata('active_menu');
        $this->session->set_userdata('active_menu', '3002');
        /* end menu active/inactive section */

        if ($param1 == 'update') {
            // offline_payment
            $offline_payment_enable = $this->input->post('offline_payment_enable');
            if ($offline_payment_enable == 'on'):
                $data['value'] = "true";
                $this->db->where('title', 'offline_payment_enable');
                $this->db->update('config', $data);
            else:
                $data['value'] = "false";
                $this->db->where('title', 'offline_payment_enable');
                $this->db->update('config', $data);
            endif;
            $data['value'] = $this->input->post('offline_payment_title');
            $this->db->where('title', 'offline_payment_title');
            $this->db->update('config', $data);

            $data['value'] = $this->input->post('offline_payment_instruction');
            $this->db->where('title', 'offline_payment_instruction');
            $this->db->update('config', $data);

            // paypal
            $paypal_enable = $this->input->post('paypal_enable');
            if ($paypal_enable == 'on'):
                $data['value'] = "true";
                $this->db->where('title', 'paypal_enable');
                $this->db->update('config', $data);
            else:
                $data['value'] = "false";
                $this->db->where('title', 'paypal_enable');
                $this->db->update('config', $data);
            endif;
            $data['value'] = $this->input->post('paypal_email');
            $this->db->where('title', 'paypal_email');
            $this->db->update('config', $data);

            $data['value'] = $this->input->post('paypal_client_id');
            $this->db->where('title', 'paypal_client_id');
            $this->db->update('config', $data);

            // stripe

            $stripe_enable = $this->input->post('stripe_enable');
            if ($stripe_enable == 'on'):
                $data['value'] = "true";
                $this->db->where('title', 'stripe_enable');
                $this->db->update('config', $data);
            else:
                $data['value'] = "false";
                $this->db->where('title', 'stripe_enable');
                $this->db->update('config', $data);
            endif;

            $data['value'] = $this->input->post('stripe_publishable_key');
            $this->db->where('title', 'stripe_publishable_key');
            $this->db->update('config', $data);

            $data['value'] = $this->input->post('stripe_secret_key');
            $this->db->where('title', 'stripe_secret_key');
            $this->db->update('config', $data);

            // razorpay

            $razorpay_enable = $this->input->post('razorpay_enable');
            if ($razorpay_enable == 'on'):
                $data['value'] = "true";
                $this->db->where('title', 'razorpay_enable');
                $this->db->update('config', $data);
            else:
                $data['value'] = "false";
                $this->db->where('title', 'razorpay_enable');
                $this->db->update('config', $data);
            endif;

            $data['value'] = $this->input->post('razorpay_key_id');
            $this->db->where('title', 'razorpay_key_id');
            $this->db->update('config', $data);

            $data['value'] = $this->input->post('razorpay_key_secret');
            $this->db->where('title', 'razorpay_key_secret');
            $this->db->update('config', $data);

            $data['value'] = $this->input->post('razorpay_inr_exchange_rate');
            $this->db->where('title', 'razorpay_inr_exchange_rate');
            $this->db->update('config', $data);

            $this->session->set_flashdata('success', 'Subscription setting update successed');
            redirect(base_url() . 'subscription/payment_setting/');
        }
        $data['page_name'] = 'payment_setting';
        $data['page_title'] = 'Payment Setting';
        $this->load->view('admin/index', $data);
    }
    
    
    function payment_setting_qr($param1 = '', $param2 = '') {
        if ($this->admin_is_login != 1 && $this->moderator_is_login != 1)
            redirect(base_url());
        /* start menu active/inactive section */
        $this->session->unset_userdata('active_menu');
        $this->session->set_userdata('active_menu', '3003');
        /* end menu active/inactive section */

        if ($param1 == 'update') {
            
            $manual_payment_enable = $this->input->post('manual_payment_enable');
            if ($manual_payment_enable == 'on'):
                $data['value'] = "true";
                $this->db->where('title', 'manual_payment_enable');
                $this->db->update('config', $data);
            else:
                $data['value'] = "false";
                $this->db->where('title', 'manual_payment_enable');
                $this->db->update('config', $data);
            endif;
            
            $data['qr_id'] = $this->input->post('qr_id');
             if (isset($_FILES['qr_image']) && $_FILES['qr_image']['name'] != '') {
                $source = $destination = 'uploads/';
                $file_name = rand(111111,999999).time() . '.png';

//Upload to s3
                $this->upload_to_file($source, $file_name, $destination, $_FILES['qr_image']['tmp_name']);
                $data['qr_image'] = $file_name;
                $this->db->where('title', 'qr_image');
                $this->db->update('config', ['value'=>$data['qr_image']]);
            }
            
            
            $this->db->where('title', 'qr_id');
            $this->db->update('config', ['value'=>$data['qr_id']]);
            
            

            $this->session->set_flashdata('success', 'Subscription setting update successed');
            redirect(base_url() . 'subscription/payment_setting_qr/');
        }
        $data['page_name'] = 'payment_settings_qr';
        $data['page_title'] = 'Payment Setting Qr';
        $this->load->view('admin/index', $data);
    }
    
     function upload_to_file($source, $file_name, $destination, $tmp_file = '', $image_link = '') {

//Upload to s3 by choose file
        if ($tmp_file != '' && move_uploaded_file($tmp_file, $destination . $file_name)) {
            upload_to_s3($source, $file_name, $destination);
        }

//Upload to s3 by image link
        if ($image_link != '') {
            upload_to_s3($source, $file_name, $destination);
        }
    }

    function cancel_subscription() {
        $response = array();
        $subscription_id = trim($_POST["subscription_id"]);
        $response['submitted_data'] = $_POST;
        $status = $this->process_cancel_subscription($subscription_id);
        $response['status'] = $status;
        echo json_encode($response);
    }

    // function process_cancel_subscription($subscription_id=""){
    //     $user_id                        = $this->session->userdata('user_id');
    //     $query                          = $this->db->get_where('subscription' , array('subscription_id' => $subscription_id, 'user_id'=>$user_id));
    //     if($user_id =='' || $user_id==NULL){
    //        return 'login_error';
    //     }else if ($query->num_rows() > 0) {
    //         $data['recurring'] = '0';
    //         //$data['status'] = '0';
    //         $this->db->where('subscription_id',$subscription_id);
    //         $this->db->update('subscription',$data);
    //         return 'success';
    //     }else{
    //        return 'error';
    //     }
    // }

    function process_cancel_subscription($subscription_id = "") {
        $user_id = $this->session->userdata('user_id');
        $query = $this->db->get_where('subscription', array('subscription_id' => $subscription_id, 'user_id' => $user_id));
        if ($user_id == '' || $user_id == NULL) {
            return 'login_error';
        } else if ($query->num_rows() > 0) {
            $data['status'] = '0';
            $this->db->where('subscription_id', $subscription_id);
            $this->db->update('subscription', $data);
            return 'success';
        } else {
            return 'error';
        }
    }

    function stripe_payment() {
        $data['plan_id'] = $this->input->post('plan_id');
        $data['page_title'] = 'Purchase Package/Subscription';
        //$this->load->view('front_end/stripe_payment', $data);
        $this->load->view('theme/' . $this->active_theme . '/stripe_payment', $data);
    }

    function stripe($plan_id = '') {
        if (isset($_POST['stripeToken'])) {
            $currency_code = $this->db->get_where('config', array('title' => 'currency'))->row()->value;
            $plan_name = $this->db->get_where('plan', array('plan_id' => $plan_id))->row()->name;
            $price = $this->db->get_where('plan', array('plan_id' => $plan_id))->row()->price;
            $charging_amount = $price * 100;
            $stripe_token = $_POST['stripeToken'];
            $stripe_secret_key = $this->db->get_where('config', array('title' => 'stripe_secret_key'))->row()->value;

            $stripe_data['stripe_token'] = $stripe_token;
            $stripe_data['amount'] = $charging_amount;
            $stripe_data['currency'] = strtolower($currency_code);
            $stripe_data['description'] = $plan_name;
            $stripe_data['stripe_secret_key'] = $stripe_secret_key;

            $stripe_response = $this->stripegateway->checkout($stripe_data);

            if (isset($stripe_response->paid) && $stripe_response->paid):
                $data['transaction_id'] = $stripe_response->balance_transaction;
                $data['payment_info'] = json_encode($stripe_response);
                $data['plan_id'] = $plan_id;
                $data['user_id'] = $this->session->userdata('user_id');
                $data['price_amount'] = $price;
                $data['paid_amount'] = $price;
                $data['currency'] = $currency_code;
                $data['payment_timestamp'] = time();
                $data['comfirmed_date'] = date('Y-m-d');
                $data['timestamp_from'] = time();
                $day = $this->db->get_where('plan', array('plan_id' => $plan_id))->row()->day;
                $day = '+' . $day . ' days';
                $data['timestamp_to'] = strtotime($day, $data['timestamp_from']);
                $data['payment_method'] = 'stripe';
                $data['status'] = 1;
                $this->db->insert('subscription', $data);
                $this->session->set_flashdata('success', 'Subscription purchase successfully!');
            else:
                $this->session->set_flashdata('error', 'Transaction fail to process.</br>Reason:' . $this->stripegateway->checkout($stripe_data));
            endif;
            redirect(base_url('my-account/subscription'));
        }
    }

    function razorpay_payment() {

        $user_id = $this->session->userdata('user_id');
        if ($this->session->userdata('login_status') != 1)
            redirect(base_url() . 'login');

        $plan_id = $this->input->post('plan_id');
        $video_id = $this->input->post('video_id');

        if ($plan_id) {
            $plan_row = $this->db->get_where('plan', array('plan_id' => $plan_id))->row();
        } else {
            $video_row = $this->db->get_where('videos', array('videos_id' => $video_id))->row();
        }

        if ($plan_row || $video_row) {
            $plan_title = $plan_row->name;
            $plan_price = $plan_row->price ? $plan_row->price : $video_row->price;

            if ($video_row) {
                $plan_price = $plan_price + ($plan_price * ($video_row->gst_percentage / 100));
            }

            $data['plan_id'] = $plan_row->plan_id ? $plan_row->plan_id : 0;
            $data['videos_id'] = $video_row->videos_id ? $video_row->videos_id : 0;

            $this->session->unset_userdata('plan_id');
            $this->session->set_userdata('plan_id', $data['plan_id']);

            $data['page_title'] = 'Purchase Package/Subscription';
            $data['site_name'] = $this->db->get_where('config', array('title' => 'site_name'))->row()->value;
            $razorpay_inr_exchange_rate = $this->db->get_where('config', array('title' => 'razorpay_inr_exchange_rate'))->row()->value;
            $razorpay = $this->db->get_where('config', array('title' => 'razorpay_enable'))->row()->value;
            $phonepay = $this->db->get_where('config', array('title' => 'phonepay_enable'))->row()->value;
            $transaction_amount = ($plan_price * (int) $razorpay_inr_exchange_rate) * 100;

            $api = new Api(ovoo_config('razorpay_key_id'), ovoo_config('razorpay_key_secret'));

            $user_id = $this->session->userdata("user_id");
            $user_info = $this->api_v130_model->get_user_info_by_user_id($user_id);
            $insertdata['plan_id'] = $data['plan_id'];
            $insertdata['user_id'] = $user_id;
            $insertdata['paid_with'] = "other";
            if ($razorpay == 'true') {
                $insertdata['payment_method'] = "RazorPay"; //RazorPay
            } elseif ($phonepay == 'true') {
                $insertdata['payment_method'] = "PhonePay";
            }

            $insertdata['device_type'] = "Website"; //Android, Ios, Website
            $insertdata['videos_id'] = $data['videos_id'];

            $insertdata['paid_amount'] = (int) $plan_price;
            $insertdata['price_amount'] = (int) $plan_price;
            $insertdata['payment_timestamp'] = strtotime(date("Y-m-d H:i:s"));
            $insertdata['comfirmed_date'] = date('Y-m-d');
            if ($plan_row) {

                $insertdata['type'] = "subscription";
                $day = $plan_row->day;
                $day = '+' . $day . ' days';
                $active_subscription = $this->api_v130_model->get_active_subscription($insertdata['user_id']);
                if (count($active_subscription)) {
                    $new_expiry = date('Y-m-d H:i:s', strtotime($active_subscription[0]["expire_date"]));
                }
                if (isset($new_expiry)) {
                    $insertdata['timestamp_from'] = strtotime($new_expiry);
                } else {
                    $insertdata['timestamp_from'] = strtotime(date("Y-m-d H:i:s"));
                }
                $description = "Package: " . $plan_title;
            } else if ($video_row->video_view_type == "Pay and Watch") {
                //&& $video_row->pre_booking_enabled == "no"
                $insertdata['type'] = "payAndWatch";
                $hours = $video_row->how_many_hours_available_for_watch;
                $day = '+' . $hours . ' hours';
                $insertdata['timestamp_from'] = strtotime(date("Y-m-d H:i:s"));
                $description = "Movie Pay and Watch : " . $video_row->title;
            } else if ($video_row->video_view_type == "Pay and Watch" && $video_row->pre_booking_enabled == "yes") {
                $insertdata['type'] = "pre_booking";
                $pre_booking_active_date = $video_row->pre_booking_active_date;
                $hours = $video_row->how_many_hours_available_for_watch;
                $day = '+' . $hours . ' hours';
                $insertdata['timestamp_from'] = strtotime($pre_booking_active_date . " " . date("H:i:s"));
                $description = "Movie Pre booking : " . $video_row->title;
            }

            $insertdata['timestamp_to'] = strtotime($day, $insertdata['timestamp_from']);
            $insertdata['status'] = 1;

            $insertdata["local_transaction_id"] = $this->common_model->generate_local_transaction_id();

            $orderData = [
                'receipt' => $insertdata["local_transaction_id"],
                'amount' => $transaction_amount, // 2000 rupees in paise
                'currency' => 'INR',
                'payment_capture' => 1 // auto capture
            ];

            $razorpayOrder = $api->order->create($orderData);

            if ($razorpay == 'true') {
                $this->db->set("razor_pay_order_id", $razorpayOrder['id']);
            }
            $this->db->insert('subscription', $insertdata);
            $this->session->unset_userdata('razorpay_order_id');
            $this->session->set_userdata('razorpay_order_id', $razorpayOrder['id']);

            if ($razorpay == 'true') {
                $data['razorpay_options'] = [
                    "key" => ovoo_config('razorpay_key_id'),
                    "amount" => $transaction_amount,
                    "name" => $data['site_name'],
                    "description" => $description,
                    "image" => base_url("uploads/system_logo/") . ovoo_config('logo'),
                    "prefill" => [
                        "name" => $this->session->userdata('name'),
                        "email" => $user_info->email,
                        "contact" => $user_info->phone,
                    ],
                    "notes" => [
                        "address" => "Hello World",
                        "merchant_order_id" => $plan_id,
                    ],
                    "theme" => [
                        "color" => "#286cd5"
                    ],
                    "order_id" => $razorpayOrder['id'],
                ];
            } else if ($phonepay == 'true') {
                //$uid = "OX9OTT" . rand(111111111, 999999999);
                $user_id = $this->session->userdata("user_id");
                $in_paise = intval($insertdata['paid_amount'] * 100);
                $con = new stdClass();
                $con->merchantId = PHONE_PAY_MERCHANT_ID;
                $con->merchantTransactionId = $insertdata["local_transaction_id"];
                $con->merchantUserId = $user_id;
                $con->amount = $in_paise;
                $con->redirectUrl = base_url('rest_api/create_payment_request/website_after_payment/' . $con->merchantTransactionId);
                $con->callbackUrl = base_url('rest_api/create_payment_request/webhook');
                $con->mobileNumber = strval($user_info->phone);
                $con->paymentInstrument->type = "PAY_PAGE";
//            $con->deviceContext->deviceOS = $device_type;
//            $con->paymentInstrument->type = $payment_instrument_type;
//            $con->paymentInstrument->targetApp = $payment_instrument_target_app;

                $encode = json_encode($con);
                $encoded = base64_encode($encode);
                $salt_key = PHONE_PAY_SALT_KEY;
                $salt_index = PHONE_PAY_KEY_INDEX;
                $string = $encoded . PHONE_PAY_API_END_POINT . $salt_key;
                $sha256 = hash("sha256", $string);
                $final_x_header = $sha256 . '###' . $salt_index;
                $request_json_decode = new stdClass();
                $request_json_decode->request = $encoded;
                $request = json_encode($request_json_decode);
                $curl = curl_init();
                curl_setopt_array($curl, [
                    CURLOPT_URL => PHONE_PAY_PAY_URL,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => "",
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 30,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => "POST",
                    CURLOPT_POSTFIELDS => $request,
                    CURLOPT_HTTPHEADER => [
                        "Content-Type: application/json",
                        "X-VERIFY: " . $final_x_header,
                        "accept: application/json"
                    ],
                ]);

                $response = curl_exec($curl);
                $err = curl_error($curl);
                curl_close($curl);

                if ($err) {
                    $arr = array('status' => "invalid", 'message' => $err);
                } else {
                    $res = json_decode($response);
                    if ($res->code == 'PAYMENT_INITIATED') {
//                        echo "<script>location.href='" . $res->data->instrumentResponse->redirectInfo->url . "'</script>";
//                        die;
                        redirect($res->data->instrumentResponse->redirectInfo->url, "refresh");
                    } else {
                        redirect('web/checkout');
                    }
                }
            }
            $this->session->unset_userdata('razorpay_options');
            $this->session->set_userdata('razorpay_options', $data['razorpay_options']);
            $this->load->view('theme/' . $this->active_theme . '/razorpay_payment', $data);
        } else {
            $this->session->set_flashdata('error', 'Please select a valid plan.');
            redirect(base_url('my-account/subscription'));
        }
    }

    function save_razorpay() {
        //$res = $this->razor_pay_model->get_order_status($_POST["razorpay_payment_id"]);
        $success = true;
        $error = "Payment Failed";
        if (empty($_POST['razorpay_payment_id']) === false) {
            $api = new Api(ovoo_config('razorpay_key_id'), ovoo_config('razorpay_key_secret'));
            try {
                $attributes = array(
                    'razorpay_order_id' => $this->session->userdata('razorpay_order_id'),
                    'razorpay_payment_id' => $_POST['razorpay_payment_id'],
                    'razorpay_signature' => $_POST['razorpay_signature']
                );
                $api->utility->verifyPaymentSignature($attributes);
            } catch (SignatureVerificationError $e) {
                $success = false;
                $this->session->set_flashdata('error', $e->getMessage());
            }
        }


        $subscription_row = $this->db->get_where("subscription", ["razor_pay_order_id" => $this->session->userdata('razorpay_order_id')])->row();

        if ($success === true) {

            if ($subscription_row) {
                $this->db->set("payment_status", "Paid");
                $this->db->set("payment_gateway_id", $_POST['razorpay_payment_id']);
                $this->db->set("transaction_id", $_POST['razorpay_payment_id']);
                $this->db->set("payment_info", json_encode($_POST));
                $this->db->where("subscription_id", $subscription_row->subscription_id);
                $this->db->update('subscription');
                $this->session->set_flashdata('success', 'Subscription purchase successfully!');
                redirect(base_url('my-account/subscription'));
            }

            $plan_id = $this->session->userdata('plan_id');
            $plan_info = $this->db->get_where('plan', array('plan_id' => $plan_id))->first_row();
            $plan_price = $plan_info->price;
            $razorpay_inr_exchange_rate = $this->db->get_where('config', array('title' => 'razorpay_inr_exchange_rate'))->row()->value;
            $transaction_amount = ($plan_price * (int) $razorpay_inr_exchange_rate);

            $data['device_type'] = "Website";
            $data["local_transaction_id"] = $this->common_model->generate_local_transaction_id();
            $data['transaction_id'] = $_POST['razorpay_payment_id'];
            $data['payment_info'] = json_encode($this->session->userdata('razorpay_options'));
            $data['plan_id'] = $plan_id;
            $data['user_id'] = $this->session->userdata('user_id');
            $data['price_amount'] = $transaction_amount;
            $data['paid_amount'] = $transaction_amount;
            $data['currency'] = "INR";
            $data['payment_timestamp'] = time();
            $data['comfirmed_date'] = date('Y-m-d');
            $data['timestamp_from'] = time();
            $day = $plan_info->day;
            $day = '+' . $day . ' days';
            $data['timestamp_to'] = strtotime($day, $data['timestamp_from']);
            $data['payment_method'] = 'Razorpay';
            $data['status'] = 1;
            $this->db->insert('subscription', $data);
            $this->session->set_flashdata('success', 'Subscription purchase successfully!');
        }
        redirect(base_url('my-account/subscription'));
    }

    function paypal($action = '') {
        if ($action == 'process') {
            $plan_id = $this->input->post('plan_id');
            $supported_currencies = array("USD", "AUD", "BRL", "GBP", "CAD", "CZK", "DKK", "EUR", "HKD", "HUF", "ILS", "JPY", "MXN", "TWD", "NZD", "NOK", "PHP", "PLN", "RUB", "SGD", "SEK", "CHF", "THB");
            $currency_code = $this->db->get_where('config', array('title' => 'currency'))->row()->value;
            $amount = $this->db->get_where('plan', array('plan_id' => $plan_id))->row()->price;
            $exchnage_rate = $this->common_model->get_usd_exchange_rate($currency_code);

            if (!in_array($currency_code, $supported_currencies)):
                $currency_code = "USD";
                $amount = $amount / $exchnage_rate;
            endif;

            $user_id = $this->session->userdata('user_id');
            $plan_name = $this->db->get_where('plan', array('plan_id' => $plan_id))->row()->name;
            $custom = 'user_id=' . $user_id . '&plan_id=' . $plan_id;
            $paypal_email = $this->db->get_where('config', array('title' => 'paypal_email'))->row()->value;

            //custom url
            $notify_url = base_url('subscription/paypal/ipn');
            $cancel_url = base_url('subscription/paypal/cancel');
            $success_url = base_url('subscription/paypal/success');

            $this->paypal->add_field('business', $paypal_email);
            $this->paypal->add_field('notify_url', $notify_url);
            $this->paypal->add_field('cancel_return', $cancel_url);
            $this->paypal->add_field('return', $success_url);

            $this->paypal->add_field('rm', 2);
            $this->paypal->add_field('no_note', 0);
            $this->paypal->add_field('item_name', $plan_name);
            $this->paypal->add_field('amount', $amount);
            $this->paypal->add_field('currency_code', $currency_code);
            $this->paypal->add_field('custom', $custom);

            // process data
            $this->paypal->submit_paypal_post();
            //var_dump($this->paypal);
        } else if ($action == 'ipn') {
            $myfile = fopen("newfile.txt", "w") or die("Unable to open file!");
            $txt = "John Doe\n";
            fwrite($myfile, $txt);
            $txt = "Jane Doe\n";
            fwrite($myfile, $txt);
            fclose($myfile);

            if ($this->paypal->validate_ipn() == true) {
                $currency_code = $this->db->get_where('config', array('title' => 'currency'))->row()->value;
                $response = '';
                $transaction_id = '';
                $payment_info = array();
                $i = 0;
                foreach ($_POST as $key => $value) {
                    $value = urlencode(stripslashes($value));
                    $response .= "\n$key=$value";
                    if ($key == "txn_id"):
                        $transaction_id = $value;
                    endif;
                    $payment_info[$i][$key] = $value;
                }
                $myfile = fopen("newfile.txt", "w") or die("Unable to open file!");
                $txt = "John Doe\n";
                fwrite($myfile, $txt);
                $txt = "Jane Doe\n";
                fwrite($myfile, $txt);
                fclose($myfile);

                $custom = $_POST['custom'];
                parse_str($custom, $_MYVAR);
                $data['plan_id'] = $_MYVAR['plan_id'];
                $data['user_id'] = $_MYVAR['user_id'];

                $price = $this->db->get_where('plan', array('plan_id' => $_MYVAR['plan_id']))->row()->price;

                $data['paid_amount'] = $price;
                $data['price_amount'] = $price;
                $data['currency'] = $currency_code;
                $day = $this->db->get_where('plan', array('plan_id' => $_MYVAR['plan_id']))->row()->day;
                $day = '+' . $day . ' days';

                $data['payment_timestamp'] = time();
                $data['comfirmed_date'] = date('Y-m-d');
                $data['timestamp_from'] = time();
                $data['timestamp_to'] = strtotime($day, $data['timestamp_from']);
                $data['payment_method'] = 'paypal';
                $data['payment_info'] = json_encode($payment_info, JSON_FORCE_OBJECT);
                $data['transaction_id'] = $transaction_id;
                $data['status'] = 1;
                $this->db->insert('subscription', $data);
            }
        } else if ($action == 'success') {
            $this->session->set_flashdata('success', 'Subscription purchase successfully!');
        } else if ($action == 'cancel') {
            $this->session->set_flashdata('error', 'Transaction cancelled!');
        }
        redirect(base_url('my-account/subscription'));
    }

    public function save_payment() {
        $response = array();
        $response['status'] = "error";
        $response['message'] = "Something went wrong.Please contact with system admin";

        $plan_id = $_POST['plan_id'];
        $payment_method = $_POST['payment_method'];
        $payment_info = $_POST['payment_info'];

        if ($this->session->userdata('user_id') != '' && $this->session->userdata('user_id') != NULL):
            $response['status'] = "error";
            $response['message'] = "Plan ID not found.";
            if ($this->db->get_where('plan', array('plan_id' => $plan_id))->num_rows() > 0):
                $data['plan_id'] = $plan_id;
                $data['user_id'] = $this->session->userdata('user_id');
                $data['paid_amount'] = $this->db->get_where('plan', array('plan_id' => $plan_id))->row()->price;
                $data['payment_timestamp'] = time();
                $data['comfirmed_date'] = date('Y-m-d');
                $data['timestamp_from'] = time();
                $day = $this->db->get_where('plan', array('plan_id' => $plan_id))->row()->day;
                $day = '+' . $day . ' days';
                $data['timestamp_to'] = strtotime($day, $data['timestamp_from']);
                $data['payment_method'] = $payment_method;
                $data['payment_info'] = $payment_info;
                $data['status'] = 1;
                $this->db->insert('subscription', $data);

                $response['status'] = "success";
                $response['message'] = "Payment Completed.";
                $this->session->set_flashdata('success', 'Payment Completed.');
            endif;
        endif;
        echo json_encode($response);
    }

    // users
    function manage_coupon($param1 = '', $param2 = '') {
        if ($this->admin_is_login != 1 && $this->moderator_is_login != 1)
            redirect(base_url());
        /* start menu active/inactive section */
        $this->session->unset_userdata('active_menu');
        $this->session->set_userdata('active_menu', '303');
        /* end menu active/inactive section */

        /* add new access */

        if ($param1 == 'add') {
            $data['title'] = $this->input->post('title');
            $data['description'] = $this->input->post('description');
            $data['coupon_code'] = strtoupper($this->input->post('coupon_code'));
            $data['date_from'] = date("Y-m-d", strtotime($this->input->post('date_from')));
            $data['date_to'] = date("Y-m-d", strtotime($this->input->post('date_to')));
            $data['type'] = $this->input->post('type');
            $data['amount'] = $this->input->post('amount');
            $data['status'] = $this->input->post('status');

            $this->db->insert('coupon', $data);
            $this->session->set_flashdata('success', 'Coupon added successed');
            redirect(base_url() . 'subscription/manage_coupon/');
        }
        if ($param1 == 'update') {
            $data['title'] = $this->input->post('title');
            $data['description'] = $this->input->post('description');
            $data['coupon_code'] = strtoupper($this->input->post('coupon_code'));
            $data['date_from'] = date("Y-m-d", strtotime($this->input->post('date_from')));
            $data['date_to'] = date("Y-m-d", strtotime($this->input->post('date_to')));
            $data['type'] = $this->input->post('type');
            $data['amount'] = $this->input->post('amount');
            $data['status'] = $this->input->post('status');

            $this->db->where('coupon_id', $param2);
            $this->db->update('coupon', $data);
            $this->session->set_flashdata('success', 'Coupon update successed.');
            redirect(base_url() . 'subscription/manage_coupon/');
        }
        $data['page_name'] = 'coupon_manage';
        $data['page_title'] = 'Coupon Management';
        $data['coupons'] = $this->db->get('coupon')->result_array();
        $this->load->view('admin/index', $data);
    }

    public function coupon_details() {
        $response = array();
        $response['status'] = "error";
        $response['message'] = "Coupon is not valid.";
        $coupon_code = strtoupper($this->input->post("coupon_code"));
        $this->db->where('date_from <=', date("Y-m-d"));
        $this->db->where('date_to >=', date("Y-m-d"));
        $this->db->where('coupon_code', $coupon_code);
        $query = $this->db->get('coupon');
        if ($query->num_rows() > 0):
            $response['status'] = "success";
            $response['message'] = "Coupon is valid";
            $response['data'] = $query->row();
        endif;
        echo json_encode($response);
    }
    
    function subscription_user(){
        $user_id = $this->session->userdata("user_id");
        $status = "1";
        //deactive previus plan
        if ($status == '1'):
            $sub_data['status'] = 0;
            $this->db->where('user_id', $user_id);
            $this->db->update('subscription', $sub_data);
        endif;
        $amount = $this->db->where('plan_id', $this->input->post('plan_id'))->get('plan')->row();
        // add subscription plan
        $data['user_id'] = $user_id;
        $data['plan_id'] = $this->input->post('plan_id');
        $data['payment_method'] = "other";
        $data['paid_amount'] = $amount->price;
        $data['payment_status'] = "Pending";
        $data['type'] = "subscription";
        $data['device_type'] = "Website";
        $data['transaction_id'] = $this->get_transaction_id();
        $data['payment_timestamp'] = time();
        $data['comfirmed_date'] = date('Y-m-d');
        $data['timestamp_from'] = strtotime(date('Y-m-d'));
        $day = $this->subscription_model->get_plan_day_by_id($data['plan_id']);
        $day_str = $day . " days";
        $data['timestamp_to'] = strtotime($day_str, $data['timestamp_from']);
        
        $data['status'] = $status;
        $data["local_transaction_id"] = $this->common_model->generate_local_transaction_id();
//        print_r($data); die;
        $data['payment_info'] = json_encode($data);
        
//        if (isset($_FILES['transaction_image']) && $_FILES['transaction_image']['name'] != '') {
//            $source = $destination = 'uploads/';
//            $file_name = rand(111111,999999).time() . '.png';
//
////Upload to s3
//            $this->upload_to_file($source, $file_name, $destination, $_FILES['transaction_image']['tmp_name']);
//            $data['attatchment'] = $file_name;
//        }
        $this->db->insert('subscription', $data);
        $trnx_id = $this->db->insert_id();
        
        $pg_settings = $this->db->get_where('config',['title'=>'upi_gateway_key'])->row();
        $user_details = $this->db->get_where('user',['user_id'=>$user_id])->row();
        $obj = new Upigateway($pg_settings->value, $pg_settings->value);

        $obj->setServerMode(strtolower(PAYMENT_GATEWAY_MODE));
        $obj->setTransactionid($data['transaction_id']);
        $obj->setAmount($data['paid_amount']);
        $obj->setConsumerName($user_details->name);
        $obj->setPhoneNumber($user_details->phone);
        $obj->setEmail($user_details->email);
        $obj->setReturn_url(base_url() . "upigateway_payment_response");

        $obj->setMerchantkey($pg_settings->value);

        // $productInfo = "Product Purchase - umart";
        //$obj->setProductInfo($productInfo);

        $pg_reponse = $obj->createOrder();

        if ($pg_reponse) {
            $this->db->set("upipayments_link", $pg_reponse);
            $this->db->where("subscription_id", $trnx_id);
            $this->db->update("subscription");
        }
        header("Location: $pg_reponse");
//        $result = json_decode($pg_reponse, true);
//        if ($result['status'] == true) {
//                $payment_url = $result['data']['payment_url'];
//                header("Location: $payment_url");
//        }
        
//        $this->session->set_flashdata('success', trans('Your_subscription_request_success'));
//        redirect(base_url() . 'subscription/upgrade');
    }
    
    function get_transaction_id() {
        $uuid = "SR" . date("dmYH") . generateRandomNumber(5);
        if ($this->db->get_where('subscription', ["transaction_id" => $uuid])->num_rows() == 0) {
            return $uuid;
        } else {
            return $this->get_transaction_id();
        }
    }

}
