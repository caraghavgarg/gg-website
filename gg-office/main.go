package main

import (
	"context"
	"embed"
	"flag"
	"fmt"
	"html/template"
	"io/fs"
	"log"
	"net"
	"net/http"
	"net/url"
	"os"
	"os/exec"
	"path/filepath"
	"runtime"
	"strconv"
	"strings"
	"time"
)

//go:embed tpl/*.html static/*
var assets embed.FS

const version = "1.0.0"

type App struct {
	s       *Store
	dataDir string
	exePath string
	port    int
	pages   map[string]*template.Template
}

// Page is what every template receives.
type Page struct {
	Title  string
	Nav    string
	Me     *User
	CSRF   string
	Office string
	Flash  string
	Error  string
	Today  string
	V      map[string]any
	D      *Data
}

func main() {
	port := flag.Int("port", 8080, "port to listen on")
	noBrowser := flag.Bool("nobrowser", false, "do not open the browser on start")
	dataFlag := flag.String("data", "", "folder for office data (default: gg-office-data next to the program)")
	flag.Parse()

	exe, _ := os.Executable()
	exe, _ = filepath.EvalSymlinks(exe)
	dataDir := *dataFlag
	if dataDir == "" {
		dataDir = filepath.Join(filepath.Dir(exe), "gg-office-data")
	}
	os.MkdirAll(dataDir, 0o755)

	addr := fmt.Sprintf(":%d", *port)
	ln, err := net.Listen("tcp", addr)
	if err != nil {
		// Already running: just open it.
		fmt.Println("GG Office is already running on this computer.")
		if !*noBrowser {
			openBrowser(fmt.Sprintf("http://localhost:%d", *port))
		}
		time.Sleep(3 * time.Second)
		return
	}

	st, err := openStore(filepath.Join(dataDir, "office.json"))
	if err != nil {
		log.Fatalf("Could not read office data: %v", err)
	}
	app := &App{s: st, dataDir: dataDir, exePath: exe, port: *port}
	app.loadTemplates()

	go func() {
		for {
			st.dailyBackup(filepath.Join(dataDir, "backups"))
			time.Sleep(time.Hour)
		}
	}()

	fmt.Println("==============================================")
	fmt.Println("  GG Office " + version + " is running")
	fmt.Println("==============================================")
	fmt.Printf("  On this computer:   http://localhost:%d\n", *port)
	for _, ip := range lanIPs() {
		fmt.Printf("  On office network:  http://%s:%d\n", ip, *port)
	}
	fmt.Println("  Data folder:        " + dataDir)
	fmt.Println("  Keep this window open (you can minimise it).")
	fmt.Println("  Closing it stops GG Office for everyone.")
	fmt.Println("==============================================")

	if !*noBrowser {
		go openBrowser(fmt.Sprintf("http://localhost:%d", *port))
	}
	srv := &http.Server{Handler: app.routes(), ReadHeaderTimeout: 20 * time.Second}
	log.Fatal(srv.Serve(ln))
}

func lanIPs() []string {
	var out []string
	ifs, _ := net.Interfaces()
	for _, i := range ifs {
		if i.Flags&net.FlagUp == 0 || i.Flags&net.FlagLoopback != 0 {
			continue
		}
		addrs, _ := i.Addrs()
		for _, a := range addrs {
			if n, ok := a.(*net.IPNet); ok && n.IP.To4() != nil && !n.IP.IsLinkLocalUnicast() {
				out = append(out, n.IP.String())
			}
		}
	}
	return out
}

func openBrowser(u string) {
	time.Sleep(700 * time.Millisecond)
	switch runtime.GOOS {
	case "windows":
		exec.Command("rundll32", "url.dll,FileProtocolHandler", u).Start()
	case "darwin":
		exec.Command("open", u).Start()
	default:
		exec.Command("xdg-open", u).Start()
	}
}

// ---------------- templates ----------------

