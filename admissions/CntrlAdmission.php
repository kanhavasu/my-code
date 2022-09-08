<?php
defined('BASEPATH') OR exit('No direct script access allowed');

include_once dirname(__FILE__) . '/CntrlResource.php';

class CntrlAdmission extends CntrlResource {
    
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
    
    function applicantRegistration(){
        if(!$this->session->userdata('logged_in') || $this->session->userdata('logged_in_as_admin') || (($this->session->userdata('logged_in') && in_array("applicant-registration", explode(",",$this->session->userdata("sub_menu_permission")))))){
            if(!$this->session->userdata('logged_in')){
                $this->csrf->getToken();
            }
            $medium = $this->input->get("medium", true) ? strtoupper($this->input->get("medium", true)) : "ONLINE";
            $data['enquiry'] = array("medium"=>$medium);
            $this->load->view('backend/admission/frmApplicantRegistration', $data);
        }
    }
    
    function fetchPrograms(){
        if(!$this->session->userdata('logged_in') || $this->session->userdata('logged_in_as_admin') || (($this->session->userdata('logged_in') && in_array("applicant-registration", explode(",",$this->session->userdata("sub_menu_permission")))))){
            if($this->session->userdata("token")===$this->input->post("token",true)){
                $level = $this->input->post("level",true);
                $programs = "<option value='0'>-- Select --</option>";
                $lateral = "<option value='-1'>-- Select --</option>";
                $lateral .= "<option value='0'>No</option>";
                $lateralFlag = true;
                $row = $this->db->query("SELECT `id`, `name`, `alias` FROM `tbl_program_master` a WHERE `status`=1 AND `level`='$level'")->result();
                if(isset($row)){    
                    foreach($row as $r){
                        $programs .= "<option value='$r->id'>$r->name [$r->alias]</option>";
                    }
                    echo json_encode(array("response"=>true,"message"=>"<i class='fa fa-check-circle'></i>Programs fetched successfully...!","programs"=>$programs));
                } else{
                    echo json_encode(array("response"=>false,"error"=>"<i class='fa fa-times-circle'></i>Unable to fetch programs...!"));
                }
            }
            else{
                echo json_encode(array("response"=>false,"error"=>"<i class='fa fa-times-circle'></i>Security token mismatch...!"));
            }
        } else{
            echo json_encode(array("response"=>false,"error"=>"<i class='fa fa-times-circle'></i>Operation not allowed...!"));
        }
    }

    function checkLateralEntry(){
        if(!$this->session->userdata('logged_in') || $this->session->userdata('logged_in_as_admin') || (($this->session->userdata('logged_in') && in_array("applicant-registration", explode(",",$this->session->userdata("sub_menu_permission")))))){
            if($this->session->userdata("token")===$this->input->post("token",true)){
                $program_id = $this->input->post("program_id", true);
                $lateral = "<option value='-1'>-- Select --</option>";
                $lateral .= "<option value='0'>No</option>";
                $lateralFlag = true;
                $row = $this->db->query("SELECT DISTINCT(`lateral`) FROM `tbl_branch_master` WHERE `program_id`='$program_id' AND `lateral`=1")->result();
                if(isset($row)){
                    foreach($row as $r){
                        if($r->lateral && $lateralFlag){
                            $lateral .= "<option value='1'>Yes</option>";
                            $lateralFlag = false;
                        }
                    }
                    echo json_encode(array("response"=>true,"message"=>"<i class='fa fa-check-circle'></i>Lateral option in program checked successfully...!","lateral"=>$lateral));
                } else{
                    echo json_encode(array("response"=>false,"error"=>"<i class='fa fa-times-circle'></i>Unable to check lateral entry in program...!"));
                }
            }
            else{
                echo json_encode(array("response"=>false,"error"=>"<i class='fa fa-times-circle'></i>Security token mismatch...!"));
            }
        } else{
            echo json_encode(array("response"=>false,"error"=>"<i class='fa fa-times-circle'></i>Operation not allowed...!"));
        }
    }

    function fetchBranches(){
        if(!$this->session->userdata('logged_in') || $this->session->userdata('logged_in_as_admin') || (($this->session->userdata('logged_in') && in_array("applicant-registration", explode(",",$this->session->userdata("sub_menu_permission")))))){
            if($this->session->userdata("token")===$this->input->post("token",true)){
                $program_id = $this->input->post("program",true);
                $lateral = $this->input->post("lateral",true);
                $branches = "<table class='table table-condensed table-striped table-hover'>";
                $branches .= "<tr><td><label>Branches <span class='text-danger'>*</span></label></td><td><label>Priorities</label></td>";
                $row = ($lateral==="0") ? $this->db->query("SELECT id, alias, name FROM tbl_branch_master WHERE program_id='$program_id' AND first_year=1 AND status=1 ORDER BY name")->result() : $this->db->query("SELECT id, alias, name FROM tbl_branch_master WHERE lateral='$lateral' AND program_id='$program_id' AND status=1 ORDER BY name")->result();
                if(isset($row)){    
                    foreach($row as $r){
                        $branches .= "<tr><td><label><input type='checkbox' class='checkBox' name='chk$r->alias' value='$r->alias' id='$r->alias' onclick='checkBoxState(\"$r->alias\")'/> $r->name</td><td><input type='number' class='form-control' name='$r->alias' value='0'></td></tr>";
                    }
                    $branches .= "</table>";
                    echo json_encode(array("response"=>true,"message"=>"<i class='fa fa-check-circle'></i>Branches fetched successfully...!","branches"=>$branches));
                } else{
                    echo json_encode(array("response"=>false,"error"=>"<i class='fa fa-times-circle'></i>Unable to find branches...!"));
                }
            }
            else{
                echo json_encode(array("response"=>false,"error"=>"<i class='fa fa-times-circle'></i>Security token mismatch...!"));
            }
        } else{
            echo json_encode(array("response"=>false,"error"=>"<i class='fa fa-times-circle'></i>Operation not allowed...!"));
        }
    }
    
