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
 * DNS Email MX record check.
 *
 * @package    tool_emailutils
 * @author     Brendan Heywood <brendan@catalyst-au.net>
 * @copyright  Catalyst IT 2024
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_emailutils\check;
use core\check\check;
use core\check\result;
use tool_emailutils\dns_util;

/**
 * DNS Email MX record check.
 *
 * @package    tool_emailutils
 * @author     Brendan Heywood <brendan@catalyst-au.net>
 * @copyright  Catalyst IT 2024
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class dnsmx extends check {

    /**
     * A link to a place to action this
     *
     * @return \action_link|null
     */
    public function get_action_link(): ?\action_link {
        return new \action_link(
            new \moodle_url('/admin/tool/emailutils/dkim.php'),
            get_string('dkimmanager', 'tool_emailutils'));
    }

    /**
     * Get Result.
     *
     * @return result
     */
    public function get_result(): result {
        global $DB, $CFG;

        $url = new \moodle_url($CFG->wwwroot);
        $domain = $url->get_host();

        $details = '';
        $status = result::INFO;
        $summary = '';

        $dns = new dns_util();

        $noreply = $dns->get_noreply();
        $details .= "<p>No reply email: <code>$noreply</code></p>";

        $noreplydomain = $dns->get_noreply_domain();
        $details .= "<p>Looking for MX in domain: <code>$noreplydomain</code></p>";

        $mxdomains = $dns->get_mx_record($noreplydomain);

        if (empty($mxdomains)) {
            $details .= "<p>MX record is missing</p>";
            $status = result::WARNING;
            $summary = "MX DNS record missing";
        } else {
            $allmxdomains = join('<br>', array_map(function ($x) {
                return $x['target'] . ' (' . $x['pri'] . ')';
            }, $mxdomains));
            $details .= "<p>MX record found on domain <code>$noreplydomain</code> pointing to<br><code>$allmxdomains</code></p>";
            $status = result::OK;
            $summary = "MX record points to " . $mxdomains[0]['target'];
        }

        return new result($status, $summary, $details);
    }
}
