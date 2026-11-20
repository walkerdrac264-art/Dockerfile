<?php
/**
 * Riverside FC - RiverBot Intelligent Matchday & Ticketing API
 * Supports buyers with match selection, stand advice, payment guidance, and order tracking.
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

// Parse incoming request JSON or POST
$rawInput = file_get_contents('php://input');
$data = [];
if (!empty($rawInput)) {
    $data = json_decode($rawInput, true) ?: [];
}
if (empty($data)) {
    $data = $_POST;
}

$message = trim($data['message'] ?? '');
$action = trim($data['action'] ?? '');
$context = $data['context'] ?? [];
$currentUser = currentUser();

try {
    $db = getDB();
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'reply' => "Sorry, I am having trouble connecting to the ticketing system database right now. Please try again shortly or contact the box office at " . CLUB_PHONE,
        'quick_replies' => ['Try Again', 'Contact Support']
    ]);
    exit;
}

// Helper to sanitize and prepare responses
function botResponse(string $reply, array $quickReplies = [], array $cards = [], string $intent = 'general'): void {
    echo json_encode([
        'success' => true,
        'reply' => $reply,
        'quick_replies' => $quickReplies,
        'cards' => $cards,
        'intent' => $intent
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// -------------------------------------------------------------
// 1. Initial Greeting / Context Handshake
// -------------------------------------------------------------
if ($action === 'init') {
    $currentPage = $context['page'] ?? '';
    $greeting = "Hello! 👋 I'm **RiverBot**, your official Riverside FC Matchday & Ticketing Concierge.";
    
    if ($currentUser) {
        $greeting = "Welcome back, **" . h($currentUser['name']) . "**! ⚽ I'm **RiverBot**, your matchday concierge.";
    }

    $pageNotice = "";
    $quick = ['🔥 Upcoming Matches', '🎟️ How to Buy Tickets', '🏟️ Compare Stands', '💳 Payment & MoMo', '🔍 Track My Booking', '📍 Stadium & Gates'];

    if (str_contains($currentPage, 'checkout.php')) {
        $pageNotice = "\n\nI see you are on the **Checkout** page! Need help with **MTN MoMo**, **Telecel Cash**, or seat verification?";
        $quick = ['💳 How to Pay with MoMo', '📱 QR Code Entry Guide', '🏟️ Compare Stands', '🔥 Upcoming Matches'];
    } elseif (str_contains($currentPage, 'fixture.php')) {
        $pageNotice = "\n\nLooking at match tickets right now? Ask me about **stand views**, **prices**, or which enclosure is best for you!";
        $quick = ['🏟️ Compare Stands', '👨‍👩‍👧 Family Enclosure Info', '🌟 VIP Lounge Info', '💳 Payment & MoMo'];
    } elseif (str_contains($currentPage, 'my-tickets.php')) {
        $pageNotice = "\n\nNeed to find an order or print your passes? Enter your **Order Reference** (e.g. `RFC-2026-A109B`) or your email below!";
        $quick = ['🔍 Track My Booking', '📱 How QR Turnstiles Work', '🔥 Upcoming Matches'];
    }

    $welcomeText = $greeting . "\n\nI can help you:\n" .
        "• 🎟️ **Find & Buy Tickets** for upcoming home fixtures in Ghana Cedis (GH₵)\n" .
        "• 🏟️ **Pick the Best Stand** (VIP, Grandstand, Family Enclosure, Ultras, Popular)\n" .
        "• 💳 **Pay smoothly** with MTN MoMo, Telecel Cash, or Card\n" .
        "• 🔍 **Track your order** and download scannable QR passes" . $pageNotice;

    botResponse($welcomeText, $quick, [], 'welcome');
}

// If message is empty
if (empty($message)) {
    botResponse(
        "How can I assist your Riverside FC matchday experience? You can ask about upcoming fixtures, stand prices, MoMo payment, or enter your booking reference code.",
        ['🔥 Upcoming Matches', '🎟️ How to Buy Tickets', '💳 Payment & MoMo', '🔍 Track My Booking']
    );
}

$lowerMsg = mb_strtolower($message);

// -------------------------------------------------------------
// 2. Booking Reference or Email Pattern Detection (Direct Ticket Finder)
// -------------------------------------------------------------
if (preg_match('/sample reference/i', $lowerMsg)) {
    handleTicketLookup($db, 'RFC-2026-A109B', '');
}

// Pattern for booking reference like RFC-2026-A109B or RFC-TKT-XXXXXX
$bookingRefMatch = [];
if (preg_match('/(?:RFC[-_\s]?)?(?:202\d|TKT)[-_\s]?[A-Z0-9]+/i', $message, $bookingRefMatch) || preg_match('/RFC-[A-Z0-9\-]+/i', $message, $bookingRefMatch)) {
    $refToSearch = strtoupper(trim(str_replace([' ', '_'], '-', $bookingRefMatch[0])));
    if (!str_starts_with($refToSearch, 'RFC-')) {
        $refToSearch = 'RFC-' . $refToSearch;
    }
    handleTicketLookup($db, $refToSearch, '');
}

// Pattern for email address
$emailMatch = [];
if (preg_match('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', $message, $emailMatch)) {
    $emailToSearch = strtolower($emailMatch[0]);
    handleTicketLookup($db, '', $emailToSearch);
}

// If user explicitly asks to track ticket or order
if (preg_match('/(where (is|are) my ticket|track (my )?(booking|order|ticket)|find (my )?(booking|order|ticket)|check (my )?(booking|order|ticket)|lost my ticket)/i', $lowerMsg)) {
    if ($currentUser) {
        // Look up user's tickets directly
        handleUserTickets($db, $currentUser);
    } else {
        botResponse(
            "I'd be happy to track your tickets! 🎟️\n\nPlease reply with your **Booking Reference Code** (e.g., `RFC-2026-A109B`) or your **Email Address** that you entered during checkout.",
            ['Show sample reference', '🔥 Upcoming Matches', 'Contact Box Office'],
            [],
            'lookup_prompt'
        );
    }
}

// -------------------------------------------------------------
// 3. Specific Opponent Inquiries (e.g. Hearts of Oak, Kotoko, Medeama)
// -------------------------------------------------------------
$opponents = [
    'hearts' => ['name' => 'Hearts of Oak', 'alias' => ['hearts', 'hearts of oak', 'phobia']],
    'kotoko' => ['name' => 'Asante Kotoko SC', 'alias' => ['kotoko', 'asante kotoko', 'porcupine']],
    'medeama' => ['name' => 'Medeama SC', 'alias' => ['medeama', 'tarkwa']],
    'dreams' => ['name' => 'Dreams FC', 'alias' => ['dreams', 'dreams fc', 'still believe']],
    'olympics' => ['name' => 'Great Olympics', 'alias' => ['olympics', 'great olympics', 'wonder club']]
];

foreach ($opponents as $key => $opp) {
    foreach ($opp['alias'] as $alias) {
        if (str_contains($lowerMsg, $alias)) {
            handleSpecificOpponent($db, $opp['name']);
        }
    }
}

// -------------------------------------------------------------
// 4. Upcoming Matches / Fixtures Intent
// -------------------------------------------------------------
if (preg_match('/(upcoming|fixture|match|game|schedule|calendar|next match|play|who.*playing|tickets.*available)/i', $lowerMsg)) {
    handleUpcomingFixtures($db);
}

// -------------------------------------------------------------
// 5. How to Buy Tickets / Step-by-Step Purchasing Walkthrough
// -------------------------------------------------------------
if (preg_match('/(how (can|do) i (buy|get|purchase|order)|how to buy|buying process|how to get ticket|purchase ticket|step to buy)/i', $lowerMsg)) {
    $guideText = "### 🎟️ How to Buy Match Tickets on Riverside FC:\n\n" .
        "Buying your digital ticket takes less than 60 seconds with instant QR code delivery:\n\n" .
        "1. **Choose Your Match**: Click **Fixtures & Tickets** and pick the home fixture you want to attend.\n" .
        "2. **Select Stadium Stand**: Choose your preferred section (VIP Executive, Main Grandstand, Popular East, North Terrace, or Family Enclosure).\n" .
        "3. **Choose Quantity**: Select from **1 to 10 tickets** per transaction.\n" .
        "4. **Supporter Details**: Enter your name, email, and Ghana phone number (guest checkout available).\n" .
        "5. **Pay in Ghana Cedis**: Authorize payment instantly via **MTN MoMo**, **Telecel Cash**, or **Card (Visa/Mastercard)**.\n" .
        "6. **Instant E-Ticket Pass**: Your unique QR pass is generated instantly! Save it to your phone or print it for turnstile entry at Riverside Arena.";

    $cards = [
        [
            'type' => 'action_banner',
            'title' => 'Ready to reserve your matchday seats?',
            'subtitle' => 'Browse all upcoming Ghana Premier League fixtures',
            'btn_label' => '🔥 View Upcoming Fixtures Now',
            'btn_url' => BASE_URL . '/fixtures.php'
        ]
    ];

    botResponse(
        $guideText,
        ['🔥 Upcoming Matches', '🏟️ Compare Stands', '💳 How to Pay with MoMo', '📱 QR Code Entry Guide'],
        $cards,
        'buying_guide'
    );
}

// -------------------------------------------------------------
// 6. Stand Selection & Recommendation Guide
// -------------------------------------------------------------
if (preg_match('/(stand|section|seat|where (should|can) i sit|best (view|seat|stand)|vip|family|ultras|terrace|grandstand|popular stand|enclosure)/i', $lowerMsg)) {
    handleStandAdvice($lowerMsg);
}

// -------------------------------------------------------------
// 7. Payment Methods & MoMo Assistance
// -------------------------------------------------------------
if (preg_match('/(pay|payment|momo|mtn|telecel|vodafone|cash|card|visa|mastercard|cedis|ghs|currency|refund|cost)/i', $lowerMsg)) {
    $payText = "### 💳 Payment Methods & Ghana Cedis (GH₵) Information:\n\n" .
        "All match tickets are officially priced in **Ghana Cedis (GH₵)**. We support fast, cashless payments:\n\n" .
        "• 📱 **MTN Mobile Money**: Enter your Ghana phone number at checkout. An instant USSD prompt will pop up on your phone — simply enter your MoMo PIN to authorize.\n" .
        "• 📱 **Telecel Cash**: Enter your Telecel number and confirm payment seamlessly via your mobile handset.\n" .
        "• 💳 **Debit & Credit Cards**: Visa and Mastercard are securely accepted with bank-grade encryption.\n" .
        "• 🏢 **Box Office Reservation**: Reserve online and pay at Gate B Box Office on matchday.\n\n" .
        "💡 *Tip: There are zero hidden fees. The price listed on the stand is the exact total you pay!*";

    botResponse(
        $payText,
        ['🎟️ How to Buy Tickets', '🔥 Upcoming Matches', '🏟️ Compare Stands', '📱 QR Code Entry Guide'],
        [],
        'payment_info'
    );
}

// -------------------------------------------------------------
// 8. Stadium, Gates, QR Turnstile Entry & Rules
// -------------------------------------------------------------
if (preg_match('/(gate|open|time|turnstile|qr|scan|print|phone|stadium|arena|location|address|park|prohibited|bag|arrive)/i', $lowerMsg)) {
    $stadiumText = "### 🏟️ Matchday & Stadium Guide (Riverside Arena):\n\n" .
        "• 📍 **Location**: " . STADIUM_NAME . ", High Street, Accra, Ghana (Capacity: 25,000).\n" .
        "• ⏰ **Gate Opening Times**: Turnstiles open **2 hours before kick-off**. Please arrive at least 45 minutes early to avoid turnstile queues.\n" .
        "• 📱 **Do I Need to Print My Ticket?**: **No!** You can display your digital QR ticket directly on your smartphone screen. Ensure your screen brightness is turned up at the gate.\n" .
        "• 🖨️ **Can I Print It?**: Yes, A4 paper printouts are also fully supported.\n" .
        "• 🚪 **Turnstile Gates**:\n" .
        "   - **Gate A**: VIP Executive Lounge & Club Directors\n" .
        "   - **Gate B**: Main Grandstand (West)\n" .
        "   - **Gate C**: Popular Stand (East)\n" .
        "   - **Gate D**: North Terrace (Ultras Stand)\n" .
        "   - **Gate E**: Riverside Family Enclosure (South)\n" .
        "• 🚗 **Parking**: Dedicated supporter parking available at North Arena Lot.\n" .
        "• 🚫 **Prohibited Items**: Flairs, glass bottles, weapons, and large luggage are strictly disallowed.";

    botResponse(
        $stadiumText,
        ['🔥 Upcoming Matches', '🎟️ How to Buy Tickets', '🏟️ Compare Stands', 'Contact Box Office'],
        [],
        'stadium_guide'
    );
}

// -------------------------------------------------------------
// 9. Account & Guest Registration Help
// -------------------------------------------------------------
if (preg_match('/(account|register|login|guest|sign up|sign in|password)/i', $lowerMsg)) {
    $accText = "### 👤 Account & Supporter Login:\n\n" .
        "• **Guest Checkout**: You can buy tickets as a guest without creating an account! Just enter your name, email, and phone at checkout.\n" .
        "• **Supporter Account Benefits**: Creating a free account lets you:\n" .
        "  - Access all your tickets under **My Tickets** anytime without needing order numbers.\n" .
        "  - Fast 1-click checkout for high-demand matches.\n" .
        "  - Digital pass re-download whenever needed.";

    $cards = [
        [
            'type' => 'account_actions',
            'register_url' => BASE_URL . '/register.php',
            'login_url' => BASE_URL . '/login.php',
            'my_tickets_url' => BASE_URL . '/my-tickets.php'
        ]
    ];

    botResponse(
        $accText,
        ['🔥 Upcoming Matches', '🎟️ How to Buy Tickets', '🔍 Track My Booking'],
        $cards,
        'account_info'
    );
}

// -------------------------------------------------------------
// 10. Contact Club / Human Support Escalation
// -------------------------------------------------------------
if (preg_match('/(contact|human|agent|call|phone|email|office|help desk|support|reach)/i', $lowerMsg)) {
    $contactText = "### 📞 Riverside FC Ticket Office & Customer Support:\n\n" .
        "Our supporter care team is ready to assist you:\n\n" .
        "• 📞 **Telephone**: " . CLUB_PHONE . "\n" .
        "• ✉️ **Email**: [" . CLUB_EMAIL . "](mailto:" . CLUB_EMAIL . ")\n" .
        "• 📍 **Physical Box Office**: Gate B, " . STADIUM_NAME . ", High Street, Accra\n" .
        "• 🕒 **Operating Hours**: Monday – Saturday, 8:30 AM – 5:00 PM GMT (and from 9:00 AM on Matchdays)";

    botResponse(
        $contactText,
        ['🔥 Upcoming Matches', '🎟️ How to Buy Tickets', '🔍 Track My Booking'],
        [],
        'contact_info'
    );
}

// -------------------------------------------------------------
// 11. Polite / Conversational Greetings & Thanks
// -------------------------------------------------------------
if (preg_match('/^(hi|hello|hey|good day|good morning|good afternoon|good evening|kobby|riverbot)/i', $lowerMsg)) {
    botResponse(
        "Hello supporter! ⚽ Ready for matchday with **The Riverians**? How can I help you? You can check our upcoming fixtures, compare stands, or ask how to buy tickets.",
        ['🔥 Upcoming Matches', '🎟️ How to Buy Tickets', '🏟️ Compare Stands', '💳 Payment & MoMo', '🔍 Track My Booking']
    );
}

if (preg_match('/(thank|thanks|medaase|appreciate|awesome|cool|great)/i', $lowerMsg)) {
    botResponse(
        "You're very welcome! 🙌 We can't wait to welcome you to **" . STADIUM_NAME . "** to cheer on Riverside FC! Let me know if you need any other assistance.",
        ['🔥 Upcoming Matches', '🎟️ How to Buy Tickets', '📍 Stadium & Gates']
    );
}

// -------------------------------------------------------------
// 12. Fallback Smart Matcher
// -------------------------------------------------------------
handleFallback($db, $message);


// =============================================================
// HANDLER FUNCTIONS
// =============================================================

/**
 * Handle Upcoming Fixtures list
 */
