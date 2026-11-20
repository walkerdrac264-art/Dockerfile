/**
 * Riverside FC - RiverBot Client Engine
 * Matchday & Ticketing Intelligent Interactive Concierge
 */

(function () {
  'use strict';

  // State
  let isOpen = false;
  let soundEnabled = localStorage.getItem('rfc_bot_sound') !== 'false';
  let history = [];
  let unreadCount = 0;
  let isSending = false;
  let speechRecognizer = null;
  let isListening = false;

  // DOM Elements
  let widget, launcher, windowEl, messagesEl, inputEl, sendBtn, voiceBtn, soundBtn, resetBtn, closeBtn, badgeEl, teaserEl, teaserCloseBtn, quickBarEl, typingEl;
  let baseUrl = '';
  let currentPage = '';
  let userName = '';

  document.addEventListener('DOMContentLoaded', () => {
    initWidget();
  });

  function initWidget() {
    widget = document.getElementById('rfc-chatbot-widget');
    if (!widget) return;

    baseUrl = widget.dataset.baseUrl || '';
    currentPage = widget.dataset.currentPage || 'index.php';
    userName = widget.dataset.userName || '';

    launcher = document.getElementById('rfc-chat-launcher');
    windowEl = document.getElementById('rfc-chat-window');
    messagesEl = document.getElementById('rfc-chat-messages');
    inputEl = document.getElementById('rfc-chat-input');
    sendBtn = document.getElementById('rfc-btn-send');
    voiceBtn = document.getElementById('rfc-btn-voice');
    soundBtn = document.getElementById('rfc-btn-sound');
    resetBtn = document.getElementById('rfc-btn-reset');
    closeBtn = document.getElementById('rfc-btn-close');
    badgeEl = document.getElementById('rfc-chat-badge');
    teaserEl = document.getElementById('rfc-chat-teaser');
    teaserCloseBtn = document.getElementById('rfc-teaser-close-btn');
    quickBarEl = document.getElementById('rfc-chat-quick-bar');
    typingEl = document.getElementById('rfc-typing-indicator');

    // Restore Sound state
    updateSoundIcon();

    // Event Listeners
    if (launcher) launcher.addEventListener('click', toggleChat);
    if (closeBtn) closeBtn.addEventListener('click', closeChat);
    if (soundBtn) soundBtn.addEventListener('click', toggleSound);
    if (resetBtn) resetBtn.addEventListener('click', confirmResetChat);

    if (teaserCloseBtn) {
      teaserCloseBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        hideTeaser();
        sessionStorage.setItem('rfc_teaser_dismissed', 'true');
      });
    }

    if (teaserEl) {
      teaserEl.addEventListener('click', () => {
        hideTeaser();
        openChat();
      });
    }

    if (sendBtn) {
      sendBtn.addEventListener('click', () => {
        handleUserSend();
      });
    }

    if (inputEl) {
      inputEl.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' && !e.shiftKey) {
          e.preventDefault();
          handleUserSend();
        }
      });
    }

    // Quick bar buttons
    if (quickBarEl) {
      quickBarEl.querySelectorAll('.rfc-quick-pill').forEach(pill => {
        pill.addEventListener('click', () => {
          const query = pill.dataset.query;
          if (query) {
            if (!isOpen) openChat();
            sendQuery(query);
          }
        });
      });
    }

    // Voice recognition setup
    setupVoiceRecognition();

    // Restore chat history from sessionStorage
    loadHistory();

    // Proactive greeting bubble after delay if not dismissed
    const teaserDismissed = sessionStorage.getItem('rfc_teaser_dismissed') === 'true';
    if (!teaserDismissed && !isOpen && history.length <= 1) {
      setTimeout(() => {
        if (!isOpen && teaserEl) {
          teaserEl.style.display = 'block';
        }
      }, 3500);
    }

    // Keyboard escape to close
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && isOpen) {
        closeChat();
      }
    });
  }

  // Sound Chime synthesis via Web Audio API (Zero external MP3 dependencies)
  function playMelodicChime() {
    if (!soundEnabled) return;
    try {
      const AudioCtx = window.AudioContext || window.webkitAudioContext;
      if (!AudioCtx) return;
      const ctx = new AudioCtx();
      const now = ctx.currentTime;

      // Tone 1 (D5 - 587Hz)
      const osc1 = ctx.createOscillator();
      const gain1 = ctx.createGain();
      osc1.type = 'sine';
      osc1.frequency.setValueAtTime(587.33, now);
      gain1.gain.setValueAtTime(0.06, now);
      gain1.gain.exponentialRampToValueAtTime(0.001, now + 0.15);
      osc1.connect(gain1);
      gain1.connect(ctx.destination);
      osc1.start(now);
      osc1.stop(now + 0.15);

      // Tone 2 (A5 - 880Hz)
      const osc2 = ctx.createOscillator();
      const gain2 = ctx.createGain();
      osc2.type = 'sine';
      osc2.frequency.setValueAtTime(880, now + 0.08);
      gain2.gain.setValueAtTime(0.08, now + 0.08);
      gain2.gain.exponentialRampToValueAtTime(0.0001, now + 0.35);
      osc2.connect(gain2);
      gain2.connect(ctx.destination);
      osc2.start(now + 0.08);
      osc2.stop(now + 0.35);
    } catch (err) {
      // Audio autoplay policy handled silently
    }
  }

  function toggleSound() {
    soundEnabled = !soundEnabled;
    localStorage.setItem('rfc_bot_sound', soundEnabled ? 'true' : 'false');
    updateSoundIcon();
    if (soundEnabled) {
      playMelodicChime();
    }
  }

  function updateSoundIcon() {
    if (!soundBtn) return;
    const icon = soundBtn.querySelector('i');
    if (icon) {
      if (soundEnabled) {
        icon.className = 'fas fa-volume-up';
        soundBtn.title = 'Mute audio sounds';
      } else {
        icon.className = 'fas fa-volume-mute text-danger';
        soundBtn.title = 'Unmute audio sounds';
      }
    }
  }

  // Voice Recognition (Speech-to-Text)
  function setupVoiceRecognition() {
    const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
    if (!SpeechRecognition) {
      if (voiceBtn) voiceBtn.style.display = 'none';
      return;
    }

    speechRecognizer = new SpeechRecognition();
    speechRecognizer.continuous = false;
    speechRecognizer.interimResults = false;
    speechRecognizer.lang = 'en-US';

    speechRecognizer.onstart = () => {
      isListening = true;
      if (voiceBtn) {
        voiceBtn.classList.add('is-listening');
        voiceBtn.title = 'Listening... Speak your question';
      }
      if (inputEl) {
        inputEl.placeholder = 'Listening... Speak now 🎙️';
      }
    };

    speechRecognizer.onresult = (event) => {
      const transcript = event.results[0][0].transcript;
      if (inputEl && transcript) {
        inputEl.value = transcript;
        handleUserSend();
      }
    };

    speechRecognizer.onerror = (event) => {
      stopVoice();
    };

    speechRecognizer.onend = () => {
      stopVoice();
    };

    if (voiceBtn) {
      voiceBtn.addEventListener('click', () => {
        if (isListening) {
          speechRecognizer.stop();
        } else {
          try {
            speechRecognizer.start();
          } catch (e) {
            stopVoice();
          }
        }
      });
    }
  }

  function stopVoice() {
    isListening = false;
    if (voiceBtn) {
      voiceBtn.classList.remove('is-listening');
      voiceBtn.title = 'Speak to RiverBot (Voice Input)';
    }
    if (inputEl) {
      inputEl.placeholder = 'Ask about fixtures, stands, MoMo, or enter booking ref...';
    }
  }

  // Open / Close / Toggle
  function toggleChat() {
    if (isOpen) {
      closeChat();
    } else {
      openChat();
    }
  }

  function openChat() {
    isOpen = true;
    hideTeaser();
    windowEl.classList.add('is-open');
    launcher.classList.add('is-active');
    unreadCount = 0;
    updateBadge();

    // Scroll to bottom
    scrollToBottom();

    // Focus input on desktop
    if (window.innerWidth > 576 && inputEl) {
      setTimeout(() => inputEl.focus(), 300);
    }

    // If history is empty, fetch initial handshake greeting
    if (history.length === 0) {
      fetchBotResponse('', 'init');
    }
  }

  function closeChat() {
    isOpen = false;
    windowEl.classList.remove('is-open');
    launcher.classList.remove('is-active');
    if (isListening && speechRecognizer) {
      speechRecognizer.stop();
    }
  }

  function hideTeaser() {
    if (teaserEl) {
      teaserEl.style.display = 'none';
    }
  }

  function updateBadge() {
    if (!badgeEl) return;
    if (unreadCount > 0 && !isOpen) {
      badgeEl.textContent = unreadCount > 9 ? '9+' : unreadCount;
      badgeEl.style.display = 'block';
    } else {
      badgeEl.style.display = 'none';
    }
  }

  // History Persistence
  function loadHistory() {
    try {
      const stored = sessionStorage.getItem('rfc_riverbot_history');
      if (stored) {
        history = JSON.parse(stored);
        if (Array.isArray(history) && history.length > 0) {
          renderAllMessages();
          return;
        }
      }
    } catch (e) {
      history = [];
    }
  }

  function saveHistory() {
    try {
      // Keep up to 50 recent messages to prevent storage overflow
      const trimmed = history.slice(-50);
      sessionStorage.setItem('rfc_riverbot_history', JSON.stringify(trimmed));
    } catch (e) {}
  }

  function confirmResetChat() {
    if (confirm('Restart conversation with RiverBot?')) {
      history = [];
      sessionStorage.removeItem('rfc_riverbot_history');
      messagesEl.innerHTML = '';
      fetchBotResponse('', 'init');
    }
  }

  // Sending Messages
  function handleUserSend() {
    if (isSending || !inputEl) return;
    const text = inputEl.value.trim();
    if (!text) return;

    inputEl.value = '';
    sendQuery(text);
  }

  function sendQuery(text) {
    if (isSending) return;

    // Append user message
    const userMsg = {
      id: 'msg_' + Date.now(),
      role: 'user',
      text: text,
      time: getCurrentTime()
    };
    appendMessage(userMsg);
    history.push(userMsg);
    saveHistory();

    // Call API
    fetchBotResponse(text);
  }

  function fetchBotResponse(message, action = '') {
    isSending = true;
    showTyping(true);

    const payload = {
      message: message,
      action: action,
      context: {
        page: currentPage,
        url: window.location.href,
        user: userName
      }
    };

    fetch(baseUrl + '/api/chatbot.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify(payload)
    })
      .then(res => {
        if (!res.ok) throw new Error('Network error: ' + res.status);
        return res.json();
      })
      .then(data => {
        showTyping(false);
        isSending = false;

        const replyText = data.reply || "I'm sorry, I couldn't process your request. Please try again.";
        const quickReplies = Array.isArray(data.quick_replies) ? data.quick_replies : [];
        const cards = Array.isArray(data.cards) ? data.cards : [];

        const botMsg = {
          id: 'msg_' + Date.now(),
          role: 'bot',
          text: replyText,
          quick_replies: quickReplies,
          cards: cards,
          time: getCurrentTime()
        };

        appendMessage(botMsg);
        history.push(botMsg);
        saveHistory();

        if (!isOpen) {
          unreadCount++;
          updateBadge();
        }

        playMelodicChime();
      })
      .catch(err => {
        showTyping(false);
        isSending = false;

        const errorMsg = {
          id: 'msg_' + Date.now(),
          role: 'bot',
          text: "I encountered a brief connection issue. ⚠️\n\nPlease check your internet connection or reach our Matchday Ticket Office at **+233 24 555 0192**.",
          quick_replies: ['Try Again', 'Upcoming Matches', 'Contact Box Office'],
          cards: [],
          time: getCurrentTime()
        };
        appendMessage(errorMsg);
        history.push(errorMsg);
        saveHistory();
      });
  }

  function showTyping(show) {
    if (!typingEl) return;
    typingEl.style.display = show ? 'block' : 'none';
    if (show) scrollToBottom();
  }

  function getCurrentTime() {
    const d = new Date();
    return d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
  }

  function scrollToBottom() {
    if (messagesEl) {
      setTimeout(() => {
        messagesEl.scrollTop = messagesEl.scrollHeight;
      }, 50);
    }
  }

  // Rendering
  function renderAllMessages() {
    if (!messagesEl) return;
    messagesEl.innerHTML = '';
    history.forEach(msg => {
      appendMessage(msg, false);
    });
    scrollToBottom();
  }

  function appendMessage(msg, shouldScroll = true) {
    if (!messagesEl) return;

    const row = document.createElement('div');
    row.className = `rfc-msg-row ${msg.role === 'user' ? 'rfc-msg-user' : 'rfc-msg-bot'}`;
    row.id = msg.id;

    if (msg.role === 'bot') {
      const avatar = document.createElement('img');
      avatar.src = `${baseUrl}/assets/images/bot_avatar.svg`;
      avatar.alt = 'RiverBot';
      avatar.className = 'rfc-msg-avatar';
      row.appendChild(avatar);
    }

    const bodyWrap = document.createElement('div');
    bodyWrap.className = 'rfc-msg-body-wrap';

    // Bubble
    const bubble = document.createElement('div');
    bubble.className = 'rfc-msg-bubble';

    if (msg.role === 'user') {
      bubble.textContent = msg.text;
    } else {
      bubble.innerHTML = formatMarkdown(msg.text);

      // Render Rich Cards if any
      if (Array.isArray(msg.cards) && msg.cards.length > 0) {
        const cardsContainer = renderCards(msg.cards);
        bubble.appendChild(cardsContainer);
      }

      // Render Quick Replies
      if (Array.isArray(msg.quick_replies) && msg.quick_replies.length > 0) {
        const quickContainer = renderQuickReplies(msg.quick_replies);
        bubble.appendChild(quickContainer);
      }
    }

    bodyWrap.appendChild(bubble);

    // Timestamp
    const timeEl = document.createElement('div');
    timeEl.className = 'rfc-msg-time';
    timeEl.textContent = msg.time || '';
    bodyWrap.appendChild(timeEl);

    row.appendChild(bodyWrap);
    messagesEl.appendChild(row);

    if (shouldScroll) {
      scrollToBottom();
    }
  }

  // Markdown Parser
  function formatMarkdown(text) {
    if (!text) return '';

    // Sanitize basic tags
    let html = escapeHtml(text);

    // Headers
    html = html.replace(/^### (.*$)/gim, '<h3>$1</h3>');
    html = html.replace(/^## (.*$)/gim, '<h4>$1</h4>');

    // Bold & Italic
    html = html.replace(/\*\*(.*?)\*\*/gim, '<strong>$1</strong>');
    html = html.replace(/\*(.*?)\*/gim, '<em>$1</em>');

    // Markdown Links [Text](URL)
    html = html.replace(/\[(.*?)\]\((.*?)\)/gim, '<a href="$2" target="_blank" rel="noopener">$1</a>');

    // Bullet lists
    const lines = html.split('\n');
    let inList = false;
    let listType = 'ul';
    const processedLines = [];

    for (let i = 0; i < lines.length; i++) {
      const line = lines[i].trim();
      const isBullet = line.startsWith('• ') || line.startsWith('- ');
      const isNumber = /^\d+\.\s/.test(line);

      if (isBullet || isNumber) {
        const currentType = isNumber ? 'ol' : 'ul';
        if (!inList) {
          processedLines.push(`<${currentType}>`);
          inList = true;
          listType = currentType;
        } else if (listType !== currentType) {
          processedLines.push(`</${listType}><${currentType}>`);
          listType = currentType;
        }

        const content = isBullet ? line.replace(/^[•\-]\s*/, '') : line.replace(/^\d+\.\s*/, '');
        processedLines.push(`<li>${content}</li>`);
      } else {
        if (inList) {
          processedLines.push(`</${listType}>`);
          inList = false;
        }
        if (line) {
          if (!line.startsWith('<h3') && !line.startsWith('<h4')) {
            processedLines.push(`<p>${line}</p>`);
          } else {
            processedLines.push(line);
          }
        }
      }
    }

    if (inList) {
      processedLines.push(`</${listType}>`);
    }

    return processedLines.join('');
  }

  function escapeHtml(str) {
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
  }

  // Render Cards
  function renderCards(cards) {
    const container = document.createElement('div');
    container.className = 'rfc-cards-container';

    cards.forEach(card => {
      if (card.type === 'fixture') {
        container.appendChild(createFixtureCard(card));
      } else if (card.type === 'fixture_stands') {
        container.appendChild(createFixtureStandsCard(card));
      } else if (card.type === 'booking_result') {
        container.appendChild(createBookingResultCard(card));
      } else if (card.type === 'user_booking_summary') {
        container.appendChild(createUserBookingSummaryCard(card));
      } else if (card.type === 'action_banner') {
        container.appendChild(createActionBannerCard(card));
      }
    });

    return container;
  }

  function createFixtureCard(card) {
    const el = document.createElement('div');
    el.className = 'rfc-fixture-card';

    const remainingText = card.remaining > 0 
      ? `<span class="badge bg-success-subtle text-success border border-success">${card.remaining.toLocaleString()} seats left</span>`
      : `<span class="badge bg-danger-subtle text-danger">Sold Out</span>`;

    el.innerHTML = `
      <div class="rfc-card-header">
        <img src="${escapeHtml(card.logo)}" alt="${escapeHtml(card.opponent)}" class="rfc-opponent-crest" onerror="this.src='${baseUrl}/assets/images/logo.svg'">
        <div class="flex-grow-1">
          <div class="rfc-fixture-title">vs ${escapeHtml(card.opponent)}</div>
          <span class="rfc-competition-badge">${escapeHtml(card.competition)}</span>
        </div>
      </div>
      <div class="rfc-fixture-meta">
        <span class="rfc-meta-item"><i class="fas fa-calendar-alt text-info"></i> ${escapeHtml(card.match_date)}</span>
        <span class="rfc-meta-item"><i class="fas fa-clock text-warning"></i> ${escapeHtml(card.kick_off)}</span>
      </div>
      <div class="d-flex align-items-center justify-content-between mt-1">
        <span class="rfc-price-pill">${escapeHtml(card.price_range)}</span>
        ${remainingText}
      </div>
      <a href="${escapeHtml(card.buy_url)}" class="rfc-card-btn mt-1">
        <i class="fas fa-ticket-alt"></i> Select Stand &amp; Buy Tickets
      </a>
    `;
    return el;
  }

  function createFixtureStandsCard(card) {
    const el = document.createElement('div');
    el.className = 'rfc-fixture-card';

    let standsHtml = '';
    if (Array.isArray(card.sections)) {
      standsHtml = card.sections.map(sec => `
        <div class="rfc-stand-row">
          <div>
            <strong>${escapeHtml(sec.name)}</strong>
            <span class="text-secondary small d-block">${escapeHtml(sec.gate)} • ${sec.remaining} left</span>
          </div>
          <div class="text-end">
            <span class="badge bg-primary mb-1">${escapeHtml(sec.price_formatted)}</span><br>
            <a href="${escapeHtml(sec.direct_buy_url)}" class="btn btn-xs btn-outline-primary" style="font-size: 0.72rem; padding: 1px 8px;">
              ⚡ Buy
            </a>
          </div>
        </div>
      `).join('');
    }

    el.innerHTML = `
      <div class="rfc-card-header">
        <img src="${escapeHtml(card.logo)}" alt="${escapeHtml(card.opponent)}" class="rfc-opponent-crest" onerror="this.src='${baseUrl}/assets/images/logo.svg'">
        <div>
          <div class="rfc-fixture-title">vs ${escapeHtml(card.opponent)}</div>
          <span class="small text-secondary"><i class="fas fa-calendar"></i> ${escapeHtml(card.match_date)} at ${escapeHtml(card.kick_off)}</span>
        </div>
      </div>
      <div class="mt-2">
        ${standsHtml}
      </div>
      <a href="${escapeHtml(card.view_all_url)}" class="rfc-card-btn mt-2">
        <i class="fas fa-eye"></i> View Full Stand Map &amp; Match Details
      </a>
    `;
    return el;
  }

  function createBookingResultCard(card) {
    const el = document.createElement('div');
    el.className = 'rfc-booking-card';

    const statusBadge = card.status === 'paid'
      ? '<span class="badge bg-success">PAID &amp; CONFIRMED</span>'
      : '<span class="badge bg-warning text-dark">RESERVED</span>';

    el.innerHTML = `
      <div class="d-flex justify-content-between align-items-center mb-1">
        <span class="rfc-booking-badge"><i class="fas fa-shield-alt"></i> Verified Ticket</span>
        ${statusBadge}
      </div>
      <div class="rfc-booking-ref-box">
        <span>${escapeHtml(card.booking_ref)}</span>
        <button type="button" class="rfc-copy-btn" data-copy="${escapeHtml(card.booking_ref)}" title="Copy Reference Code">
          <i class="fas fa-copy me-1"></i> Copy
        </button>
      </div>
      <div class="small text-dark mb-2">
        <div><strong>Match:</strong> Riverside FC vs ${escapeHtml(card.opponent)}</div>
        <div><strong>Date:</strong> ${escapeHtml(card.match_date)} (${escapeHtml(card.kick_off)})</div>
        <div><strong>Stand:</strong> ${escapeHtml(card.section_name)} (${escapeHtml(card.gate)})</div>
        <div><strong>Passes:</strong> ${card.ticket_count} ticket(s)</div>
      </div>
      <div class="d-flex gap-2">
        <a href="${escapeHtml(card.view_tickets_url)}" class="rfc-card-btn flex-grow-1" target="_blank">
          <i class="fas fa-qrcode"></i> View Passes
        </a>
        ${card.ticket_pass_url ? `
        <a href="${escapeHtml(card.ticket_pass_url)}" class="btn btn-sm btn-outline-primary" target="_blank" title="Single Pass">
          <i class="fas fa-ticket-alt"></i>
        </a>` : ''}
      </div>
    `;

    // Hook up copy button
    const copyBtn = el.querySelector('.rfc-copy-btn');
    if (copyBtn) {
      copyBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        const code = copyBtn.dataset.copy;
        if (navigator.clipboard) {
          navigator.clipboard.writeText(code).then(() => {
            copyBtn.innerHTML = '<i class="fas fa-check text-success me-1"></i> Copied!';
            setTimeout(() => {
              copyBtn.innerHTML = '<i class="fas fa-copy me-1"></i> Copy';
            }, 2000);
          });
        }
      });
    }

    return el;
  }

  function createUserBookingSummaryCard(card) {
    const el = document.createElement('div');
    el.className = 'rfc-fixture-card';
    el.innerHTML = `
      <div class="rfc-card-header">
        <img src="${escapeHtml(card.logo)}" alt="${escapeHtml(card.opponent)}" class="rfc-opponent-crest" onerror="this.src='${baseUrl}/assets/images/logo.svg'">
        <div class="flex-grow-1">
          <div class="rfc-fixture-title">vs ${escapeHtml(card.opponent)}</div>
          <span class="small text-secondary">${escapeHtml(card.match_date)} • ${escapeHtml(card.booking_ref)}</span>
        </div>
      </div>
      <div class="d-flex justify-content-between align-items-center mt-1">
        <span class="badge bg-secondary">${card.ticket_count} Ticket(s)</span>
        <span class="badge bg-success text-uppercase">${escapeHtml(card.status)}</span>
      </div>
      <a href="${escapeHtml(card.view_url)}" class="rfc-card-btn mt-1">
        <i class="fas fa-qrcode"></i> View Order &amp; QR Passes
      </a>
    `;
    return el;
  }

  function createActionBannerCard(card) {
    const el = document.createElement('div');
    el.className = 'rfc-fixture-card text-center';
    el.innerHTML = `
      <div class="fw-bold text-dark mb-1">${escapeHtml(card.title || '')}</div>
      <div class="small text-secondary mb-2">${escapeHtml(card.subtitle || '')}</div>
      <a href="${escapeHtml(card.btn_url)}" class="rfc-card-btn">
        ${escapeHtml(card.btn_label || 'View Now')}
      </a>
    `;
    return el;
  }

  // Quick Replies
  function renderQuickReplies(replies) {
    const container = document.createElement('div');
    container.className = 'rfc-quick-replies';

    replies.forEach(replyText => {
      const chip = document.createElement('button');
      chip.type = 'button';
      chip.className = 'rfc-reply-chip';
      chip.textContent = replyText;

      chip.addEventListener('click', () => {
        sendQuery(replyText);
      });

      container.appendChild(chip);
    });

    return container;
  }

})();
