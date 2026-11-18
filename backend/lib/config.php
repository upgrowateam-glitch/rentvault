<?php
// ===== EDIT THESE VALUES (from Hostinger hPanel → Databases → MySQL Databases) =====
const DB_HOST = 'localhost';
const DB_NAME = 'u000000000_rentvault';   // your database name
const DB_USER = 'u000000000_rentadmin';   // your database user
const DB_PASS = 'CHANGE_ME';              // your database password

// Public base URL of the subdomain (no trailing slash)
const BASE_URL = 'https://rentdocs.upgrowa.com';

// Secret key required once by install.php to create the admin account.
// Change to any long random string, then delete install.php after setup.
const INSTALL_KEY = 'CHANGE_ME_TO_A_LONG_RANDOM_STRING';

// Max upload size per document (bytes)
const MAX_UPLOAD_BYTES = 10 * 1024 * 1024; // 10 MB
