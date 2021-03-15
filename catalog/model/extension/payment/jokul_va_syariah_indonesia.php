<?php

class ModelExtensionPaymentJokulVASyariahIndonesia extends Model
{
  public function getMethod($address, $total)
  {
    $this->load->language('extension/payment/doku');

    $status = true;

    $method_data = array();

    // 'code' value dari radio button
    if ($status) {
      $method_data = array(
        'code'       => 'jokul_va_syariah_indonesia',
        'title'      => $this->config->get('payment_jokul_va_syariah_indonesia_name'),
        'terms'      => '',
        'sort_order' => $this->config->get('payment_doku_sort_order')
      );
    }

    return $method_data;
  }
}
