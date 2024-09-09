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
 * DNS Email SPF check.
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
 * DNS Email SPF check.
 *
 * @package    tool_emailutils
 * @author     Brendan Heywood <brendan@catalyst-au.net>
 * @copyright  Catalyst IT 2024
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class dnsspf extends check {

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

        $spf = $dns->get_spf_record();

        // Does it have an SPF record at all?
        if (empty($spf)) {
            $summary = 'Missing SPF record';
            $details .= "<p>$domain does not have an SPF record</p>";
            return new result(result::ERROR, $summary, $details);
        }

        $details .= "<p>SPF record:<br><code>$spf</code></p>";
        $status = result::OK;
        $summary = 'SPF record exists';

        $include = get_config('tool_emailutils', 'dnsspfinclude');
        if (!empty($include)) {
            $present = $dns->include_present($include);
            if ($present) {
                $summary = "SPF record exists and has '$present' include";
                $details .= "<p>Expecting include: <code>$include</code> and matched on <code>$present</code></p>";
            } else {
                $status = result::ERROR;
                $summary = "SPF record exists but is missing '$include' include";
                $details .= "<p>Expecting include is missing: <code>$include</code></p>";
            }
        }

        return new result($status, $summary, $details);
    }
}
