# Banking Domain Model

This document is the implementation contract for the banking domain. It is
intended to drive the final UML class diagram and the PHP implementation.

## Design decisions

- PHP 8.1+ strict types are required in every domain class.
- Monetary values are stored and passed as integer minor units (for example,
  cents), never as `float`. A value must be positive unless a method explicitly
  documents a zero value.
- `BankAccount` is an abstract base class. `SavingsAccount` and
  `CurrentAccount` inherit common account behavior and implement their own
  withdrawal policy.
- An account owns its transaction history. The history is private to the
  account and is exposed only as a read-only array copy or an immutable
  statement; callers cannot append or replace ledger entries.
- `Bank` owns the customer and account registries and coordinates operations
  involving more than one account, especially transfers.
- A failed operation must leave every affected balance and transaction history
  unchanged. A transfer validates both accounts and all policies before
  mutating either account.

## Types and responsibilities

The design contains seven required classes and three supporting types.

### `Customer`

Represents one validated bank customer and the accounts owned by that customer.
It does not change account balances directly.

| Member | Visibility and type | Contract |
| --- | --- | --- |
| `$customerId` | `private readonly string` | Stable non-empty identifier. |
| `$fullName` | `private string` | Non-empty trimmed customer name. |
| `$email` | `private string` | Validated email address. |
| `$accounts` | `private array<string, BankAccount>` | Accounts owned by this customer, keyed by account number. |
| `__construct(string $customerId, string $fullName, string $email)` | `public` | Rejects blank IDs/names and invalid email addresses. |
| `getCustomerId(): string` | `public` | Returns the stable identifier. |
| `getFullName(): string` | `public` | Returns the current name. |
| `getEmail(): string` | `public` | Returns the current email. |
| `updateContactDetails(string $fullName, string $email): void` | `public` | Revalidates and replaces both contact values atomically. |
| `addAccount(BankAccount $account): void` | `public` | Adds an account owned by this customer; rejects a duplicate number or a different owner. |
| `getAccount(string $accountNumber): BankAccount` | `public` | Returns the owned account or throws `AccountNotFoundException`. |
| `getAccounts(): array` | `public` | Returns a copy of the account map; callers cannot mutate ownership. |

### `BankAccount` (abstract)

Owns common account state, lifecycle, balance changes, and the account ledger.
It is the only class that can append transactions to an account's history.

| Member | Visibility and type | Contract |
| --- | --- | --- |
| `$accountNumber` | `private readonly string` | Non-empty unique account number. |
| `$accountHolder` | `private readonly Customer` | Exactly one owning customer. |
| `$balanceInCents` | `private int` | Never negative unless a current-account overdraft is explicitly allowed. |
| `$status` | `private AccountStatus` | Starts as `ACTIVE`; closed accounts cannot transact. |
| `$transactions` | `private array<int, Transaction>` | Append-only successful operations for this account. |
| `__construct(string $accountNumber, Customer $accountHolder, int $openingBalanceInCents = 0)` | `protected` | Validates the number and non-negative opening balance. |
| `getAccountNumber(): string` | `public` | Returns the account number. |
| `getAccountHolder(): Customer` | `public` | Returns the owning customer. |
| `getBalanceInCents(): int` | `public` | Returns the current balance. |
| `getStatus(): AccountStatus` | `public` | Returns the lifecycle state. |
| `isActive(): bool` | `public` | Returns whether financial operations are allowed. |
| `deposit(int $amountInCents, string $description = ''): Transaction` | `public` | Requires an active account and a positive amount; credits the balance and records one deposit. |
| `withdraw(int $amountInCents, string $description = ''): Transaction` | `public` | Requires an active account and the subclass withdrawal policy; debits the balance and records one withdrawal. |
| `close(): void` | `public` | Changes `ACTIVE` to `CLOSED`; rejects repeated closure and does not erase history. |
| `getTransactions(): array` | `public` | Returns a read-only copy of `Transaction` records in chronological order. |
| `canWithdraw(int $amountInCents): bool` | `abstract protected` | Subclasses decide whether a withdrawal is permitted without mutating state. |
| `recordTransaction(Transaction $transaction): void` | `private` | Appends only a transaction belonging to this account; not callable by collaborators. |

