package main

import (
	"encoding/csv"
	"fmt"
	"net/http"
	"net/url"
	"sort"
	"strings"
	"time"
)

func urlq(s string) string { return url.QueryEscape(s) }

// ---------------- attendance ----------------

type attRow struct {
	Date, Day     string
	In, Out, Note string
	Hours         string
	Leave         string
	Sunday        bool
}

func hoursBetween(in, out string) string {
	a, e1 := time.Parse("15:04", in)
	b, e2 := time.Parse("15:04", out)
	if e1 != nil || e2 != nil || !b.After(a) {
		return ""
	}
	d := b.Sub(a)
	return fmt.Sprintf("%d:%02d", int(d.Hours()), int(d.Minutes())%60)
}

func (a *App) attendance(w http.ResponseWriter, r *http.Request, u *User) {
	a.s.mu.RLock()
	defer a.s.mu.RUnlock()
	d := a.s.D
	m := r.URL.Query().Get("m")
	if _, ok := parseDay(m + "-01"); !ok {
		m = now().Format("2006-01")
	}
	uid := u.ID
	if isPartner(u) {
		if v := fidQ(r, "u"); v > 0 {
			uid = v
		}
	}
	who := d.user(uid)
	if who == nil {
		who = u
	}
	start, _ := parseDay(m + "-01")
	var rows []attRow
	present, leaves := 0, 0
	for dt := start; dt.Format("2006-01") == m; dt = dt.AddDate(0, 0, 1) {
		ds := dt.Format("2006-01-02")
		if ds > today() {
			break
		}
		row := attRow{Date: ds, Day: dt.Format("Mon"), Sunday: dt.Weekday() == time.Sunday}
		for _, x := range d.Attendance {
			if x.UserID == uid && x.Date == ds {
				row.In, row.Out, row.Note = x.In, x.Out, x.Note
				row.Hours = hoursBetween(x.In, x.Out)
			}
		}
		for _, l := range d.Leaves {
			if l.UserID == uid && l.Status == "Approved" && l.From <= ds && l.To >= ds {
				row.Leave = l.Kind
			}
		}
		if row.In != "" {
			present++
		}
		if row.Leave != "" {
			leaves++
		}
		rows = append(rows, row)
	}
	// newest day first
	for i, j := 0, len(rows)-1; i < j; i, j = i+1, j-1 {
		rows[i], rows[j] = rows[j], rows[i]
	}
	var todayAll []teamRow
	if isPartner(u) {
		t := today()
		for _, s := range d.activeUsers() {
			tr := teamRow{User: s}
			for _, x := range d.Attendance {
				if x.UserID == s.ID && x.Date == t {
					tr.In, tr.Out = x.In, x.Out
				}
			}
			for _, l := range d.Leaves {
				if l.UserID == s.ID && l.Status == "Approved" && l.From <= t && l.To >= t {
					tr.OnLeave = true
				}
			}
			todayAll = append(todayAll, tr)
		}
	}
	var mine *Attendance
	for _, x := range d.Attendance {
		if x.UserID == u.ID && x.Date == today() {
			mine = x
		}
	}
	a.render(w, r, "attendance", &Page{Title: "Attendance", Nav: "attendance", V: map[string]any{
		"Rows": rows, "Who": who, "M": m, "Month": start.Format("January 2006"),
		"Prev": start.AddDate(0, -1, 0).Format("2006-01"), "Next": start.AddDate(0, 1, 0).Format("2006-01"),
		"Present": present, "Leaves": leaves, "Users": d.activeUsers(), "TodayAll": todayAll, "Mine": mine,
	}})
}

func fidQ(r *http.Request, k string) int64 {
	var n int64
	fmt.Sscan(r.URL.Query().Get(k), &n)
	return n
}

func (a *App) punch(w http.ResponseWriter, r *http.Request, u *User) {
	a.s.mu.Lock()
	defer a.s.mu.Unlock()
	t, hm := today(), now().Format("15:04")
	var rec *Attendance
	for _, x := range a.s.D.Attendance {
		if x.UserID == u.ID && x.Date == t {
			rec = x
		}
	}
	msg := ""
	switch f(r, "what") {
	case "in":
		if rec == nil {
			a.s.D.Attendance = append(a.s.D.Attendance, &Attendance{ID: a.s.newID(), UserID: u.ID, Date: t, In: hm})
			msg = "Checked in at " + hm + "."
		}
	case "out":
		if rec != nil {
			rec.Out = hm
			msg = "Checked out at " + hm + "."
		}
	}
	a.s.save()
	back(w, r, safeNext(f(r, "back")), msg)
}

