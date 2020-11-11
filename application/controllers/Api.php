<?php

defined('BASEPATH') OR exit('No direct script access allowed');
require(APPPATH . 'libraries/REST_Controller.php');

class Api extends REST_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('Product_model');
        $this->load->model('Event_model');
        $this->load->library('session');
        $this->checklogin = $this->session->userdata('logged_in');
        $this->user_id = $this->session->userdata('logged_in')['login_id'];
    }

    public function index() {
        $this->load->view('welcome_message');
    }

    function getUserDetails($user_id) {
        $this->db->where('id', $user_id); //set column_name and value in which row need to update
        $query = $this->db->get('app_user');
        $userData = $query->row();
        return $userData;
    }

    function updateCurd_post() {
        $fieldname = $this->post('name');
        $value = $this->post('value');
        $pk_id = $this->post('pk');
        $tablename = $this->post('tablename');
        if ($this->checklogin) {
            $data = array($fieldname => $value);
            $this->db->set($data);
            $this->db->where("id", $pk_id);
            $this->db->update($tablename, $data);
        }
    }

    //function for product list
    function loginOperation_get() {
        $userid = $this->user_id;
        $this->db->select('au.id,au.first_name,au.last_name,au.email,au.contact_no');
        $this->db->from('admin_users au');
        $this->db->where('id', $userid);
        $this->db->limit(1);
        $query = $this->db->get();
        $result = $query->row();
        $this->response($result);
    }

    //Login Function 
    //function for product list
    function loginOperation_post() {
        $email = $this->post('contact_no');
        $password = $this->post('password');
        $this->db->select('au.id,au.first_name,au.last_name,au.email,au.contact_no');
        $this->db->from('admin_users au');
        $this->db->where('contact_no', $email);
        $this->db->where('password', md5($password));
        $this->db->limit(1);
        $query = $this->db->get();
        $result = $query->row();

        $sess_data = array(
            'username' => $result->email,
            'first_name' => $result->first_name,
            'last_name' => $result->last_name,
            'login_id' => $result->id,
        );
        $this->session->set_userdata('logged_in', $sess_data);
        $this->response($result);
    }

    function registerMobileGuest_post() {
        $this->config->load('rest', TRUE);
        header('Access-Control-Allow-Origin: *');
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
        $reg_id = $this->post('reg_id');
        $model = $this->post('model');
        $manufacturer = $this->post('manufacturer');
        $uuid = $this->post('uuid');
        $regArray = array(
            "reg_id" => $reg_id,
            "manufacturer" => $manufacturer,
            "uuid" => $uuid,
            "model" => $model,
            "user_id" => "Guest",
            "user_type" => "Guest",
            "datetime" => date("Y-m-d H:i:s a")
        );
        $this->db->where('reg_id', $reg_id);
        $query = $this->db->get('gcm_registration');
        $regarray = $query->result_array();
        if ($regArray) {
            
        } else {
            $this->db->insert('gcm_registration', $regArray);
        }
        $this->response(array("status" => "done"));
    }

    function getUserCard_get($userid) {
        $this->db->where('card_id', $userid);
        $query = $this->db->get('card');
        $userdata = $query->row_array();
        $userdata['qrcode'] = base_url() . "assets/userqr/" . $userdata['card_id'] . ".png";
        $this->response($userdata);
    }

    function createUserQrCode($cardid) {
        $this->load->library('phpqr');
        $filelocation = APPPATH . "../assets/userqr/" . $cardid . ".png";
        $linkdata = site_url("Api/getUserCard/" . $cardid);
        $this->phpqr->createcode($linkdata, $filelocation);
        $imagepath = base_url() . "assets/userqr/" . $cardid . ".png";
        return $imagepath;
    }

    function createUserQrCode_get($cardid) {
        $this->load->library('phpqr');
        $filelocation = APPPATH . "../assets/userqr/" . $cardid . ".png";
        $linkdata = site_url("Api/getUserCard/" . $cardid);
        $this->phpqr->createcode($linkdata, $filelocation);
        $imagepath = base_url() . "assets/userqr/" . $cardid . ".png";
        echo $imagepath;
    }

    function getCardQr_get($cardid) {
        $this->load->library('phpqr');
        $this->db->where('card_id', $cardid);
        $query = $this->db->get('card');
        $userdata = $query->row();
        $linkdata = site_url("Api/getUserCard/" . $userdata->card_id);
//        header('Content-type: image/jpeg');
        $this->phpqr->showcode($linkdata);
    }

    function createUserCard_get($userid) {
        //Set the Content Type
        header('Content-type: image/jpeg');
        // Create Image From Existing File
        $jpg_image = imagecreatefromjpeg(APPPATH . "../assets/cardtemplate/card1.jpg");
        // Allocate A Color For The Text
        $white = imagecolorallocate($jpg_image, 255, 255, 255);
        $blue = imagecolorallocate($jpg_image, 1, 129, 161);

        // Set Path to Font File
        $font_path1 = APPPATH . "../assets/cardtemplate/fonts/Aaargh.ttf";

        $font_path2 = APPPATH . "../assets/cardtemplate/fonts/ABeeZee-Regular.otf";

        // Set Text to Be Printed On Image
        $text = "Pankaj Pathak";
        $this->db->where('usercode', $userid);
        $query = $this->db->get('app_user');
        $userdata = $query->row();
        $randid = rand(10000000, 99999999);
        $destination_image = APPPATH . "../assets/usercard/card1" . $userdata->id . $randid . ".jpg";
        $filelocation = APPPATH . "../assets/userqr/" . $userdata->usercode . ".png";
        $frame = imagecreatefrompng($filelocation);

        // Print Text On Image
        imagettftext($jpg_image, 65, 0, 130, 240, $white, $font_path2, $userdata->name);
        imagettftext($jpg_image, 40, 0, 130, 330, $blue, $font_path1, $userdata->designation);
        imagettftext($jpg_image, 28, 0, 280, 630, $white, $font_path2, $userdata->email);
        imagettftext($jpg_image, 28, 0, 280, 780, $white, $font_path2, $userdata->contact_no);
        imagettftext($jpg_image, 28, 0, 280, 930, $white, $font_path2, $userdata->company);
        imagettftext($jpg_image, 65, 0, 1250, 480, $blue, $font_path2, $userdata->company);
        imagecopymerge($jpg_image, $frame, 1400, 680, 0, 0, 800, 800, 100);
        // Send Image to Browser
        imagejpeg($jpg_image, $destination_image);
        $imagepath = base_url() . "assets/usercard/card1" . $userdata->id . $randid . ".jpg";

        $this->db->set("cardimage", "card1" . $userdata->id . $randid . ".jpg");
        $this->db->where('usercode', $userid);
        $this->db->update("app_user");


        $this->response(array("imagelink" => $imagepath));
    }

    function getUserDataByPassword_post() {
        $this->config->load('rest', TRUE);
        header('Access-Control-Allow-Origin: *');
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
        $password = $this->post('password');
        $email = $this->post('email');
        $this->db->where('password', $password);
        $this->db->where('email', $email);
        $query = $this->db->get('app_user');
        $userdata = $query->row();
        if ($userdata) {
            $this->response(array("status" => "200", "userdata" => $userdata));
        } else {
            $this->response(array("status" => "100"));
        }
    }

    function registration_post() {
        $this->config->load('rest', TRUE);
        header('Access-Control-Allow-Origin: *');
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
        $name = $this->post('displayName');
        $email = $this->post('email');
        $contact_no = "";
        $password = rand(10000000, 99999999);
        $usercode = rand(10000000, 99999999);
        $usercode = $this->post('userId');
        $profileimageurl = $this->post('imageUrl');
        $regArray = array(
            "name" => $name,
            "email" => $email,
            "contact_no" => $contact_no,
            "password" => $password,
            "usercode" => $usercode,
            "datetime" => date("Y-m-d H:i:s a"),
        );
        $this->db->where('email', $email);
        $query = $this->db->get('app_user');
        $userdata = $query->row();
        if ($userdata) {
            unset($regArray['password']);
            $this->db->set($regArray);
            $this->db->where('email', $email); //set column_name and value in which row need to update
            $this->db->update("app_user");
            $this->response(array("status" => "200", "userdata" => $userdata));
        } else {
            $this->db->insert('app_user', $regArray);
            $this->response(array("status" => "200", "userdata" => $regArray));
        }
    }

    function registrationMobile_post() {
        $this->config->load('rest', TRUE);
        header('Access-Control-Allow-Origin: *');
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
        $name = $this->post('name');
        $email = $this->post('email');
        $password = $this->post('password');
        $contact_no = "";

        $usercode = rand(10000000, 99999999);

        $regArray = array(
            "name" => $name,
            "email" => $email,
            "contact_no" => $contact_no,
            "password" => $password,
            "usercode" => "",
            "datetime" => date("Y-m-d H:i:s a"),
        );
        $this->db->where('email', $email);
        $query = $this->db->get('app_user');
        $userdata = $query->row();
        if ($userdata) {


            $this->response(array("status" => "300", "userdata" => $userdata, "message" => "Your Have Already Registered."));
        } else {
            $this->db->insert('app_user', $regArray);
            $last_id = $this->db->insert_id();
            $usercoden = $usercode . $last_id;
            $this->db->set("usercode", $usercoden);
            $this->db->where('id', $last_id); //set column_name and value in which row need to update
            $this->db->update("app_user");
            $regArray['usercode'] = $usercoden;
            $this->response(array("status" => "200", "userdata" => $regArray));
        }
    }

    function updateProfile_post() {
        $this->config->load('rest', TRUE);
        header('Access-Control-Allow-Origin: *');
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
        $email = $this->post('email');
        $profiledata = array(
            'name' => $this->post('name'),
            'email' => $this->post('email'),
            'contact_no' => $this->post('mobile_no'),
            'company' => $this->post('company'),
            'designation' => $this->post('designation'),
        );
        $this->db->set($profiledata);
        $this->db->where('email', $email); //set column_name and value in which row need to update
        $this->db->update("app_user");
        $this->db->order_by('name asc');

        $this->db->where('email', $email); //set column_name and value in which row need to update
        $query = $this->db->get('app_user');
        $userData = $query->row();
        $this->response(array("userdata" => $userData));
    }

    function saveCards_post() {
        $this->config->load('rest', TRUE);
        header('Access-Control-Allow-Origin: *');
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
        $scannerid = $this->post('scanner_id');
        $user_id = $this->post('user_id');

        $this->db->where('card_id', $scannerid);
        $query = $this->db->get('card');
        $carddata = $query->row();

        if ($carddata) {

            $receiver_id = $carddata->user_id;
            $card_id = $carddata->id;
            $sender_id = $user_id;

            $regArray = array(
                "message" => "Card Scan",
                "sender" => $sender_id,
                "receiver" => $receiver_id,
                "card_id" => $card_id,
                "datetime" => date("Y-m-d H:i:s a"),
                "connection" => "Yes",
            );
            $this->db->where('card_id', $card_id);
            $this->db->where('receiver', $receiver_id);
            $this->db->where("sender", $sender_id);
            $query = $this->db->get('card_user_connection');
            $connectobj = $query->row_array();
            if ($connectobj) {
                $this->response(array("message" => "Card Already Saved", "title" => "Already Saved"));
            } else {
                $this->db->insert('card_user_connection', $regArray);
                $last_id = $this->db->insert_id();
                $this->response(array("message" => "Card has been saved..", "title" => "Card Saved"));
            }
        } else {
            $this->response(array("message" => "Invalid card scanned.", "title" => "Card Not Found"));
        }
    }

    function getUsersSavedCard_get($user_id) {
        $cartlist = $this->Product_model->checkUserConnectionCard($user_id);
        $usercarddata = [];
        foreach ($cartlist as $key => $value) {
            $this->db->where('id', $value['card_id']);
            $query = $this->db->get('card');
            $user = $query->row();
            if ($user) {
                $user->connection_id = $value['id'];

//            $user->qrcode = base_url() . "assets/usercard/" . $user->cardimage;
                array_push($usercarddata, $user);
            }
        }
        return $this->response($usercarddata);
    }

    function removeSavedCard_post() {
        $connectid = $this->post("connection_id");
        $this->db->where('id', $connectid);
        $this->db->delete('card_user_connection');
         $this->response(array("message" => "Card has been removed..", "title" => "Card Removed"));
    }

    function removeUsersCard_get($cardid) {
        $this->db->where('id', $cardid);
        $this->db->delete('card_share');
    }

    function getUsersCardAll_get($user_id) {
        $this->db->where('user_id', $user_id);
        $query = $this->db->get('card');
        $usercards = $query->result_array();
        return $this->response($usercards);
    }

    function getDirectoryCardAll_get($user_id) {
        $this->db->where('visibility', "public");
        $this->db->where("user_id !=$user_id");
        $query = $this->db->get('card');
        $usercards = $query->result_array();
        $usercardlist = $usercards;
        $usercardlist = [];
        foreach ($usercards as $key => $value) {
            $user_ids = $value['user_id'];
            $cart_id = $value['id'];
            $usercheck = $this->Product_model->checkUserConnection($user_id, $user_ids, $cart_id);

            if ($usercheck) {
                $value['connected'] = 'Yes';
            } else {
                $value['connected'] = 'No';
            }
            array_push($usercardlist, $value);
        }
        return $this->response($usercardlist);
    }

    function createCard_post() {
        $this->config->load('rest', TRUE);
        header('Access-Control-Allow-Origin: *');
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
        $usercode = rand(10000000, 99999999);
        $visibility = $this->post("visibiliey");
        $visibilitytype = $visibility ? 'public' : 'private';
        $regArray = array(
            "name" => $this->post('name'),
            "email" => $this->post('email'),
            "contact_no" => $this->post('contact_no'),
            "company_name" => $this->post('company'),
            "designation" => $this->post('designation'),
            "industry" => $this->post('industry'),
            "address1" => $this->post('address1'),
            "address2" => $this->post('address2'),
            "country" => $this->post('country'),
            "state" => $this->post('state'),
            "city" => $this->post('city'),
            "datetime" => date("Y-m-d H:i:s a"),
            "user_id" => $this->post('user_id'),
            "card_type" => $this->post("card_type"),
            "visibility" => $visibilitytype,
        );
        $this->db->insert('card', $regArray);
        $last_id = $this->db->insert_id();

        $userid = $this->post('user_id');

        $this->db->where('id', $userid);
        $query = $this->db->get('app_user');
        $userdata = $query->row();

        $usercode = $userdata->usercode;

        $cardid = $usercode . "" . $last_id;

        $imagepath = base_url() . "assets/userqr/" . $cardid . ".png";
        $this->db->set("card_id", $cardid);
        $this->db->set("qrcode", "yes");
        $this->db->where('id', $last_id); //set column_name and value in which row need to update
        $this->db->update("card");
        $cardurl = $this->createUserQrCode($cardid);
        $this->response(array("status" => "200", "userdata" => $regArray));
    }

    function removeCard_post() {
        $card_id = $this->post('card_id');
        $this->db->where('card_id', $card_id); //set column_name and value in which row need to update
        $this->db->delete("card");
    }

    //Event Controllers 
    function eventsList_get() {
        $eventlist = $this->Event_model->EventDataAll();
        $eventlisttemp = [];
        $imagepath = base_url() . "assets/media/";
        foreach ($eventlist as $key => $value) {
            $value['image'] = $imagepath . $value['image'];
            array_push($eventlisttemp, $value);
        }
        $this->response($eventlisttemp);
    }

    function eventDetails_get($event_id) {
        $this->db->where("id", $event_id);
        $query = $this->db->get('events');
        $eventDetails = $query->row_array();
        $imagepath = base_url() . "assets/media/";
        $eventDetails['image'] = $imagepath . $eventDetails['image'];
        $eventDetails['map'] = "https://maps.google.com/?q=" . $eventDetails['venue'] . "+" . $eventDetails['address'] . "&output=embed";
        $this->response($eventDetails);
    }

    function joinEvent_post() {
        $this->config->load('rest', TRUE);
        header('Access-Control-Allow-Origin: *');
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
        $profiledata = array(
            'user_id' => $this->post('user_id'),
            'event_id' => $this->post('event_id'),
            'package_id' => $this->post('package_id'),
            'join_date' => date("Y-m-d"),
            'join_time' => date("H:i:s a"),
            "join_code" => "",
            "status" => ""
        );
        $this->response(array("message" => "Joined"));
    }

    //end of event controller']
    //
    //
    // start of user connection
    function userConnection_post() {
        $sender = $this->post('sender');
        $receiver = $this->post('receiver');
        $card_id = $this->post('card_id');
        $regArray = array(
            "message" => $this->post('message'),
            "sender" => $this->post('sender'),
            "receiver" => $this->post('receiver'),
            "card_id" => $this->post('card_id'),
            "datetime" => date("Y-m-d H:i:s a"),
            "connection" => "No",
        );
        $this->db->where('card_id', $card_id);
        $this->db->where('receiver', $receiver);
        $this->db->where("sender", $sender);
        $query = $this->db->get('card_user_connection');
        $connectobj = $query->row_array();
        if ($connectobj) {
            $this->response(array("message" => "Your request already sent.", "title" => "Already Sent"));
        } else {
            $this->db->insert('card_user_connection', $regArray);
            $last_id = $this->db->insert_id();
            $this->response(array("message" => "Your request has been sent.", "title" => "Request Sent"));
        }
    }

    function activeConnection_post() {
        $connection_id = $this->post('connection_id');
        $rtype = $this->post('rtype');
        if ($rtype == 'accept') {
            $this->db->set("connection", "Yes");
            $this->db->where('id', $connection_id); //set column_name and value in which row need to update
            $this->db->update("card_user_connection");
        } else {
            $this->db->where('id', $connection_id); //set column_name and value in which row need to update
            $this->db->delete("card_user_connection");
        }
    }

    //end of user connection
    //
    //
    //Notification controller
    function notifications_get($user_id) {
        $notificationarray = $this->Product_model->getUserNotificaions($user_id);
        $this->response($notificationarray);
    }

    function notificaioncount_get($user_id) {
        $notificationarray = $this->Product_model->getUserNotificaions($user_id);
        $this->response(array("count" => count($notificationarray)));
    }

    //end of notification Controller
    //
    //
    // User message Controller
    function userMessage_post() {
        $sender = $this->post('sender');
        $receiver = $this->post('receiver');
        $regArray = array(
            "message" => $this->post('message'),
            "sender" => $this->post('sender'),
            "receiver" => $this->post('receiver'),
            "datetime" => date("Y-m-d H:i:s a"),
            "read_status" => "0",
        );

        $this->db->insert('user_message', $regArray);
        $last_id = $this->db->insert_id();
    }

    function getLastMessage($user_id, $connect_id) {
        $msquery = "select  message, datetime from 
(SELECT * FROM user_message where sender = $connect_id and receiver = $user_id 
UNION
SELECT * FROM user_message where sender = $user_id and receiver = $connect_id
 ) as usermessage order by id limit 0, 1";
        $query = $this->db->query($msquery);
        $messagearray = $query->row_array();
        return $messagearray;
    }

    function getLastMessage_get($user_id) {
        $msquery = "select user_id from (
              SELECT receiver as  user_id FROM `user_message` where sender = $user_id 
              union all
              SELECT sender as user_id FROM `user_message` where receiver = $user_id
              ) as messageusers group by user_id";
        $query = $this->db->query($msquery);
        $messagearray = $query->result_array();
        $messageArrayTemp = array();
        foreach ($messagearray as $key => $value) {
            $connect_id = $value['user_id'];
            $messageobj = $this->getLastMessage($user_id, $connect_id);
            $userdata = $this->getUserDetails($connect_id);
            $userMessageTemp = array("message" => $messageobj, "user" => $userdata);
            array_push($messageArrayTemp, $userMessageTemp);
        }
        $this->response($messageArrayTemp);
    }

    function userMessage_get($user_s, $user_r) {
        $this->db->set("read_status", "1");
        $this->db->where('receiver', $user_s);
        $this->db->where('sender', $user_r);
        $this->db->update("user_message");
        $query = " select
            message, datetime, read_status, sender, receiver from
            (select message, datetime, read_status, sender, receiver from user_message where sender = $user_s and receiver = $user_r
                union
            select message, datetime, read_status, sender, receiver from user_message where sender = $user_r and receiver = $user_s)
             as messagedata order by datetime asc   
                ";

        $query = $this->db->query($query);
        $messagearray = $query->result_array();
        $this->response($messagearray);
    }

    function postEventWall_post() {
        $this->config->load('rest', TRUE);
        header('Access-Control-Allow-Origin: *');
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");

        $visibilitytype = $visibility ? 'public' : 'private';
        $regArray = array(
            "name" => $this->post('name'),
            "email" => $this->post('email'),
        );
    }

    //end of user message controller
}

?>