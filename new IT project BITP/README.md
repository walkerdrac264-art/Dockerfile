# ⚽ Riverside FC - Football Match Ticketing Website (PHP)

An enterprise-grade, modern, and user-friendly **Football Match Ticketing System** designed specifically for **Riverside FC** (*The Riverians*), playing home fixtures at **Riverside Arena, Accra, Ghana**.

All ticket pricing and financial reporting are fully denominated in **Ghana Cedis (GH₵ / GHS)**.

---

## 🌟 Key Highlights & Features

### 1. 🎟️ Public / Supporter Portal
- **Modern Club Aesthetic**: Deep navy and sky blue color palette, stylized Riverside FC vector crest, sleek typography (Outfit & Inter), and mobile-first responsive layout.
- **Matchday Spotlight & Live Countdown**: Dynamic real-time countdown to kick-off for the next blockbuster home fixture.
- **Fixtures Directory**: Filter by Upcoming, Past, or All matches with search by opponent or competition (Ghana Premier League, MTN FA Cup, CAF Champions League).
- **Interactive Stand & Seat Picker**:
  - Live capacity progress bars and real-time remaining seat indicators.
  - Choose between **VIP Executive Lounge (Gate A)**, **Main Grandstand (Gate B)**, **Popular Stand East (Gate C)**, **North Terrace Ultras (Gate D)**, and **Riverside Family Enclosure (Gate E)**.
  - Interactive ticket quantity stepper (1 to 10 seats) with live pricing calculation in Ghana Cedis.
- **Seamless Checkout & Payment Simulation**:
  - **MTN Mobile Money**, **Telecel Cash**, **Card (Visa/Mastercard)**, and **Box Office reservation**.
  - Guest checkout supported, with automatic account linking for logged-in fans.
- **Anti-Overselling Concurrency Protection**: Atomic SQL updates within database transactions (`UPDATE ticket_types SET remaining = remaining - :qty WHERE id = :id AND remaining >= :qty`) strictly prevent overselling.
- **Instant Digital E-Ticket with QR Code**:
  - Automatically generates unique tickets (`RFC-TKT-XXXXXX`) with high-resolution scannable QR codes.
  - Pass includes turnstile gate, seat section, holder name, kickoff time, and booking reference.
  - Dedicated **Print / Save as PDF** stylesheet optimized for mobile passes and A4 printing.
- **Supporter "My Tickets" Hub**: Retrieve past and upcoming match tickets anytime via login or quick order reference lookup.
- **🤖 RiverBot Intelligent Matchday & Ticketing Concierge**:
  - Floating, responsive matchday chatbot powered by asynchronous REST API (`/api/chatbot.php`).
  - **Match Finder**: Displays upcoming fixtures with live Ghanaian Cedis pricing, remaining seats, and 1-click booking cards.
  - **Stand Guidance**: Recommends best enclosure (VIP, Grandstand, Family, Ultras, Popular) based on fan preference.
  - **Instant Order & Pass Lookup**: Fans can type their Booking Reference (`RFC-2026-A109B`) or email to instantly retrieve passes and 1-click copy codes.
  - **Payment & MoMo Assistant**: Step-by-step guidance for MTN Mobile Money USSD authorization and Telecel Cash.
  - **Speech-to-Text & Melodic Audio Chimes**: Web Speech voice input and Web Audio API synthesizer for seamless accessibility.

---

### 2. 🛡️ Club Administration Console (`/admin`)
- **Protected Role-Based Authentication**: Secure login restricted to administrative staff (`admin@riversidefc.com`).
- **Live Operations Dashboard**:
  - **KPI Metrics**: Total Revenue in Ghana Cedis (`GH₵`), Tickets Sold Today, Total Bookings, Active Upcoming Fixtures.
  - **Interactive Chart.js Visualizations**: Revenue per fixture (Bar chart) and Stand sales distribution (Doughnut chart).
  - **Real-Time Stadium Capacity Monitor**: Visual progress bars and low-inventory warnings when sections reach critical occupancy.
