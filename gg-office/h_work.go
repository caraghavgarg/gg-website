package main

import (
	"encoding/csv"
	"fmt"
	"io"
	"net/http"
	"sort"
	"strconv"
	"strings"
)

// ---------------- sorting ----------------

func sortWork(ws []*Work) {
	sort.SliceStable(ws, func(i, j int) bool {
		a, b := ws[i], ws[j]
		if isDone(a.Status) != isDone(b.Status) {
			return !isDone(a.Status)
		}
		if (a.Due == "") != (b.Due == "") {
			return a.Due != ""
		}
		if a.Due != b.Due {
			return a.Due < b.Due
		}
		return a.ID < b.ID
	})
}

func sortNotices(ns []*Notice) {
	sort.SliceStable(ns, func(i, j int) bool {
		a, b := ns[i], ns[j]
		if noticeOpen(a.Status) != noticeOpen(b.Status) {
			return noticeOpen(a.Status)
		}
		ad, bd := a.ReplyDue, b.ReplyDue
		if a.Hearing != "" && (ad == "" || a.Hearing < ad) {
			ad = a.Hearing
		}
		if b.Hearing != "" && (bd == "" || b.Hearing < bd) {
			bd = b.Hearing
		}
		if (ad == "") != (bd == "") {
			return ad != ""
		}
		if ad != bd {
			return ad < bd
		}
		return a.ID > b.ID
	})
}

func has(hay, needle string) bool { return strings.Contains(lower(hay), needle) }

// ---------------- clients ----------------

func (a *App) clientList(w http.ResponseWriter, r *http.Request, u *User) {
	a.s.mu.RLock()
	defer a.s.mu.RUnlock()
	q, typ, show := lower(f(r, "q")), f(r, "type"), f(r, "show")
	var list []*Client
	for _, c := range a.s.D.sortedClients() {
		if show == "" && !c.Active {
			continue
		}
		if typ != "" && c.Type != typ {
			continue
		}
		if q != "" && !(has(c.Name, q) || has(c.PAN, q) || has(c.GSTIN, q) || has(c.FileNo, q) || has(c.Group, q) || has(c.Phone, q)) {
			continue
		}
		list = append(list, c)
	}
	open := map[int64]int{}
	for _, x := range a.s.D.Works {
		if !isDone(x.Status) {
			open[x.ClientID]++
		}
	}
	a.render(w, r, "clients", &Page{Title: "Clients", Nav: "clients", V: map[string]any{
		"List": list, "Q": f(r, "q"), "Type": typ, "Show": show, "Types": ClientTypes, "Open": open, "Total": len(a.s.D.Clients),
	}})
}

func (a *App) clientView(w http.ResponseWriter, r *http.Request, u *User) {
	a.s.mu.RLock()
	defer a.s.mu.RUnlock()
	c := a.s.D.client(pid(r))
	if c == nil {
		http.NotFound(w, r)
		return
	}
	var ws []*Work
	for _, x := range a.s.D.Works {
		if x.ClientID == c.ID {
			ws = append(ws, x)
		}
	}
	sortWork(ws)
	var ns []*Notice
	for _, n := range a.s.D.Notices {
		if n.ClientID == c.ID {
			ns = append(ns, n)
		}
	}
	sortNotices(ns)
	a.render(w, r, "client_view", &Page{Title: c.Name, Nav: "clients", V: map[string]any{"C": c, "Works": ws, "Notices": ns, "Statuses": WorkStatuses}})
}

func (a *App) clientForm(w http.ResponseWriter, r *http.Request, u *User) {
	a.s.mu.RLock()
	defer a.s.mu.RUnlock()
	c := &Client{Active: true, Type: "Individual"}
	if id := pid(r); id > 0 {
		if c = a.s.D.client(id); c == nil {
			http.NotFound(w, r)
			return
		}
	}
	a.render(w, r, "client_form", &Page{Title: "Client", Nav: "clients", V: map[string]any{"C": c, "Types": ClientTypes, "Users": a.s.D.activeUsers()}})
}

