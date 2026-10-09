<?php
/**
 * General Settings of the New Life modules (business rules).
 * Values live in the app_settings table; read with nlSetting('key').
 * The "Website" tab of the General Settings page is stored separately (web_settings).
 *
 * field types: text | number | select | switch
 */
return [
    'student' => [
        'title' => 'Student',
        'icon'  => 'bx-user-pin',
        'fields' => [
            'student_id_prefix' => ['label' => 'Student ID Prefix', 'type' => 'text', 'default' => 'STD', 'max' => 10,
                                    'help' => 'Student IDs look like STD-260001 (prefix + year + serial)'],
        ],
    ],
    'certificate' => [
        'title' => 'Certificate',
        'icon'  => 'bx-award',
        'fields' => [
            'certificate_no_prefix' => ['label' => 'Certificate Number Prefix', 'type' => 'text', 'default' => 'CERT', 'max' => 10,
                                        'help' => 'Generated numbers look like CERT-2026-00001'],
        ],
    ],
    'accounts' => [
        'title' => 'Accounts',
        'icon'  => 'bx-calculator',
        'fields' => [
            'auto_post_income' => ['label' => 'Post student fee payments to Accounts as income', 'type' => 'switch', 'default' => '1',
                                   'help' => 'Each payment creates an income voucher (head "Course Fee") in Basic Accounts'],
            'auto_post_out_income' => ['label' => 'Post Out of Student job fees to Accounts as income', 'type' => 'switch', 'default' => '1',
                                       'help' => 'Each out-student job creates an income voucher (head "Practical Test Fee") in Basic Accounts'],
        ],
    ],
];
