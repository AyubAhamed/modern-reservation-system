# modern-reservation-system
A modern, mobile-friendly reservation system with date picker, guest &amp; meal selectors, dynamic time slots, email notifications, and an admin settings panel.

Contributors: ayub-ahamed
Tags: reservations, booking, restaurant, tables, calendar
Requires at least: 5.8
Tested up to: 6.5
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A sleek, modern reservation plugin. Use the shortcode [modern_reservation].


= Features =
* Date picker (Flatpickr)
* Guests & Meal selectors (Choices.js)
* Dynamic, capacity-aware time slots
* AJAX-based — no page reloads
* Email notifications to guest + admin
* Admin settings: meals, timeslots JSON, capacity, max guests, brand, terms URL
* Custom Post Type for reservations


= Installation =
1. Upload the ZIP via Plugins → Add New → Upload Plugin.
2. Activate the plugin.
3. Go to **Reservations → Settings** to configure meals and time slots.
4. Add the shortcode `[modern_reservation]` to a page.


= Notes =
Time slots are defined per meal via JSON, e.g.:
{
  "Dinner": ["17:30","17:45","18:00","18:15","18:30","18:45","19:00","19:15","19:30","19:45","20:00","20:15","20:30"]
}
