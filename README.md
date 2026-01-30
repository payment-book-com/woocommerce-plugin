## PAYMENT BOOK - WooCommerce Plugin

### Overview
PAYMENT BOOK is a robust payment gateway plugin for WooCommerce designed to handle diverse card payment requirements. It offers a flexible architecture where merchants can specify multiple services with separate configurations for each client, ensuring a customized payment experience.

### Key Features
- **Universal Card Acceptance**: Ready to accept all major credit and debit cards securely.
- **Multi-Service Logic**: Configure unique parameters and separate services for different client segments within a single backoffice.
- **Seamless Integration**: Fully integrated into the native WooCommerce checkout flow.
- **Secure Transactions**: Built with security in mind to ensure safe processing for both merchants and customers.
- **Automatic Status Updates**: Supports secure webhooks to automatically update order status upon payment completion.

### Requirements
- PHP 7.3 or higher.
- WooCommerce 3.0 or higher.
- **Composer** (if installing from source).

### Installation

#### From Source (GitHub / Code)
1. Clone or download the repository to `wp-content/plugins/woocommerce-plugin`.
2. Navigate to the plugin directory in your terminal:
   ```bash
   cd wp-content/plugins/woocommerce-plugin
   ```
3. Install dependencies:
   ```bash
   composer install --no-dev
   ```
4. Activate the plugin via WordPress Admin > Plugins.

#### From Zip
1. Ensure the `vendor` directory is included in the zip file (run `composer install` before zipping).
2. Upload via WordPress Admin > Plugins > Add New > Upload Plugin.

### Configuration
1. Navigate to **WooCommerce > Settings > Payments**.
2. Locate **PAYMENT BOOK** in the list and click **Finish set up** or **Manage**.
3. Enter your **API Key Name**, **API Secret Key**, and **Service ID** obtained from your PAYMENT BOOK account.
4. Ensure the **API URL** is correct (defaults to `https://payment-book.com/api/transaction/purchase/create`).
5. Save changes.

### Webhook Configuration (CRITICAL)
For the plugin to receive automatic payment confirmations, you **MUST** configure a webhook in your PAYMENT BOOK service backoffice:
1. Log in to your PAYMENT BOOK account.
2. Navigate to **Services > [Your Service] > Webhooks**.
3. Add a new webhook with the following URL:
   `https://your-domain.com/?wc-api=payment_book`
   *(Replace `your-domain.com` with your actual website domain)*.
4. Select the event type for Transaction Status Changes (e.g., `transaction.updated` or equivalent).

### Testing Flow
1. **Setup**: Ensure the plugin is installed, activated, and configured with valid API credentials.
2. **Product**: Create a test product or use an existing one in WooCommerce.
3. **Checkout**: Add the product to the cart and proceed to checkout.
4. **Billing Details**: Fill in all required fields. **Crucial**: You must select a valid **Date of Birth** (18+).
5. **Payment**: Choose **PAYMENT BOOK** as the payment method and click "Place Order".
6. **Redirect**: You should be redirected to the secure PAYMENT BOOK payment page.
7. **Complete Payment**: Use a test card or complete the payment process on the gateway.
8. **Return**: You will be redirected back to the "Order Received" page on your store.
9. **Verification**:
   - Check the Order Status in **WooCommerce > Orders**.
   - It should initially be **Pending Payment**.
   - Once the callback (webhook) is received from PAYMENT BOOK, the status should automatically update to **Processing** or **Completed**.

### FAQ
**Q: Do I need to set up a scheduler (cron job) for status checks?**
A: **No.** This plugin uses **Webhooks (Callbacks)** for real-time status updates, which is more efficient and faster than polling via schedulers. As long as you have configured the Webhook URL in your PAYMENT BOOK backoffice, your orders will update automatically.

---

**Powered by [payment-book.com](https://payment-book.com/)**
