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
$string['bouncecheckfull'] = 'Are you absolutely sure you want to reset the bounce count for {$a} ?';
$string['bouncecount'] = 'Bounce count';
$string['bounces'] = 'For a list of bounces, visit {$a} and search for emails ending with ".b.invalid."';
$string['bouncesreset'] = 'Bounces have been reset for the selected users';
$string['configmissing'] = 'Missing config.php setting ($CFG->handlebounces) please review config-dist.php for more information.';
$string['complaints'] = 'For a list of complaints, search for ".c.invalid"';
$string['dkimmanager'] = 'SPF & DKIM manager';
$string['checkdnsspf'] = 'DNS Email SPF check';
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
$string['dnsspfinclude_help'] = '<p>This is an SPF include domain which is expected to be present in the record. For example if this was set to <code>spf.acme.org</code> then the SPF security check would pass if the SPF record was <code>v=spf1 include:spf.ache.org -all</code>.</p>
<p>The * char can be used as a wildcard eg <code>*acme.org</code> would also match.</p>
';
$string['domaindefaultnoreply'] = 'Default noreply';
$string['enabled'] = 'Enabled';
$string['enabled_help'] = 'Allow the plugin to process incoming messages';
$string['event:notificationreceived'] = 'AWS SNS notification received';
$string['header'] = 'Header';
$string['header_help'] = 'HTTP Basic Auth Header';
$string['incorrect_access'] = 'Incorrect access detected. For use only by AWS SNS.';
$string['list'] = 'Complaints List';
$string['mxtoolbox'] = 'MXtoolbox links';
$string['not_implemented'] = 'Not implemented yet. Search the user report for emails ending with ".b.invalid" and ".c.invalid".';
$string['password'] = 'Password';
$string['password_help'] = 'HTTP Basic Auth Password - Leave empty if you\'re not changing the password';
$string['privacy:metadata:tool_emailutils_list'] = 'Information.';
$string['privacy:metadata:tool_emailutils_list:userid'] = 'The ID of the user.';
$string['privacy:metadata:tool_emailutils_list:updatedid'] = 'The ID of updated user.';
$string['pluginname'] = 'Email utilities';
$string['resetbounces'] = 'Reset the number of bounces';
$string['sendcount'] = 'Send count';
$string['selectoractive'] = 'Active selector';
$string['selectoractivate'] = 'Activate key pair';
$string['selectoractivated'] = 'Selector was activated';
$string['selectoractivateconfirm'] = 'This will set $CFG->emaildkimselector to this selector and it will be used for signing outgoing emails.';
$string['selectorcreate'] = 'Create a new domain:selector certificate pair';
$string['selectorcreatesubmit'] = 'Create new selector';
$string['selectorcreated'] = 'A new certificate pair has been created';
$string['selectordefault'] = '%Y-%m';
$string['selectordeactivate'] = 'Deactivate key pair';
$string['selectordeactivated'] = 'Email signing has been turned off';
$string['selectordeactivateconfirm'] = 'This will unset $CFG->emaildkimselector so emails will no longer be signed.';
$string['selectormissing'] = 'No DKIM selector certificates found';
$string['selectordelete'] = 'Delete key pair';
$string['selectordeleted'] = 'Key pair has been deleted';
$string['selectordeleteconfirm'] = 'This will permanently delete this selector\'s private and public keys and is irreversable.';
$string['settings'] = 'AWS SES settings';
$string['username'] = 'Username';
$string['username_help'] = 'HTTP Basic Auth Username';
