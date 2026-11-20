/**
 * Riverside FC Match Ticketing System - Client Logic
 */

document.addEventListener('DOMContentLoaded', () => {
    // Initialize Match Countdown Timer if element exists
    initCountdownTimer();

    // Initialize Stand / Section Picker in fixture.php
    initStandPicker();

    // Initialize Payment Method Switcher in checkout.php
    initPaymentSwitcher();

    // Initialize Print Trigger
    initPrintButtons();
});

/**
 * Live Countdown Timer to Kick-Off
 */
function initCountdownTimer() {
    const timerEl = document.getElementById('match-countdown');
    if (!timerEl) return;

    const targetDateStr = timerEl.getAttribute('data-kickoff');
    if (!targetDateStr) return;

    const targetDate = new Date(targetDateStr).getTime();

    function update() {
        const now = new Date().getTime();
        const diff = targetDate - now;

        if (diff <= 0) {
            timerEl.innerHTML = '<span class="text-warning fw-bold">MATCHDAY LIVE!</span>';
            return;
        }

        const days = Math.floor(diff / (1000 * 60 * 60 * 24));
        const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((diff % (1000 * 60)) / 1000);

        const daysEl = document.getElementById('cd-days');
        const hoursEl = document.getElementById('cd-hours');
        const minsEl = document.getElementById('cd-mins');
        const secsEl = document.getElementById('cd-secs');

        if (daysEl) daysEl.textContent = String(days).padStart(2, '0');
        if (hoursEl) hoursEl.textContent = String(hours).padStart(2, '0');
        if (minsEl) minsEl.textContent = String(minutes).padStart(2, '0');
        if (secsEl) secsEl.textContent = String(seconds).padStart(2, '0');
    }

    update();
    setInterval(update, 1000);
}

/**
 * Interactive Stand / Section and Quantity Picker
 */
function initStandPicker() {
    const standCards = document.querySelectorAll('.stand-option-card');
    const orderSectionName = document.getElementById('summary-section-name');
    const orderUnitPrice = document.getElementById('summary-unit-price');
    const orderQty = document.getElementById('summary-qty');
    const orderTotal = document.getElementById('summary-total');
    const checkoutBtn = document.getElementById('btn-checkout');
    const selectedInput = document.getElementById('input-selected-type-id');
    const qtyInput = document.getElementById('input-quantity');

    if (!standCards.length || !checkoutBtn) return;

    let currentSelectedCard = null;

    function recalculate() {
        if (!currentSelectedCard) {
            if (checkoutBtn) checkoutBtn.disabled = true;
            return;
        }

        const price = parseFloat(currentSelectedCard.getAttribute('data-price')) || 0;
        const name = currentSelectedCard.getAttribute('data-name');
        const maxAvailable = parseInt(currentSelectedCard.getAttribute('data-remaining')) || 0;
        const typeId = currentSelectedCard.getAttribute('data-id');

        let quantity = parseInt(qtyInput.value) || 1;
        if (quantity < 1) quantity = 1;
        if (quantity > maxAvailable) quantity = maxAvailable;
        if (quantity > 10) quantity = 10; // Max per transaction limit

        qtyInput.value = quantity;
        if (selectedInput) selectedInput.value = typeId;

        const total = price * quantity;

        if (orderSectionName) orderSectionName.textContent = name;
        if (orderUnitPrice) orderUnitPrice.textContent = 'GH₵ ' + price.toFixed(2);
        if (orderQty) orderQty.textContent = quantity;
        if (orderTotal) orderTotal.textContent = 'GH₵ ' + total.toFixed(2);

        // Update stepper button limits
        const btnMinus = document.querySelector('.btn-qty-minus');
        const btnPlus = document.querySelector('.btn-qty-plus');
        if (btnMinus) btnMinus.disabled = quantity <= 1;
        if (btnPlus) btnPlus.disabled = quantity >= Math.min(10, maxAvailable);

        if (checkoutBtn) {
            checkoutBtn.disabled = maxAvailable <= 0;
            if (maxAvailable <= 0) {
                checkoutBtn.innerHTML = '<i class="fas fa-ban me-2"></i> Section Sold Out';
            } else {
                checkoutBtn.innerHTML = '<i class="fas fa-ticket-alt me-2"></i> Proceed to Checkout (GH₵ ' + total.toFixed(2) + ')';
            }
        }
    }

    standCards.forEach(card => {
        card.addEventListener('click', () => {
            const remaining = parseInt(card.getAttribute('data-remaining')) || 0;
            if (remaining <= 0) return; // Can't select sold out

            standCards.forEach(c => c.classList.remove('selected'));
            card.classList.add('selected');
            currentSelectedCard = card;
            recalculate();
        });
    });

    // Plus/Minus steppers
    const btnMinus = document.querySelector('.btn-qty-minus');
    const btnPlus = document.querySelector('.btn-qty-plus');

    if (btnMinus) {
        btnMinus.addEventListener('click', (e) => {
            e.preventDefault();
            let val = parseInt(qtyInput.value) || 1;
            if (val > 1) {
                qtyInput.value = val - 1;
                recalculate();
            }
        });
    }

    if (btnPlus) {
        btnPlus.addEventListener('click', (e) => {
            e.preventDefault();
            let val = parseInt(qtyInput.value) || 1;
            qtyInput.value = val + 1;
            recalculate();
        });
    }

    if (qtyInput) {
        qtyInput.addEventListener('change', recalculate);
    }

    // Auto-select first available stand
    const firstAvailable = document.querySelector('.stand-option-card:not([data-remaining="0"])');
    if (firstAvailable) {
        firstAvailable.click();
    }
}

/**
 * Payment method toggle (MoMo vs Card)
 */
function initPaymentSwitcher() {
    const paymentRadios = document.querySelectorAll('input[name="payment_method"]');
    const momoDetails = document.getElementById('momo-details-panel');
    const cardDetails = document.getElementById('card-details-panel');

    if (!paymentRadios.length) return;

    function toggleMethod() {
        const selected = document.querySelector('input[name="payment_method"]:checked')?.value || 'momo_mtn';
        if (selected.startsWith('momo')) {
            if (momoDetails) momoDetails.style.display = 'block';
            if (cardDetails) cardDetails.style.display = 'none';
        } else if (selected === 'card') {
            if (momoDetails) momoDetails.style.display = 'none';
            if (cardDetails) cardDetails.style.display = 'block';
        } else {
            if (momoDetails) momoDetails.style.display = 'none';
            if (cardDetails) cardDetails.style.display = 'none';
        }
    }

    paymentRadios.forEach(r => r.addEventListener('change', toggleMethod));
    toggleMethod();

    // Prevent double form submission
    const checkoutForm = document.getElementById('checkout-form');
    if (checkoutForm) {
        checkoutForm.addEventListener('submit', (e) => {
            const submitBtn = document.getElementById('btn-pay-now');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Processing Order...';
            }
        });
    }
}

/**
 * Print Ticket Trigger
 */
function initPrintButtons() {
    const printBtns = document.querySelectorAll('.btn-print-ticket');
    printBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            window.print();
        });
    });
}