func (a *App) clientSave(w http.ResponseWriter, r *http.Request, u *User) {
	a.s.mu.Lock()
	defer a.s.mu.Unlock()
	d := a.s.D
	id := pid(r)
	c := &Client{}
	if id > 0 {
		if c = d.client(id); c == nil {
			http.NotFound(w, r)
			return
		}
	}
	t := *c
	t.Name, t.FileNo, t.Type = f(r, "name"), f(r, "file_no"), f(r, "type")
	t.PAN, t.GSTIN, t.TAN, t.CIN = strings.ToUpper(f(r, "pan")), strings.ToUpper(f(r, "gstin")), strings.ToUpper(f(r, "tan")), strings.ToUpper(f(r, "cin"))
	t.Phone, t.Email, t.Address, t.Group, t.Notes = f(r, "phone"), f(r, "email"), f(r, "address"), f(r, "group"), f(r, "notes")
	t.Manager = fid(r, "manager")
	t.Active = r.FormValue("active") == "1"
	msg := ""
	if t.Name == "" {
		msg = "Enter the client's name."
	}
	if t.PAN != "" {
		for _, o := range d.Clients {
			if o.ID != c.ID && o.PAN == t.PAN {
				msg = "PAN " + t.PAN + " is already saved for " + o.Name + "."
			}
		}
	}
	if msg != "" {
		a.render(w, r, "client_form", &Page{Title: "Client", Nav: "clients", Error: msg, V: map[string]any{"C": &t, "Types": ClientTypes, "Users": d.activeUsers()}})
		return
	}
	if id == 0 {
		t.ID = a.s.newID()
		t.Created = stamp()
		d.Clients = append(d.Clients, &t)
	} else {
		*c = t
	}
	a.s.save()
	back(w, r, fmt.Sprintf("/clients/%d", t.ID), "Client saved.")
}

func (a *App) clientDelete(w http.ResponseWriter, r *http.Request, u *User) {
	a.s.mu.Lock()
	defer a.s.mu.Unlock()
	d := a.s.D
	id := pid(r)
	for _, x := range d.Works {
		if x.ClientID == id {
			back(w, r, fmt.Sprintf("/clients/%d", id), "This client has work entries, so it cannot be deleted. Untick 'Active client' instead.")
			return
		}
	}
	for _, x := range d.Notices {
		if x.ClientID == id {
			back(w, r, fmt.Sprintf("/clients/%d", id), "This client has notices, so it cannot be deleted. Untick 'Active client' instead.")
			return
		}
	}
	for i, c := range d.Clients {
		if c.ID == id {
			d.Clients = append(d.Clients[:i], d.Clients[i+1:]...)
			break
		}
	}
	a.s.save()
	back(w, r, "/clients", "Client deleted.")
}

// ---------------- client CSV import / export ----------------

var clientCols = []string{"Name", "File No", "Type", "PAN", "GSTIN", "TAN", "CIN", "Phone", "Email", "Address", "Group", "Notes"}

func (a *App) clientTemplate(w http.ResponseWriter, r *http.Request, u *User) {
	csvHead(w, "clients-template.csv")
	cw := csv.NewWriter(w)
	cw.Write(clientCols)
	cw.Write([]string{"Ramesh Kumar", "101", "Individual", "ABCDE1234F", "", "", "", "9812345678", "ramesh@example.com", "Sector 7, Kurukshetra", "Kumar family", ""})
	cw.Flush()
}

func (a *App) clientExport(w http.ResponseWriter, r *http.Request, u *User) {
	a.s.mu.RLock()
	defer a.s.mu.RUnlock()
	csvHead(w, "clients-"+today()+".csv")
	cw := csv.NewWriter(w)
	cw.Write(append(clientCols, "Manager", "Active"))
	for _, c := range a.s.D.sortedClients() {
		cw.Write([]string{c.Name, c.FileNo, c.Type, c.PAN, c.GSTIN, c.TAN, c.CIN, c.Phone, c.Email, c.Address, c.Group, c.Notes, a.s.D.userName(c.Manager), yn(c.Active)})
	}
	cw.Flush()
}

