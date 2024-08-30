<?php
// This indicates that the file contains PHP code.

namespace Opencart\Catalog\Controller\Extension\Paystack\Payment;
// This sets the namespace for this controller. It helps organize your code and avoid name conflicts.

class Paystack extends \Opencart\System\Engine\Controller
{
    // This defines the Paystack class, which extends the base controller class provided by OpenCart.
    // Extending this base class allows us to use the built-in functionalities provided by OpenCart.

    public function index()
    {
        // This is the index method, which is called when the Paystack payment option is selected by the customer.
        
        $this->load->model('checkout/order');
        // Loads the Order model. This model is used to interact with the orders in the OpenCart database.

        $this->load->language('extension/paystack/payment/paystack');
        // Loads the language file for Paystack. This file contains all the text strings used in the Paystack payment view.

        $data['button_confirm'] = $this->language->get('button_confirm');
        // Retrieves the translated text for the 'Confirm' button from the language file and stores it in the $data array.

        $data['text_testmode'] = $this->language->get('text_testmode');
        $data['livemode'] = $this->config->get('payment_paystack_live');
        // Retrieves the text for test mode and the live mode status (true/false) from the configuration.

        if ($this->config->get('payment_paystack_live')) {
            $data['key'] = $this->config->get('payment_paystack_live_public');
            // If live mode is enabled, get the live public key for Paystack.
        } else {
            $data['key'] = $this->config->get('payment_paystack_test_public');
            // If test mode is enabled, get the test public key for Paystack.
        }

        $order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);
        // Retrieves the order information using the order ID stored in the session.

        $data['currency'] = $order_info['currency_code'];
        // Gets the currency code for the order and stores it in the $data array.
        // $data['currency'] = 'NGN';
        // This line is commented out. If you wanted to force the currency to NGN (Nigerian Naira), you would uncomment this.

        $data['ref']      = uniqid('' . $this->session->data['order_id'] . '-');
        // Generates a unique reference for the transaction. It combines the order ID with a unique ID.

        $data['amount']   = intval($this->currency->format($order_info['total'] * 100, $order_info['currency_code'], $order_info['currency_value'], false));
        // Converts the total order amount to the smallest currency unit (e.g., kobo for NGN) and stores it in $data.

        $data['email']    = $order_info['email'];
        // Stores the customer's email address in the $data array.

        $data['callback'] = $this->url->link('extension/paystack/payment/paystack.callback', 'trxref=' . rawurlencode($data['ref']), true);
        // Generates the callback URL for Paystack to redirect to after payment. This URL includes the transaction reference.

