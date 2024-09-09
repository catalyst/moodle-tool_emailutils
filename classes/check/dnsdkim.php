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
 * DNS Email DKIM check.
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
 * DNS Email DKIM check.
 *
 * @package    tool_emailutils
 * @author     Brendan Heywood <brendan@catalyst-au.net>
 * @copyright  Catalyst IT 2024
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class dnsdkim extends check {

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
        $details .= "<p>No reply domain: <code>$noreplydomain</code></p>";

        $selector = $dns->get_dkim_selector();

        // Does it have an DKIM record at all?
        if (empty($selector)) {
            $summary = 'Missing DKIM selector';
            $details .= "<p>$domain does not have an DKIM selector set in \$CFG->emaildkimselector.</p>";
            return new result(result::ERROR, $summary, $details);
        }

        $dkimdomain = $dns->get_dkim_dns_domain($selector, $noreplydomain);
        $details .= "<p>DKIM domain: <code>$dkimdomain</code></p>";

        $dkim = $dns->get_dkim_record($selector);

        if (empty($dkim)) {
            $details .= "<p>DKIM record is missing with selector '$selector'</p>";
            $status = result::ERROR;
            $summary = "DKIM DNS record missing for selector '$selector'";
        } else {
            $details .= "<p>DKI record:<br><code>$dkim</code></p>";
            $status = result::OK;
            $summary = "DKIM record exists with selector '$selector'";
        }

        return new result($status, $summary, $details);
    }
}
