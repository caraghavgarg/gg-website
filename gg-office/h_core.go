package main

import (
	"encoding/json"
	"fmt"
	"io"
	"net/http"
	"os"
	"path/filepath"
	"runtime"
	"sort"
	"strings"
)

// ---------------- first-run setup ----------------

func (a *App) setupForm(w http.ResponseWriter, r *http.Request) {
	if !a.needsSetup() {
		http.Redirect(w, r, "/login", 303)
		return
	}
	a.s.mu.RLock()
	defer a.s.mu.RUnlock()
	a.render(w, r, "setup", &Page{Title: "Set up GG Office"})
}

func (a *App) setupSave(w http.ResponseWriter, r *http.Request) {
	if !a.needsSetup() {
		http.Redirect(w, r, "/login", 303)
		return
	}
	name, user, p1, p2, office := f(r, "name"), lower(f(r, "username")), r.FormValue("pass"), r.FormValue("pass2"), f(r, "office")
	errMsg := ""
	switch {
	case name == "" || user == "":
		errMsg = "Enter your name and a username."
	case len(p1) < 8:
		errMsg = "Choose a password of at least 8 characters."
	case p1 != p2:
		errMsg = "The two passwords do not match."
	}
	if errMsg != "" {
		a.s.mu.RLock()
		defer a.s.mu.RUnlock()
		a.render(w, r, "setup", &Page{Title: "Set up GG Office", Error: errMsg, V: map[string]any{"Name": name, "Username": user, "OfficeName": office}})
		return
	}
	a.s.mu.Lock()
	if len(a.s.D.Users) > 0 {
		a.s.mu.Unlock()
		http.Redirect(w, r, "/login", 303)
		return
	}
	if office != "" {
		a.s.D.Settings.Office = office
	}
	u := &User{ID: a.s.newID(), Name: name, Username: user, Pass: hashPass(p1), Role: "admin", Active: true, Created: stamp()}
	a.s.D.Users = append(a.s.D.Users, u)
	if len(a.s.D.DueDates) == 0 {
		seedDueDates(a.s.D)
	}
	a.s.save()
	a.s.mu.Unlock()
	a.startSession(w, u)
	back(w, r, "/users", "Welcome! Now add your partners and staff.")
}

// ---------------- login ----------------

func (a *App) loginForm(w http.ResponseWriter, r *http.Request) {
	if a.needsSetup() {
		http.Redirect(w, r, "/setup", 303)
		return
	}
	if u, _ := a.current(r); u != nil {
		http.Redirect(w, r, "/", 303)
		return
	}
	a.s.mu.RLock()
	defer a.s.mu.RUnlock()
	a.render(w, r, "login", &Page{Title: "Sign in", V: map[string]any{"Next": r.URL.Query().Get("next")}})
}

func (a *App) loginPost(w http.ResponseWriter, r *http.Request) {
	ip := clientIP(r)
	user, pass, next := lower(f(r, "username")), r.FormValue("pass"), f(r, "next")
	fail := func(msg string) {
		a.s.mu.RLock()
		defer a.s.mu.RUnlock()
		a.render(w, r, "login", &Page{Title: "Sign in", Error: msg, V: map[string]any{"Next": next, "Username": user}})
	}
	if locked(ip) {
		fail("Too many wrong tries from this computer. Wait 10 minutes and try again.")
		return
	}
	a.s.mu.RLock()
	var found *User
	for _, u := range a.s.D.Users {
		if u.Username == user && u.Active {
			found = u
		}
	}
	a.s.mu.RUnlock()
	if found == nil || !checkPass(found.Pass, pass) {
		noteFail(ip)
		fail("Wrong username or password.")
		return
	}
	a.startSession(w, found)
	http.Redirect(w, r, safeNext(next), 303)
}

// ---------------- dashboard ----------------

type teamRow struct {
	User              *User
	Open, Over, Today int
	In, Out           string
	OnLeave           bool
}

