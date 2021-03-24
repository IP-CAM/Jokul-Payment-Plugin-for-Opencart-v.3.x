<?php
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
*/
class ControllerExtensionPaymentDOKU extends Controller
{
    public $ip_range = "103.10.129.";

    public function index()
    {
        $log = new Log('checkout.log');
        $this->language->load('extension/payment/doku');
        $this->load->model('checkout/order');
        $log->write($this->model_checkout_order->getOrder($this->session->data));
        $order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);
        $serverconfig = $this->getUrlLocal();
        $data['urlLocal'] = $serverconfig . "/index.php?route=extension/payment/doku/redirect";
        $data['urlSetProses'] = $serverconfig . "/index.php?route=extension/payment/doku/processdoku";
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

        $myservername = $_SERVER['SERVER_NAME'].$port . $serverpath;
        return $myserverprotocol . '://' . $myservername;
    }

    public function processdoku()
    {
        if (isset($this->request->post['invoice_number'])) {
            $invoice_number = $this->request->post['invoice_number'];
            $this->load->model('checkout/order');
//            $this->model_checkout_order->addOrderHistory($invoice_number, $this->config->get('payment_doku_companyid'), 'DOKU Payment Initiate', true);
        } else {
            echo "Stop : Access Not Valid";
            $this->log->write("DOKU Process Not in Correct Format - IP Logged " . $this->getipaddress());
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
        $this->db->query("INSERT INTO " . DB_PREFIX . "doku SET $SQL");
    }

    public function redirect()
    {
        $baseUrl = 'https://api-sandbox.doku.com';
        if ($this->config->get('payment_doku_server_set') == "1") {
            $baseUrl = 'https://api.doku.com/';
        }

        $this->load->model('checkout/order');
        $order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);

        $paymentchannel = $order_info['payment_code'];
        $data['acquirer'] = '0';

        if ($this->customer->isLogged()) {
            $data['email'] = $this->customer->getEmail();
            $data['telephone'] = $this->customer->getTelephone();
            $this->load->model('account/address');
            $trx_data = $this->model_account_address->getAddress($this->session->data['payment_address']['address_id']);
        } elseif (isset($this->session->data['guest'])) {
            $data['email'] = $this->session->data['guest']['email'];
            $data['telephone'] = $this->session->data['guest']['telephone'];
            $trx_data = $this->session->data['payment_address'];
        }
        $data['client_id'] = $this->config->get('payment_doku_mallid');
        $data['sharedkey'] = $this->config->get('payment_doku_shared');
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
        $this->add_dokuonecheckout($trx);

        $requestTimestamp = date("Y-m-d H:i:s");
        $requestTimestamp = date(DATE_ISO8601, strtotime($requestTimestamp));

        $requestTarget = "";
        if ($paymentchannel == 'jokul_va_mandiri') {
            $requestTarget = "/mandiri-virtual-account/v2/payment-code";
        } elseif ($paymentchannel == 'jokul_va_syariah_indonesia') {
            $requestTarget = "/bsm-virtual-account/v2/payment-code";
        } elseif ($paymentchannel == 'jokul_va_doku') {
            $requestTarget = "/doku-virtual-account/v2/payment-code";
        } elseif ($paymentchannel == 'jokul_va_bca') {
            $requestTarget = "/bca-virtual-account/v2/payment-code";
        } elseif ($paymentchannel == 'jokul_va_permata') {
            $requestTarget = "/permata-virtual-account/v2/payment-code";
        }

        $signatureParams = array(
            "clientId" => $data['client_id'],
            "key" => $data['sharedkey'],
            "requestTarget" => $requestTarget,
            "requestId" => $this->guidv4(),
            "requestTimestamp" => substr($requestTimestamp, 0, 19) . "Z"
        );

        $url = $baseUrl . $requestTarget;

        $response = $this->post($url, $data, $signatureParams);
        $this->handleResponse($response, $paymentchannel, $data, $trx);
    }

    public function notify()
    {
        $rawbody = urldecode(file_get_contents('php://input'));
        $postData = json_decode($rawbody, true);
        $sharedKey = $this->config->get('payment_doku_shared');

        $myserverpath = explode("/", $_SERVER['PHP_SELF']);
        $serverpath = '/' . $myserverpath[1];
        for ($i = 2; $i < count($myserverpath) - 1; $i++) {
            $serverpath = $serverpath . '/' .  $myserverpath[$i];
        }

        $headers = getallheaders();
        $signatureParams = array(
            "clientId" => $headers["Client-Id"],
            "key" => $sharedKey,
            "requestTarget" => $serverpath . '/index.php?route=extension/payment/doku/notify',
            "requestId" => $headers['Request-Id'],
            "requestTimestamp" => $headers['Request-Timestamp']
        );

        $signature = $this->doCreateNotifySignature($signatureParams, $rawbody);

        $this->log->write("Parameter used " . print_r($postData, true));
        $this->log->write("Request target " . $serverpath . '/index.php?route=extension/payment/doku/notify');
        if (empty($postData)) {
            $this->log->write("DOKU Notify Not in Correct Format - IP Logged " . $this->getipaddress());
            http_response_code(400);
            die;
        }

        $trx['amount'] = $postData["order"]["amount"];
        $trx['process_type'] = 'NOTIFY';
        $trx['invoice_number'] = $postData["order"]["invoice_number"];
        $trx['payment_code'] = $postData["virtual_account_info"]["virtual_account_number"];
        $trx['doku_payment_datetime'] = $postData["virtual_account_payment"]["date"];
        $trx['notify_type'] = "P"; //change if reverse is used

        if ($headers['Signature'] != $signature) {
            $this->log->write("WORDS " . $signature);
            $this->log->write("DOKU Notify Invalid Signature - IP Logged " . $this->getipaddress());
            http_response_code(400);
            die;
        }

        if ($postData["transaction"]["status"] == "SUCCESS") {
            $result = $this->checkTrx($trx);
            if ($result < 1) {
                echo "Stop : Transaction Not Found 0";
                $this->log->write("DOKU Notify Cannot Find Transactions - IP Logged " . $this->getipaddress());
                http_response_code(400);
                die;
            } else {
                $trx['result_msg'] = 'SUCCESS';
                $this->load->model('checkout/order');
                $this->cart->clear();
                $this->model_checkout_order->addOrderHistory($trx['invoice_number'], 2, 'PROCESSING', true);
                $result = $this->checkTrxStatus($trx);
                if ($result < 1) {
                    $this->add_dokuonecheckout($trx);
                }
                http_response_code(200);
            }
        } else {
            echo "Stop : Transaction Not Found 1";
            $this->log->write("DOKU Notify Cannot Find Transactions - IP Logged " . $this->getipaddress());
            $this->load->model('checkout/order');
            $trx['result_msg'] = 'FAILED';
            $this->model_checkout_order->addOrderHistory($trx['invoice_number'], 10, 'FAILED', true);
                $resultFailed = $this->checkTrxStatus($trx);
                if ($resultFailed < 1) {
                    $this->add_dokuonecheckout($trx);
                }
            http_response_code(400);
            die;
        }
    }

    public function checkTrx($trx, $process = 'PENDING_MH_VA', $result_msg = '')
    {
        if ($result_msg == "PENDING") {
            return 0;
        }
        $check_result_msg = "";
        if (!empty($result_msg)) {
            $check_result_msg = " AND result_msg = '$result_msg'";
        }
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "doku" . " WHERE process_type = '$process'" . $check_result_msg . " AND invoice_number = '" . $trx['invoice_number'] . "'" . " AND amount = '" . $trx['amount'] . "'" . " AND payment_code = '" . $trx['payment_code'] . "'");
        return $query->num_rows;
    }

    public function checkTrxStatus($trx, $process = 'NOTIFY')
    {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "doku" . " WHERE process_type = '$process'" . " AND invoice_number = '" . $trx['invoice_number'] . "'" . " AND amount = '" . $trx['amount'] . "'" . " AND payment_code = '" . $trx['payment_code'] . "'");
        return $query->num_rows;
    }

    public function generateCheckSum($data)
    {
        return hash(
            'sha256',
            $data['client_id'] .
                $data['email'] .
                $data['customer_name'] .
                round($data['amount']) .
                $data['invoice_number'] .
                $data['expired_time'] .
                $data['reusable_status'] .
                htmlspecialchars_decode($data['sharedkey'])
        );
    }

    public function post($url, $rawData, $signatureParams)
    {
        $bodyJson = $this->preparePaymentData($rawData);
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
        $this->log->write("URL EXEC " . $ch);
        $this->log->write("URL " . $url);
        $this->log->write("Request  " . json_encode($bodyJson));
        if (is_string($responseJson)) {
            $responsePayment = json_decode($responseJson, false);
        } else {
            $responsePayment = $responseJson;
        }

        $response = array('httpCode' => $httpcode, 'responsePayment' => $responsePayment);
        $this->log->write("RESPONSE " . print_r($response, true));
        return $response;
    }

    public function get($url)
    {
        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $responseJson = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $this->log->write("URL EXEC " . $ch);
        $this->log->write("URL " . $url);

        if (is_string($responseJson)) {
            $responsePayment = json_decode($responseJson, false);
        } else {
            $responsePayment = $responseJson;
        }

        $response = array('httpCode' => $httpcode, 'responsePayment' => $responsePayment);
        $this->log->write("RESPONSE " . print_r($response, true));
        return $responsePayment;
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
        $channelName = '';

        if ($paymentchannel == 'jokul_va_mandiri') {
            $channelName = 'Mandiri Virtual Account';
        } else if ($paymentchannel == 'jokul_va_syariah_indonesia') {
            $channelName = 'Syariah Indonesia Virtual Account';
        } else if ($paymentchannel == 'jokul_va_bca') {
            $channelName = 'BCA Virtual Account';
        } else if ($paymentchannel == 'jokul_va_permata') {
            $channelName = 'Permata Virtual Account';
        } else if ($paymentchannel == 'jokul_va_doku') {
            $channelName = 'Other ATMs (VA by DOKU)';
        }

        if (isset($responsePayment->virtual_account_info->virtual_account_number) && $httpcode == 200) {
            $data['channel_name'] = $channelName;
            $trx['status_code'] = $httpcode;
            $trx['response_code'] = $httpcode;
            $trx['payment_channel'] = $data['paymentchannel'];
            $trx['doku_payment_datetime'] = date("Y-m-d H:i:s");
            $trx['result_msg'] = 'PENDING';
            $trx['process_datetime'] = date("Y-m-d H:i:s");
            $trx['payment_code'] = $responsePayment->virtual_account_info->virtual_account_number;
            $trx['process_type'] = 'PENDING_MH_VA';
            $data['payment_code'] = $trx['payment_code'];
            // Email Instruction
            $trx['message'] = $this->fetchEmailTemplate($responsePayment, $data);
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

    public function fetchEmailTemplate($responsePayment, $data)
    {
        $urlHowtopay = $responsePayment->virtual_account_info->how_to_pay_api;
        $response = $this->get($urlHowtopay);
        $paymentInstruction = $response->payment_instruction;
        $outputStep='';
        foreach ($paymentInstruction as $value){

            $stepIndex=1;
            $outputStep.= $value->channel."\n"."\n";
            foreach ($value->step as $step){

                $outputStep .= $stepIndex.". ".$step."\n";
                $stepIndex++;
            }
            $outputStep.="\n";
        }

        return "<b>Cara Pembayaran</b> \n".$outputStep;
    }

    function guidv4($data = null)
    {
        $data = $data ?? random_bytes(16);

        $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // set version to 0100
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // set bits 6-7 to 10

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

}