`BankAccount` must not expose a public balance setter, transaction-list setter,
or mutable reference to its internal ledger.

### `SavingsAccount`

Applies savings-specific policy: a minimum balance and a per-transaction
withdrawal limit. It inherits deposit, close, status, and history behavior.

| Member | Visibility and type | Contract |
| --- | --- | --- |
| `MINIMUM_BALANCE_IN_CENTS` | `public const int` | Required balance after each withdrawal. |
| `MAX_WITHDRAWAL_IN_CENTS` | `public const int` | Largest permitted single withdrawal. |
| `__construct(string $accountNumber, Customer $accountHolder, int $openingBalanceInCents = 0)` | `public` | Delegates shared validation to the parent and rejects an opening balance below the minimum policy. |
| `canWithdraw(int $amountInCents): bool` | `protected` | Allows only positive amounts at or below the limit that leave the minimum balance. |

### `CurrentAccount`

Applies current-account policy: an optional overdraft limit and a transfer or
maintenance fee policy. It inherits the common account lifecycle and ledger.

| Member | Visibility and type | Contract |
| --- | --- | --- |
| `OVERDRAFT_LIMIT_IN_CENTS` | `public const int` | Maximum permitted negative balance, normally zero for this prototype. |
| `TRANSFER_FEE_IN_CENTS` | `public const int` | Fee applied by `Bank` when this account is the transfer source. |
| `__construct(string $accountNumber, Customer $accountHolder, int $openingBalanceInCents = 0)` | `public` | Delegates shared validation and accepts an opening balance that satisfies the overdraft policy. |
| `canWithdraw(int $amountInCents): bool` | `protected` | Allows a positive withdrawal when the resulting balance is not below the overdraft limit. |
| `getTransferFeeInCents(): int` | `public` | Returns the configured fee for transfer coordination. |

The explicit difference is that savings withdrawals preserve a minimum
balance, while current withdrawals may use the configured overdraft limit and
may incur a transfer fee.

### `Transaction`

An immutable audit record for one successful account-side posting.

| Member | Visibility and type | Contract |
| --- | --- | --- |
| `$transactionId` | `private readonly string` | Unique non-empty identifier. |
| `$occurredAt` | `private readonly DateTimeImmutable` | Time at which the posting was created. |
| `$type` | `private readonly TransactionType` | Deposit, withdrawal, transfer, or fee. |
| `$amountInCents` | `private readonly int` | Positive posted amount. |
| `$sourceAccountNumber` | `private readonly ?string` | Source account for transfers; otherwise `null`. |
| `$destinationAccountNumber` | `private readonly ?string` | Destination account for transfers; otherwise `null`. |
| `$description` | `private readonly string` | Human-readable non-sensitive description. |
| `__construct(string $transactionId, DateTimeImmutable $occurredAt, TransactionType $type, int $amountInCents, ?string $sourceAccountNumber, ?string $destinationAccountNumber, string $description)` | `public` | Validates positive amount, required transfer endpoints, and non-blank IDs. |
| `getTransactionId(): string` | `public` | Returns the ID. |
| `getOccurredAt(): DateTimeImmutable` | `public` | Returns the immutable timestamp. |
| `getType(): TransactionType` | `public` | Returns the transaction category. |
| `getAmountInCents(): int` | `public` | Returns the posted amount. |
| `getSourceAccountNumber(): ?string` | `public` | Returns the source account when applicable. |
| `getDestinationAccountNumber(): ?string` | `public` | Returns the destination account when applicable. |
| `getDescription(): string` | `public` | Returns the description. |

### `Bank`

Acts as the application-facing domain coordinator. It owns registries and
orchestrates cross-account work; it does not duplicate account balance logic.