func (a *App) clientImportForm(w http.ResponseWriter, r *http.Request, u *User) {
	a.s.mu.RLock()
	defer a.s.mu.RUnlock()
	a.render(w, r, "client_import", &Page{Title: "Import clients", Nav: "clients", V: map[string]any{"Cols": clientCols}})
}

func (a *App) clientImport(w http.ResponseWriter, r *http.Request, u *User) {
	file, _, err := r.FormFile("file")
	if err != nil {
		back(w, r, "/clients/import", "Choose a CSV file first.")
		return
	}
	defer file.Close()
	raw, _ := io.ReadAll(io.LimitReader(file, 20<<20))
	raw = []byte(strings.TrimPrefix(string(raw), "\xef\xbb\xbf"))
	cr := csv.NewReader(strings.NewReader(string(raw)))
	cr.FieldsPerRecord = -1
	cr.LazyQuotes = true
	rows, err := cr.ReadAll()
	if err != nil || len(rows) < 2 {
		back(w, r, "/clients/import", "Could not read that file. Save it from Excel as 'CSV UTF-8' and try again.")
		return
	}
	idx := map[string]int{}
	for i, h := range rows[0] {
		k := strings.NewReplacer(" ", "", "_", "", ".", "").Replace(lower(strings.TrimSpace(h)))
		switch k {
		case "name", "clientname", "nameofclient", "partyname":
			k = "name"
		case "fileno", "file", "filenumber", "code", "clientcode":
			k = "fileno"
		case "type", "status", "constitution", "category":
			k = "type"
		case "phone", "mobile", "mobileno", "phoneno", "contact":
			k = "phone"
		case "email", "emailid", "mail":
			k = "email"
		case "gstin", "gst", "gstno":
			k = "gstin"
		case "pan", "panno":
			k = "pan"
		}
		if _, ok := idx[k]; !ok {
			idx[k] = i
		}
	}
	if _, ok := idx["name"]; !ok {
		back(w, r, "/clients/import", "The first row must have a 'Name' column.")
		return
	}
	get := func(row []string, k string) string {
		if i, ok := idx[k]; ok && i < len(row) {
			return strings.TrimSpace(row[i])
		}
		return ""
	}
	a.s.mu.Lock()
	defer a.s.mu.Unlock()
	d := a.s.D
	added, skipped := 0, 0
	for _, row := range rows[1:] {
		name := get(row, "name")
		if name == "" {
			continue
		}
		pan := strings.ToUpper(get(row, "pan"))
		dup := false
		for _, c := range d.Clients {
			if (pan != "" && c.PAN == pan) || (pan == "" && lower(c.Name) == lower(name)) {
				dup = true
				break
			}
		}
		if dup {
			skipped++
			continue
		}
		typ := get(row, "type")
		match := "Other"
		for _, t := range ClientTypes {
			if lower(t) == lower(typ) || (typ != "" && strings.HasPrefix(lower(t), lower(typ))) {
				match = t
				break
			}
		}
		if typ == "" {
			match = "Individual"
		}
		d.Clients = append(d.Clients, &Client{
			ID: a.s.newID(), Name: name, FileNo: get(row, "fileno"), Type: match, PAN: pan,
			GSTIN: strings.ToUpper(get(row, "gstin")), TAN: strings.ToUpper(get(row, "tan")), CIN: strings.ToUpper(get(row, "cin")),
			Phone: get(row, "phone"), Email: get(row, "email"), Address: get(row, "address"), Group: get(row, "group"), Notes: get(row, "notes"),
			Active: true, Created: stamp(),
		})
		added++
	}
	a.s.save()
	back(w, r, "/clients", fmt.Sprintf("Imported %d clients. %d were already in the list and were skipped.", added, skipped))
}

