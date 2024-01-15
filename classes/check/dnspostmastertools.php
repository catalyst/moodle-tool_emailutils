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
 * DNS Email post master tools
 *
 * @package    tool_emailutils
 * @author     Brendan Heywood <brendan@catalyst-au.net>
 * @copyright  Catalyst IT 2024
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

namespace tool_emailutils\check;
use core\check\check;
use core\check\result;
use tool_emailutils\dns_util;

/**
 * DNS Post master tools
 *
 * @package    tool_emailutils
 * @author     Brendan Heywood <brendan@catalyst-au.net>
 * @copyright  Catalyst IT 2024
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class dnspostmastertools extends check {

    /**
     * A link to a place to action this
     *
     * @return \action_link|null
     */
    public function get_action_link(): ?\action_link {
        return new \action_link(
            new \moodle_url('/admin/settings.php?section=tool_emailutils_dns'),
            get_string('postmastertools', 'tool_emailutils'));
    }

    /**
     * Get Result.
     *
     * @return result
     */
    public function get_result() : result {
        global $DB, $CFG;

        $url = new \moodle_url($CFG->wwwroot);
        $domain = $url->get_host();

        $details = '<table class="admintable generaltable table-sm w-auto">';
        $details .= '<tr><th>Vendor</th><th>Token</th><th>Confirmed</th><th>Url</th></tr>';
        $status = result::INFO;
        $summary = '';

        $dns = new dns_util();

        $noreply = $dns->get_noreply();
        $details .= "<p>No reply email: <code>$noreply</code></p>";

        $noreplydomain = $dns->get_noreply_domain();

        // Later intend to support other email providers.
        $vendors = ['google'];
        $vendornames = join(', ', $vendors);
        $summary = "Post master tools setup for $vendornames ";

        $status = result::INFO;

        foreach ($vendors as $vendor) {

            $token = get_config('tool_emailutils', 'postmaster' . $vendor . 'token');
            $record = $dns->get_matching_dns_record($noreplydomain, $token);

            if (empty($token)) {
                $summary = "Post master tools not setup with $vendor";
                $status = result::WARNING;
                $confirmed = 'N/A';
            } else if (empty($record)) {
                $confirmed = 'No';
                $details .= "<p>$token was not found in TXT records</p>";
                $status = result::ERROR;
                $summary = "Post master tools not verified with $vendor";
            } else {
                $confirmed = 'Yes';
                $status = result::OK;
            }
            $details .= "<tr><td>$vendor</td><td>$token</td><td>$confirmed</td></tr>";
        }
        $details .= '</table>';

        return new result($status, $summary, $details);
    }

}
