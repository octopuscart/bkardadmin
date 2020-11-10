<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Event_model extends CI_Model {

    function __construct() {
        // Call the Model constructor
        parent::__construct();
    }

    public function AppointmentDataByCountry($country) {
        $this->db->where('country', $country);
        $this->db->group_by('days');
        $this->db->order_by('id');
        $query = $this->db->get('appointment_entry');
        $countryAppointment = $query->result_array();

        $appointmentData = array();

        foreach ($countryAppointment as $akey => $avalue) {
            $aid = $avalue['aid'];
            $this->db->where('aid', $aid);

            $query = $this->db->get('appointment_entry');
            $timeData = $query->result_array();
            $avalue['dates'] = array();
            foreach ($timeData as $tkey => $tvalue) {
                $temparray = array();
                $temparray['date'] = $tvalue['date'];
                $temparray['timing1'] = $tvalue['from_time'];
                $temparray['timing2'] = $tvalue['to_time'];
                array_push($avalue['dates'], $temparray);
            }
            array_push($appointmentData, $avalue);
        }

        return $appointmentData;
    }

    public function EventDataAll($user_id = 0) {
        $date = date("Y-m-d");
        $this->db->where('end_date>=', $date);
        if ($user_id) {
            $this->db->where('user_id', $user_id);
        }
        $this->db->group_by('days');
        $this->db->order_by('end_date desc');
        $query = $this->db->get('events');
        $countryAppointment = $query->result_array();
        $appointmentData = array();
        foreach ($countryAppointment as $akey => $avalue) {
            $aid = $avalue['id'];
            $this->db->where('event_id', $aid);
            $query = $this->db->get('events_dates');
            $timeData = $query->result_array();

            $this->db->where('id', $avalue['user_id']);
            $query = $this->db->get('admin_users');
            $userData = $query->row_array();
            $avalue['manager'] = $userData;

            $avalue['dates'] = array();
            foreach ($timeData as $tkey => $tvalue) {
                $temparray = array();
                $temparray['date'] = $tvalue['date'];
                $temparray['timing1'] = $tvalue['from_time'];
                $temparray['timing2'] = $tvalue['to_time'];
                array_push($avalue['dates'], $temparray);
            }
            array_push($appointmentData, $avalue);
        }
        return $appointmentData;
    }

    function EventData($appId) {
        $appointmentSingle = array();
        $this->db->where('id', $appId);
        $query = $this->db->get('events');
        $appData = $query->result_array();
        
        $this->db->where('event_id', $appId);
        $query = $this->db->get('events_dates');
        $eventdatelist = $query->result_array();
        
        $appDataEvent = $appData[0];
        $appointmentSingle["appointment"] = $appDataEvent;
        $appointmentSingle['date_time_list'] = [];
        foreach ($eventdatelist as $key => $value) {
            $temp = array(
                'from_time' => $value['from_time'],
                'to_time' => $value['to_time'],
                'date' => $value['date'],
                'id' => $value['id']
            );
            array_push($appointmentSingle['date_time_list'], $temp);
        }
        return $appointmentSingle;
    }

}
