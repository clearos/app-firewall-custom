<?php

/**
 * Custom firewall add rule view.
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

$this->lang->load('base');
$this->lang->load('firewall');
$this->lang->load('firewall_custom');

///////////////////////////////////////////////////////////////////////////////
// Form handler
///////////////////////////////////////////////////////////////////////////////

if ($line >= 0) {
    $form = 'firewall_custom/add_edit/' . $line;
    $buttons = array(
        form_submit_update('submit_standard', 'high'),
        anchor_cancel('/app/firewall_custom')
    );
} else {
    $form = 'firewall_custom/add_edit/';
    $buttons = array(
        form_submit_add('submit_standard', 'high'),
        anchor_cancel('/app/firewall_custom')
    );
}

///////////////////////////////////////////////////////////////////////////////
// Form
///////////////////////////////////////////////////////////////////////////////

echo form_open($form);
echo form_header(lang('firewall_rule'));

echo field_input('entry', $entry, lang('firewall_rule'));
echo field_input('description', $description, lang('base_description'));
echo field_toggle_enable_disable('enabled', $enabled, lang('base_status'));

echo field_button_set($buttons);

echo form_footer();
echo form_close();
