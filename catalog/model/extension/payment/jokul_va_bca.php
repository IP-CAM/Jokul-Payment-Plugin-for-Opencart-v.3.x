<?php

class ModelExtensionPaymentJokulVABca extends Model
{
  public function getMethod($address, $total)
  {
    $this->load->language('extension/payment/jokul');

    $status = true;

    $method_data = array();

    // 'code' value dari radio button
    if ($status) {
      $method_data = array(
        'code'       => 'jokul_va_bca',
        'title'      => $this->config->get('payment_jokul_va_bca_name'),
        'terms'      => '',
        'sort_order' => $this->config->get('payment_jokul_sort_order')
      );
    }

    return $method_data;
  }
}
