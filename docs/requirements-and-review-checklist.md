# Requirements and Final Review Checklist

This document converts the assignment brief into the agreed implementation
scope. It is the review baseline for the PHP prototype and should be updated if
an open decision is resolved differently during implementation.

## Scope and actors

### Actors

| Actor | Responsibility |
| --- | --- |
| Customer | Owns one or more accounts and requests account operations through the application. |
| Bank operator / application user | Registers customers, opens and closes accounts, and initiates deposits, withdrawals, transfers, and inquiries. The prototype does not implement separate operator authentication. |
| System | Validates input, applies account policies, records successful transactions, and presents safe error feedback. |
| Reviewer | Verifies the acceptance checklist, tests, UML, screenshots, report, and demonstration evidence. |

The web interface is the only actor-facing boundary in this prototype. Domain
classes must remain usable independently of HTML rendering.

## Functional requirements and acceptance rules

Every accepted operation must preserve the invariants in the domain model and
must be covered by at least one positive and one negative test.

| ID | Use case | Primary actor | Acceptance rules |
| --- | --- | --- | --- |
| FR-01 | Register customer | Bank operator | A customer is created with a non-blank unique ID, name, and valid email. Duplicate IDs and invalid contact data are rejected without creating a partial record. |
| FR-02 | Open account | Bank operator | A registered customer can open either a `SavingsAccount` or `CurrentAccount` with a valid unique account number and valid opening amount. The account is registered with both the bank and its customer. Duplicate or unknown owners are rejected. |
| FR-03 | Deposit | Customer / operator | A positive integer amount in minor units is accepted only for an active account. The balance increases by exactly that amount and one deposit transaction is recorded. Zero, negative, invalid, or closed-account deposits are rejected with no state change. |
| FR-04 | Withdraw | Customer / operator | A positive amount is accepted only when the account-specific withdrawal policy allows it. The balance decreases by exactly that amount and one withdrawal transaction is recorded. Insufficient funds, policy limits, invalid amounts, and closed accounts are rejected with no state change. |
| FR-05 | Transfer | Customer / operator | A positive amount can move from one existing active account to a different existing active account. Source policy and any fee are validated before mutation. Both balances and both histories update together; a failed transfer changes neither account. |
| FR-06 | Balance inquiry | Customer / operator | An existing account's current balance and status can be read without mutating the account. A missing account returns a domain error that the UI converts to safe feedback. |
| FR-07 | Transaction history | Customer / operator | An account's successful transactions can be queried in chronological order. History remains available after closure and cannot be changed by callers. Missing accounts are rejected. |
| FR-08 | Statement generation | Customer / operator | `AccountStatement` can render account number, status, balance, and chronological transaction data from an account snapshot. Rendering does not mutate account state and does not expose stack traces or sensitive internal details. |
| FR-09 | Account closure | Bank operator | An active account can be closed once. Closure preserves balance and history, changes status to `CLOSED`, and prevents future deposits, withdrawals, and transfers. Repeated closure is rejected or reported as an invalid lifecycle operation. |

## Business rules and error scenarios

| Rule | Required behavior | Expected domain response |
| --- | --- | --- |
| Account status | Accounts start `ACTIVE`; only active accounts may perform financial operations. `CLOSED` accounts remain readable. | `AccountStatus` enum and `ClosedAccountException`. |
| Amount validity | All money values are integer minor units. Deposits, withdrawals, and transfers require an amount greater than zero; opening balances must satisfy the account policy. | `InvalidAmountException`; no state mutation. |
| Insufficient funds | A withdrawal must satisfy the concrete account policy. Savings must retain its minimum balance; current accounts must not exceed their overdraft limit. | `InsufficientFundsException`; no transaction is recorded. |
| Missing account | Account lookup must use an existing valid account number. | `AccountNotFoundException`; no operation begins. |
| Missing customer | Account opening requires a registered customer. | `CustomerNotFoundException`; no account is registered. |
| Duplicate identity | Customer IDs and account numbers are unique within the bank registry. | `DuplicateCustomerException` or `DuplicateAccountException`. |
| Invalid account number | Blank or malformed account numbers are rejected at construction or lookup. | Domain validation exception; no record is created. |
| Same-account transfer | Source and destination must be different accounts. | Invalid-operation/domain exception; both accounts remain unchanged. |
| Atomic transfer | Validate both accounts, amount, policy, and fee before changing either side. | Any failure leaves both balances and histories unchanged. |
| Auditability | Each successful posting creates an immutable `Transaction` with ID, timestamp, type, amount, endpoints where relevant, and description. | No silent balance changes. |
| Encapsulation | Balance, status, and transaction collections cannot be assigned from outside their owning object. | Private/protected state with controlled public methods only. |

