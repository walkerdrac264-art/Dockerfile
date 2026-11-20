<?php
/**
 * Riverside FC - RiverBot Chatbot Widget Component
 * Intelligent Matchday & Ticketing Assistant
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/auth.php';

$botUser = currentUser();
$botUserName = $botUser ? htmlspecialchars($botUser['name'], ENT_QUOTES, 'UTF-8') : '';
$currentPage = basename($_SERVER['PHP_SELF'] ?? 'index.php');
?>
<!-- Riverside FC RiverBot Widget -->
<div id="rfc-chatbot-widget" data-base-url="<?= BASE_URL ?>" data-current-page="<?= htmlspecialchars($currentPage, ENT_QUOTES, 'UTF-8') ?>" data-user-name="<?= $botUserName ?>">

    <!-- Proactive Teaser Speech Bubble -->
    <div id="rfc-chat-teaser" class="no-print" role="dialog" aria-label="RiverBot matchday greeting">
        <button type="button" class="rfc-teaser-close" id="rfc-teaser-close-btn" aria-label="Dismiss greeting">&times;</button>
        <div class="d-flex align-items-center gap-2">
            <img src="<?= BASE_URL ?>/assets/images/bot_avatar.svg" alt="RiverBot" width="30" height="30" class="rounded-circle">
            <div style="font-size: 0.82rem; line-height: 1.35;">
                <span class="fw-bold text-primary">Need ticket help? ⚽</span><br>
                Chat with <strong>RiverBot</strong> for fixtures, stands, or MoMo!
            </div>
        </div>
    </div>

    <!-- Floating Chat Launcher Button -->
    <button type="button" id="rfc-chat-launcher" class="no-print" aria-label="Open RiverBot matchday assistant" title="Chat with RiverBot">
        <img src="<?= BASE_URL ?>/assets/images/bot_avatar.svg" alt="RiverBot" class="rfc-launcher-avatar">
        <i class="fas fa-times rfc-launcher-close-icon"></i>
        <span id="rfc-chat-badge" aria-label="Unread messages count">1</span>
    </button>

    <!-- Floating Chat Window -->
    <div id="rfc-chat-window" class="no-print" role="region" aria-label="RiverBot conversation window">
        
        <!-- Header -->
        <div class="rfc-chat-header">
            <div class="rfc-chat-header-profile">
                <div class="rfc-chat-avatar-wrap">
                    <img src="<?= BASE_URL ?>/assets/images/bot_avatar.svg" alt="RiverBot Crest" class="rfc-chat-header-avatar">
                    <span class="rfc-chat-online-dot" title="RiverBot is online"></span>
                </div>
                <div>
                    <h3 class="rfc-chat-header-title">
                        RiverBot <i class="fas fa-check-circle text-info" style="font-size: 0.75rem;" title="Verified Riverside FC Assistant"></i>
                    </h3>
                    <p class="rfc-chat-header-subtitle">
                        <i class="fas fa-circle text-success" style="font-size: 0.5rem;"></i> Matchday Concierge • GH₵
                    </p>
                </div>
            </div>
            
            <div class="rfc-chat-header-actions">
                <button type="button" class="rfc-chat-btn-icon" id="rfc-btn-sound" title="Toggle audio sound (chime)" aria-label="Toggle sound">
                    <i class="fas fa-volume-up"></i>
                </button>
                <button type="button" class="rfc-chat-btn-icon" id="rfc-btn-reset" title="Restart conversation" aria-label="Restart chat">
                    <i class="fas fa-rotate-right"></i>
                </button>
                <button type="button" class="rfc-chat-btn-icon" id="rfc-btn-close" title="Close chat window" aria-label="Close chat">
                    <i class="fas fa-minus"></i>
                </button>
            </div>
        </div>

        <!-- Quick Topics Pill Bar -->
        <div class="rfc-chat-quick-bar" id="rfc-chat-quick-bar">
            <button type="button" class="rfc-quick-pill" data-query="Upcoming Matches">
                <span>🔥</span> Fixtures
            </button>
            <button type="button" class="rfc-quick-pill" data-query="Compare Stands">
                <span>🏟️</span> Stands &amp; Gates
            </button>
            <button type="button" class="rfc-quick-pill" data-query="How to Pay with MoMo">
                <span>💳</span> MTN MoMo &amp; Cards
            </button>
            <button type="button" class="rfc-quick-pill" data-query="Track My Booking">
                <span>🔍</span> Track My Order
            </button>
            <button type="button" class="rfc-quick-pill" data-query="Stadium & Gates Guide">
                <span>📍</span> Arena Guide
            </button>
            <button type="button" class="rfc-quick-pill" data-query="Contact Box Office">
                <span>📞</span> Box Office
            </button>
        </div>

        <!-- Messages Feed Container -->
        <div class="rfc-chat-messages" id="rfc-chat-messages">
            <!-- Messages and cards will be rendered dynamically -->
        </div>

        <!-- Typing Indicator (Animated dots) -->
        <div class="px-3 pb-2" id="rfc-typing-indicator" style="display: none;">
            <div class="rfc-typing-indicator">
                <div class="rfc-typing-dot"></div>
                <div class="rfc-typing-dot"></div>
                <div class="rfc-typing-dot"></div>
                <span class="rfc-typing-text">RiverBot is thinking...</span>
            </div>
        </div>

        <!-- Input Bar -->
        <div class="rfc-chat-input-bar">
            <div class="rfc-input-wrapper">
                <input 
                    type="text" 
                    id="rfc-chat-input" 
                    class="rfc-chat-input" 
                    placeholder="Ask about fixtures, stands, MoMo, or enter booking ref..." 
                    autocomplete="off"
                    aria-label="Ask RiverBot a question"
                >
                <button type="button" id="rfc-btn-voice" class="rfc-btn-voice" title="Speak to RiverBot (Voice Input)" aria-label="Voice input">
                    <i class="fas fa-microphone"></i>
                </button>
                <button type="button" id="rfc-btn-send" class="rfc-btn-send" title="Send message" aria-label="Send message">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </div>
            <div class="rfc-chat-footer-note">
                <span>Official Ticketing System • Denominated in Ghana Cedis (GH₵)</span>
            </div>
        </div>

    </div>

</div>
