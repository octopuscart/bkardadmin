<?php

defined('BASEPATH') OR exit('No direct script access allowed');
require(APPPATH . 'libraries/REST_Controller.php');

class Apiv2 extends REST_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('Product_model');
        $this->load->model('Card_model');
        $this->load->model('Event_model');
        $this->load->library('session');
        $this->API_ACCESS_KEY = "AAAAbw0xhr4:APA91bHrZptVEVRpQdGwg0TClX_JTcc2jqRU3Uhn_Qm6Qgj4G6BrO651YqZzDLlFzfTD4IvYTceShd5LEfZTEL7aOwcyTgAoGPQ9e6nU6f1_Pb9PabMFPb-zNWtZktaookSvCNw05IrA";
        $this->user_id = $this->session->userdata('logged_in_id');
    }

    public function index() {
        $this->load->view('welcome_message');
    }

    private function useCurl($url, $headers, $fields = null) {
        // Open connection
        $ch = curl_init();
        if ($url) {
            // Set the url, number of POST vars, POST data
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            // Disabling SSL Certificate support temporarly
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            if ($fields) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
            }

            // Execute post
            $result = curl_exec($ch);
            if ($result === FALSE) {
                die('Curl failed: ' . curl_error($ch));
            }

            // Close connection
            curl_close($ch);

            return $result;
        }
    }

    public function android($data, $reg_id_array) {
        $url = 'https://fcm.googleapis.com/fcm/send';
        $headers = array(
            'Authorization: key=' . $this->API_ACCESS_KEY,
            'Content-Type: application/json'
        );
        return $this->useCurl($url, $headers, json_encode($data));
    }

    function getUserDetails($user_id) {
        $this->db->where('id', $user_id); //set column_name and value in which row need to update
        $query = $this->db->get('app_user');
        $userData = $query->row();
        $imageurltemp = $userData->photo_url == "null" ? "https://ui-avatars.com/api/?name=" . $userData->name : $userData->photo_url;
        $userData->photo_url = $imageurltemp;
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

    function getUserCard_get($card_id) {
        $usercard = $this->Card_model->cardDetails($card_id);
        $this->response($usercard);
    }

    function checkCurrentUser_get() {
        $this->response(array("user_id" => $this->session->userdata('logged_in_id')));
    }

    function getCardQr_get($card_id) {
        $this->load->library('phpqr');

        $usercard = $this->Card_model->cardDetails($card_id);

        $qrdata = array();
        if ($usercard["status"] == "200") {
            $qrdatalist = ["name", "profile_image", "card_id", "designation"];
            foreach ($qrdatalist as $key => $value) {
                $qrdata[$value] = $usercard[$value];
            }
        } else {
            $qrdata = $usercard;
        }

        $this->phpqr->showcode(json_encode($qrdata));
    }

    function createUserCard_get($userid) {
        //Set the Content Type
//        header('Content-type: image/jpeg');
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
        $filelocation = APPPATH . "../assets/userqr/" . $userdata->id . ".png";
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
//        imagejpeg($jpg_image, $destination_image);
//        $imagepath = base_url() . "assets/usercard/card1" . $userdata->id . $randid . ".jpg";
//
//        $this->db->set("cardimage", "card1" . $userdata->id . $randid . ".jpg");
//        $this->db->where('usercode', $userid);
//        $this->db->update("app_user");
//
//
//        $this->response(array("imagelink" => $imagepath));
    }

    function registrationMobileGoogle_post() {
        $this->config->load('rest', TRUE);
        header('Access-Control-Allow-Origin: *');
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
        $name = $this->post('name');
        $email = $this->post('email');
        $profile_id = $this->post('id');
        $photo_url = $this->post('photo_url');
        $photo_type = $this->post('photo_type');
        $profile_id = $this->post('profile_id');
        $contact_no = "";
        $regArray = array(
            "name" => $name,
            "email" => $email,
            "contact_no" => $contact_no,
            "photo_type" => "google",
            "profile_id" => $profile_id,
            "photo_url" => $photo_url,
            "datetime" => date("Y-m-d H:i:s a"),
        );
        $this->db->where('email', $email);
        $query = $this->db->get('app_user');
        $userdata = $query->row();
        if ($userdata) {
            $this->response(array("status" => "200", "userdata" => $userdata));
        } else {
            $this->db->insert('app_user', $regArray);
            $last_id = $this->db->insert_id();
            $regArray["id"] = $last_id;
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
            $user = $query->row_array();

            if ($user) {
                $usercard = $this->Card_model->cardDetails($user['card_id']);
                $usercard["connection_id"] = $value['id'];

//            $user->qrcode = base_url() . "assets/usercard/" . $user->cardimage;
                array_push($usercarddata, $usercard);
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

    function getUsersCardAll_get($user_id = 1) {
        $this->db->where('user_id', $user_id);
        $this->db->order_by("id desc");
        $query = $this->db->get('card');
        $usercards = $query->result_array();
        $usercardslist = [];
        foreach ($usercards as $key => $value) {
            $usercard = $this->Card_model->cardDetails($value["card_id"]);
            array_push($usercardslist, $usercard);
        }
        return $this->response($usercardslist);
    }

    function getDirectoryCardAll_get($user_id = 1) {

        $this->db->where("user_id !=$user_id");
        $query = $this->db->get('card');
        $usercards = $query->result_array();
        $usercardlist = $usercards;
        $usercardlist = [];
        foreach ($usercards as $key => $value) {
            $user_ids = $value['user_id'];
            $cart_id = $value['id'];
            $usercard = $this->Card_model->cardDetails($value["card_id"]);
            $usercheck = $this->Product_model->checkUserConnection($user_id, $user_ids, $cart_id);

            if (isset($usercheck['connection'])) {
                $usercard['connected'] = $usercheck['connection'];
            } else {
                $usercard['connected'] = '-';
            }

            array_push($usercardlist, $usercard);
        }
        return $this->response($usercardlist);
    }

    function fileupload_post() {

        $ext1 = explode('.', $_FILES['file']['name']);
        $ext = strtolower(end($ext1));
        $filename = $type . rand(1000, 10000);

        $actfilname = $_FILES['file']['name'];

        $filelocation = "assets/profile_image/";
        move_uploaded_file($_FILES["file"]['tmp_name'], $filelocation . $actfilname);


        $this->response(array("status" => "200"));
    }

    function testFile_get() {

        echo $filelocation = APPPATH . "../assets/profile_image";
    }

    function createCard_post() {
        $this->config->load('rest', TRUE);
        header('Access-Control-Allow-Origin: *');
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
        $usercode = rand(10000000, 99999999);
        $visibility = $this->post("visibility");
        $visibilitytype = $visibility ? 'public' : 'private';
        $regArray = array(
            "first_name" => $this->post('first_name'),
            "last_name" => $this->post('last_name'),
            "email" => $this->post('email'),
            "image" => $this->post('image'),
            "contact_no" => $this->post('contact_no'),
            "company_name" => $this->post('company_name'),
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
            "country_code" => $this->post("country_code"),
            "visibility" => $visibility,
        );
        $this->db->insert('card', $regArray);
        $last_id = $this->db->insert_id();

        $userid = $this->post('user_id');

        $this->db->where('id', $userid);
        $query = $this->db->get('app_user');
        $userdata = $query->row();

        $usercode = $usercode;

        $cardid = $usercode . "" . $last_id;

        $this->db->set("card_id", $cardid);
        $this->db->set("qrcode", "yes");
        $this->db->where('id', $last_id); //set column_name and value in which row need to update
        $this->db->update("card");

        $this->response(array("status" => "200", "userdata" => $regArray, "card_id" => $cardid, "message" => "Your card has been created."));
    }

    function editCard_post($card_id) {
        $this->config->load('rest', TRUE);
        header('Access-Control-Allow-Origin: *');
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");

        $regArray = array(
            "first_name" => $this->post('first_name'),
            "last_name" => $this->post('last_name'),
            "email" => $this->post('email'),
            "image" => $this->post('image'),
            "contact_no" => $this->post('contact_no'),
            "company_name" => $this->post('company_name'),
            "designation" => $this->post('designation'),
            "industry" => $this->post('industry'),
            "address1" => $this->post('address1'),
            "address2" => $this->post('address2'),
            "country" => $this->post('country'),
            "state" => $this->post('state'),
            "city" => $this->post('city'),
            "datetime" => date("Y-m-d H:i:s a"),
            "country_code" => $this->post("country_code"),
        );
        $this->db->set($regArray);
        $this->db->where('id', $card_id); //set column_name and value in which row need to update
        $this->db->update("card");
        $this->response(array("status" => "200", "userdata" => $regArray, "card_id" => $card_id, "message" => "Your card has been updated."));
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
        $message = $this->post('message');
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

        $messageArray = array(
            "message" => $this->post('message'),
            "sender" => $this->post('sender'),
            "receiver" => $this->post('receiver'),
            "datetime" => date("Y-m-d H:i:s a"),
            "read_status" => "0",
        );
        if ($message) {
            $this->db->insert('user_message', $messageArray);
            $last_message_id = $this->db->insert_id();
        }


        if ($connectobj) {
            $this->response(array("message" => "Your request already sent.", "title" => "Already Sent"));
        } else {
            $this->db->insert('card_user_connection', $regArray);
            $last_id = $this->db->insert_id();
            $this->response(array("message" => "Your request has been sent.", "title" => "Request Sent"));
        }
    }

    function requestConnections_get($user_id) {

        $this->db->where("receiver", $user_id);
        $this->db->where("connection", "No");
        $this->db->order_by("id desc");
        $query = $this->db->get("card_user_connection");
        $connectionlist = $query->result_array();
        $connectionfinal = [];
        foreach ($connectionlist as $key => $value) {
            $tempconnect = $value;
            $tempconnect["sender"] = $this->getUserDetails($value["sender"]);
            $tempconnect["card"] = $this->Card_model->cardDetails($value["card_id"], 1);
            array_push($connectionfinal, $tempconnect);
        }
        $this->response($connectionfinal);
    }

    function countNotifications_get($user_id) {
        $unseenmessge = $this->countUnseenMessage($user_id, 0);
        $this->db->select("count(id) as count");
        $this->db->where("receiver", $user_id);
        $this->db->where("connection", "No");
        $this->db->order_by("id desc");
        $query = $this->db->get("card_user_connection");
        $connectioncount = $query->row();
        $totalcount = $unseenmessge + $connectioncount->count;
         $this->response(array("count"=>$totalcount));
    }

    function activeConnection_post() {
        $connection_id = $this->post('connection_id');
        $rtype = $this->post('rtype');
        if ($rtype == 'accept') {
            $this->db->set("connection", "Yes");
            $this->db->where('id', $connection_id); //set column_name and value in which row need to update
            $this->db->update("card_user_connection");
            $this->response(array("message" => "Connect request has been accepted", "title" => "Connected"));
        } else {
            $this->db->where('id', $connection_id); //set column_name and value in which row need to update
            $this->db->delete("card_user_connection");
            $this->response(array("message" => "Connect request has been rejected", "title" => "Rejected"));
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
        $this->db->select("id");
        $this->db->where("receiver", $user_id);
        $this->db->where("read_status", "0");
        $query = $this->db->get("user_message");
        $messagearray = $query->row_array();

        $messagearraycount = $messagearray ? count($messagearray) : 0;

        $this->response(array("notification_count" => count($notificationarray), "message_count" => $messagearraycount));
    }

    //end of notification Controller
    //
    //
    // User message Controller

    function chatUser_get($user_id) {
        $this->db->where('id', $user_id); //set column_name and value in which row need to update
        $query = $this->db->get("app_user");
        $userobj = $query->row();
        $userarray = array("status" => "100");
        if ($userobj) {
            $imageurltemp = $userobj->photo_url == "null" ? "https://ui-avatars.com/api/?name=" . $userobj->name : $userobj->photo_url;
            $name = $userobj->name;
            $userarray = array("name" => $name, "image" => $imageurltemp, "status" => "200");
        }
        $this->response($userarray);
    }

    function sendChatNotification($user_id, $sender_id, $message) {
        $this->db->where('id', $sender_id); //set column_name and value in which row need to update
        $query = $this->db->get("app_user");
        $userobj = $query->row();
        if ($userobj) {
            $imageurltemp = $userobj->photo_url == "null" ? "https://ui-avatars.com/api/?name=" . $userobj->name : $userobj->photo_url;
            $name = $userobj->name;
            $this->db->where('user_id', $user_id); //set column_name and value in which row need to update
            $query2 = $this->db->get("gcm_registration");
            $tokenobj = $query2->row();



            if ($tokenobj) {

                $data = [
                    "to" => $tokenobj->reg_id,
                    "notification" => [
                        "body" => $message,
                        "title" => "Chat Message from $name",
                        "page" => "chat",
                        "icon" => "ic_launcher",
                        "image" => $imageurltemp
                    ],
                    "data" => array("user_id" => $sender_id)
                ];
                $this->android($data, [$tokenobj->reg_id]);
            }
        }
    }

    function sendChatNotification_get() {
        $this->sendChatNotification(24, 23, "test message");
    }

    function userMessage_post() {
        $sender = $this->post('sender');
        $receiver = $this->post('receiver');
        $message = $this->post('message');
        $regArray = array(
            "message" => $this->post('message'),
            "sender" => $this->post('sender'),
            "receiver" => $this->post('receiver'),
            "datetime" => date("Y-m-d H:i:s a"),
            "read_status" => "0",
        );
        $this->db->insert('user_message', $regArray);
        $last_id = $this->db->insert_id();
        $this->sendChatNotification($receiver, $sender, $message);
    }

    function countUnseenMessage($user_id, $connect_id) {
        $this->db->select("count(id) as count");
        $this->db->where('receiver', $user_id);
        if ($connect_id) {
            $this->db->where('sender', $connect_id);
        }//set column_name and value in which row need to update
        $this->db->where('read_status', "0");
        $query = $this->db->get("user_message");
        $userobj = $query->row();
        return $userobj->count;
    }

    function getLastMessage($user_id, $connect_id) {
        $msquery = "select  message, datetime, id, read_status from 
(SELECT * FROM user_message where sender = $connect_id and receiver = $user_id
UNION
SELECT * FROM user_message where sender = $user_id and receiver = $connect_id   
 ) as usermessage order by id desc limit 0, 1";
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
            $count = $this->countUnseenMessage($user_id, $connect_id);
            $userdata = $this->getUserDetails($connect_id);
            $userMessageTemp = array("message" => $messageobj, "user" => $userdata, "unseen" => $count);
            array_push($messageArrayTemp, $userMessageTemp);
        }
        $this->response($messageArrayTemp);
    }

    function userMessage_get($user_s, $user_r) {
        $this->db->set("read_status", "1");
        $this->db->where('receiver', $user_s);
        $this->db->where('sender', $user_r);
        $this->db->update("user_message");

        $this->db->where('id', $user_r); //set column_name and value in which row need to update
        $query = $this->db->get("app_user");
        $userobj = $query->row();

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

    function setFCMToken_post() {
        $postdata = $this->post();
        $insertArray = array(
            "model" => "",
            "manufacturer" => "",
            "uuid" => "",
            "datetime" => date("Y-m-d H:m:s a"),
            "user_id" => $postdata["user_id"],
            "reg_id" => $postdata["token_id"],
        );
        $this->db->where("user_id", $postdata["user_id"]);
        $query = $this->db->get("gcm_registration");
        $querydata = $query->result_array();
        if ($querydata) {
            $this->db->set($insertArray)->where("user_id", $postdata["user_id"])->update("gcm_registration");
            $this->response(array("status" => "200", "last_id" => $querydata[0]["id"]));
        } else {
            $this->db->insert("gcm_registration", $insertArray);
            $insert_id = $this->db->insert_id();
        }
        $this->response(array("status" => "200", "last_id" => $insert_id));
    }

    function testNotification_get() {
        $tokenid = "fItBW9yASM2ex_n8htEex9:APA91bH76PbREaw_A6mGNHUQEwKiwEV4iiLPeTFVReE8EW-nsRF8spY7qsgYtiWKTZJ2OhwMJtkdikgY-PgPVje8dGFk7ZiMj1ir9ZXLoOc_ItukaR3B_XYSK3d5ENV9_0No48M9xS1a";
        $data = [
            "to" => $tokenid,
            "notification" => [
                "body" => "This is message body 32322323 ",
                "page" => "chat",
                "icon" => "ic_launcher",
                "image" => "https://lh3.googleusercontent.com/a-/AOh14GiB7yiRkI4V4-YdxtDt27CWqF1U-0ZhfQ3mT_96uA"
            ],
            "data" => array("channel_id" => "1215")
        ];
        echo $this->android($data, [$tokenid]);
    }

}

?>