## Account-specific policies

- `BankAccount` owns common balance, status, validation, deposit, withdrawal,
  closure, and transaction-history behavior.
- `SavingsAccount` requires a configured minimum balance after withdrawal and a
  configured maximum withdrawal amount.
- `CurrentAccount` applies a configured overdraft limit and exposes the
  configured transfer fee used by `Bank`.
- Policy values must be named class constants or explicitly configured typed
  values; unexplained literals are not acceptable.

## Minimum domain model

The minimum eight-class/type requirement is met by the following planned types.
The first seven are classes; the remaining entries are supporting types that
are also visible in the UML.

| Type | Planned responsibility | Target path |
| --- | --- | --- |
| `Customer` | Validated customer identity and account ownership | `src/Classes/Customer.php` |
| `BankAccount` | Abstract common account state and operations | `src/Classes/BankAccount.php` |
| `SavingsAccount` | Savings withdrawal and minimum-balance policy | `src/Classes/SavingsAccount.php` |
| `CurrentAccount` | Current-account overdraft and fee policy | `src/Classes/CurrentAccount.php` |
| `Transaction` | Immutable successful-operation record | `src/Classes/Transaction.php` |
| `Bank` | Customer/account registries and transfer coordination | `src/Classes/Bank.php` |
| `AccountStatement` | Read-only statement generation | `src/Classes/AccountStatement.php` |
| `AccountStatus` | `ACTIVE` and `CLOSED` lifecycle enum | `src/Classes/AccountStatus.php` |
| `TransactionType` | Deposit, withdrawal, transfer, and fee enum | `src/Classes/TransactionType.php` |
| Domain exceptions | Typed invalid-operation outcomes | `src/Exceptions/` |

The API, visibility, relationships, and invariants for these types are defined
in [the domain model](domain-model.md).

## Non-functional requirements

| ID | Requirement | Review evidence |
| --- | --- | --- |
| NFR-01 | Use PHP 8.1 or later with strict typing in domain classes. | `composer.json`, PHP version output, and source declarations. |
| NFR-02 | Keep business rules in domain classes, separate from the web page. | Source review and unit tests that instantiate domain classes directly. |
| NFR-03 | Protect mutable state through encapsulation and typed public APIs. | UML, source review, and type/error tests. |
| NFR-04 | Use integer minor units for money and immutable transaction records. | Domain model, implementation, and boundary tests. |
| NFR-05 | Preserve data consistency when any operation fails. | Negative and atomic-transfer tests. |
| NFR-06 | Provide safe, understandable UI errors without stack traces or secrets. | Manual browser check and screenshots. |
| NFR-07 | Make the prototype runnable from a clean checkout using Composer. | Installation and test commands in `README.md`. |
| NFR-08 | Keep the interface simple and demonstrable for the required workflows. | `public/index.php`, browser walkthrough, and screenshots. |

## Interface and technology decisions

- **Minimum PHP:** PHP 8.1 or later.
- **Interface style:** server-rendered HTML with CSS, served by PHP's built-in
  development server from `public/index.php`. No SPA framework or API-only
  interface is required.
- **Persistence:** PHP session-backed prototype state unless implementation
  work records and justifies a replacement. No production database is in
  scope.
- **Dependencies:** Composer for autoloading and PHPUnit for automated tests.
- **Money representation:** integer minor units, as defined above.
- **Presentation boundary:** the interface catches domain exceptions and shows
  safe messages; domain classes do not print HTML.

## Deliverable work items and repository locations

Each work item has an owner-ready role and a target path. The role can be
replaced with a group member's name when work is assigned.