        return $this->load->view('extension/paystack/payment/paystack', $data);
        // Loads the Paystack payment view and passes the $data array to it.
    }

    private function query_api_transaction_verify($reference)
    {
        // This is a private method that verifies the Paystack transaction by querying the Paystack API.

        if ($this->config->get('payment_paystack_live')) {
            $skey = $this->config->get('payment_paystack_live_secret');
            // If live mode is enabled, use the live secret key.
        } else {
            $skey = $this->config->get('payment_paystack_test_secret');
            // If test mode is enabled, use the test secret key.
        }

        $context = stream_context_create(
            array(
                'http' => array(
                    'method' => "GET",
                    'header' => "Authorization: Bearer " .  $skey,
                    'user-agent' => "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/51.0.2704.103 Safari/537.36",
                )
            )
        );
        // Creates a stream context with HTTP headers including the Authorization header with the secret key.
        // This context is used to make the API request.

        $url = 'https://api.paystack.co/transaction/verify/' . rawurlencode($reference);
        // Constructs the URL for the Paystack API request to verify the transaction.

        $request = file_get_contents($url, false, $context);
        // Sends the HTTP GET request to Paystack API and retrieves the response.

        return json_decode($request, true);
        // Decodes the JSON response from Paystack into an associative array and returns it.
    }

    private function redir_and_die($url, $onlymeta = false)
    {
        // This is a private method that redirects the user to a specified URL and then stops the script.

        if (!headers_sent() && !$onlymeta) {
            header('Location: ' . $url);
            // If headers haven't been sent yet and onlymeta is false, send a Location header to redirect the browser.
        }

        echo "<meta http-equiv=\"refresh\" content=\"0;url=" . addslashes($url) . "\" />";
        // Outputs a meta tag that also redirects the browser. This is a fallback for cases where headers have already been sent.

        die();
        // Terminates the script execution.
    }

    public function callback()
    {
        // This is the callback method, which is triggered after Paystack processes the payment.

        if (isset($this->request->get['trxref'])) {
            // Checks if the transaction reference (trxref) is present in the request.

            $trxref = $this->request->get['trxref'];
            // Retrieves the transaction reference from the request.

            // order id is what comes before the first dash in trxref
            $order_id = substr($trxref, 0, strpos($trxref, '-'));
            // Extracts the order ID from the transaction reference. The order ID is the part before the first dash.

            // if no dash were in transaction reference, we will have an empty order_id
            if (!$order_id) {
                $order_id = 0;
                // If the order ID is empty, set it to 0 as a fallback.
            }

            $this->load->model('checkout/order');
            // Loads the Order model again to interact with the order data.

            $order_info = $this->model_checkout_order->getOrder($order_id);
            // Retrieves the order information based on the order ID.

            if ($order_info) {
                // Checks if the order information was successfully retrieved.

                if ($this->config->get('payment_paystack_debug')) {
                    $this->log->write('PAYSTACK :: CALLBACK DATA: ' . print_r($this->request->get, true));
                    // If debug mode is enabled, logs the callback data for debugging purposes.
                }

                // Callback paystack to get real transaction status
                $ps_api_response = $this->query_api_transaction_verify($trxref);
                // Calls the method to verify the transaction with Paystack and gets the response.

                $order_status_id = $this->config->get('config_order_status_id');
                // Sets the initial order status ID to the default order status.

                if (array_key_exists('data', $ps_api_response) && array_key_exists('status', $ps_api_response['data']) && ($ps_api_response['data']['status'] === 'success')) {
                    // Checks if the transaction was successful.

                    $order_status_id = $this->config->get('payment_paystack_order_status_id');
                    // If successful, set the order status to the configured successful status ID.

                    $redir_url = $this->url->link('checkout/success');
                    // Set the redirect URL to the success page.
                } elseif (array_key_exists('data', $ps_api_response) && array_key_exists('status', $ps_api_response['data']) && ($ps_api_response['data']['status'] === 'failed')) {
                    // If the transaction failed, set the order status to the failed status ID.

                    $order_status_id = $this->config->get('payment_paystack_failed_status_id');
                    $redir_url = $this->url->link('checkout/checkout', '', true);
                    // Set the redirect URL to the checkout page.
                } else {
                    // If the transaction was neither successful nor failed, set the order status to canceled.

                    $order_status_id = $this->config->get('payment_paystack_canceled_status_id');
                    $redir_url = $this->url->link('checkout/checkout', '', true);
                    // Set the redirect URL to the checkout page.
                }

                $this->model_checkout_order->addHistory($order_id, $order_status_id);
                // Updates the order history with the new status.

                $this->redir_and_die($redir_url);
                // Redirects the user to the appropriate page and stops the script.
            }
        } else if ((strtoupper($_SERVER['REQUEST_METHOD']) == 'POST') && array_key_exists('x-paystack-signature', $_SERVER)) {
            // This block handles the webhook notification from Paystack.
            // It checks if the request method is POST and if the Paystack signature is present.

            $input = @file_get_contents("php://input");
            // Retrieves the raw POST data (body of the request).

            if ($this->config->get('payment_paystack_live')) {
                $secret_key = $this->config->get('payment_paystack_live_secret');
                // If live mode is enabled, use the live secret key.
            } else {
                $secret_key = $this->config->get('payment_paystack_test_secret');
                // If test mode is enabled, use the test secret key.
            }

            if ($_SERVER['HTTP_X_PAYSTACK_SIGNATURE'] !== hash_hmac('sha512', $input, $secret_key))
                exit();
            // Validates the webhook signature to ensure the request is genuinely from Paystack. If the signature doesn't match, the script exits.

            http_response_code(200);
            // Sends a 200 OK response to Paystack to acknowledge the webhook was received.

            $event = json_decode($input);
            // Parses the JSON event data into a PHP object.

            if ($event['event'] == 'charge.success') {
                // If the event is a successful charge, process the payment.

                $trxref = $event['data']['reference'];
                // Retrieves the transaction reference from the event data.

                $order_id = substr($trxref, 0, strpos($trxref, '-'));
                // Extracts the order ID from the transaction reference.

                if (!$order_id) {
                    $order_id = 0;
                    // If the order ID is empty, set it to 0 as a fallback.
                }

                $this->load->model('checkout/order');
                // Loads the Order model again to interact with the order data.

                $order_info = $this->model_checkout_order->getOrder($order_id);
                // Retrieves the order information based on the order ID.

                $order_status_id = $this->config->get('config_order_status_id');
                // Sets the initial order status ID to the default order status.

                if (array_key_exists('data', $event) && array_key_exists('status', $event['data']) && ($event['data']['status'] === 'success')) {
                    // If the transaction was successful, set the order status to the configured successful status ID.

                    $order_status_id = $this->config->get('payment_paystack_order_status_id');
                } elseif (array_key_exists('data', $event) && array_key_exists('status', $event['data']) && ($event['data']['status'] === 'failed')) {
                    // If the transaction failed, set the order status to the declined status ID.

                    $order_status_id = $this->config->get('payment_paystack_declined_status_id');
                } else {
                    // If the transaction was neither successful nor failed, set the order status to canceled.

                    $order_status_id = $this->config->get('payment_paystack_canceled_status_id');
                }

                $this->model_checkout_order->addHistory($order_id, $order_status_id);
                // Updates the order history with the new status.
            }
            exit();
            // Exits the script after processing the webhook.
        }
    }
}
