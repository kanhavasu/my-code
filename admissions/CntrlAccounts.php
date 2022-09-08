<?php
defined('BASEPATH') OR exit('No direct script access allowed');

include_once dirname(__FILE__) . '/CntrlResource.php';

class CntrlAccounts extends CntrlResource {
    
    public $login_id, $date;
    
    function __construct(){
        parent::__construct();
        if($this->session->userdata("logged_in")){
            $this->login_id = $this->session->userdata("logged_in_id");
            $permission = $this->db->query("SELECT GROUP_CONCAT(DISTINCT(a.alias)) as main_menu FROM tbl_main_menu a, tbl_sub_menu b, tbl_user_permission c WHERE c.user_id='$this->login_id' AND c.sub_menu_id=b.id AND b.main_menu_id=a.id")->row();
            if(isset($permission->main_menu)){
                $this->session->set_userdata('main_menu_permission',$permission->main_menu);
            }
            $permission = $this->db->query("SELECT GROUP_CONCAT(DISTINCT(a.alias)) as sub_menu FROM tbl_sub_menu a, tbl_user_permission b WHERE b.user_id='$this->login_id' AND b.sub_menu_id=a.id AND a.status=1")->row();
            if(isset($permission->sub_menu)){
                $this->session->set_userdata('sub_menu_permission',$permission->sub_menu);
            }
        } 
    }

    function payment(){
        if(!$this->session->userdata('logged_in') || $this->session->userdata('logged_in_as_admin') || (($this->session->userdata('logged_in') && in_array("payment", explode(",",$this->session->userdata("sub_menu_permission")))))){
            if(!$this->session->userdata('logged_in')){
                $this->csrf->getToken();
            }
            $reference = $this->input->get('reference',true);
            $data['mode'] = array('mode'=>$this->input->get('mode',true));
            $data['applicant'] = $this->db->query("SELECT a.id, a.name, a.email, a.mobile, b.alias, a.lateral, a.branch, a.branch_allotted FROM tbl_admission_master a, tbl_program_master b WHERE b.id=a.program_id AND UCASE(SHA1(a.id))='$reference'")->row();
            $data['applicant_payment'] = $this->db->query("SELECT a.id,b.name as applicant_name,b.fname,b.mobile,a.payment_mode,a.transaction_at,a.utr,a.name,a.place,a.amount,DATE_FORMAT(a.transaction_date,'%d-%m-%Y') as transaction_date,DATE_FORMAT(a.date,'%d-%m-%Y %h:%i:%s') as date, a.status, a.remarks FROM tbl_admission_payment_details a, tbl_admission_master b WHERE a.applicant_id=b.id AND UCASE(SHA1(a.applicant_id))='$reference' ORDER BY id")->result();
            $data['reference'] = $this->db->query("SELECT id FROM tbl_admission_master")->result();
            $data['bank'] = $this->db->query("SELECT name FROM tbl_financial_institutions WHERE status=1 ORDER BY name")->result();
            $this->load->view('backend/accounts/frmPayment', $data);
        }
    }
    
    function fetchRegistrationData(){
        if($this->session->userdata('logged_in_as_admin') || (($this->session->userdata('logged_in') && in_array("payment", explode(",",$this->session->userdata("sub_menu_permission")))))){
            if($this->session->userdata('token')===$this->input->post('token', true)){
                $reference = $this->input->post('reference',true);
                $row = $this->db->query("SELECT id,payment_mode,transaction_at,utr,name,place,amount,DATE_FORMAT(transaction_date,'%d-%m-%Y') as transaction_date,DATE_FORMAT(date,'%d-%m-%Y %h:%i:%s') as date, status, remarks FROM tbl_admission_payment_details WHERE applicant_id='$reference' ORDER BY id")->result();
                $paymentTable = "";
                if(isset($row)){
                    foreach($row as $r){
                        $paymentTable .= "<tr><td>$r->id</td><td>$r->payment_mode</td><td>$r->transaction_at</td><td>$r->name</td><td>$r->place</td><td>$r->utr</td><td>$r->amount</td><td>$r->transaction_date</td><td>$r->remarks<br>Submitted on $r->date<br>$r->status</td></tr>";
                    }
                }
                $row = $this->db->query("SELECT a.id, a.name, a.email, a.mobile, b.alias, a.lateral, a.branch, a.branch_allotted FROM tbl_admission_master a, tbl_program_master b WHERE b.id=a.program_id AND a.id='$reference'")->row();
                if(isset($row)){
                    echo json_encode(array("response"=>true,"id"=>$row->id,"name"=>$row->name,"email"=>$row->email,"mobile"=>$row->mobile,"alias"=>$row->alias,"lateral"=>$row->lateral ? "YES" : "NO","branch"=>$row->branch,"branch_allotted"=>$row->branch_allotted,"paymentTable"=>$paymentTable));
                } else{
                    echo json_encode(array("response"=>false,"error"=>"<i class='fa fa-warning'></i>Invalid REFERENCE NO...!"));
                }
            } else{
                echo json_encode(array("response"=>false,"error"=>"<i class='fa fa-warning'></i>Security token mismatch...!"));
            }
        } else{
            echo json_encode(array("response"=>false,"error"=>"<i class='fa fa-warning'></i>Operation not allowed...!"));
        }
    }
    
