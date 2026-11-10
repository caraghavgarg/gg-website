# GG Office

Office-only staff website (runs on one office computer, opened by staff over the office network).

- Work tracker, clients, notices register, due dates, attendance, leave, daily work log
- Roles: admin, partner, staff
- Data: `gg-office-data/office.json` next to the program, with daily backups

Build for Windows: `GOOS=windows GOARCH=amd64 go build -trimpath -ldflags="-s -w" -o gg-office.exe .`
