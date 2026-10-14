<?php

declare(strict_types=1);

require dirname(__DIR__, 2).'/vendor/autoload.php';

use Lebensbaum\ContaoDomainManagerBundle\Frontend\WorkspacePresentation;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

$root = dirname(__DIR__, 2);
$output = $root.'/build/browser';
if (!is_dir($output)) {
    mkdir($output, 0755, true);
}
$clean = static function (string $source): string {
    $source = preg_replace('/\{%\s*add\b.*?\{%\s*endadd\s*%\}/s', '', $source);
    return preg_replace('/\{%\s*(?:extends|use)\b.*?%\}/s', '', $source);
};
$loader = new ArrayLoader([
    'updates.html.twig' => $clean(file_get_contents($root.'/contao/templates/content_element/domain_manager_updates.html.twig')),
    '@Contao/components/domain_manager_workspace_controls.html.twig' => $clean(file_get_contents($root.'/contao/templates/components/domain_manager_workspace_controls.html.twig')),
    '@Contao/components/domain_manager_workspace_navigation.html.twig' => '',
]);
$twig = new Environment($loader, ['autoescape' => 'html']);
$thumbnail = 'data:image/svg+xml;base64,'.base64_encode(file_get_contents($root.'/public/branding/maintend-logo.svg'));
$styles = '';
foreach (['domain-manager-updates.css', 'domain-manager-workspace.css'] as $file) {
    $styles .= file_get_contents($root.'/public/css/'.$file);
}
$scripts = '';
foreach (['domain-manager-updates.js', 'domain-manager-workspace.js'] as $file) {
    $scripts .= '<script>'.file_get_contents($root.'/public/js/'.$file).'</script>';
}
foreach ([false, true] as $pro) {
    $rows = [];
    for ($id = 1; $id <= 80; ++$id) {
        $pid = ($id - 1) % 60 + 1;
        $parent = sprintf('kunde-%02d.example', $pid);
        $domain = $id > 60 ? 'mirror.'.$parent : $parent;
        $state = !$pro ? 'locked' : ($id <= 12 ? 'available' : ($id === 13 ? 'error' : ($id === 14 ? 'check_failed' : 'current')));
        $gap = $pro && $id === 1 ? 5 : 0;
        $rows[] = [
            'id' => $id, 'pid' => $pid, 'domain' => $domain, 'parent_domain' => $parent,
            'environment' => $id > 60 ? 'mirror' : 'live', 'environment_label' => $id > 60 ? 'Mirror' : 'Live',
            'thumbnail_path' => $thumbnail, 'contao_version' => $gap > 0 ? '5.7.10' : '5.7.15',
            'target_contao_version' => $state === 'available' ? '5.7.15' : '',
            'update_state' => $state, 'update_state_label' => $state,
            'update_release_gap' => $gap, 'frontend_metadata' => [], 'update_details' => null,
            'frontend_actions' => !$pro ? [] : array_merge([
                ['name' => 'update-check', 'label' => 'Update prüfen', 'url' => '/pro/check/'.$id, 'method' => 'post', 'variant' => 'primary', 'confirm' => ''],
            ], $state !== 'available' ? [] : [
                ['name' => 'update-install', 'label' => 'Aktualisieren', 'url' => '/pro/install/'.$id, 'method' => 'post', 'variant' => 'warning', 'confirm' => 'Fixture'],
            ]),
        ];
    }
    $presentation = new WorkspacePresentation();
    $html = $twig->render('updates.html.twig', [
        'installations' => $rows, 'pro_enabled' => $pro, 'workspace' => $presentation->features(['pro_available' => $pro, 'pro_authorized' => $pro]),
        'workspace_counts' => $presentation->summarize($rows), 'workspace_groups' => 60,
        'check_failure_count' => $pro ? 1 : 0, 'error_count' => $pro ? 1 : 0,
        'result_installation_id' => 0, 'result_message' => '', 'backup_result_message' => '',
        'backup_result_installation_id' => 0, 'backup_result_error' => false, 'install_status' => '', 'check_status' => '',
        'contao' => ['request_token' => 'fixture'],
    ]);
    file_put_contents($output.'/'.($pro ? 'pro' : 'free').'.html', '<!doctype html><html><head><meta charset="utf-8"><style>body{margin:0;padding:20px;font-family:Arial,sans-serif;background:#f4f7f8}*{box-sizing:border-box}'.$styles.'</style></head><body>'.$html.$scripts.'</body></html>');
}
