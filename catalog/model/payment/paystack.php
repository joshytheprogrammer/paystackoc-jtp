<?php
namespace Opencart\Catalog\Model\Extension\Paystack\Payment;
// The namespace declaration specifies that this class belongs to the Paystack payment module in the Catalog section of OpenCart.

class Paystack extends \Opencart\System\Engine\Model
{
    // The Paystack class extends the base Model class provided by OpenCart, inheriting its properties and methods.
    
    public function getMethods(array $address): array
    {
        // This method is responsible for determining whether the Paystack payment method should be available to the customer.
        // It takes an array $address as a parameter, which contains the customer's address information.

        $this->load->language('extension/paystack/payment/paystack');
        // Loads the language file associated with the Paystack payment method. This file contains text strings like the payment method title.

        $total = $this->cart->getTotal();
        // Retrieves the total value of the items in the cart.

        if ($this->config->get('payment_paystack_total') > 0 && $this->config->get('payment_paystack_total') > $total) {
            $status = false;
            // If a minimum order total is set for the Paystack payment method, and the current cart total is less than this minimum, disable Paystack.
        } elseif (!$this->config->get('payment_paystack_geo_zone_id')) {
            $status = true;
            // If no geo zone is configured for Paystack, enable the payment method.
        } else {
            $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "zone_to_geo_zone` WHERE `geo_zone_id` = '" . (int)$this->config->get('payment_paystack_geo_zone_id') . "' AND `country_id` = '" . (int)$address['country_id'] . "' AND (`zone_id` = '" . (int)$address['zone_id'] . "' OR `zone_id` = '0')");
            // If a geo zone is set, check whether the customer's address falls within this geo zone.

            if ($query->num_rows) {
                $status = true;
                // If the customer's address is within the geo zone, enable the Paystack payment method.
            } else {
                $status = false;
                // Otherwise, disable the payment method.
            }
        }
        
        // Paystack only supports NGN, GHS, USD, ZAR, KES, EGP, and CIV currencies.
        if ($status && (!in_array(
            strtoupper($this->config->get('config_currency')),
            [
                'NGN',
                'GHS',
                'USD',
                'ZAR',
                'KES',
                'EGP',
                'CIV'
            ]
        ))) {
            $status = false;
            // If the current store currency is not supported by Paystack, disable the payment method.
        }

        $method_data = [];
        $option_data = [];

        if ($status) {
            // If all conditions are met (minimum total, geo zone, supported currency), prepare the Paystack payment method data.
            
            $option_data['paystack'] = [
                'code' => 'paystack.paystack',
                'name' => $this->language->get('text_title')
                // Set the option code and name for the Paystack payment method.
            ];

            $method_data = [
                'code'       => 'paystack',
                'name'       => $this->language->get('text_title'),
                'option'     => $option_data,
                'sort_order' => $this->config->get('payment_paystack_sort_order')
                // Set the main method data including code, name, options, and sort order.
            ];
        }

        return $method_data;
        // Return the configured payment method data. If $status is false, this will be an empty array, meaning the payment method won't be available.
    }
}
