=== Freight Integration for Woocommerce to Logistra Cargonizer or Profrakt by WildRobot  ===
Contributors: robertosnap
Donate link: https://wildrobot.app
Tags: Cargonizer, Logistra, WildRobot, Profrakt, Frakt
Requires at least: 5.1
Tested up to: 6.6.2
Stable tag: 7.4.4
WC requires at least: 3.0.0
WC tested up to: 9.3.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Freight integration for Woocommerce to Logistra Cargonizer or Profrakt. Automatic freight administration and print shipping labels from Woocommerce. Requires API key from Logistra or Profrakt that you can get here: https://logistra.no/

[DOCUMENTATION](https://intercom.help/robots-will-take-over-the-world-as/nb)

== Description ==

Complete freight and transport management for Norwegian e-commerce.

Features

* Shipping tracking URL in customer emails.
* Simple setup.
* Pick from 85 transporters, among Bring, Postnord, HeltHjem, DHL etc.
* Direct from Woocommerce to Printer.
* Let customer choose pickup point.
* Custom shipping method with free delivery, weight control and freight estimation with price surcharge.
* Support delivery to organizations.
* Schedule label prints.
* Schedule data transfers to transporters.
* Set specific printers and print intervals for individual freight methods.
* Automatically create return labels.
* Print picklist on printer with [WooCommerce PDF Invoices & Packing Slips](https://nb.wordpress.org/plugins/woocommerce-pdf-invoices-packing-slips/)
* Override orders that require special handling.
* Bulk send orders.
* Bulk print orders.
* +++


== Installation ==

1. Upload plugin zip file thorugh the Wordpress admin interface.
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Go into Woocommerce -> Settings -> Logistra and input you Logistra API key.
1. Register billing information to receive a 14 day trail.
1. Start sending orders from Woocommerce to Logistra either from Orders list (rememver to show actions) or from within the edit-order screen.

== Changelog ==

= 7.4.4 =
* FIX Bug where package would not be created if quantity was 0.

= 7.4.3 =
* FIX Bug introduced in 7.4.2 where package gave an error when sending or overriding orders.


= 7.4.2 =
* FIX Bug where estimations would not count dimensions and weight from variations.

= 7.4.1 =
* FIX Bug where package fields were cleared when updating product.

= 7.4.0 =
* NEW: Added a "Kom igang" guide to help you get started with Wildrobot.

= 7.3.8 =
* FIX Remove booking request service from estimates.

= 7.3.7 =
* BUG Too few arguments to function display_notice_if_user_has_orders_not_responded

= 7.3.6 =
* NEW: Package settings are now dynamically displayed when updating the "Number of packages" field for a product.

= 7.3.5 =
* FIX Bug when estimating packages in checkout and in strict mode.

= 7.3.4 =
* New Added a print again url on the order notes.
* NEW Exclude a product from free freight calcualtion.
* UI Added some styling to structure product settings.

= 7.3.3 =
* FIX Not adding "egen pakke" to the order.

= 7.3.2 =
* NEW Added som services 

= 7.3.1 =
* NEW Added a service
* FIX Freight estimation with multiple packages/ "kolli" dident properly add the base package in freight estimations.

= 7.3.0 =
* FIX Properly parse new error messages.
* FIX Freight estimation with multiple packages/ "kolli" now calculates correctly.

= 7.2.3 =
* NEW When creating a delivery and if there is no response from freight vendor and the order is not delivered within an hour. The user will Then the user will see a notice in admin dashboard urging them to check it.

= 7.2.0 =
* NEW Possible to instruct a product to carry multiple "kolli" / packages into an order with configurable name, dimensions, and weight. Also possible to generate packages/"kollier" based on quantity,

= 7.1.0 =
* FIX Calcualte weight on bundled items, had wrong logic on how to count weight for container and disregard bundled item. Lead to double counting container item.

= 7.0.1 =
* FIX undefined array key "override_weight"

= 7.0.0 =
* Breaking: Added HPOS compatibility. For more information, visit the WooCommerce HPOS Documentation https://developer.woocommerce.com/docs/category/hpos/. This update ensures that your store can leverage WooCommerce's High-Performance Order Storage for improved performance and scalability.

= 6.24.1 =
* FIX Show "Utlevering ikke mulig." when no pickuppoints was fetched. This is for future competability with new transport services coming.

= 6.24.0 =
* CHANGE: Properly uses order ID and not order NUMBER when delivering orders. If you have special order numbers, IDs, or if these two are not the same, please make sure that creating deliveries is working as expected.
* FIX: Consignment attributes for order_id now use order_id and not order_number.
* FIX: Creating picklist now use order_id and not order_number. 


= 6.23.0 =
* FIX Not beeing able to delete first package in override when multiple packages.
* FIX Possible to update with 0 weight in override.

= 6.22.5 =
* FIX Showing return services for Profrakt.

= 6.22.4 =
* NEW Round estimates to nearest 10,9 and 1. New option introduced on the shipping method.

= 6.22.3 =
* NEW Possibility to have estimated cost include carriers fixed cost (Profrakt).
* NEW Round estimates to nearest 10.

= 6.22.2 =
* FIX Possible fix for creating multiple collies for products who dont have this checked.

= 6.22.1 =
* FIX Bug not beeing able to uncheck seperate package setting on product.

= 6.22.0 =
* BREAKING New package box estimation algorithm. It will now break up quantities and stack them on the smallest dimension.

= 6.21.0 =
* NEW Possible to mark product to generate seperate package when creating delivery. Possiblity to group products into same seperate package by transport description.

= 6.20.3 =
* FIX Profrakt print picklist
* CHAGE Smaller QR code on picklist

= 6.20.2 =
* Bump tested with.

= 6.20.1 =
* FIX Adjusting how to get order id in order details.

= 6.20.0 =
* FIX Adjusting how to get order id in order details.

= 6.19.0 =
* FIX Qr code generator.

= 6.18.4 =
* NEW Hook right before creating delivery, wildrobot_logistra_before_send_consignments with two arguments. First is the json object, second is the order id. 

= 6.18.3 =
* NEW Service parner request with freight product "postnord_mypack_small" now has some new features.

= 6.18.2 =
* FIX Changed add_meta_boxes_shop_order to add_meta_boxes because of changes in Woocommerce
* FIX Added fallback for bulk action dropdown admin bulk_actions-woocommerce_page_wc-orders.

= 6.18.1 =
* NEW Pick and delivery override now has send email to consignee option.

= 6.18.0 =
* NEW Possible to filter transportation products to show everywhere this option is presented.

= 6.17.8 =
* New Choose placement of tracking URL in order confirmation email.

= 6.17.7 =
* FIX Return label on fallback freight method.

= 6.17.5-6=
* NEW Added wildrobot_logistra_order_derlivery_created hook with args $order and $shipment

= 6.17.4 =
* BUG Possible bug when checking for customs_value

= 6.17.3 =
* BUG Render error when not finding delivery relation in delicery point picker.

= 6.17.2 =
* BUG: get_billing_postcode() had an mistype.

= 6.17.1 =
* FIX: Resolved an issue where the picklist page was utilizing an outdated option.

= 6.17.0 =
* NEW: Order automation is now available. Check it out in the Wildrobot settings.
* BREAKING: The checkout pickup point picker has been relocated to a separate row within the checkout form. The Woocommerce template has also been updated to the latest version, ensuring compatibility with Klarna iframe freight methods.
* FIX: Resolved an issue where 'undefined' was displayed when overriding an delivery for an order if no pickup point was selected but the freight product was changed to one with a pickup point.

= 6.16.1 =
* ADD Filter wildrobot-logistra-shipping-method-identifier-custom for headless solution.
* FIX Show address to sender select dropdown.

= 6.16.0 =
* BREAKING: Updated the freight cost estimation process to first apply tax and then round to determine the target price, resulting in a more accurate freight price.
* FIX: Enhanced handling of decimals in estimation responses for improved accuracy.
* CHANGE: Refreshed descriptions provided for freight estimation settings for better clarity.
* FIX Bug where invoice list navigate did not show.
* FIX Only use fallback delivery point for Postnord.
* FIX Better logging for service-partner request.

= 6.15.3 =
* NEW Choose to based freight method cost estimation on net (your discounted freight price) or gross (your non-discounted freight price).

= 6.15.0 =
* NEW Better feedback messages when delivering orders with bulk actions or delivering by updateing status.
* NEW Display freight method description when mapping to transporters freight method.

= 6.14.2 =
* BUG Fix labels

= 6.14.1 =
* BUG Fix decimal error when calculating weight on quantities in range 0-1

= 6.14.0 =
* BUG Fix in volume estimation on packages that needed dimension normalization.

= 6.13.3 =
* BUG Fix bug where picking list would not format correctly.

= 6.13.2 =
* Fix Bug when sending orders with certain weight values.

= 6.13.1 =
* FIX For variable product calculate dimensions.

= 6.13.0 =
* NEW Feed customer addresses to Pickup Point API for better nearest location selection.

= 6.12.1 =
* NEW Added more bundle weight and dimension comptability.

= 6.12.0 =
* NEW Possbility to set minimum and maximum dimension based on estimated total cart dimensions.
* FIX BUG With where a freight method had "Noen fraktklasser" and "Vektstyrt" would disregard weight requirements.
* FIX BUG where bundle weight was added to order weight.
* FIX Added more possibilities to combine exclude, require and some freightclasses on freight methods.

= 6.11.4 =
* FIX Dont calculate bundle dimensions. It will always take these from the individual products.

= 6.11.3 =
* FIX Bug where stacking logic for calculating package box dimesions did not run.

= 6.11.2 =
* FIX Override order bug with new box dimension calculatation.

= 6.11.1 =
* FIX Properly format data for delivery point picker.

= 6.11.0 =
* FIX When calculating package box dimensions, make sure dimensions are not swapped.

= 6.10.2 =
* FIX Fix order id in pick and delivery screen.

= 6.10.1 =
* NEW Min length, width and height settings for freight method.

= 6.10.0 =
* NEW Add description to freight methods that is not exposed to customer, useful when you need multiple freight methods with different settings. For example weight based.
* NEW Auto close override order window after sending the order option. Can be toggled inside override order windows. Settings save locally.

= 6.9.11 =
* FIX Possible bug where shipping code is set to Norway because a third part plugin is overriding country codes.

= 6.9.10 =
* FIX Bug formatting product description to transport description with unsupported characters.

= 6.9.9 =
* CHANGE Pickuppoint label in dropdowns more consistent representation.
* NEW Possible to change return address name.

= 6.9.8 =
* Change Changed download label option functionality so that if order has label it will show right away. Removing the other download label option as its redudant.

= 6.9.7 =
* CHANGE Menu name "Leverings innstillinger" renamed to "Wildrobot innstillinger" 
* CHANGE Menu name "Plukk & Levering" renamed to "Wildrobot Plukk & Lever" 

= 6.9.3-6 =
* FIX Changed labels

= 6.9.2 =
* NEW Possible to add freight label download button on order list. Configure this option under settings.
* NEW Possible to make send button into an download freight butter after successful delivery creation. Configure this option under settings.

= 6.9.1 =
* FIX Bug when estimating freight price, should not estimate freight price correctly.

= 6.9.0 =
* NEW Support Profrakt.
* NEW Added support for variable products when calculating order dimensions.
* CHANGE Many text labels have changed. Woocommerce settings tab is now named "Wildrobot fraktintegrasjon".
* FIX Bug with updating printers.
* FIX Support DEV and PROD configurations. See [article](https://intercom.help/robots-will-take-over-the-world-as/nb/articles/6556840-setup-test-enviroment)

= 6.8.3 =
* NEW Added multiple Bring services.

= 6.8.2 =
* FIX Compatability fix for plugin/theme that formats shipping country from code to name, we format it back to code for our purpos.

= 6.8.1 =
* FIX "Inline html" service point picker should not show if only fallback servicepoint is available.

= 6.8.0 =
* NEW Added support for EORI on DHL Electronic invoice (currency also added to this service)
* FIX Disregards bundle products on Eletronic invoice. 

= 6.7.7 =
* NEW - Added billing to fields on Eletronic Invoice service.
* NEW - Added Lithium or PI967-II services on eletronic invoice when set label information.

= 6.7.6 =
* FIX curl timeout

= 6.7.5 =
* FIX Calculate estimated shipping method cost and round to nearest 9 bug has been addressed and fixed.

= 6.7.4 =
* FIX Format error in pick and delivery view.
* FIX More loggin on rounding.

= 6.7.3 =
* FIX - Properly add discounts on subtotals for DHL Electronic invoices.
* FIX - Possible bug where missing weights would output unreadable error message.

= 6.7.2 =
* NEW - Filter wildrobot_logistra_service_partner_request where you can filter raw get service partner response.
* NEW - Option to filter out "Pakkeautomater" from beeing picked as service points (utleveringssteder).

= 6.7.1 =
* NEW - Added phone and mobile fields to return address used when creating return deliveries.

= 6.7.0 =
* BUG - Shipping methods inn "zones not covered by other zones" was not working.
* FIX - Table rate fix for sipping methods inn "zones not covered by other zones" .

= 6.6.3 =
* FIX Convert cm3 to dm3 before sending orders to Logistra.

= 6.6.2 =
* FIX Properly output information when an order is a return order.
* NEW Show indicator that a return is created on the order.

= 6.6.1 =
* NEW Added classed to pickuppoint picker

= 6.6.0 =
* FIX Increase performance and decrease bundle size.
* NEW Mark and send multiple orders from pick and delivery screen.

= 6.5.6 =
* FIX Possiblity to show delivery point picker with certain custem theme settings
* FIX Better dom selectors for delivery point picker.

= 6.5.5 =
* FIX Migration of services from older version.
* FIX Order action button alignment

= 6.5.4 =
* FIX Fixed a scenario where freight method estimates could not be parsed into prices.

= 6.5.3 =
* FIX Improved error message.

= 6.5.2 =
* FIX A bug where free freight notice would not show up if options was not migrated.

= 6.5.1 =
* FIX A bug where delivery point picker would not show if options was not migrated.

= 6.5.0 =
* FIX User printer did not properly override in some scenenarios. Now it should always override.

= 6.4.3 =
* FIX Traling commas error for certain PHP version.
* ADD If order has custom field shipping_email or shipping_phone it will now be used as delivery information.

= 6.4.2 =
* FIX File error.

= 6.4.1 =
* FIX Properly override dimensions and volume when overriding order even thought setting for this is turned off.

= 6.4.0 =
* FIX Regards discounts applied to cart when determining if cart has free shipping from the freight method.

= 6.3.3 =
* FIX Bug where send order was not added.

= 6.3.2 =
* FIX Bug where tracking URL was not added.

= 6.3.1 =
* FIX Printing multiple "varebrev split"

= 6.3.0 =
* FIX Where order id was used instead on order number on freight label. 
* FIX Added possiblity to book hovedsending with or without pickup.

= 6.2.5 = 
* FIX Added terms of delivery options to DHL Express economy select

= 6.2.4 = 
* FIX Added terms of delivery options to DHL Express economy select

= 6.2.3 = 
* FIX Added terms of delivery options to DHL Express economy select

= 6.2.2 = 
* FIX Added back booking varebrev hovedsending functionalty to override order.

= 6.2.1 = 
* FIX Bug with warnings labels on DHL shipments.

= 6.2.0 = 
* NEW Added support for Etterkrav (cash on delivery) with Posnord Mypack.

= 6.1.2 = 
* FIX Possible to override orders without shipping method.

= 6.1.1 = 
* FIX Removed multiple messages on estimate send order.

= 6.1.0 = 
* FIX Add back trim transport description to 200 chars.
* FIX Bug with decimals weight for DHL international freight.
* FIX Show estimated cost in order notes.
* NEW Added settings to hide send and override buttons on order detalins and order list. Find it in general settings.

= 6.0.2 =
* FIX User printer not showing updated value in freight settings.
* NEW Set a user spesific printer on user settings.

= 6.0.1-beta.7 = 
* FIX Handle possible missmatch in transport agreements.

= 6.0.1-beta.6 = 
* FIX Override orders without delivery relation.
* FIX Bug with not sending orders on marketing them complete.

= 6.0.1-beta.5 = 
* FIX Bug with overriding orders.

= 6.0.1-beta.4 = 
* FIX Bug with delivering orders on status change.

= 6.0.0-beta.1 =
* BREAKING All weight settings are now set in your Woocommerce local weight. If you are not useing KG and CM you MUST adjust your settings.
* BREAKING Changed most slugs from logistra-robots to wildrobot-logistra. Will effect translations.

* NEW Menu name "Transport" renamed to "Leverings innstillinger"
* NEW Pick and delivery screen to manage orders that needs to be delivered. Named "Plukk og levering".
* NEW Supports multiple shipping methods on orders.
* NEW Users without admin rights are able to set user spesific printer.
* NEW Connect to dev enviroment for dev, test og staging sites. Contact us.
* NEW Possibility to override DHL order values.
* NEW All weights and dimensions are now be denominated in your local woocommerce units and converted accordently.
* NEW Multiple performance enhancedments.
* NEW Add max_length, max_height, max_width on shipping method so it will be removed if any product in cart exceeds one of these dimensions.

* REMOVE Setting manual pickup in general settings
* REMOVE Leverings log view has been removed.

* FIX Adding new cards
* FIX More accurate calculation of combined volume of items.

Filter renamed
* logistra-robots-consignment-consignee-mobile => wildrobot_logistra_parts_cosignee_mobile
* logistra-robots-consignment-consignee-email => wildrobot_logistra_parts_cosignee_email
* logistra_robots_parts_cosignee_name => wildrobot_logistra_parts_cosignee_name
* logistra_robots_parts_cosignee_address1 => wildrobot_logistra_parts_cosignee_address1
* logistra_robots_parts_cosignee_country => wildrobot_logistra_parts_cosignee_country
* logistra_robots_parts_cosignee_phone => wildrobot_logistra_parts_cosignee_phone
* logistra_robots_parts_cosignee_mobile => wildrobot_logistra_parts_cosignee_mobile
* logistra_robots_parts_cosignee_country = wildrobot_logistra_consignment_country

New filters
* wildrobot_logistra_avaiable_service_partners, filter availability of service partners (pickuppoints).

= 5.0.0-beta.3 =
* BREAKING - Cost baased Freight estimation for shipping method calculation in cart and checkout will now ADD tax (mva) to estimated cost when rounding the amount.

= 5.0.0-beta.2 =
* FIX Error parsing freight estimations
* FIX Rouding on freight estimation with certain tax settings.

= 5.0.0-beta.1 =
* BREAKING Supportes freight products on different agreeements with same identifier. Requires updateing your freight product relations. Please keep some record of your current shipping method to freight product mapping.

= 4.9.6-beta =
* FIX Remove duplicate warning labels.

= 4.9.5-beta =
* FIX Stricter product description trim for DHL Electronic invoice

= 4.9.5 =
* FIX Static call on picklist print.

= 4.9.4 =
* NEW Toggle freight method availability if one product has that class.

= 4.9.3 =
* FIX Fix where override window would close when choosing certain freight product.

= 4.9.2 =
* FIX Add logging to how freight method is choosen.

= 4.9.1 =
* FIX Set test sites bug.

= 4.9.0 =
* NEW Book DHL pickups from override delivery.
* FIX Problem parsing services in some different PHP enviorments. 

= 4.8.1 =
* NEW Delivering(sending) when order changes status to completed will now check if it has been delivered(sent) before and dont resend the order (in-memory check).
* FIX Adjust tax text on shipping method

= 4.8.0 =
* NEW Delivering(sending) when order changes status to completed will now check if it has been delivered(sent) before and dont resend the order.

= 4.7.4 =
* NEW MAJOR Toggle freight method availability based on product shipping class.

= 4.7.3 =
* FIX DHL Electronic invoice handle freight with no warning labels correctly.
* FIX DHL Eletronic invoice strip html from description

= 4.7.2 =
* NEW Added services postnord_temp_control and postnord_id_check.
* FIX Removed delete account as it led to unintended deletion.
* NEW Added link to documentation. 

= 4.7.1 =
* CHANGE Changed text on buttons and bulk send orders.

= 4.7.0 = 
* FIX Better PHP8 support
* NEW Fallback servicepartner.
* NEW FreightMethod hide if order value is below.

= 4.6.0 = 
* NEW / FIX New logic to find correct time. Please check your transfer and print times after this update.
* NEW Helthjem now requires shopId and transportProduct in service partner request. 

= 4.5.0 = 
* NEW Send orders to transport will now NOT print allready printed orders. 

= 4.4.5 = 
* FIX dhl_express_lithium_ion_pi967ii indicator if warning label with "lithium", "ion", "pi967ii" is added.
* FIX spelling. 

= 4.4.4 = 
* ADD New properties to Electronic invoice with DHL.
* FIX Properly resolve unit value in Electronic invoice.

= 4.4.3 = 
* FIX Adjusted address1 in freight method.

= 4.4.2 = 
* FIX Export code not properly parsed for consignment

= 4.4.1 = 
* FIX DHL Electronic invoice properly resolves parent product description for variable products.
* NEW More services supported for freight estimation.
* FIX Better error messages

= 4.4.0 = 
* FIX DHL Electronic invoice now tries shipping descriptionm short description then long description to find product description for Eletronic invoice.

= 4.3.1 = 
* FIX DHL Electronic invoice for variants will now inherit parent product international shipping information.

= 4.3.0 = 
* FIX Properly strip emojis etc. from customer messages to orders.

= 4.2.5 = 
* ADD Possible to create "Hovedsending" (for Home small transport products) from override order.

= 4.2.4 =
* FIX php8 competability when parse_value from fronend.

= 4.2.3 =
* ADD new filter for consignee phone apply_filters('logistra_robots_parts_cosignee_phone', $order->get_billing_phone(), $orderId)

= 4.2.2 =
* FIX array_key_exist on object in class logistra options.

= 4.2.1 =
* FIX Added check for valid termsOfDelivery
* ADD New filter to override termsOfDelivery, apply_filters("logistra-robots-consignment-terms-of-delivery", $termsOfDelivery)

= 4.2.0 =
* ADD Can now also map up shipping methods not handled by other zones.

= 4.1.4 =
* FIX Disabled freight methods will not show in Logistra settings mapping.
* ADD Electronic invoice product warning labels is now a tag list. 
* FIX Electronic invoice countries properly displays all countries.

= 4.1.3 =
* FIX export settings json parse error

= 4.1.2 =
* FIX Cache problem customer number export settings.

= 4.1.1 =
* FIX Properly deletes all relations.
* NEW Added DHL Eletronic invoice support.

= 4.1.0 =
* BREAKING Freight method will now use your Woocommerce weight unit when calculating weight on Logistra Robots Freight method. If you use anything but KG for Woocommerce weight, you WILL NEED to change your values in the freight method.

= 4.0.6 =
* FIX Freight method now properly displays what units the weight or currency will be denomitated inn.
* NEW Possibility too sett free freight if weight is under certain value.

= 4.0.5-beta =
* FIX Resetting Wildrobot api key

= 4.0.4-beta =
* ADDED Not sending package dimensions by default. New setting in general settings to turn on/off sending dimesions (width, heigh, lengt) to Cargonizer.
* ADDED Shows Cargonizer NET estimate in order notes.


= 4.0.3-beta =
* FIX Not modifying packing slips when picklist not activated.

= 4.0.2-beta =

* FIX Printer settings errors
* FIX Error with override dimensions

= 4.0.1-beta =

* Fix apikey display.

= 4.0.0-beta =
* BREAKING changes in this update. Will require a migration script to run. Backup your data before upgrading.
* Added filter for email tracking URL apply_filters("logistra-robots-freight-track-email-field", $tracking_field, $fields, $sent_to_admin, $order)
* CHANGED Overide freight  now calculates from your woocommerce base dimesion unit and converts this to Logistra dimension unit.
* ADDED Overide freight possibility to add multiple collies, with their own measurements.
* CHANGED Billing interface.
* ADDED Billing possility to add custom montly limit.
* ADDED Billing possibility to change card.
* PERFORMANCE Multiple performance enhancements.
* ADDED New pickuppoint select in checkout. Better compatabilties with themes and can be customized with templates.

= 3.6.2 =
* Fix Return email label to customers.

= 3.6.1 =
* Fix so packing slip style does not interfer with default style when not used for freight print.

= 3.6.0 =
* NEW You can now print picklist on label printer. It will include both a picklist and a QRcode. The QRcode can be scanned with a mobile to fullfill the order 
and thereby create a freight label (Must be configured in settings). Requires the free plugin https://nb.wordpress.org/plugins/woocommerce-pdf-invoices-packing-slips/

= 3.5.9 =
* NEW Added possiblity to set custom order status after processing order delivery.
* FIX invoice list scroll

= 3.5.8 =
* Added service bring2_personal_delivery

= 3.5.7 =
* NEW Calculate dimensions based on multiple packages.

= 3.5.6 =
* FIX DHL order amount tax

= 3.5.5 =
* NEW Support for Bring priority service.

= 3.5.4 =
* FIX Admin check message, now properly displays only when user missing manage_options capability. 

= 3.5.3 =
* NEW Added paid by customer nr as setting on DHL international. 

= 3.5.2 =
* Fix for freight estimate caching.

= 3.5.1 =
* Fix payment page.

= 3.5.0 =
* NEW Logistra Robots freight method has setting for setting cost to 0 when users add free freight coupon.
* NEW Logistra Robots freight method has setting for debugging freight estimatation calculation.
* NEW Logistra Robots freight method has setting doing volume calculation in freight estimation.
* FIX Changed weight and volume normalization in freight estimation.
* NEW Added general option to calculate volume for all shipments connected to orders.

= 3.4.3 = 
* Added notification when user does not seem to have admin rights.
* Fixed style issues.

= 3.4.2 = 
* NEW Added consignment consignee filters
= 3.4.1 =
* FIX Possible case where freight estimation remove freight mehod would not actual remove the option on API request fail.

= 3.4.0
* FIX Freight estimation: Removed volume calculation because it will often give wrong results when there is no shipping box calulation. 
* CHANGE Add admin email for customer ordered customer returns.

= 3.3.6 =
* FIX Bug where deliveryTerm whould not show updated value in dropdown.

= 3.3.5 =
* FIX Bug when updating freight products

= 3.3.4 =
* FIX Freight estimation - Rouding when tax are applied. 
* FIX Freight estimation caching - New way to generate hash. 

= 3.3.3 =
* Fix fallback for orders without country.

= 3.3.2 =
* FIX bug where actual freight relation would not be used if fallback was set.
* FIX Bug in handle consignee country.
* NEW International shipping with DHL Express Worldwide. Add delivery terms on deliveries and will automatically add customs value and currency.

= 3.3.1 =
* FIX User defined printer now properly saves its value on the user, not requiering admin rights. Privious connected printers to user will need to be reconnected. 

= 3.3.0 =
* NEW Logistra Robots freight method now has feature to estimate freight cost based on API-call for cost estimation. 
* NEW Freight estimation: Add fixed sum to estimated freight cost.
* NEW Freight estimation: Add % sum to estimated freight cost.
* NEW Freight estimation: Hide freight methods which could not be estimated.
* NEW Freight estimation: Round freight cost estimation to nearest 9.

= 3.2.4 =
* FIX Not working fallback in some cases where freight method is set.

= 3.2.3 =
* FIX Better colors on button to see if an order has been shipped.

= 3.2.2 =
* NEW Added logistra-robots-send-order hook that accepts orderId to send orders.
* FIX Return on orders with services that was not applicable for the return consignment is now fixed. 

= 3.2.1 =
* Fix Bug after upgrade in override delivery.

= 3.2.0 =
* NEW Option on deliveries to print return shipping labels automatically when sending orders. Then put that label into package and use if needed.
* FIX Error for some transport products that required phone number on consignee object.
* FIX Text inn general settings.
* NEW Option in print settings to let user set a spesific printer connected to their user for all their deliveries.

= 3.0.14 =
* FIX Parsing of weight integer

= 3.0.13 =
* FIX Parsing of volume integer

= 3.0.12 =
* PERFORMANCE cached transportProducts and printers in client for better performance in concurrent components requst.

= 3.0.11 =
* FIX bug where transport products would not show when having many shipping methods.

= 3.0.10 =
* FIX standard override packages is now 1.
* PERFORMANCE Return order module.
* FEATURE If order has freight method, suggest it when overriding.

= 3.0.9 =
* ADD Possibility to use a fallback freight product for orders that dont have freight method etc. Turn it on in settings and congigure the fallback option in freightmethod and freightproduct relation. 

= 3.0.8 = 
* FIX removed console logging
* ADD Order defaults (and espcially weight) should now propegate to override order settings.
* ADD If weight order default found, will suggest it override.
* PERFORMANCE Optimized ajax request affecting freight method configuration.

= 3.0.7 = 
* Fix escape order input that made some order not sendable.

= 3.0.6 = 
* Fix error with override and possiblity to override from something without shipping method and service partner.

= 3.0.5 = 
* Fix error with error message on send order.

= 3.0.4 =
* Fix printers cache did not update

= 3.0.3 =
* CHANGE filter logistra_robots_parts_cosignee_country now also returns shipping (or billing if shipping does not exist)
* Removed Phone from consignee so numbers wont turn up twice on labels.

= 3.0.2 =
* FIX Parsing billing order data properly if shipping data is not present.
* FIX order number display.
* FIX logic error for overriding pickuppoint.

= 3.0.1 =
* FIX removed text in pickup selecter
* added translation file
* FIX where users would not be able to update apikey or sender.
* FIX incorret display of logistra packages.

= 3.0.0 =
* FIX select printer
* BREAKING CHANGE: Services connected to your transport settings must be reconfigured.
* BREAKING CHANGE: Removed support for Woocoomerce older than 2.5, which released in 2016. Shipping zones are here to stay.
* BREAKING CHANGE filter: logistra_robots_parts_cosignee_country now returns orderId, instead of Order object.
* Clearer saving in settings
* Override freight has been recreated with many new options for overriding av specific order.
* Pickup point selector for cart and checkout has been rewritten.
* Many improvements to setting page.
* Booking request now available to set as a service both on freight product relations and in override order.
* See your account information with Logistra and remaining consignments.
* Added agreementterms
* Add addon modules. 
* Addon module, customer return. Let customers create return on their own orders and print labels.
* Reduced size of js files
* Supports custom woocommerce weight units.

= 2.5.5 =
* FIX print select

= 2.5.4 =
* Fix texts

= 2.5.3 =
* Fix printer select 
* Fix order buttons

= 2.5.2 =
* Fix customer return

= 2.5.1 =
* FIX DHL format consignment without consignee

= 2.5.0 =
* Upgraded modules.
* ADD customer return addon.
* FIX Dont send customer number for guest to Cargonizer.

= 2.4.0 =
* FIX Text in readme.

= 2.3.1 =
* Fixed buttons to woopress admin style

= 2.3.0 =
* FIX volume calculation
* ADD option to turn on off email tracking. Default ON
* ADD Hide freight metode over certain treshhold value.
* ADD option to turn on free freight notice
* ADD option to set a almost free freight notice with shop link.
* FIX CAREFUL! Stores must check their calculation after applying update. Calculation of order value for Logistra Wildrobots free treshhold and max value. Should now be easier to set free treshhold and max value. 

= 2.2.2 =
* ADD Volume calculation
* ADJUST title and helt text shipping method.
* ADD possibility to manual printing (without direct print)
* ADJUST only show 5 latest deliveryPoints.

= 2.2.1 =
* Fix override service

= 2.2.0 =
* Fixed log messages
* Refacotred consignment generation
* Fixed bug where transfers setting would not be respected

= 2.1.6 =
* Support flatsome checkout and cart for delivery point picker.
* Delivery point picker will now not show when no delivery point is needed or required.
* Optimizations to querys done from cart and checkout for delivery point picker.
* Styled tracking url in emails.
* Added more functionality to override freight. Now you can set number of packages, a spesific weight and dimensions.
* Refactor for consignment generation
* Better error handling bulk.

= 2.1.5 =
* Fixed typings
* Fixed bug with one service on a transport agreement
* Changed hook order processing orders so tracking url is added.

= 2.1.4 =
* Fix for message when adding free freight.

= 2.1.3 =
* Dont show cancle when sub is cancled.
* Fix where services was not added
* Added shipping method for handling based on weight and order value.
* Notifications in cart and checkout for this shipping methods

= 2.1.2 =
* Removed transport description from
* Added more services

= 2.1.1 =
* Fixed bug where services would not display in relation when only one.

= 2.1.0 =
* Removed not used services and added bring2_parcel_pickup_point and pose_pa_doren.
* Comptability with new Bring changes
* Better loggging
* Changed some labels
* Render bug when relating shipping methods with transport products
* Now shows more info on which transport product that has been used for shipping method.
* Added possibility to zero out freight relations
* Added bulk send order functionality

= 2.0.8 =
* Fix logs display

= 2.0.7 =
* Added logging
* Added plausible fix to not require transport agreement for overriden orders.

= 2.0.6 =
* Added default country code to "NO" (NORWAY) when sending orders and nothign specsified.
* Added logging

= 2.0.5 =
* Added freight booking manual activation

= 2.0.4 =
* Fixed better handlign of tabel rate if useing if but not integrating it.
* Fixed order meta getters.

= 2.0.3 =
* Fix for table rates with empty label. Should not show up.
* Added setting to control table rates on each line or as a sum.

= 2.0.2 =
* Fixed smaller order list buttons

= 2.0.1 =
* Fix where UI bugs when no transport agreements.

= 2.0.0 =
* Backend rewritten
* Frontend rewritten with React hooks.
* Restarted changelog, many new features.
* Added general option to send freight on complete.
* Added return address
* Simple Shipping rate plugin from Woocommerce support
* Added advanced setting

= 1.1.8 =
* Fixed properly scoping css admin.

= 1.1.7 =
* Adjusted order action buttons.
* Scoped css

= 1.1.6 =
* Fix bug with printing.


= 1.1.5 =
* New service point picker frontend
* Changed labels for settings
* Added a recovery method for auth
* Change order buttons design
* Added cache for service partners.
* Encodeing

= 1.1.4 =
* Fix for printers, get all by multiple senders.

= 1.1.3 =
* Optimizations

= 1.1.2 =
* Optimizations

= 1.1.1 =
* Added log.
* Changed servicepicker to a popup.
* Many UX optmizations.

= 1.1.1 =
* Fixed some translations.

= 1.1.0 =
* Release - started changelog