- **Fixture Management (Full CRUD)**:
  - Add new home matches with opponent, competition, match date, kick-off time, and venue.
  - Configure capacity and Ghana Cedis pricing for each stand enclosure.
  - Edit existing fixture schedules or update stand ticket allocations.
  - Safe deletion checks (prevents deleting fixtures with active bookings).
- **Order & Booking Inspector**:
  - Search by reference code, supporter name, email, or match.
  - Inspect individual tickets and order audit trail.
  - One-click cancellation with **automatic inventory restocking** back to the stadium stand.
- **Turnstile Gate QR Scanner (`/admin/scan.php`)**:
  - Gate stewards can scan supporter QR codes via USB/Bluetooth barcode guns or device camera.
  - Instant gate verification:
    - **GREEN**: Valid ticket — admits supporter and marks pass as `used`.
    - **YELLOW/RED**: Pass already scanned — shows exact time previously redeemed to eliminate ticket pass-backs.
    - **RED**: Invalid or counterfeit ticket.
- **Sales & Financial Reports (`/admin/reports.php`)**:
  - Financial breakdown by fixture, stand category, and payment channel.
  - One-click **Export to CSV** for spreadsheets and accounting.

---

## 🛠️ Technology Stack

- **Backend**: Pure PHP 8.x (lightweight, clean modular architecture, no bloated external frameworks).
- **Database**: MySQL / MariaDB (Primary) with resilient SQLite auto-fallback. Prepared statements used for 100% of database queries.
- **Frontend**: HTML5, CSS3, JavaScript (Vanilla ES6), Bootstrap 5.3.3.
- **Charts & QR Codes**: Chart.js for dashboard analytics; standalone, zero-dependency SVG QR Code generator (works completely offline).
- **Security**: CSRF tokens on all state-changing forms, `password_hash()` BCRYPT authentication, role validation guards.
- **Currency**: Ghana Cedis (`GH₵` / `GHS`).

---

## 📁 Project Directory Structure

```text
├── assets/
│   ├── css/
│   │   ├── style.css             # Riverside FC design system & tokens
│   │   └── print-ticket.css      # Print & PDF media stylesheet
│   ├── js/
│   │   └── main.js               # Reactive stand picker, countdown, checkout logic
│   └── images/                   # Riverside FC crest & opponent club SVGs
├── config/
│   ├── config.php                # App constants, club details, Ghana Cedis configuration
│   └── database.php              # PDO MySQL connection with auto-fallback
├── database/
│   ├── schema.sql                # Complete MySQL schema + initial seed data
│   └── seed.php                  # Automated PHP database seeder
├── includes/
│   ├── auth.php                  # Authentication, session management, role guards
│   ├── functions.php             # Currency formatting (GH₵), CSRF, alerts
│   ├── qrcode.php                # Zero-dependency QR Code generator
│   ├── qr_svg.php                # Offline SVG QR fallback stream
│   ├── header.php                # Public navbar and head tags
│   ├── footer.php                # Public footer & scripts
│   ├── admin-header.php          # Admin sidebar & navigation
│   └── admin-footer.php          # Admin layout footer
├── admin/
│   ├── index.php                 # Operations dashboard, KPIs, & charts
│   ├── login.php                 # Secure admin login
│   ├── fixtures.php              # Fixture management listing
│   ├── fixture-create.php        # Add fixture & stand prices
│   ├── fixture-edit.php          # Edit fixture & adjust capacities
│   ├── bookings.php              # All orders & supporter search
│   ├── booking-detail.php        # Order inspector & cancellation
│   ├── scan.php                  # Turnstile QR gate scanner
│   └── reports.php               # Sales & financial analytics + CSV export
├── index.php                     # Supporter homepage & stadium map
├── fixtures.php                  # All home fixtures schedule & filter
├── fixture.php                   # Match details & interactive stand picker
├── checkout.php                  # MoMo & card checkout in Ghana Cedis
├── confirmation.php              # Order confirmation with digital QR tickets
├── ticket.php                    # Dedicated single matchday QR pass
├── my-tickets.php                # Supporter booking history & ticket lookup
├── login.php                     # Supporter sign-in
├── register.php                  # Supporter registration
├── logout.php                    # Sign-out handler
├── install.php                   # Web-based 1-click database installer & diagnostics
└── README.md                     # Documentation
```

