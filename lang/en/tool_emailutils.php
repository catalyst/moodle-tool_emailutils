<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Lang pack
 *
 * @package    tool_emailutils
 * @copyright  2018 onwards Catalyst IT {@link http://www.catalyst-eu.net/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Harry Barnard <harry.barnard@catalyst-eu.net>
 */

$string['authorisationcategory'] = 'Authorisation Settings';
$string['aws_key'] = 'AWS Access Key';
$string['aws_key_desc'] = 'Your AWS Access Key ID';
$string['aws_region'] = 'AWS Region';
$string['aws_region_desc'] = 'The AWS region for your SES service';
$string['aws_secret'] = 'AWS Secret Key';
$string['aws_secret_desc'] = 'Your AWS Secret Access Key';
$string['aws_suppressionlist'] = 'AWS suppression list';
$string['aws_suppressionlist_disabled'] = 'Updating of the account level AWS suppression list is currently disabled. This can be enabled by changing enable_suppression_list in AWS SES settings.';
$string['aws_suppressionlist_taskdisabled'] = 'The scheduled task to update this list is disabled.';
$string['aws_suppressionlist_tasknever'] = 'The scheduled task to update this list has never been run successfully.';
$string['aws_suppressionlist_taskupdated'] = 'The scheduled task to update this list was last run {$a} ago.';
$string['bouncebreakdown'] = 'Breakdown of users with bounces';
$string['bouncecheckfull'] = 'Are you absolutely sure you want to reset the bounce count for {$a} ?';
$string['bouncecount'] = 'Bounce count';
$string['bounceconfig'] = 'Bounce handling is enabled. Emails will not be sent to addresses with over {$a->minbounces} bounces and a bounce ratio above {$a->bounceratio}. These values can be configured in the emailutils plugin settings.';
$string['bounceratio'] = 'Bounce ratio';
$string['bounces'] = 'For a list of bounces, visit {$a} and search for emails ending with ".b.invalid."';
$string['bouncesreset'] = 'Bounces have been reset for the selected users';
$string['checkbounces'] = 'Email bounces';
$string['checkdnsdkim'] = 'DNS Email DKIM check';
$string['checkdnsdmarc'] = 'DNS Email DMARC check';
$string['checkdnsmx'] = 'DNS Email MX check';
$string['checkdnsnoreply'] = 'DNS Email noreply shape check';
$string['checkdnspostmastertools'] = 'Check Post master tools';
$string['checkdnsspf'] = 'DNS Email SPF check';
$string['check:bounces:breakdown:overthreshold'] = 'Over threshold';
$string['check:bounces:breakdown:overminbounces'] = 'Over minbounces, under ratio';
$string['check:bounces:breakdown:underminbounces'] = 'Under minbounces';
$string['check:bounces:disabled'] = 'The site is not configured to handle bounces.';
$string['check:bounces:none'] = 'No users have recorded bounces.';
$string['check:bounces:overthreshold'] = 'Found {$a->count} users over the Moodle bounce threshold.';
$string['check:bounces:underthreshold'] = 'No users are over the Moodle bounce threshold.';
$string['complaints'] = 'For a list of complaints, search for ".c.invalid"';
$string['dkimmanager'] = 'SPF & DKIM manager';
$string['dkimmanagerhelp'] = '<p>This shows all DKIM key pairs / selectors available for email signing, including those made by this admin tool or put in place by external tools such as open-dkim. For most systems this is the end to end setup:</p>
<ol>
<li>First decide and set the <code>$CFG->noreply</code> email as the domain of the reply email is tied to the signing.
<li>Create a new private and public key pair using a selector of your choice. The selector is arbitrary but a rough date format is a good convention.
<li>Save the DNS record shown in this tool into your DNS server
<li>Confirm that the DNS is in the correct shape using the MXtoolbox links
<li>Now activate the selector you have chosen
<li>Use the <a href="{$a->emailtest}">test email tool</a> to send a real email and confirm the DKIM headers have been sent
<li>Also confirm the DKIM headers validate using a 3rd party tool, such as those provided by Gmail and most email clients
</ol>
';
$string['dnssettings'] = 'SPF / DKIM / DMARC DNS settings';
$string['dnsspfinclude'] = 'SPF include';
$string['dnsspfinclude_help'] = '<p>This is an SPF include domain which is expected to be present in the record. For example if this was set to <code>spf.acme.org</code> then the SPF security check would pass if the SPF record was <code>v=spf1 include:spf.acme.org -all</code>.</p>
<p>The * char can be used as a wildcard eg <code>*acme.org</code> would also match.</p>
';
$string['domain'] = 'Domain';
$string['domaindefaultnoreply'] = 'Default noreply';
$string['enable_bounce_handling'] = 'Enable bounce handling';
$string['enable_bounce_handling_help'] = 'Allow the plugin to process incoming bounce notifications emitted by SES from SNS';
$string['enable_suppression_list'] = 'Enable email suppression list';
$string['enable_suppression_list_desc'] = 'When enabled, the system will maintain a suppression list of email addresses that should not receive emails.';
$string['event:bouncecountreset'] = 'Bounce count reset';
$string['event:notificationreceived'] = 'AWS SNS notification received';
$string['event:overbouncethreshold'] = 'Over bounce threshold';
$string['header'] = 'Header';
$string['header_help'] = 'HTTP Basic Auth Header';
$string['incorrect_access'] = 'Incorrect access detected. For use only by AWS SNS.';
$string['list'] = 'Complaints List';
$string['noreplyemail'] = 'No reply email:';
$string['mxrecords'] = 'MX records';
$string['mxtoolbox'] = 'MXtoolbox links';
$string['not_implemented'] = 'Not implemented yet. Search the user report for emails ending with ".b.invalid" and ".c.invalid".';
$string['notificationlog'] = 'SNS notification log';
$string['password'] = 'Password';
$string['password_help'] = 'HTTP Basic Auth Password - Leave empty if you\'re not changing the password';
$string['pluginname'] = 'Email utilities';
$string['postmastergoogletoken'] = 'Post master tools google-site-verification';
$string['postmastergoogletoken_help'] = 'This is the entire TXT DNS record and looks similar to <code>google-site-verification=abcdef123456789abcdef</code>';
$string['postmastertools'] = 'Post master tools';
$string['privacy:metadata:tool_emailutils_list'] = 'Information.';
$string['privacy:metadata:tool_emailutils_list:updatedid'] = 'The ID of updated user.';
$string['privacy:metadata:tool_emailutils_list:userid'] = 'The ID of the user.';
$string['reportbounces'] = 'Email bounces';
$string['resetbounceratio'] = 'A bounce ratio is being used, so resetting the bounce count will also reset the send count.';
$string['resetbounces'] = 'Reset bounce count';
$string['selectoractivate'] = 'Activate key pair';
$string['selectoractivateconfirm'] = 'This will set $CFG->emaildkimselector to this selector and it will be used for signing outgoing emails.';
$string['selectoractive'] = 'Active selector';
$string['selectoractivated'] = 'Selector was activated';
$string['selectorcreate'] = 'Create a new domain:selector certificate pair';
$string['selectorcreated'] = 'A new certificate pair has been created';
$string['selectorcreatesubmit'] = 'Create new selector';
$string['selectordeactivate'] = 'Deactivate key pair';
$string['selectordeactivateconfirm'] = 'This will unset $CFG->emaildkimselector so emails will no longer be signed.';
$string['selectordeactivated'] = 'Email signing has been turned off';
$string['selectordefault'] = '%Y-%m';
$string['selectordelete'] = 'Delete key pair';
$string['selectordeleteconfirm'] = 'This will permanently delete this selector\'s private and public keys and is irreversable.';
$string['selectordeleted'] = 'Key pair has been deleted';
$string['selectormissing'] = 'No DKIM selector certificates found';
$string['selectornotblank'] = 'Selector cannot be empty';
$string['sendcount'] = 'Send count';
$string['settings'] = 'AWS SES settings';
$string['subtypes'] = 'Reason';
$string['suppressionreason'] = 'Reason';
$string['suppressioncreated'] = 'Created at';
$string['task_update_suppression_list'] = 'Update email suppression list';
$string['type'] = 'Type';
$string['username'] = 'Username';
$string['username_help'] = 'HTTP Basic Auth Username';
$string['userdomains'] = 'Most common user email domains';
$string['vendor'] = 'Vendor';
$string['config:minbounces'] = 'Minimum bounces';
$string['config:minbounces:desc'] = 'Along with bounce ratio, if the user exceeds this value they are stopped from sending emails.';
$string['config:bounceratio'] = 'Bounce ratio';
$string['config:bounceratio:desc'] = 'The number of bounces divided by the number of emails sent. Along with minimum bounces, if the user exceeds this value they are stopped from sending emails.';
$string['config:bounce'] = 'Bounce handling';
$string['bouncehandlingnotenabled'] = 'Bounce handling is not enabled';
