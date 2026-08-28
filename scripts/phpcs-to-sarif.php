<?php
/**
 * Convierte el reporte JSON de PHP_CodeSniffer (phpcs-security-audit) a formato SARIF 2.1.0
 * para poder publicarlo en GitHub Code Scanning junto con el resultado de Psalm.
 *
 * Uso: php scripts/phpcs-to-sarif.php <entrada.json> <salida.sarif>
 */

$inputFile = $argv[1] ?? 'phpcs-results.json';
$outputFile = $argv[2] ?? 'phpcs-results.sarif';

$rulesIndex = [];
$rules = [];
$results = [];

if (file_exists($inputFile)) {
    $raw = file_get_contents($inputFile);
    $data = json_decode($raw, true);

    if (is_array($data) && isset($data['files'])) {
        foreach ($data['files'] as $filePath => $fileReport) {
            $relativePath = str_replace('\\', '/', $filePath);

            foreach ($fileReport['messages'] as $message) {
                $ruleId = $message['source'] ?: 'PHPCS.Security.Generic';

                if (!isset($rulesIndex[$ruleId])) {
                    $rulesIndex[$ruleId] = count($rules);
                    $rules[] = [
                        'id' => $ruleId,
                        'shortDescription' => ['text' => $ruleId],
                        'fullDescription' => ['text' => $message['message']],
                        'helpUri' => 'https://github.com/pheromone/phpcs-security-audit',
                    ];
                }

                $level = strtoupper($message['type']) === 'ERROR' ? 'error' : 'warning';

                $results[] = [
                    'ruleId' => $ruleId,
                    'ruleIndex' => $rulesIndex[$ruleId],
                    'level' => $level,
                    'message' => ['text' => $message['message']],
                    'locations' => [[
                        'physicalLocation' => [
                            'artifactLocation' => ['uri' => $relativePath],
                            'region' => [
                                'startLine' => max(1, (int) $message['line']),
                                'startColumn' => max(1, (int) $message['column']),
                            ],
                        ],
                    ]],
                ];
            }
        }
    }
}

$sarif = [
    '$schema' => 'https://raw.githubusercontent.com/oasis-tcs/sarif-spec/master/Schemata/sarif-schema-2.1.0.json',
    'version' => '2.1.0',
    'runs' => [[
        'tool' => [
            'driver' => [
                'name' => 'phpcs-security-audit',
                'informationUri' => 'https://github.com/pheromone/phpcs-security-audit',
                'rules' => $rules,
            ],
        ],
        'results' => $results,
    ]],
];

file_put_contents($outputFile, json_encode($sarif, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

echo "SARIF generado en $outputFile con " . count($results) . " hallazgo(s).\n";
