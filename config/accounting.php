<?php

return [
    'accounts' => [
        '1000' => [
            'name' => 'Cash',
            'account_type' => 'asset',
            'normal_balance' => 'debit',
            'description' => 'Cash received and refunded at the store counter.',
        ],
        '1010' => [
            'name' => 'Card Receivable',
            'account_type' => 'asset',
            'normal_balance' => 'debit',
            'description' => 'Amounts awaiting settlement from card providers.',
        ],
        '1020' => [
            'name' => 'UPI Receivable',
            'account_type' => 'asset',
            'normal_balance' => 'debit',
            'description' => 'Amounts awaiting settlement from UPI providers.',
        ],
        '1030' => [
            'name' => 'Mixed Payment Receivable',
            'account_type' => 'asset',
            'normal_balance' => 'debit',
            'description' => 'Payment amounts recorded as mixed tender until reconciled.',
        ],
        '1050' => [
            'name' => 'Bank Settlement Account',
            'account_type' => 'asset',
            'normal_balance' => 'debit',
            'description' => 'Bank value received when card or UPI settlements are reconciled.',
        ],
        '1060' => [
            'name' => 'Supplier Payment Bank Account',
            'account_type' => 'asset',
            'normal_balance' => 'debit',
            'description' => 'Bank value paid to suppliers through transfers, cheques, or digital payments.',
        ],
        '1100' => [
            'name' => 'Inventory Asset',
            'account_type' => 'asset',
            'normal_balance' => 'debit',
            'description' => 'Cost value of saleable stock on hand.',
        ],
        '1200' => [
            'name' => 'Input Tax Credit',
            'account_type' => 'asset',
            'normal_balance' => 'debit',
            'description' => 'Recoverable tax recorded on supplier invoices.',
        ],
        '2000' => [
            'name' => 'Accounts Payable',
            'account_type' => 'liability',
            'normal_balance' => 'credit',
            'description' => 'Amounts owed to suppliers for received stock.',
        ],
        '2020' => [
            'name' => 'Customer Credit Liability',
            'account_type' => 'liability',
            'normal_balance' => 'credit',
            'description' => 'Customer credit created when a return is not fully refunded immediately.',
        ],
        '2100' => [
            'name' => 'Output Tax Payable',
            'account_type' => 'liability',
            'normal_balance' => 'credit',
            'description' => 'Tax collected on finalized sales.',
        ],
        '4000' => [
            'name' => 'Sales Revenue',
            'account_type' => 'revenue',
            'normal_balance' => 'credit',
            'description' => 'Net product revenue after line discounts.',
        ],
        '5000' => [
            'name' => 'Cost of Goods Sold',
            'account_type' => 'expense',
            'normal_balance' => 'debit',
            'description' => 'Cost of inventory issued through sales.',
        ],
        '6000' => [
            'name' => 'Payment Processing Fees',
            'account_type' => 'expense',
            'normal_balance' => 'debit',
            'description' => 'Fees withheld by card or payment providers at settlement.',
        ],
    ],

    'payment_account_codes' => [
        'cash' => '1000',
        'card' => '1010',
        'upi' => '1020',
        'mixed' => '1030',
        'store_credit' => '2020',
    ],

    'supplier_payment_account_codes' => [
        'cash' => '1000',
        'bank_transfer' => '1060',
        'upi' => '1060',
        'cheque' => '1060',
        'other' => '1060',
    ],
];
