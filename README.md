Simplified Project Overview: Wallet Service API

Built a simple and secure REST API for managing digital wallets using Laravel 11 and MySQL.
Users can create new wallets with an owner name and currency, starting with a zero balance.
Supports listing all wallets or filtering them by owner name or currency.
Allows deposits into any wallet, with idempotency to prevent duplication if the same request is repeated (using Idempotency-Key).
Supports withdrawals, immediately rejecting requests if the balance is insufficient, with full idempotency.
Enables transfers between wallets in a single atomic operation, with the following conditions:
Same currency only
No self-transfers
Sufficient balance in the source wallet
Idempotency to prevent duplicate transfers

Provides easy balance inquiry for any wallet.
Offers complete transaction history per wallet (deposits, withdrawals, transfers in/out), with filtering by type or date range and pagination.
Includes a health check endpoint that returns {"status": "ok"} to verify the API is running.
All monetary amounts are stored as integers (minor units) for perfect precision, avoiding floating-point issues.
Negative balances are never allowed; any operation that would cause one is rejected.
Transfers use double-entry accounting (recording both debit and credit sides).
Input validation rejects negative, zero, or missing amounts.
Timestamps are automatically recorded for all wallets and transactions.
Business logic is separated into a dedicated WalletService class for better maintainability and testing.
Chose /wallets/{id}/transfer endpoint instead of /transfers for clearer RESTful design (source wallet is explicit in the URL).

How to Run the Project
To set up and run the project locally:

1- Clone the repository:
git clone https://github.com/your-username/wallet-service-api.git
cd wallet-service-api

2- Install dependencies:
composer install

3- Copy the environment file and configure your database (MySQL):
cp .env.example .env
php artisan key:generate
Edit .env to set your database credentials (e.g., DB_DATABASE, DB_USERNAME, DB_PASSWORD).

4- Run database migrations:
php artisan migrate

5- Start the development server:
php artisan serve
================================================== API Documentation: Wallet Service =========================================
The API is built on Laravel and operates under the base path /api. All responses are in JSON format, and no authentication is required.
1. Health Check
GET/api/health
Expected Response:
{"status": "ok"}

2. Wallet Management
Create a New WalletPOST/api/walletsRequest Body:
{
    "owner_name": "Saad",
    "currency": "USD"
}
Response: Details of the new wallet with id and zero balance (Status 201).
List All WalletsGET/api/wallets
Supports filtering: ?owner_name=Saad or ?currency=USDResponse: Array of wallets.
Get Wallet DetailsGET/api/wallets/{id}Response: Wallet details including current balance.
Get Balance OnlyGET/api/wallets/{id}/balanceResponse
{"balance": 1000.50}
{
    "amount": 1000.50
}
Optional Header: Idempotency-Key: any-unique-string (prevents duplication)
Response: {"balance": new_balance}
WithdrawPOST/api/wallets/{id}/withdraw
Same body and header as deposit.
If insufficient balance → Error 400 with {"error": "Insufficient balance"}

4. Transfers Between Wallets

TransferPOST/api/wallets/{id}/transfer (where {id} is the source wallet)
Request Body:
{
    "to_wallet_id": 2,
    "amount": 400.75
}
Optional Header: Idempotency-KeyConditions:
Same currency
No self-transfer
Sufficient balance
Response:
{
    "message": "Transfer successful",
    "from_balance": 600.25,
    "to_balance": 400.75
}
5. Transaction History

GET/api/wallets/{id}/transactions
Supports filtering:
?type=deposit (or withdrawal, transfer_in, transfer_out)
?from=2025-12-01 and ?to=2025-12-31 (date range)
Automatic pagination
Response: List of transactions with id, type, amount, balance_after, related_wallet_id (for transfers), and created_at.


General Notes

All monetary values are stored and processed as integers (minor units) for complete accuracy.
Every financial operation is atomic and safe from partial failures.
Idempotency is supported for deposits, withdrawals, and transfers to prevent duplicates.
Transfers use double-entry accounting for accurate tracking.

The API is ready for use and testing via the included Postman collection in the repository.
Feel free to ask if you need further clarification or additional examples!

