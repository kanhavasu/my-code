<?php
defined('BASEPATH') OR exit('No direct script access allowed');

include_once dirname(__FILE__) . '/CntrlResource.php';

class CntrlHome extends CntrlResource {

    function index(){
        $this->csrf->getToken();
        $this->session->set_userdata('utm_source',$this->input->get('utm_source', true));
        $this->session->set_userdata('utm_medium',$this->input->get('utm_medium', true));
        $this->session->set_userdata('utm_campaign',$this->input->get('utm_campaign', true));
        $this->session->set_userdata('fbclid',$this->input->get('fbclid', true));
        $this->session->set_userdata('gclid',$this->input->get('gclid', true));
        $this->load->view('home/frmHome');
    }
    
    function home(){
        $this->csrf->getToken();
        $this->session->set_userdata('utm_source',$this->input->get('utm_source', true));
        $this->session->set_userdata('utm_medium',$this->input->get('utm_medium', true));
        $this->session->set_userdata('utm_campaign',$this->input->get('utm_campaign', true));
        $this->session->set_userdata('fbclid',$this->input->get('fbclid', true));
        $this->session->set_userdata('gclid',$this->input->get('gclid', true));
        $this->load->view('home/frmHome1');
    }
    
    function enquirySubmit(){
        if($this->session->userdata('token')===$this->input->post('token', true)){
            $id = date('y');
            $row = $this->db->query("SELECT MAX(id) as id FROM tbl_enquiry_master WHERE id LIKE '$id%'")->row();
//            $id = $row->id ? $row->id + 1 : $id . "00001";
            $this->session->set_userdata("id",$row->id ? $row->id + 1 : $id . "00001");
            $program = $this->input->post("program",true);
            $data = array("id"=>$this->session->userdata("id"),"name"=>strtoupper($this->input->post("name",true)),"course"=>$program,"mobile"=>$this->input->post("mobile",true),"email"=>strtolower($this->input->post("email",true)),"city"=>strtoupper($this->input->post("city",true)),"query"=>$this->input->post("query",true),"session_id"=>$this->getCurrentSessionID(),"source"=>$this->input->post("source", true),"utm_source"=>$this->input->post("utm_source", true),"utm_medium"=>$this->input->post("utm_medium", true),"utm_campaign"=>$this->input->post("utm_campaign", true),"fbclid"=>$this->input->post("fbclid", true),"gclid"=>$this->input->post("gclid", true),"date"=>date("Y-m-d H:i:s"));
            $message = "Dear " . strtoupper($this->input->post("name", true)) . ",";
            $message .= "<p>Thank you for showing interest in studying at GL BAJAJ Group of Institutions, Mathura. Our admission counsellor will get back to you soon.</p><p>Meanwhile, you may have a virtual tour of the campus through the link: <a href='https://www.glbajajgroup.org/virtual-tour'>https://www.glbajajgroup.org/virtual-tour</a>.</p>";
            $message .= "<p>&nbsp;</p><p>Stay Safe!&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Stay Healthy!</p>";
            //sending SMS to aspirant
//            $this->sendSMS($this->input->post("mobile",true), $message);
            if($this->sendEmail(strtolower($this->input->post("email",true)), "Acknowledgement for Admission Enquiry", $message)){
                $this->db->insert("tbl_enquiry_master",$data);
                $message = "Dear Sir,";
                $message .= "<p>Following are the details of the new enquiry:";
                $message .= "<br>Name: <strong>" . strtoupper($this->input->post("name",true)) . "</strong>,";
                $message .= "<br>Program: <strong>$program</strong>,";
                $message .= "<br>Mobile: <strong>" . $this->input->post("mobile",true) . "</strong>,";
                $message .= "<br>Email: <strong>" . strtolower($this->input->post("email",true)) . "</strong>, and";
                $message .= "<br>City: <strong>" . strtoupper($this->input->post("city",true)) . "</strong>.";
                //sending SMS to admission heads
//                $this->sendSMS("8477820001", $message);
                $this->sendEmail(array("shashank.awasthi@glbitm.ac.in","admissions@glbajajgroup.org"), "New enquiry in $program - GLBGI LANDING PAGE", $message);
                $this->csrf->getToken();
                echo json_encode(array("response"=>true,"message"=>"<i class='fa fa-check-circle'></i>Data submitted successfully. We shall contact you in a short while.","applicant_id"=>sha1($this->session->userdata("id")),"token"=>$this->session->userdata("token")));
            } else{
                echo json_encode(array("response"=>false,"message"=>"<i class='fa fa-times-circle'></i>Unable to submit data. Please try later."));
            }
        } else{
            echo json_encode(array("response"=>false,"message"=>"<i class='fa fa-times-circle'></i>Security token mismatch."));
        }
    }
    
    function enquiryThankYou(){
        if($this->session->userdata('token')===$this->input->get('token', true)){
            if(sha1($this->session->userdata('id'))===$this->input->get('app_id', true)){
                $this->load->view("home/frmThankYou");
            } else{
                echo "Sorry...! You are not allowed to proceed further.";
            }
        } else{
            echo "Security token mismatch...!";
        }
    }
    
    function thankYou(){
        $this->load->view("home/frmThankYou");
    }
}
