# ShopSync — Windows e Run korar Guide (Bangla)

> Ei guide ta pore project ta Windows PC te **ek command diye** full setup + run
> korte parba. Detail explanation er jonno `VIVA_GUIDE_BANGLA.md` dekho.

---

## 1. Age ja lagbe (Prerequisites)

Setup script (`setup-windows.ps1`) nijei sob kore — kintu PHP khub khoti hoy
na thakle script nije install korte parbe na, tai ekbar bujhe rakho:

| Software | Keno lagbe | Script ki kore |
| --- | --- | --- |
| **PHP 8.3+** | Laravel er language | Khuje ber kore; na pabe path dekhay |
| **Composer** | PHP dependency install | Na thakle **winget diye nije install kore** |
| **Node.js 20+ (npm)** | Frontend build (Tailwind/Vite) | Na thakle **winget diye nije install kore** |
| Database | Data rakhbo | **Lagbe na!** SQLite use hoy — project er bhitorei file |

**Sob cheye sohoja path:** script ta prothom bar chalale Composer ar Node.js
nije install kore fele (internet + koyek minute lagbe). PHP jodi na thake,
script clear instruction debe.

> **Shortcut:** [Laragon](https://laragon.org) full version install korle
> PHP + Composer + Node ek sathe automatically thake — tarpor script direct
> kaj korbe. XAMPP o cholbe (script `C:\xampp\php\php.exe` khudhe pabe).

---

## 2. ONE COMMAND — Setup + Run

### Option A: Double-click (sob cheye sohoj)

1. Puro project folder ta Windows PC te copy koro (jemon `C:\Users\TomarName\shopsync`)
2. Folder e **`setup-windows.bat`** file ta **double-click** koro
3. Finished hobar por browser e jan: **http://127.0.0.1:8000**

### Option B: PowerShell theke

```powershell
cd C:\Users\TomarName\shopsync
powershell -ExecutionPolicy Bypass -File .\setup-windows.ps1
```

> "Running scripts is disabled" type error asle `-ExecutionPolicy Bypass`
> flag ta thik ei jonne — bat file ta already ei flag use kore.

### Script ta ki ki kore (automatically)

1. PHP khuje (XAMPP / C:\php / winget sob jaygay), version 8.3+ check
2. `php.ini` te doroner PHP extension on kore (`pdo_sqlite`, `mbstring`, `openssl`...)
3. Composer na thakle winget diye install
4. Node.js na thakle winget diye install
5. `composer install` — PHP library gulo (`vendor/` folder)
6. Notun `.env` banay (**SQLite** mode — database install korar dorkar nei).
   Purano `.env` thakle backup kore rake (`.env.backup-<tarikh>`)
7. `php artisan key:generate` + `php artisan migrate` — database table toiri
8. `php artisan shopsync:seed-demo --force` — **demo data** (account, product,
   order sob) import kore. Internet na thakle image chara try kore.
9. `npm install` + `npm run build` — frontend assets
10. `php artisan serve` — server chalu, browser e `http://127.0.0.1:8000`

**Bandh korte:** terminal e `Ctrl + C`.

**Pore abar chalate:** ar full setup lagbe na — sudhu `php artisan serve`
(mane `php artisan serve` command ta terminal e chalao, section 3 dekho).

---

## 3. Pore protibar kivabe run korbe (setup ses, sudhu run)

Setup ekbar hoye gele, protibar sudhu ei duita command:

```bat
cd C:\Users\TomarName\shopsync
php artisan serve
```

Erbapor browser e: **http://127.0.0.1:8000**

`php` command na chinarle full path likho (jemon `C:\php\php.exe artisan serve`
ba XAMPP hole `C:\xampp\php\php.exe artisan serve`), ba PHP er `C:\php` folder
ta PATH e add kore daw.

---

## 4. Demo Login (password sobai: `password`)

| Role | Email |
| --- | --- |
| Admin | `admin@shopsync.test` |
| Business Owner | `dataset.owner@shopsync.test` |
| Customer (order history ache) | `customer01@shopsync.test` |
| Customer (khali — nijei order korte parba) | `customer23@shopsync.test` |

---

## 5. (Optional) Nijer asol database backup ta use korte chaille

Script default e **SQLite + demo data** dey — lab demo er jonno eta best.
Kintu tomar Linux PC er MariaDB backup (`smart_shoping`) Windows eo restore
korte chaille MySQL/MariaDB lagbe:

1. **XAMPP** install koro → XAMPP Control Panel e **MySQL** start koro
2. Browser e `http://localhost/phpmyadmin` → notun database banao (jemon
   `smart_shoping`, collation: `utf8mb4_unicode_ci`)
3. **Import** tab → tomar `.sql` backup file select → Go
4. Project folder er `.env` file khule DB part ta change koro:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=smart_shoping
DB_USERNAME=root
DB_PASSWORD=
```

5. Cache clear + server:

```bat
php artisan config:clear
php artisan serve
```

> ⚠️ Kheyal koro: SQLite mode e je demo data seed hoyechilo, MySQL switch
> korle segulo pabe na — MySQL e tomar restore kora backup er data thakbe.

---

## 6. (Optional) Real-time Chat chalate chaile (Reverb)

Script chat ta **refresh-mode** e rakhe (kaj kore, kintu instant na).
Real-time on korte:

1. `.env` e ei line change koro:
   ```env
   BROADCAST_CONNECTION=reverb
   ```
2. `npm run build` abar chalao (VITE_ variable change)
3. **Notun ek terminal** khule:
   ```bat
   php artisan reverb:start
   ```
4. Aregulo terminal e `php artisan serve` choluk
5. Ekhon duita browser window e customer ar owner login kore chat korle
   instant message jabe

---

## 7. Somossa asle (Troubleshooting)

| Somossa | Somadhan |
| --- | --- |
| `php` is not recognized | PHP install koro (guide er section 1), ba path ta script ke dekhao — `C:\php` e rakhle script nije pabe. Install er por **terminal restart** koro |
| `extension=pdo_sqlite` / `mbstring` missing | Script nije fix kore. Na hole `php.ini` khule `extension=pdo_sqlite`, `extension=mbstring`, `extension=openssl`, `extension=zip` line gulo uncomment koro, abar run koro |
| Composer install e SSL/certificate error | Purano antivirus/proxy check koro; ba `composer config -g -- disable-tls false` thik ache kina dekho |
| `npm install` fail | Node.js LTS version install koro, terminal restart kore abar |
| Page khulle "Unable to locate file in Vite manifest" | `npm run build` chalao, tarpor `php artisan config:clear` |
| Page khulle 500 / log e DB error | `.env` er DB connection thik ache kina dekho; `php artisan migrate` chalano hoyechilo kina |
| Port 8000 already in use | `php artisan serve --port=8080` |
| Seed e "cURL error" | Internet lagbe; thik hole abar `php artisan shopsync:seed-demo --force`, offline hole `--skip-images` |
| Image gulo dekha jacche na | `php artisan storage:link` chalao, tarpor browser cache hard-refresh (Ctrl+Shift+R) |
| "419 Page Expired" login e | Browser cache clear + cookie clear koro, abar try koro |

---

## 8. Ek nojor e sob command

```bat
:: Prothom bar (script sob kaj kore):
setup-windows.bat

:: Pore protibar:
php artisan serve

:: Demo data abar seed (fresh data ante):
php artisan migrate:fresh --force
php artisan shopsync:seed-demo --force

:: Test chalate:
php artisan test --compact
```

Viva preparation er jonno `VIVA_GUIDE_BANGLA.md` file ta poro — puro
project er code explanation, file path, connection map, ar expected
questions answers soho ache. 🎓
