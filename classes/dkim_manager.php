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
 * DKIM manager
 *
 * This loads, verifies and can auto create DKIM pairs of certificates
 * Code largely adapted from PHPMailer
 *
 * @package    tool_emailutils
 * @copyright  2023 onwards Catalyst IT {@link http://www.catalyst-eu.net/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Brendan Heywood <brendan@catalyst-au.net>
 */

namespace tool_emailutils;

/**
 * DKIM manager
 */
class dkim_manager {

    /** @var Domain */
    protected $domain;

    /** @var Selector */
    protected $selector;

    /** @var Private key */
    protected $privatekey;

    /** @var Public key */
    protected $publickey;

    /** @var DNS record */
    protected $dnsrecord;

    /** Digest algorythm */
    const DIGEST_ALG = 'sha256';

    /**
     * Create or load the certificates for a domain and selector
     * @param string $domain domain
     * @param string $selector
     * @param bool $autocreate Should this autocreate cert pairs if they don't exist?
     */
    public function __construct($domain, $selector, $autocreate = false) {
        $this->domain = $domain;
        $this->selector = $selector;

        $privatekeyfile = $this->get_private_key_path();
        $publickeyfile  = $this->get_public_key_path();

        if (!file_exists($privatekeyfile) && $autocreate) {
            $this->get_base_path(true);
            // Create a 2048-bit RSA key with an SHA256 digest.
            $pk = openssl_pkey_new(
                [
                    'digest_alg' => self::DIGEST_ALG,
                    'private_key_bits' => 2048,
                    'private_key_type' => OPENSSL_KEYTYPE_RSA,
                ]
            );

            // Save both keys.
            openssl_pkey_export_to_file($pk, $privatekeyfile);
            $details = openssl_pkey_get_details($pk);
            file_put_contents($publickeyfile, $details['key']);
        }

        $this->privatekey = file_get_contents($privatekeyfile);
        $this->publickey  = file_get_contents($publickeyfile);
    }

    /**
     * Get the domain file path
     */
    public function get_domain_path() {
        global $CFG;
        return $CFG->dataroot . '/dkim/' . $this->domain . '/';
    }

    /**
     * Get the domain file path
     * @param bool $create auto create the directories
     */
    public function get_base_path($create = false) {
        $certdir = $this->get_domain_path();
        if ($create) {
            @mkdir($certdir, 0777, true);
        }
        return $certdir . '/' . $this->selector;
    }

    /**
     * Get the private key file path
     */
    public function get_private_key_path() {
        return $this->get_base_path() . '.private';
    }

    /**
     * Get the public key file path
     */
    public function get_public_key_path() {
        return $this->get_base_path() . '.public';
    }

    /**
     * Get the DNS record file path
     */
    public function get_dns_record_path() {
        return $this->get_base_path() . '.txt';
    }

    /**
     * Get the domain the DKIM record should be stored at
     */
    public function get_dns_domain() {
        return "{$this->selector}._domainkey.{$this->domain}";
    }

    /**
     * Get the key of the DKIM txt record
     */
    public function get_dns_key() {
        return $this->get_dns_domain() . ' IN TXT';
    }

    /**
     * Get the value of the DKIM record
     *
     * This loads the public key and then stores the DNS record in a file.
     */
    public function get_dns_value() {
        if (!empty($this->dnsrecord)) {
            return $this->dnsrecord;
        }

        // TODO add support for records added by open dkim
        // These do not include the public key in the normal format, only in the DNS value format.

        if (empty($this->publickey)) {
            return "ERROR: Can't find public key";
        }

        $dnsvalue = 'v=DKIM1;';
        $dnsvalue .= ' h=' . self::DIGEST_ALG . ';'; // Hash algorythm.
        $dnsvalue .= ' t=s;';   // No sub domains allowed.
        $dnsvalue .= ' k=rsa;'; // Key type.
        $dnsvalue .= ' p=';     // Public key.

        $publickey = $this->publickey;
        $publickey = preg_replace('/^-+.*?-+$/m', '', $publickey); // Remove PEM wrapper.
        $publickey = str_replace(["\r", "\n"], '', $publickey); // Strip line breaks.
        $dnsvalue .= $publickey;

        $this->dnsrecord = trim($dnsvalue);

        return $this->dnsrecord;
    }

    /**
     * Get a chunked version of the DKIM record
     *
     * Strip and split the key into smaller parts and format for DNS as many systems
     * don't like long TXT entries but are OK if it's split into 255-char chunks.
     */
    public function get_dns_value_chunked() {

        $dnsvalue = '';
        $rawvalue = $this->get_dns_value();

        // Split into chunks.
        $keyparts = str_split($rawvalue, 253); // Becomes 255 when quotes are included.
        // Quote each chunk.
        foreach ($keyparts as $keypart) {
            $dnsvalue .= '"' . trim($keypart) . '" ';
        }

        return $dnsvalue;

    }

    /**
     * Get the alternate escaped version of the DKIM record
     *
     * Some DNS servers don't like ;(semi colon) chars unless backslash-escaped
     */
    public function get_dns_value_escaped() {

        $value = $this->get_dns_value_chunked();
        $value = str_replace(';', '\;', $value);
        return $value;

    }

    /**
     * Delete all info about a selector
     */
    public function delete_selector() {
        $privatekeyfile = $this->get_private_key_path();
        $publickeyfile  = $this->get_public_key_path();
        $dnsrecordfile  = $this->get_dns_record_path();
        $domaindir = $this->get_domain_path();

        @unlink($privatekeyfile);
        @unlink($publickeyfile);
        @unlink($dnsrecordfile);
        @rmdir($domaindir);
    }
}