    function applicantPay(){
        if(!$this->session->userdata('logged_in') || $this->session->userdata('logged_in_as_admin') || (($this->session->userdata('logged_in') && in_array("payment", explode(",",$this->session->userdata("sub_menu_permission")))))){
            if($this->session->userdata('token')===$this->input->post('token', true)){
                $id = date('y');
                $row = $this->db->query("SELECT MAX(id) as id FROM tbl_admission_payment_details WHERE id LIKE '$id%'")->row();
                $id = isset($row->id) ? $row->id + 1 : $id . "00000001";
                $applicant_id = $this->input->post("applicant_id",true);
                $submitted_by = !$this->session->userdata('logged_in') ? $applicant_id : $this->login_id;
                $status_marked_by = !$this->session->userdata('logged_in') ? "0" : $this->login_id;
                $status_marked_on = !$this->session->userdata('logged_in') ? "0000-00-00 00:00:00" : date("Y-m-d H:i:s");
                $role = !$this->session->userdata('logged_in') ? "APPLICANT" : "USER";
                $attachment = "";
                if($this->input->post("file", true)){
                    $temp = explode('.', $_FILES['fileUTR']['name']);
                    $extension = end($temp);
                    $attachment = $applicant_id . "_" . $this->RandomToken(5) . ".$extension";
                    $config = array('upload_path'=> './upload/marksheet_for_admission/','allowed_types'=>array('jpeg','jpg','pdf'),'overwrite'=>true,'file_name'=>$attachment);
                    $this->upload->initialize($config);
                    $this->upload->do_upload("fileUTR");
                }
                $data = array("id"=>$id,"applicant_id"=>$applicant_id,"payment_mode"=>$this->input->post("payment_mode",true),"transaction_at"=>$this->input->post("transaction_at",true),"utr"=>strtoupper($this->input->post("utr",true)),"attachment"=>$attachment,"utr_verified"=>"","name"=>$this->input->post("name",true),"place"=>strtoupper($this->input->post("place",true)),"amount"=>$this->input->post("amount",true),"transaction_date"=>$this->input->post("transaction_date",true),"date"=>date("Y-m-d H:i:s"),"status"=>$this->input->post("status",true),"status_marked_by"=>$status_marked_by,"status_marked_on"=>$status_marked_on,"remarks"=>$this->input->post("remarks",true),"role"=>$role,"submitted_by"=>$submitted_by,"reason"=>"");
                if($this->db->insert("tbl_admission_payment_details",$data)){
                    $row = $this->db->query("SELECT id,payment_mode,transaction_at,utr,name,place,amount,DATE_FORMAT(transaction_date,'%d-%m-%Y') as transaction_date,DATE_FORMAT(date,'%d-%m-%Y %h:%i:%s') as date, status, remarks FROM tbl_admission_payment_details WHERE applicant_id='$applicant_id' ORDER BY id")->result();
                    $paymentTable = "";
                    foreach($row as $r){
                        $paymentTable .= "<tr><td>$r->id</td><td>$r->payment_mode</td><td>$r->transaction_at</td><td>$r->name</td><td>$r->place</td><td>$r->utr</td><td>$r->amount</td><td>$r->transaction_date</td><td>$r->remarks<br>Submitted on $r->date<br>$r->status</td></tr>";
                    }
                    $message = (!$this->session->userdata('logged_in')) ? "Transactions updated. The transaction will be realised with 24 hours or on next working day...!" : "Transactions updated.";
                    echo json_encode(array("response"=>true,"message"=>"<i class='fa fa-check-circle'></i>$message","paymentTable"=>$paymentTable,"date"=>date("Y-m-d")));
                } else{
                    echo json_encode(array("response"=>false,"error"=>"<i class='fa fa-warning'></i>Unable to update transactions...!"));
                }
            } else{
                echo json_encode(array("response"=>false,"error"=>"<i class='fa fa-warning'></i>Security token mismatch...!"));
            }
        } else{
            echo json_encode(array("response"=>false,"error"=>"<i class='fa fa-warning'></i>Operation not allowed...!"));
        }
    }
    
