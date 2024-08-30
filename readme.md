# Paystack Payment Extension for OpenCart

**Author:** joshytheprogrammer

## Overview

This extension integrates Paystack payment gateway with your OpenCart store. It allows you to accept payments via Paystack, supporting various currencies and providing a seamless checkout experience for your customers.

## Features

- **Seamless Integration**: Easy setup and integration with Paystack payment gateway.
- **Multiple Currency Support**: Accept payments in NGN, GHS, USD, ZAR, KES, EGP, and CIV.
- **Test and Live Modes**: Configure test and live API keys for secure transactions.
- **Order Status Management**: Automatically update order statuses based on payment results.
- **Webhooks**: Handle asynchronous notifications from Paystack for real-time transaction updates.
- **Validation**: Ensure that API keys and settings are correctly configured.

## Installation

1. **Download the Extension**: Obtain the extension package from the source or marketplace.

2. **Upload the Files**:
   - Go to your OpenCart admin panel.
   - Navigate to `Extensions` > `Installer`.
   - Upload the extension package file.

3. **Install the Extension**:
   - After uploading, go to `Extensions` > `Payments`.
   - Locate `Paystack` in the list of payment methods and click `Install`.

4. **Configure the Extension**:
   - Navigate to `Extensions` > `Payments` > `Paystack` and click `Edit`.
   - Enter your Paystack API keys (live and test) and configure other settings as required.

## Configuration

- **Live Secret Key**: Enter your Paystack live secret key.
- **Live Public Key**: Enter your Paystack live public key.
- **Test Secret Key**: Enter your Paystack test secret key.
- **Test Public Key**: Enter your Paystack test public key.
- **Minimum Order Total**: Set the minimum order total required to enable Paystack as a payment option.
- **Order Statuses**: Configure statuses for different payment outcomes (success, pending, failed, canceled).
- **Geo Zones**: Define the geographic regions where Paystack payment is available.

## Usage

1. **Frontend Checkout**: Once configured, customers will see Paystack as a payment option during checkout.
2. **Callback Handling**: Paystack will send payment notifications to the configured callback URL, which will update the order status accordingly.

## Troubleshooting

- **API Key Errors**: Ensure that your API keys are correct and properly configured.
- **Order Status Issues**: Verify that the order statuses are correctly set up and matched with Paystack's payment statuses.
- **Webhook Issues**: Check the webhook URL and ensure that your server can receive and process Paystack's notifications.

## Support

For support, please contact JOSHYTHEPROGRAMMER at [support@joshytheprogrammer.com](mailto:support@example.com).

## License

This extension is licensed under the [OpenCart License](https://www.opencart.com/). Use it in accordance with the license terms provided.