function handleUpcomingFixtures(PDO $db): void {
    $stmt = $db->query("
        SELECT f.*, 
               MIN(tt.price) as min_price, 
               MAX(tt.price) as max_price,
               SUM(tt.remaining) as total_remaining
        FROM fixtures f
        LEFT JOIN ticket_types tt ON f.id = tt.fixture_id
        WHERE f.status = 'upcoming'
        GROUP BY f.id
        ORDER BY f.match_date ASC
        LIMIT 4
    ");
    $fixtures = $stmt->fetchAll();

    if (empty($fixtures)) {
        botResponse(
            "There are currently no active upcoming matches scheduled at " . STADIUM_NAME . ". Please check back soon as our new fixture dates are confirmed!",
            ['Contact Box Office', '🔍 Track My Booking']
        );
    }

    $text = "### ⚽ Upcoming Home Fixtures at " . STADIUM_NAME . ":\n\n" .
        "Here are the active matches currently open for ticket booking. Click **Select Stand & Buy** on any match to reserve your seats:";

    $cards = [];
    $quick = [];

    foreach ($fixtures as $fix) {
        $minP = (float)($fix['min_price'] ?? 30);
        $maxP = (float)($fix['max_price'] ?? 150);
        $oppLogo = !empty($fix['opponent_logo']) ? $fix['opponent_logo'] : 'hearts_of_oak.svg';
        $rem = (int)($fix['total_remaining'] ?? 0);

        $cards[] = [
            'type' => 'fixture',
            'id' => (int)$fix['id'],
            'opponent' => $fix['opponent'],
            'competition' => $fix['competition'],
            'match_date' => formatDate($fix['match_date'], 'D, d M Y'),
            'kick_off' => formatKickOff($fix['kick_off']),
            'venue' => $fix['venue'],
            'logo' => BASE_URL . '/assets/images/' . $oppLogo,
            'price_range' => 'GH₵ ' . number_format($minP, 0) . ' - GH₵ ' . number_format($maxP, 0),
            'remaining' => $rem,
            'buy_url' => BASE_URL . '/fixture.php?id=' . $fix['id']
        ];

        $quick[] = 'vs ' . $fix['opponent'];
    }

    $quick[] = '🏟️ Compare Stands';
    $quick[] = '💳 Payment & MoMo';

    botResponse($text, array_slice($quick, 0, 4), $cards, 'fixtures_list');
}

/**
 * Handle Specific Opponent Details & Stands
 */
function handleSpecificOpponent(PDO $db, string $opponentName): void {
    $stmt = $db->prepare("
        SELECT * FROM fixtures 
        WHERE opponent LIKE ? AND status = 'upcoming'
        ORDER BY match_date ASC 
        LIMIT 1
    ");
    $stmt->execute(['%' . $opponentName . '%']);
    $fixture = $stmt->fetch();

    if (!$fixture) {
        botResponse(
            "We don't currently have an upcoming home fixture scheduled against **" . h($opponentName) . "**. Would you like to view our other upcoming matches?",
            ['🔥 Upcoming Matches', '🎟️ How to Buy Tickets']
        );
    }

    // Fetch stands for this fixture
    $typeStmt = $db->prepare("SELECT * FROM ticket_types WHERE fixture_id = ? ORDER BY price DESC");
    $typeStmt->execute([$fixture['id']]);
    $sections = $typeStmt->fetchAll();

    $oppLogo = !empty($fixture['opponent_logo']) ? $fixture['opponent_logo'] : 'hearts_of_oak.svg';

    $text = "### ⚽ Riverside FC vs " . h($fixture['opponent']) . "\n" .
        "• 🏆 **Competition**: " . h($fixture['competition']) . "\n" .
        "• 📅 **Date & Time**: " . formatDate($fixture['match_date'], 'l, d F Y') . " at " . formatKickOff($fixture['kick_off']) . "\n" .
        "• 📍 **Venue**: " . h($fixture['venue']) . "\n\n" .
        "**Available Stands & Ticket Categories:**";

    $cards = [
        [
            'type' => 'fixture_stands',
            'fixture_id' => (int)$fixture['id'],
            'opponent' => $fixture['opponent'],
            'logo' => BASE_URL . '/assets/images/' . $oppLogo,
            'match_date' => formatDate($fixture['match_date'], 'D, d M Y'),
            'kick_off' => formatKickOff($fixture['kick_off']),
            'sections' => array_map(function($sec) use ($fixture) {
                return [
                    'id' => (int)$sec['id'],
                    'name' => $sec['name'],
                    'gate' => $sec['gate'],
                    'price' => (float)$sec['price'],
                    'price_formatted' => formatMoney($sec['price']),
                    'remaining' => (int)$sec['remaining'],
                    'capacity' => (int)$sec['capacity'],
                    'direct_buy_url' => BASE_URL . '/checkout.php?fixture_id=' . $fixture['id'] . '&ticket_type_id=' . $sec['id'] . '&quantity=1'
                ];
            }, $sections),
            'view_all_url' => BASE_URL . '/fixture.php?id=' . $fixture['id']
        ]
    ];

    botResponse(
        $text,
        ['🏟️ Which stand is best?', '💳 How to Pay with MoMo', '🔥 Other Fixtures'],
        $cards,
        'fixture_detail'
    );
}

/**
 * Handle Stand Recommendations
 */
function handleStandAdvice(string $msg): void {
    if (str_contains($msg, 'vip')) {
        $text = "### 🌟 VIP Executive Lounge (Gate A) — GH₵ 150:\n\n" .
            "• **Atmosphere**: Luxury matchday hospitality & networking.\n" .
            "• **Perks**: Access to air-conditioned lounge, complimentary matchday drinks and gourmet snacks, padded wide leather seats, private restrooms, and dedicated Gate A entry.\n" .
            "• **Recommended for**: Business hospitality, special celebrations, or fans looking for the ultimate luxury football experience.";
    } elseif (str_contains($msg, 'family') || str_contains($msg, 'kid') || str_contains($msg, 'child')) {
        $text = "### 👨‍👩‍👧 Riverside Family Enclosure (Gate E) — GH₵ 50:\n\n" .
            "• **Atmosphere**: Safe, welcoming, and enjoyable for supporters of all ages.\n" .
            "• **Perks**: Dedicated family stewards, alcohol-free seating area, close to children's food kiosks, and expedited Gate E turnstile access.\n" .
            "• **Recommended for**: Parents bringing children, youth groups, and supporters seeking a friendly, comfortable atmosphere.";
    } elseif (str_contains($msg, 'ultra') || str_contains($msg, 'chant') || str_contains($msg, 'atmosphere') || str_contains($msg, 'terrace')) {
        $text = "### 🥁 North Terrace Ultras Stand (Gate D) — GH₵ 30:\n\n" .
            "• **Atmosphere**: High-octane, nonstop 90-minute singing and drumming!\n" .
            "• **Perks**: Unreserved standing terrace behind the north goal. Home of the official Riverians Ultras fan clubs with massive flags and banners.\n" .
            "• **Recommended for**: Passionate die-hard fans who want to sing, shout, and create an electric stadium wall of sound.";
    } elseif (str_contains($msg, 'grandstand') || str_contains($msg, 'view') || str_contains($msg, 'best view')) {
        $text = "### 🏟️ Main Grandstand (Gate B) — GH₵ 80:\n\n" .
            "• **Atmosphere**: Covered, reserved seating with premium sightlines.\n" .
            "• **Perks**: Elevated view directly over the halfway line and player benches. Protected from sun and rain by the west roof canopy.\n" .
            "• **Recommended for**: Fans wanting the best tactical overview of the game and guaranteed reserved seating comfort.";
    } else {
        $text = "### 🏟️ Riverside Arena Stand Guide:\n\n" .
            "Choose the stand that best matches your matchday vibe:\n\n" .
            "1. 🌟 **VIP Executive Lounge (Gate A) • GH₵ 150**: Air-conditioned luxury lounge, complimentary refreshments, cushioned seats.\n" .
            "2. 🏟️ **Main Grandstand (Gate B) • GH₵ 80**: Covered reserved seating with the best tactical view over the halfway line.\n" .
            "3. 👨‍👩‍👧 **Riverside Family Enclosure (Gate E) • GH₵ 50**: Dedicated family zone, alcohol-free, perfect with children.\n" .
            "4. 🥁 **North Terrace Ultras (Gate D) • GH₵ 30**: Behind the goal; flags, drums, and 90 minutes of singing.\n" .
            "5. ⚡ **Popular Stand East (Gate C) • GH₵ 40**: Electric stadium atmosphere and unbeatable value!";
    }

    botResponse(
        $text,
        ['🔥 Upcoming Matches', '🎟️ How to Buy Tickets', '💳 Payment & MoMo'],
        [],
        'stand_advice'
    );
}

/**
 * Handle Ticket Lookup by Ref or Email
 */
function handleTicketLookup(PDO $db, string $reference, string $email): void {
    $sql = "
        SELECT t.*, 
               tt.name as section_name, tt.gate, tt.price,
               b.booking_reference, b.customer_name, b.customer_email, b.payment_status, b.total_amount,
               f.opponent, f.opponent_logo, f.competition, f.match_date, f.kick_off, f.venue
        FROM bookings b
        JOIN tickets t ON t.booking_id = b.id
        JOIN ticket_types tt ON t.ticket_type_id = tt.id
        JOIN fixtures f ON b.fixture_id = f.id
        WHERE 1=1
    ";
    $params = [];

    if (!empty($reference)) {
        $sql .= " AND (UPPER(b.booking_reference) = UPPER(?) OR UPPER(t.ticket_code) = UPPER(?))";
        $params[] = $reference;
        $params[] = $reference;
    } elseif (!empty($email)) {
        $sql .= " AND LOWER(b.customer_email) = LOWER(?)";
        $params[] = $email;
    }

    $sql .= " ORDER BY f.match_date ASC, t.id ASC LIMIT 10";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $tickets = $stmt->fetchAll();

    if (empty($tickets)) {
        botResponse(
            "I searched our records but couldn't find any bookings matching **" . h($reference ?: $email) . "**.\n\n" .
            "• Please ensure you typed your 14-character Booking Reference correctly (e.g. `RFC-2026-A109B`).\n" .
            "• Or reply with the exact email address used at checkout.\n" .
            "• If you need immediate assistance, contact our box office at " . CLUB_PHONE . ".",
            ['Try another email', '🔥 Upcoming Matches', 'Contact Box Office']
        );
    }

    $first = $tickets[0];
    $ticketCount = count($tickets);
    $oppLogo = !empty($first['opponent_logo']) ? $first['opponent_logo'] : 'hearts_of_oak.svg';

    $text = "### 🎟️ Booking Found: " . h($first['booking_reference']) . "\n\n" .
        "• **Fan Name**: " . h($first['customer_name']) . "\n" .
        "• **Match**: Riverside FC vs " . h($first['opponent']) . "\n" .
        "• **Date & Kick-Off**: " . formatDate($first['match_date'], 'D, d M Y') . " at " . formatKickOff($first['kick_off']) . "\n" .
        "• **Stand & Gate**: " . h($first['section_name']) . " (" . h($first['gate']) . ")\n" .
        "• **Total Tickets**: **" . $ticketCount . " ticket(s)**\n" .
        "• **Payment Status**: **" . strtoupper($first['payment_status']) . "** (" . formatMoney($first['total_amount']) . ")\n\n" .
        "You can view or print your digital QR turnstile passes right now:";

    $cards = [
        [
            'type' => 'booking_result',
            'booking_ref' => $first['booking_reference'],
            'customer_name' => $first['customer_name'],
            'opponent' => $first['opponent'],
            'logo' => BASE_URL . '/assets/images/' . $oppLogo,
            'match_date' => formatDate($first['match_date'], 'D, d M Y'),
            'kick_off' => formatKickOff($first['kick_off']),
            'section_name' => $first['section_name'],
            'gate' => $first['gate'],
            'ticket_count' => $ticketCount,
            'status' => $first['payment_status'],
            'view_tickets_url' => BASE_URL . '/my-tickets.php?ref=' . urlencode($first['booking_reference']),
            'ticket_code' => $first['ticket_code'],
            'ticket_pass_url' => BASE_URL . '/ticket.php?code=' . urlencode($first['ticket_code'])
        ]
    ];

    botResponse(
        $text,
        ['📱 How QR Turnstiles Work', '📍 Stadium & Gates', '🔥 Upcoming Matches'],
        $cards,
        'ticket_found'
    );
}

/**
 * Handle Current Logged-in User Tickets
 */
function handleUserTickets(PDO $db, array $user): void {
    $stmt = $db->prepare("
        SELECT b.booking_reference, b.total_amount, b.payment_status,
               f.opponent, f.opponent_logo, f.match_date, f.kick_off,
               COUNT(t.id) as ticket_count,
               MIN(t.ticket_code) as sample_code
        FROM bookings b
        JOIN fixtures f ON b.fixture_id = f.id
        LEFT JOIN tickets t ON t.booking_id = b.id
        WHERE b.user_id = ? OR b.customer_email = ?
        GROUP BY b.id
        ORDER BY f.match_date ASC
        LIMIT 5
    ");
    $stmt->execute([$user['id'], $user['email']]);
    $bookings = $stmt->fetchAll();

    if (empty($bookings)) {
        botResponse(
            "Hello " . h($user['name']) . ", you don't have any match tickets on your account yet.\n\nWould you like to browse our upcoming home fixtures?",
            ['🔥 Upcoming Matches', '🎟️ How to Buy Tickets']
        );
    }

    $text = "### 🎟️ Your Matchday Bookings (" . h($user['name']) . "):\n\n" .
        "Here are your active tickets on file with Riverside FC:";

    $cards = [];
    foreach ($bookings as $b) {
        $oppLogo = !empty($b['opponent_logo']) ? $b['opponent_logo'] : 'hearts_of_oak.svg';
        $cards[] = [
            'type' => 'user_booking_summary',
            'booking_ref' => $b['booking_reference'],
            'opponent' => $b['opponent'],
            'logo' => BASE_URL . '/assets/images/' . $oppLogo,
            'match_date' => formatDate($b['match_date'], 'D, d M Y'),
            'kick_off' => formatKickOff($b['kick_off']),
            'ticket_count' => (int)$b['ticket_count'],
            'status' => $b['payment_status'],
            'view_url' => BASE_URL . '/my-tickets.php?ref=' . urlencode($b['booking_reference'])
        ];
    }

    botResponse(
        $text,
        ['📱 How QR Turnstiles Work', '🔥 Upcoming Matches', '📍 Stadium & Gates'],
        $cards,
        'user_tickets'
    );
}

/**
 * Handle General Fallback
 */
function handleFallback(PDO $db, string $msg): void {
    // Check if there is an upcoming fixture
    $stmt = $db->query("SELECT opponent FROM fixtures WHERE status = 'upcoming' ORDER BY match_date ASC LIMIT 1");
    $nextOpp = $stmt->fetchColumn() ?: 'our next match';

    $text = "I'm here to ensure you have a seamless ticket buying experience for Riverside FC!\n\n" .
        "You can ask me to:\n" .
        "• Show matches: *\"What are the upcoming games?\"* or *\"Show {$nextOpp} match\"*\n" .
        "• Help you buy: *\"How do I buy tickets?\"* or *\"Can I pay with MTN MoMo?\"*\n" .
        "• Recommend seats: *\"Where should I sit with my family?\"* or *\"Tell me about VIP\"*\n" .
        "• Look up your tickets: *\"Track order RFC-2026-A109B\"* or provide your email";

    botResponse(
        $text,
        ['🔥 Upcoming Matches', '🎟️ How to Buy Tickets', '🏟️ Compare Stands', '💳 Payment & MoMo', '🔍 Track My Booking'],
        [],
        'fallback'
    );
}
