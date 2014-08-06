<?php

/**
 * Custom firewall summary view.
 *
 * @category   apps
 * @package    firewall-custom
 * @subpackage views
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
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
// Warnings
///////////////////////////////////////////////////////////////////////////////

if ($panic)
    $this->load->view('firewall/panic');

if ($network_mode == Network::MODE_TRUSTED_STANDALONE)
    $this->load->view('network/firewall_verify');

///////////////////////////////////////////////////////////////////////////////
// Headers
///////////////////////////////////////////////////////////////////////////////

$headers = array(
    lang('base_description'),
    lang('firewall_rule'),
    lang('base_priority')
);

///////////////////////////////////////////////////////////////////////////////
// Anchors 
///////////////////////////////////////////////////////////////////////////////

$anchors = array(anchor_add('/app/firewall_custom/add_edit'));

///////////////////////////////////////////////////////////////////////////////
// Rules
///////////////////////////////////////////////////////////////////////////////

$counter = 0;
foreach ($rules as $rule) {
    $key = $rule['protocol'] . '/' . $rule['port'];
    $state = ($rule['enabled']) ? 'disable' : 'enable';
    $state_anchor = 'anchor_' . $state;

    $item['title'] = $rule['description'];
    $item['current_state'] = (bool)$rule['enabled'];

    $priority_buttons = array();
    if ($counter > 0)
        $priority_buttons[] = anchor_custom('/app/firewall_custom/priority/' . $rule['line'] . '/1', '+');
    if ($counter < count($rules) - 1)
        $priority_buttons[] = anchor_custom('/app/firewall_custom/priority/' . $rule['line'] . '/-1', '-');

    if (empty($priority_buttons))
        $priority = '---';
    else
        $priority = button_set($priority_buttons);

    $item['anchors'] = button_set(
        array(
            $state_anchor('/app/firewall_custom/toggle/' . $rule['line']),
            anchor_edit('/app/firewall_custom/add_edit/' . $rule['line']),
            anchor_delete('/app/firewall_custom/delete/' . $rule['line'])
        )
    );
    $brief = $rule['entry'];
    if (strlen($brief) > 50)
        $brief = "<a href='#' class='view_rule' id='rule_id_" . $rule['line'] . "'>" . substr($rule['entry'], 0, 50) . '...</a>';
    $item['details'] = array(
        $rule['description'],
        $brief,
        $priority,
    );

    $items[] = $item;
    $js[] = "rules['rule_id_" . $rule['line'] . "'] = '" . $rule['entry'] . "';";
    $counter++;
}

///////////////////////////////////////////////////////////////////////////////
// Summary table
///////////////////////////////////////////////////////////////////////////////

$options = array (
    'sort' => FALSE,
    'id' => 'summary_rule_table',
    'row-enable-disable' => TRUE
);

echo summary_table(
    lang('firewall_rules'),
    $anchors,
    $headers,
    $items,
    $options
);
echo "<script type='text/javascript'>var rules = new Array();\n";
foreach ($js as $line) {
    echo $line . "\n";
}
echo "</script>";
