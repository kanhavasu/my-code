<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class CntrlResource extends CI_Controller {
	
    function getCurrentSessionID(){
        return $this->db->query("SELECT MAX(id) as session FROM tbl_academic_session")->row()->session;
    }
    
    function getCurrentSession(){
        return $this->db->query("SELECT session FROM tbl_academic_session WHERE id=" . $this->getCurrentSessionID())->row()->session;
    }
    
    function configEmail(){
        $config = array("protocol"=>"smtp","smtp_host"=>"ssl://smtp.gmail.com","smtp_user"=>"donotreply@glbajajgroup.org","smtp_pass"=>"Dnr@7543##","smtp_port"=>"465","mailtype"=>"html","charset"=>"utf-8","newline"=>"\r\n");
        $this->email->initialize($config);
    }
    
    function sendOTPEmail($email,$subject,$message){
        $this->configEmail();
        $this->email->from("donotreply@glbajajgroup.org", "GL BAJAJ");
        $this->email->to($email);
        $this->email->subject($subject); 
        $this->email->message("<span style='font-family: Garamond; font-size: 17px'>$message<p style='font-size: 13px'>--<br>Thank you,</p><p style='font-size: 13px; color: #4e2a12'><strong>TEAM - ADMISSION CELL<br>GL BAJAJ Group of Institutions, Mathura</strong><br>Mobile: 8477820001 / 8477820002<br>Email: admissions@glbajajgroup.org<br>Web: https://www.glbajajgroup.org</p></span><img src='" . base_url() . "landingpage/img/logo.png' alt='GLBGI Logo'/><hr><p><small style='color: #999'><strong>Note:</strong> This is a system-generated response. Please don't reply to this email.</small></p>"); 
        //Send mail 
        if($this->email->send()){
            return true;
        }
        else{
            return false;
        }
    }
    
    function sendEmail($email,$subject,$message){
        $this->configEmail();
        $this->email->from("donotreply@glbajajgroup.org", "GL BAJAJ");
        $this->email->reply_to("admissions@glbajajgroup.org", "GL BAJAJ");
        $this->email->to($email);
        $this->email->cc("admissions@glbajajgroup.org", "GL BAJAJ");
        $this->email->subject($subject); 
        $this->email->message("<span style='font-family: Garamond; font-size: 17px'>$message<p style='font-size: 13px'>--<br>Thank you,</p><p style='font-size: 13px; color: #4e2a12'><strong>TEAM - ADMISSION CELL<br>GL BAJAJ Group of Institutions, Mathura</strong><br>Mobile: 8477820001 / 8477820002<br>Email: admissions@glbajajgroup.org<br>Web: https://www.glbajajgroup.org</p></span><img src='" . base_url() . "landingpage/img/logo.png' alt='GLBGI Logo'/><hr><p><small style='color: #999'><strong>Note:</strong> This is a system-generated response. Please don't reply to this email.</small></p>"); 
        //Send mail 
        if($this->email->send()){
            return true;
        }
        else{
            return false;
        }
    }
    
    function RandomToken($length = 32){
        if(!isset($length) || intval($length) <= 4 ){
          $length = 32;
        }
        if (function_exists('random_bytes')) {
            return bin2hex(random_bytes($length));
        }
        if (function_exists('mcrypt_create_iv')) {
            return bin2hex(mcrypt_create_iv($length, MCRYPT_DEV_URANDOM));
        } 
        if (function_exists('openssl_random_pseudo_bytes')) {
            return bin2hex(openssl_random_pseudo_bytes($length));
        }
    }
    
    function generateOTP($length){
        if (function_exists('random_bytes')) {
            return bin2hex(random_bytes($length));
        }
        if (function_exists('mcrypt_create_iv')) {
            return bin2hex(mcrypt_create_iv($length, MCRYPT_DEV_URANDOM));
        } 
        if (function_exists('openssl_random_pseudo_bytes')) {
            return bin2hex(openssl_random_pseudo_bytes($length));
        }
    }

    function sendSMS($mobile,$message){
        if (function_exists("curl_init")) {
            // initialize a new curl resource
            $ch = curl_init();
            $message = urlencode($message);
            $URI = "http://cloud.smsindiahub.in/vendorsms/pushsms.aspx?user=web.support&password=Vivek@97&msisdn=$mobile&sid=GLBADM&msg=$message&fl=0";

//                $URI = "https://messageapi.in/MessagingAPI/sendMessage.php?LoginId=9759227543&password=Vivek@97&mobile_number=$mobile&message=$message";

            curl_setopt($ch, CURLOPT_URL, $URI);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

            // Make request
            curl_exec($ch);
            curl_close($ch);
            return true;
        } else{
            return false;
        }
    }
}
