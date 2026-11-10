package main

import (
	"crypto/pbkdf2"
	"crypto/rand"
	"crypto/sha256"
	"crypto/subtle"
	"encoding/base64"
	"encoding/hex"
	"fmt"
	"net"
	"net/http"
	"strconv"
	"strings"
	"sync"
	"time"
)

const passIter = 210000

func hashPass(p string) string {
	salt := make([]byte, 16)
	rand.Read(salt)
	k, _ := pbkdf2.Key(sha256.New, p, salt, passIter, 32)
	return fmt.Sprintf("pbkdf2$%d$%s$%s", passIter, base64.RawStdEncoding.EncodeToString(salt), base64.RawStdEncoding.EncodeToString(k))
}

func checkPass(stored, p string) bool {
	parts := strings.Split(stored, "$")
	if len(parts) != 4 || parts[0] != "pbkdf2" {
		return false
	}
	iter, err := strconv.Atoi(parts[1])
	if err != nil {
		return false
	}
	salt, err1 := base64.RawStdEncoding.DecodeString(parts[2])
	want, err2 := base64.RawStdEncoding.DecodeString(parts[3])
	if err1 != nil || err2 != nil {
		return false
	}
	k, _ := pbkdf2.Key(sha256.New, p, salt, iter, len(want))
	return subtle.ConstantTimeCompare(k, want) == 1
}

func token() string {
	b := make([]byte, 24)
	rand.Read(b)
	return hex.EncodeToString(b)
}

// ---- login throttling: 8 wrong tries from one computer locks it for 10 minutes ----

var (
	failMu sync.Mutex
	fails  = map[string][]time.Time{}
)

func clientIP(r *http.Request) string {
	h, _, err := net.SplitHostPort(r.RemoteAddr)
	if err != nil {
		return r.RemoteAddr
	}
	return h
}

func locked(ip string) bool {
	failMu.Lock()
	defer failMu.Unlock()
	cut := time.Now().Add(-10 * time.Minute)
	var keep []time.Time
	for _, t := range fails[ip] {
		if t.After(cut) {
			keep = append(keep, t)
		}
	}
	fails[ip] = keep
	return len(keep) >= 8
}

func noteFail(ip string) {
	failMu.Lock()
	fails[ip] = append(fails[ip], time.Now())
	failMu.Unlock()
}

const cookieName = "ggoffice"

// current returns the signed-in user and session, or nil.
func (a *App) current(r *http.Request) (*User, *Session) {
	c, err := r.Cookie(cookieName)
	if err != nil {
		return nil, nil
	}
	a.s.mu.RLock()
	defer a.s.mu.RUnlock()
	ss := a.s.D.Sessions[c.Value]
	if ss == nil || ss.Expires < now().Format(time.RFC3339) {
		return nil, nil
	}
	u := a.s.D.user(ss.UserID)
	if u == nil || !u.Active {
		return nil, nil
	}
	return u, ss
}

func (a *App) startSession(w http.ResponseWriter, u *User) {
	t := token()
	a.s.mu.Lock()
	// drop expired sessions
	nowS := now().Format(time.RFC3339)
	for k, v := range a.s.D.Sessions {
		if v.Expires < nowS {
			delete(a.s.D.Sessions, k)
		}
	}
	a.s.D.Sessions[t] = &Session{UserID: u.ID, CSRF: token(), Expires: now().Add(12 * time.Hour).Format(time.RFC3339)}
	a.s.save()
	a.s.mu.Unlock()
	http.SetCookie(w, &http.Cookie{Name: cookieName, Value: t, Path: "/", HttpOnly: true, SameSite: http.SameSiteLaxMode})
}

func (a *App) endSession(w http.ResponseWriter, r *http.Request) {
	if c, err := r.Cookie(cookieName); err == nil {
		a.s.mu.Lock()
		delete(a.s.D.Sessions, c.Value)
		a.s.save()
		a.s.mu.Unlock()
	}
	http.SetCookie(w, &http.Cookie{Name: cookieName, Value: "", Path: "/", MaxAge: -1})
}

func isPartner(u *User) bool { return u != nil && (u.Role == "admin" || u.Role == "partner") }
func isAdmin(u *User) bool   { return u != nil && u.Role == "admin" }