---

## 🚀 Quick Setup & Installation

### Option 1: Using the Built-in PHP Server (Fastest, zero install)

1. Open PowerShell / Terminal in this project folder:
   ```powershell
   cd "c:\Users\DELL\Desktop\new IT project"
   ```
2. Start the local server:
   ```powershell
   php -S localhost:8000
   ```
3. Open your browser and navigate to:
   - **Public Site**: [http://localhost:8000/](http://localhost:8000/)
   - **Database Installer & Diagnostics**: [http://localhost:8000/install.php](http://localhost:8000/install.php)
   - **Admin Portal**: [http://localhost:8000/admin/](http://localhost:8000/admin/)

---

### Option 2: Using XAMPP / WampServer / Laragon

1. Copy or move this folder into your web root:
   - **XAMPP**: `C:\xampp\htdocs\riverside-fc`
   - **Laragon**: `C:\laragon\www\riverside-fc`
2. Start **Apache** and **MySQL** in your control panel.
3. Import the database schema:
   - Open **phpMyAdmin** ([http://localhost/phpmyadmin](http://localhost/phpmyadmin))
   - Create a database named `riverside_fc`
   - Click **Import** and select `database/schema.sql`
   - *OR* navigate to [http://localhost/riverside-fc/install.php](http://localhost/riverside-fc/install.php) and click **"Initialize & Seed Database Now"**!
4. Access the site at:
   - `http://localhost/riverside-fc/`

---

## 🔑 Pre-Configured Test Accounts

| Role | Email | Password | Access Portal |
| :--- | :--- | :--- | :--- |
| **Club Administrator** | `admin@riversidefc.com` | `admin123` | [Admin Portal](/admin/login.php) |
| **Supporter 1** | `kwame@example.com` | `user123` | [Supporter Portal](/login.php) |
| **Supporter 2** | `akosua@example.com` | `user123` | [Supporter Portal](/login.php) |

*(Note: Guest users can also buy tickets immediately without registering!)*

---

## 📲 How to Test the Full User Flow

1. **Supporter Journey**:
   - Go to [Homepage](http://localhost:8000/).
   - Click **"Book Tickets vs Hearts of Oak"**.
   - Select a stadium enclosure (e.g., **Main Grandstand** or **VIP Executive Lounge**).
   - Adjust ticket quantity with the `+` / `-` stepper.
   - Click **"Proceed to Checkout"**.
   - Fill in contact details and select **MTN Mobile Money** or **Telecel Cash**.
   - Click **"Pay & Generate QR Tickets"**.
   - View your digital ticket passes with unique QR codes, click **"Print / Save Pass"**, or access via **My Tickets**.

2. **Gate Steward Scanning Journey**:
   - Log into the [Admin Console](http://localhost:8000/admin/) using `admin@riversidefc.com` / `admin123`.
   - Go to **"Gate QR Scanner"**.
   - Enter or scan the ticket code (e.g., `RFC-TKT-KM01A`).
   - Click **"Verify"** to see the green verification badge.
   - Click **"ADMIT & RECORD GATE CHECK-IN"**.
   - If scanned a second time, the screen alerts **ALREADY SCANNED** with the exact check-in timestamp.

3. **Club Manager Journey**:
   - Open the **Admin Dashboard** to monitor live ticket revenue in Ghana Cedis (`GH₵`), tickets sold today, and stand occupancy bars.
   - Go to **Fixture Management** to add next week's fixture or update prices.
   - Go to **Sales Reports** to review revenue by match and download the **CSV Export**.

---

## 🔒 Security Best Practices Implemented

- **Prepared Statements Everywhere**: Zero raw SQL queries; fully immune to SQL Injection attacks.
- **Cross-Site Request Forgery (CSRF)**: Cryptographic tokens validated on all POST forms.
- **XSS Mitigation**: Contextual escaping using `htmlspecialchars()` for all dynamic outputs.
- **Secure Password Storage**: Industry-standard `PASSWORD_DEFAULT` (BCRYPT) hashing.
- **Anti-Oversell Locks**: Strict SQL transaction checks preventing capacity overrun.
