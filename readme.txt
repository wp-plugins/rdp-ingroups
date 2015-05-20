=== Plugin Name ===
Contributors: rpayne7264
Tags: linkedin,linkedin groups,rdp linkedin,rdp groups+,rdp ingroups+,rdp linkedin groups,rdp linkedin groups+,ingroups+,
Requires at least: 3.0
Tested up to: 4.2.2
Stable tag: 0.5.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Integrate LinkedIn groups into WordPress
== Description ==

RDP inGroups+ adds read-only LinkedIn Groups functionality to your WordPress blog using simple shortcodes, so you can keep your LinkedIn tribe on your own site.

You can set a default group ID in the group shortcode so that a group's discussions will be pulled from LinkedIn. For a non-logged-in visitor, the last 10 most recent discussions are scraped from LinkedIn. For logged-in user, paging will be active for up to 19 additional pages of discussions.

You can set up multiple pages on your site to display different groups by setting a different group ID for each shortcode.

RDP inGroups+ provides:

* Group shortcode - display a list of discussions for a single group by supplying a LinkedIn group ID
* Login button shortcode - shows a *Sign in with LinkedIn* button when logged out
* Member Count shortcode, with the ability to designate a link URL
* Ability to register a visitor with the WordPress installaton
* Logged-in session security using nonces and client IP address
* OOP with hooks and filters for easy integration and customization
* Ability to add a list of company IDs that a registered user will automatically follow
* Works with WPEngine hosting and other caching systems that consider query string parameters
* Bitly URL shortening API integration for social sharing and syndication functionality on individual discussions
* Bookmarkable/shareable links for individual discussions
* RSS feed for individual LinkedIn groups
* Special RSS feed for Mailchimp newsletter template
* Integration with [RDP Wiki-Press Embed](https://wordpress.org/plugins/rdp-wiki-press-embed/)


= Warning About Caching =

This plug-in is intentionally coded to re-write hyperlinks with a cache-busting query string parameter so that the plug-in code is executed with every page load. Therefore, any sort of caching will be, effectively, disabled when using this plug-in.


= Sponsor =

This plug-in brought to you through the generous funding of [Laboratory Informatics Institute, Inc.](http://www.limsinstitute.org/)


== Installation ==

= From your WordPress dashboard =

1. Visit 'Plugins > Add New'
2. Search for 'RDP inGroups+'
3. Click the Install Now link.
3. Activate RDP inGroups+ from your Plugins page.


= From WordPress.org =

1. Download RDP inGroups+.
2. Upload the 'rdp-linkedin-groups' directory to your '/wp-content/plug-ins/' directory, using your favorite method (ftp, sftp, scp, etc...)
3. Activate RDP inGroups+ from your Plugins page.


= After Activation - Go to 'Settings > RDP inGroups+' and: =

1. Get LinkedIn Application API keys using the link and settings shown at the top of the settings page.
2. Enter API Key.
3. Enter Secret Key.
4. Set other configurations as desired.
5. Click 'Save Changes' button.
6. Add the [rdp-ingroups-group] shortcode to a page and save the page.


= Extra =

1. Adjust the CSS widths on the settings page to make everything pretty, if necessary.
2. For more control, add an ingroups.custom.css file to your theme's folder. Start with the ingroups.custom-sample.css file located in the 'rdp-linkedin-groups/pl/style/' directory.



= Special Notes About Company Auto-Follow Feature =

To have a user auto-follow companies, *Register New Users?* must be enabled.

The auto-follow feature is a one-time process for each user who is registered with the WordPress installation. Adding new company IDs will not retroactively join existing site users to the newly added companies.



== Frequently Asked Questions ==

= How do I access the RSS feed designed for Mailchimp newsletters? =

Click on the RSS icon in the group header to open the standard RSS feed in a browser window. Now, modify the URL in the newly opened window by changing rss.php to rss_1.php.


== Usage ==

Add the [rdp-ingroups-group] shortcode to a page and specify a group ID, which will display discussions of the designated group to site visitors: [rdp-ingroups-group id=2069898]. 
The shortcode will display a *Sign in with LinkedIn* button if the user is not logged in.

For a display of a group's member count, use the [rdp-ingroups-member-count] shortcode. 
The id attribute is required and is set to a group ID: [rdp-ingroups-member-count id=209217]. 
You can also specify a url to make the member count a hyperlink: [rdp-ingroups-member-count id=209217 link=http://example.com]. 
To make the link open in a new tab, add new as a shortcode attribute: [rdp-ingroups-member-count id=209217 link=http://example.com new].

To display a *Sign in with LinkedIn* button, use the [rdp-ingroups-login] shortcode.



== Screenshots ==

1. Login button shortcode in sidebar. 
2. Group shortcode, using the ID attribute to specify a LinkedIn group, embedded in a page. Notice that the user is not logged in. The first ten discussions have been scrapped from LinkedIn and displayed. If the group is open, a public RSS feed is made available, displaying the ten most recent discussions.
3. Group shortcode, using the ID attribute to specify a LinkedIn group, embedded in a page and with the user logged in.
4. Popup action menu for logged-in user. Additional custom links can be added with a little PHP coding.
5. Single discussion as seen by a non-logged-in visitor - must log in to see comments.
6. Single discussion as seen by a logged-in user.
7. Offer sharing of single discussions, using the Bitly API.
8. Settings page.
9. Button to launch the shortcode embed helper form

== Change Log ==

= 0.5.0 =
* Initial RC


== Upgrade Notice ==

== Other Notes ==

== External Scripts Included ==
* jQuery.PositionCalculator v1.1.2 under MIT License
* Query Object v2.1.8 under WTFPL License
* URL v1.8.6 under MIT License

== Hook Reference: ==

= rdp_lig_before_user_login =

* Param: JSON object representing a LinkedIn Person containing firstName, lastName, emailAddress, pictureUrl, publicProfileUrl, and id
* Fires before any user is logged into the site via LinkedIn.

= rdp_lig_after_insert_user =

* Param: WP User Object
* Fires after a new user is registered with the site. *(Register New Users? must be enabled)*

= rdp_lig_after_registered_user_login =

* Param: WP User Object
* Fires after a registered user is logged into the site. *(Register New Users? must be enabled)*

= rdp_lig_registered_user_login_fail =

* Param: JSON object representing a LinkedIn Person containing firstName, lastName, emailAddress, pictureUrl, publicProfileUrl, and id
* Fires after a failed attempt to log registered user into the site. *(Register New Users? must be enabled)*

= rdp_lig_after_user_login =

* Param: RDP_LIG_DATAPASS object
* Fires after any user is logged into the site via LinkedIn.

= rdp_lig_after_scripts_styles =

* Param: none
* Fires after enqueuing plug-in-specific scripts and styles

== Filter Reference: ==

= rdp_lig_render_header_top =

* Param 1: String containing opening div and wrapper HTML for header section
* Param 2: String containing status - 'true' if user is logged in, 'false' otherwise
* Return: opening HTML for header section

= rdp_lig_render_header =

* Param 1: String containing the body HTML for header section
* Param 2: String containing status - 'true' if user is logged in, 'false' otherwise
* Return: body HTML for header section

= rdp_lig_render_header_bottom =

* Param 1: String containing closing wrapper and div HTML for header section
* Param 2: String containing status - 'true' if user is logged in, 'false' otherwise
* Return: closing HTML for header section

= rdp_lig_render_main_container_header =

* Param 1: String containing HTML for main container header section
* Param 2: String containing status - 'true' if user is logged in, 'false' otherwise
* Return: HTML for main container header section
* Default behavior is to render the group profile logo and name

= rdp_lig_render_paging =

* Param 1: String containing HTML for paging section
* Param 2: String containing status - 'true' if user is logged in, 'false' otherwise
* Param 3: String containing the location - 'top' of main container section, 'bottom' of main container section
* Return: HTML for paging section. For infinity paging, location 'top' is not rendered.

= rdp_lig_render_login =

* Param 1: String containing log-in HTML for the **[rdp-ingroups-login]** shortcode
* Param 2: String containing status - 'true' if user is logged in, 'false' otherwise
* Return: log-in HTML for the **[rdp-ingroups-login]** shortcode

= rdp_lig_before_insert_user =

* Param 1: Boolean indicating if user exists based on result of Wordpress username_exists() function, using supplied email address 
* Param 2: JSON object representing a LinkedIn Person containing firstName, lastName, emailAddress, pictureUrl, publicProfileUrl, and id
* Return: Boolean indicating if user exists

= rdp_lig_before_registered_user_login =

* Param 1: Boolean indicating if user is logged in based on result of Wordpress is_user_logged_in() function
* Param 2: String containing email address of user
* Return: Boolean indicating if user is logged in

= rdp_lig_custom_menu_items =

* Param 1: Array to hold custom link data
* Param 2: String containing status - 'true' if user is logged in, 'false' otherwise
* Return: Array of links, where the link text is the key and the link URL is the value
