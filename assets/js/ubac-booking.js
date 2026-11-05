/**
 * Ubac Booking -- the booking widget's logic.
 * Interactive calendar, two panels (dates and times), and the transition
 * between them.
 */
(function () {
  'use strict';

  // Month and day names are injected via wp_localize_script for i18n support.
  const i18n = (typeof ubacBookingData !== 'undefined' && ubacBookingData.i18n) ? ubacBookingData.i18n : {};
  const MONTHS = i18n.months || ['January','February','March','April','May','June','July','August','September','October','November','December'];
  const DAYS   = i18n.days   || ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
  const WEEKDAYS = i18n.weekdays || ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'];

  /**
   * One complete booking widget.
   * Several instances can live on the same page -- the manage page, for
   * instance.
   */
  class UbacBookingWidget {
    constructor(rootEl, options) {
      this.root = rootEl;
      this.options = options || {};
      this.currentDate = new Date();
      this.viewMonth = this.currentDate.getMonth();
      this.viewYear = this.currentDate.getFullYear();
      this.selectedDate = null; // format YYYY-MM-DD
      this.selectedTime = null; // format HH:MM
      this.availableCache = {}; // Available slots cached per date.

      this.bindElements();
      this.bindEvents();
      this.renderCalendar();
    }

    bindElements() {
      this.gridEl = this.root.querySelector('.ubac-booking__step1-grid');
      this.datesPanel = this.root.querySelector('.ubac-booking__dates-panel');
      this.timesPanel = this.root.querySelector('.ubac-booking__times-panel');
      this.calMonthLabel = this.root.querySelector('.ubac-booking__cal-month-label');
      this.calDays = this.root.querySelector('.ubac-booking__cal-days');
      this.calPrev = this.root.querySelector('.ubac-booking__cal-prev');
      this.calNext = this.root.querySelector('.ubac-booking__cal-next');
      this.timesDateLabel = this.root.querySelector('.ubac-booking__times-date-label');
      this.timesList = this.root.querySelector('.ubac-booking__times-list');

      // Populate weekday header labels (Mon–Sun) from i18n data.
      const weekdaysEl = this.root.querySelector('.ubac-booking__cal-weekdays');
      if (weekdaysEl && weekdaysEl.children.length === 0) {
        WEEKDAYS.forEach(function (label) {
          const span = document.createElement('span');
          span.textContent = label;
          weekdaysEl.appendChild(span);
        });
      }
    }

    bindEvents() {
      if (this.calPrev) {
        this.calPrev.addEventListener('click', () => this.changeMonth(-1));
      }
      if (this.calNext) {
        this.calNext.addEventListener('click', () => this.changeMonth(1));
      }
    }

    changeMonth(delta) {
      this.viewMonth += delta;
      if (this.viewMonth > 11) { this.viewMonth = 0; this.viewYear++; }
      if (this.viewMonth < 0) { this.viewMonth = 11; this.viewYear--; }
      this.renderCalendar();
    }

    /**
     * Draws the calendar for the month currently on show.
     */
    renderCalendar() {
      const today = new Date();
      today.setHours(0, 0, 0, 0);

      const maxDate = new Date(today);
      maxDate.setDate(maxDate.getDate() + (this.options.maxDaysAhead || 60));

      this.calMonthLabel.textContent = MONTHS[this.viewMonth] + ' ' + this.viewYear;

      // Disable "previous month" on the current month and earlier.
      const isCurrentMonth = this.viewYear === today.getFullYear() && this.viewMonth === today.getMonth();
      this.calPrev.disabled = isCurrentMonth;

      const firstDay = new Date(this.viewYear, this.viewMonth, 1);
      // Monday = 0 ... Sunday = 6, shifted from getDay(), which gives Sunday = 0.
      let startOffset = firstDay.getDay() - 1;
      if (startOffset < 0) startOffset = 6;

      const daysInMonth = new Date(this.viewYear, this.viewMonth + 1, 0).getDate();

      this.calDays.innerHTML = '';

      // Empty cells before the first of the month.
      for (let i = 0; i < startOffset; i++) {
        const empty = document.createElement('div');
        empty.className = 'ubac-booking__cal-day ubac-booking__cal-day--empty';
        this.calDays.appendChild(empty);
      }

      const openDays = this.options.openDays || [1, 2, 3, 4, 5, 6];

      for (let day = 1; day <= daysInMonth; day++) {
        const dateObj = new Date(this.viewYear, this.viewMonth, day);
        dateObj.setHours(0, 0, 0, 0);
        const dayOfWeek = dateObj.getDay();
        const dateStr = this.formatDateISO(dateObj);

        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'ubac-booking__cal-day';
        btn.textContent = day;

        const isPast = dateObj < today;
        const isTooFar = dateObj > maxDate;
        const isClosedDay = !openDays.includes(dayOfWeek);
        const isToday = dateObj.getTime() === today.getTime();

        if (isPast || isTooFar || isClosedDay) {
          btn.classList.add('ubac-booking__cal-day--disabled');
          btn.disabled = true;
        } else {
          btn.classList.add('ubac-booking__cal-day--available');
          btn.addEventListener('click', () => this.selectDate(dateStr, dateObj));
        }

        if (isToday) {
          btn.classList.add('ubac-booking__cal-day--today');
        }

        if (this.selectedDate === dateStr) {
          btn.classList.add('ubac-booking__cal-day--selected');
        }

        this.calDays.appendChild(btn);
      }
    }

    formatDateISO(dateObj) {
      const y = dateObj.getFullYear();
      const m = String(dateObj.getMonth() + 1).padStart(2, '0');
      const d = String(dateObj.getDate()).padStart(2, '0');
      return `${y}-${m}-${d}`;
    }

    formatDateLong(dateObj) {
      return DAYS[dateObj.getDay()] + ' ' + dateObj.getDate() + ' ' + MONTHS[dateObj.getMonth()];
    }

    /**
     * Picks a date in the calendar: opens the second panel (times) while
     * shrinking the first (dates).
     */
    selectDate(dateStr, dateObj) {
      this.selectedDate = dateStr;
      this.selectedTime = null;
      this.renderCalendar();

      // Open the times panel, if this widget has one.
      if (this.timesPanel && this.gridEl) {
        this.timesPanel.style.display = 'block';
        this.gridEl.classList.add('has-date-selected');
      }

      this.timesDateLabel.textContent = this.formatDateLong(dateObj);
      this.fetchAvailableTimes(dateStr);

      if (this.options.onDateSelected) {
        this.options.onDateSelected(dateStr, dateObj);
      }
    }

    /**
     * Asks WordPress over AJAX for the free slots on one date.
     */
    fetchAvailableTimes(dateStr) {
      if (this.availableCache[dateStr]) {
        this.renderTimes(this.availableCache[dateStr]);
        return;
      }

      this.timesList.innerHTML = '<div class="ubac-booking__times-loading"><div class="ubac-booking__spinner" style="border-top-color:#6d4cf9;border-color:rgba(109,76,249,0.2);"></div></div>';

      const formData = new FormData();
      formData.append('action', 'ubac_get_available_times');
      formData.append('nonce', ubacBookingData.nonce);
      formData.append('date', dateStr);

      fetch(ubacBookingData.ajaxUrl, { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
          if (data.success) {
            this.availableCache[dateStr] = data.data.times;
            this.renderTimes(data.data.times);
          } else {
            this.timesList.innerHTML = '<div class="ubac-booking__times-empty">' + ubacBookingData.i18n.error + '</div>';
          }
        })
        .catch(() => {
          this.timesList.innerHTML = '<div class="ubac-booking__times-empty">' + ubacBookingData.i18n.error + '</div>';
        });
    }

    /**
     * Shows the list of available times.
     */
    renderTimes(times) {
      this.timesList.innerHTML = '';

      if (!times || times.length === 0) {
        this.timesList.innerHTML = '<div class="ubac-booking__times-empty">' + ubacBookingData.i18n.noSlots + '</div>';
        return;
      }

      times.forEach(time => {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'ubac-booking__time-slot';
        btn.textContent = time;
        btn.dataset.time = time;
        btn.addEventListener('click', () => this.selectTime(time, btn, times));
        this.timesList.appendChild(btn);
      });
    }

    /**
     * Picks a time. The chosen button turns into a [time] [Next] pair and
     * the others stay below it.
     */
    selectTime(time, btnEl, allTimes) {
      this.selectedTime = time;

      // Rebuilds the list: the chosen slot becomes a row with a Next button,
      // and the other slots stay plain buttons, in their original order.
      this.timesList.innerHTML = '';

      allTimes.forEach(t => {
        if (t === time) {
          const row = document.createElement('div');
          row.className = 'ubac-booking__time-slot-row';

          const selectedBtn = document.createElement('button');
          selectedBtn.type = 'button';
          selectedBtn.className = 'ubac-booking__time-slot ubac-booking__time-slot--selected';
          selectedBtn.textContent = t;
          selectedBtn.addEventListener('click', () => {
            // Lets the visitor deselect and go back to the plain list.
            this.renderTimes(allTimes);
          });

          const nextBtn = document.createElement('button');
          nextBtn.type = 'button';
          nextBtn.className = 'ubac-booking__time-slot ubac-booking__time-slot--confirm';
          nextBtn.textContent = ubacBookingData.i18n.next;
          nextBtn.addEventListener('click', () => {
            if (this.options.onTimeConfirmed) {
              this.options.onTimeConfirmed(time);
            }
          });

          row.appendChild(selectedBtn);
          row.appendChild(nextBtn);
          this.timesList.appendChild(row);
        } else {
          const btn = document.createElement('button');
          btn.type = 'button';
          btn.className = 'ubac-booking__time-slot';
          btn.textContent = t;
          btn.addEventListener('click', () => this.selectTime(t, btn, allTimes));
          this.timesList.appendChild(btn);
        }
      });

      if (this.options.onTimeSelected) {
        this.options.onTimeSelected(time);
      }
    }

    reset() {
      this.selectedDate = null;
      this.selectedTime = null;
      this.viewMonth = new Date().getMonth();
      this.viewYear = new Date().getFullYear();
      this.timesList.innerHTML = '<div class="ubac-booking__times-placeholder">' + ubacBookingData.i18n.pickDate + '</div>';
      this.timesDateLabel.textContent = '';
      if (this.timesPanel && this.gridEl) {
        this.timesPanel.style.display = 'none';
        this.gridEl.classList.remove('has-date-selected');
      }
      this.renderCalendar();
    }
  }

  // ============================================
  // WIDGET PRINCIPAL : [ubac_booking] (page contact)
  // ============================================
  function initMainBookingWidget() {
    const widgetRoot = document.getElementById('ubac-booking-widget');
    if (!widgetRoot) return;

    const backBtn = widgetRoot.querySelector('.ubac-booking__back');
    const headerBox = widgetRoot.querySelector('.ubac-booking__header');
    const recapTime = widgetRoot.querySelector('.ubac-booking__recap-time');
    const recapDate = widgetRoot.querySelector('.ubac-booking__recap-date');
    const step2Only = widgetRoot.querySelectorAll('.ubac-booking__step2-only');

    const step1 = widgetRoot.querySelector('[data-step="1"]');
    const step2 = widgetRoot.querySelector('[data-step="2"]');
    const step3 = widgetRoot.querySelector('[data-step="3"]');

    const form = widgetRoot.querySelector('.ubac-booking__form');
    const formError = widgetRoot.querySelector('.ubac-booking__form-error');
    const submitBtn = widgetRoot.querySelector('.ubac-booking__submit');

    let selectedDateObj = null;

    const widget = new UbacBookingWidget(widgetRoot, {
      maxDaysAhead: ubacBookingData.maxDaysAhead,
      openDays: ubacBookingData.openDays,
      onDateSelected: function (dateStr, dateObj) {
        selectedDateObj = dateObj;
      },
      // Fired by the "Next" button that appears beside the chosen time.
      onTimeConfirmed: function (time) {
        goToStep2();
      },
    });

    widget.timesList.innerHTML = '<div class="ubac-booking__times-placeholder">' + ubacBookingData.i18n.pickDate + '</div>';

    function goToStep2() {
      if (!widget.selectedDate || !widget.selectedTime) return;

      // Refresh the date and time summary in the header.
      recapTime.textContent = widget.selectedTime;
      recapDate.textContent = widget.formatDateLong(selectedDateObj);

      step1.style.display = 'none';
      step2.style.display = 'block';
      step2.classList.add('ubac-booking__step');
      headerBox.style.display = 'flex';
      backBtn.style.display = 'flex';
      step2Only.forEach(el => el.style.display = '');
    }

    function goToStep1() {
      step2.style.display = 'none';
      step1.style.display = 'block';
      headerBox.style.display = 'none';
      backBtn.style.display = 'none';
      formError.style.display = 'none';
      step2Only.forEach(el => el.style.display = 'none');
    }

    backBtn.addEventListener('click', function () {
      const visibleStep = step2.style.display !== 'none' ? 2 : 3;
      if (visibleStep === 2) {
        goToStep1();
      }
    });

    form.addEventListener('submit', function (e) {
      e.preventDefault();
      formError.style.display = 'none';

      const name = form.querySelector('#ubac-name').value.trim();
      const email = form.querySelector('#ubac-email').value.trim();
      const message = form.querySelector('#ubac-message').value.trim();

      if (!name || !email) {
        formError.textContent = i18n.error || 'Please provide your name and email.';
        formError.style.display = 'block';
        return;
      }

      submitBtn.disabled = true;
      submitBtn.querySelector('.ubac-booking__submit-text').textContent = ubacBookingData.i18n.sending;
      submitBtn.querySelector('.ubac-booking__spinner').style.display = 'inline-block';

      const formData = new FormData();
      formData.append('action', 'ubac_create_booking');
      formData.append('nonce', ubacBookingData.nonce);
      formData.append('date', widget.selectedDate);
      formData.append('time', widget.selectedTime);
      formData.append('name', name);
      formData.append('email', email);
      formData.append('message', message);
      const hpField = form.querySelector('#ubac-hp');
      formData.append('ubac_hp', hpField ? hpField.value : '');

      fetch(ubacBookingData.ajaxUrl, { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
          if (data.success) {
            showStep3();
          } else {
            const msg = data.data && data.data.message === 'slot_taken'
              ? ubacBookingData.i18n.slotTaken
              : (data.data && data.data.message ? data.data.message : ubacBookingData.i18n.error);
            formError.textContent = msg;
            formError.style.display = 'block';
            resetSubmitBtn();
          }
        })
        .catch(() => {
          formError.textContent = ubacBookingData.i18n.error;
          formError.style.display = 'block';
          resetSubmitBtn();
        });
    });

    function resetSubmitBtn() {
      submitBtn.disabled = false;
      submitBtn.querySelector('.ubac-booking__submit-text').textContent = ubacBookingData.i18n.confirm;
      submitBtn.querySelector('.ubac-booking__spinner').style.display = 'none';
    }

    function showStep3() {
      step2.style.display = 'none';
      step3.style.display = 'block';
      headerBox.style.display = 'none';
      backBtn.style.display = 'none';

      const recap = widgetRoot.querySelector('.ubac-booking__success-recap');
      recap.textContent = widget.selectedTime + ' · ' + widget.formatDateLong(selectedDateObj);
    }
  }

  // ============================================
  // WIDGET DE GESTION (page modifier/annuler)
  // ============================================
  function initManageWidget() {
    const manageRoot = document.getElementById('ubac-manage-widget');
    if (!manageRoot) return;

    const token = manageRoot.dataset.token;
    const rescheduleBtn = document.getElementById('ubac-manage-reschedule-btn');
    const cancelBtn = document.getElementById('ubac-manage-cancel-btn');
    const rescheduleZone = document.getElementById('ubac-manage-reschedule-zone');
    const confirmRescheduleBtn = document.getElementById('ubac-manage-confirm-reschedule');
    const resultBox = document.getElementById('ubac-manage-result');
    const actionsBox = manageRoot.querySelector('.ubac-manage-page__actions');

    let rescheduleWidget = null;

    rescheduleBtn.addEventListener('click', function () {
      rescheduleZone.style.display = 'block';
      rescheduleBtn.style.display = 'none';
      cancelBtn.style.display = 'none';

      if (!rescheduleWidget) {
        const rescheduleRoot = document.getElementById('ubac-booking-widget-reschedule');
        rescheduleWidget = new UbacBookingWidget(rescheduleRoot, {
          maxDaysAhead: 60,
          openDays: [1, 2, 3, 4, 5, 6],
          onTimeConfirmed: function () {
            confirmRescheduleBtn.disabled = false;
          },
        });
        rescheduleWidget.timesList.innerHTML = '<div class="ubac-booking__times-placeholder">' + (i18n.pickDate || 'Please choose a date.') + '</div>';
      }
    });

    cancelBtn.addEventListener('click', function () {
      cancelBtn.disabled = true;

      const formData = new FormData();
      formData.append('action', 'ubac_cancel_booking');
      formData.append('nonce', ubacBookingData.nonce);
      formData.append('token', token);

      fetch(ubacBookingData.ajaxUrl, { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
          if (data.success) {
            showResult(i18n.cancelSuccess || 'Your appointment has been cancelled. A confirmation email will be sent to you.', true);
            actionsBox.style.display = 'none';
          } else {
            showResult(data.data.message || i18n.error || 'An error occurred.', false);
            cancelBtn.disabled = false;
          }
        });
    });

    confirmRescheduleBtn.addEventListener('click', function () {
      if (!rescheduleWidget.selectedDate || !rescheduleWidget.selectedTime) return;

      confirmRescheduleBtn.disabled = true;
      confirmRescheduleBtn.textContent = i18n.sending || 'Sending…';

      const formData = new FormData();
      formData.append('action', 'ubac_reschedule_booking');
      formData.append('nonce', ubacBookingData.nonce);
      formData.append('token', token);
      formData.append('date', rescheduleWidget.selectedDate);
      formData.append('time', rescheduleWidget.selectedTime);

      fetch(ubacBookingData.ajaxUrl, { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
          if (data.success) {
            showResult(i18n.rescheduleSuccess || 'Your appointment has been rescheduled. A confirmation email will be sent to you.', true);
            rescheduleZone.style.display = 'none';
          } else {
            const msg = data.data.message === 'slot_taken' ? (i18n.slotTaken || 'This slot is no longer available.') : (data.data.message || i18n.error || 'An error occurred.');
            showResult(msg, false);
            confirmRescheduleBtn.disabled = false;
            confirmRescheduleBtn.textContent = i18n.confirmReschedule || 'Confirm new slot';
          }
        });
    });

    function showResult(message, isSuccess) {
      resultBox.textContent = message;
      resultBox.className = 'ubac-manage-page__result ' + (isSuccess ? 'ubac-manage-page__result--success' : 'ubac-manage-page__result--error');
      resultBox.style.display = 'block';
    }
  }

  document.addEventListener('DOMContentLoaded', function () {
    initMainBookingWidget();
    initManageWidget();
  });

})();