    function paymentVerification(){
        if($this->session->userdata('logged_in_as_admin') || (($this->session->userdata('logged_in') && in_array("payment-verification", explode(",",$this->session->userdata("sub_menu_permission")))))){
            $data['applicant_payment'] = $this->db->query("SELECT a.id,b.name as applicant_name,b.fname,b.mobile,a.payment_mode,a.transaction_at,a.utr,a.name,a.place,a.amount,DATE_FORMAT(a.transaction_date,'%d-%m-%Y') as transaction_date,DATE_FORMAT(a.date,'%d-%m-%Y %h:%i:%s') as date, a.status FROM tbl_admission_payment_details a, tbl_admission_master b WHERE a.applicant_id=b.id")->result();
            $this->load->view('backend/accounts/frmPaymentVerification',$data);
        }
    }
    
    function verifyPayment(){
        if($this->session->userdata('logged_in_as_admin') || (($this->session->userdata('logged_in') && in_array("payment-verification", explode(",",$this->session->userdata("sub_menu_permission")))))){
            if($this->session->userdata('token')===$this->input->post('token', true)){
                $data = array("utr_verified"=>$this->input->post("utr_verified",true),"status"=>$this->input->post("status",true),"status_marked_by"=>$this->login_id,"status_marked_on"=>date("Y-m-d H:i:s"),"reason"=>$this->input->post("reason",true));
                if($this->db->update("tbl_admission_payment_details",$data,array("id"=>$this->input->post("p_id",true)))){
                    $row = $this->db->query("SELECT a.id,b.name as applicant_name,b.fname,b.mobile,a.payment_mode,a.transaction_at,a.utr,a.name,a.place,a.amount,DATE_FORMAT(a.transaction_date,'%d-%m-%Y') as transaction_date,DATE_FORMAT(a.date,'%d-%m-%Y %h:%i:%s') as date, a.status FROM tbl_admission_payment_details a, tbl_admission_master b WHERE a.applicant_id=b.id")->result();
                    $paymentTable = "";
                    foreach($row as $r){
                        if($r->status==="UNDER PROCESS"){
                            $paymentTable .= "<tr><td>$r->id</td><td>$r->applicant_name</td><td>$r->fname</td><td>$r->mobile</td><td>$r->payment_mode</td><td>$r->transaction_at</td><td>$r->name</td><td>$r->place</td><td>$r->utr</td><td>$r->amount</td><td>$r->transaction_date</td><td class='text-success text-center'><i class='fa fa-2x fa-check-circle' style='cursor: pointer' onclick='openPopup(\"$r->id\",\"VERIFIED\")'></i></td><td class='text-danger text-center'><i class='fa fa-2x fa-times-circle' style='cursor: pointer' onclick='openPopup(\"$r->id\",\"NOT VERIFIED\")'></i></td></tr>";
                        } else{
                            $paymentTable .= "<tr><td>$r->id</td><td>$r->applicant_name</td><td>$r->fname</td><td>$r->mobile</td><td>$r->payment_mode</td><td>$r->transaction_at</td><td>$r->name</td><td>$r->place</td><td>$r->utr</td><td>$r->amount</td><td>$r->transaction_date</td><td colspan='2' class='text-success text-center'>$r->status</td></tr>";
                        }
                    }
                    echo json_encode(array("response"=>true,"message"=>"<i class='fa fa-check-circle'></i>Transaction updated...!","paymentTable"=>$paymentTable));
                } else{
                    echo json_encode(array("response"=>false,"error"=>"<i class='fa fa-warning'></i>Unable to update transactions...!"));
                }
            } else{
                echo json_encode(array("response"=>false,"error"=>"<i class='fa fa-warning'></i>Security token mismatch...!"));
            }
        } else{
            echo json_encode(array("response"=>false,"error"=>"<i class='fa fa-warning'></i>Operation not allowed...!"));
        }
    }
}
