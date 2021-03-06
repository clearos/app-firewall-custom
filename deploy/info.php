<?php

/////////////////////////////////////////////////////////////////////////////
// General information
/////////////////////////////////////////////////////////////////////////////

$app['basename'] = 'firewall_custom';
$app['version'] = '2.4.1';
$app['release'] = '1';
$app['vendor'] = 'ClearFoundation';
$app['packager'] = 'ClearFoundation';
$app['license'] = 'GPLv3';
$app['license_core'] = 'LGPLv3';
$app['summary'] = lang('firewall_custom_summary');
$app['description'] = lang('firewall_custom_app_description');
$app['tooltip'] = array(
    lang('firewall_custom_help_dragging'),
    lang('firewall_custom_help_iptables_constant')
);

/////////////////////////////////////////////////////////////////////////////
// App name and categories
/////////////////////////////////////////////////////////////////////////////

$app['name'] = lang('firewall_custom_app_name');
$app['category'] = lang('base_category_network');
$app['subcategory'] = lang('base_subcategory_firewall');

/////////////////////////////////////////////////////////////////////////////
// Packaging
/////////////////////////////////////////////////////////////////////////////

$app['requires'] = array(
    'app-firewall >= 1:2.2.15',
    'app-network-core >= 1:1.5.1',
    'app-base-core >= 1:1.6.5'
);

$app['core_file_manifest'] = array(
    'custom' => array(
        'target' => '/etc/clearos/firewall.d/custom',
        'mode' => '0755',
        'config' => TRUE,
        'config_params' => 'noreplace'
    )
);
$app['delete_dependency'] = array(
    'app-firewall-custom-core'
);