    function insertApplicantRegistration(){
        if(!$this->session->userdata('logged_in') || $this->session->userdata('logged_in_as_admin') || (($this->session->userdata('logged_in') && in_array("applicant-registration", explode(",",$this->session->userdata("sub_menu_permission")))))){
            if($this->session->userdata("token")===$this->input->post("token",true)){ 
                $aadhar = $this->input->post('aadhar');
                $rowAadhar = $this->db->query("SELECT aadhar FROM tbl_admission_master WHERE aadhar='$aadhar'")->row();
                if($rowAadhar){
                    echo json_encode(array("response"=>false,"error"=>"<i class='fa fa-times-circle'></i>Record already exist...!"));
                } else{
                    $id = date('y');
                    $row = $this->db->query("SELECT MAX(id) as id FROM tbl_admission_master WHERE id LIKE '$id%'")->row();
                    //generating id
                    $id = isset($row->id) ? $row->id + 1 : $id . "00000001";
                    $login = (!$this->session->userdata('logged_in')) ? $id : $this->login_id;  
                    $role = (!$this->session->userdata('logged_in')) ? "APPLICANT" : "USER";  
                    $data = array("id"=>$id,"program_id"=>$this->input->post("program_id",true),"session_id"=>$this->getCurrentSessionID(),"branch"=>$this->input->post("branch",true),"lateral"=>$this->input->post("lateral",true),"medium"=>$this->input->post("medium",true),"name"=>strtoupper($this->input->post("name",true)),"aadhar"=>$aadhar,"fname"=>strtoupper($this->input->post("fname",true)),"fmobile"=>$this->input->post("fmobile",true),"mname"=>strtoupper($this->input->post("mname",true)),"mmobile"=>$this->input->post("mmobile",true),"gender"=>$this->input->post("gender",true),"dob"=>date_format(date_create($this->input->post("dob",true)),"Y-m-d"),"email"=>strtolower($this->input->post("email",true)),"mobile"=>$this->input->post("mobile",true),"address"=>strtoupper($this->input->post("address",true)),"pincode"=>$this->input->post("pincode",true),"hschool"=>strtoupper($this->input->post("hschool",true)),"hboard"=>strtoupper($this->input->post("hboard",true)),"hpassingyear"=>$this->input->post("hpassingyear",true),"hscience"=>$this->input->post("hscience",true),"hmathematics"=>$this->input->post("hmathematics",true),"henglish"=>$this->input->post("henglish",true),"hpercentage"=>$this->input->post("hpercentage",true),"hmarksheet"=>$this->session->userdata("marksheet10")? $id . "_" . $this->session->userdata("marksheet10"):"","ischool"=>strtoupper($this->input->post("ischool",true)),"iboard"=>strtoupper($this->input->post("iboard",true)),"iresult"=>$this->input->post("iresult",true),"ipassingyear"=>$this->input->post("ipassingyear",true),"iphysics"=>$this->input->post("iphysics",true),"ichemistry"=>$this->input->post("ichemistry",true),"imathematics"=>$this->input->post("imathematics",true),"itotal"=>$this->input->post("itotal",true),"ipcm"=>$this->input->post("ipcm",true),"ienglish"=>$this->input->post("ienglish",true),"ipercentage"=>$this->input->post("ipercentage",true),"imarksheet"=>$this->session->userdata("marksheet12")? $id . "_" . $this->session->userdata("marksheet12"):"","dschool"=>strtoupper($this->input->post("dschool",true)),"dboard"=>strtoupper($this->input->post("dboard",true)),"dbranch"=>strtoupper($this->input->post("dbranch",true)),"dresult"=>$this->input->post("dresult",true),"dpassingyear"=>$this->input->post("dpassingyear",true),"dmaxmarks"=>$this->input->post("dmaxmarks",true),"dmarksobt"=>$this->input->post("dmarksobt",true),"dpercentage"=>$this->input->post("dpercentage",true),"dmarksheet"=>$this->session->userdata("marksheetD")? $id . "_" . $this->session->userdata("marksheetD"):"","gschool"=>strtoupper($this->input->post("gschool",true)),"guniversity"=>strtoupper($this->input->post("guniversity",true)),"gstream"=>strtoupper($this->input->post("gstream",true)),"gresult"=>$this->input->post("gresult",true),"gpassingyear"=>$this->input->post("gpassingyear",true),"gmaxmarks"=>$this->input->post("gmaxmarks",true),"gmarksobt"=>$this->input->post("gmarksobt",true),"gpercentage"=>$this->input->post("gpercentage",true),"gcgpa"=>$this->input->post("gcgpa",true),"gmarksheet"=>$this->session->userdata("marksheetG")? $id . "_" . $this->session->userdata("marksheetG"):"","upseerollno"=>$this->input->post("upseerollno",true),"upseerank"=>$this->input->post("upseerank",true),"jeerollno"=>$this->input->post("jeerollno",true),"jeerank"=>$this->input->post("jeerank",true),"cmatrollno"=>$this->input->post("cmatrollno",true),"cmatrank"=>$this->input->post("cmatrank",true),"catrollno"=>$this->input->post("catrollno",true),"catrank"=>$this->input->post("catrank",true),"matrollno"=>$this->input->post("matrollno",true),"matrank"=>$this->input->post("matrank",true),"xatrollno"=>$this->input->post("xatrollno",true),"xatrank"=>$this->input->post("xatrank",true),"otherexam"=>strtoupper($this->input->post("otherexam",true)),"otherrollno"=>$this->input->post("otherrollno",true),"otherrank"=>$this->input->post("otherrank",true),"hostel"=>$this->input->post("hostel",true),"transport"=>$this->input->post("transport",true),"upgrade"=>$this->input->post("upgrade",true),"dos"=>date("Y-m-d H:i:s"),"admission_status"=>"","admission_status_marked_by"=>"0","admission_status_date"=>"0000-00-00 00:00:00","previous_branch_allotted"=>"","branch_allotted"=>"","branch_allotted_by"=>"0","branch_allotted_date"=>"0000-00-00 00:00:00","allotment_valid_till"=>"0000-00-00","previous_status"=>"","final_status"=>"","final_status_marked_by"=>"0","final_status_date"=>"0000-00-00 00:00:00","submitted_by"=>$login,"role"=>$role,"registration_no"=>"");
                    if($this->session->userdata("marksheet10")||$this->session->userdata("marksheet12")||$this->session->userdata("marksheetD")||$this->session->userdata("marksheetG")){
                        $this->moveMarksheet($id);
                    }
                    $name = strtoupper($this->input->post("name", true));
                    $fname = strtoupper($this->input->post("fname", true));
                    $program_id = $this->input->post("program_id",true);
                    $year = $this->input->post("lateral",true) ? "II" : "I";
                    $program = $this->db->query("SELECT alias FROM tbl_program_master WHERE id='$program_id'")->row()->alias;
                    $message = "<p><strong>$name,<br>";
                    $message .= "S/o / D/o Mr. / Ms/ $fname</strong>,</p>";
                    $message .= "<p>&nbsp;</p><p>Dear $name,</p>";
                    $message .= "<p>Greetings from GL Bajaj Group of Institutions, Mathura  !</p>";
                    $message .= "<p>Thank you for choosing GL Bajaj Group of Institutions, Mathura for your higher studies in the course of your interest i.e. $program. We are happy to share that GL Bajaj Group of Institutions, Mathura always does its best to cater to the innovative teaching-learning environment as well as honing the students’ inherent talent through their participation in other co-curricular and extra curricular activities.</p>";
                    $message .= "<p>You have shown interest to make online registration in the following Course / Branch seeking admission in the session <strong>" . $this->getCurrentSession() . "</strong> on the basis of merit in Management Quota Seats / Vacant Seats.</p>";
                    $message .= "<p>Course Name: $program - $year Year</p>";
                    if($program_id=="1"){
                        $branch = explode(",",$this->input->post("branch",true));
                        sort($branch);
                        for($i=0;$i<count($branch);$i++){
                            $key = explode("-",$branch[$i])[0];
                            $value = explode("-",$branch[$i])[1];
                            $message .= "Priority $key - $value<br>";
                        }
                    }
                    $message .= "<ol>";
                    $message .= "<li>To complete the Online Registration Process, you are <strong>requested to make payment of Rs.2000/-</strong> (Two Thousand Rupees Only) latest by the <strong>" . date('d-m-Y', strtotime(' + 2 days')) . ". You will get your brochure at your address of correspondence with us shortly.</strong> The non-receipt of payment will lead to cancellation of your request. You can pay the registration fee through <span style='color:#800000;font-weight:bold'>NEFT/ RTGS/ IMPS</span>. The necessary Bank Accounts details are mentioned as under -<br>";
                    $message .= "<strong>Account Holder Name	 : GL Bajaj Group of Institutions, Mathura<br> 
                                 Bank A/c No             : 11602191017407<br>
                                 IFSC Code               : PUNB0147710<br>
                                 Bank Name               : Punjab National Bank<br>
                                 Branch                  : GLBIET, Mathura</strong>";
                    $message .= "</li>";
                    $message .= "<p><strong>Note :</strong> Please Do <strong>NOT</strong> deposit <strong>CASH</strong> in the college bank account directly.</p>";
                    $payment_link = base_url() . 'backend/accounts/payment?reference=' . strtoupper(sha1($id)) . '&mode=6B062A445C9F893EDE37C3BDFB0B9A4ABE1F396E';
                    $message .= "<li>After making payment, please fill the transaction details in the tab provided under your Registration Login to get the payment receipt through email. To access please click on link <a href='$payment_link'><strong>$payment_link</strong></a>.</li>";
                    $message .= "</ol>";
                    $message .= "<p>In case of any query/assistance, please feel free to contact 8477820001 / 02 / 03 / 04.</p>";
                    $message .= "<p>&nbsp;</p><p>Stay Safe!&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Stay Healthy!</p>";
                    if($this->db->insert("tbl_admission_master",$data)){
                        $this->sendEmail(strtolower($this->input->post("email",true)), "[ID: $id, $name S/o D/o $fname] Seeking payment of Registration Fee to complete the Registration Process", $message);
                        echo json_encode(array("response"=>true,"message"=>"<i class='fa fa-check-circle'></i>Thank you for showing your interest in GL BAJAJ GROUP OF INSTITUTIONS. Your form submitted successfully. Check your email for the same. Please note down your REFERENCE No. $id for further process."));
                    } else{
                        echo json_encode(array("response"=>false,"error"=>"<i class='fa fa-times-circle'></i>Unable to submit data. Please try later."));
                    }
                } 
            }
            else{
                echo json_encode(array("response"=>false,"error"=>"<i class='fa fa-times-circle'></i>Security token mismatch...!"));
            }
        } else{
            echo json_encode(array("response"=>false,"error"=>"<i class='fa fa-times-circle'></i>Operation not allowed...!"));
        }
    }
    
