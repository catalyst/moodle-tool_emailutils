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
 * DKIM manager admin page
 *
 * @package    tool_emailutils
 * @copyright  2023 onwards Catalyst IT {@link http://www.catalyst-eu.net/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Brendan Heywood <brendan@catalyst-au.net>
 */

use tool_emailutils\dkim_manager;

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

$baseurl = new moodle_url('/admin/tool/emailutils/dkim.php');
$PAGE->set_url($baseurl);
admin_externalpage_setup('tool_emailutils_dkim');

$action = optional_param('action', '', PARAM_ALPHA);

if ($action == 'delete') {
    require_sesskey();
    $domain = required_param('domain',  PARAM_TEXT);
    $selector = required_param('selector', PARAM_TEXT);
    $manager = new dkim_manager($domain, $selector);
    $manager->delete_selector();
    redirect($baseurl, get_string('selectordeleted', 'tool_emailutils'), null, \core\output\notification::NOTIFY_WARNING);
}

if ($action == 'activate') {
    require_sesskey();
    $selector = required_param('selector', PARAM_TEXT);
    add_to_config_log('emaildkimselector', $CFG->emaildkimselector, $selector, '');
    set_config('emaildkimselector', $selector);
    redirect($baseurl, get_string('selectoractivated', 'tool_emailutils'), null, \core\output\notification::NOTIFY_SUCCESS);
}

if ($action == 'deactivate') {
    require_sesskey();
    $selector = required_param('selector', PARAM_TEXT);
    add_to_config_log('emaildkimselector', $CFG->emaildkimselector, '', '');
    set_config('emaildkimselector', '');
    redirect($baseurl, get_string('selectordeactivated', 'tool_emailutils'), null, \core\output\notification::NOTIFY_WARNING);
}

$form = new \tool_emailutils\form\create_dkim();
if ($form->is_cancelled()) {
    redirect($prevurl);
} else if ($fromform = $form->get_data()) {

    $domain = $fromform->domain;
    $selector = $fromform->selector;
    $manager = new dkim_manager($domain, $selector, true);
    redirect($baseurl, get_string('selectorcreated', 'tool_emailutils'), null, \core\output\notification::NOTIFY_SUCCESS);
}

$dkimdir = $CFG->dataroot . '/dkim/';
$domains = [];
if (is_dir($dkimdir)) {
    $domains = scandir($dkimdir, SCANDIR_SORT_DESCENDING);
}

$domaincount = 0;
$noreplydomain = substr($CFG->noreplyaddress, strpos($CFG->noreplyaddress, '@') + 1);

// Always make sure the noreply domain is included even if nothing has been setup yet.
$domains = array_unique(array_merge($domains, [$noreplydomain]));

print $OUTPUT->header();
print $OUTPUT->heading(get_string('dkimmanager', 'tool_emailutils'));

print "<table class='table table-sm w-auto table-bordered'>";
print '<tr><th colspan=2>Domains / selectors</th><th>Actions</th></tr>';
foreach ($domains as $domain) {

    if (substr($domain, 0, 1) == '.') {
        continue;
    }
    if (!is_dir($dkimdir . $domain) && $domain != $noreplydomain) {
        continue;
    }

    $domaincount ++;

    print '<tr><td colspan=2>';
    print '<h3>';
    print html_writer::tag('span', "@$domain ");
    if ($domain == $noreplydomain) {
        print ' ' . html_writer::tag('span', get_string('domaindefaultnoreply', 'tool_emailutils'),
            ['class' => 'badge badge-secondary']);
    }
    print '</h3>';
    print '</td>';
    print '<td>';

    $url = new moodle_url('https://mxtoolbox.com/SuperTool.aspx', ['action' => "spf:$domain", 'run' => 'toolpage']);
    print get_string('mxtoolbox', 'tool_emailutils');
    print '<ul>';
    print "<li><a href='$url' target='_blank'>SPF</a>";

    $url = new moodle_url('https://mxtoolbox.com/SuperTool.aspx', ['action' => "txt:$domain"]);
    print "<li><a href='$url' target='_blank'>Raw TXT</a>";

    $url = new moodle_url('https://mxtoolbox.com/SuperTool.aspx', ['action' => "dmarc:$domain", 'run' => 'toolpage']);
    print "<li><a href='$url' target='_blank'>DMARC</a>";

    $url = new moodle_url('https://mxtoolbox.com/SuperTool.aspx', ['action' => "txt:_dmarc.$domain"]);
    print "<li><a href='$url' target='_blank'>DARMC TXT</a>";

    print '</th></tr>';


    $selectors = [];
    $selectordir = $dkimdir . $domain;
    if (is_dir($selectordir)) {
        $selectors = scandir($selectordir);
    }

    // We want newer date based selectors to be at the top.
    natsort($selectors);
    $selectors = array_reverse($selectors);

    $selectorcount = 0;

    foreach ($selectors as $file) {

        if (substr($file, -8, 8) !== '.private') {
            continue;
        }

        $selector = substr($file, 0, -8);
        $manager = new dkim_manager($domain, $selector);

        $context = [
            'domain'    => $domain,
            'selector'  => $selector,
            'dkimurl'   => new moodle_url('https://mxtoolbox.com/SuperTool.aspx',
                ['action' => "dkim:$domain:$selector", 'run' => 'toolpage']),
            'dkimrawurl' => new moodle_url('https://mxtoolbox.com/SuperTool.aspx',
                ['action' => "txt:$selector._domainkey.$domain"]),
            'dnskey'    => $manager->get_dns_key(),
            'dnsvalue'          => $manager->get_dns_value(),
            'dnsvaluechunked'   => $manager->get_dns_value_chunked(),
            'dnsvalueescaped'   => $manager->get_dns_value_escaped(),
            'id' => uniqid(),
        ];

        if ($CFG->emaildkimselector == $selector) {
            $context['selectoractive'] = true;
        }

        $isactive = $CFG->emaildkimselector == $selector;

        // Only give the option to delete if it is not being used.
        $confirmation = new \confirm_action(
            get_string('selectordeleteconfirm', 'tool_emailutils'),
            null,
            get_string('selectordelete', 'tool_emailutils')
        );
        $context['selectordelete'] = $OUTPUT->action_link(
            new moodle_url('/admin/tool/emailutils/dkim.php', [
                    'domain'    => $domain,
                    'selector'  => $selector,
                    'action'    => 'delete',
                    'sesskey'   => sesskey()]),
                get_string('selectordelete', 'tool_emailutils'),
                $confirmation,
                [ 'class' => 'btn btn-sm ' . ($isactive ? 'btn-secondary disabled' : 'btn-outline-danger') ],
                new pix_icon('i/delete', ''));

        if ($isactive) {
            // Only give the option to make it the active select if it is not being used.
            $confirmation = new \confirm_action(
                get_string('selectordeactivateconfirm', 'tool_emailutils'),
                null,
                get_string('selectordeactivate', 'tool_emailutils')
            );
            $context['selectordeactivate'] = $OUTPUT->action_link(
                new moodle_url('/admin/tool/emailutils/dkim.php', [
                        'selector'  => $selector,
                        'action'    => 'deactivate',
                        'sesskey'   => sesskey()]),
                    get_string('selectordeactivate', 'tool_emailutils'),
                    $confirmation,
                    [ 'class' => 'btn btn-sm btn-secondary' ],
                    new pix_icon('i/show', ''));
        } else {
            // Only give the option to make it the active select if it is not being used.
            $confirmation = new \confirm_action(
                get_string('selectoractivateconfirm', 'tool_emailutils'),
                null,
                get_string('selectoractivate', 'tool_emailutils')
            );
            $context['selectoractivate'] = $OUTPUT->action_link(
                new moodle_url('/admin/tool/emailutils/dkim.php', [
                        'selector'  => $selector,
                        'action'    => 'activate',
                        'sesskey'   => sesskey()]),
                    get_string('selectoractivate', 'tool_emailutils'),
                    $confirmation,
                    [ 'class' => 'btn btn-sm btn-primary' ],
                    new pix_icon('t/hide', ''));
        }

        print $OUTPUT->render_from_template('tool_emailutils/dkimselector', $context);
    }
}
print "</table>";

if ($domaincount == 0) {
    echo $OUTPUT->notification(get_string('selectormissing', 'tool_emailutils'),  \core\notification::ERROR);
}

print html_writer::tag('div',
    get_string('dkimmanagerhelp', 'tool_emailutils', ['emailtest' => (new moodle_url('/admin/testoutgoingmailconf.php'))->out()]),
    ['class' => 'crap', 'style' => 'max-width: 40em']);

$form->display();

echo $OUTPUT->footer();
