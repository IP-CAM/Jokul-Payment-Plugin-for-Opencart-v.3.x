<?php

class ControllerExtensionPaymentJokulVABca extends Controller
{
    const CHANNEL_CODE = '_va_bca';

    public function index()
    {
        $this->load->language('extension/payment/jokul' . self::CHANNEL_CODE);

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('setting/setting');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            $this->model_setting_setting->editSetting('payment_jokul' . self::CHANNEL_CODE, $this->request->post);

            $this->session->data['success'] = $this->language->get('text_success');

            $this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true));
        }
        // static value to display
        # Get Form Data
        $data['heading_title']                  = $this->language->get('heading_title');
        $data['server_params']                  = $this->language->get('server_params');
        $data['entry_status']                   = $this->language->get('entry_status');
        $data['text_enabled']                   = $this->language->get('text_enabled');
        $data['text_disabled']                  = $this->language->get('text_disabled');
        $data['text_edit']                      = $this->language->get('text_edit');
        $data['url_title']                      = $this->language->get('url_title');
        $data['button_save']                    = $this->language->get('button_save');
        $data['button_cancel']                  = $this->language->get('button_cancel');

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text'      => $this->language->get('text_home'),
            'href'      =>  $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true),
            'separator' => false
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_extension'),
            'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true)
        );

        $data['breadcrumbs'][] = array(
            'text'      => $this->language->get('heading_title'),
            'href'      => $this->url->link('extension/payment/jokul' . self::CHANNEL_CODE, 'user_token=' . $this->session->data['user_token'], true),
            'separator' => ' :: '
        );

        $data['action'] = $this->url->link('extension/payment/jokul' . self::CHANNEL_CODE, 'user_token=' . $this->session->data['user_token'], true);
        $data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token']  . '&type=payment', true);

        if (isset($this->request->post['payment_jokul' . self::CHANNEL_CODE . '_channel_name'])) {
            $data['channel_name'] = $this->request->post['payment_jokul' . self::CHANNEL_CODE . '_name'];
        } else {
            $data['channel_name'] = $this->config->get('payment_jokul' . self::CHANNEL_CODE . '_name');
        }

        if (isset($this->request->post['payment_jokul' . self::CHANNEL_CODE . '_status'])) {
            $data['channel_status'] = $this->request->post['payment_jokul' . self::CHANNEL_CODE . '_status'];
        } else {
            $data['channel_status'] = $this->config->get('payment_jokul' . self::CHANNEL_CODE . '_status');
        }

        $this->load->model('localisation/geo_zone');
        $data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();

        $this->load->model('localisation/order_status');
        $data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

        if (isset($this->request->post['payment_jokul' . self::CHANNEL_CODE . '_sort_order'])) {
            $data['payment_jokul' . self::CHANNEL_CODE . '_sort_order'] = $this->request->post['payment_jokul' . self::CHANNEL_CODE . '_sort_order'];
        } else {
            $data['payment_jokul' . self::CHANNEL_CODE . '_sort_order'] = $this->config->get('payment_jokul' . self::CHANNEL_CODE . '_sort_order');
        }

        $data['user_token'] = $this->session->data['user_token'];

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        //move to language later
        $data['va_input_label'] = $this->language->get('va_input_label');
        $data['channel_code'] = self::CHANNEL_CODE;
        $data['channel_name'] = "BCA VA";
        //end

        $this->response->setOutput($this->load->view('extension/payment/jokulva', $data));
    }

    public function install()
    {
        if ($this->user->hasPermission('modify', 'extension/extension')) {
            $this->load->model('extension/payment/jokul' . self::CHANNEL_CODE);
            $this->model_extension_payment_jokul_va_bca->install();
        }
    }

    public function uninstall()
    {
        if ($this->user->hasPermission('modify', 'extension/extension')) {
            $this->load->model('extension/payment/jokul' . self::CHANNEL_CODE);
            $this->model_extension_payment_jokul_va_bca->uninstall();
        }
    }

    public function validate()
    {
        return true;
    }
}
