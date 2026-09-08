# Banking and Account Management System

A PHP Object-Oriented Programming (OOP) prototype for managing customers, bank accounts, and common banking transactions.

This repository contains the work for **Group 2 — Activity 2: Design and Develop an OOP-Based Banking System**.

> **Project status:** Planning and initial setup. Development work is organized in the [GitHub issue backlog](https://github.com/12mose/BANKING-AND-ACCOUNT-MANAGEMENT-SYSTEM/issues).

## Project overview

The system models a small financial institution where customers can own savings or current accounts. It is designed to demonstrate how OOP principles can protect sensitive account state, separate responsibilities, reuse shared behavior, and handle invalid banking operations safely.

The completed prototype will allow users to:

- Register a customer.
- Open a savings or current account.
- Deposit money.
- Withdraw money.
- Transfer money between accounts.
- Check an account balance.
- View transaction history.
- Generate an account statement.
- Close an account.

The agreed requirements, acceptance rules, deliverable ownership checklist,
and open design decisions are recorded in the
[requirements and final review checklist](docs/requirements-and-review-checklist.md).

## Learning objectives

The project demonstrates:

- Classes and objects
- Encapsulation and controlled state changes
- Inheritance and method specialization
- Abstraction and polymorphism
- Public, protected, and private access modifiers
- Typed properties, parameters, and return values
- Class constants for fixed business policies
- Exceptions and input validation
- Composition between domain objects
- Automated and manual testing

## Planned domain model

| Class or type | Responsibility |
| --- | --- |
| `Customer` | Stores validated customer details and manages account ownership. |
| `BankAccount` | Abstract base class containing common account state and behavior. |
| `SavingsAccount` | Applies savings-specific minimum-balance and withdrawal rules. |
| `CurrentAccount` | Applies current-account fee and balance or overdraft rules. |
| `Transaction` | Represents an immutable record of a banking operation. |
| `Bank` | Registers customers, opens accounts, resolves account numbers, and coordinates operations. |
| `AccountStatement` | Produces a readable statement from transaction records. |
| `AccountStatus` | Represents account lifecycle states such as active and closed. |
| `TransactionType` | Defines supported transaction categories. |
| Domain exceptions | Report invalid amounts, insufficient funds, missing accounts, and closed-account operations. |

The final design will contain at least eight classes or supporting OOP types, as required by the assignment.

## Core business rules

- Account numbers must be valid and unique.
- The account balance cannot be modified directly from outside the account class.
- Deposits, withdrawals, and transfers must use controlled public methods.
- Monetary amounts must be positive and valid.
- Account-specific minimum balances, withdrawal limits, fees, and overdraft policies must be enforced.
- A transfer must use existing, active source and destination accounts.
- A failed operation must not partially change balances or transaction history.
- Closed accounts retain their historical records but reject new financial operations.
- Each successful operation creates an auditable transaction record.

## Required transaction data

Every transaction record will include:

- Transaction ID
- Date and time
- Transaction type
- Amount
- Source account
- Destination account, when applicable
- Description

## Class constants

Fixed policy values will be expressed with meaningful class constants rather than unexplained values in method bodies. Planned examples include:

- `BANK_NAME`
- `MINIMUM_BALANCE`
- `TRANSFER_FEE`
- `MAX_WITHDRAWAL`

A constant is appropriate for these values because each represents a named policy shared by all relevant objects and should not change independently for a single instance.

## Validation and error handling

The application must handle the following scenarios without corrupting account data:

- Attempting to withdraw more than the permitted available balance
- Depositing a negative or zero amount
- Transferring to a non-existent account
- Using an invalid account number
- Attempting to operate on a closed account
- Supplying an inappropriate PHP value type
- Opening a duplicate account
- Transferring to the same account

Domain exceptions will provide clear messages, while the application interface will display safe error feedback without exposing internal stack traces.

## Technology

- PHP 8.1 or later
- Composer for autoloading and development dependencies
- PHPUnit for automated tests
- HTML and CSS for the demonstration interface
- PHP sessions for prototype data persistence, unless the final design selects another approach

## Planned project structure

```text
.
├── composer.json
├── public/
│   └── index.php
├── src/
│   ├── Account/
│   ├── Customer/
│   ├── Exception/
│   ├── Statement/
│   └── Transaction/
├── tests/
│   ├── Unit/
│   └── Integration/
├── docs/
│   ├── report/
│   ├── test-results/
│   └── uml/
├── screenshots/
└── README.md
```

The structure may be refined during implementation while keeping domain logic separate from the user interface.

## Getting started

The commands below will become fully operational as the setup and implementation issues are completed.

### Prerequisites

- PHP 8.1+
- Composer
- Git

### Installation

```bash
git clone https://github.com/12mose/BANKING-AND-ACCOUNT-MANAGEMENT-SYSTEM.git
cd BANKING-AND-ACCOUNT-MANAGEMENT-SYSTEM
composer install
```

### Run the application

```bash
php -S localhost:8000 -t public
```

Then open [http://localhost:8000](http://localhost:8000) in a browser.

### Run tests

```bash
composer test
```

If the Composer script is unavailable, run:

```bash
vendor/bin/phpunit
```

## Development workflow

1. Select an unassigned issue from the ordered backlog.
2. Assign the issue before starting work.
3. Create a focused branch using the issue number, for example `feature/07-savings-account`.
4. Keep business rules inside domain classes rather than page templates.
5. Add or update tests for every behavioral change.
6. Open a pull request that references the issue.
7. Request review and merge only after acceptance criteria and tests pass.
8. Update the individual contribution record with completed work and evidence.

Work should follow the numbered dependencies in the [issue backlog](https://github.com/12mose/BANKING-AND-ACCOUNT-MANAGEMENT-SYSTEM/issues).

## Testing strategy

Testing will include:

- Unit tests for each domain class
- Boundary tests for minimum balances, limits, and fees
- Integration tests for complete banking workflows
- Negative tests for every required error scenario
- Type-handling tests under strict PHP typing
- Manual interface checks with recorded expected and actual results

A failed transaction must leave all affected balances and transaction histories unchanged.

## Required deliverables

- [ ] Complete PHP source code
- [ ] Minimum eight classes or supporting OOP types
- [ ] UML class diagram
- [ ] Access modifier demonstration
- [ ] Class constant demonstration
- [ ] Property type-handling demonstration
- [ ] Test cases and results
- [ ] Technical report
- [ ] System screenshots
- [ ] Individual contribution report for each of the six group members
- [ ] Presentation
- [ ] Practical demonstration

## Definition of done

The project is complete when:

- Every required banking operation works through the application interface.
- All specified invalid operations are rejected safely.
- Unit and integration tests pass from a clean installation.
- The UML diagram matches the implemented classes.
- Documentation and screenshots match the final behavior.
- All required deliverables are present and ready for submission.
- The practical demonstration can be completed using documented sample data.

## Academic scope

This application is an educational prototype created to demonstrate PHP OOP concepts. It is not designed for production banking or the storage of real financial or personal data.
