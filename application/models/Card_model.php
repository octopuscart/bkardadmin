<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Card_model extends CI_Model {

    function __construct() {
        // Call the Model constructor
        parent::__construct();
        $this->load->database();
    }

    function getCardQr($cardid) {
        $this->load->library('phpqr');
        $this->db->where('card_id', $cardid);
        $query = $this->db->get('card');
        $userdata = $query->row();
        $linkdata = site_url("Api/getUserCard/" . $userdata->card_id);
        header('Content-type: image/jpeg');
        $cardqr = "bkard@" . ($cardid);
        $this->phpqr->showcode($cardqr);
    }

    function cardDetails($card_id, $by_id = 0) {
        if ($by_id) {
            $this->db->where('id', $card_id);
        } else {
            $this->db->where('card_id', $card_id);
        }
        $query = $this->db->get('card');
        $carddata = $query->row_array();
        if ($carddata) {
            $carddata["name"] = ucwords($carddata["first_name"] . " " . $carddata["last_name"]);
            $carddata["designation"] = ucwords($carddata["designation"]);
            $carddata["company_name"] = ucwords($carddata["company_name"]);
            $image = $carddata["image"];
            $default_image = base_url("assets/img/defaultuser.png");
            $profile_image = base_url("assets/profile_image/$image");
            $carddata["profile_image"] = $image ? $profile_image : $default_image;
            $carddata["qrimage"] = site_url("Apiv2/getCardQr/" . $carddata["card_id"]);
            $carddata["status"] = "200";
            $carddata["message"] = "Card Validate";
            return $carddata;
        } else {
            return array("status" => "404", "message" => "Card Not Found");
        }
    }

}