func (a *App) loadTemplates() {
	fm := template.FuncMap{
		"uname": func(id int64) string { return a.s.D.userName(id) },
		"cname": func(id int64) string { return a.s.D.clientName(id) },
		"date":  fmtDate,
		"due":   dueClass,
		"sel": func(a, b any) template.HTMLAttr {
			if fmt.Sprint(a) == fmt.Sprint(b) {
				return "selected"
			}
			return ""
		},
		"chk": func(b bool) template.HTMLAttr {
			if b {
				return "checked"
			}
			return ""
		},
		"partner": isPartner,
		"admin":   isAdmin,
		"slug": func(s string) string {
			s = strings.ToLower(s)
			var b strings.Builder
			for _, r := range s {
				if r >= 'a' && r <= 'z' {
					b.WriteRune(r)
				} else if b.Len() > 0 && !strings.HasSuffix(b.String(), "-") {
					b.WriteByte('-')
				}
			}
			return strings.Trim(b.String(), "-")
		},
		"done":     func(s string) bool { return isDone(s) },
		"nl":       func(s string) template.HTML { return template.HTML(strings.ReplaceAll(template.HTMLEscapeString(s), "\n", "<br>")) },
		"add":      func(a, b int) int { return a + b },
		"q":        url.QueryEscape,
		"initials": initials,
		"dow": func(s string) string {
			if t, ok := parseDay(s); ok {
				return t.Format("Mon")
			}
			return ""
		},
	}
	a.pages = map[string]*template.Template{}
	files, _ := fs.Glob(assets, "tpl/*.html")
	for _, f := range files {
		name := strings.TrimSuffix(filepath.Base(f), ".html")
		if name == "layout" {
			continue
		}
		t := template.Must(template.New("").Funcs(fm).ParseFS(assets, "tpl/layout.html", f))
		a.pages[name] = t
	}
}

func (a *App) render(w http.ResponseWriter, r *http.Request, name string, p *Page) {
	// Callers hold a.s.mu (read or write) while rendering.
	if ss, ok := r.Context().Value(ssKey{}).(*Session); ok && ss != nil {
		p.CSRF = ss.CSRF
		p.Me = a.s.D.user(ss.UserID)
	}
	p.Today = today()
	if p.V == nil {
		p.V = map[string]any{}
	}
	if f := r.URL.Query().Get("ok"); f != "" && p.Flash == "" {
		p.Flash = f
	}
	p.Office = a.s.D.Settings.Office
	p.D = a.s.D
	w.Header().Set("Content-Type", "text/html; charset=utf-8")
	w.Header().Set("Cache-Control", "no-store")
	t := a.pages[name]
	if t == nil {
		http.Error(w, "page missing: "+name, 500)
		return
	}
	if err := t.ExecuteTemplate(w, "layout", p); err != nil {
		log.Printf("template %s: %v", name, err)
	}
}

func initials(s string) string {
	f := strings.Fields(s)
	out := ""
	for i, x := range f {
		if i > 1 {
			break
		}
		out += strings.ToUpper(string([]rune(x)[0]))
	}
	return out
}

func fmtDate(s string) string {
	if t, ok := parseDay(s); ok {
		return t.Format("02 Jan 2006")
	}
	return s
}

func dueClass(due, status string) string {
	if due == "" || isDone(status) {
		return ""
	}
	t := today()
	switch {
	case due < t:
		return "overdue"
	case due == t:
		return "today"
	case due <= dayAdd(7):
		return "soon"
	}
	return ""
}

// ---------------- routing ----------------

type ssKey struct{}

type handler func(w http.ResponseWriter, r *http.Request, u *User)

