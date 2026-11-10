package main

import (
	"encoding/json"
	"os"
	"path/filepath"
	"sort"
	"strings"
	"sync"
	"time"
)

// All office data lives in one JSON file next to the program. Every change is
// written to a temporary file first and then renamed, so a power cut can never
// leave a half-written file.

type User struct {
	ID       int64  `json:"id"`
	Name     string `json:"name"`
	Username string `json:"username"`
	Pass     string `json:"pass"`
	Role     string `json:"role"` // admin, partner, staff
	Active   bool   `json:"active"`
	Created  string `json:"created"`
}

type Client struct {
	ID      int64  `json:"id"`
	Name    string `json:"name"`
	FileNo  string `json:"file_no"`
	Type    string `json:"type"`
	PAN     string `json:"pan"`
	GSTIN   string `json:"gstin"`
	TAN     string `json:"tan"`
	CIN     string `json:"cin"`
	Phone   string `json:"phone"`
	Email   string `json:"email"`
	Address string `json:"address"`
	Group   string `json:"group"`
	Manager int64  `json:"manager"`
	Notes   string `json:"notes"`
	Active  bool   `json:"active"`
	Created string `json:"created"`
}

type Work struct {
	ID        int64  `json:"id"`
	ClientID  int64  `json:"client_id"`
	Type      string `json:"type"`
	Period    string `json:"period"`
	Assigned  int64  `json:"assigned"`
	Due       string `json:"due"`
	Status    string `json:"status"`
	Priority  string `json:"priority"`
	AckNo     string `json:"ack_no"`
	DoneDate  string `json:"done_date"`
	Fee       string `json:"fee"`
	Remarks   string `json:"remarks"`
	CreatedBy int64  `json:"created_by"`
	Created   string `json:"created"`
	Updated   string `json:"updated"`
}

type Notice struct {
	ID          int64  `json:"id"`
	ClientID    int64  `json:"client_id"`
	Act         string `json:"act"`
	Section     string `json:"section"`
	Period      string `json:"period"`
	RefNo       string `json:"ref_no"`
	NoticeDate  string `json:"notice_date"`
	ReplyDue    string `json:"reply_due"`
	Hearing     string `json:"hearing"`
	Assigned    int64  `json:"assigned"`
	Status      string `json:"status"`
	RepliedDate string `json:"replied_date"`
	Summary     string `json:"summary"`
	Remarks     string `json:"remarks"`
	CreatedBy   int64  `json:"created_by"`
	Created     string `json:"created"`
	Updated     string `json:"updated"`
}

type Attendance struct {
	ID     int64  `json:"id"`
	UserID int64  `json:"user_id"`
	Date   string `json:"date"`
	In     string `json:"in"`
	Out    string `json:"out"`
	Note   string `json:"note"`
}

type Leave struct {
	ID        int64  `json:"id"`
	UserID    int64  `json:"user_id"`
	From      string `json:"from"`
	To        string `json:"to"`
	Kind      string `json:"kind"`
	Reason    string `json:"reason"`
	Status    string `json:"status"` // Pending, Approved, Rejected
	DecidedBy int64  `json:"decided_by"`
	Created   string `json:"created"`
}

type WorkLog struct {
	ID       int64  `json:"id"`
	UserID   int64  `json:"user_id"`
	Date     string `json:"date"`
	ClientID int64  `json:"client_id"`
	Hours    string `json:"hours"`
	Text     string `json:"text"`
	Created  string `json:"created"`
}

type Comment struct {
	ID     int64  `json:"id"`
	Kind   string `json:"kind"` // work, notice
	RefID  int64  `json:"ref_id"`
	UserID int64  `json:"user_id"`
	Text   string `json:"text"`
	At     string `json:"at"`
}

type DueDate struct {
	ID    int64  `json:"id"`
	Date  string `json:"date"`
	Title string `json:"title"`
	Law   string `json:"law"`
	Notes string `json:"notes"`
}

type Session struct {
	UserID  int64  `json:"user_id"`
	CSRF    string `json:"csrf"`
	Expires string `json:"expires"`
}

type Settings struct {
	Office string `json:"office"`
}

type Data struct {
	NextID     int64               `json:"next_id"`
	Settings   Settings            `json:"settings"`
	Users      []*User             `json:"users"`
	Clients    []*Client           `json:"clients"`
	Works      []*Work             `json:"works"`
	Notices    []*Notice           `json:"notices"`
	Attendance []*Attendance       `json:"attendance"`
	Leaves     []*Leave            `json:"leaves"`
	Logs       []*WorkLog          `json:"logs"`
	Comments   []*Comment          `json:"comments"`
	DueDates   []*DueDate          `json:"due_dates"`
	Sessions   map[string]*Session `json:"sessions"`
}

