<?php
defined('BASEPATH') OR exit('No direct script access allowed');

include_once dirname(__FILE__) . '/CntrlResource.php';

class CntrlUser extends CntrlResource {
    
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
        } else{
            redirect(base_url() . 'sign-in');
        }
    }

    function user(){
        if(($this->session->userdata('logged_in_as_admin') || in_array("user-management", explode(",",$this->session->userdata("main_menu_permission"))))){
            $data['users'] = $this->db->query("SELECT id, name, password, gender, email, mobile, email_verify_flag, mobile_verify_flag, role, created_by, DATE_FORMAT(last_login,'%d-%m-%Y %h:%i:%s') as last_login, last_ip, status FROM tbl_login_master WHERE role<>'ADMIN' AND id NOT IN ($this->login_id) ORDER BY status")->result();
            $data['menu'] = $this->db->query("SELECT a.name as main, b.name as sub, b.id as sub_id FROM tbl_main_menu a, tbl_sub_menu b WHERE a.id=b.main_menu_id AND a.status=1 AND b.status=1 ORDER BY a.id, b.name")->result();
            $this->load->view('backend/user/frmUser', $data);
        }
    }
    
    function addUser(){
        if($this->session->userdata('logged_in_as_admin') || in_array("add-user", explode(",",$this->session->userdata("sub_menu_permission")))){
            if($this->session->userdata('token')===$this->input->post('token', true)){
                $email = strtolower($this->input->post('email', true));
                $mobile = $this->input->post('mobile', true);
                $rowEmail = $this->db->query("SELECT email FROM tbl_login_master WHERE email='$email'")->row();
                $rowMobile = $this->db->query("SELECT mobile FROM tbl_login_master WHERE mobile='$mobile'")->row();
                if($rowEmail || $rowMobile){
                    echo json_encode(array("response"=>false,"error"=>"<i class='fa fa-warning'></i> Credentials already exist...!"));
                } else{
                    $id = date('y');
                    $row = $this->db->query("SELECT MAX(id) as id FROM tbl_login_master WHERE id LIKE '$id%'")->row();
                    $id = isset($row->id) ? $row->id + 1 : $id . '00000001';
                    $this->input->post("gender",true)==="M" ? copy("./backend/dist/img/user/male-avatar.jpg", "./backend/dist/img/user/$id.jpg") : copy("./backend/dist/img/user/female-avatar.jpg", "./backend/dist/img/user/$id.jpg");
                    $password = $this->RandomToken(5);
                    $data = array('id'=>$id,'title'=>$this->input->post('title', true),'name'=>strtoupper($this->input->post('name', true)),'password'=>strtoupper(sha1($password)),'gender'=>$this->input->post('gender', true),'email'=>$email,'mobile'=>$mobile,'email_verify_flag'=>'0','mobile_verify_flag'=>'0','role'=>'USER','last_login'=>'0000-00-00 00:00:00','last_ip'=>'000.000.000.000','created_by'=>"$this->login_id",'created_on'=>date('Y-m-d H:i:s'),'status'=>'ACTIVE');
                    $message = "Dear " . strtoupper($this->input->post('title', true) . ' ' . $this->input->post("name", true)) . ",";
                    $message .= "<p>At an outset, we welcome you in the admission cell of GL Bajaj Group of Institutions, Mathura. You can access it's web portal by clicking <a href='" . base_url() . 'sign-in' . "'>here</a> using your <strong>registered email or mobile</strong> and password <strong>$password</strong>.</p>";
                    $message .= "<p><strong>Registered email: $email<br>Registered mobile: $mobile</strong></p>";
                    $message .= "<p>Stay Safe!&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Stay Healthy!";
                    if($this->sendOTPEmail($email, "Welcome to Admission Portal", $message)){
                        $this->db->insert("tbl_login_master",$data);
                        $row = $this->db->query("SELECT id, name FROM tbl_login_master WHERE status='ACTIVE' AND role<>'ADMIN' AND id NOT IN ($this->login_id) ORDER BY name")->result();
                        $options = "<option value='0'>-- Select User --</option>";
                        foreach($row as $r){
                            $options .= "<option value='$r->id'>$r->name [$r->id]</option>";
                        }
                        echo json_encode(array("response"=>true,"message"=>"<i class='fa fa-check-circle'></i> Login created successfully.","options"=>$options));
                    } else{
                        echo json_encode(array("response"=>false,"error"=>"<i class='fa fa-times-circle'></i> Unable to submit data. Please try later."));
                    }
                }
            }else{
                echo json_encode(array("response"=>false,"error"=>"<i class='fa fa-warning'></i> Security token mismatch...!"));
            }
        } else{
            echo json_encode(array("response"=>false,"error"=>"<i class='fa fa-warning'></i> Operation not allowed...!"));
        }
    }
    
    function fetchUserPermission(){
        if($this->session->userdata('logged_in_as_admin') || in_array("user-permission", explode(",",$this->session->userdata("sub_menu_permission")))){
            if($this->session->userdata('token')===$this->input->post('token', true)){
                $user_id = $this->input->post('user_id', true);
                $row = $this->db->query("SELECT GROUP_CONCAT(sub_menu_id) as user_permission FROM tbl_user_permission WHERE user_id='$user_id'")->row();
                if(isset($row)){
                    echo json_encode(array("response"=>true,"message"=>"<i class='fa fa-check-circle'></i> User permission fetched...!","permission"=>explode(",",$row->user_permission)));
                } else{
                    echo json_encode(array("response"=>false,"error"=>"<i class='fa fa-warning'></i> User permission NULL...!"));
                }
            }else{
                echo json_encode(array("response"=>false,"error"=>"<i class='fa fa-warning'></i> Security token mismatch...!"));
            }
        } else{
            echo json_encode(array("response"=>false,"error"=>"<i class='fa fa-warning'></i> Operation not allowed...!"));
        }
    }
    
    function updateUserPermission(){
        if($this->session->userdata('logged_in_as_admin') || in_array("user-permission", explode(",",$this->session->userdata("sub_menu_permission")))){
            if($this->session->userdata('token')===$this->input->post('token', true)){
                $user_id = $this->input->post('user_id', true);
                $permission = implode(",",$this->input->post('permission', true));
                $this->db->query("DELETE FROM tbl_user_permission WHERE user_id='$user_id' AND sub_menu_id NOT IN ($permission)");
                $date = date("Y-m-d H:i:s");
                if($this->db->query("INSERT INTO tbl_user_permission SELECT $user_id, id, $this->login_id, '$date' FROM tbl_sub_menu WHERE id IN ($permission) AND id NOT IN (SELECT sub_menu_id FROM tbl_user_permission WHERE user_id='$user_id')")){
                    echo json_encode(array("response"=>true,"message"=>"<i class='fa fa-check-circle'></i> User permission updated...!"));
                } else{
                    echo json_encode(array("response"=>false,"error"=>"<i class='fa fa-warning'></i> Unable to update user permission...!"));
                }
            }else{
                echo json_encode(array("response"=>false,"error"=>"<i class='fa fa-warning'></i> Security token mismatch...!"));
            }
        } else{
            echo json_encode(array("response"=>false,"error"=>"<i class='fa fa-warning'></i> Operation not allowed...!"));
        }
    }
    
    function resetUserPassword(){
        if($this->session->userdata('logged_in_as_admin') || in_array("reset-user-password", explode(",",$this->session->userdata("sub_menu_permission")))){
            if($this->session->userdata('token')===$this->input->post('token', true)){
                $user_id = $this->input->post('user_id', true);
                $row = $this->db->query("SELECT id, name, email, mobile FROM tbl_login_master WHERE id='$user_id' OR mobile='$user_id' OR email='$user_id'")->row();
                $password = $this->RandomToken(5);
                $message = "Dear " . strtoupper($row->name) . ",";
                $message .= "<p>The password for accessing the Admission Portal has been reset. You can access it by clicking <a href='" . base_url() . 'sign-in' . "'>here</a> using your <strong>registered email or mobile</strong> and password <strong>$password</strong>.</p>";
                $message .= "<p><strong>Registered email: $row->email<br>Registered mobile: $row->mobile</strong></p>";
                $message .= "<p>Stay Safe!&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Stay Healthy!";
                if($this->sendOTPEmail($row->email, "Password reset", $message)){
                    $this->db->update("tbl_login_master",array("password"=>strtoupper(sha1($password))),array("id"=>$row->id));
                    echo json_encode(array("response"=>true,"message"=>"<i class='fa fa-check-circle'></i> Password sent to registered email successfully."));
                } else{
                    echo json_encode(array("response"=>false,"error"=>"<i class='fa fa-times-circle'></i> Unable to send password. Please try later."));
                }
            }else{
                echo json_encode(array("response"=>false,"error"=>"<i class='fa fa-warning'></i> Security token mismatch...!"));
            }
        } else{
            echo json_encode(array("response"=>false,"error"=>"<i class='fa fa-warning'></i> Operation not allowed...!"));
        }
    }
    
    function profile(){
        $this->load->view('backend/user/frmProfile');
    }
    
    function changePassword(){
        if($this->session->userdata('token')===$this->input->post('token', true)){
            $c_pass = $this->input->post('c_pass', true);
            $n_pass = $this->input->post('n_pass', true);
            $r_pass = $this->input->post('r_pass', true);
            $row = $this->db->query("SELECT name, password, email, mobile FROM tbl_login_master WHERE id='$this->login_id'")->row();
            if($c_pass===$row->password){
                if($n_pass===$r_pass){
                    $message = "Dear " . strtoupper($row->name) . ",";
                    $message .= "<p>The password for accessing the Admission Portal has been changed. You can access it by clicking <a href='" . base_url() . 'sign-in' . "'>here</a> using your <strong>registered email or mobile</strong> and password as requested by you.</p>";
                    $message .= "<p>If you haven not changed your password, contact administrator.</p>";
                    $message .= "<p><strong>Registered email: $row->email<br>Registered mobile: $row->mobile</strong></p>";
                    $message .= "<p>Stay Safe!&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Stay Healthy!";
                    if($this->sendOTPEmail($row->email, "Password changed", $message)){
                        $this->db->update("tbl_login_master",array("password"=>$n_pass),array("id"=>$this->login_id));
                        echo json_encode(array("response"=>true,"message"=>"<i class='fa fa-check-circle'></i> Password changed successfully."));
                    } else{
                        echo json_encode(array("response"=>false,"error"=>"<i class='fa fa-times-circle'></i> Unable to change password. Please try later."));
                    }
                } else{
                    echo json_encode(array("response"=>false,"error"=>"<i class='fa fa-times-circle'></i> Passwords do not match.", "focus"=>"$('#txtNPassword')"));
                }
            } else{
                echo json_encode(array("response"=>false,"error"=>"<i class='fa fa-times-circle'></i> Wrong current password.", "focus"=>"$('#txtCPassword')"));
            }
        }else{
            echo json_encode(array("response"=>false,"error"=>"<i class='fa fa-warning'></i> Security token mismatch...!"));
        }
    }
}
