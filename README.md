Implementation Details – Matching the Requirements
المتطلب في الـ Task,التنفيذ في المشروع,التفاصيل والملفات المعنية
"POST /wallets
Create wallet with owner_name and currency, zero balance",مطابَق تمامًا,"WalletController@store + StoreWalletRequest
يُنشئ محفظة برصيد 0 افتراضيًا"
"GET /wallets/{id}
Retrieve wallet details including balance",مطابَق تمامًا,"WalletController@show
يعرض owner_name, currency, balance (بتحويل minor units → decimal)"
"GET /wallets
List all wallets with optional filters (owner/currency)",مطابَق تمامًا,"WalletController@index
يدعم query params مثل ?owner_name=Saad أو ?currency=USD"
"POST /wallets/{id}/deposit
Idempotent deposit",مطابَق تمامًا,"WalletController@deposit + WalletService@deposit
يدعم Idempotency-Key عبر جدول idempotency_keys"
"POST /wallets/{id}/withdraw
Reject insufficient balance, idempotent",مطابَق تمامًا,"WalletController@withdraw + WalletService@withdraw
يرفض إذا رصيد < المبلغ، ويدعم idempotency"
"Transfers
Atomic, reject insufficient/self-transfer/different currency, idempotent",مطابَق تمامًا (مع اختلاف تصميمي مبرر),"TransferController@store + WalletService@transfer
Endpoint: POST /wallets/{id}/transfer (بدل /transfers لأسباب RESTful أفضل)
Atomic عبر DB::transaction
Double-entry (transfer_out + transfer_in)
رفض self-transfer و different currency
Idempotency مدعوم"
"GET /wallets/{id}/transactions
History with filters (type, date range), pagination, includes related wallet",مطابَق تمامًا,"WalletController@transactions
فلترة بـ ?type=deposit&from=2025-12-01&to=2025-12-31
Pagination بـ Laravel paginate
يعرض related_wallet_id للتحويلات"
GET /wallets/{id}/balance,مطابَق تمامًا,WalletController@balance
"GET /health → {""status"": ""ok""}",مطابَق تمامًا,HealthController@index
"Monetary Precision
Use integers (minor units) only",مطابَق تمامًا,"كل المبالغ مخزنة كـ bigInteger (minor units)
تحويل في getter/setter في Model Wallet و Service"
No negative balances,مطابَق تمامًا,التحقق في WalletService@withdraw و transfer
"Validation
Reject negative/zero/missing amounts",مطابَق تمامًا,Form Requests + Service validation
Double-Entry for transfers,مطابَق تمامًا,معاملتين منفصلتين: transfer_out و transfer_in
Idempotency,مطابَق تمامًا,لكل العمليات المالية عبر جدول idempotency_keys
Timestamps,مطابَق تمامًا,Laravel timestamps تلقائيًا