func (a *App) dashboard(w http.ResponseWriter, r *http.Request, u *User) {
	a.s.mu.RLock()
	defer a.s.mu.RUnlock()
	d := a.s.D
	t := today()
	var mine []*Work
	for _, x := range d.Works {
		if x.Assigned == u.ID && !isDone(x.Status) {
			mine = append(mine, x)
		}
	}
	sortWork(mine)
	if len(mine) > 15 {
		mine = mine[:15]
	}
	var notices []*Notice
	for _, n := range d.Notices {
		if noticeOpen(n.Status) && (n.Assigned == u.ID || (isPartner(u) && n.Assigned == 0)) {
			notices = append(notices, n)
		}
	}
	sortNotices(notices)
	var dues []*DueDate
	for _, x := range d.DueDates {
		if x.Date >= t && x.Date <= dayAdd(14) {
			dues = append(dues, x)
		}
	}
	sort.SliceStable(dues, func(i, j int) bool { return dues[i].Date < dues[j].Date })

	var att *Attendance
	for _, x := range d.Attendance {
		if x.UserID == u.ID && x.Date == t {
			att = x
		}
	}
	counts := map[string]int{}
	for _, x := range d.Works {
		if x.Assigned == u.ID && !isDone(x.Status) {
			counts["open"]++
			if x.Due != "" && x.Due < t {
				counts["over"]++
			}
			if x.Due != "" && x.Due >= t && x.Due <= dayAdd(7) {
				counts["week"]++
			}
		}
	}
	for _, n := range notices {
		_ = n
		counts["notices"]++
	}

	v := map[string]any{"Mine": mine, "Notices": notices, "Dues": dues, "Att": att, "C": counts}
	if isPartner(u) {
		var team []teamRow
		for _, s := range d.activeUsers() {
			row := teamRow{User: s}
			for _, x := range d.Works {
				if x.Assigned == s.ID && !isDone(x.Status) {
					row.Open++
					if x.Due != "" && x.Due < t {
						row.Over++
					}
					if x.Due == t {
						row.Today++
					}
				}
			}
			for _, at := range d.Attendance {
				if at.UserID == s.ID && at.Date == t {
					row.In, row.Out = at.In, at.Out
				}
			}
			for _, l := range d.Leaves {
				if l.UserID == s.ID && l.Status == "Approved" && l.From <= t && l.To >= t {
					row.OnLeave = true
				}
			}
			team = append(team, row)
		}
		v["Team"] = team
		var pend []*Leave
		for _, l := range d.Leaves {
			if l.Status == "Pending" {
				pend = append(pend, l)
			}
		}
		v["PendingLeave"] = pend
		unassigned := 0
		for _, x := range d.Works {
			if x.Assigned == 0 && !isDone(x.Status) {
				unassigned++
			}
		}
		v["Unassigned"] = unassigned
	}
	a.render(w, r, "dashboard", &Page{Title: "Today", Nav: "home", V: v})
}

// ---------------- my account ----------------

func (a *App) accountForm(w http.ResponseWriter, r *http.Request, u *User) {
	a.s.mu.RLock()
	defer a.s.mu.RUnlock()
	a.render(w, r, "account", &Page{Title: "My account", Nav: "account"})
}

func (a *App) accountSave(w http.ResponseWriter, r *http.Request, u *User) {
	old, p1, p2 := r.FormValue("old"), r.FormValue("pass"), r.FormValue("pass2")
	msg := ""
	switch {
	case !checkPass(u.Pass, old):
		msg = "Your current password is not correct."
	case len(p1) < 8:
		msg = "Choose a new password of at least 8 characters."
	case p1 != p2:
		msg = "The two new passwords do not match."
	}
	a.s.mu.Lock()
	defer a.s.mu.Unlock()
	if msg != "" {
		a.render(w, r, "account", &Page{Title: "My account", Nav: "account", Error: msg})
		return
	}
	u.Pass = hashPass(p1)
	a.s.save()
	back(w, r, "/account", "Password changed.")
}

// ---------------- users (admin) ----------------

func (a *App) userList(w http.ResponseWriter, r *http.Request, u *User) {
	a.s.mu.RLock()
	defer a.s.mu.RUnlock()
	users := append([]*User(nil), a.s.D.Users...)
	sort.Slice(users, func(i, j int) bool {
		if users[i].Active != users[j].Active {
			return users[i].Active
		}
		return lower(users[i].Name) < lower(users[j].Name)
	})
	a.render(w, r, "users", &Page{Title: "Staff & logins", Nav: "users", V: map[string]any{"Users": users}})
}

