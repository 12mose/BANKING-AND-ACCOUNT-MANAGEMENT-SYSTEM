<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/Classes/Bank.php';
require_once __DIR__ . '/../src/Classes/AccountStatement.php';

$bank = new Bank();
$customer = new Customer('DEMO-001', 'Demo Customer', 'demo@example.com');
$bank->registerCustomer($customer);
$account = $bank->openSavingsAccount('DEMO-001', 'SAV-0001', SavingsAccount::MINIMUM_BALANCE);

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	try {
		$amount = (int) round(((float) ($_POST['amount'] ?? 0)) * 100);
		if (($_POST['action'] ?? '') === 'deposit') {
			$account->deposit($amount, 'Web deposit');
		} else {
			$account->withdraw($amount, 'Web withdrawal');
		}
		$message = 'Operation completed.';
	} catch (Throwable $exception) {
		$message = $exception->getMessage();
	}
}
?>
<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><title><?= htmlspecialchars(Bank::BANK_NAME) ?> Banking Demo</title></head>
<body>
<h1><?= htmlspecialchars(Bank::BANK_NAME) ?> Banking Demo</h1>
<p>Account <?= htmlspecialchars($account->getAccountNumber()) ?> balance: <?= number_format($account->getBalanceInCents() / 100, 2) ?></p>
<?php if ($message !== ''): ?><p><?= htmlspecialchars($message) ?></p><?php endif; ?>
<form method="post">
	<label>Amount <input name="amount" type="number" min="0.01" step="0.01" required></label>
	<button name="action" value="deposit">Deposit</button>
	<button name="action" value="withdraw">Withdraw</button>
</form>
<pre><?= htmlspecialchars((new AccountStatement($account))->render()) ?></pre>
</body>
</html>
