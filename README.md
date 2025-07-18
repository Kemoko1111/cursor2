# MenteeGo

MenteeGo is a web-based mentor–mentee matching platform for the ACES organisation. It intelligently pairs mentees and mentors based on skills, preferences and availability, while respecting the following business rules:

* A mentee may send requests to multiple mentors but can accept only **one** mentor.
* A mentor may accept up to **three** mentees.

## Features

1. User registration & authentication (email verification)
2. Role-based access control (Admin / Mentor / Mentee)
3. Profile management – skills, bio, availability
4. Intelligent matching & request workflow
5. Real-time messaging between matched users
6. Notifications & email alerts (PHPMailer)
7. Admin dashboard with user & match management
8. Fully-responsive UI built with HTML, CSS, JS & Bootstrap 5

---

## Tech Stack

* **PHP 8.1+**
* **MySQL 8** (managed with phpMyAdmin)
* **Eloquent ORM** (Illuminate/Database package – no full Laravel install required)
* **PHPMailer** for transactional email
* **Vanilla JS + Bootstrap 5** front-end

---

## Local Setup

1. Install PHP 8.1+, Composer and MySQL.
2. Clone this repository and install PHP dependencies:

```bash
composer install
```
3. Copy the example environment file and fill in your local credentials:

```bash
cp .env.example .env
```

```
DB_HOST=127.0.0.1
DB_NAME=menteego
DB_USER=root
DB_PASS=
MAIL_HOST=smtp.example.com
MAIL_USER=postmaster@example.com
MAIL_PASS=secret
MAIL_PORT=587
```

4. Create a database named `menteego` then import `database.sql` via phpMyAdmin or CLI:

```bash
mysql -u root -p menteego < database.sql
```

5. Run the built-in PHP server at the project root:

```bash
composer start
```

Visit `http://localhost:8000` in your browser.

---

## Project Structure

```
.
├── public/          # Web root (index.php, assets, views)
├── src/             # PHP source code (Models, Controllers, Helpers)
├── vendor/          # Composer dependencies (generated)
├── database.sql     # Normalised MySQL schema
├── .env.example     # Sample environment variables
└── README.md        # This file
```

---

## Deployment Notes

* Configure your virtual host to point to the `public/` directory.
* Ensure `vendor/` is present on the server (`composer install --no-dev --optimize-autoloader`).
* Adjust file/folder permissions so that PHP can write to `public/uploads` (profile pictures) and `storage/` (if later added).

---

## License

MIT © 2023 ACES