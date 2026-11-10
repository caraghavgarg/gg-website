# ggassociates.in – website theme and plugin

The WordPress theme and plugin for the Garg Garg & Associates group website.

- `gg-theme/` – the GG Theme (design, header, menu bars, page layouts).
- `gg-site/` – the GG Site plugin (services, team, due dates, Acts & Rules hub, calculators, enquiry and careers forms, GST Registration Drafter, one-click setup, updater).
- `dist/` – ready-to-install zips and `versions.json`. The website checks `dist/versions.json` and shows a normal WordPress update when a version here is newer than the installed one.

## Releasing a change

1. Edit files in `gg-site/` or `gg-theme/`.
2. Raise the version: `GGS_VER` and the `Version:` header in `gg-site/gg-site.php`, or `Version:` in `gg-theme/style.css`.
3. Run `./build.sh` and commit everything, including `dist/`.
4. On the website: Dashboard → Updates → Check again → update.

If the repository is made private, add a read-only GitHub access key in Settings → GG Site → Updates on the website.