    function uploadMarksheet(){
        if($this->session->userdata("token")===$this->input->post("token",true)){
            $temp = explode('.', $_FILES['fileMarksheet']['name']);
            $extension = end($temp);
            $filename = $this->RandomToken(5) . ".$extension";
            if($this->input->post("class",true)==="10"){
                $this->session->set_userdata("marksheet10",$filename);
            }
            if($this->input->post("class",true)==="12"){
                $this->session->set_userdata("marksheet12",$filename);
            }
            if($this->input->post("class",true)==="D"){
                $this->session->set_userdata("marksheetD",$filename);
            }
            if($this->input->post("class",true)==="G"){
                $this->session->set_userdata("marksheetG",$filename);
            }
            $config = array('upload_path'=> './upload/marksheet_for_admission/','allowed_types'=>array('jpeg','jpg','pdf'),'overwrite'=>true,'file_name'=>$filename);
            $this->upload->initialize($config);
            if($this->upload->do_upload("fileMarksheet")){
                echo json_encode(array("response"=>true));
            }
        }
        else{
            echo json_encode(array("response"=>false));
        }
    }
        
    function moveMarksheet($id){
        $directory = "./upload/marksheet_for_admission/";
        if($this->session->userdata("marksheet10")){
            rename($directory . $this->session->userdata("marksheet10"),$directory . $id . "_" . $this->session->userdata("marksheet10"));
            $this->session->unset_userdata("marksheet10");
        }
        if($this->session->userdata("marksheet12")){
            rename($directory . $this->session->userdata("marksheet12"),$directory . $id . "_" . $this->session->userdata("marksheet12"));
            $this->session->unset_userdata("marksheet12");
        }
        if($this->session->userdata("marksheetD")){
            rename($directory . $this->session->userdata("marksheetD"),$directory . $id . "_" . $this->session->userdata("marksheetD"));
            $this->session->unset_userdata("marksheetD");
        }
        if($this->session->userdata("marksheetG")){
            rename($directory . $this->session->userdata("marksheetG"),$directory . $id . "_" . $this->session->userdata("marksheetG"));
            $this->session->unset_userdata("marksheetG");
        }
    }
    
