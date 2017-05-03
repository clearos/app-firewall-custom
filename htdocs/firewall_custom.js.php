<?php

/**
 * Javascript helper for Firewall_Custom.
 *
 * @category   apps
 * @package    firewall-custom
 * @subpackage javascript
 * @author     ClearCenter <developer@clearcenter.com>
 * @copyright  2011 ClearCenter
 * @license    http://www.clearcenter.com/Company/terms.html ClearSDN license
 * @link       http://www.clearcenter.com/support/documentation/clearos/firewall_custom/
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
// B O O T S T R A P
///////////////////////////////////////////////////////////////////////////////

$bootstrap = getenv('CLEAROS_BOOTSTRAP') ? getenv('CLEAROS_BOOTSTRAP') : '/usr/clearos/framework/shared';
require_once $bootstrap . '/bootstrap.php';

clearos_load_language('base');
clearos_load_language('firewall');

header('Content-Type: application/x-javascript');

?>

var lang_firewall_rule = '<? echo lang('firewall_rule') ?>';
var lang_warning = '<? echo lang('base_warning') ?>';

$(document).ready(function() {
    if ($('#summary_rule_ipv4').length != 0) {
        table_ipv4 = get_table_summary_rule_ipv4();
    }
    if ($('#summary_rule_ipv6').length != 0) {
        table_ipv6 = get_table_summary_rule_ipv6();
    }

    $('a.view_rule').on('click', function (e) {
        e.preventDefault();
        var options = new Object();
        options.type = 'info';
        var rule_name = $(this).closest('tr').find('td:nth-child(2)').html();
        var rule_display = $('span:first-child', $(this).closest('tr').find('td:nth-child(3)')).html();
        clearos_dialog_box('rule', lang_firewall_rule + ' - ' + rule_name, rule_display, options);
    });
    $('tbody.ui-sortable').sortable({
        update: function(event, ui) {
            type = 'ipv4';
            if ($(this).parent().attr('id').match('ipv6') != null)
                type = 'ipv6';
            var rules = [];
            $('#summary_rule_' + type + ' tr').each(function(i, tr) {
                var rule = $('td:nth-child(3) div', $(this)).html();
                if (rule != undefined)
                    rules.push(rule);
            });
            set_rules(type, rules);
        }
    });
});

function set_rules(type, rules) {
    $.ajax({
        type: 'POST',
        dataType: 'json',
        url: '/app/firewall_custom/' + type + '/set_rules',
        data: 'ci_csrf_token=' + $.cookie('ci_csrf_token') + '&type=' + type + '&rules=' + JSON.stringify(rules),
        success: function(data) {
            if (data.code == 0) {
                return;
            } else {
                clearos_dialog_box('error', lang_warning, data.errmsg);
            }
        },
        error: function(xhr, text, err) {
            clearos_dialog_box('error', lang_warning, xhr.responseText.toString());
        }
    });
}

// vim: syntax=javascript ts=4
