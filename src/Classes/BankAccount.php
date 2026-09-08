<?php
require_once __DIR__ . "/Customer.php";
class BankAccount{
    // constraints
    const MINIMUM_BALANCE = 500;
    const TRANSFER_FEE = 0.03;
    const MAX_WITHDRAWAL = 2000000;
    const BANK_NAME = "BNR";
    // Instance variables
    protected string $accountNumber;
    protected Customer $accountHolder;
    protected float $balance;
    protected string $accountType;
    protected string $status;
    // constructor
    function __construct(string $accountNumber, Customer $accountHolder, float $balance, string $accountType, string $status){
        $this->accountNumber = $accountNumber;
        $this->accountHolder = $accountHolder;
        $this->balance = $balance;
        $this->accountType = $accountType;
        $this->status = $status;
    }
    // Creating methods
    // checking account status
    static function checkStatus() : bool {
        if($this->status === "closed"){
            return false;
        }
        return true;
        
    }
    // DEPOSIT Function
    function deposit(float $amount) : void {
    //checking status
    if(!$this->checkStatus()){
        // exceptions
    throw new Exception("The account is closed");
    }
    // checking if amount is positive
    if($amount < 0 ){
        throw new Exception("Invalid amount");
    }
    // deposit amount
    $this->balance += $amount;

    }
}
$bankAccount1 = new BankAccount();
 ?>