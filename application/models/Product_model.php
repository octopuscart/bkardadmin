<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Product_model extends CI_Model {

    function __construct() {
        // Call the Model constructor
        parent::__construct();
        $this->load->database();
    }

    function edit_table_information($tableName, $id) {
        $this->User_model->tracking_data_insert($tableName, $id, 'update');
        $this->db->update($tableName, $id);
    }

    public function query_exe($query) {
        $query = $this->db->query($query);
        if ($query->num_rows() > 0) {
            foreach ($query->result_array() as $row) {
                $data[] = $row;
            }
            return $data; //format the array into json data
        }
    }

    function delete_table_information($tableName, $columnName, $id) {
        $this->db->where($columnName, $id);
        $this->db->delete($tableName);
    }

    ///*******  Get data for deepth of the array  ********///

    function get_children($id, $container) {
        $this->db->where('id', $id);
        $query = $this->db->get('category');
        $category = $query->result_array()[0];
        $this->db->where('parent_id', $id);
        $query = $this->db->get('category');
        if ($query->num_rows() > 0) {
            $childrens = $query->result_array();

            $category['children'] = $query->result_array();

            foreach ($query->result_array() as $row) {
                $pid = $row['id'];
                $this->get_children($pid, $container);
            }
            return $category;
        } else {
            return $category;
        }
    }

    function getparent($id, $texts) {
        $this->db->where('id', $id);
        $query = $this->db->get('category');
        if ($query->num_rows() > 0) {
            foreach ($query->result_array() as $row) {
                array_push($texts, $row);
                $texts = $this->getparent($row['parent_id'], $texts);
            }
            return $texts;
        } else {
            return $texts; //format the array into json data
        }
    }

    function parent_get($id) {
        $catarray = $this->getparent($id, []);
        array_reverse($catarray);
        $catarray = array_reverse($catarray, $preserve_keys = FALSE);
        $catcontain = array();
        foreach ($catarray as $key => $value) {
            array_push($catcontain, $value['category_name']);
        }
        $catstring = implode("->", $catcontain);
        return array('category_string' => $catstring, "category_array" => $catarray);
    }

    function child($id) {
        $this->db->where('parent_id', $id);
        $query = $this->db->get('category');
        if ($query->num_rows() > 0) {
            foreach ($query->result_array() as $row) {
                $cat[] = $row;
                $cat[$row['id']] = $this->child($row['id']);
                $cat[] = $row;
            }
            return $cat; //format the array into json data
        }
    }

    function singleProductAttrs($product_id) {
        $query = "SELECT pa.attribute, pa.product_id, pa.attribute_value_id, cav.attribute_value FROM product_attribute as pa 
join category_attribute_value as cav on cav.id = pa.attribute_value_id
where pa.product_id = $product_id group by attribute_value_id";
        $product_attr_value = $this->query_exe($query);
        $arrayattr = [];
        foreach ($product_attr_value as $key => $value) {
            $attrk = $value['attribute'];
            $attrv = $value['attribute_value'];
            array_push($arrayattr, $attrk . '-' . $attrv);
        }
        return implode(", ", $arrayattr);
    }

    function product_attribute_list($product_id) {
        $this->db->where('product_id', $product_id);
        $this->db->group_by('attribute_value_id');
        $query = $this->db->get('product_attribute');
        $atterarray = array();
        if ($query->num_rows() > 0) {
            $attrs = $query->result_array();
            foreach ($attrs as $key => $value) {
                $atterarray[$value['attribute_id']] = $value;
            }
            return $atterarray;
        } else {
            return array();
        }
    }

    function productAttributes($product_id) {
        $pquery = "SELECT pa.attribute, cav.attribute_value, cav.additional_value FROM product_attribute as pa
      join category_attribute_value as cav on cav.id = pa.attribute_value_id
      where pa.product_id = $product_id";
        $attr_products = $this->query_exe($pquery);
        return $attr_products;
    }

    function variant_product_attr($product_id) {
        $queryr = "SELECT pa.attribute_id, pa.attribute, pa.product_id, pa.attribute_value_id, cav.attribute_value FROM product_attribute as pa 
join category_attribute_value as cav on cav.id = pa.attribute_value_id 
where pa.product_id=$product_id ";
        $query = $this->db->query($queryr);
        return $query->result_array();
    }

    function category_attribute_list($id) {
        $this->db->where('attribute_id', $id);
        $this->db->group_by('attribute_value');
        $query = $this->db->get('category_attribute_value');
        if ($query->num_rows() > 0) {
            $attrs = $query->result_array();
            return $attrs;
        } else {
            return array();
        }
    }

    function category_items_prices_id($category_items_id) {

        $queryr = "SELECT cip.price, ci.item_name, cip.id FROM custome_items_price as cip
                       join custome_items as ci on ci.id = cip.item_id
                       where cip.category_items_id = $category_items_id";
        $query = $this->db->query($queryr);
        $category_items_price_array = $query->result();
        return $category_items_price_array;
    }

    function category_items_prices() {
        $query = $this->db->get('category_items');
        $category_items = $query->result();
        $category_items_return = array();
        foreach ($category_items as $citkey => $citvalue) {
            $category_items_id = $citvalue->id;
            $category_items_price_array = $this->category_items_prices_id($category_items_id);
            $citvalue->prices = $category_items_price_array;
            array_push($category_items_return, $citvalue);
        }
        return $category_items_return;
    }

///udpate after 16-02-2019
    function stringCategories($category_id) {
        $this->db->where('parent_id', $category_id);
        $query = $this->db->get('category');
        $category = $query->result_array();
        $container = "";
        foreach ($category as $ckey => $cvalue) {
            $container .= $this->stringCategories($cvalue['id']);
            $container .= ", " . $cvalue['id'];
        }
        return $container;
    }

    function getUserDetails($user_id) {
        $this->db->where('id', $user_id);
        $query = $this->db->get('app_user');
        $userarray = $query->row_array();
        return $userarray;
    }

    function getUserNotificaions($user_id) {
        $query = "SELECT id, sender, receiver, message, datetime, tablename from (
                 
                  SELECT id, sender, receiver, message, datetime, 'card_user_connection' as tablename  
                  FROM card_user_connection where CONNECTION = 'No' and receiver = $user_id
                  ) as notification order by datetime desc";
        $query = $this->db->query($query);
        $notification = $query->result_array();
        $notificationarray = [];
        foreach ($notification as $key => $value) {
            $sid = $value['sender'];
            $userdata = $this->getUserDetails($sid);
            $value['profile_image'] = "";
            $value['name'] = $userdata['name'];
            if ($value['tablename'] == 'card_user_connection') {
                $value['title'] = 'New Connect Request From ' . $userdata['name'];
            } else {
                $value['title'] = 'Message From ' . $userdata['name'];
            }
            array_push($notificationarray, $value);
        }
        return $notificationarray;
    }
    
    function checkUserConnection($user_s, $user_d, $card_id){
         $query = "
                  SELECT id, sender, receiver, message, datetime, connection FROM card_user_connection
                  where (sender = $user_s and receiver = $user_d and card_id=$card_id)
                  union
                  SELECT id, sender, receiver, message, datetime, connection FROM card_user_connection
                  where (receiver = $user_s and sender = $user_d and card_id=$card_id) 
                  ";
        $query = $this->db->query($query);
        $checkconnection = $query->row_array();
        return $checkconnection;
    }
    
    function checkUserConnectionCard($user_s){
         $query = "
             select card_id, id from (
                  SELECT card_id, id FROM card_user_connection
                  where sender = $user_s  and connection = 'Yes'
                  union
                  SELECT card_id, id FROM card_user_connection
                  where receiver = $user_s  and connection  = 'Yes'
                  ) as card_table where card_id not in (select id from card where user_id=$user_s) and card_id";
        $query = $this->db->query($query);
        $checkconnection = $query->result_array();
        return $checkconnection;
    }
    

}