    function registration(){
        if($this->session->userdata('logged_in_as_admin') || (($this->session->userdata('logged_in') && in_array("registration", explode(",",$this->session->userdata("sub_menu_permission")))))){
            $id = substr($this->getCurrentSession(),2,2);
            $data['enquiry'] = $this->db->query("SELECT id FROM tbl_admission_master WHERE id LIKE '$id%'")->result();
            $this->load->view('backend/admission/frmRegistration',$data);
        } else{
            redirect(base_url() . 'sign-in');
        }
    }
    
    function fetchApplicantRegistrationData(){
        if($this->session->userdata('logged_in_as_admin') || (($this->session->userdata('logged_in') && in_array("registration", explode(",",$this->session->userdata("sub_menu_permission")))))){
            if($this->session->userdata("token")===$this->input->post("token",true)){
                $reference = $this->input->post("reference", true);
                $row = $this->db->query("SELECT id, (SELECT level FROM tbl_program_master WHERE id=a.program_id) as level, (SELECT alias FROM tbl_program_master WHERE id=a.program_id) as program, program_id, branch, lateral, medium, name, aadhar, gender, fname, fmobile, mname, mmobile, DATE_FORMAT(dob,'%d-%m-%Y') as dob, email, mobile, address, pincode, hschool, hboard, hpassingyear, hscience, hmathematics, henglish, hpercentage, hmarksheet, ischool, iboard, iresult, ipassingyear, iphysics, ichemistry, imathematics, itotal, ipcm, ienglish, ipercentage, imarksheet, dschool, dboard, dbranch, dresult, dpassingyear, dmaxmarks, dmarksobt, dpercentage, dmarksheet, gschool, guniversity, gstream, gresult, gpassingyear, gmaxmarks, gmarksobt, gpercentage, gcgpa, gmarksheet, upseerollno, upseerank, jeerollno, jeerank, cmatrollno, cmatrank, catrollno, catrank, matrollno, matrank, xatrollno, xatrank, otherexam, otherrollno, otherrank, upgrade, DATE_FORMAT(dos,'%d-%m-%Y') as dos, admission_status, (SELECT name FROM tbl_login_master WHERE id=a.admission_status_marked_by) as admission_status_marked_by, DATE_FORMAT(admission_status_date,'%d-%m-%Y') as admission_status_date, previous_branch_allotted, (SELECT name FROM tbl_login_master WHERE id=a.branch_allotted_by) as branch_allotted_by, DATE_FORMAT(branch_allotted_date,'%d-%m-%Y') as branch_allotted_date, DATE_FORMAT(allotment_valid_till,'%d-%m-%Y') as allotment_valid_till, previous_status, (SELECT name FROM tbl_login_master WHERE id=a.final_status_marked_by) as final_status_marked_by, DATE_FORMAT(final_status_date,'%d-%m-%Y') as final_status_date, registration_no FROM tbl_admission_master a WHERE id='$reference'")->row();
                if(isset($row)){
                    $this->session->set_userdata("ms10",$row->hmarksheet);
                    $this->session->set_userdata("ms12",$row->imarksheet);
                    $this->session->set_userdata("msD",$row->dmarksheet);
                    $this->session->set_userdata("msG",$row->gmarksheet);
                    $branch = explode(",",$row->branch);
                    sort($branch);
                    $branch_requested = "<thead><tr><th>Priority</th><th>Branch</th></tr></thead><tbody>";
                    for($i=0;$i<count($branch);$i++){
                        $key = explode("-",$branch[$i])[0];
                        $value = explode("-",$branch[$i])[1];
                        $branch_requested .= "<tr><td>$key</td><td>$value</td></tr>";
                    }
                    $branch_requested .= "</tbody><tfoot><tr><th>Priority</th><th>Branch</th></tr></tfoot>";
                    $branch_to_be_allotted = "<option value='0'>-- Select Branch --</option>";
                    $row2 = $row->lateral==="0" ? $this->db->query("SELECT alias FROM tbl_branch_master WHERE program_id='$row->program_id' AND first_year=1 AND status=1 ORDER BY alias")->result() : $this->db->query("SELECT alias FROM tbl_branch_master WHERE program_id='$row->program_id' AND lateral='1' AND status=1 ORDER BY alias")->result();
                    foreach($row2 as $r){
                        $selected = $r->alias===$row->previous_branch_allotted ? "selected" : "";
                        $branch_to_be_allotted .= "<option value='$r->alias' $selected>$r->alias</option>";
                    }
                    $row3 = $this->db->query("SELECT id,payment_mode,transaction_at,utr,name,place,amount,DATE_FORMAT(transaction_date,'%d-%m-%Y') as transaction_date,DATE_FORMAT(date,'%d-%m-%Y %h:%i:%s') as date,status,remarks FROM tbl_admission_payment_details WHERE applicant_id='$reference' ORDER BY id")->result();
                    $paymentTable = "";
                    foreach($row3 as $r){
                        $paymentTable .= "<tr><td>$r->id</td><td>$r->payment_mode</td><td>$r->transaction_at</td><td>$r->name</td><td>$r->place</td><td>$r->utr</td><td>$r->amount</td><td>$r->transaction_date</td><td>$r->remarks<br>Submitted on $r->date<br>$r->status</td></tr>";
                    }
                    echo json_encode(array("response"=>true,"id"=>$row->id,"level"=>$row->level,"program"=>$row->program,"program_id"=>$row->program_id,"branch_requested"=>$branch_requested,"lateral"=>$row->lateral,"name"=>$row->name,"aadhar"=>$row->aadhar,"gender"=>$row->gender,"fname"=>$row->fname,"fmobile"=>$row->fmobile,"mname"=>$row->mname,"mmobile"=>$row->mmobile,"dob"=>$row->dob,"email"=>$row->email,"mobile"=>$row->mobile,"address"=>$row->address,"pincode"=>$row->pincode,"hschool"=>$row->hschool,"hboard"=>$row->hboard,"hpassingyear"=>$row->hpassingyear,"hscience"=>$row->hscience, "hmathematics"=>$row->hmathematics, "henglish"=>$row->henglish, "hpercentage"=>$row->hpercentage, "hmarksheet"=>$row->hmarksheet,"ischool"=>$row->ischool,"iboard"=>$row->iboard,"iresult"=>$row->iresult,"ipassingyear"=>$row->ipassingyear,"iphysics"=>$row->iphysics, "ichemistry"=>$row->ichemistry, "imathematics"=>$row->imathematics, "itotal"=>$row->itotal, "ipcm"=>$row->ipcm, "ienglish"=>$row->ienglish, "ipercentage"=>$row->ipercentage, "imarksheet"=>$row->imarksheet, "dschool"=>$row->dschool, "dboard"=>$row->dboard, "dbranch"=>$row->dbranch, "dresult"=>$row->dresult, "dpassingyear"=>$row->dpassingyear, "dmaxmarks"=>$row->dmaxmarks, "dmarksobt"=>$row->dmarksobt, "dpercentage"=>$row->dpercentage, "dmarksheet"=>$row->dmarksheet, "gschool"=>$row->gschool, "guniversity"=>$row->guniversity, "gstream"=>$row->gstream, "gresult"=>$row->gresult, "gpassingyear"=>$row->gpassingyear, "gmaxmarks"=>$row->gmaxmarks, "gmarksobt"=>$row->gmarksobt, "gpercentage"=>$row->gpercentage, "gcgpa"=>$row->gcgpa, "gmarksheet"=>$row->gmarksheet, "upseerollno"=>$row->upseerollno, "upseerank"=>$row->upseerank, "jeerollno"=>$row->jeerollno, "jeerank"=>$row->jeerank, "cmatrollno"=>$row->cmatrollno, "cmatrank"=>$row->cmatrank, "catrollno"=>$row->catrollno, "catrank"=>$row->catrank, "matrollno"=>$row->matrollno, "matrank"=>$row->matrank, "xatrollno"=>$row->xatrollno, "xatrank"=>$row->xatrank, "otherexam"=>$row->otherexam, "otherrollno"=>$row->otherrollno, "otherrank"=>$row->otherrank, "dos"=>$row->dos,"admission_status"=>$row->admission_status, "admission_status_marked_by"=>$row->admission_status_marked_by ? $row->admission_status_marked_by : "", "admission_status_date"=>$row->admission_status_date, "previous_branch_allotted"=>$row->previous_branch_allotted, "branch_allotted_by"=>$row->branch_allotted_by, "branch_to_be_allotted"=>$branch_to_be_allotted, "allotment_valid_till"=>$row->allotment_valid_till, "previous_status"=>$row->previous_status, "final_status_marked_by"=>$row->final_status_marked_by ? $row->final_status_marked_by : "", "final_status_date"=>$row->final_status_date,"registration_no"=>$row->registration_no,"paymentTable"=>$paymentTable,"allotment"=>$this->seatAllotmentStatus()));
                } else{
                    echo json_encode(array("response"=>false,"error"=>"<i class='fa fa-warning'></i> Data not found...!"));
                }
            } else{
                echo json_encode(array("response"=>false,"error"=>"<i class='fa fa-warning'></i> Security token mismatch...!"));
            }
        } else{
            echo json_encode(array("response"=>false,"error"=>"<i class='fa fa-warning'></i> Operation not allowed...!"));
        }
    }
        
