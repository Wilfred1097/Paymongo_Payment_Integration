
# 💳 PayMongo Payment Gateway Dashboar

A lightweight, secure, and user-friendly PHP dashboard for managing online payments, tracking transactions, switching between **Test** and **Live** modes, and processing refunds using the **PayMongo API**.

**Sample Page:**
![https://github.com/Wilfred1097/Paymongo_Payment_Integration/blob/main/images/sample-ui.png](https://github.com/Wilfred1097/Paymongo_Payment_Integration/blob/main/images/sample-ui.png?raw=true)

**API Keys Configuration:**
![https://github.com/Wilfred1097/Paymongo_Payment_Integration/blob/main/images/key-configuration.png](https://github.com/Wilfred1097/Paymongo_Payment_Integration/blob/main/images/key-configuration.png?raw=true)

---

## 🚀 Features

- **Dynamic API Key Management:** Configure and update PayMongo API keys dynamically via a secure configuration UI.
- **Checkout Integration:** Generate PayMongo Checkout Sessions supporting GCash, Credit/Debit Cards, and QRPH.
- **Instant Refunds via AJAX:** Process transaction refunds directly from the dashboard and view detailed error or success feedback using **SweetAlert2**.
- **Scrollable Data Table:** Clean, responsive table layout featuring 
-  **sticky header** and a max-height scrollable container for managing large transaction histories.
- **Real-time Search:** Instantly filter transaction records by reference number, description, status, or amount.
- **Modern UI:** Styled with Google Fonts (*Plus Jakarta Sans*), FontAwesome icons, and status badges.

---
## 📂 Project Structure
<pre style="font-size: 1.2rem; line-height: 1.5; background: #f8fafc; padding: 12px; border-radius: 6px;">
```text
paymongo-dashboard/
├── conn.php # Database connection settings (PDO)
├── index.php # Main dashboard view, transaction table, and modal configurations
├── create_payment.php # Handles communication with PayMongo Checkout API
├── process_refund.php # Handles PayMongo refund requests and database status updates
├── paymongo_keys.php # Auto-generated file storing your API keys.
└── style.css # Custom styles and layout rules
```
</pre>

---
## 🛠️ Prerequisites

- **PHP** >= 8.0
- **MySQL / MariaDB**
- **cURL** extension enabled in PHP
- A **PayMongo Account** (with valid **Test** and **Live API** keys)
---
## ⚙️ Installation & Setup

1. Clone or Download the Repository** — Structure and romantic styling / animations.
    Place your project directory inside your local server environment (e.g., `htdocs` for XAMPP or `www` for WAMP).
    
2. Set Up the Database
    Create a MySQL database and run the following SQL query to create the required `payments` table with mode logging support:
```SQL
CREATE TABLE payments (
   id INT AUTO_INCREMENT PRIMARY KEY,
   reference_number VARCHAR(100) NOT NULL,
   payment_intent_id VARCHAR(100) DEFAULT NULL,
   payment_id VARCHAR(100) DEFAULT NULL,
   amount DECIMAL(10, 2) NOT NULL,
   status VARCHAR(50) DEFAULT 'pending',
   description TEXT DEFAULT NULL,
   checkout_url TEXT DEFAULT NULL,
   mode VARCHAR(10) DEFAULT 'test',
   created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP 
);
```

3. Configure Database Connection
    Update `conn.php` with your database credentials:
    ```PHP
    <?php
        $host = 'localhost';
        $db   = 'your_database_name';
        $user = 'root';
        $pass = '';
        try {
            $conn = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            die("Connection failed: " . $e->getMessage());
        }
    ?>
    ```

4. Configure API Keys
   1. Launch your application in the browser (e.g., `http://localhost/paymongo/index.php`).
   2. Click the **"Configure Paymongo Key"** button at the top right of the dashboard.
   3. Input your **Live API Key** (`sk_live_...`) and **Test API Key** (`sk_test_...`) and click **Save**.
---

## 💡 Usage Guide

1. **Making a Payment:** Fill out the _New Payment_ form on the left side of the dashboard with an amount and description, then click **Proceed to Pay**. You will be redirected to PayMongo's hosted checkout page.
2. **Switching Modes:** Use the toggle buttons at the top header to alternate between **Test Mode** and **Live Mode**.
3. **Searching Transactions:** Use the search bar inside the _Recent Transactions_ card header to dynamically narrow down table results.
4. **Refunding a Payment:** For eligible successful transactions, click the **Refund** action button. A SweetAlert confirmation modal will appear. If successful, the database status updates instantly to `refunded`.

---

## 📄 License

Distributed under the **MIT License**. See `LICENSE` for more information.

---

<div align="center">
  <sub>Built with ❤️ by <a href="https://github.com/wilfred1097">wilfred1097</a></sub>
</div>