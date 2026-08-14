# Build notes — Accounts & login (spec §6)

The original build shipped the *post*-login half of §6 (My Account extras,
special-date + WhatsApp capture, the value-ladder hook the CRM plugin fills) but
no way for a customer to get an account or sign in. This note covers the layer
added to close that gap.

## Files

| File | What it does |
| --- | --- |
| `inc/auth.php` | All auth logic. Loaded from `functions.php` inside the `class_exists('WooCommerce')` block. |
| `woocommerce/myaccount/form-login.php` | Branded sign-in / register screen — one panel at a time. |
| `assets/js/account.js` | Instant panel switching (enhancement only). |
| `woocommerce/checkout/thankyou.php` | Now renders the post-order account claim form + a notice outlet. |
| `header.php` | Account icon in the header actions, beside the cart. |
| `inc/template-tags.php` | Added the `user` icon. |
| `assets/css/woocommerce.css` | `.econ-auth`, `.econ-claim`, hints, nudges. Loads only on cart/checkout/account. |
| `assets/css/components.css` | Signed-in dot on the header account icon. |

Nothing was added to the theme's template count: My Account is a
WooCommerce-managed page, like cart and checkout (§1 explicitly scopes those out
of the "exactly three templates" rule).

## Behaviour

**Login accepts a mobile number or an email.** `econur_authenticate_by_phone`
runs on `authenticate` at priority 30 — i.e. *after* core's username and email
checks — so it only handles what core could not resolve. It re-applies the
`wp_authenticate_user` gate, so blocked/spam accounts are still rejected.

**Registration is phone-first.** Mobile is required and is the login identifier;
email is optional. Three entry points, none of which gate a purchase:

1. `/my-account/` — a segmented Sign in / Create account switch, one panel
   visible at a time (the convention people expect, and the only sane layout on a
   phone). The active panel comes from `?econ_view=login|register` rendered
   server-side, so the tabs work as ordinary links with JavaScript off;
   `account.js` only upgrades them to an instant toggle. A failed registration
   reopens on the register panel rather than dumping the customer back on sign-in
   with an error they can't see. The param is namespaced rather than the generic
   `action`, which WordPress and plugins both reach for.
2. A checkout opt-in checkbox (native WooCommerce, unchecked by default).
3. A one-tap claim on the order-received page for guests who just bought.

**Guest checkout is untouched.** The forced-`false`
`woocommerce_checkout_registration_required` filter in `inc/woocommerce.php` still
stands. Nothing added here blocks buying.

## Decisions worth knowing about

**Phone-only accounts get a synthesised email.** WooCommerce cannot create a
customer without an email, and `WC_Form_Handler::process_registration` reads
`$_POST['email']` at the top of the request and hard-fails on an empty value —
there is no later filter that can supply one. So when a customer registers with a
mobile and no email, we substitute `01XXXXXXXXX@phone.invalid` on `wp_loaded`
priority 19, just before WooCommerce's handler at 20. `.invalid` is reserved by
RFC 2606 and can never resolve.

The same substitution is needed at checkout, where
`WC_Checkout::process_customer()` hands `billing_email` straight to
`wc_create_new_customer()`. There we inject via `woocommerce_checkout_posted_data`
(only when the opt-in box is ticked) and strip the synthesised address back off
the order in `woocommerce_checkout_create_order`, so the order record itself stays
email-less rather than showing a fake address in wp-admin and the CRM.

Consequences, all handled:

- Mail addressed only to a `.invalid` address is dropped in `pre_wp_mail`, so
  WooCommerce's new-account and order emails don't queue permanent bounces.
- Password reset is blocked for these accounts via `allow_password_reset`, with a
  message pointing at WhatsApp — better than a "check your email" screen that
  never pays off.
- The My Account dashboard nudges these customers to add a real email. Doing so
  clears the `econur_placeholder_email` flag and switches reset back on.

**Forced WooCommerce options.** Registration on My Account, signup from checkout,
username generation (on) and password generation (off) are forced at the front end
only — wp-admin still reads the real stored value, so the Accounts settings screen
doesn't look broken. This mirrors the existing guest-checkout pattern in
`inc/woocommerce.php`.

**Only the order-claim flow adopts an existing order.** Possession of the order
key proves the person placed that order, so that flow attaches the order to the
new account. Plain registration deliberately does *not* sweep up matching guest
orders: a phone number typed into a signup form proves nothing, and past orders
carry the customer's home address.

