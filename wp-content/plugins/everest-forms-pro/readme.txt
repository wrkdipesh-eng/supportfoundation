=== Everest Forms (Pro) ===
Contributors: WPEverest
Tags: Everest Forms, addon, features
Requires at least: 5.5
Tested up to: 7.0
Requires PHP: 7.2
Stable tag: 1.9.17
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Everest Forms features and extensions controller.

= Support Policy =

We will happily patch any confirmed bugs with this plugin, however, we will not offer support for:

1. Customisations of this plugin or any plugins it relies upon
2. Conflicts with "premium" themes from ThemeForest and similar marketplaces (due to bad practice and not being readily available to test)
3. CSS Styling (this is customisation work)

If you need help with customisation you will need to find and hire a developer capable of making the changes.

== Installation ==

To install this plugin, please follow the [instructions on plugin installation](https://wordpress.org/support/article/managing-plugins/#manual-plugin-installation) given in this article.

== Changelog ==

= 1.9.17           - 09-06-2026 =
* Feature     	   - Introduced webhook support to update payment status.
* Feature     	   - Added trial period and expiry support for subscriptions in the payment gateway.
* Feature     	   - Subscription plan field now supports Authorize.Net, Mollie, Square, and Razorpay payment gateways.
* Feature     	   - Introduced Payment Gateway field with all gateway mappings configurable directly within the field.
* Enhance     	   - Improved UI for the Subscription plan field.
* Fix			   - Update tooltips.
* Fix 			   - Add feedback button reappearing on quiz settings.
* Fix 			   - Form data sync to active campaign contacts and lists.
* Fix 			   - Ship missing EverestForms icon font files to resolve 404s.
* Fix              - Quantity Field Accepts 0, Causing Incorrect Total Calculation.
* Fix 			   - List of the all modules in the integration tab if not installed.
* Fix			   - Calculation field value not saved when readonly visibility is enabled.
* Fix 			   - Fatal Error When Duplicate Custom Username Is Submitted During Registration.
* Added 		   - Payments overview page inside the payments menu.

= 1.9.16           - 29-04-2026 =
* Feature 		   - Added Payment Summary field.
* Feature 		   - Added coupon usage limitation.
* Feature 		   - Enabled stackable coupons.
* Feature 		   - Added Payment History block and shortcode.
* Feature 		   - Added bulk actions in payment logs.
* Feature 		   - Added payment gateway filter in payment logs.
* Enhance 		   - Performance Optimization.
* Enhance 		   - Moved payment logs to the Dashboard submenu.
* Fix 	           - User id not updating on entry when auto login.
* Fix 			   - Payment checkbox value not displaying in entry page.
* Fix 			   - Fixed issue where coupons didn’t work with 4-digit codes.

= 1.9.15           - 16-04-2026 =
* Fix 			   - Parsing issue on entry table and view entry.

= 1.9.14           - 25-03-2026 =
* Fix - Missing plugin update notice issue.

= 1.9.13           - 24-03-2026 =
* Refactor 		   - Everest Forms Global Settings.
* Enhancement      - Form Builder.
* Enhancement      - Analytics report.
* Fix 			   - Security issue.
* Fix              - Notice Consistency.
* Fix     	       - Multiple license hit.
* Fix              - Handle array field values in conditional logic to prevent TypeError.
* Fix              - Notification for entry approval and denial.
* Fix              - Validation message for the file uploads in edit entry.
* Fix              - Color field not editable in the entry.
* Fix              - Notification for entry approval and denial.
* Fix              - License API issue causing excessive requests.
* Fix              - Validation message for the file uploads in edit entry.
* Fix              - Handle array field values in conditional logic to prevent TypeError.
* Fix              - Integration and payment settings not appearing on initial load in individual form settings.

= 1.9.12			- 19-02-2026 =
* Fix               - Fixed license deactivation issue when deactivating an add-on.
* Fix               - Fixed issue with coupon creation when using fixed amount for Euro currency.
* Fix               - Improved file validation in admin edit page.
* Removed           - Removed Premium sidebar from Global Settings.

= 1.9.11			- 26-01-2026 =
* Enhance 			- Submenu rearranged and added common header for some pages.
* Fix 				- Email not sent while using conditional logic according square payment.
* Dev 				- Added filters for form save and continue error messages.
* Refactor 			- Prevent duplicate Stripe customers for multiple form submissions.
* Fix 				- Customer creation process handled in one time payment.

= 1.9.10			- 24-09-2025 =
* Fix				- License activatation issue.

= 1.9.9				- 24-09-2025 =
* Fix				- Conditional logic not working.

= 1.9.8				- 19-09-2025 =
* Tweak				- Added option to create leads in Pipedrive without requiring an organization.
* Fix				- Amount display issue in entry table when currency is in pound.
* Fix				- Deserialization issue on Signature field.
* Fix 				- Dequeued unnecessary JS when related fields are absent.
* Fix				- Improved validation by treating the Payment field as a string in conditional logic.

= 1.9.7         	- 28-07-2025 =
* Fix 				- Hidden required field validation on conditional logic.

= 1.9.6         	- 08-07-2025 =
* Enhance 			- Global setting for paypal in settings payment.
* Enhance 	  		- Form confirmation redirection and previews after submission.
* Enhance 			- Edit field file upload, image upload and signature from both frontend listing and admin.
* Dev 				- Compatibility for delete files.
* Fix 				- Display square payment credit card while using live key.
* Fix 				- Email conditional logic while using auto populate on multiple choice option.

= 1.9.5         	- 23-06-2025 =
* Fix     			- Improved backend handling of file deletion processes.

= 1.9.4         	- 05-06-2025 =
* Tweak 			- Global settings url on form builder payment tab.
* Fix 				- PayPal notice was displayed even when PayPal was not enabled.
* Fix 				- Radio button selection not working while using special character in radio label.
* Fix 				- Array to string conversion issue.

= 1.9.3         	- 12-05-2025 =
* Enhance 			- Popup form option and customization.
* Enhance 			- Conditional Logic support on the basis of time format.
* Dev 				- Edit meta key options on Builder.
* Fix 				- Conditional logic for the multi part form.
* Fix				- Added full Country name in ActiveCampaign.
* Fix 				- Square payment issue when amount is large.

= 1.9.2         	- 01-04-2025 =
* Enhance			- Date time field for the conditional logic.
* Enhance 			- Form landing page builder settings design.
* Enhance 			- Default message template for slack and telegram.
* Enhance 			- Option to hide/show everest forms branding on Landing page.
* Tweak 			- Display entry id in the entries list view.
* Fix 				- Double opt option not showing in Brevo.
* Fix 				- Error message misplaced on coupons creation.
* Fix 				- Everest Forms icon on WooCommerce product tab.
* Fix 				- Conditional logic issue in total payment field.
* Fix 				- Error on Everest Forms while using user registration module.

= 1.9.1         	- 05-03-2025 =
* Enhance 			- Activation in module flow.
* Fix 				- Design issue in coupon page header.
* Fix 				- Form builder payment settings issue.

= 1.8.1         	- 24-01-2025 =
* Feature 			- Subscription plan field with trail period.
* Enhance	 		- Footer in PDF.
* Fix 				- Media file conflict issue.
* Fix               - Active campaign custom field issue.

= 1.8.0         	- 08-01-2025 =
* Fix 			- Stripe not working live key issue.
* Fix 			- Issues on Brevo integration design.
* Fix 			- ACF checkbox value not shwoing issue.
* Fix 			- PDF name customization in google drive.

= 1.7.9         - 03-12-2024 =
* Feature       - amoCRM Module.
* Feature   	- QR Generator module.
* Feature       - Get gist integration.
* Feature       - ClickSend Integration.
* Feature       - License settings page.
* Feature       - CleverReach Integration.
* Feature       - Slack Integration Module.
* Feature       - Generate Bulk Coupon Code.
* Feature 		- User Registration Social Login.
* Tweak         - Overall Feedback Design.
* Tweak         - Active campaign into module.
* Tweak         - SMS notification into module.
* Tweak         - Campaign Monitor moved to module.
* Tweak         - Track module activation in TG User Tracking.
* Fix 			- Tooltips does not work on mobile.
* Fix           - Background color for PDF submission.
* Fix           - Data mapping issue in Google Sheets.
* Fix 			- Calculation module decimal issue in form builder.
* Fix           - Critical issue when the publishable key is empty in live mode for recurring payment.


= 1.7.8.1       - 23-09-2024 =
* Fix 			- Calculation failed for big form.
* Fix     		- Addons update notification not being displayed.


= 1.7.8         - 16-09-2024 =
* Feature       - Apilog table.
* Feature       - Aweber integration.
* Enhancement   - Multiple webhooks.
* Enhancement   - Calculation Module.
* Tweak         - Hidden field editable.
* Fix           - DateTime Class not found in mailchimp.

= 1.7.7.1       - 20-08-2024 =
* Tweak         - Update user details if email already exists in brevo.
* Fix           - Dropbox expire token issue.
* Fix           - Fatal error when connecting mailerlite.
* Fix           - Unable to select the hidden group options.

= 1.7.7         - 07-08-2024 =
* Feature       - Landing Page.
* Feature       - Airtable Integration.
* Feature       - Salesflare into Module.
* Feature       - Mollie Payment Integration.
* Feature       - Square Payment Integration.
* Feature       - Transaction table for Payment.
* Feature       - Telegram Integration compatibility.
* Feature       - Delete option in Header logo in PDF Submission.
* Enhancement   - Pdf file name customization.
* Tweak         - ConvertKit into module.
* Tweak         - Drip module compatibility.
* Tweak         - Brevo module compatibility.
* Tweak         - PipeDrive module compatibility.
* Tweak         - GetResponse module compatibility.
* Tweak         - ConstantContact module compatibility.
* Tweak         - Mailerlite and MailChimp module compatibility.
* Tweak         - GetResponse module compatibility.

= 1.7.6         - 03-07-2024 =
* Tweak         - Plugin Updater.

= 1.7.5         - 23-05-2024 =
* Feature 		- Trello.​
* Feature 		– Moosend.​
* ​Feature		 - iContact.​
* Feature 		– MailPoet​.
* Feature 		- OnePageCRM.​
* Feature 		– WooCommerce.​
* Feature 		- Google Sheets integration for repeater fields .
* Enhancement  	- Global setting design.
* Dev     		- Move everest form pro fields into free.​
* Fix     		- Vendor conflict issue.​
* Fix     		- Amount format issue with euro.​
* Fix     		- Dynamic properties issue with PHP.​
* Fix 			- Country not showing in redirection.
* Fix     		- Stripe payment showing error when the price is more than 1000. ​

= 1.7.4         - 04-04-2024 =
* Feature 		- Admin approval entries.

= 1.7.3         - 12-03-2024 =
* Fix 			- File uploader not working on Elementor pop-Ups.
* Fix 			- Vendor conflict with our other addons.

= 1.7.2         - 22-02-2024 =
* Feature       - Google calendar integration.
* Fix           - Required issue with Address field when conditional logic applied.
* Fix           - Email template not working when payment, after completed conditional logic is enabled.
* Fix           - Lookup field is not showing in mapping area of integration.
* Fix           - Deprecation of dynamic properties in Everest Forms PRO in PHP 8.2 Version.
* Fix           - Decimal number is not working with sub total field.

= 1.7.1         - 26-12-2023 =
* Fix 			- Fatal error on form submission when enabling email attachments and disabling entry storage.
* Fix 		    - Double Email Issue in Payment Conditional Logic.
* Fix 			- Section Title Appears in PDF Despite Being Hidden via Conditional logic.

= 1.7.0         - 05-10-2023 =
* Refactor      - Form Builder Design.
* Enhancement   - Changed checkbox option to toggle.
* Fix           - Conditional Logic Not Working for Total field. (Less than and greater than).
* Fix		    - Critical issue with root sage theme.

= 1.6.9         - 06-09-2023 =
* Fix  	        - PDF is empty when we enable attach form data in PDF.
* Fix			- Phone field not showing issue.
* Fix 			- Notice issue when the server is not apache.

= 1.6.7.1       - 20-07-2023 =
* Fix           - Release bug.

= 1.6.7         - 19-07-2023 =
* Enhancement   - Auto-install and activate Everest Forms when clicking the link in the notice.
* Fix 			- Submit button not working with text area when conditionally hidden in repeater fields.
* Fix 			- Repeater Row, the Address field shows 'required' error when conditional logic is enabled.

= 1.6.6         - 14-06-2023 =
* Enhancement   - Upgrade to the pro improve for dragging a field
* Fix 			- Conditional Logic not Empty Rule not working with Multiple Choice and Checkbox Fields.
* Fix 			- Critical error with WP Project Manager plugin.
* Fix 			- Coupon code issue with fixed amount discount.
* Fix 			- Schedule entry delete issue.

= 1.6.5       - 08-05-2023 =
* Fix 		  - Form not showing to export entries while the storing entry information is disabled.
* Fix 		  - When Conditional Logic is enabled, the ReadOnly Visibility field does not work.
* Fix 		  - Two copies of the attachments sent in the email issue.
* Fix 		  - Critical issue while submitting the form when the Google listing ads plugin activated.
* Fix		  - Unnecessary JS Load on Frontend.
* Fix 		  - Conditionally hidden field value removed from entry listing.
* Fix         - Postal code showing empty when the other address field is hidden.
* Enhancement - Search forms in entries select.
* Enhancement - Send CSV in email.
* Enhancement - Smart Tag Support for HTML Field.
* Enhancement - Serial number on entry listing table.
* Feature     - Certain IPs and Email Black List.

= 1.6.4       - 28-03-2023 =
* Fix 		  - Signature field not showing in Email.
* Fix 		  - PayPal issue with repeater fields.
* Fix 		  - Design issue with WYSIWYG in elementor.
* Fix         - Choice field options do not update dynamically in the conditional logic input field.
* Fix         - Conditional logic not working on the section title.

= 1.6.3       - 30-01-2023 =
* Enhancement - Whitelist domain options.

= 1.6.2       - 21-12-2022 =
* Fix 		  - Dropbox file upload issue.
* Fix 		  - Correct State value not showing in email and PDF.

= 1.6.1       - 17-11-2022 =
* Fix 		  - Google Drive Integration issue.
* Fix	      - State value not showing correctly in entries with default country
* Enhancement - Added filter for sending form data to the custom URL in the webhook.
* Feature 	  - Subtotal Field.
* Feature     - Color Field.

= 1.6.0 - 18-10-2022 =
* Fix - Send file as Attachment issue.
* Refactor - Multiple select2 code in the admin area.
* Enhancement - Unique ID smart tag.
* Feature - Print an individual entry.

= 1.5.9.2 - 27-09-2022 =
* Fix - Progress field order issue.

= 1.5.9.1 - 27-09-2022 =
* Fix - State field required even if value filled.

= 1.5.9 - 26-09-2022 =
* Enhancement - Dynamic country wise state dropdown.
* Enhancement - OAuth in HubSpot
* Feature - Reset Field.
* Feature - Progress field.
* Refactor - Multi-select value code in the builder.

= 1.5.8 - 31-08-2022 =
* Enhancement - File validation check while file is not uploaded in the server.
* Enhancement - Required field validation(Global setting, Individual setting).
* Dev - File name filter.

= 1.5.7 - 02-08-2022 =
* Fix - Field required with conditional logic.
* Fix - Row/Part Conditional logic with required fields.
* Fix - Required issue when map is selected in address field of advanced option.
* Fix - Conditional redirection multiple logic not saving.
* Enhancement - File Uploads and Signature fields support for repeater.

= 1.5.6 - 19-07-2022 =
* Enhancement - Flatpickr on date range field of export entries.
* Enhancement - Row setting for conditional logic.
* Fix - Validate as unique issue when the field value is empty.
* Fix - UI glitch on conditional match for dropdown field with multiple selections.

= 1.5.5 - 06-07-2022 =
* Fix - Field validation params miss match.

= 1.5.4 - 06-07-2022 =
* Enhancement - PDF send to Google Drive or Dropbox.
* Enhancement - Select/Unselect on Select2.
* Fix - Dynamic mapping issue in form builder.
* Fix - Conditional logic field required issue.
* Fix - Range Slider field min/max value glitch and default value validation.
* Fix - Toggle PayPal and Stripe recurring subscription payments glitch.
* Fix - Toggle Enable PayPal Standard.


= 1.5.3 - 15-06-2022 =
* Fix - Select-box field value not displayed in builder integration custom fields.
* Feature - Conditional logic added in Multipart Form.
* Feature - Form field icons added.
* Feature - Advance Export Entries.

= 1.5.2 - 19-05-2022 =
* Feature - Validate as Unique.
* Feature - Keyboard Friendly Forms.

= 1.5.1 - 20-04-2022 =
* Tweak - Colon removed from field label in PDF.
* Fix - Signature field not working issue in twenty twenty two theme.
* Fix - Likert and Rating fields not working issue in Save and Continue.
* Fix - Undefined index 'enable_save_and_continue' issue.
* Fix - Undefined index 'pdf_enabled' issue.
* Fix - Required issue in Conditional logic when the field is hidden.
* Fix - Conditional logic no working in Checkbox, Radio as payement items.
* Enhancement - File upload message option added.
* Enhancement - Payment fields support in repeater field for stripe.
* Feature - Webhook (Send form data to custom URL).

= 1.5.0 - 29-03-2022 =
* Fix - Range slider not showing issue in entries and PDF.
* Enhancement - Select/Unselect All options in dropdown field(multi-selection).
* Enhancement - Exclude Some Countries in Country Field.
* Feature - Field Visibility.
* Feature - Popup Forms.


= 1.4.8 - 01-03-2022 =
* Fix - Entries sorting URL glitch.
* Feature - WYSIWYG field.
* Fix- Undefined Index issue in Whitelist domain.
* Enhancement - Smart Tag for default value of Hidden Field

= 1.4.7 - 03-02-2022 =
* Enhancement - AMP plugin compatibility.
* Enhancement - Form builder validation.
* Enhancement - lost password in login form.
* Feature - Whitelisted Domain.
* Feature - Entries table sorting based on column value.
* Fix - Google Drive Permission Issue.
* Fix - Quantity field issue with single item.
* Fix - Enqueuing Styles and Scripts issue.
* Fix - Payment Redirection issue conditional logic.

= 1.4.6 - 06-01-2022 =
* Enhancement - Integration setting for google sheets.

= 1.4.5 - 27-12-2021 =
* Enhancement - File send as email attachment.
* Enhancement - No Duplication option added to email field.
* Enhancement - Auto delete entries older than certain time.
* Enhancement - Conditional logic conditional redirection.
* Fix - Number of icons empty issue.
* Fix - Checkbox-Radio style customization issue.
* Fix - Everest forms caching issue in Forms and Entries.
* Fix - file preview max count glitches.

= 1.4.4 - 17-11-2021 =
* Enhancement - Column adjustment in entries table.

= 1.4.3 - 26-10-2021 =
* Enhancement - Conditional logic added for form submission redirection.
* Fix - PayPal total invalid payament issue.

= 1.4.2 - 16-09-2021 =
* Fix - Load scripts for Signature field conditionally.
* Fix - Reset signatures when the viewport size is changed.
* Fix - Signature field not working when they are conditionally hidden.
* Fix - Signature field not working in multi-part form with multiple parts.
* Fix - Console log JQMIGRATE: `jQuery.fn.removeAttr` no longer sets boolean properties.
* Fix - Default Value for Payment Quantity Field.

= 1.4.1 - 28-07-2021 =
* Enhancement - Improve performance of dashboard analytics data.
* Enhancement - Prevent duplicate SQL queries to improve performance.
* Fix - Signature field not working with conditionally enabled.
* Tweak - Password validation at back-end.

= 1.4.0 - 17-06-2021 =
* Feature - Integration with MemberPress and User Role Editor plugins to control capabilities.
* Feature - Added divider field.
* Enhancement - Stripe Recurring Payment with custom interval.
* Enhancement - Required Indicators for Field Type.
* Tweak - Change lost license key link.
* Fix - File upload with unspecified extensions in WordPress.
* Fix - Signature field image generation issue with PDF file.
* Fix - Survey Polls settings checkbox glitches.
* Fix - Dashboard Analytics issues for Graphs and Entries.
* Fix - Delete uploaded files after removing Forms or Entries.

= 1.3.9 - 11-03-2021 =
* Fix - jQuery deprecated shorthands shown by jQuery Migrate.
* Fix - Incorrect `wp_localize_script()` usage for conditional logic.

= 1.3.8 - 26-01-2021 =
* Fix - Introduce Japanese Yen currency support.
* Fix - PayPal redirecting issue when Conditional Logic enabled.
* Fix - Fatal error occurred when Everest Forms core is deactivated.

= 1.3.7.2 - 13-12-2020 =
* Fix - PHP error while authenticating Google Drive.
* Fix - Conditional logic preventing redirect to payment gateway.

= 1.3.7.1 - 04-11-2020 =
* Fix - Plugin activation warning on PHP version below 7.1.3.

= 1.3.7 - 16-10-2020 =
* Feature - File Upload to external Google Drive and Dropbox services.
* Enhancement - Add supports for Campaign Monitor Addon.
* Fix - Country field default value selection.
* Fix - Country selection throwing `[object Object]`.
* Fix - Signature field not working in multi-part form.
* Fix - Dashboard Analytics JS error thrown on editable entries.

= 1.3.6 - 23-09-2020 =
* Enhancement - Add supports for email Templates Addon.
* Enhancement - Add supports for Google Sheets integration Addon.
* Fix - The Zip/Postal code is replaced by Address 1 in Email.
* Fix - In CSV export, for Signature field, the text "signature" comes as a value instead of the link.
* Fix - Signature image is missing in exported PDF and email attachment.
* Fix - Not Supported File type not throwing errors while uploading the file
* Fix - Conditional logic issue with payment quantity field.
* Fix - Conditional Logic issue with checkbox hidden.
* Fix - Conditional logic for dropdown broken.

= 1.3.5.2 - 11-08-2020 =
* Fix - Signature being cleared when scrolling in mobile.
* Fix - Error message not showing on frontend file upload.
* Tweak - WP 5.5 Compatibility.

= 1.3.5.1 - 29-07-2020 =
* Fix - Raw serialized data fetched on CSV Export.
* Fix - Privacy field formatting data without type check.

= 1.3.5 - 15-07-2020 =
* Feature - Entries analytics dashboard.
* Feature - MailerLite Addon support.
* Feature - Active Campaign Addon support.
* Feature - Form Restriction Addon support.
* Feature - Addded new Privacy Policy field.
* Feature - Export single entry with quiz report.
* Feature - Attach quiz report in email (in pdf format).
* Feature - Added recurring (subscription) payment support.
* Feature - Added Strong Customer Authentiation (SCA) support.
* Fix - Conditional logic while comma on field options.
* Fix - Next row ID being NaN which results in field override.
* Fix - Payment Single field calculation with different currencies.
* Fix - Signature field image link on email.
* Fix - Survey report while using Likert field.
* Fix - Phone field should not allow alphabet input but allow spaces.
* Tweak - Show total unread entries count in the main entries menu.
* Tweak - Improvement of phone field design and RTL is now supported.

= 1.3.4.1 - 19-05-2020 =
* Fix - Conditional value not showing.
* Fix - Conditional bug on the submit button while applying condition on field.

= 1.3.4 - 18-05-2020 =
* Fix - Conditional Logic block on Email, Submit Button and Payment.
* Fix - Z-index issue on intlTelInput country list popup with HighendWP theme.
* Fix - Address field preventing form submission when conditional logic is enabled.

= 1.3.3 - 17-05-2020 =
* Feature - Added a new Pro field Range Slider
* Fix - PHP 7.4 Undefined index notices in conditional logic checks.
* Fix - Translation issue plus utilize wp_kses_post() to escape html.
* Tweak - Updated intlTelInput JS library to v17.0.0.

= 1.3.2 - 01-05-2020 =
* Enhancement - Add support to country flags option.
* Fix - Multipart progress bar not responding in IE bug.
* Fix - Total amount calculation with different currencies.
* Fix - Payment field calculation with different currencies.
* Fix - Payment fields not working properly with conditional logic.
* Tweak - PHPCS fixes.
* Tweak - WPML compatibility for premium fields.
* Tweak - Field exporter compatiable with PDF submission addon.

= 1.3.1 - 06-04-2020 =
* Fix - Localization for file upload.
* Fix - Links not shown clickable in emails.
* Fix - Selective html tag support in field label.
* Fix - Logic implemented to AJAX validate during file upload.

= 1.3.0 - 10-02-2020 =
* Feature - Multiple File and Image Upload.
* Feature - Address field's sub labels made editable.
* Feature - Added password strength meter to password field.
* Feature - Added password visibility toggle to password field.
* Feature - Mark as read/unread and starred actions for entries.
* Feature - Entry actions to Resend notification, Export to CSV, etc.
* Enhancement - Improve payment choice field with image support.
* Enhancement - Robust the layout for displaying field choice i.e inline or {one|two|three} columns.
* Fix - Real-time updates for Single Item field.
* Fix - Restrict and format user money input field.
* Fix - Single Item currency symbol bug on first load.
* Fix - Choice default value not rendered on frontend.
* Fix - Credit card field should default to being required.
* Fix - Load `jquery-intl-tel-input` library if phone field format is smart.
* Fix - Hide empty serialized data field like file and image upload in entry.
* Fix - If not payment form hide status column in entries list table accordingly.
* Tweak - Gutenberg block preview styles.
* Tweak - Sortable design issue and smooth payment choices.
* Tweak - Set {10|20|30} as default amount for payment choice field.

= 1.2.10 - 11-12-2019 =
* Feature - MailChimp groups supported.

= 1.2.9 - 18-11-2019 =
* Enhancement - Smart format for Phone fields.
* Enhancement - Conditional logic operators.
* Fix - WordPress 5.3 UI compatibility bugs.

= 1.2.8 - 06-11-2019 =
* Fix - File upload and Signature field compatible with TCPDF.

= 1.2.7 - 19-09-2019 =
* Fix - Design isuue on IE browser.
* Fix - Conditional logic on payment Single item field.
* Fix - Removed negative amount on payment field.
* Fix - Total amount while payment single item field is set to user defined.
* Fix - Calculation of total amount if the payment field is conditionally hidden.
* Fix - Payment single item while required on field type hidden and pre defined.
* Fix - Hidden field on Entries.
* Fix - Conditional logic bug fixes.

= 1.2.6 - 16-07-2019 =
* Feature - Add support for Custom Captcha addon.
* Feature - Add support for User Registration addon.
* Feature - Add support for User Post Submissions.
* Fix - MailChimp no list error.
* Fix - Email issue with checkbox.
* Fix - Likert field list option design.
* Fix - Country code returning its full name in all fields.
* Fix - Conditional Logic error on Payment and email section.
* Fix - Fatal error while form ID not valid on conditional logic.
* Tweak - Logo images for payment tab.
* Tweak - Conditional logic box design style.
* Tweak - Global validation required message included for signature field.

= 1.2.5 - 04-06-2019 =
* Fix - Submit button visibility issue on multi-part.
* Fix - Undefined index due to incorrect isset check.

= 1.2.4 - 28-05-2019 =
* Feature - Introduced payments conditional logic.
* Feature - Introduced Credit card field for stripe.
* Feature - Introduced submit button conditional logic.
* Fix - Conditional logic for form fields.
* Fix - Issue with conditional logic first child.
* Fix - Introduce multiple signature field support.
* Fix - Freezing signature field in multi-part form.
* Tweaks - Design improment and SASS refactor.

= 1.2.3 - 02-05-2019 =
* Feature - Add support for Zapier addon.
* Fix - Conditional Logic issue on Payment multiple choice field.

= 1.2.2 - 04-04-2019 =
* Feature - Introduced Signature and Payment Charge Field.
* Fix - Validation for number of ratings.
* Fix - Undefined `wpColorPicker` in console.
* Fix - Removed placeholder setting from payment multiple choice and checkboxes.

= 1.2.1 - 25-03-2019 =
* Fix - Tooltip js issue.

= 1.2.0 - 22-03-2019 =
* Feature - Add support for Style Customizer addon.
* Feature - Add support for Survey, Polls and Quiz addon.

= 1.1.8 - 13-03-2019 =
* Fix - Removed unnecessary `primary-input` class.
* Fix - Removed extra placeholder in address field.
* Fix - Validate uploader class with conditionally hidden rules.

= 1.1.7 - 05-03-2019 =
* Fix - deleting field used conditionally.
* Fix - Conditionally hidden value submitted.
* Fix - Payment gateway and transaction id on single entry.
* Tweak - Validate message for WordPress not permitted file type.

= 1.1.6 - 31-01-2019 =
* Fix - Conditional logic operator rules not being saved.
* Fix - No required checked for conditionally hidden address field.
* Fix - Conditional logic show/hide rules for country and address fields.

= 1.1.5 - 21-01-2019 =
* Fix - Conditional Logic bug with Checkbox.

= 1.1.4 - 17-12-2018 =
* Fix - Conditional Logic bug with First Name and Last Name.
* Fix - Entry data from Paypal with checkbox Field
* Tweak - Backward Compatible with Everest Forms Core Multiple Email.

= 1.1.3 - 06-12-2018 =
* Tweak - Backward Compatible with Everest Forms Core Multiple Email.
* Tweak - Required version updated.

= 1.1.2 - 03-12-2018 =
* Tweak - Compatible with Everest Forms Core Multiple Email.
* Fix - Conditional Logic issue with similar field types.

= 1.1.1 - 05-11-2018 =
* Feautre - Introduced Conditional Logic For Email.
* Enhancement - Input mask added on Phone field.
* Fix - Escaping attribute for conditional logic.

= 1.1.0 - 09-10-2018 =
* Feautre - Introduced Conditional Logic.
* Fix - Meta key required for HTML and title field.

= 1.0.1 - 05-10-2018 =
* Fix - Integration issue on Js while enabling conditional Logic.
* Fix - Compatibility with lower php version.

= 1.0.0 - 10-09-2018 =
* Initial release.