type Store struct {
	mu   sync.RWMutex
	path string
	D    *Data
}

func openStore(path string) (*Store, error) {
	s := &Store{path: path, D: &Data{NextID: 1, Sessions: map[string]*Session{}}}
	b, err := os.ReadFile(path)
	if err == nil {
		if err := json.Unmarshal(b, s.D); err != nil {
			return nil, err
		}
		if s.D.Sessions == nil {
			s.D.Sessions = map[string]*Session{}
		}
	} else if !os.IsNotExist(err) {
		return nil, err
	}
	if s.D.Settings.Office == "" {
		s.D.Settings.Office = "GG Associates"
	}
	return s, nil
}

func (s *Store) newID() int64 { id := s.D.NextID; s.D.NextID++; return id }

// save must be called with the write lock held.
func (s *Store) save() error {
	b, err := json.MarshalIndent(s.D, "", " ")
	if err != nil {
		return err
	}
	tmp := s.path + ".tmp"
	f, err := os.Create(tmp)
	if err != nil {
		return err
	}
	if _, err := f.Write(b); err != nil {
		f.Close()
		return err
	}
	f.Sync()
	f.Close()
	return os.Rename(tmp, s.path)
}

func (s *Store) snapshot() ([]byte, error) {
	s.mu.RLock()
	defer s.mu.RUnlock()
	return json.MarshalIndent(s.D, "", " ")
}

// Daily backup copy, keeping the latest 30.
func (s *Store) dailyBackup(dir string) {
	os.MkdirAll(dir, 0o755)
	name := filepath.Join(dir, "office-"+today()+".json")
	if _, err := os.Stat(name); err == nil {
		return
	}
	b, err := s.snapshot()
	if err != nil {
		return
	}
	os.WriteFile(name, b, 0o644)
	files, _ := filepath.Glob(filepath.Join(dir, "office-*.json"))
	sort.Strings(files)
	for len(files) > 30 {
		os.Remove(files[0])
		files = files[1:]
	}
}

// ---- lookups (caller holds a lock) ----

func (d *Data) user(id int64) *User {
	for _, u := range d.Users {
		if u.ID == id {
			return u
		}
	}
	return nil
}

func (d *Data) userName(id int64) string {
	if u := d.user(id); u != nil {
		return u.Name
	}
	return ""
}

func (d *Data) client(id int64) *Client {
	for _, c := range d.Clients {
		if c.ID == id {
			return c
		}
	}
	return nil
}

func (d *Data) clientName(id int64) string {
	if c := d.client(id); c != nil {
		return c.Name
	}
	return ""
}

func (d *Data) work(id int64) *Work {
	for _, w := range d.Works {
		if w.ID == id {
			return w
		}
	}
	return nil
}

func (d *Data) notice(id int64) *Notice {
	for _, n := range d.Notices {
		if n.ID == id {
			return n
		}
	}
	return nil
}

func (d *Data) activeUsers() []*User {
	var out []*User
	for _, u := range d.Users {
		if u.Active {
			out = append(out, u)
		}
	}
	sort.Slice(out, func(i, j int) bool { return strings.ToLower(out[i].Name) < strings.ToLower(out[j].Name) })
	return out
}

func (d *Data) sortedClients() []*Client {
	out := append([]*Client(nil), d.Clients...)
	sort.Slice(out, func(i, j int) bool { return strings.ToLower(out[i].Name) < strings.ToLower(out[j].Name) })
	return out
}

func (d *Data) comments(kind string, id int64) []*Comment {
	var out []*Comment
	for _, c := range d.Comments {
		if c.Kind == kind && c.RefID == id {
			out = append(out, c)
		}
	}
	return out
}

// ---- time helpers (office time is IST) ----

var ist = time.FixedZone("IST", 5*3600+1800)

func now() time.Time        { return time.Now().In(ist) }
func today() string         { return now().Format("2006-01-02") }
func stamp() string         { return now().Format("2006-01-02 15:04") }
func dayAdd(n int) string   { return now().AddDate(0, 0, n).Format("2006-01-02") }
func parseDay(s string) (time.Time, bool) {
	t, err := time.ParseInLocation("2006-01-02", s, ist)
	return t, err == nil
}