func (a *App) routes() http.Handler {
	m := http.NewServeMux()
	static, _ := fs.Sub(assets, "static")
	m.Handle("GET /static/", http.StripPrefix("/static/", http.FileServer(http.FS(static))))

	m.HandleFunc("GET /setup", a.setupForm)
	m.HandleFunc("POST /setup", a.setupSave)
	m.HandleFunc("GET /login", a.loginForm)
	m.HandleFunc("POST /login", a.loginPost)
	m.HandleFunc("POST /logout", func(w http.ResponseWriter, r *http.Request) {
		a.endSession(w, r)
		http.Redirect(w, r, "/login", 303)
	})

	in := func(p string, h handler) { m.HandleFunc(p, a.auth(h, "")) }
	partner := func(p string, h handler) { m.HandleFunc(p, a.auth(h, "partner")) }
	adm := func(p string, h handler) { m.HandleFunc(p, a.auth(h, "admin")) }

	in("GET /{$}", a.dashboard)
	in("GET /account", a.accountForm)
	in("POST /account", a.accountSave)

	in("GET /clients", a.clientList)
	in("GET /clients/new", a.clientForm)
	in("POST /clients/new", a.clientSave)
	in("GET /clients/{id}", a.clientView)
	in("GET /clients/{id}/edit", a.clientForm)
	in("POST /clients/{id}/edit", a.clientSave)
	partner("POST /clients/{id}/delete", a.clientDelete)
	in("GET /clients/import", a.clientImportForm)
	in("POST /clients/import", a.clientImport)
	in("GET /clients/template.csv", a.clientTemplate)
	in("GET /clients/export.csv", a.clientExport)

	in("GET /work", a.workList)
	in("GET /work/new", a.workForm)
	in("POST /work/new", a.workSave)
	in("GET /work/bulk", a.workBulkForm)
	in("POST /work/bulk", a.workBulkSave)
	in("GET /work/export.csv", a.workExport)
	in("GET /work/{id}", a.workForm)
	in("POST /work/{id}", a.workSave)
	in("POST /work/{id}/status", a.workStatus)
	partner("POST /work/{id}/delete", a.workDelete)

	in("GET /notices", a.noticeList)
	in("GET /notices/new", a.noticeForm)
	in("POST /notices/new", a.noticeSave)
	in("GET /notices/export.csv", a.noticeExport)
	in("GET /notices/{id}", a.noticeForm)
	in("POST /notices/{id}", a.noticeSave)
	partner("POST /notices/{id}/delete", a.noticeDelete)

	in("POST /comment", a.commentAdd)

	in("GET /calendar", a.calendar)
	partner("POST /calendar/add", a.dueAdd)
	partner("POST /calendar/{id}/delete", a.dueDelete)

	in("GET /attendance", a.attendance)
	in("POST /attendance/punch", a.punch)
	partner("POST /attendance/edit", a.attEdit)
	partner("GET /attendance/export.csv", a.attExport)
	in("GET /leave", a.leaveList)
	in("POST /leave", a.leaveApply)
	in("POST /leave/{id}/cancel", a.leaveCancel)
	partner("POST /leave/{id}/decide", a.leaveDecide)
	in("GET /log", a.logList)
	in("POST /log", a.logAdd)
	in("POST /log/{id}/delete", a.logDelete)

	adm("GET /users", a.userList)
	adm("GET /users/new", a.userForm)
	adm("POST /users/new", a.userSave)
	adm("GET /users/{id}", a.userForm)
	adm("POST /users/{id}", a.userSave)
	adm("GET /settings", a.settings)
	adm("POST /settings", a.settingsSave)
	adm("GET /settings/backup", a.backupDownload)
	adm("POST /settings/restore", a.restore)
	adm("POST /settings/startup", a.startup)

	return secureHeaders(m)
}

func secureHeaders(h http.Handler) http.Handler {
	return http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		w.Header().Set("X-Frame-Options", "DENY")
		w.Header().Set("X-Content-Type-Options", "nosniff")
		w.Header().Set("Referrer-Policy", "same-origin")
		h.ServeHTTP(w, r)
	})
}

func (a *App) needsSetup() bool {
	a.s.mu.RLock()
	defer a.s.mu.RUnlock()
	return len(a.s.D.Users) == 0
}

func (a *App) auth(h handler, level string) http.HandlerFunc {
	return func(w http.ResponseWriter, r *http.Request) {
		if a.needsSetup() {
			http.Redirect(w, r, "/setup", 303)
			return
		}
		u, ss := a.current(r)
		if u == nil {
			http.Redirect(w, r, "/login?next="+url.QueryEscape(r.URL.RequestURI()), 303)
			return
		}
		r = r.WithContext(context.WithValue(r.Context(), ssKey{}, ss))
		if (level == "partner" && !isPartner(u)) || (level == "admin" && !isAdmin(u)) {
			a.s.mu.RLock()
			defer a.s.mu.RUnlock()
			a.render(w, r, "message", &Page{Title: "Not allowed", V: map[string]any{"Text": "This page is only for partners or the administrator."}})
			return
		}
		if r.Method == http.MethodPost {
			r.ParseMultipartForm(32 << 20)
			if subtleEq(r.FormValue("csrf"), ss.CSRF) == false {
				http.Error(w, "This form has expired. Go back, reload the page and try again.", 400)
				return
			}
		}
		h(w, r, u)
	}
}

func subtleEq(a, b string) bool { return a != "" && a == b }

// ---------------- small helpers ----------------

func f(r *http.Request, k string) string { return strings.TrimSpace(r.FormValue(k)) }

func fid(r *http.Request, k string) int64 {
	n, _ := strconv.ParseInt(strings.TrimSpace(r.FormValue(k)), 10, 64)
	return n
}

func pid(r *http.Request) int64 {
	n, _ := strconv.ParseInt(r.PathValue("id"), 10, 64)
	return n
}

func back(w http.ResponseWriter, r *http.Request, to, msg string) {
	if msg != "" {
		sep := "?"
		if strings.Contains(to, "?") {
			sep = "&"
		}
		to += sep + "ok=" + url.QueryEscape(msg)
	}
	http.Redirect(w, r, to, 303)
}

func safeNext(s string) string {
	if strings.HasPrefix(s, "/") && !strings.HasPrefix(s, "//") {
		return s
	}
	return "/"
}