    function forwardRegistration(){
        if($this->session->userdata('logged_in_as_admin') || (($this->session->userdata('logged_in') && in_array("registration-forward", explode(",",$this->session->userdata("sub_menu_permission")))))){
            if($this->session->userdata("token")===$this->input->post("token",true)){
                $id = array("id"=>$this->input->post("id", true));
                $data = array("admission_status"=>"FORWARDED","admission_status_marked_by"=>$this->login_id,"admission_status_date"=>date("Y-m-d H:i:s"));
                if($this->db->update("tbl_admission_master",$data,$id)){
                    echo json_encode(array("response"=>true,"message"=>"<i class='fa fa-check-circle'></i>Application forwarded."));
                } else{
                    echo json_encode(array("response"=>false,"error"=>"<i class='fa fa-times-circle'></i>Unable to forward the application."));
                }
            } else{
                echo json_encode(array("response"=>false,"error"=>"<i class='fa fa-times-circle'></i>Security token mismatch...!"));
            }
        } else{
            echo json_encode(array("response"=>false,"error"=>"<i class='fa fa-times-circle'></i>Operation not allowed...!"));
        }
    }

    function rejectRegistration(){
        if($this->session->userdata('logged_in_as_admin') || (($this->session->userdata('logged_in') && in_array("registration-reject", explode(",",$this->session->userdata("sub_menu_permission")))))){
            if($this->session->userdata("token")===$this->input->post("token",true)){
                $id = $this->input->post("id", true);
                $name = $this->input->post("name", true);
                $message = "<p>Dear $name,</p>";
                $message .= "<p>We are sorry to inform you that your admission for the applied course and branch is not possible.</p>";
                $message .= "<p>In case of any query/assistance, please feel free to contact 8477820001 / 02 / 03 / 04.</p>";
                $message .= "<p>&nbsp;</p><p>Stay Safe!&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Stay Healthy!</p>";
                $data = array("admission_status"=>"REJECTED","admission_status_marked_by"=>$this->login_id,"admission_status_date"=>date("Y-m-d H:i:s"));
                if($this->db->update("tbl_admission_master",$data,array("id"=>$id))){
                    $this->sendEmail(strtolower($this->input->post("email",true)), "Application rejected", $message);
                    echo json_encode(array("response"=>true,"message"=>"<i class='fa fa-check-circle'></i>Application rejected."));
                } else{
                    echo json_encode(array("response"=>false,"error"=>"<i class='fa fa-times-circle'></i>Unable to reject application."));
                }
            }
            else{
                 echo json_encode(array("response"=>false,"error"=>"<i class='fa fa-times-circle'></i>Security token mismatch...!"));
            }
        } else{
            echo json_encode(array("response"=>false,"error"=>"<i class='fa fa-times-circle'></i>Operation not allowed...!"));
        }
    }

