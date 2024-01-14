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
 * SPF utils
 *
 * @package    tool_emailutils
 * @copyright  Catalyst IT 2024
 * @author     Brendan Heywood <brendan@catalyst-au.net>
 * @copyright  2023 onwards Catalyst IT {@link http://www.catalyst-eu.net/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_emailutils;

/**
 * SPF utils
 *
 * @package    tool_emailutils
 * @copyright  Catalyst IT 2024
 * @author     Brendan Heywood <brendan@catalyst-au.net>
 * @copyright  2023 onwards Catalyst IT {@link http://www.catalyst-eu.net/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class dns_util {

    /**
     * Get no reply
     * @return string email
     */
    public function get_noreply() {
        global $CFG;

        return $CFG->noreplyaddress;
    }

    /**
     * Get no reply domain
     * @return string domain
     */
    public function get_noreply_domain() {
        global $CFG;

        $noreplydomain = substr($CFG->noreplyaddress, strpos($CFG->noreplyaddress, '@') + 1);
        return $noreplydomain;
    }

    /**
     * Get spf txt record contents
     * @return string txt record
     */
    public function get_spf_record() {

        $domain = $this->get_noreply_domain();
        $records = @dns_get_record($domain, DNS_TXT);
        if (empty($records)) {
            return '';
        }
        foreach ($records as $record) {
            $txt = $record['txt'];
            if (substr($txt, 0, 6) == 'v=spf1') {
                return $txt;
            }
        }
        return '';
    }

    /**
     * Get spf txt record contents
     * @return string url
     */
    public function get_mxtoolbox_spf_url() {
    }


    /**
     * Returns the include if matched
     *
     * The include can have a wildcard and this will return the actual matched value.
     * @param string include domain
     * @return string matched include
     */
    public function include_present(string $include) {
        $txt = $this->get_spf_record();

        $escaped = preg_quote($include);

        // Allow a * wildcard match.
        $escaped = str_replace('\*', '\S*', $escaped);
        $regex = "/include:($escaped)/U";
        if (preg_match($regex, $txt, $matches)) {
            return $matches[1];
        }

        return '';
    }

    /**
     * Get DKIM selector
     * @return string txt record
     */
    public function get_dkim_selector() {
        global $CFG;
        return $CFG->emaildkimselector;
    }

    /**
     * Get DKIM txt record contents
     * @return string txt record
     */
    public function get_dkim_dns_domain($selector, $domain) {
        return "$selector._domainkey.$domain";
    }

    /**
     * Get DKIM txt record contents
     * @return string txt record
     */
    public function get_dkim_record($selector) {

        $domain = $this->get_noreply_domain();
        $dns = $this->get_dkim_dns_domain($selector, $domain);

        $records = @dns_get_record($dns, DNS_TXT);
        if (empty($records)) {
            return '';
        }
        return $records[0]['txt'];
    }

    /**
     * Get DKIM txt record contents
     * @return string txt record
     */
    public function get_dmarc_dns_record() {
        $domain = $this->get_noreply_domain();

        while ($domain) {
            $dmarcdomain = '_dmarc.' . $domain;
            $records = @dns_get_record($dmarcdomain, DNS_TXT);
            if (!empty($records)) {
                return [$dmarcdomain, $records[0]['txt']];
            }

            $parts = explode('.', $domain);
            $domain = join('.', array_slice($parts, 1));

        }
        return ['', ''];
    }

    /**
     * Get MX record contents
     * @return string txt record
     */
    public function get_mx_record($domain) {

        $records = @dns_get_record($domain, DNS_MX);
        if (empty($records)) {
            return;
        }
        usort($records, function($a, $b) {
            if ($a['pri'] == $b['pri']) {
                return $a['target'] <=> $b['target'];
            }
            return $a['pri'] <=> $b['pri'];
        });
        return $records;
    }
}