| Member | Visibility and type | Contract |
| --- | --- | --- |
| `$customers` | `private array<string, Customer>` | Customers keyed by customer ID. |
| `$accounts` | `private array<string, BankAccount>` | Accounts keyed by unique account number. |
| `registerCustomer(Customer $customer): void` | `public` | Adds a customer; rejects a duplicate ID. |
| `getCustomer(string $customerId): Customer` | `public` | Resolves a customer or throws `CustomerNotFoundException`. |
| `openSavingsAccount(string $customerId, string $accountNumber, int $openingBalanceInCents = 0): SavingsAccount` | `public` | Requires a registered customer and unused account number; registers the new account. |
| `openCurrentAccount(string $customerId, string $accountNumber, int $openingBalanceInCents = 0): CurrentAccount` | `public` | Same registration guarantees for a current account. |
| `getAccount(string $accountNumber): BankAccount` | `public` | Resolves an account or throws `AccountNotFoundException`. |
| `transfer(string $sourceAccountNumber, string $destinationAccountNumber, int $amountInCents, string $description = ''): array` | `public` | Validates distinct active accounts, amount, source policy, and fee before posting both sides; returns the created transaction records. |
| `closeAccount(string $accountNumber): void` | `public` | Resolves and closes the account while retaining its history. |

### `AccountStatement`

Formats a snapshot of one account's history for display or export. It does not
own balances or mutate transactions.

| Member | Visibility and type | Contract |
| --- | --- | --- |
| `$account` | `private readonly BankAccount` | Account being reported. |
| `__construct(BankAccount $account)` | `public` | Captures the account dependency. |
| `getAccount(): BankAccount` | `public` | Returns the reported account. |
| `getTransactions(): array` | `public` | Returns the account's current transaction snapshot. |
| `render(): string` | `public` | Produces a readable statement containing account number, status, balance, and chronological transactions. |

## Supporting types and exceptions

### `AccountStatus` enum

Backed enum with `ACTIVE` and `CLOSED` values. It replaces string comparisons
such as `status === 'closed'` and makes lifecycle state explicit.

### `TransactionType` enum

Backed enum with `DEPOSIT`, `WITHDRAWAL`, `TRANSFER`, and `FEE` values. It keeps
transaction categories constrained and UML-visible.

### Domain exceptions

All extend `DomainException`, which extends PHP's `RuntimeException`:

- `InvalidAmountException`: zero, negative, or otherwise invalid amount.
- `InsufficientFundsException`: account policy rejects a withdrawal.
- `AccountNotFoundException`: an account number cannot be resolved.
- `CustomerNotFoundException`: a customer ID cannot be resolved.
- `ClosedAccountException`: an operation targets a closed account.
- `DuplicateAccountException` and `DuplicateCustomerException`: registry keys
  are already in use.

These exceptions are part of the public method contracts and allow the UI to
show safe messages without exposing implementation details.

## Relationships for the UML diagram

- `SavingsAccount` and `CurrentAccount` generalize `BankAccount`.
- `BankAccount` has exactly one `Customer` (`Customer` association,
  multiplicity `1`).
- `Customer` aggregates zero or more `BankAccount` objects (`0..*`); the bank
  registers the same accounts in its account registry.
- `BankAccount` composes zero or more `Transaction` objects (`0..*`), so the
  account owns the history and retains it after closure.
- `AccountStatement` depends on one `BankAccount` and reads its transaction
  snapshot.
- `Bank` aggregates customers and accounts and depends on account policies for
  transfer orchestration.
- `Transaction` uses `TransactionType`; `BankAccount` uses `AccountStatus`.
- Domain exceptions are thrown by the classes that detect the invalid state.

## Transaction-history ownership and querying

Each account is the source of truth for its own history. A successful deposit
or withdrawal creates one `Transaction` and appends it inside the same account
operation. A transfer creates a debit transaction on the source and a credit
transaction on the destination only after all validation succeeds. The `Bank`
coordinates this two-account operation but does not maintain a second mutable
ledger.

Callers query history through `BankAccount::getTransactions()` or
`AccountStatement::getTransactions()` / `render()`. Both return snapshots, so
external code cannot rewrite the account's audit trail. Closed accounts remain
queryable, while new financial operations are rejected.