    function allotmentRegistration(){
        if($this->session->userdata('logged_in_as_admin') || (($this->session->userdata('logged_in') && in_array("registration-allotment", explode(",",$this->session->userdata("sub_menu_permission")))))){
            if($this->session->userdata("token")===$this->input->post("token",true)){
                $id = $this->input->post("id", true);
                $admission_status = $this->db->query("SELECT admission_status FROM tbl_admission_master WHERE id='$id' AND admission_status='FORWARDED'")->row();
                if(isset($admission_status)){
                    $name = $this->input->post("name", true);
                    $fname = $this->input->post("fname", true);
                    $program = $this->input->post("course", true);
                    $year = $this->input->post("lateral", true)==="0" ? "I" : "II";
                    $branch_allotted = $this->input->post("branch_allotted", true);
                    $r_no = date('y') . "REG";
                    $row = $this->db->query("SELECT MAX(registration_no) as r_no FROM tbl_admission_master WHERE registration_no LIKE '$r_no%'")->row();
                    if($row->r_no){
                        $temp = intval(substr($row->r_no,strlen($row->r_no)-5,5))+1;
                        $r_no .= strlen($temp)===1 ? "0000$temp" : (strlen($temp)===2 ? "000$temp" : (strlen($temp)===3 ? "00$temp" : (strlen($temp)===4 ? "0$temp" : "$temp")));
                    } else{
                        $r_no .= "00001";
                    }
                    $data = array("previous_branch_allotted"=>"$branch_allotted","branch_allotted"=>"$branch_allotted","branch_allotted_by"=>$this->login_id,"branch_allotted_date"=>date("Y-m-d H:i:s"),"allotment_valid_till"=>date_format(date_create($this->input->post("avt",true)),"Y-m-d"),"registration_no"=>$r_no);
                    $message = "<p><strong>$name,<br>";
                    $message .= "S/o / D/o Mr. / Ms/ $fname,<br>";
                    $message .= "Registration No.: <u>$r_no</u></strong></p>";
                    $message .= "<p>&nbsp;</p><p>Dear $name,</p>";
                    $message .= "<p>Greetings from GL Bajaj Group of Institutions, Mathura  !</p>";
                    $message .= "<p>In reference to your application vide no. $id, we are happy to inform that you have provisionally been given admission in the <strong>$program/ $branch_allotted - $year Year</strong> under the Management Quota Seats / Vacant Seats in the session <strong>" . $this->getCurrentSession() . "</strong>. You are requested to deposit the balance fee (if any) latest by the <strong>" . date('d-m-Y', strtotime(' + 7 days')) . "</strong>. Please pay the fee through <span style='color:#800000;font-weight:bold'>NEFT/ RTGS/ IMPS</span>. The necessary bank accounts details are mentioned as hereunder -</p>";
                    $message .= "<strong>Account Holder Name	 : GL Bajaj Group of Institutions, Mathura<br> 
                                 Bank A/c No             : 11602191017407<br>
                                 IFSC Code               : PUNB0147710<br>
                                 Bank Name               : Punjab National Bank<br>
                                 Branch                  : GLBIET, Mathura</strong>";
                    $message .= "<p><strong>Note :</strong> Please Do <strong>NOT</strong> deposit <strong>CASH</strong> in the college bank account directly.</p>";
                    $payment_link = base_url() . 'backend/accounts/payment?reference=' . strtoupper(sha1($id)) . '&mode=C9F5C76B649A7ABA3E080D3A0E30336E23F83DB9';
                    $message .= "<p>After making payment, please fill the transaction details in the tab provided under your Registration Login to get the payment receipt through email. To access please click on link <a href='$payment_link'><strong>$payment_link</strong></a>.</p>";
                    $message .= "<p>Once you get the payment receipt, thereafter, please upload the documents as mentioned in the <a href='https://www.glbajajgroup.org/admissions/process'>Admission Process</a> failing which your provisional admission may be cancelled. It may be noted that you have been given provisional admission based on the details and documents provided by you and shall be liable to cancel, if any information(s)/document(s) is/are found incorrect/fallacious at any stage or point of time.</p>";
                    $message .= "<p>Hope to have a fruitful academic association with GL BAJAJ Group of Institutions, Mathura.</p>";
                    $message .= "<p>In case of any query/assistance, please feel free to contact 8477820001 / 02 / 03 / 04.</p>";
                    $message .= "<p>&nbsp;</p><p>Stay Safe!&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Stay Healthy!</p>";
                    if($this->db->update("tbl_admission_master",$data,array("id"=>$id))){
                        $this->sendEmail(strtolower($this->input->post("email",true)), "Provisional Branch Allotment Letter", $message);
                        echo json_encode(array("response"=>true,"message"=>"<i class='fa fa-check-circle'></i>Branch allotted.","registration_no"=>$r_no,"allotment"=>$this->seatAllotmentStatus()));
                    } else{
                        echo json_encode(array("response"=>false,"error"=>"<i class='fa fa-times-circle'></i>Unable to allot the branch."));
                    }
                } else{
                    echo json_encode(array("response"=>false,"error"=>"<i class='fa fa-times-circle'></i>Please FORWARD the application first."));
                }
            } else{
                echo json_encode(array("response"=>false,"error"=>"<i class='fa fa-times-circle'></i>Security token mismatch...!"));
            }
        } else{
            echo json_encode(array("response"=>false,"error"=>"<i class='fa fa-times-circle'></i>Operation not allowed...!"));
        }
    }
    