func csvHead(w http.ResponseWriter, name string) {
	w.Header().Set("Content-Type", "text/csv; charset=utf-8")
	w.Header().Set("Content-Disposition", `attachment; filename="`+name+`"`)
	w.Write([]byte("\xef\xbb\xbf")) // so Excel reads Hindi / ₹ correctly
}

func yn(b bool) string {
	if b {
		return "Yes"
	}
	return "No"
}

// ---------------- work ----------------

type workFilter struct {
	Who, Status, Type, Q, Due, Client string
}

func (a *App) filterWork(r *http.Request, u *User) (workFilter, []*Work) {
	fl := workFilter{Who: r.URL.Query().Get("who"), Status: r.URL.Query().Get("status"), Type: r.URL.Query().Get("type"), Q: f(r, "q"), Due: r.URL.Query().Get("due"), Client: r.URL.Query().Get("client")}
	if !r.URL.Query().Has("who") {
		if isPartner(u) {
			fl.Who = "all"
		} else {
			fl.Who = "me"
		}
	}
	if !r.URL.Query().Has("status") {
		fl.Status = "open"
	}
	q := lower(fl.Q)
	t := today()
	var out []*Work
	for _, x := range a.s.D.Works {
		switch fl.Who {
		case "all":
		case "me":
			if x.Assigned != u.ID {
				continue
			}
		case "none":
			if x.Assigned != 0 {
				continue
			}
		default:
			if fmt.Sprint(x.Assigned) != fl.Who {
				continue
			}
		}
		switch fl.Status {
		case "", "any":
		case "open":
			if isDone(x.Status) {
				continue
			}
		default:
			if x.Status != fl.Status {
				continue
			}
		}
		if fl.Type != "" && x.Type != fl.Type {
			continue
		}
		if fl.Client != "" && fmt.Sprint(x.ClientID) != fl.Client {
			continue
		}
		switch fl.Due {
		case "overdue":
			if x.Due == "" || x.Due >= t || isDone(x.Status) {
				continue
			}
		case "week":
			if x.Due == "" || x.Due > dayAdd(7) {
				continue
			}
		case "month":
			if x.Due == "" || x.Due > dayAdd(31) {
				continue
			}
		case "nodate":
			if x.Due != "" {
				continue
			}
		}
		if q != "" {
			c := a.s.D.client(x.ClientID)
			hay := x.Period + " " + x.Remarks + " " + x.AckNo + " " + x.Type
			if c != nil {
				hay += " " + c.Name + " " + c.PAN + " " + c.FileNo + " " + c.Group
			}
			if !has(hay, q) {
				continue
			}
		}
		out = append(out, x)
	}
	sortWork(out)
	return fl, out
}

func (a *App) workList(w http.ResponseWriter, r *http.Request, u *User) {
	a.s.mu.RLock()
	defer a.s.mu.RUnlock()
	fl, list := a.filterWork(r, u)
	total := len(list)
	if len(list) > 600 {
		list = list[:600]
	}
	cid, _ := strconv.ParseInt(fl.Client, 10, 64)
	a.render(w, r, "work", &Page{Title: "Work tracker", Nav: "work", V: map[string]any{
		"ClientName": a.s.D.clientName(cid), "F": fl, "List": list, "Total": total, "Users": a.s.D.activeUsers(), "Types": WorkTypes, "Statuses": WorkStatuses, "Query": r.URL.RawQuery,
	}})
}

func (a *App) workExport(w http.ResponseWriter, r *http.Request, u *User) {
	a.s.mu.RLock()
	defer a.s.mu.RUnlock()
	_, list := a.filterWork(r, u)
	csvHead(w, "work-"+today()+".csv")
	cw := csv.NewWriter(w)
	cw.Write([]string{"Client", "File No", "PAN", "Work", "Period", "Assigned to", "Due date", "Status", "Priority", "Ack / SRN", "Done on", "Fee", "Remarks"})
	for _, x := range list {
		c := a.s.D.client(x.ClientID)
		if c == nil {
			c = &Client{}
		}
		cw.Write([]string{c.Name, c.FileNo, c.PAN, x.Type, x.Period, a.s.D.userName(x.Assigned), x.Due, x.Status, x.Priority, x.AckNo, x.DoneDate, x.Fee, x.Remarks})
	}
	cw.Flush()
}

