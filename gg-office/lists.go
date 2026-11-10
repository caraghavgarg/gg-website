package main

import (
	"fmt"
	"strings"
)

var (
	ClientTypes = []string{"Individual", "HUF", "Proprietorship", "Partnership Firm", "LLP", "Company", "Trust / Society", "AOP / BOI", "Other"}
	WorkTypes   = []string{
		"Income Tax Return", "Tax Audit", "Statutory Audit", "Internal / Other Audit",
		"GST Return (GSTR-1 / 3B)", "GST Annual Return (GSTR-9 / 9C)", "GST Registration / Amendment",
		"TDS / TCS Return", "Advance Tax", "ROC / MCA Filing", "Accounting / Bookkeeping",
		"Project Report / CMA", "Trust / NGO (Form 105, 12A, 80G)", "Notice Reply", "Certificate", "Other",
	}
	WorkStatuses   = []string{"Not started", "Documents awaited", "In progress", "Under review", "Filed / Done", "On hold"}
	Priorities     = []string{"Normal", "High", "Urgent"}
	NoticeActs     = []string{"Income Tax", "GST", "TDS / TCS", "MCA / ROC", "Other"}
	NoticeStatuses = []string{"Received", "Documents awaited", "Reply drafting", "Replied", "Hearing fixed", "Order received", "Appeal filed", "Closed"}
	LeaveKinds     = []string{"Casual leave", "Sick leave", "Earned leave", "Half day", "Other"}
	Roles          = []string{"staff", "partner", "admin"}
)

func isDone(status string) bool {
	return status == "Filed / Done"
}

func noticeOpen(status string) bool {
	return status != "Closed"
}

// seedDueDates fills the calendar with the regular statutory dates for the next
// twelve months. Partners can add, change or delete any of them.
func seedDueDates(d *Data) {
	n := now()
	start := n.AddDate(0, 0, -n.Day()+1)
	type ann struct {
		m     int
		day   int
		title string
		law   string
	}
	annual := []ann{
		{6, 15, "Advance tax: 1st instalment (15%)", "Income Tax"},
		{9, 15, "Advance tax: 2nd instalment (45%)", "Income Tax"},
		{12, 15, "Advance tax: 3rd instalment (75%)", "Income Tax"},
		{3, 15, "Advance tax: last instalment (100%)", "Income Tax"},
		{7, 31, "TDS statement Q1 (Apr-Jun)", "TDS / TCS"},
		{10, 31, "TDS statement Q2 (Jul-Sep)", "TDS / TCS"},
		{1, 31, "TDS statement Q3 (Oct-Dec)", "TDS / TCS"},
		{5, 31, "TDS statement Q4 (Jan-Mar)", "TDS / TCS"},
		{7, 15, "TCS statement Q1", "TDS / TCS"},
		{10, 15, "TCS statement Q2", "TDS / TCS"},
		{1, 15, "TCS statement Q3", "TDS / TCS"},
		{5, 15, "TCS statement Q4", "TDS / TCS"},
		{7, 31, "Income tax return (non-audit cases)", "Income Tax"},
		{9, 30, "Tax audit report", "Income Tax"},
		{10, 31, "Income tax return (audit cases)", "Income Tax"},
		{12, 31, "Belated / revised income tax return", "Income Tax"},
		{12, 31, "GSTR-9 / 9C annual return", "GST"},
		{9, 30, "DIR-3 KYC of directors", "MCA / ROC"},
		{10, 29, "AOC-4 financial statements (AGM on 30 Sep)", "MCA / ROC"},
		{11, 29, "MGT-7 / 7A annual return (AGM on 30 Sep)", "MCA / ROC"},
		{5, 30, "LLP Form 11 annual return", "MCA / ROC"},
		{10, 30, "LLP Form 8 accounts and solvency", "MCA / ROC"},
		{4, 30, "TDS / TCS deposit for March", "TDS / TCS"},
	}
	for i := 0; i < 12; i++ {
		m := start.AddDate(0, i, 0)
		ym := m.Format("2006-01")
		add := func(day int, title, law string) {
			d.DueDates = append(d.DueDates, &DueDate{ID: d.NextID, Date: fmt.Sprintf("%s-%02d", ym, day), Title: title, Law: law})
			d.NextID++
		}
		if m.Month() != 4 {
			add(7, "TDS / TCS deposit for previous month", "TDS / TCS")
		}
		add(11, "GSTR-1 (monthly filers)", "GST")
		add(13, "GSTR-1 IFF (QRMP, optional)", "GST")
		add(15, "PF and ESI contribution", "Labour")
		add(20, "GSTR-3B (monthly filers)", "GST")
		add(25, "PMT-06 GST payment (QRMP)", "GST")
		for _, a := range annual {
			if int(m.Month()) == a.m {
				add(a.day, a.title, a.law)
			}
		}
	}
}

func lower(s string) string { return strings.ToLower(s) }