    function confirmRegistration(){
        if($this->session->userdata('logged_in_as_admin') || (($this->session->userdata('logged_in') && in_array("registration-confirm", explode(",",$this->session->userdata("sub_menu_permission")))))){
            if($this->session->userdata("token")===$this->input->post("token",true)){
                $id = $this->input->post("id", true);
                $branch_allotted = $this->db->query("SELECT branch_allotted FROM tbl_admission_master WHERE id='$id' AND branch_allotted IN ('CSE','CSE_AI','CSE_AI_ML','AI_DS','MBA')")->row();
                if(isset($branch_allotted)){
                    $r_no = $this->input->post("r_no", true);
                    $name = $this->input->post("name", true);
                    $fname = $this->input->post("fname", true);
                    $program = $this->input->post("course", true);
                    $year = $this->input->post("lateral", true)==="0" ? "I" : "II";
                    $branch_allotted = $this->input->post("branch_allotted", true);
                    $data = array("branch_allotted"=>"","previous_status"=>$branch_allotted,"final_status"=>$branch_allotted,"final_status_marked_by"=>$this->login_id,"final_status_date"=>date("Y-m-d H:i:s"));
                    $message = "<p><strong>$name,<br>";
                    $message .= "S/o / D/o Mr. / Ms/ $fname,<br>";
                    $message .= "Registration No: <u>$r_no</u></strong></p>";
                    $message .= "<p>&nbsp;</p><p>Dear $name,</p>";
                    $message .= "<p>Greetings from GL Bajaj Group of Institutions, Mathura  !</p>";
                    $message .= "<p>In reference to your application vide no. $id, registration no. $r_no we are happy to inform that you have provisionally been given admission in the <strong>$program/ $branch_allotted - $year Year</strong> under the Management Quota Seats / Vacant Seats in the session <strong>" . $this->getCurrentSession() . "</strong>.";
                    $document_upload_link = base_url() . 'backend/admission/document/upload?reference=' . strtoupper(sha1($id));
                    $message .= "<p>You are required to upload the documents as mentioned in the <a href='https://www.glbajajgroup.org/admissions/process'>Admission Process</a> using <a href='$document_upload_link'><strong>$document_upload_link</strong></a> failing which your provisional admission may be cancelled. It may be noted that you have been given provisional admission based on the details and documents provided by you and shall be liable to cancel, if any information(s)/document(s) is/are found incorrect/fallacious at any stage or point of time.</p>";
                    $message .= "<p>In case of any query/assistance, please feel free to contact 8477820001 / 02 / 03 / 04.</p>";
                    $message .= "<p>&nbsp;</p><p>Stay Safe!&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Stay Healthy!</p>";
                    if($this->db->update("tbl_admission_master",$data,array("id"=>$id))){
                        $this->sendEmail(strtolower($this->input->post("email",true)), "Provisional Admission Letter", $message);
                        echo json_encode(array("response"=>true,"message"=>"<i class='fa fa-check-circle'></i>Admission Confirmed.","allotment"=>$this->seatAllotmentStatus()));
                    } else{
                        echo json_encode(array("response"=>false,"error"=>"<i class='fa fa-times-circle'></i>Unable to allot the branch."));
                    }
                } else{
                    echo json_encode(array("response"=>false,"error"=>"<i class='fa fa-times-circle'></i>Allot the branch first."));
                }
            } else{
                echo json_encode(array("response"=>false,"error"=>"<i class='fa fa-times-circle'></i>Security token mismatch...!"));
            }
        } else{
            echo json_encode(array("response"=>false,"error"=>"<i class='fa fa-times-circle'></i>Operation not allowed...!"));
        }
    }

