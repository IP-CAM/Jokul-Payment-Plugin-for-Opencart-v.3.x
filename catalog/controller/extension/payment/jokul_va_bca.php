<?php

class ControllerExtensionPaymentJokulVABca extends Controller
{
    public function index()
    {
        $this->language->load('extension/payment/jokul');
        $this->load->model('checkout/order');
        $serverconfig = $this->getUrlLocal();
        $data['urlLocal'] = $serverconfig . "/index.php?route=extension/payment/jokul/redirect";
        $data['urlSetProses'] = $serverconfig . "/index.php?route=extension/payment/jokul/processdoku";
        $data['button_confirm'] = $this->language->get('button_confirm');
        $data['invoice_number'] = $this->session->data['order_id'];
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
}