func (a *App) workForm(w http.ResponseWriter, r *http.Request, u *User) {
	a.s.mu.RLock()
	defer a.s.mu.RUnlock()
	x := &Work{Status: "Not started", Priority: "Normal", Assigned: u.ID}
	if id := pid(r); id > 0 {
		if x = a.s.D.work(id); x == nil {
			http.NotFound(w, r)
			return
		}
	} else {
		cid, _ := strconv.ParseInt(r.URL.Query().Get("client"), 10, 64)
		x.ClientID = cid
		if c := a.s.D.client(cid); c != nil && c.Manager != 0 && isPartner(u) {
			x.Assigned = c.Manager
		}
		x.Type = r.URL.Query().Get("type")
	}
	a.workRender(w, r, u, x, "")
}

func (a *App) workRender(w http.ResponseWriter, r *http.Request, u *User, x *Work, errMsg string) {
	var comments []*Comment
	if x.ID > 0 {
		comments = a.s.D.comments("work", x.ID)
	}
	a.render(w, r, "work_form", &Page{Title: "Work", Nav: "work", Error: errMsg, V: map[string]any{
		"W": x, "Clients": a.s.D.sortedClients(), "Users": a.s.D.activeUsers(), "Types": WorkTypes, "Statuses": WorkStatuses,
		"Priorities": Priorities, "Comments": comments, "Back": r.URL.Query().Get("back"),
	}})
}

func (a *App) workSave(w http.ResponseWriter, r *http.Request, u *User) {
	a.s.mu.Lock()
	defer a.s.mu.Unlock()
	d := a.s.D
	id := pid(r)
	x := &Work{}
	if id > 0 {
		if x = d.work(id); x == nil {
			http.NotFound(w, r)
			return
		}
	}
	t := *x
	t.ClientID, t.Type, t.Period = fid(r, "client_id"), f(r, "type"), f(r, "period")
	t.Due, t.Status, t.Priority = f(r, "due"), f(r, "status"), f(r, "priority")
	t.AckNo, t.DoneDate, t.Fee, t.Remarks = f(r, "ack_no"), f(r, "done_date"), f(r, "fee"), f(r, "remarks")
	if isPartner(u) {
		t.Assigned = fid(r, "assigned")
	} else if id == 0 {
		t.Assigned = u.ID
	}
	if isDone(t.Status) && t.DoneDate == "" {
		t.DoneDate = today()
	}
	msg := ""
	if d.client(t.ClientID) == nil {
		msg = "Choose the client."
	} else if t.Type == "" {
		msg = "Choose the type of work."
	}
	if msg != "" {
		a.workRender(w, r, u, &t, msg)
		return
	}
	t.Updated = stamp()
	if id == 0 {
		t.ID, t.CreatedBy, t.Created = a.s.newID(), u.ID, stamp()
		d.Works = append(d.Works, &t)
	} else {
		*x = t
	}
	a.s.save()
	to := safeNext(f(r, "back"))
	if to == "/" {
		to = "/work"
	}
	back(w, r, to, "Saved: "+d.clientName(t.ClientID)+" · "+t.Type)
}

func (a *App) workStatus(w http.ResponseWriter, r *http.Request, u *User) {
	a.s.mu.Lock()
	defer a.s.mu.Unlock()
	x := a.s.D.work(pid(r))
	st := f(r, "status")
	ok := false
	for _, s := range WorkStatuses {
		if s == st {
			ok = true
		}
	}
	if x != nil && ok {
		x.Status = st
		if isDone(st) && x.DoneDate == "" {
			x.DoneDate = today()
		}
		x.Updated = stamp()
		a.s.save()
	}
	back(w, r, safeNext(f(r, "back")), "")
}