    function printRegistration(){
        if($this->session->userdata('logged_in_as_admin') || (($this->session->userdata('logged_in') && in_array("registration-print", explode(",",$this->session->userdata("sub_menu_permission")))))){
            if($this->session->userdata("token")===$this->input->get("token",true)){
                $rid = $this->input->get("rid", true);
                $data['details'] = $this->db->query("SELECT a.*, b.alias, c.session FROM tbl_admission_master a, tbl_program_master b, tbl_academic_session c WHERE a.id='$rid' AND a.program_id=b.id AND c.id=a.session_id")->row();
                $this->load->view("backend/admission/printRegistration",$data);
            }
            else{
                echo json_encode(array("response"=>false,"error"=>"<i class='fa fa-times-circle'></i>Security token mismatch...!"));
            }
        } else{
            echo json_encode(array("response"=>false,"error"=>"<i class='fa fa-times-circle'></i>Operation not allowed...!"));
        }
    }
    
    private function seatAllotmentStatus(){
        $id = substr($this->getCurrentSession(),2,2);
        $row = $this->db->query("SELECT alias, intake,(SELECT COUNT(branch_allotted) FROM tbl_admission_master WHERE branch_allotted=a.alias AND id LIKE '$id%') as allotment,(SELECT COUNT(final_status) FROM tbl_admission_master WHERE final_status=a.alias AND id LIKE '$id%') as confirm FROM tbl_branch_master a ORDER BY alias")->result();
        $allotment = "";
        if(isset($row)){
            foreach($row as $r){
                $allotment .= "<tr><td>$r->alias</td><td>$r->intake</td><td>$r->allotment</td><td>$r->confirm</td></tr>";
            }
        }
        return $allotment;
    }
    
    function documentUpload(){
        if(!$this->session->userdata('logged_in') || $this->session->userdata('logged_in_as_admin') || (($this->session->userdata('logged_in') && in_array("document-upload", explode(",",$this->session->userdata("sub_menu_permission")))))){
            if(!$this->session->userdata('logged_in')){
                $this->csrf->getToken();
            }
            $this->load->view('backend/admission/frmDocumentUpload');
        }
    }
}