func (a *App) attEdit(w http.ResponseWriter, r *http.Request, u *User) {
	uid, dt := fid(r, "u"), f(r, "date")
	if _, ok := parseDay(dt); !ok {
		back(w, r, "/attendance", "Choose a date.")
		return
	}
	in, out, note := f(r, "in"), f(r, "out"), f(r, "note")
	a.s.mu.Lock()
	defer a.s.mu.Unlock()
	var rec *Attendance
	idx := -1
	for i, x := range a.s.D.Attendance {
		if x.UserID == uid && x.Date == dt {
			rec, idx = x, i
		}
	}
	if in == "" && out == "" && note == "" {
		if idx >= 0 {
			a.s.D.Attendance = append(a.s.D.Attendance[:idx], a.s.D.Attendance[idx+1:]...)
		}
	} else if rec == nil {
		a.s.D.Attendance = append(a.s.D.Attendance, &Attendance{ID: a.s.newID(), UserID: uid, Date: dt, In: in, Out: out, Note: note})
	} else {
		rec.In, rec.Out, rec.Note = in, out, note
	}
	a.s.save()
	back(w, r, fmt.Sprintf("/attendance?u=%d&m=%s", uid, dt[:7]), "Attendance corrected for "+fmtDate(dt)+".")
}

func (a *App) attExport(w http.ResponseWriter, r *http.Request, u *User) {
	a.s.mu.RLock()
	defer a.s.mu.RUnlock()
	m := r.URL.Query().Get("m")
	if _, ok := parseDay(m + "-01"); !ok {
		m = now().Format("2006-01")
	}
	csvHead(w, "attendance-"+m+".csv")
	cw := csv.NewWriter(w)
	cw.Write([]string{"Staff", "Date", "In", "Out", "Hours", "Leave", "Note"})
	start, _ := parseDay(m + "-01")
	for _, s := range a.s.D.activeUsers() {
		for dt := start; dt.Format("2006-01") == m; dt = dt.AddDate(0, 0, 1) {
			ds := dt.Format("2006-01-02")
			var in, out, note, lv string
			for _, x := range a.s.D.Attendance {
				if x.UserID == s.ID && x.Date == ds {
					in, out, note = x.In, x.Out, x.Note
				}
			}
			for _, l := range a.s.D.Leaves {
				if l.UserID == s.ID && l.Status == "Approved" && l.From <= ds && l.To >= ds {
					lv = l.Kind
				}
			}
			cw.Write([]string{s.Name, ds, in, out, hoursBetween(in, out), lv, note})
		}
	}
	cw.Flush()
}

// ---------------- leave ----------------

func (a *App) leaveList(w http.ResponseWriter, r *http.Request, u *User) {
	a.s.mu.RLock()
	defer a.s.mu.RUnlock()
	var list []*Leave
	for _, l := range a.s.D.Leaves {
		if isPartner(u) || l.UserID == u.ID {
			list = append(list, l)
		}
	}
	sort.SliceStable(list, func(i, j int) bool {
		pi, pj := list[i].Status == "Pending", list[j].Status == "Pending"
		if pi != pj {
			return pi
		}
		return list[i].From > list[j].From
	})
	if len(list) > 300 {
		list = list[:300]
	}
	a.render(w, r, "leave", &Page{Title: "Leave", Nav: "leave", V: map[string]any{"List": list, "Kinds": LeaveKinds}})
}

func (a *App) leaveApply(w http.ResponseWriter, r *http.Request, u *User) {
	from, to, kind, reason := f(r, "from"), f(r, "to"), f(r, "kind"), f(r, "reason")
	if to == "" {
		to = from
	}
	_, ok1 := parseDay(from)
	_, ok2 := parseDay(to)
	if !ok1 || !ok2 || to < from {
		back(w, r, "/leave", "Enter the leave dates (the 'to' date cannot be before the 'from' date).")
		return
	}
	a.s.mu.Lock()
	a.s.D.Leaves = append(a.s.D.Leaves, &Leave{ID: a.s.newID(), UserID: u.ID, From: from, To: to, Kind: kind, Reason: reason, Status: "Pending", Created: stamp()})
	a.s.save()
	a.s.mu.Unlock()
	back(w, r, "/leave", "Leave request sent to the partners.")
}

