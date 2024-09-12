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

namespace tool_emailutils;

/**
 * Tests for DKIM default suffix.
 *
 * @package    tool_emailutils
 * @author     Benjamin Walker <benjaminwalker@catalyst-au.net>
 * @copyright  Catalyst IT 2024
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class suffix_test extends \advanced_testcase {

    /**
     * Test suffix.
     *
     * @param string $lmsdomain lms domain
     * @param string $noreplydomain noreply domain
     * @param string $primarydomain primary domain
     * @param string $selectorsuffix selector suffix
     * @dataProvider dns_comparisons
     * @covers \tool_emailutils\dns_util::get_selector_suffix
     * @covers \tool_emailutils\dns_util::get_primary_domain
     * @covers \tool_emailutils\dns_util::get_noreply_domain
     */
    public function test_suffix(string $lmsdomain, string $noreplydomain, string $primarydomain, string $selectorsuffix) {
        $this->resetAfterTest();
        $mock = $this->getMockBuilder('\tool_emailutils\dns_util')
            ->setMethods(['get_primary_domain', 'get_noreply_domain'])
            ->getMock();

        $mock->expects($this->any())
            ->method('get_primary_domain')
            ->willReturn($primarydomain);

        $mock->expects($this->any())
            ->method('get_noreply_domain')
            ->willReturn($noreplydomain);

        $selector = $mock->get_selector_suffix($lmsdomain);
        $this->assertEquals($selectorsuffix, $selector);
    }

    /**
     * Data provider used to test comparisons between different domains.
     *
     * @return array
     */
    public static function dns_comparisons(): array {
        return [
            'no subdomain' => [
                'client.com',
                'client.com',
                'client.com',
                '',
            ],
            'subdomain' => [
                'lms.client.com',
                'client.com',
                'client.com',
                'lms',
            ],
            'another subdomain' => [
                'moodle.client.com',
                'client.com',
                'client.com',
                'moodle',
            ],
            'multiple subdomain' => [
                'lms.moodle.client.com',
                'client.com',
                'client.com',
                'lms-moodle',
            ],
            'longer tld' => [
                'lms.moodle.client.nsw.gov.au',
                'client.nsw.gov.au',
                'client.nsw.gov.au',
                'lms-moodle',
            ],
            'www only subdomain' => [
                'www.client.com',
                'client.com',
                'client.com',
                '',
            ],
            'www multiple subdomain' => [
                'www.moodle.client.com',
                'client.com',
                'client.com',
                'moodle',
            ],
            'different subdomain' => [
                'lms.client.com',
                'mail.client.com',
                'client.com',
                'lms',
            ],
            'noreply contains part of subdomain' => [
                'lms.moodle.client.com',
                'moodle.client.com',
                'client.com',
                'lms',
            ],
            'noreply subdomain of lms' => [
                'lms.moodle.client.com',
                'email.lms.moodle.client.com',
                'client.com',
                '',
            ],
            'unable to identify primary domain' => [
                'lms.moodle.client.com',
                'client.com',
                'lms.moodle.client.com',
                'lms-moodle',
            ],
            'different noreply' => [
                'lms.moodle.client.com',
                'vendor.com',
                'client.com',
                'lms-moodle-client-com',
            ],
            'different noreply with no subdomain' => [
                'client.com',
                'vendor.com',
                'client.com',
                'client-com',
            ],
        ];
    }
}
