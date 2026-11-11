<?php
if (!defined('GGO')) { http_response_code(403); exit; }

const CLIENT_TYPES = array('Individual', 'HUF', 'Proprietorship', 'Partnership Firm', 'LLP', 'Company', 'Trust / Society', 'AOP / BOI', 'Other');
const WORK_TYPES = array(
    'Income Tax Return', 'Tax Audit', 'Statutory Audit', 'Internal / Other Audit',
    'GST Return (GSTR-1 / 3B)', 'GST Annual Return (GSTR-9 / 9C)', 'GST Registration / Amendment',
    'TDS / TCS Return', 'Advance Tax', 'ROC / MCA Filing', 'Accounting / Bookkeeping',
    'Project Report / CMA', 'Trust / NGO (Form 105, 12A, 80G)', 'Notice Reply', 'Certificate', 'Other',
);
const WORK_STATUSES = array('Not started', 'Documents awaited', 'In progress', 'Under review', 'Filed / Done', 'On hold');
const PRIORITIES = array('Normal', 'High', 'Urgent');
const NOTICE_ACTS = array('Income Tax', 'GST', 'TDS / TCS', 'MCA / ROC', 'Other');
const NOTICE_STATUSES = array('Received', 'Documents awaited', 'Reply drafting', 'Replied', 'Hearing fixed', 'Order received', 'Appeal filed', 'Closed');
const LEAVE_KINDS = array('Casual leave', 'Sick leave', 'Earned leave', 'Half day', 'Other');
const LAWS = array('Income Tax', 'GST', 'TDS / TCS', 'MCA / ROC', 'Labour', 'Other');

/** Regular statutory dates for the next twelve months; partners can change them. */
function seed_due_dates(&$d) {
    $annual = array(
        array(6, 15, 'Advance tax: 1st instalment (15%)', 'Income Tax'),
        array(9, 15, 'Advance tax: 2nd instalment (45%)', 'Income Tax'),
        array(12, 15, 'Advance tax: 3rd instalment (75%)', 'Income Tax'),
        array(3, 15, 'Advance tax: last instalment (100%)', 'Income Tax'),
        array(7, 31, 'TDS statement Q1 (Apr-Jun)', 'TDS / TCS'),
        array(10, 31, 'TDS statement Q2 (Jul-Sep)', 'TDS / TCS'),
        array(1, 31, 'TDS statement Q3 (Oct-Dec)', 'TDS / TCS'),
        array(5, 31, 'TDS statement Q4 (Jan-Mar)', 'TDS / TCS'),
        array(7, 15, 'TCS statement Q1', 'TDS / TCS'),
        array(10, 15, 'TCS statement Q2', 'TDS / TCS'),
        array(1, 15, 'TCS statement Q3', 'TDS / TCS'),
        array(5, 15, 'TCS statement Q4', 'TDS / TCS'),
        array(7, 31, 'Income tax return (non-audit cases)', 'Income Tax'),
        array(9, 30, 'Tax audit report', 'Income Tax'),
        array(10, 31, 'Income tax return (audit cases)', 'Income Tax'),
        array(12, 31, 'Belated / revised income tax return', 'Income Tax'),
        array(12, 31, 'GSTR-9 / 9C annual return', 'GST'),
        array(9, 30, 'DIR-3 KYC of directors', 'MCA / ROC'),
        array(10, 29, 'AOC-4 financial statements (AGM on 30 Sep)', 'MCA / ROC'),
        array(11, 29, 'MGT-7 / 7A annual return (AGM on 30 Sep)', 'MCA / ROC'),
        array(5, 30, 'LLP Form 11 annual return', 'MCA / ROC'),
        array(10, 30, 'LLP Form 8 accounts and solvency', 'MCA / ROC'),
        array(4, 30, 'TDS / TCS deposit for March', 'TDS / TCS'),
    );
    $start = strtotime(date('Y-m-01'));
    for ($i = 0; $i < 12; $i++) {
        $m = strtotime("+$i month", $start);
        $ym = date('Y-m', $m);
        $mon = (int) date('n', $m);
        $add = function ($day, $title, $law) use (&$d, $ym) {
            $d['due_dates'][] = array('id' => new_id($d), 'date' => sprintf('%s-%02d', $ym, $day), 'title' => $title, 'law' => $law, 'notes' => '');
        };
        if ($mon !== 4) { $add(7, 'TDS / TCS deposit for previous month', 'TDS / TCS'); }
        $add(11, 'GSTR-1 (monthly filers)', 'GST');
        $add(13, 'GSTR-1 IFF (QRMP, optional)', 'GST');
        $add(15, 'PF and ESI contribution', 'Labour');
        $add(20, 'GSTR-3B (monthly filers)', 'GST');
        $add(25, 'PMT-06 GST payment (QRMP)', 'GST');
        foreach ($annual as $a) { if ($a[0] === $mon) { $add($a[1], $a[2], $a[3]); } }
    }
}

function sort_work(&$ws) {
    usort($ws, function ($a, $b) {
        $da = is_done($a['status']); $db = is_done($b['status']);
        if ($da !== $db) { return $da ? 1 : -1; }
        $ea = $a['due'] === ''; $eb = $b['due'] === '';
        if ($ea !== $eb) { return $ea ? 1 : -1; }
        if ($a['due'] !== $b['due']) { return strcmp($a['due'], $b['due']); }
        return $a['id'] - $b['id'];
    });
}

function notice_key($n) {
    $k = $n['reply_due'];
    if ($n['hearing'] !== '' && ($k === '' || $n['hearing'] < $k)) { $k = $n['hearing']; }
    return $k;
}
function sort_notices(&$ns) {
    usort($ns, function ($a, $b) {
        $oa = notice_open($a['status']); $ob = notice_open($b['status']);
        if ($oa !== $ob) { return $oa ? -1 : 1; }
        $ka = notice_key($a); $kb = notice_key($b);
        if (($ka === '') !== ($kb === '')) { return $ka === '' ? 1 : -1; }
        if ($ka !== $kb) { return strcmp($ka, $kb); }
        return $b['id'] - $a['id'];
    });
}

function has_text($hay, $needle) { return $needle === '' || mb_stripos((string) $hay, $needle) !== false; }
