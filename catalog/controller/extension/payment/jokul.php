<?php
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
*/
class ControllerExtensionPaymentJokul extends Controller
{
    public $ip_range = "103.10.129.";
    public $paymentChannel = array(
        "jokul_va_mandiri" => array(
            "path" => "/mandiri-virtual-account/v2/payment-code",
            "name" => "Bank Mandiri VA"
        ), "jokul_va_syariah_indonesia" => array(
            "path" => "/bsm-virtual-account/v2/payment-code",
            "name" => "Bank Syariah Indonesia VA"
        ), "jokul_va_bca" => array(
            "path" => "/bca-virtual-account/v2/payment-code",
            "name" => "BCA VA"
        ), "jokul_va_bri" => array(
            "path" => "/bri-virtual-account/v2/payment-code",
            "name" => "BRI VA"
        ), "jokul_va_permata" => array(
            "path" => "/permata-virtual-account/v2/payment-code",
            "name" => "Bank Permata"
        ), "jokul_va_doku" => array(
            "path" => "/doku-virtual-account/v2/payment-code",
            "name" => "Other Banks (VA by DOKU)"
        ), "jokul_o2o_alfa" => array(
            "path" => "/alfa-online-to-offline/v2/payment-code",
            "name" => "Alfamart"
        ), "jokul_cc" => array(
            "path" => "/credit-card/v1/payment-page",
            "name" => "Credit Card"
        )

    );