| Work item | Owner-ready role | Target path / evidence |
| --- | --- | --- |
| Implement domain classes and supporting types | Domain implementation owner | `src/Classes/`, `src/Exceptions/` |
| Demonstrate at least eight classes/types | Domain implementation owner | `src/Classes/` and `src/Exceptions/` |
| Build the working web interface | Interface owner | `public/index.php`, `public/` CSS/assets as needed |
| Add unit and integration tests | Test owner | `tests/Unit/`, `tests/Integration/` |
| Record test commands and results | Test owner | `docs/test-results/` |
| Produce UML class diagram matching code | Design/documentation owner | `docs/uml/` |
| Demonstrate access modifiers and encapsulation | Domain implementation owner | Source classes plus `docs/domain-model.md` |
| Demonstrate class constants | Domain implementation owner | Account policy classes plus `docs/domain-model.md` |
| Demonstrate typed properties and strict typing | Domain implementation owner | Source classes plus type tests in `tests/Unit/` |
| Write technical report | Report owner | `docs/report/` |
| Capture system screenshots | Interface/documentation owner | `screenshots/` |
| Write individual contribution records for six members | Project coordinator | `docs/report/contributions/` |
| Prepare presentation | Presentation owner | `docs/presentation/` |
| Prepare practical demonstration script and sample data | Demonstration owner | `docs/demo/` |

An unchecked row is unfinished until its target artifact exists and the final
review checklist has evidence that it works.

## Final review checklist

### Functional review

- [ ] Customer registration validates identity, email, and uniqueness.
- [ ] Savings and current accounts can be opened for registered customers.
- [ ] Deposits accept valid positive amounts and record history.
- [ ] Withdrawals enforce account-specific limits and funds rules.
- [ ] Transfers update both accounts atomically and record both sides.
- [ ] Balance inquiry works for active and closed accounts.
- [ ] Transaction history is chronological, read-only, and retained after closure.
- [ ] Statements contain the required account and transaction information.
- [ ] Account closure is reflected in status and blocks later financial operations.

### Error and integrity review

- [ ] Zero and negative amounts are rejected.
- [ ] Invalid PHP value types fail safely under strict typing.
- [ ] Insufficient funds and policy violations do not change state.
- [ ] Missing accounts and customers produce safe error feedback.
- [ ] Duplicate customers and accounts are rejected.
- [ ] Same-account transfers are rejected.
- [ ] Closed-account operations are rejected.
- [ ] Failed transfers leave both balances and histories unchanged.
- [ ] No UI error exposes stack traces or sensitive implementation details.

### Submission review

- [ ] PHP source and Composer setup run from a clean checkout.
- [ ] At least eight classes/types are implemented and represented in UML.
- [ ] Unit, integration, negative, boundary, and type-handling tests pass.
- [ ] Test results are recorded under `docs/test-results/`.
- [ ] Technical report, UML, screenshots, contributions, presentation, and demo artifacts exist at their target paths.
- [ ] README and this checklist agree with the implemented behavior.

## Open decisions and assumptions

These items are intentionally recorded before implementation and must be
resolved in code or marked as accepted assumptions during final review.

| Decision | Current assumption | Resolution evidence |
| --- | --- | --- |
| Exact account-number format | Non-blank unique string; implementation may enforce a documented pattern. | Validation tests and domain exception behavior. |
| Savings minimum and withdrawal limit | Named constants; final numeric values must be agreed before account tests are written. | Constants and boundary tests. |
| Current overdraft and transfer fee | Named constants; default overdraft is zero unless the team approves otherwise. | Policy constants and transfer tests. |
| Transfer transaction shape | One debit record on the source and one credit record on the destination, linked by transaction metadata. | UML and integration tests. |
| Session persistence details | Session-backed prototype state; no database migration is required. | Running application walkthrough. |
| Authentication and authorization | Out of scope; the prototype has one trusted application/operator boundary. | Scope review and interface behavior. |
| Currency and formatting | One currency for the demonstration; currency code and display formatting are presentation concerns. | Statement examples and report. |
| Six individual contributors | Names and assignments are filled in by the coordinator. | `docs/report/contributions/`. |

Any change to these assumptions should update this document, the README, and
the affected tests or UML before final submission.