**One phone, one account.** `econur_phone_login` is the canonical normalised
lookup key, kept in sync with `billing_phone` by hooking the meta write (so
checkout, the address book and admin edits are all covered). A number already
owned by another account is never re-pointed, and registration rejects duplicates.
`econur_get_user_by_phone` falls back to a raw `billing_phone` match so accounts
that predate this module still resolve.

## CRM plugin

No changes needed. Registration writes `econur_special_date` /
`econur_special_date_label` and fires `econur_special_date_saved`, which is the
same hook `inc/account.php` already fires — so `Econur_CRM_Reminders` builds the
annual reminder on signup exactly as it does on an account edit. The WhatsApp
number defaults to the mobile, so `econur_whatsapp_number` is populated from the
moment the account exists.

## Phone ownership verification — deliberately not built

Signup takes a mobile number at face value. There is no OTP and no confirmation
step: a customer types a number and the account is created.

This was scoped out on purpose, not overlooked. It was built once and removed as
unnecessary overhead for where the business is now — every practical route cost
either money or staff time:

- **Firebase phone auth** bills per SMS and has required a billing account since
  September 2024 (only ~10 SMS/day unbilled).
- **Firebase Phone Number Verification**, the newer carrier/SIM-based product with
  no per-message fee, does not cover Bangladesh and is a device SDK, not something
  a mobile web store can call.
- **Local BD gateways** are cheap — around ৳0.30 per SMS — but need an account, a
  sender ID and BTRC clearance.
- **WhatsApp**, sent programmatically, needs the approval-gated Business Cloud API
  — the same wall §7.1 already documented.

### What this means in practice

A mobile number is *claimed*, not *proven*. Duplicate numbers are still blocked at
registration, so one number still means one account, and the number still drives
login and the CRM's reminder contact. What's missing is any guarantee that the
person who typed it owns it.

That is a reasonable trade while the CRM's WhatsApp step is staff-operated: a
wrong number surfaces as a bounced conversation the first time staff message it.
It would be worth revisiting if reminders ever go fully automated, or if accounts
start carrying credit, discounts or loyalty balances — at that point a bad number
costs real money rather than one wasted message.

## Profile page

The profile is **not** a fourth page template. My Account is a WooCommerce-managed
page in the same bracket as cart and checkout, which §1 explicitly scopes out of
the "exactly three templates" rule — so it's an override of
`woocommerce/myaccount/dashboard.php`. Template count stays at three, and
WooCommerce's own routing, endpoints and permissions keep working untouched.

It shows: greeting, offers, number confirmation, recent orders with a one-tap
**Order again** (WooCommerce's native `order_again`, which refills the cart), and a
details summary linking to the edit screens.

"Offers for you" needed the value ladder to return *every* qualifying rule rather
than just the first, so `recommend_all_for_user()` was added alongside the existing
`recommend_for_user()`, which now delegates to it. Duplicate products across rules
are collapsed. When the full offers list renders, the older single "Picked for you"
card stands down so the page doesn't say the same thing twice.

The `econ-btn--order` treatment is *not* used on the WhatsApp or Create-account
buttons — that gradient stays reserved for the purchase action, per the client
rule noted in `components.css`. "Order again" does use it, since that genuinely is
a purchase action.

## Still open

- **Phone numbers are unverified** — see the section above for what that costs and
  when it would be worth revisiting.
- **Rate limiting** on the login form is whatever WordPress and the host provide.
  Worth a look at deployment, since phone numbers are a smaller guessing space
  than email addresses.

Sources for the verification cost research:
[Firebase Auth pricing](https://blog.logto.io/firebase-authentication-pricing),
[Firebase PNV pricing & carriers](https://firebase.google.com/docs/phone-number-verification/pricing),
[sms.bd](https://sms.bd/), [BD Bulk SMS](https://bdbulksms.com/).

## Test checklist

- [ ] Register with mobile + password, no email → account created, username is the
      phone, no bounce in the mail log.
- [ ] Register with mobile + real email → normal WooCommerce account, reset works.
- [ ] Sign in with the mobile number; sign in with the email. Both land on My Account.
- [ ] Register a mobile that already exists → rejected with a link to sign in.
- [ ] Check out as a guest, then claim the account on the order-received page →
      signed in, and the order appears under Orders.
- [ ] Check out with the "save my details" box ticked → account created, phone and
      address prefilled on the next checkout.
- [ ] Check out as a guest without ticking anything → completes with no account,
      no prompt blocking anything.
- [ ] Set a special date during registration → reminder row appears in Econur CRM.
- [ ] Lost password on a phone-only account → clear message, not a dead end.
