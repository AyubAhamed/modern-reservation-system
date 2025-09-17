/* Seaford RSL Booking — Front-end */
(function($){
  const $form   = $('#mr-form');
  const $date   = $('#mr-date');
  const $meal   = $('#mr-meal');
  const $guests = $('#mr-guests');
  const $slots  = $('#mr-slots');
  const $time   = $('#mr-time');
  const $resp   = $('#mr-response');
  const $submit = $('#mr-submit');

  // Init datepicker
  if (typeof flatpickr !== 'undefined') {
    flatpickr('#mr-date', {
      minDate: 'today',
      dateFormat: 'Y-m-d',
      disableMobile: false
    });
  }

  // Init Choices
  if (typeof Choices !== 'undefined') {
    new Choices('#mr-meal',   {searchEnabled:false, itemSelectText:''});
    new Choices('#mr-guests', {searchEnabled:false, itemSelectText:''});
  }

  function renderSlots(list){
    $slots.empty();
    $time.val('');
    if(!list || !list.length){
      $slots.append('<div class="mr-empty">No time slots defined. Please contact us.</div>');
      return;
    }
    list.forEach(function(slot){
      const pill = $('<button type="button" class="mr-slot"></button>');
      pill.text(slot.time);
      if(!slot.available) pill.addClass('disabled');
      pill.on('click', function(){
        if (pill.hasClass('disabled')) return;
        $('.mr-slot').removeClass('selected');
        pill.addClass('selected');
        $time.val(slot.time);
      });
      $slots.append(pill);
    });
  }

  function fetchSlots(){
    $slots.addClass('loading');
    $.post(MR_AJAX.ajax_url, {
      action: 'mr_get_slots',
      nonce: MR_AJAX.nonce,
      date: $date.val(),
      meal: $meal.val(),
      guests: $guests.val()
    }, function(res){
      $slots.removeClass('loading');
      if(res && res.success){
        renderSlots(res.data);
      } else {
        renderSlots([]);
      }
    });
  }

  // Trigger fetch on change
  $date.on('change', fetchSlots);
  $meal.on('change', fetchSlots);
  $guests.on('change', fetchSlots);

  // Initial fetch when page loads (after a small delay for widgets)
  setTimeout(fetchSlots, 300);

  function showResp(msg, ok){
    $resp.text(msg).removeClass('success error show')
      .addClass(ok ? 'success' : 'error').addClass('show');
    if (ok) {
      $form[0].reset();
      $('.mr-slot').removeClass('selected');
      $time.val('');
      setTimeout(fetchSlots, 250);
    }
  }

  $form.on('submit', function(e){
    e.preventDefault();
    $submit.prop('disabled', true).text('Booking...');
    const data = $form.serializeArray().reduce((acc, cur) => (acc[cur.name] = cur.value, acc), {});
    data['action'] = 'mr_submit';
    data['nonce']  = MR_AJAX.nonce;

    $.post(MR_AJAX.ajax_url, data, function(res){
      $submit.prop('disabled', false).text('Find a Table');
      if(res && res.success){
        showResp(res.data.message, true);
      } else {
        showResp(res && res.data ? res.data.message : 'Something went wrong. Please try again.', false);
      }
    });
  });

})(jQuery);
