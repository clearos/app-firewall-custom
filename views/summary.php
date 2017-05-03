<?php

/**
 * Custom firewall summary view.
 *
 * @category   apps
 * @package    firewall-custom
 * @subpackage views
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011-2016 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/firewall_custom/
 */

///////////////////////////////////////////////////////////////////////////////
//
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program.  If not, see <http://www.gnu.org/licenses/>.  
//  
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
// Load dependencies
///////////////////////////////////////////////////////////////////////////////

use \clearos\apps\network\Network as Network;

$this->lang->load('firewall');
$this->lang->load('firewall_custom');

///////////////////////////////////////////////////////////////////////////////
// Headers
///////////////////////////////////////////////////////////////////////////////

$headers = array(
    lang('base_description'),
    lang('firewall_rule')
);

///////////////////////////////////////////////////////////////////////////////
// Anchors 
///////////////////////////////////////////////////////////////////////////////

$anchors = array(anchor_add('/app/firewall_custom/' . $type . '/add_edit'));

///////////////////////////////////////////////////////////////////////////////
// Rules
///////////////////////////////////////////////////////////////////////////////

foreach ($rules as $line => $rule) {
    $key = $rule['protocol'] . '/' . $rule['port'];
    $state = ($rule['enabled']) ? 'disable' : 'enable';
    $state_anchor = 'anchor_' . $state;

    $item['title'] = $rule['description'];
    $item['current_state'] = (bool)$rule['enabled'];

    $item['anchors'] = button_set(
        array(
            $state_anchor('/app/firewall_custom/' . $type . '/toggle/' . $rule['line']),
            anchor_edit('/app/firewall_custom/' . $type . '/add_edit/' . $rule['line']),
            anchor_delete('/app/firewall_custom/' . $type . '/delete/' . $rule['line'])
        )
    );
    $brief = "<span class='custom-rule'>" . $rule['entry'] . "</span><div class='theme-hidden'>" . current($rule['raw']) . "</div>";
    if (strlen($brief) > 20)
        $brief = "<a href='#' class='view_rule'>" . substr($rule['entry'], 0, 20) . "...<span class='custom-rule theme-hidden'>" . $rule['entry'] . "</span><div class='theme-hidden'>" . current($rule['raw']) . "</div></a>";
    $item['details'] = array(
        $rule['description'],
        $brief
    );

    $items[] = $item;
}

///////////////////////////////////////////////////////////////////////////////
// Summary table
///////////////////////////////////////////////////////////////////////////////

$options = array (
    'sort' => FALSE,
    'id' => 'summary_rule_' . $type,
    'class' => 'fw-sortable',
    'row-reorder' => TRUE,
    'row-enable-disable' => TRUE
);

echo summary_table(
    lang('firewall_rules') . ' - ' . ($type == 'ipv4' ? lang('firewall_ip_v4') : lang('firewall_ip_v6')),
    $anchors,
    $headers,
    $items,
    $options
);