func (a *App) workDelete(w http.ResponseWriter, r *http.Request, u *User) {
	a.s.mu.Lock()
	defer a.s.mu.Unlock()
	d := a.s.D
	id := pid(r)
	for i, x := range d.Works {
		if x.ID == id {
			d.Works = append(d.Works[:i], d.Works[i+1:]...)
			break
		}
	}
	a.s.save()
	back(w, r, "/work", "Work entry deleted.")
}

func (a *App) workBulkForm(w http.ResponseWriter, r *http.Request, u *User) {
	a.s.mu.RLock()
	defer a.s.mu.RUnlock()
	typ := r.URL.Query().Get("ctype")
	var cl []*Client
	for _, c := range a.s.D.sortedClients() {
		if c.Active && (typ == "" || c.Type == typ) {
			cl = append(cl, c)
		}
	}
	a.render(w, r, "work_bulk", &Page{Title: "Add work for many clients", Nav: "work", V: map[string]any{
		"Clients": cl, "CType": typ, "CTypes": ClientTypes, "Users": a.s.D.activeUsers(), "Types": WorkTypes, "Priorities": Priorities,
	}})
}

func (a *App) workBulkSave(w http.ResponseWriter, r *http.Request, u *User) {
	typ, period, due, prio := f(r, "type"), f(r, "period"), f(r, "due"), f(r, "priority")
	assigned := fid(r, "assigned")
	useMgr := r.FormValue("use_manager") == "1"
	if !isPartner(u) {
		assigned, useMgr = u.ID, false
	}
	if typ == "" || period == "" {
		back(w, r, "/work/bulk", "Choose the work type and enter the period (for example AY 2026-27).")
		return
	}
	a.s.mu.Lock()
	defer a.s.mu.Unlock()
	d := a.s.D
	added, skipped := 0, 0
	for _, v := range r.Form["c"] {
		cid, _ := strconv.ParseInt(v, 10, 64)
		c := d.client(cid)
		if c == nil {
			continue
		}
		dup := false
		for _, x := range d.Works {
			if x.ClientID == cid && x.Type == typ && lower(x.Period) == lower(period) {
				dup = true
				break
			}
		}
		if dup {
			skipped++
			continue
		}
		as := assigned
		if useMgr && c.Manager != 0 {
			as = c.Manager
		}
		d.Works = append(d.Works, &Work{ID: a.s.newID(), ClientID: cid, Type: typ, Period: period, Due: due, Assigned: as, Status: "Not started", Priority: prio, CreatedBy: u.ID, Created: stamp(), Updated: stamp()})
		added++
	}
	a.s.save()
	back(w, r, "/work?who=all&type="+urlq(typ), fmt.Sprintf("Added %d work entries. %d clients already had this work for %s and were skipped.", added, skipped, period))
}

// ---------------- notices ----------------

func (a *App) noticeList(w http.ResponseWriter, r *http.Request, u *User) {
	a.s.mu.RLock()
	defer a.s.mu.RUnlock()
	qv := r.URL.Query()
	who, status, act, q := qv.Get("who"), qv.Get("status"), qv.Get("act"), lower(f(r, "q"))
	if !qv.Has("who") {
		who = "all"
	}
	if !qv.Has("status") {
		status = "open"
	}
	var list []*Notice
	for _, n := range a.s.D.Notices {
		if who == "me" && n.Assigned != u.ID {
			continue
		}
		if who != "me" && who != "all" && fmt.Sprint(n.Assigned) != who {
			continue
		}
		if status == "open" && !noticeOpen(n.Status) {
			continue
		}
		if status != "open" && status != "any" && status != "" && n.Status != status {
			continue
		}
		if act != "" && n.Act != act {
			continue
		}
		if q != "" {
			c := a.s.D.client(n.ClientID)
			hay := n.Section + " " + n.Period + " " + n.RefNo + " " + n.Summary + " " + n.Remarks
			if c != nil {
				hay += " " + c.Name + " " + c.PAN + " " + c.GSTIN
			}
			if !has(hay, q) {
				continue
			}
		}
		list = append(list, n)
	}
	sortNotices(list)
	a.render(w, r, "notices", &Page{Title: "Notices register", Nav: "notices", V: map[string]any{
		"List": list, "Who": who, "Status": status, "Act": act, "Q": f(r, "q"), "Users": a.s.D.activeUsers(), "Acts": NoticeActs, "Statuses": NoticeStatuses,
	}})
}

