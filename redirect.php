<?php

global $wpdb;
global $postid;

$wpcf7 = WPCF7_ContactForm::get_current();
$submission = WPCF7_Submission::get_instance();
$user_email = '';
$user_mobile = '';
$description = '';
$user_price = '';

if ($submission) {
    $data = $submission->get_posted_data();
    $user_email = isset($data['user_email']) ? $data['user_email'] : "";
    $user_mobile = isset($data['user_mobile']) ? $data['user_mobile'] : "";
    $description = isset($data['description']) ? $data['description'] : "";
    $user_price = isset($data['user_price']) ? $data['user_price'] : "";
}

$price = get_post_meta($postid, "_cf7pp_price", true);
if ($price == "") {
    $price = $user_price;
}
$options = get_option('cf7pp_options');
foreach ($options as $k => $v) {
    $value[$k] = $v;
}
$active_gateway = 'IranDargah';
$MID = $value['gateway_merchantid'];
$url_return = $value['return'];

//$user_email;
// Set Data -> Table Trans_ContantForm7
$table_name = $wpdb->prefix . "cfIRD7_transaction";
$_x = array();
$_x['idform'] = $postid;
$_x['transid'] = ''; // create dynamic or id_get
$_x['gateway'] = $active_gateway; // name gateway
$_x['cost'] = $price;
$_x['created_at'] = time();
$_x['email'] = $user_email;
$_x['user_mobile'] = $user_mobile;
$_x['description'] = $description;
$_x['status'] = 'none';
$_y = array('%d', '%s', '%s', '%d', '%d', '%s', '%s', '%s', '%s');

if ($active_gateway == 'IranDargah') {

    $MerchantID = $MID; //Required
    $Amount = $price; //Amount will be based on Toman - Required
    $Description = $description; // Required
    $Email = $user_email; // Optional
    $Mobile = $user_mobile; // Optional
    $CallbackURL = get_site_url() . '/' . $url_return; // Required

    $client = new SoapClient('https://www.dargaah.com/wsdl', ['encoding' => 'UTF-8']);

    $result = $client->IRDPayment(
        [
            'merchantID' => $MerchantID,
            'amount' => $Amount,
            'description' => $Description,
            'mobile' => $Mobile,
            'callbackURL' => $CallbackURL,
        ]
    );

    if ($result->status == 200) {

        $_x['transid'] = $result->authority;

        $s = $wpdb->insert($table_name, $_x, $_y);

        Header('Location: https://www.dargaah.com/ird/startpay/' . $result->authority);

    } else {
        $tmp = 'خطایی رخ داده در اطلاعات پرداختی درگاه' . '<br>Error:' . $result->status . '<br> لطفا به مدیر اطلاع دهید <br><br>';
        $tmp .= '<a href="' . get_option('siteurl') . '" class="mrbtn_red" > بازگشت به سایت </a>';
        echo CreatePage_cf7('خطا در عملیات پرداخت', $tmp);
    }
}