func (a *App) userForm(w http.ResponseWriter, r *http.Request, u *User) {
	a.s.mu.RLock()
	defer a.s.mu.RUnlock()
	item := &User{Role: "staff", Active: true}
	if id := pid(r); id > 0 {
		if item = a.s.D.user(id); item == nil {
			http.NotFound(w, r)
			return
		}
	}
	a.render(w, r, "user_form", &Page{Title: "Login", Nav: "users", V: map[string]any{"Item": item, "Roles": Roles}})
}

func (a *App) userSave(w http.ResponseWriter, r *http.Request, u *User) {
	a.s.mu.Lock()
	defer a.s.mu.Unlock()
	d := a.s.D
	id := pid(r)
	item := &User{}
	if id > 0 {
		if item = d.user(id); item == nil {
			http.NotFound(w, r)
			return
		}
	}
	tmp := *item
	tmp.Name, tmp.Username, tmp.Role = f(r, "name"), lower(f(r, "username")), f(r, "role")
	tmp.Active = r.FormValue("active") == "1"
	pass := r.FormValue("pass")
	msg := ""
	if tmp.Name == "" || tmp.Username == "" || strings.ContainsAny(tmp.Username, " \t") {
		msg = "Enter a name and a username without spaces."
	}
	if tmp.Role != "staff" && tmp.Role != "partner" && tmp.Role != "admin" {
		tmp.Role = "staff"
	}
	for _, o := range d.Users {
		if o.ID != item.ID && o.Username == tmp.Username {
			msg = "That username is already used by " + o.Name + "."
		}
	}
	if id == 0 && len(pass) < 8 {
		msg = "Set a first password of at least 8 characters."
	}
	if id > 0 && pass != "" && len(pass) < 8 {
		msg = "A new password must have at least 8 characters."
	}
	if item.ID == u.ID && (tmp.Role != "admin" || !tmp.Active) {
		msg = "You cannot remove your own administrator access."
	}
	if msg != "" {
		a.render(w, r, "user_form", &Page{Title: "Login", Nav: "users", Error: msg, V: map[string]any{"Item": &tmp, "Roles": Roles}})
		return
	}
	if pass != "" {
		tmp.Pass = hashPass(pass)
		// sign the person out everywhere so the new password takes effect
		for k, s := range d.Sessions {
			if s.UserID == item.ID && item.ID != u.ID {
				delete(d.Sessions, k)
			}
		}
	}
	if !tmp.Active {
		for k, s := range d.Sessions {
			if s.UserID == item.ID {
				delete(d.Sessions, k)
			}
		}
	}
	if id == 0 {
		tmp.ID = a.s.newID()
		tmp.Created = stamp()
		d.Users = append(d.Users, &tmp)
	} else {
		*item = tmp
	}
	a.s.save()
	back(w, r, "/users", "Saved "+tmp.Name+".")
}

// ---------------- comments ----------------

func (a *App) commentAdd(w http.ResponseWriter, r *http.Request, u *User) {
	kind, ref, text := f(r, "kind"), fid(r, "ref"), f(r, "text")
	to := "/"
	if kind == "work" {
		to = fmt.Sprintf("/work/%d", ref)
	} else if kind == "notice" {
		to = fmt.Sprintf("/notices/%d", ref)
	}
	if text != "" && (kind == "work" || kind == "notice") {
		a.s.mu.Lock()
		a.s.D.Comments = append(a.s.D.Comments, &Comment{ID: a.s.newID(), Kind: kind, RefID: ref, UserID: u.ID, Text: text, At: stamp()})
		a.s.save()
		a.s.mu.Unlock()
	}
	back(w, r, to+"#notes", "")
}

// ---------------- settings, backup, restore (admin) ----------------

func startupFile() string {
	return filepath.Join(os.Getenv("APPDATA"), `Microsoft\Windows\Start Menu\Programs\Startup`, "GG Office.bat")
}

