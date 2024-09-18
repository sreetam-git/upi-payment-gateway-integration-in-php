<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Subscription_model extends CI_Model {

    function __construct() {
        parent::__construct();
        $this->table_name = "subscription";
    }

    function create_trial_subscription($user_id = '') {
        $data['user_id'] = $user_id;
        $data['plan_id'] = '0';
        $data['timestamp_from'] = time();
        $day = $this->db->get_where('config', array('title' => 'trial_period'))->row()->value;
        $day_str = $day . " days";
        $data['timestamp_to'] = strtotime($day_str, $data['timestamp_from']);
        $data['status'] = '1';
        $this->db->insert('subscription', $data);
        return TRUE;
    }

    function create_subscription($user_id = '', $plan_id = '') {
        $data['user_id'] = $user_id;
        $data['plan_id'] = $plan_id;
        $data['timestamp_from'] = time();
        $day = $this->get_plan_day_by_id($plan_id);
        $day_str = $day . " days";
        $data['timestamp_to'] = strtotime($day_str, $data['timestamp_from']);
        $data['status'] = '1';
        $this->db->insert('subscription', $data);
        return TRUE;
    }
    
    function get_depositing_row($transaction_id) {
        $this->db->where("transaction_id", $transaction_id);
        $row = $this->db->get($this->table_name)->row();
        
        return $row;
    }
    
    function mark_as_transaction_faliure($transaction_id, $pg_ref_id, $response_obj_json, $manual_remark = false) {
        $row = $this->get_depositing_row($transaction_id);

        $this->db->trans_begin();

        if ($row->payment_status == "Failed") {
            return;
        }

        $this->db->where("transaction_id", $transaction_id);

        if ($manual_remark == FALSE) {
            $this->db->set("comment", $row->comment . " Transaction Failed at " . date("d-m-Y h:i:s A"));
        } else {
            $this->db->set("comment", $row->comment . $manual_remark);
        }

        $this->db->set("timestamp_to", time());
        $this->db->set("payment_gateway_id", $pg_ref_id);
        $this->db->set("payment_gateway_response_log", $response_obj_json);
        $this->db->set("payment_status", "Failed");
        $this->db->update($this->table_name);
        if ($this->db->affected_rows()) {
            $this->db->trans_complete();
            if ($this->db->trans_status() === FALSE) {
                $this->db->trans_rollback();
                return false;
            } else {
                $this->db->trans_commit();
            }
        } else {
            return false;
        }
    }
    
    function mark_as_transaction_successful($transaction_id, $pg_ref_id, $response_obj_json = null) {
        
        $row = $this->get_depositing_row($transaction_id);

        if ($row->payment_status == "Paid") {
            return;
        }

        $this->db->trans_begin();

        $this->db->where("transaction_id", $transaction_id);
        $this->db->set("comment", $row->comment . " Transaction Success at " . date("d-m-Y h:i:s A"));
        $this->db->set("timestamp_to", time());
        $this->db->set("payment_gateway_id", $pg_ref_id);
        $this->db->set("payment_gateway_response_log", $response_obj_json);
        $this->db->set("payment_status", "Paid");
        $this->db->update($this->table_name);
        if ($this->db->affected_rows()) {
             $this->db->trans_complete();

            if ($this->db->trans_status() === FALSE) {
                $this->db->trans_rollback();
                return false;
            } else {
                $this->db->trans_commit();
                return true;
            }
        }
    }

    function get_active_plan_title($user_id = '') {
        $title = 'Free';
        $query = $this->db->get_where('subscription', array('user_id' => $user_id, 'status' => '1'), 1);
        if ($query->num_rows() > 0):
            $plan_id = $query->row()->plan_id;
            if ($plan_id == 0):
                $title = 'Trial';
            else:
                $title = $this->get_plan_name_by_id($plan_id);
            endif;
        endif;
        return $title;
    }

    function get_plan_name_by_id($plan_id) {
        $name = "Not Found";
        if ($plan_id == 0):
            $name = "Trial";
        endif;
        $query = $this->db->get_where('plan', array('plan_id' => $plan_id));
        if ($query->num_rows() > 0):
            $name = $this->db->get_where('plan', array('plan_id' => $plan_id))->row()->name;
        endif;
        return $name;
    }

    function get_plan_day_by_id($plan_id) {
        return $this->db->get_where('plan', array('plan_id' => $plan_id))->row()->day;
    }

    function get_active_plan_validity($user_id = '') {
        $validity = 'Lifetime';
        $query = $this->db->get_where('subscription', array('user_id' => $user_id, 'status' => '1'), 1);
        if ($query->num_rows() > 0):
            $date = time();
            if ($date > $query->row()->timestamp_to):
                $validity = "Expired";
            else:
                $validity = date("d-m-Y", $query->row()->timestamp_to);
            endif;
        endif;
        return $validity;
    }

    function check_video_availability($slug = '') {
        $error = FALSE;
        if ($slug == '' || $slug == NULL)
            $error = TRUE;

        $videos_exist = $this->common_model->videos_exist_by_slug($slug);
        if (!$videos_exist)
            $error = TRUE;
        return $error;
    }

    function check_live_tv_availability($slug = '') {
        $error = FALSE;
        if ($slug == '' || $slug == NULL)
            $error = TRUE;

        $videos_exist = $this->common_model->live_tv_exist_by_slug($slug);
        if (!$videos_exist)
            $error = TRUE;
        return $error;
    }

    function videos_exist_by_slug($slug = '') {
        $rows = $this->db->get_where('videos', array('slug' => $slug, 'publication' => '1'))->num_rows();
        if ($rows > 0) {
            return TRUE;
        } else {
            return FALSE;
        }
    }

    function live_tv_exist_by_slug($slug = '') {
        $rows = $this->db->get_where('live_tv', array('slug' => $slug, 'publish' => '1'))->num_rows();
        if ($rows > 0) {
            return TRUE;
        } else {
            return FALSE;
        }
    }

    function check_video_accessibility($videos_id = '') {
        $accessibility = "denied";
        // free content can access by all
        $is_paid = $this->db->get_where('videos', array('videos_id' => $videos_id))->row()->is_paid;
        if ($is_paid == '0')
            $accessibility = "allowed";
        if ($is_paid == '1'):
            $subscription = $this->check_validated_subscription_plan();
            //var_dump($subscription);
            if ($subscription == "login_required"):
                $accessibility = "login_required";
            elseif ($subscription === "TRUE"):
                $accessibility = "allowed";
            endif;
        endif;
        // admin can access all movie
        if ($this->session->userdata('admin_is_login') == 1)
            $accessibility = "allowed";
        return $accessibility;
    }

    function check_live_tv_accessibility($live_tv_id = '') {
        $accessibility = "denied";

        // free content can access by all
        $is_paid = $this->db->get_where('live_tv', array('live_tv_id' => $live_tv_id))->row()->is_paid;
        if ($is_paid == '0')
            $accessibility = "allowed";
        if ($is_paid == '1'):
            $subscription = $this->check_validated_subscription_plan();
            //var_dump($subscription);
            if ($subscription == "login_required"):
                $accessibility = "login_required";
            elseif ($subscription === "TRUE"):
                $accessibility = "allowed";
            endif;
        endif;
        // admin can access all movie
        if ($this->session->userdata('admin_is_login') == 1)
            $accessibility = "allowed";
        return $accessibility;
    }

    function check_validated_subscription_plan() {
        $validity = "FALSE";
        $user_id = $this->session->userdata('user_id');
        if (!empty($user_id)):
            $this->db->where('status', '1');
            $this->db->where('timestamp_to >', time());
            $this->db->where('user_id', $this->session->userdata('user_id'));
            $query = $this->db->get('subscription');
            if ($query->num_rows() > 0):
                $validity = $query->row()->timestamp_to;
                if ($validity > time())
                    $validity = "TRUE";
            endif;
        endif;
        if (empty($user_id)):
            $validity = "login_required";
        endif;
        return $validity;
    }

    function get_active_subscription() {
        $this->db->order_by("subscription_id", "desc");
        $this->db->where('status', '1');
        $this->db->where('payment_status', 'Paid');
        $this->db->where('timestamp_to >', time());
        $this->db->where('user_id', $this->session->userdata('user_id'));
        return $this->db->get('subscription');
    }

    function get_inactive_subscription() {
        $this->db->group_start();
        $this->db->where('status', '0');
        $this->db->or_where('timestamp_to <', time());
        $this->db->group_end();
        $this->db->order_by("subscription_id", "desc");
        $this->db->where('user_id', $this->session->userdata('user_id'));
        return $this->db->get('subscription');
    }

    function get_total_income() {
        $currency_symbol = $this->db->get_where('config', array('title' => 'currency_symbol'))->row()->value;
        $this->db->select_sum('paid_amount');
        //$this->db->where('payment_timestamp >=',strtotime(date("Y-m-d 00:00:00")));
        //$this->db->where('payment_timestamp <=',strtotime(date("Y-m-d 23:59:59")));
        $amount = $this->db->get('subscription')->row()->paid_amount;
        return $currency_symbol . ' ' . number_format($amount, 2);
    }

    function get_today_income() {
        $currency_symbol = $this->db->get_where('config', array('title' => 'currency_symbol'))->row()->value;
        $this->db->select_sum('paid_amount');
        $this->db->where('payment_timestamp >=', strtotime(date("Y-m-d 00:00:00")));
        $this->db->where('payment_timestamp <=', time());
        $amount = $this->db->get('subscription')->row()->paid_amount;
        return $currency_symbol . ' ' . number_format($amount, 2);
    }

    function get_weekly_income() {
        $currency_symbol = $this->db->get_where('config', array('title' => 'currency_symbol'))->row()->value;
        $this->db->select_sum('paid_amount');
        $this->db->where('payment_timestamp >=', strtotime('last friday'));
        $this->db->where('payment_timestamp <=', time());
        $amount = $this->db->get('subscription')->row()->paid_amount;
        //var_dump($this->db->last_query());
        return $currency_symbol . ' ' . number_format($amount, 2);
    }

    function get_monthly_income() {
        $currency_symbol = $this->db->get_where('config', array('title' => 'currency_symbol'))->row()->value;
        $this->db->select_sum('paid_amount');
        $this->db->where('payment_timestamp >=', strtotime('first day of this month'));
        $this->db->where('payment_timestamp <=', time());
        $amount = $this->db->get('subscription')->row()->paid_amount;
        return $currency_symbol . ' ' . number_format($amount, 2);
    }

    function get_active_subscriber() {
        $this->db->distinct('user_id');
        $this->db->where('timestamp_to >', time());
        $this->db->where('status', '1');
        $query = $this->db->get('subscription');
        return $query->num_rows();
    }

    function get_inactive_subscriber() {
        $this->db->distinct('user_id');
        $this->db->where('timestamp_to <', time());
        $this->db->where('status', '1');
        $query = $this->db->get('subscription');
        //var_dump($this->db->last_query());
        return $query->num_rows();
    }

}
