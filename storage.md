# Public files ka storage (bina `storage:link` ke)

Yeh document batata hai ke hum ne Hostinger / shared hosting par **uploaded files** (payment slip, enrollment discount file, therapist documents) ka issue kaise fix kiya — **bina** `php artisan storage:link` chalaye.

---

## Problem kya thi?

Laravel normally yeh command chalwata hai:

```bash
php artisan storage:link
```

Is se `public/storage` folder **link** hota hai `storage/app/public` se. Phir browser mein URL:

```text
https://site.com/storage/payments/slips/file.jpg
```

direct file dikha deti hai.

**Hostinger (aur kai shared hosts) par PHP ki `symlink()` function band hoti hai.** Is liye command fail hoti thi:

```text
Call to undefined function Illuminate\Filesystem\symlink()
```

Aur live par files **view nahi** ho pa rahi thin — slip / document links broken the.

---

## Laravel normally kya karta hai?

1. File save hoti hai: `storage/app/public/...`
2. Link banani padti hai: `public/storage` → `storage/app/public`
3. Browser URL: `/storage/...` web server seedha file serve karta hai

Hum ne **step 2 hata diya** — link ki zaroorat nahi.

---

## Hum ne kya solution lagaya?

**Files ab bhi wahi save hoti hain** (`storage/app/public/`), lekin browser ko file **Laravel route** se milti hai.

### 4 cheezein add ki:

| # | Kaam | Kahan |
|---|------|--------|
| 1 | URL `/storage/...` pakro | `routes/web.php` |
| 2 | Disk se file read karke browser ko bhejo | `PublicStorageController.php` |
| 3 | Apache ko bolo `/storage/*` Laravel ko bheje | `public/.htaccess` |
| 4 | Sahi URL banane ka helper | `frc_storage_url()` in `app/helpers.php` |

**Ab `php artisan storage:link` local ya live — kahin bhi chalane ki zaroorat nahi.**

---

## Flow (simple)

```text
User browser mein kholta hai:
  https://frc-software.codefusionsol.com/storage/payments/slips/abc.jpg

       ↓
public/.htaccess → request index.php (Laravel) par jati hai

       ↓
Route: GET /storage/{path}

       ↓
PublicStorageController file dhundhta hai:
  storage/app/public/payments/slips/abc.jpg

       ↓
File milti hai → browser mein image/PDF dikhti hai
```

### Security (important)

Controller mein checks hain:

- `..` wala path block (koi aur folder na khul jaye)
- File sirf `storage/app/public` ke andar honi chahiye
- File na ho to **404**

---

## Code kahan hai?

- **Controller:** `app/Http/Controllers/Web/PublicStorageController.php`
- **Route:** `routes/web.php` — line jahan `storage/{path}` hai
- **Helper:** `app/helpers.php` — function `frc_storage_url($path)`
- **htaccess:** `public/.htaccess` — `RewriteRule ^storage/(.*)$ index.php`
- **Payment slip link:** `Payment` model → `payment_slip_url` ab `frc_storage_url()` use karta hai

Views mein bhi jahan file link ho, wahan:

```blade
{{ frc_storage_url($enrollment->discount_file) }}
```

use karo — purana `asset('storage/...')` bhi same URL banata hai, lekin helper clear hai.

---

## Upload ab bhi kahan save hoti hai?

Code change nahi — pehle jaisa:

```php
$file->store('payments/slips', 'public');
```

Server par asal jagah:

```text
storage/app/public/payments/slips/...
storage/app/public/enrollments/discount-files/...
```

Database mein sirf **path** save hota hai, jaise:

```text
payments/slips/xyz123.jpg
```

Poora URL database mein nahi.

---

## Live (Hostinger) par kya karna hai?

1. Latest code deploy karo (controller + route + htaccess + helpers)
2. **`php artisan storage:link` mat chalao** — error aayega aur ab zaroorat bhi nahi
3. Agar `public/storage` naam ka **khali / broken** folder ho to delete kar sakte ho (optional)
4. Cache clear:

```bash
php artisan optimize:clear
php artisan config:cache
```

5. `.env` mein `APP_URL` sahi domain ho (jaise `https://frc-software.codefusionsol.com`)

### Test kaise karein?

Browser mein kholo:

```text
https://apni-domain.com/storage/payments/slips/FILENAME.jpg
```

Agar woh file server par `storage/app/public/payments/slips/` mein hai to dikh jani chahiye.

---

## Local par

- Symlink bana ho ya na ho — dono chal sakta hai
- Route wala tareeqa hamesha kaam karega
- Nayi coding mein `frc_storage_url()` use karna best hai

---

## Bonus: `frc_pkr()` wala error (alag fix)

Kabhi live par dashboard par `Call to undefined function frc_pkr()` aata tha.

**Wajah:** `app/helpers.php` server par load nahi ho rahi thi (composer autoload / deploy issue).

**Fix:** `AppServiceProvider` mein `require_once app/helpers.php` — ab helpers hamesha load hote hain.

Yeh storage se alag issue tha, lekin aksar deploy ke baad dono ek sath notice hote hain.

---

## Pehle vs ab (short)

| Pehle | Ab |
|--------|-----|
| `storage:link` zaroori thi | **Zaroorat nahi** |
| Hostinger par symlink error | Custom route se file serve |
| Live par slip/file nahi khulti | URL se khul jati hai |
| Deploy par extra command | Sirf code deploy + cache clear |

**Files ki jagah same hai** — sirf browser tak file pohanchane ka tareeqa badla hai (symlink ki jagah Laravel route).
