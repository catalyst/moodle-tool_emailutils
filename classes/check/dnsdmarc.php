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
 * DNS Email DMARC check.
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
class dnsdmarc extends check {

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
        $details .= "<p>Start looking in domain: <code>$noreplydomain</code></p>";

        [$dmarcdomain, $dmarc, $policy] = $dns->get_dmarc_dns_record();

        if (empty($dmarc)) {
            $details .= "<p>DMARC record is missing</p>";
            $status = result::ERROR;
            $summary = "DMARC DNS record missing";
        } else {
            $details .= "<p>DMARC record found on domain <code>$dmarcdomain</code><br><code>$dmarc</code></p>";
            $status = result::OK;
            $summary = "DMARC record exists with policy '$policy'";
        }

        return new result($status, $summary, $details);
    }
}
