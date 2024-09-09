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
     * Attempts to extract the primary domain from a domain
     *
     * This may not always be clear, if there is any confusion return the full domain.
     * @param string $domain domain to check
     * @return string primary domain
     */
    public function get_primary_domain($domain) {
        $originaldomain = $domain;

        // Checks for the first domain that has a NS record.
        while ($domain) {
            $records = @dns_get_record($domain, DNS_NS);
            if (!empty($records)) {
                return $domain;
            }
            $parts = explode('.', $domain);
            // A domain should always have more than 1 part.
            if (count($parts) >= 2) {
                break;
            }
            $domain = join('.', array_slice($parts, 1));
        }
        return $originaldomain;
    }

    /**
     * Attempts to extract the subdomains from a domain
     *
     * This may not always be clear, if there is any confusion return known subdomains or a blank string.
     * @param string $domain domain to check
     * @return string subdomains
     */
    public function get_subdomains($domain) {
        $primarydomain = $this->get_primary_domain($domain);
        return rtrim(strstr($domain, $primarydomain, true), '.');
    }

    /**
     * Get spf txt record contents
     * @param string $domain specify a different domain
     * @return string txt record
     */
    public function get_spf_record($domain = '') {

        if (empty($domain)) {
            $domain = $this->get_noreply_domain();
        }

        $records = @dns_get_record($domain, DNS_TXT);
        if (empty($records)) {
            return '';
        }
        foreach ($records as $record) {
            $txt = $record['txt'];
            if (preg_match('/v=spf1 redirect=(\S*)/', $txt, $matches)) {
                return $this->get_spf_record($matches[1]);
            }
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
     * @param string $include include domain
     * @return string matched include
     */
    public function include_present(string $include) {
        $txt = $this->get_spf_record();

        $escaped = preg_quote($include);

        // Allow a * wildcard match.
        $escaped = str_replace('\*', '\S*', $escaped);
        $regex = "/include:($escaped)\s/U";
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
     * @param string $selector DKIM selector
     * @param string $domain DKIM domain
     * @return string txt record
     */
    public function get_dkim_dns_domain($selector, $domain) {
        return "$selector._domainkey.$domain";
    }

    /**
     * Get DKIM txt record contents
     * @param string $selector DKIM selector
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
     * @return array txt record
     */
    public function get_dmarc_dns_record() {
        $domain = $this->get_noreply_domain();

        while ($domain) {
            $dmarcdomain = '_dmarc.' . $domain;
            $records = @dns_get_record($dmarcdomain, DNS_TXT);
            if (!empty($records)) {
                $record = $records[0]['txt'];
                preg_match('/p\=(.*?);/', $record, $matches);

                return [
                    $dmarcdomain,
                    $record,
                    $matches[1],
                ];
            }

            $parts = explode('.', $domain);
            $domain = join('.', array_slice($parts, 1));
        }
        return ['', ''];
    }

    /**
     * Get MX record contents
     * @param string $domain domain to check
     * @return array txt record
     */
    public function get_mx_record($domain) {

        $records = @dns_get_record($domain, DNS_MX);
        if (empty($records)) {
            return [];
        }
        usort($records, function ($a, $b) {
            if ($a['pri'] == $b['pri']) {
                return $a['target'] <=> $b['target'];
            }
            return $a['pri'] <=> $b['pri'];
        });
        return $records;
    }

    /**
     * Get matching record contents
     * @param string $domain domain to check
     * @param string $match search for specific match
     * @return string txt record
     */
    public function get_matching_dns_record($domain, $match) {

        $records = @dns_get_record($domain, DNS_TXT);
        if (empty($records)) {
            return '';
        }
        foreach ($records as $record) {
            if ($record['txt'] == $match) {
                return $match;
            }
        }
        return '';
    }

    /**
     * Gets the selector suffix
     * @param string $domain check specific domain
     * @return string suffix
     */
    public function get_selector_suffix($domain = '') {
        GLOBAL $CFG;

        if (empty($domain)) {
            $url = new \moodle_url($CFG->wwwroot);
            $domain = $url->get_host();
        }

        // Determine the suffix based on the LMS domain and noreply domain.
        $primarydomain = $this->get_primary_domain($domain);
        $noreplydomain = $this->get_noreply_domain();
        if ($primarydomain == $noreplydomain) {
            // Noreply domain is same as primary domain, add all LMS subdomains.
            $suffix = $this->get_subdomains($domain);
        } else if (strpos($domain, '.' . $noreplydomain) !== false) {
            // Noreply domain includes part of the LMS subdomain, only add different subdomains.
            $suffix = str_replace('.' . $noreplydomain, '', $domain);
        } else if (strpos($noreplydomain, '.' . $domain) !== false) {
            // Noreply domain is a subdomain of LMS, domain already has all info.
            $suffix = '';
        } else if (strpos($noreplydomain, '.' . $primarydomain) !== false) {
            // Noreply domain is a different subdomain of primary domain, add all LMS subdomains.
            $suffix = $this->get_subdomains($domain);
        } else {
            // Noreply domain shares nothing in common with LMS, add entire LMS domain.
            $suffix = $domain;
        }

        // Clean the suffix to remove www and foreign language chars, and convert '.' to '-'.
        // Email filter is enough because domains don't contain the other allowed chars.
        $suffix = ltrim($suffix, 'www.');
        $suffix = trim(filter_var($suffix, FILTER_SANITIZE_EMAIL), '.');
        $suffix = str_replace('.', '-', $suffix);

        return $suffix;
    }
}

