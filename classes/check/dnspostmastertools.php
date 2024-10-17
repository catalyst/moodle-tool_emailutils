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
    public function get_result(): result {
        global $OUTPUT;

        $status = result::INFO;
        $summary = '';

        $dns = new dns_util();

        $noreply = $dns->get_noreply();
        $noreplydomain = $dns->get_noreply_domain();

        // Later intend to support other email providers.
        $vendors = ['google'];
        $vendornames = join(', ', $vendors);
        $summary = "Post master tools setup for $vendornames ";

        // Check the most common user domains.
        $userdomains = $dns->get_user_domains(10);
        $userdomaininfo = [];
        foreach ($userdomains as $domain) {
            $mxrecords = $dns->get_mx_record($domain->domain);
            $allmxdomains = $dns->format_mx_records($mxrecords);
            $domainvendors = array_filter($vendors, function($vendor) use ($mxrecords) {
                foreach ($mxrecords as $mxrecord) {
                    if (strpos($mxrecord['target'], $vendor) !== false) {
                        return true;
                    }
                }
            });
            $userdomaininfo[] = (object) [
                'domain' => '@' . $domain->domain,
                'count' => $domain->count,
                'mxrecords' => $allmxdomains,
                'vendors' => implode(',', $domainvendors),
            ];
        }

        $status = result::INFO;
        $vendorinfo = [];
        foreach ($vendors as $vendor) {
            $token = get_config('tool_emailutils', 'postmaster' . $vendor . 'token');
            $record = $dns->get_matching_dns_record($noreplydomain, $token);
            $usevendor = !empty(array_filter($userdomaininfo, function($row) use ($vendor) {
                return strpos($row->vendors, $vendor) !== false;
            }));

            if (empty($token) && !$usevendor) {
                $summary = "Post master tools not required for $vendor";
                $status = result::NA;
                $confirmed = 'N/A';
            } else if (empty($token)) {
                $summary = "Post master tools not setup with $vendor";
                $status = result::INFO;
                $confirmed = 'N/A';
            } else if (empty($record)) {
                $confirmed = 'No';
                $details .= "<p>$token was not found in TXT records</p>";
                $status = result::WARNING;
                $summary = "Post master tools not verified with $vendor";
            } else {
                $confirmed = 'Yes';
                $status = result::OK;
            }

            $vendorinfo = (object) [
                'vendor' => $vendor,
                'token' => $token,
                'confirmed' => $confirmed,
                'url' => '',
            ];
        }

        $details = $OUTPUT->render_from_template('tool_emailutils/postmaster', [
            'noreply' => $noreply,
            'userdomains' => $userdomaininfo,
            'vendorinfo' => $vendorinfo,
        ]);

        return new result($status, $summary, $details);
    }
}
