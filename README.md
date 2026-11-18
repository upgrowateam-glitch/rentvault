# RentVault

Admin-only mobile app (React Native / Expo) + PHP/MySQL backend on Hostinger.

- `backend/` → upload to the **rentdocs** subdomain folder on Hostinger
- `mobile/`  → the admin app (Android / iOS)

Customer URLs: `https://rentdocs.upgrowa.com/vehicle1`, `/vehicle2`, …
The root `https://rentdocs.upgrowa.com` shows only "Page not found".

## 1. Hostinger setup

1. **hPanel → Domains → Subdomains**: create `rentdocs` for `upgrowa.com`.
   Note its folder (usually `public_html/rentdocs`).
2. **hPanel → Security → SSL**: make sure SSL is active for `rentdocs.upgrowa.com`.
3. **hPanel → Databases → MySQL Databases**: you already have a database. Note its name, user and password.
4. **hPanel → Advanced → PHP Configuration**: PHP 8.1 or newer. Make sure `upload_max_filesize` and `post_max_size` are at least 12M.
5. Edit `backend/lib/config.php`: set `DB_NAME`, `DB_USER`, `DB_PASS`, and a long random `INSTALL_KEY`.
6. Upload **everything inside `backend/`**, including the hidden `.htaccess` files, into the subdomain folder with File Manager.
7. Open `https://rentdocs.upgrowa.com/install.php`. Enter the install key, admin username and password.
   This creates the tables and the single admin account.
8. **Delete `install.php`** from the server.

## 2. Run the app (development)

```bash
cd mobile
npx expo start
```
Scan the QR code with the **Expo Go** app on your phone.

## 3. Build an installable Android app (APK)

```bash
cd mobile
npx eas-cli@latest login
npx eas-cli@latest build:configure
npx eas-cli@latest build -p android --profile preview   # gives an .apk download link
```
To get an APK instead of an AAB, set `"buildType": "apk"` under `build.preview.android` in `eas.json`.

## Storage

MySQL stores vehicle details and file metadata only. The files themselves go in `storage/` on Hostinger's disk.
`storage/` is blocked from direct web access, and files are served only through `/vehicleN/<doc>`.
25 vehicles × 4 documents ≈ 100–300 MB, which is well within shared hosting limits. No Cloudinary needed.

## Admin password reset

No sign-up exists by design. To change the password, run this in phpMyAdmin with a hash made by PHP's `password_hash()`:
`UPDATE admins SET password_hash='<new hash>' WHERE username='<user>';`
