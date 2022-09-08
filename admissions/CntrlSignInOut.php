<?php
defined('BASEPATH') OR exit('No direct script access allowed');

include_once dirname(__FILE__) . '/CntrlResource.php';

class CntrlSignInOut extends CntrlResource {

    function signIn(){
        $this->csrf->getToken();
        $this->load->view('sign-in-out/frmSignIn');
    }
    
    function getCurrentSession(){
        return $this->db->query("SELECT MAX(id) as session FROM tbl_academic_session")->row()->session;
    }
    
    function checkCredentials(){
        if($this->session->userdata('token')===$this->input->post('token', true)){
            $lid = $this->input->post('lid', true);
            $password = $this->input->post('password', true);
            $row = $this->db->query("SELECT id, name, password, email_verify_flag, mobile_verify_flag, role FROM tbl_login_master WHERE (email='$lid' OR mobile='$lid') AND status='ACTIVE'")->row();
            if(isset($row)){
                if($password===$row->password){
                    $this->session->set_userdata('logged_in', true);
                    $this->session->set_userdata('logged_in_id', $row->id);
                    $this->session->set_userdata('logged_in_name', $row->name);
                    $this->session->set_userdata('email_verify_flag', $row->email_verify_flag ? true : false);
                    $this->session->set_userdata('mobile_verify_flag', $row->mobile_verify_flag ? true : false);
                    $row->role==="ADMIN" ? $this->session->set_userdata('logged_in_as_admin', true) : $this->session->set_userdata('logged_in_as_admin', false);
                    echo json_encode(array("response"=>true,"url"=> base_url() . 'backend/dashboard'));
                } else{
                    echo json_encode(array("response"=>false,"error"=>"<i class='fa fa-warning'></i>Password doesn't match...!"));
                }
            } else{
                echo json_encode(array("response"=>false,"error"=>"<i class='fa fa-warning'></i>Account doesn't exist...!"));
            }
        } else{
            echo json_encode(array("response"=>false,"error"=>"<i class='fa fa-warning'></i>Security token mismatch...!"));
        }
    }
    
    function checkCredentialsAndSendOTP(){
        if($this->session->userdata('token')===$this->input->post('token', true)){
            $lid = $this->input->post('lid', true);
            $row = $this->db->query("SELECT name, email, mobile FROM tbl_login_master WHERE (email='$lid' OR mobile='$lid') AND status='ACTIVE'")->row();
            if(isset($row)){
                $otp = $this->generateOTP(3);
                $this->session->set_userdata("otp",$otp);
                $message = "Dear " . $row->name . ",";
                $message .= "<p>Welcome to the Admission Portal. Please use <strong>$otp</strong> as One Time Password (OTP) for signing in.";
                //sending SMS to admission heads
//                $this->sendSMS($row->mobile, $message);
                $this->sendOTPEmail($row->email, "One Time Password (OTP)", $message);
                echo json_encode(array("response"=>true,"message"=>"<i class='fa fa-info-circle'></i>OTP sent to registered email...!","otp"=>$otp));
            } else{
                echo json_encode(array("response"=>false,"error"=>"<i class='fa fa-warning'></i>Account doesn't exist...!"));
            }
        } else{
            echo json_encode(array("response"=>false,"error"=>"<i class='fa fa-warning'></i>Security token mismatch...!"));
        }
    }
    
    function verifyOTP(){
        if($this->session->userdata('token')===$this->input->post('token', true)){
            if($this->session->userdata('otp')===$this->input->post('otp', true)){
                $lid = $this->input->post('lid', true);
                $row = $this->db->query("SELECT id, name, password, email_verify_flag, mobile_verify_flag, role FROM tbl_login_master WHERE (email='$lid' OR mobile='$lid') AND status='ACTIVE'")->row();
                $this->session->set_userdata('logged_in', true);
                $this->session->set_userdata('logged_in_id', $row->id);
                $this->session->set_userdata('logged_in_name', $row->name);
                $this->session->set_userdata('email_verify_flag', $row->email_verify_flag ? true : false);
                $this->session->set_userdata('mobile_verify_flag', $row->mobile_verify_flag ? true : false);
                $this->session->unset_userdata('otp');
                $row->role==="ADMIN" ? $this->session->set_userdata('logged_in_as_admin', true) : $this->session->set_userdata('logged_in_as_admin', false);
                echo json_encode(array("response"=>true,"url"=> base_url() . 'backend/dashboard'));
            } else{
                echo json_encode(array("response"=>false,"error"=>"<i class='fa fa-warning'></i>OTP mismatch...!"));
            }
        } else{
            echo json_encode(array("response"=>false,"error"=>"<i class='fa fa-warning'></i>Security token mismatch...!"));
        }
    }
    
    function signOut(){
        $this->session->unset_userdata('logged_in');
        $this->session->unset_userdata('logged_in_id');
        $this->session->unset_userdata('logged_in_name');
        $this->session->unset_userdata('email_verify_flag');
        $this->session->unset_userdata('mobile_verify_flag');
        $this->session->unset_userdata('logged_in_as_admin');
        $this->session->unset_userdata('main_menu_permission');
        $this->session->unset_userdata('sub_menu_permission');
        redirect(base_url() . 'sign-in');
    }
}
