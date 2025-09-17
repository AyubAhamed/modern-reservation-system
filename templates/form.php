<?php
$brand = get_option('mr_brand_name', get_bloginfo('name'));
$meals = get_option('mr_meals', array('Breakfast','Lunch','Dinner'));
$max_guests = (int) get_option('mr_max_guests', 12);
$terms_url = get_option('mr_terms_url','');
?>
<div class="mr-wrapper">
  <div class="mr-card">
    <div class="mr-header">
      <h2><?php echo esc_html($brand); ?><?php esc_html_e('','modern-reservations');?></h2>
      <p class="mr-sub"><?php esc_html_e('Choose your date, guests, and meal. Then pick a time slot.','modern-reservations');?></p>
    </div>
    <form id="mr-form" class="mr-grid" autocomplete="on">
            <div class="mr-inline">
        <div class="mr-field">
          <label><?php esc_html_e('Date','modern-reservations');?> *</label>
          <input id="mr-date" name="date" type="text" placeholder="Select date" required>
        </div>
		<div class="mr-field">
		  <label><?php esc_html_e('Guests','modern-reservations');?> *</label>
		  <select id="mr-guests" name="guests" required>
			<option value="" disabled selected><?php esc_html_e('Please select guest','modern-reservations');?></option>
			<?php for($i=1;$i<=$max_guests;$i++): ?>
			  <option value="<?php echo $i;?>">
				<?php echo $i;?> <?php echo ($i==1)?__('Guest','modern-reservations'):__('Guests','modern-reservations');?>
			  </option>
			<?php endfor; ?>
		  </select>
		</div>

        <div class="mr-field">
		  <label><?php esc_html_e('Meal','modern-reservations');?> *</label>
		  <select id="mr-meal" name="meal" required>
			<option value="" disabled selected><?php esc_html_e('Please select meal','modern-reservations');?></option>
			<?php foreach($meals as $meal): ?>
			  <option value="<?php echo esc_attr($meal);?>">
				<?php echo esc_html($meal);?>
			  </option>
			<?php endforeach; ?>
		  </select>
		</div>

        <div class="mr-field mr-inline-btn">
          <label>&nbsp;</label>
          <button type="button" class="mr-btn" id="mr-find"><?php esc_html_e('Find Table','modern-reservations');?></button>
        </div>
      </div>
	  
          <p class="mr-warning">
              <?php echo wp_kses_post(get_option('mr_booking_notice','')); ?>
          </p>
                
		
			  <!-- Wrap rest of the form -->
		<div id="mr-rest-form" style="display:none;">
		  <div class="mr-field mr-times">
			<label><?php esc_html_e('Time Slots','modern-reservations');?></label>
			<div id="mr-slots" class="mr-slots"></div>
			<input type="hidden" id="mr-time" name="time">
		  </div>

		  <!-- Inline row for Name, Phone, Email -->
		<div class="mr-inline" id="mr-contact-row" style="display:none;">
		  <div class="mr-field">
			<label><?php esc_html_e('Name','modern-reservations');?> *</label>
			<input type="text" name="name" id="mr-name" placeholder="Your full name" required>
		  </div>
		  <div class="mr-field">
			<label><?php esc_html_e('Phone','modern-reservations');?> *</label>
			<input type="tel" name="phone" id="mr-phone" placeholder="+94 7X XXX XXXX" required>
		  </div>
		  <div class="mr-field">
			<label><?php esc_html_e('Email','modern-reservations');?> *</label>
			<input type="email" name="email" id="mr-email" placeholder="you@example.com" required>
		  </div>
		</div>

		  <div class="mr-field mr-note">
			<label><?php esc_html_e('Note','modern-reservations');?></label>
			<textarea name="note" id="mr-note" placeholder="<?php esc_attr_e('Occasion, seating preference, allergies, etc.','modern-reservations');?>"></textarea>
		  </div>

		  <div class="mr-field mr-terms">
			<label class="mr-checkbox">
			  <input type="checkbox" name="agree" id="mr-agree" required>
			  <span><?php esc_html_e('I agree to the','modern-reservations');?> <a class="mr-terms-url" target="_blank" href="<?php echo esc_url($terms_url ?: '#');?>"><?php esc_html_e('Terms & Conditions','modern-reservations');?></a></span>
			</label>
		  </div>

		  <div class="mr-actions">
			<button type="submit" class="mr-btn" id="mr-submit"><?php esc_html_e('Book Now','modern-reservations');?></button>
		  </div>
		</div>


      <div id="mr-response" class="mr-response" role="alert" aria-live="polite"></div>
    </form>
  </div>
</div>
<div id="mr-terms-modal" class="mr-modal" style="display:none;">
  <div class="mr-modal-content">
    <span class="mr-close">&times;</span>
    <h3>Terms & Conditions</h3>
    <p>
      Bookings must be confirmed prior to your arrival. If the staff are unable to contact 
      the phone number or email address provided on the booking to confirm attendance, the 
      bistro may be unable to hold the reservation. It is a requirement that all bookings 
      must adhere to the government Covid-19 regulations within the venue including the QR 
      sign in, sanitising &amp; having a valid Covid Vaccination Certificate. 
      We thank you for your support from the Seaford RSL.
    </p>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const findBtn = document.getElementById('mr-find');
  const restForm = document.getElementById('mr-rest-form');
  const dateField = document.getElementById('mr-date');
  const guestsField = document.getElementById('mr-guests');
  const mealField = document.getElementById('mr-meal');

  findBtn.addEventListener('click', function() {
    let errors = [];

    if (!dateField.value.trim()) {
      errors.push('Please select a date.');
      dateField.focus();
    } else if (!guestsField.value) {
      errors.push('Please select number of guests.');
      guestsField.focus();
    } else if (!mealField.value) {
      errors.push('Please select a meal.');
      mealField.focus();
    }

    if (errors.length > 0) {
      alert(errors[0]); // Show first error
      return;
    }

    // Show the rest of the form
    restForm.style.display = 'block';

    // Optionally scroll to the rest of the form
    restForm.style.display = 'grid'; // keep grid layout
	  document.getElementById('mr-contact-row').style.display = 'grid'; // show contact row
	  restForm.scrollIntoView({ behavior: 'smooth' });
  });
});
	
document.addEventListener('DOMContentLoaded', function() {
  const modal = document.getElementById('mr-terms-modal');
  const link = document.querySelector('.mr-terms-url');
  const closeBtn = document.querySelector('.mr-close');

  // Open modal when link clicked
  link.addEventListener('click', function(e) {
    e.preventDefault();
    modal.style.display = 'flex';
  });

  // Close when X clicked
  closeBtn.addEventListener('click', function() {
    modal.style.display = 'none';
  });

  // Close when clicking outside modal content
  modal.addEventListener('click', function(e) {
    if (e.target === modal) {
      modal.style.display = 'none';
    }
  });
});
</script>
