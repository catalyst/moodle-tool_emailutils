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
 * DNS Email Noreply check.
 *
 * @package    tool_emailutils
 * @author     Benjamin Walker <benjaminwalker@catalyst-au.net>
 * @copyright  Catalyst IT 2024
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_emailutils\check;
use core\check\check;
use core\check\result;
use tool_emailutils\dns_util;

/**
 * DNS Email Noreply check.
 *
 * @package    tool_emailutils
 * @author     Benjamin Walker <benjaminwalker@catalyst-au.net>
 * @copyright  Catalyst IT 2024
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class dnsnoreply extends check {

    /**
     * A link to a place to action this
     *
     * @return \action_link|null
     */
    public function get_action_link(): ?\action_link {
        return new \action_link(
            new \moodle_url('/admin/settings.php?section=outgoingmailconfig'),
            get_string('outgoingmailconfig', 'core_admin'));
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

        $details .= "<p>LMS domain: <code>$domain</code></p>";

        $primarydomain = $dns->get_primary_domain($domain);

        if ($noreplydomain == $domain) {
            $status = result::OK;
            $summary = "LMS is same as noreply domain";
        } else if (strpos($domain, '.' . $noreplydomain) !== false) {
            $status = result::OK;
            $summary = "LMS is a subdomain of noreply domain";
        } else if (strpos($noreplydomain, '.' . $domain) !== false) {
            $status = result::OK;
            $summary = "Noreply domain is a subdomain of LMS";
        } else if ($noreply == $primarydomain || strpos($noreplydomain, '.' . $primarydomain) !== false) {
            $summary = "LMS and noreply domain have a shared domain";
        } else {
            $summary = "LMS and noreply domain have nothing in common";
        }

        return new result($status, $summary, $details);
    }
}