    public function index()
    {
        $log = new Log('checkout.log');
        $this->language->load('extension/payment/jokul');
        $this->load->model('checkout/order');
        $log->write($this->model_checkout_order->getOrder($this->session->data));
        $order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);
        $serverconfig = $this->getUrlLocal();
        $data['urlLocal'] = $serverconfig . "/index.php?route=extension/payment/jokul/redirect";
        $data['urlSetProses'] = $serverconfig . "/index.php?route=extension/payment/jokul/processdoku";
        $data['button_confirm'] = $this->language->get('button_confirm');
        $data['invoice_number'] = $this->session->data['order_id'];
        $data['amount'] = $this->currency->format($order_info['total'], $order_info['currency_code'], false, false);
        return $this->load->view('extension/payment/doku', $data);
    }

    public function getUrlLocal()
    {
        $myserverpath = explode("/", $_SERVER['PHP_SELF']);
        if ($myserverpath[1] <> 'admin') {
            $serverpath = '/' . $myserverpath[1];
            for ($i = 2; $i < count($myserverpath) - 1; $i++) {
                $serverpath = $serverpath . '/' .  $myserverpath[$i];
            }
        } else {
            $serverpath = '';
        }
        if ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443) {
            $myserverprotocol = "https";
        } else {
            $myserverprotocol = "http";
        }

        if (!in_array($_SERVER['SERVER_PORT'], [80, 443])) {
            $port = ":$_SERVER[SERVER_PORT]";
        } else {
            $port = '';
        }

        $myservername = $_SERVER['SERVER_NAME'] . $port . $serverpath;
        return $myserverprotocol . '://' . $myservername;
    }

    public function paymentsuccesscc()
    {
        $this->load->model('checkout/order');
        $order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);
        $amount = number_format($this->currency->format($order_info['total'], $order_info['currency_code'], false, false), 2, '.', '');
        $invoiceNumber = $this->session->data['order_id'];
        $this->showPageCcSuccess($invoiceNumber, $amount);
    }

    public function paymentfailedcc()
    {
        $this->load->model('checkout/order');
        $order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);
        $amount = number_format($this->currency->format($order_info['total'], $order_info['currency_code'], false, false), 2, '.', '');
        $invoiceNumber = $this->session->data['order_id'];
        $this->showPageCcFail($invoiceNumber, $amount);
    }

    public function processdoku()
    {
        if (isset($this->request->post['invoice_number'])) {
            $this->load->model('checkout/order');
        } else {
            $this->doku_log("Invalid Request - IP Logged " . $this->getipaddress());
        }
    }
    public function getipaddress()
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        return $ip;
    }

    public function add_dokuonecheckout($datainsert)
    {
        $SQL = "";
        foreach ($datainsert as $field_name => $field_data) {
            $SQL .= " $field_name = '$field_data',";
        }
        $SQL = substr($SQL, 0, -1);
        $this->db->query("INSERT INTO " . DB_PREFIX . "jokul SET $SQL");
    }

    public function redirect()
    {
        $baseUrl = 'https://api-sandbox.doku.com';
        if ($this->config->get('payment_jokul_server_set') == "1") {
            $baseUrl = 'https://api.doku.com/';
        }

        $this->load->model('checkout/order');
        $order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);
        $paymentchannel = $order_info['payment_code'];
        $data['acquirer'] = '0';

        $this->load->model('account/order');
        $products = $this->model_account_order->getOrderProducts($this->session->data['order_id']);
        $products = $this->cart->getProducts();
        $data['list_product'] = $products;

        if ($this->customer->isLogged()) {
            $data['customer_id'] = $this->customer->getId();
            $data['email'] = $this->customer->getEmail();
            $data['telephone'] = $this->customer->getTelephone();
            $this->load->model('account/address');
            $address = $this->model_account_address->getAddress($this->customer->getAddressId());
            $data['payment_address'] = $address['address_1'] . " " . $address['address_2'];
            $trx_data = $this->model_account_address->getAddress($this->session->data['payment_address']['address_id']);
        } elseif (isset($this->session->data['guest'])) {
            $data['customer_id'] = '';
            $data['payment_address'] = $this->session->data['payment_address']['address_1'] . " " . $this->session->data['payment_address']['address_2'];
            $data['email'] = $this->session->data['guest']['email'];
            $data['telephone'] = $this->session->data['guest']['telephone'];
            $trx_data = $this->session->data['payment_address'];
        }
        $data['client_id'] = $this->config->get('payment_jokul_mallid');
        $data['sharedkey'] = $this->config->get('payment_jokul_shared');
        $data['amount'] = number_format($this->currency->format($order_info['total'], $order_info['currency_code'], false, false), 2, '.', '');
        $data['button_confirm'] = $this->language->get('button_confirm');
        $data['invoice_number'] = $this->session->data['order_id'];
        $data['customer_name'] = $trx_data['firstname'] . ' ' . $trx_data['lastname'];
        $data['paymentchannel'] = $paymentchannel;
        $data['data_product'] = 'Transaction Invoice Number ' . $data['invoice_number'] . ',' . $data['amount'] . ',1,' . $data['amount'] . ';';
        $trx['ip_address'] = $this->getipaddress();
        $trx['process_datetime'] = date("Y-m-d H:i:s");
        $trx['process_type'] = 'REQUEST';
        $trx['invoice_number'] = $data['invoice_number'];
        $trx['amount'] = $data['amount'];
        $trx['payment_channel'] = $paymentchannel;
        $trx['message'] = "Transaction request start";
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['column_right'] = $this->load->controller('common/column_right');
        $data['content_top'] = $this->load->controller('common/content_top');
        $data['content_bottom'] = $this->load->controller('common/content_bottom');
        $data['footer'] = $this->load->controller('common/footer');
        $data['header'] = $this->load->controller('common/header');
        $data['reusable_status'] = 'false';
        $data['expired_time'] = 60;

        $data['footer_message'] = $this->config->get('payment_jokul_o2o_alfa_footer_message') != null  ? $this->config->get('payment_jokul_o2o_alfa_footer_message') : "";
        $data['language'] = $this->config->get('payment_jokul_cc_language') != null  ? $this->config->get('payment_jokul_cc_language') : "";
        $data['bg_color'] = $this->config->get('payment_jokul_cc_bg_color') != null  ? $this->config->get('payment_jokul_cc_bg_color') : "";
        $data['font_color'] = $this->config->get('payment_jokul_cc_font_color') != null  ? $this->config->get('payment_jokul_cc_font_color') : "";
        $data['button_bg_color'] = $this->config->get('payment_jokul_cc_button_bg_color') != null  ? $this->config->get('payment_jokul_cc_button_bg_color') : "";
        $data['button_font_color'] = $this->config->get('payment_jokul_cc_button_font_color') != null  ? $this->config->get('payment_jokul_cc_button_font_color') : "";

        $this->add_dokuonecheckout($trx);

        $requestTimestamp = date("Y-m-d H:i:s");
        $requestTimestamp = date(DATE_ISO8601, strtotime($requestTimestamp));

        $signatureParams = array(
            "clientId" => $data['client_id'],
            "key" => $data['sharedkey'],
            "requestTarget" => $this->paymentChannel[$paymentchannel]['path'],
            "requestId" => $this->guidv4(),
            "requestTimestamp" => substr($requestTimestamp, 0, 19) . "Z"
        );

        $url = $baseUrl . $this->paymentChannel[$paymentchannel]['path'];

        $response = $this->post($url, $data, $signatureParams);

        if (strpos($data['paymentchannel'], "va") !== false) {
            $this->handleResponse($response, $paymentchannel, $data, $trx);
        } else if (strpos($data['paymentchannel'], "o2o") !== false) {
            $this->handleO2OResponse($response, $paymentchannel, $data, $trx);
        } else {
            $this->handleCcResponse($response, $paymentchannel, $data, $trx);
        }
    }

    public function notify()
    {
        $rawbody = urldecode(file_get_contents('php://input'));
        $postData = json_decode($rawbody, true);
        $serviceId = $postData['service']['id'];
        $sharedKey = $this->config->get('payment_jokul_shared');

        $myserverpath = explode("/", $_SERVER['PHP_SELF']);
        $serverpath = '/' . $myserverpath[1];
        for ($i = 2; $i < count($myserverpath) - 1; $i++) {
            $serverpath = $serverpath . '/' .  $myserverpath[$i];
        }

        $headers = getallheaders();
        $signatureParams = array(
            "clientId" => $headers["Client-Id"],
            "key" => $sharedKey,
            "requestTarget" => $serverpath . '/index.php',
            "requestId" => $headers['Request-Id'],
            "requestTimestamp" => $headers['Request-Timestamp']
        );

        $signature = $this->doCreateNotifySignature($signatureParams, $rawbody);

        $this->doku_log("Notify Request Body: " . print_r($postData, true), $postData["order"]["invoice_number"]);
        $this->doku_log("Request Target: " . $serverpath . '/index.php?route=extension/payment/jokul/notify', $postData["order"]["invoice_number"]);
        if (empty($postData)) {
            $this->doku_log("Invalid Request Body format - IP Logged " . $this->getipaddress(), $postData["order"]["invoice_number"]);
            http_response_code(400);
            die;
        }

        $trx['amount'] = $postData["order"]["amount"];
        $trx['process_type'] = 'NOTIFY';
        $trx['invoice_number'] = $postData["order"]["invoice_number"];

        if ($postData['service']['id'] === "ONLINE_TO_OFFLINE") {
            $trx['payment_code'] = $postData["online_to_offline_info"]["payment_code"];
            $trx['doku_payment_datetime'] = $postData["transaction"]["date"];
        } else if ($postData['service']['id'] === "VIRTUAL_ACCOUNT") {
            $trx['payment_code'] = $postData["virtual_account_info"]["virtual_account_number"];
            $trx['doku_payment_datetime'] = $postData["virtual_account_payment"]["date"];
        }

        $trx['notify_type'] = "P"; //change if reverse is used

        if ($headers['Signature'] != $signature) {
            $this->doku_log("Jokul Notification Invalid Signature - IP Logged " . $this->getipaddress(), $postData["order"]["invoice_number"]);
            http_response_code(401);
            die;
        }

        if ($postData["transaction"]["status"] == "SUCCESS") {
            $result = $this->checkTrx($trx, '', '', $serviceId);
            if ($result < 1) {
                $this->doku_log("Cannot find the transaction - IP Logged " . $this->getipaddress(), $postData["order"]["invoice_number"]);
                http_response_code(404);
                die;
            } else {
                $trx['result_msg'] = 'SUCCESS';
                $this->load->model('checkout/order');
                $this->cart->clear();
                $this->model_checkout_order->addOrderHistory($trx['invoice_number'], 2, 'PROCESSING', true);
                $result = $this->checkTrxStatus($trx, '', $serviceId);
                if ($result < 1) {
                    $this->add_dokuonecheckout($trx);
                }
                http_response_code(200);
            }
        } else {
            $this->doku_log("Cannot find the transaction - IP Logged " . $this->getipaddress(), $postData["order"]["invoice_number"]);
            $this->load->model('checkout/order');
            $trx['result_msg'] = 'FAILED';
            $this->model_checkout_order->addOrderHistory($trx['invoice_number'], 10, 'FAILED', true);
            $resultFailed = $this->checkTrxStatus($trx, '', $serviceId);
            if ($resultFailed < 1) {
                $this->add_dokuonecheckout($trx);
            }
            http_response_code(404);
            die;
        }
    }

    public function checkTrx($trx, $process, $result_msg = '', $serviceId)
    {
        if ($result_msg == "PENDING") {
            return 0;
        }
        $check_result_msg = "";
        if (!empty($result_msg)) {
            $check_result_msg = " AND result_msg = '$result_msg'";
        }
        if (strtolower($serviceId) == strtolower('CREDIT_CARD')) {
            $process = 'PENDING_CC';
            $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "jokul" . " WHERE process_type = '$process'" . $check_result_msg . " AND invoice_number = '" . $trx['invoice_number'] . "'" . " AND amount = '" . $trx['amount'] . "'");
            return $query->num_rows;
        } else {
            $process = 'PENDING_MH_VA';
            $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "jokul" . " WHERE process_type = '$process'" . $check_result_msg . " AND invoice_number = '" . $trx['invoice_number'] . "'" . " AND amount = '" . $trx['amount'] . "'" . " AND payment_code = '" . $trx['payment_code'] . "'");
            return $query->num_rows;
        }
    }

    public function checkTrxStatus($trx, $process, $serviceId)
    {
        $process = 'NOTIFY';
        if (strtolower($serviceId) == strtolower('CREDIT_CARD')) {
            $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "jokul" . " WHERE process_type = '$process'" . " AND invoice_number = '" . $trx['invoice_number'] . "'" . " AND amount = '" . $trx['amount'] . "'");
            return $query->num_rows;
        } else {
            $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "jokul" . " WHERE process_type = '$process'" . " AND invoice_number = '" . $trx['invoice_number'] . "'" . " AND amount = '" . $trx['amount'] . "'" . " AND payment_code = '" . $trx['payment_code'] . "'");
            return $query->num_rows;
        }
    }

    public function post($url, $rawData, $signatureParams)
    {
        if (strpos($rawData['paymentchannel'], "va") !== false) {
            $bodyJson = $this->preparePaymentData($rawData);
        } else if (strpos($rawData['paymentchannel'], "cc") !== false) {
            $bodyJson = $this->preparePaymentDataCc($rawData);
        } else {
            $bodyJson = $this->prepareO2OPaymentData($rawData);
        }

        $signature = $this->doCreateRequestSignature($signatureParams, $bodyJson);
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($bodyJson));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Signature:' . $signature,
            'Request-Id:' . $signatureParams['requestId'],
            'Client-Id:' . $signatureParams['clientId'],
            'Request-Timestamp:' . $signatureParams['requestTimestamp']
        ));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $responseJson = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $this->doku_log("URL " . $url, $rawData['invoice_number']);
        $this->doku_log("Request  " . json_encode($bodyJson),  $rawData['invoice_number']);

        if (is_string($responseJson)) {
            $responsePayment = json_decode($responseJson, false);
        } else {
            $responsePayment = $responseJson;
        }

        $response = array('httpCode' => $httpcode, 'responsePayment' => $responsePayment);
        $this->doku_log("RESPONSE " . print_r($response, true),  $rawData['invoice_number']);
        return $response;
    }

    public function get($url)
    {
        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $responseJson = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if (is_string($responseJson)) {
            $responsePayment = json_decode($responseJson, false);
        } else {
            $responsePayment = $responseJson;
        }

        $response = array('httpCode' => $httpcode, 'responsePayment' => $responsePayment);
        return $responsePayment;
    }

    public function preparePaymentDataCc($data)
    {
        $itemQty = array();
        $dataCustomer = array();
        foreach ($this->cart->getProducts() as $result) {
            $itemQty[] = array('name' => $result['name'], 'price' => $result['price'], 'quantity' => $result['quantity']);
        }

        $myserverpath = explode("/", $_SERVER['PHP_SELF']);
        if ($myserverpath[1] <> 'admin') {
            $serverpath = '/' . $myserverpath[1];
            for ($i = 2; $i < count($myserverpath) - 2; $i++) {
                $serverpath = $serverpath . '/' .  $myserverpath[$i];
            }
        } else {
            $serverpath = '';
        }

        if ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443) {
            $myserverprotocol = "https";
        } else {
            $myserverprotocol = "http";
        }

        if (!in_array($_SERVER['SERVER_PORT'], [80, 443])) {
            $port = ":$_SERVER[SERVER_PORT]";
        } else {
            $port = '';
        }
        $myservername = $_SERVER['SERVER_NAME'] . $port . $serverpath;

        if ($this->customer->isLogged()) {
            $dataCustomer = array('id' => $data['customer_id'], 'name' => $data['customer_name'], 'email' => $data['email'], 'phone' => $data['telephone'], 'country' => 'ID', 'address' => $data['payment_address']);
        } elseif (isset($this->session->data['guest'])) {
            $dataCustomer = array('name' => $data['customer_name'], 'email' => $data['email'], 'phone' => $data['telephone'], 'country' => 'ID', 'address' => $data['payment_address']);
        }

        return array(
            "customer" => $dataCustomer,
            "order" => array(
                "invoice_number" => $data['invoice_number'],
                "line_items" => $itemQty,
                "amount" => round($data['amount']),
                "failed_url" => $myserverprotocol . '://' . $myservername . "/index.php?route=extension/payment/jokul/paymentfailedcc",
                "callback_url" => $myserverprotocol . '://' . $myservername . "/index.php?route=extension/payment/jokul/paymentsuccesscc",
                "auto_redirect" => true
            ),
            "override_configuration" => array(
                "themes" => array(
                    "language" => $data['language'],
                    "background_color" => $data['bg_color'],
                    "font_color" => $data['font_color'],
                    "button_background_color" => $data['button_bg_color'],
                    "button_font_color" => $data['button_font_color'],
                )
            ),
            "additional_info" => array(
                "integration" => array(
                    "name" => "opencart-plugin",
                    "version" => "2.0.1"
                )
            )
        );
    }

    public function preparePaymentData($data)
    {
        return array(
            "order" => array(
                "invoice_number" => $data['invoice_number'],
                "amount" => round($data['amount'])
            ),
            "virtual_account_info" => array(
                "expired_time" => $data['expired_time'],
                "reusable_status" => $data['reusable_status']
            ),
            "customer" => array(
                "name" => $data['customer_name'],
                "email" => $data['email']
            ),
            "additional_info" => array(
                "integration" => array(
                    "name" => "opencart-plugin",
                    "version" => "2.0.1"
                )
            )
        );
    }

    public function prepareO2OPaymentData($data)
    {
        return array(
            "order" => array(
                "invoice_number" => $data['invoice_number'],
                "amount" => round($data['amount'])
            ),
            "online_to_offline_info" => array(
                "expired_time" => $data['expired_time'],
                "reusable_status" => false,
                "info1" => ''
            ),
            "alfa_info" => array(
                "receipt" => array(
                    "footer_message" => $data['footer_message']
                )
            ),
            "customer" => array(
                "name" => $data['customer_name'],
                "email" => $data['email']
            ),
            "additional_info" => array(
                "integration" => array(
                    "name" => "opencart-plugin",
                    "version" => "2.0.1"
                )
            )
        );
    }

    public function doCreateRequestSignature($params, $body)
    {
        $body = str_replace(array("\r", "\n"), array("\\r", "\\n"), json_encode($body));
        return $this->doEncrypt($params, $body);
    }

    public function doCreateNotifySignature($params, $body)
    {
        return $this->doEncrypt($params, $body);
    }

    private function doEncrypt($params, $body)
    {
        $digest = base64_encode(hash("sha256", $body, True));
        $signatureComponent = "Client-Id:" . $params['clientId'] . "\n" .
            "Request-Id:" . $params['requestId'] . "\n" .
            "Request-Timestamp:" . $params['requestTimestamp'] . "\n" .
            "Request-Target:" . $params['requestTarget'] . "\n" .
            "Digest:" . htmlspecialchars_decode($digest);

        $signature = base64_encode(hash_hmac('SHA256', htmlspecialchars_decode($signatureComponent), htmlspecialchars_decode($params['key']), True));
        return "HMACSHA256=" . $signature;
    }

    public function handleResponse($response, $paymentchannel, $data, $trx)
    {
        $responsePayment = $response['responsePayment'];
        $httpcode = $response['httpCode'];

        if (isset($responsePayment->virtual_account_info->virtual_account_number) && $httpcode == 200) {
            $data['channel_name'] = $this->paymentChannel[$paymentchannel]['name'];
            $trx['status_code'] = $httpcode;
            $trx['response_code'] = $httpcode;
            $trx['payment_channel'] = $data['paymentchannel'];
            $trx['doku_payment_datetime'] = date("Y-m-d H:i:s");
            $trx['result_msg'] = 'PENDING';
            $trx['process_datetime'] = date("Y-m-d H:i:s");
            $trx['payment_code'] = $responsePayment->virtual_account_info->virtual_account_number;
            $trx['process_type'] = 'PENDING_MH_VA';
            $data['payment_code'] = $trx['payment_code'];
            $trx['message'] = $this->fetchEmailTemplate($responsePayment->virtual_account_info->how_to_pay_api);
            $data['return_message'] = "This is your Payment Code : " . $trx['payment_code'] . "<br>Please do the payment before expired.<br>If you need help for payment, please contact our customer service.<br>";
            $data['expiry_date'] = date_format(date_create($responsePayment->virtual_account_info->expired_date), "d F Y, H:i");
            $data['how_to_pay_page'] = $responsePayment->virtual_account_info->how_to_pay_page;
            $this->model_checkout_order->addOrderHistory($trx['invoice_number'], 1, $trx['message'], true);
            $this->add_dokuonecheckout($trx);
            $this->cart->clear();
            $this->response->setOutput($this->load->view('extension/payment/doku_pending_merchant_hosted', $data));
        } else {
            $this->showCheckoutFailedPage();
        }
    }

    public function handleO2OResponse($response, $paymentchannel, $data, $trx)
    {
        $responsePayment = $response['responsePayment'];
        $httpcode = $response['httpCode'];

        if (isset($responsePayment->online_to_offline_info->payment_code) && $httpcode == 200) {
            $data['channel_name'] = $this->paymentChannel[$paymentchannel]['name'];
            $trx['status_code'] = $httpcode;
            $trx['response_code'] = $httpcode;
            $trx['payment_channel'] = $data['paymentchannel'];
            $trx['doku_payment_datetime'] = date("Y-m-d H:i:s");
            $trx['result_msg'] = 'PENDING';
            $trx['process_datetime'] = date("Y-m-d H:i:s");
            $trx['payment_code'] = $responsePayment->online_to_offline_info->payment_code;
            $trx['process_type'] = 'PENDING_MH_VA';
            $data['payment_code'] = $trx['payment_code'];
            // Email Instruction
            $trx['message'] = $this->fetchEmailTemplate($responsePayment->online_to_offline_info->how_to_pay_api);
            $data['return_message'] = "This is your Payment Code : " . $trx['payment_code'] . "<br>Please do the payment before expired.<br>If you need help for payment, please contact our customer service.<br>";
            $data['expiry_date'] = date_format(date_create($responsePayment->online_to_offline_info->expired_date), "d F Y, H:i");
            $data['how_to_pay_page'] = $responsePayment->online_to_offline_info->how_to_pay_page;
            $this->model_checkout_order->addOrderHistory($trx['invoice_number'], 1, $trx['message'], true);
            $this->add_dokuonecheckout($trx);
            $this->cart->clear();
            $this->response->setOutput($this->load->view('extension/payment/doku_pending_merchant_hosted', $data));
        } else {
            $this->showCheckoutFailedPage();
        }
    }

    public function handleCcResponse($response, $paymentchannel, $data, $trx)
    {
        $responsePayment = $response['responsePayment'];

        $httpcode = $response['httpCode'];

        if ($httpcode == 200) {
            $data['channel_name'] = $this->paymentChannel[$paymentchannel]['name'];
            $trx['status_code'] = $httpcode;
            $trx['response_code'] = $httpcode;
            $trx['payment_channel'] = $data['paymentchannel'];
            $trx['doku_payment_datetime'] = date("Y-m-d H:i:s");
            $trx['result_msg'] = 'PENDING';
            $trx['process_datetime'] = date("Y-m-d H:i:s");
            $trx['payment_code'] = '';
            $trx['process_type'] = 'PENDING_CC';
            $trx['message'] = 'success';
            $this->model_checkout_order->addOrderHistory($trx['invoice_number'], 1, $trx['message'], true);
            $this->add_dokuonecheckout($trx);
            $this->showCcForm($responsePayment->credit_card_payment_page->url, $responsePayment);
        } else {
            $this->showCheckoutFailedPage();
        }
    }

    public function showCcForm($urlCc, $responsePayment)
    {
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['column_right'] = $this->load->controller('common/column_right');
        $data['content_top'] = $this->load->controller('common/content_top');
        $data['content_bottom'] = $this->load->controller('common/content_bottom');
        $data['footer'] = $this->load->controller('common/footer');
        $data['header'] = $this->load->controller('common/header');
        $data['ccUrl'] = $urlCc;
        $this->response->setOutput($this->load->view('extension/payment/doku_cc', $data));
    }

    public function showPageCcSuccess($invoiceNumber, $amount)
    {
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['column_right'] = $this->load->controller('common/column_right');
        $data['content_top'] = $this->load->controller('common/content_top');
        $data['content_bottom'] = $this->load->controller('common/content_bottom');
        $data['footer'] = $this->load->controller('common/footer');
        $data['header'] = $this->load->controller('common/header');
        $data['heading_title'] = "Your Transaction with Credit Card is Success";
        $data['channel_name'] = "Jokul Credit Card";
        $data['amount'] = $amount;
        $data['invoice_number'] = $invoiceNumber;
        $this->cart->clear();
        $this->response->setOutput($this->load->view('extension/payment/doku_cc_result', $data));
    }

    public function showPageCcFail($invoiceNumber, $amount)
    {
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['column_right'] = $this->load->controller('common/column_right');
        $data['content_top'] = $this->load->controller('common/content_top');
        $data['content_bottom'] = $this->load->controller('common/content_bottom');
        $data['footer'] = $this->load->controller('common/footer');
        $data['header'] = $this->load->controller('common/header');
        $data['heading_title'] = "Your Payment with Credit Card is Failed";
        $data['channel_name'] = "Jokul Credit Card";
        $data['amount'] = $amount;
        $data['invoice_number'] = $invoiceNumber;
        $this->response->setOutput($this->load->view('extension/payment/doku_cc_result', $data));
    }

    public function showCheckoutFailedPage()
    {
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['column_right'] = $this->load->controller('common/column_right');
        $data['content_top'] = $this->load->controller('common/content_top');
        $data['content_bottom'] = $this->load->controller('common/content_bottom');
        $data['footer'] = $this->load->controller('common/footer');
        $data['header'] = $this->load->controller('common/header');
        $data['heading_title'] = "Checkout Failed";
        $data['failed_info'] = "We are sorry for the inconvenience, please try again.";
        $this->response->setOutput($this->load->view('extension/payment/doku_result', $data));
    }

    public function fetchEmailTemplate($urlHowtopay)
    {
        $response = $this->get($urlHowtopay);
        $paymentInstruction = $response->payment_instruction;
        $outputStep = '';
        foreach ($paymentInstruction as $value) {

            $stepIndex = 1;
            $outputStep .= $value->channel . "\n" . "\n";
            foreach ($value->step as $step) {

                $outputStep .= $stepIndex . ". " . $step . "\n";
                $stepIndex++;
            }
            $outputStep .= "\n";
        }

        return "<b>Cara Pembayaran</b> \n" . $outputStep;
    }

    function guidv4($data = null)
    {
        $data = $data ?? random_bytes(16);

        $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // set version to 0100
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // set bits 6-7 to 10

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    function doku_log($log_msg, $invoiceNumber = "")
    {
        $log_filename = "doku_log";
        $log_header = date(DATE_ATOM, time()) . ' ' . get_class($this) . '---> ' . $invoiceNumber . ' : ';
        if (!file_exists($log_filename)) {
            // create directory/folder uploads.
            mkdir($log_filename, 0777, true);
        }
        $log_file_data = $log_filename . '/log_' . date('d-M-Y') . '.log';
        // if you don't add `FILE_APPEND`, the file will be erased each time you add a log
        file_put_contents($log_file_data, $log_header . $log_msg . "\n", FILE_APPEND);
    }
}
