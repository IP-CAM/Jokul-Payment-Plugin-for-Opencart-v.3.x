<?php

class ModelExtensionPaymentJokulVAMandiri extends Model
{
  public function getMethod($address, $total)
  {
    $this->load->language('extension/payment/doku');

    $status = true;

    $method_data = array();

    // 'code' value dari radio button
    if ($status) {
      $method_data = array(
        'code'       => 'jokul_va_mandiri',
        'title'      => $this->config->get('payment_jokul_va_mandiri_name'),
        'terms'      => '',
        'sort_order' => $this->config->get('payment_doku_sort_order')
      );
    }

    return $method_data;
  }
}
