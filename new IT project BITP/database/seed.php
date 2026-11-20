<?php
/**
 * Riverside FC - Database Seeder
 * Populates tables and seed data for both MySQL and SQLite
 */

function seedDatabase(PDO $pdo, string $driver = 'mysql'): void {
    // Admin password: admin123
    $adminHash = password_hash('admin123', PASSWORD_DEFAULT);
    // Supporter password: user123
    $userHash = password_hash('user123', PASSWORD_DEFAULT);

    if ($driver === 'sqlite') {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                email TEXT NOT NULL UNIQUE,
                phone TEXT DEFAULT NULL,
                password TEXT NOT NULL,
                role TEXT NOT NULL DEFAULT 'supporter',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS fixtures (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                opponent TEXT NOT NULL,
                opponent_logo TEXT DEFAULT NULL,
                competition TEXT NOT NULL DEFAULT 'Ghana Premier League',
                match_date DATE NOT NULL,
                kick_off TIME NOT NULL,
                venue TEXT NOT NULL DEFAULT 'Riverside Arena, Accra',
                status TEXT NOT NULL DEFAULT 'upcoming',
                description TEXT DEFAULT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS ticket_types (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                fixture_id INTEGER NOT NULL,
                name TEXT NOT NULL,
                description TEXT DEFAULT NULL,
                gate TEXT DEFAULT 'Gate A',
                price NUMERIC NOT NULL,
                capacity INTEGER NOT NULL,
                remaining INTEGER NOT NULL,
                FOREIGN KEY (fixture_id) REFERENCES fixtures(id) ON DELETE CASCADE
            );

            CREATE TABLE IF NOT EXISTS bookings (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                booking_reference TEXT NOT NULL UNIQUE,
                user_id INTEGER DEFAULT NULL,
                customer_name TEXT NOT NULL,
                customer_email TEXT NOT NULL,
                customer_phone TEXT NOT NULL,
                fixture_id INTEGER NOT NULL,
                total_amount NUMERIC NOT NULL,
                payment_method TEXT NOT NULL DEFAULT 'momo_mtn',
                payment_status TEXT NOT NULL DEFAULT 'paid',
                notes TEXT DEFAULT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
                FOREIGN KEY (fixture_id) REFERENCES fixtures(id) ON DELETE RESTRICT
            );

            CREATE TABLE IF NOT EXISTS booking_items (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                booking_id INTEGER NOT NULL,
                ticket_type_id INTEGER NOT NULL,
                quantity INTEGER NOT NULL,
                unit_price NUMERIC NOT NULL,
                subtotal NUMERIC NOT NULL,
                FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
                FOREIGN KEY (ticket_type_id) REFERENCES ticket_types(id) ON DELETE RESTRICT
            );

            CREATE TABLE IF NOT EXISTS tickets (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                booking_id INTEGER NOT NULL,
                ticket_type_id INTEGER NOT NULL,
                ticket_code TEXT NOT NULL UNIQUE,
                qr_data TEXT NOT NULL,
                status TEXT NOT NULL DEFAULT 'valid',
                used_at DATETIME DEFAULT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
                FOREIGN KEY (ticket_type_id) REFERENCES ticket_types(id) ON DELETE RESTRICT
            );
        ");
    }

    // Check if users already seeded
    $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM users");
    $cnt = $stmt->fetch()['cnt'] ?? 0;
    if ($cnt > 0) {
        return; // Already seeded
    }

    // Insert Users
    $userStmt = $pdo->prepare("INSERT INTO users (name, email, phone, password, role) VALUES (?, ?, ?, ?, ?)");
    $userStmt->execute(['Riverside Admin', 'admin@riversidefc.com', '+233245550190', $adminHash, 'admin']);
    $userStmt->execute(['Kwame Mensah', 'kwame@example.com', '+233241234567', $userHash, 'supporter']);
    $userStmt->execute(['Akosua Boateng', 'akosua@example.com', '+233209876543', $userHash, 'supporter']);

    // Insert Fixtures
    $fixStmt = $pdo->prepare("INSERT INTO fixtures (opponent, opponent_logo, competition, match_date, kick_off, venue, status, description) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    
    $today = date('Y-m-d');
    $date1 = date('Y-m-d', strtotime('+4 days'));
    $date2 = date('Y-m-d', strtotime('+11 days'));
    $date3 = date('Y-m-d', strtotime('+18 days'));
    $date4 = date('Y-m-d', strtotime('+25 days'));
    $date5 = date('Y-m-d', strtotime('-7 days'));

    $fixStmt->execute(['Hearts of Oak', 'hearts_of_oak.svg', 'Ghana Premier League', $date1, '15:00:00', 'Riverside Arena, Accra', 'upcoming', 'The Greater Accra derby! High-intensity clash as Riverside FC hosts the Phobians under the floodlights.']);
    $fix1 = $pdo->lastInsertId();

    $fixStmt->execute(['Asante Kotoko SC', 'kotoko.svg', 'Ghana Premier League', $date2, '16:00:00', 'Riverside Arena, Accra', 'upcoming', 'The Porcupine Warriors visit Riverside Arena for a title-defining showdown. Expect a packed stadium!']);
    $fix2 = $pdo->lastInsertId();

    $fixStmt->execute(['Medeama SC', 'medeama.svg', 'MTN FA Cup - Quarter Final', $date3, '15:30:00', 'Riverside Arena, Accra', 'upcoming', 'Cup fever returns to Riverside. Knockout football where only victory secures a semi-final ticket.']);
    $fix3 = $pdo->lastInsertId();

    $fixStmt->execute(['Dreams FC', 'dreams.svg', 'Ghana Premier League', $date4, '15:00:00', 'Riverside Arena, Accra', 'upcoming', 'Sunday afternoon league encounter featuring two attacking sides known for fast, fluid football.']);
    $fix4 = $pdo->lastInsertId();

    $fixStmt->execute(['Great Olympics', 'olympics.svg', 'Ghana Premier League', $date5, '15:00:00', 'Riverside Arena, Accra', 'completed', 'Riverside FC claimed a decisive 2-1 win over the Wonder Club in front of 18,500 roaring fans.']);
    $fix5 = $pdo->lastInsertId();

    // Insert Ticket Types
    $typeStmt = $pdo->prepare("INSERT INTO ticket_types (fixture_id, name, description, gate, price, capacity, remaining) VALUES (?, ?, ?, ?, ?, ?, ?)");

    // Fixture 1 Sections
    $typeStmt->execute([$fix1, 'VIP Executive Lounge', 'Padded luxury seats, match programme, air-conditioned hospitality lounge & refreshments', 'Gate A - VIP', 150.00, 200, 48]);
    $t1 = $pdo->lastInsertId();
    $typeStmt->execute([$fix1, 'Main Grandstand', 'Covered central seating with premium elevated pitch view', 'Gate B - West', 80.00, 3000, 892]);
    $t2 = $pdo->lastInsertId();
    $typeStmt->execute([$fix1, 'Popular Stand (East)', 'Atmospheric covered stand directly opposite the players tunnel', 'Gate C - East', 40.00, 8000, 2410]);
    $typeStmt->execute([$fix1, 'North Terrace (Ultras Stand)', 'Safe standing & singing section behind the home goal', 'Gate D - North', 30.00, 5000, 1150]);
    $typeStmt->execute([$fix1, 'Riverside Family Enclosure', 'Alcohol-free family section with dedicated junior amenities', 'Gate E - South', 50.00, 1500, 620]);

    // Fixture 2 Sections
    $typeStmt->execute([$fix2, 'VIP Executive Lounge', 'Padded luxury seats, match programme & hospitality refreshments', 'Gate A - VIP', 160.00, 200, 110]);
    $typeStmt->execute([$fix2, 'Main Grandstand', 'Covered central seating with premium elevated view', 'Gate B - West', 85.00, 3000, 1450]);
    $t7 = $pdo->lastInsertId();
    $typeStmt->execute([$fix2, 'Popular Stand (East)', 'Atmospheric stand with energetic home crowd support', 'Gate C - East', 45.00, 8000, 4200]);
    $typeStmt->execute([$fix2, 'North Terrace (Ultras Stand)', 'Behind the goal dedicated singing stand', 'Gate D - North', 35.00, 5000, 2800]);
    $typeStmt->execute([$fix2, 'Riverside Family Enclosure', 'Family seating area with entertainment', 'Gate E - South', 55.00, 1500, 940]);

    // Fixture 3 Sections
    $typeStmt->execute([$fix3, 'VIP Executive Lounge', 'VIP entry, executive match seat and lounge hospitality', 'Gate A - VIP', 140.00, 200, 175]);
    $typeStmt->execute([$fix3, 'Main Grandstand', 'Elevated prime view of cup knockout action', 'Gate B - West', 75.00, 3000, 2100]);
    $typeStmt->execute([$fix3, 'Popular Stand (East)', 'Affordable pitch-side general admission', 'Gate C - East', 35.00, 8000, 5600]);
    $typeStmt->execute([$fix3, 'North Terrace (Ultras Stand)', 'Behind goal energetic terrace', 'Gate D - North', 25.00, 5000, 3900]);

    // Fixture 4 Sections
    $typeStmt->execute([$fix4, 'VIP Executive Lounge', 'VIP Box hospitality', 'Gate A - VIP', 130.00, 200, 190]);
    $typeStmt->execute([$fix4, 'Main Grandstand', 'Grandstand seating', 'Gate B - West', 70.00, 3000, 2600]);
    $typeStmt->execute([$fix4, 'Popular Stand (East)', 'East stand admission', 'Gate C - East', 35.00, 8000, 7100]);
    $typeStmt->execute([$fix4, 'North Terrace (Ultras Stand)', 'North terrace general access', 'Gate D - North', 25.00, 5000, 4500]);

    // Fixture 5 Sections (Completed)
    $typeStmt->execute([$fix5, 'VIP Executive Lounge', 'VIP Seating', 'Gate A - VIP', 150.00, 200, 0]);
    $typeStmt->execute([$fix5, 'Main Grandstand', 'Main Seating', 'Gate B - West', 80.00, 3000, 120]);
    $typeStmt->execute([$fix5, 'Popular Stand (East)', 'East Stand', 'Gate C - East', 40.00, 8000, 450]);

    // Insert Sample Bookings
    $bookStmt = $pdo->prepare("INSERT INTO bookings (booking_reference, user_id, customer_name, customer_email, customer_phone, fixture_id, total_amount, payment_method, payment_status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $itemStmt = $pdo->prepare("INSERT INTO booking_items (booking_id, ticket_type_id, quantity, unit_price, subtotal) VALUES (?, ?, ?, ?, ?)");
    $tktStmt  = $pdo->prepare("INSERT INTO tickets (booking_id, ticket_type_id, ticket_code, qr_data, status, created_at) VALUES (?, ?, ?, ?, ?, ?)");

    // Booking 1: Kwame Mensah (2 tickets Main Grandstand)
    $bookStmt->execute(['RFC-2026-A109B', 2, 'Kwame Mensah', 'kwame@example.com', '+233241234567', $fix1, 160.00, 'momo_mtn', 'paid', date('Y-m-d H:i:s', strtotime('-2 days'))]);
    $b1 = $pdo->lastInsertId();
    $itemStmt->execute([$b1, $t2, 2, 80.00, 160.00]);
    $tktStmt->execute([$b1, $t2, 'RFC-TKT-KM01A', 'RFC-TKT-KM01A|REF:RFC-2026-A109B|FIX:1|STAND:Main Grandstand|HOLDER:Kwame Mensah', 'valid', date('Y-m-d H:i:s', strtotime('-2 days'))]);
    $tktStmt->execute([$b1, $t2, 'RFC-TKT-KM02B', 'RFC-TKT-KM02B|REF:RFC-2026-A109B|FIX:1|STAND:Main Grandstand|HOLDER:Kwame Mensah', 'valid', date('Y-m-d H:i:s', strtotime('-2 days'))]);

    // Booking 2: Akosua Boateng (1 VIP ticket)
    $bookStmt->execute(['RFC-2026-B882C', 3, 'Akosua Boateng', 'akosua@example.com', '+233209876543', $fix1, 150.00, 'momo_telecel', 'paid', date('Y-m-d H:i:s', strtotime('-1 days'))]);
    $b2 = $pdo->lastInsertId();
    $itemStmt->execute([$b2, $t1, 1, 150.00, 150.00]);
    $tktStmt->execute([$b2, $t1, 'RFC-TKT-AB01X', 'RFC-TKT-AB01X|REF:RFC-2026-B882C|FIX:1|STAND:VIP Executive Lounge|HOLDER:Akosua Boateng', 'valid', date('Y-m-d H:i:s', strtotime('-1 days'))]);

    // Booking 3: Kofi Addo (2 tickets Kotoko match)
    $bookStmt->execute(['RFC-2026-C994D', null, 'Kofi Addo', 'kofi.addo@gmail.com', '+233540001122', $fix2, 170.00, 'card', 'paid', date('Y-m-d H:i:s')]);
    $b3 = $pdo->lastInsertId();
    $itemStmt->execute([$b3, $t7, 2, 85.00, 170.00]);
    $tktStmt->execute([$b3, $t7, 'RFC-TKT-KA01Y', 'RFC-TKT-KA01Y|REF:RFC-2026-C994D|FIX:2|STAND:Main Grandstand|HOLDER:Kofi Addo', 'valid', date('Y-m-d H:i:s')]);
    $tktStmt->execute([$b3, $t7, 'RFC-TKT-KA02Z', 'RFC-TKT-KA02Z|REF:RFC-2026-C994D|FIX:2|STAND:Main Grandstand|HOLDER:Kofi Addo', 'valid', date('Y-m-d H:i:s')]);
}