func (a *App) settings(w http.ResponseWriter, r *http.Request, u *User) {
	backups, _ := filepath.Glob(filepath.Join(a.dataDir, "backups", "office-*.json"))
	sort.Sort(sort.Reverse(sort.StringSlice(backups)))
	var names []string
	for _, b := range backups {
		names = append(names, filepath.Base(b))
	}
	startup := false
	if runtime.GOOS == "windows" {
		if _, err := os.Stat(startupFile()); err == nil {
			startup = true
		}
	}
	var addrs []string
	for _, ip := range lanIPs() {
		addrs = append(addrs, fmt.Sprintf("http://%s:%d", ip, a.port))
	}
	a.s.mu.RLock()
	defer a.s.mu.RUnlock()
	a.render(w, r, "settings", &Page{Title: "Settings & backup", Nav: "settings", V: map[string]any{
		"Backups": names, "DataDir": a.dataDir, "Windows": runtime.GOOS == "windows", "Startup": startup, "Addrs": addrs, "Version": version,
	}})
}

func (a *App) settingsSave(w http.ResponseWriter, r *http.Request, u *User) {
	a.s.mu.Lock()
	if o := f(r, "office"); o != "" {
		a.s.D.Settings.Office = o
	}
	a.s.save()
	a.s.mu.Unlock()
	back(w, r, "/settings", "Saved.")
}

func (a *App) backupDownload(w http.ResponseWriter, r *http.Request, u *User) {
	name := r.URL.Query().Get("f")
	var b []byte
	var err error
	if name != "" {
		name = filepath.Base(name)
		if !strings.HasPrefix(name, "office-") || !strings.HasSuffix(name, ".json") {
			http.NotFound(w, r)
			return
		}
		b, err = os.ReadFile(filepath.Join(a.dataDir, "backups", name))
	} else {
		name = "gg-office-backup-" + now().Format("2006-01-02-1504") + ".json"
		b, err = a.s.snapshot()
	}
	if err != nil {
		http.NotFound(w, r)
		return
	}
	w.Header().Set("Content-Type", "application/json")
	w.Header().Set("Content-Disposition", `attachment; filename="`+name+`"`)
	w.Write(b)
}

func (a *App) restore(w http.ResponseWriter, r *http.Request, u *User) {
	if f(r, "confirm") != "RESTORE" {
		back(w, r, "/settings", "Type RESTORE in capitals to confirm a restore.")
		return
	}
	file, _, err := r.FormFile("file")
	if err != nil {
		back(w, r, "/settings", "Choose a backup file first.")
		return
	}
	defer file.Close()
	b, _ := io.ReadAll(io.LimitReader(file, 200<<20))
	nd := &Data{}
	if err := json.Unmarshal(b, nd); err != nil || len(nd.Users) == 0 {
		back(w, r, "/settings", "That file is not a GG Office backup.")
		return
	}
	// keep a copy of what is being replaced
	cur, _ := a.s.snapshot()
	os.MkdirAll(filepath.Join(a.dataDir, "backups"), 0o755)
	os.WriteFile(filepath.Join(a.dataDir, "backups", "before-restore-"+now().Format("2006-01-02-150405")+".json"), cur, 0o644)
	a.s.mu.Lock()
	if nd.Sessions == nil {
		nd.Sessions = map[string]*Session{}
	}
	// keep the person who restored signed in if they exist in the backup
	if c, err := r.Cookie(cookieName); err == nil {
		if ss := a.s.D.Sessions[c.Value]; ss != nil && nd.user(ss.UserID) != nil {
			nd.Sessions[c.Value] = ss
		}
	}
	a.s.D = nd
	a.s.save()
	a.s.mu.Unlock()
	back(w, r, "/settings", "Backup restored. The data you replaced was saved in the backups folder.")
}

func (a *App) startup(w http.ResponseWriter, r *http.Request, u *User) {
	if runtime.GOOS != "windows" {
		back(w, r, "/settings", "This option works only on Windows.")
		return
	}
	if f(r, "on") == "1" {
		bat := "@echo off\r\ncd /d \"" + filepath.Dir(a.exePath) + "\"\r\nstart \"GG Office\" /min \"" + a.exePath + "\" -nobrowser -port " + fmt.Sprint(a.port) + "\r\n"
		if err := os.WriteFile(startupFile(), []byte(bat), 0o644); err != nil {
			back(w, r, "/settings", "Could not switch this on: "+err.Error())
			return
		}
		back(w, r, "/settings", "GG Office will now start by itself when this computer starts.")
		return
	}
	os.Remove(startupFile())
	back(w, r, "/settings", "GG Office will no longer start by itself.")
}