func (a *App) noticeForm(w http.ResponseWriter, r *http.Request, u *User) {
	a.s.mu.RLock()
	defer a.s.mu.RUnlock()
	n := &Notice{Status: "Received", Act: "Income Tax", Assigned: u.ID}
	if id := pid(r); id > 0 {
		if n = a.s.D.notice(id); n == nil {
			http.NotFound(w, r)
			return
		}
	} else {
		cid, _ := strconv.ParseInt(r.URL.Query().Get("client"), 10, 64)
		n.ClientID = cid
	}
	a.noticeRender(w, r, n, "")
}

func (a *App) noticeRender(w http.ResponseWriter, r *http.Request, n *Notice, msg string) {
	var comments []*Comment
	if n.ID > 0 {
		comments = a.s.D.comments("notice", n.ID)
	}
	a.render(w, r, "notice_form", &Page{Title: "Notice", Nav: "notices", Error: msg, V: map[string]any{
		"N": n, "Clients": a.s.D.sortedClients(), "Users": a.s.D.activeUsers(), "Acts": NoticeActs, "Statuses": NoticeStatuses, "Comments": comments,
	}})
}

func (a *App) noticeSave(w http.ResponseWriter, r *http.Request, u *User) {
	a.s.mu.Lock()
	defer a.s.mu.Unlock()
	d := a.s.D
	id := pid(r)
	n := &Notice{}
	if id > 0 {
		if n = d.notice(id); n == nil {
			http.NotFound(w, r)
			return
		}
	}
	t := *n
	t.ClientID, t.Act, t.Section, t.Period, t.RefNo = fid(r, "client_id"), f(r, "act"), f(r, "section"), f(r, "period"), f(r, "ref_no")
	t.NoticeDate, t.ReplyDue, t.Hearing, t.Status, t.RepliedDate = f(r, "notice_date"), f(r, "reply_due"), f(r, "hearing"), f(r, "status"), f(r, "replied_date")
	t.Summary, t.Remarks = f(r, "summary"), f(r, "remarks")
	if isPartner(u) {
		t.Assigned = fid(r, "assigned")
	} else if id == 0 {
		t.Assigned = u.ID
	}
	if t.Status == "Replied" && t.RepliedDate == "" {
		t.RepliedDate = today()
	}
	if d.client(t.ClientID) == nil {
		a.noticeRender(w, r, &t, "Choose the client.")
		return
	}
	t.Updated = stamp()
	if id == 0 {
		t.ID, t.CreatedBy, t.Created = a.s.newID(), u.ID, stamp()
		d.Notices = append(d.Notices, &t)
	} else {
		*n = t
	}
	a.s.save()
	back(w, r, "/notices", "Notice saved: "+d.clientName(t.ClientID))
}

func (a *App) noticeDelete(w http.ResponseWriter, r *http.Request, u *User) {
	a.s.mu.Lock()
	defer a.s.mu.Unlock()
	d := a.s.D
	id := pid(r)
	for i, x := range d.Notices {
		if x.ID == id {
			d.Notices = append(d.Notices[:i], d.Notices[i+1:]...)
			break
		}
	}
	a.s.save()
	back(w, r, "/notices", "Notice deleted.")
}