func (a *App) leaveCancel(w http.ResponseWriter, r *http.Request, u *User) {
	a.s.mu.Lock()
	defer a.s.mu.Unlock()
	for i, l := range a.s.D.Leaves {
		if l.ID == pid(r) && l.UserID == u.ID && l.Status == "Pending" {
			a.s.D.Leaves = append(a.s.D.Leaves[:i], a.s.D.Leaves[i+1:]...)
			break
		}
	}
	a.s.save()
	back(w, r, "/leave", "Leave request withdrawn.")
}

func (a *App) leaveDecide(w http.ResponseWriter, r *http.Request, u *User) {
	a.s.mu.Lock()
	defer a.s.mu.Unlock()
	st := f(r, "status")
	for _, l := range a.s.D.Leaves {
		if l.ID == pid(r) && (st == "Approved" || st == "Rejected" || st == "Pending") {
			l.Status, l.DecidedBy = st, u.ID
		}
	}
	a.s.save()
	back(w, r, safeNext(f(r, "back")), "Leave "+strings.ToLower(st)+".")
}

// ---------------- daily work log ----------------

func (a *App) logList(w http.ResponseWriter, r *http.Request, u *User) {
	a.s.mu.RLock()
	defer a.s.mu.RUnlock()
	dt := r.URL.Query().Get("d")
	if _, ok := parseDay(dt); !ok {
		dt = today()
	}
	uid := u.ID
	if isPartner(u) {
		uid = fidQ(r, "u") // 0 = everyone
		if !r.URL.Query().Has("u") {
			uid = 0
		}
	}
	var list []*WorkLog
	for _, l := range a.s.D.Logs {
		if l.Date == dt && (uid == 0 || l.UserID == uid) {
			list = append(list, l)
		}
	}
	sort.SliceStable(list, func(i, j int) bool {
		if list[i].UserID != list[j].UserID {
			return lower(a.s.D.userName(list[i].UserID)) < lower(a.s.D.userName(list[j].UserID))
		}
		return list[i].ID < list[j].ID
	})
	pd, _ := parseDay(dt)
	a.render(w, r, "log", &Page{Title: "Daily work log", Nav: "log", V: map[string]any{
		"List": list, "Date": dt, "U": uid, "Users": a.s.D.activeUsers(), "Clients": a.s.D.sortedClients(),
		"Prev": pd.AddDate(0, 0, -1).Format("2006-01-02"), "Next": pd.AddDate(0, 0, 1).Format("2006-01-02"),
	}})
}

func (a *App) logAdd(w http.ResponseWriter, r *http.Request, u *User) {
	dt, text := f(r, "date"), f(r, "text")
	if _, ok := parseDay(dt); !ok {
		dt = today()
	}
	if text == "" {
		back(w, r, "/log?d="+dt, "Write what you worked on.")
		return
	}
	a.s.mu.Lock()
	a.s.D.Logs = append(a.s.D.Logs, &WorkLog{ID: a.s.newID(), UserID: u.ID, Date: dt, ClientID: fid(r, "client_id"), Hours: f(r, "hours"), Text: text, Created: stamp()})
	a.s.save()
	a.s.mu.Unlock()
	to := "/log?d=" + dt
	if isPartner(u) {
		to += fmt.Sprintf("&u=%d", u.ID)
	}
	back(w, r, to, "Added to your work log.")
}

func (a *App) logDelete(w http.ResponseWriter, r *http.Request, u *User) {
	a.s.mu.Lock()
	defer a.s.mu.Unlock()
	dt := today()
	for i, l := range a.s.D.Logs {
		if l.ID == pid(r) && (l.UserID == u.ID || isPartner(u)) {
			dt = l.Date
			a.s.D.Logs = append(a.s.D.Logs[:i], a.s.D.Logs[i+1:]...)
			break
		}
	}
	a.s.save()
	back(w, r, "/log?d="+dt, "Entry removed.")
}
