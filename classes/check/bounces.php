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
 * User bounces check.
 *
 * @package    tool_emailutils
 * @author     Benjamin Walker <benjaminwalker@catalyst-au.net>
 * @copyright  Catalyst IT 2024
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

namespace tool_emailutils\check;
use \tool_emailutils\helper;
use core\check\check;
use core\check\result;

/**
 * User bounces check.
 *
 * @package    tool_emailutils
 * @author     Benjamin Walker <benjaminwalker@catalyst-au.net>
 * @copyright  Catalyst IT 2024
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class bounces extends check {

    /**
     * A link to a place to action this
     *
     * @return \action_link|null
     */
    public function get_action_link(): ?\action_link {
        return new \action_link(
            new \moodle_url('/admin/tool/emailutils/bounces.php'),
            get_string('reportbounces', 'tool_emailutils'));
    }

    /**
     * Get Result.
     *
     * @return result
     */
    public function get_result() : result {
        global $DB, $CFG, $OUTPUT;

        $details = '';

        [$handlebounces, $minbounces, $bounceratio] = helper::get_bounce_config();
        if (empty($handlebounces)) {
            $status = result::NA;
            $summary = get_string('check:bounces:disabled', 'tool_emailutils');
            $details = $summary;
        } else {
            $domainsql = $DB->sql_substr('LOWER(u.email)', $DB->sql_position("'@'", 'u.email') . ' + 1');
            $sql = "SELECT up.userid, up.value AS bouncecount, up2.value AS sendcount, $domainsql AS domain
                      FROM {user_preferences} up
                 LEFT JOIN {user_preferences} up2 ON up2.name = 'email_send_count' AND up.userid = up2.userid
                      JOIN {user} u ON u.id = up.userid
                     WHERE up.name = 'email_bounce_count' AND CAST(up.value AS INTEGER) > :threshold";
            // Start with a threshold of 1 as that was a historical default for manually created users.
            $bounces = $DB->get_records_sql($sql, ['threshold' => 0]);
            $userswithbounces = count($bounces);

            // Split bounces into 3 groups based on whether they meet bounce threshold criteria.
            $breakdown = [];
            $total = (object) [
                'overthreshold' => 0,
                'overbouncereq' => 0,
                'underbouncereq' => 0,
            ];
            foreach ($bounces as $bounce) {
                $bouncereq = $bounce->bouncecount >= $minbounces;
                // If there's no send count due to MDL-73798, treat it as 1.
                $sendcount = !empty($bounce->sendcount) ? $bounce->sendcount : 1;
                $ratioreq = $bounce->bouncecount / $sendcount >= $bounceratio;

                if (!isset($breakdown[$bounce->domain])) {
                    $breakdown[$bounce->domain] = (object) [
                        'domain' => $bounce->domain,
                        'overthreshold' => 0,
                        'overbouncereq' => 0,
                        'underbouncereq' => 0,
                    ];
                }
                if ($bouncereq && $ratioreq) {
                    $total->overthreshold++;
                    $breakdown[$bounce->domain]->overthreshold++;
                } else if (!$ratioreq) {
                    $total->overbouncereq++;
                    $breakdown[$bounce->domain]->overbouncereq++;
                } else {
                    $total->underbouncereq++;
                    $breakdown[$bounce->domain]->underbouncereq++;
                }
            }

            // Order breakdown by users over threshold, overbouncereq, then underbouncereq.
            usort($breakdown, function ($a, $b) {
                if ($a->overthreshold != $b->overthreshold) {
                    return $b->overthreshold - $a->overthreshold;
                }
                if ($a->overbouncereq != $b->overbouncereq) {
                    return $b->overbouncereq - $a->overbouncereq;
                }
                return $b->underbouncereq - $a->underbouncereq;
            });

            if (!$userswithbounces) {
                $status = result::OK;
                $summary = get_string('check:bounces:none', 'tool_emailutils');
            } else if (!$total->overthreshold) {
                $status = result::OK;
                $summary = get_string('check:bounces:underthreshold', 'tool_emailutils');
            } else {
                $status = result::WARNING;
                $summary = get_string('check:bounces:overthreshold', 'tool_emailutils', [
                    'count' => $total->overthreshold,
                    'minbounces' => $minbounces,
                    'bounceratio' => $bounceratio,
                ]);
            }

            // Render config used for calculating threshold.
            $details = $OUTPUT->render_from_template('tool_emailutils/bounce_config', [
                'handlebounces' => $handlebounces,
                'minbounces' => $minbounces,
                'bounceratio' => $bounceratio,
                'breakdown' => $breakdown,
                'total' => $total,
            ]);
        }

        return new result($status, $summary, $details);
    }

}