func (a *App) noticeExport(w http.ResponseWriter, r *http.Request, u *User) {
	a.s.mu.RLock()
	defer a.s.mu.RUnlock()
	csvHead(w, "notices-"+today()+".csv")
	cw := csv.NewWriter(w)
	cw.Write([]string{"Client", "PAN", "GSTIN", "Act", "Section", "AY / Period", "DIN / Ref", "Notice date", "Reply due", "Hearing", "Handled by", "Status", "Replied on", "Summary", "Remarks"})
	list := append([]*Notice(nil), a.s.D.Notices...)
	sortNotices(list)
	for _, n := range list {
		c := a.s.D.client(n.ClientID)
		if c == nil {
			c = &Client{}
		}
		cw.Write([]string{c.Name, c.PAN, c.GSTIN, n.Act, n.Section, n.Period, n.RefNo, n.NoticeDate, n.ReplyDue, n.Hearing, a.s.D.userName(n.Assigned), n.Status, n.RepliedDate, n.Summary, n.Remarks})
	}
	cw.Flush()
}

// ---------------- calendar ----------------

type calDay struct {
	Date    string
	Dues    []*DueDate
	Works   []*Work
	Notices []*Notice
}

func (a *App) calendar(w http.ResponseWriter, r *http.Request, u *User) {
	a.s.mu.RLock()
	defer a.s.mu.RUnlock()
	m := r.URL.Query().Get("m")
	if _, ok := parseDay(m + "-01"); !ok {
		m = now().Format("2006-01")
	}
	mine := r.URL.Query().Get("mine") == "1"
	start, _ := parseDay(m + "-01")
	prev, next := start.AddDate(0, -1, 0).Format("2006-01"), start.AddDate(0, 1, 0).Format("2006-01")
	days := map[string]*calDay{}
	get := func(dt string) *calDay {
		if days[dt] == nil {
			days[dt] = &calDay{Date: dt}
		}
		return days[dt]
	}
	for _, x := range a.s.D.DueDates {
		if strings.HasPrefix(x.Date, m) {
			get(x.Date).Dues = append(get(x.Date).Dues, x)
		}
	}
	for _, x := range a.s.D.Works {
		if strings.HasPrefix(x.Due, m) && !isDone(x.Status) && (!mine || x.Assigned == u.ID) {
			get(x.Due).Works = append(get(x.Due).Works, x)
		}
	}
	for _, n := range a.s.D.Notices {
		if !noticeOpen(n.Status) || (mine && n.Assigned != u.ID) {
			continue
		}
		for _, dt := range []string{n.ReplyDue, n.Hearing} {
			if strings.HasPrefix(dt, m) {
				get(dt).Notices = append(get(dt).Notices, n)
			}
		}
	}
	var list []*calDay
	for _, v := range days {
		list = append(list, v)
	}
	sort.Slice(list, func(i, j int) bool { return list[i].Date < list[j].Date })
	a.render(w, r, "calendar", &Page{Title: "Due dates", Nav: "calendar", V: map[string]any{
		"Days": list, "Month": start.Format("January 2006"), "M": m, "Prev": prev, "Next": next, "Mine": mine, "Laws": []string{"Income Tax", "GST", "TDS / TCS", "MCA / ROC", "Labour", "Other"},
	}})
}

func (a *App) dueAdd(w http.ResponseWriter, r *http.Request, u *User) {
	dt, title := f(r, "date"), f(r, "title")
	if _, ok := parseDay(dt); !ok || title == "" {
		back(w, r, "/calendar", "Enter a date and a title.")
		return
	}
	a.s.mu.Lock()
	a.s.D.DueDates = append(a.s.D.DueDates, &DueDate{ID: a.s.newID(), Date: dt, Title: title, Law: f(r, "law"), Notes: f(r, "notes")})
	a.s.save()
	a.s.mu.Unlock()
	back(w, r, "/calendar?m="+dt[:7], "Due date added.")
}

func (a *App) dueDelete(w http.ResponseWriter, r *http.Request, u *User) {
	a.s.mu.Lock()
	defer a.s.mu.Unlock()
	id := pid(r)
	m := ""
	for i, x := range a.s.D.DueDates {
		if x.ID == id {
			m = x.Date[:7]
			a.s.D.DueDates = append(a.s.D.DueDates[:i], a.s.D.DueDates[i+1:]...)
			break
		}
	}
	a.s.save()
	back(w, r, "/calendar?m="+m, "Due date removed.")
}
