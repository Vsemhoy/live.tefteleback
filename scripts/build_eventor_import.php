<?php

declare(strict_types=1);

const TARGET_USER_ID = '01KNHVWYBVJT0X6QN30HJ4VDVJ';
const BASE32_CROCKFORD = '0123456789ABCDEFGHJKMNPQRSTVWXYZ';

main($argv);

function main(array $argv): void
{
    $baseDir = dirname(__DIR__);
    $sqlDir = $baseDir . DIRECTORY_SEPARATOR . 'SQL';

    $oldSectionsPath = $sqlDir . DIRECTORY_SEPARATOR . 'evt_sections_old.sql';
    $newSectionsPath = $sqlDir . DIRECTORY_SEPARATOR . 'evt_sections_new.sql';
    $currentSectionsPath = $sqlDir . DIRECTORY_SEPARATOR . 'evt_current_sections.sql';
    $oldEventsPath = $sqlDir . DIRECTORY_SEPARATOR . 'evt_events_old.sql';

    $sectionImportPath = $sqlDir . DIRECTORY_SEPARATOR . 'evt_sections_import.sql';
    $eventsImportPath = $sqlDir . DIRECTORY_SEPARATOR . 'evt_events_import.sql';
    $sectionMapPath = $sqlDir . DIRECTORY_SEPARATOR . 'section_id_map.csv';

    $oldSections = readInsertRows($oldSectionsPath, 'evt_sections');
    $newSectionsSourcePath = is_file($currentSectionsPath) ? $currentSectionsPath : $newSectionsPath;
    $newSections = readInsertRows($newSectionsSourcePath, 'evt_sections');
    $oldEvents = readInsertRows($oldEventsPath, 'evt_events');

    if ($oldSections === []) {
        throw new RuntimeException('No rows found in evt_sections_old.sql');
    }

    if ($oldEvents === []) {
        throw new RuntimeException('No rows found in evt_events_old.sql');
    }

    $existingNames = [];
    foreach ($newSections as $row) {
        $existingNames[normalizeName((string)($row['name'] ?? ''))] = (string)$row['id'];
    }

    $usedSectionIds = [];
    foreach ($newSections as $row) {
        $usedSectionIds[(string)$row['id']] = true;
    }

    $sectionIdMap = [];
    $sectionsToInsert = [];
    $mapRows = [];

    foreach ($oldSections as $row) {
        $oldId = (string)$row['id'];
        $oldName = (string)($row['name'] ?? '');
        $nameKey = normalizeName($oldName);

        if ($nameKey !== '' && isset($existingNames[$nameKey])) {
            $targetId = $existingNames[$nameKey];
            $sectionIdMap[$oldId] = $targetId;
            $mapRows[] = [$oldId, $oldName, $targetId, $oldName, 'matched_existing_by_name'];
            continue;
        }

        $newId = generateUniqueId($usedSectionIds);
        $sectionIdMap[$oldId] = $newId;
        $existingNames[$nameKey] = $newId;

        $normalized = [
            'id' => $newId,
            'user_id' => TARGET_USER_ID,
            'name' => truncateUtf8((string)($row['name'] ?? 'New section'), 32),
            'literals' => nullIfEmpty(truncateUtf8((string)($row['literals'] ?? ''), 3)),
            'description' => nullIfEmpty(truncateUtf8((string)($row['description'] ?? ''), 256)),
            'sort_order' => normalizeInt($row['sort_order'] ?? 0, 0),
            'access' => normalizeInt($row['access'] ?? 1, 1),
            'color' => nullIfEmpty(truncateUtf8((string)($row['color'] ?? ''), 9)),
            'bgcolor' => nullIfEmpty(truncateUtf8((string)($row['bgcolor'] ?? ''), 9)),
            'icon' => nullIfEmpty(truncateUtf8((string)($row['icon'] ?? ''), 64)),
            'decor' => nullIfEmpty((string)($row['decor'] ?? '')),
            'seo' => nullIfEmpty((string)($row['seo'] ?? '')),
            'is_archived' => normalizeBoolInt($row['is_archived'] ?? 0),
            'is_default' => normalizeBoolInt($row['is_default'] ?? 0),
            'created_at' => normalizeDatetime($row['created_at'] ?? null),
            'updated_at' => normalizeDatetime($row['updated_at'] ?? null),
        ];

        $sectionsToInsert[] = $normalized;
        $mapRows[] = [$oldId, $oldName, $newId, $normalized['name'], 'created_new'];
    }

    $eventIds = [];
    foreach ($oldEvents as $row) {
        $eventIds[(string)$row['id']] = true;
    }

    $eventsToInsert = [];
    foreach ($oldEvents as $row) {
        $sectionId = nullIfEmpty((string)($row['section_id'] ?? ''));
        $mappedSectionId = $sectionId !== null && isset($sectionIdMap[$sectionId])
            ? $sectionIdMap[$sectionId]
            : null;

        $parentId = normalizeRelatedEventId($row['parent_id'] ?? null, $eventIds);
        $rootId = normalizeRelatedEventId($row['root_id'] ?? null, $eventIds);

        $eventsToInsert[] = [
            'id' => (string)$row['id'],
            'name' => nullIfEmpty(truncateUtf8((string)($row['name'] ?? ''), 128)),
            'user_id' => TARGET_USER_ID,
            'type_id' => null,
            'format' => normalizeInt($row['format'] ?? 1, 1),
            'metadata' => nullIfEmpty(truncateUtf8((string)($row['metadata'] ?? ''), 25)),
            'language' => nullIfEmpty(truncateUtf8((string)($row['language'] ?? ''), 10)),
            'code_language' => nullIfEmpty(truncateUtf8((string)($row['code_language'] ?? ''), 20)),
            'section_id' => $mappedSectionId,
            'category_id' => null,
            'project_id' => nullIfEmpty((string)($row['project_id'] ?? '')),
            'location' => nullIfEmpty(truncateUtf8((string)($row['location'] ?? ''), 50)),
            'client' => nullIfEmpty(truncateUtf8((string)($row['client'] ?? ''), 120)),
            'content' => nullIfEmpty((string)($row['content'] ?? '')),
            'status' => normalizeInt($row['status'] ?? 1, 1),
            'sort_order' => normalizeInt($row['sort_order'] ?? 1, 1),
            'access' => normalizeInt($row['access'] ?? 1, 1),
            'comment_access' => normalizeInt($row['comment_access'] ?? 2, 2),
            'parent_id' => $parentId,
            'root_id' => $rootId,
            'relation_type' => normalizeInt($row['relation_type'] ?? 0, 0),
            'is_blurred' => 0,
            'is_locked' => normalizeBoolInt($row['is_locked'] ?? 0),
            'is_pinned' => normalizeBoolInt($row['is_pinned'] ?? 0),
            'setdate' => normalizeDatetime($row['setdate'] ?? null),
            'created_at' => normalizeDatetime($row['created_at'] ?? null),
            'updated_at' => normalizeDatetime($row['updated_at'] ?? null),
        ];
    }

    writeSqlInsert(
        $sectionImportPath,
        'evt_sections',
        [
            'id', 'user_id', 'name', 'literals', 'description', 'sort_order', 'access',
            'color', 'bgcolor', 'icon', 'decor', 'seo', 'is_archived', 'is_default',
            'created_at', 'updated_at',
        ],
        $sectionsToInsert
    );

    writeSqlInsert(
        $eventsImportPath,
        'evt_events',
        [
            'id', 'name', 'user_id', 'type_id', 'format', 'metadata', 'language', 'code_language',
            'section_id', 'category_id', 'project_id', 'location', 'client', 'content',
            'status', 'sort_order', 'access', 'comment_access', 'parent_id', 'root_id',
            'relation_type', 'is_blurred', 'is_locked', 'is_pinned', 'setdate', 'created_at', 'updated_at',
        ],
        $eventsToInsert
    );

    writeSectionMapCsv($sectionMapPath, $mapRows);

    echo 'Built files:' . PHP_EOL;
    echo ' - ' . $sectionImportPath . PHP_EOL;
    echo ' - ' . $eventsImportPath . PHP_EOL;
    echo ' - ' . $sectionMapPath . PHP_EOL;
    echo PHP_EOL;
    echo 'Summary:' . PHP_EOL;
    echo ' - old sections: ' . count($oldSections) . PHP_EOL;
    echo ' - existing new sections: ' . count($newSections) . PHP_EOL;
    echo ' - sections source: ' . $newSectionsSourcePath . PHP_EOL;
    echo ' - created sections for import: ' . count($sectionsToInsert) . PHP_EOL;
    echo ' - events prepared for import: ' . count($eventsToInsert) . PHP_EOL;
}

function readInsertRows(string $path, string $table): array
{
    if (!is_file($path)) {
        throw new RuntimeException('Missing file: ' . $path);
    }

    $sql = file_get_contents($path);
    if ($sql === false) {
        throw new RuntimeException('Unable to read file: ' . $path);
    }

    $rows = [];
    $statements = extractInsertStatements($sql, $table);
    foreach ($statements as $statement) {
        $match = [];
        if (!preg_match('/INSERT\s+INTO\s+`' . preg_quote($table, '/') . '`\s*\((.*?)\)\s*VALUES\s*/is', $statement, $match, PREG_OFFSET_CAPTURE)) {
            continue;
        }

        $columnsRaw = $match[1][0];
        $valuesStart = $match[0][1] + strlen($match[0][0]);
        $valuesSql = trim(substr($statement, $valuesStart));
        if (substr($valuesSql, -1) === ';') {
            $valuesSql = substr($valuesSql, 0, -1);
        }

        $columns = array_map(
            static fn (string $c): string => trim($c, " \t\n\r\0\x0B`"),
            explode(',', $columnsRaw)
        );
        $tuples = splitSqlTuples($valuesSql);
        foreach ($tuples as $tuple) {
            $values = parseSqlTupleValues($tuple);
            if (count($values) !== count($columns)) {
                throw new RuntimeException('Column/value mismatch in ' . $path);
            }
            $rows[] = array_combine($columns, $values);
        }
    }

    return $rows;
}

function extractInsertStatements(string $sql, string $table): array
{
    $needle = 'INSERT INTO `' . $table . '`';
    $offset = 0;
    $statements = [];
    $len = strlen($sql);

    while (true) {
        $pos = strpos($sql, $needle, $offset);
        if ($pos === false) {
            break;
        }

        $inString = false;
        $escape = false;
        $end = null;
        for ($i = $pos; $i < $len; $i++) {
            $ch = $sql[$i];

            if ($inString) {
                if ($escape) {
                    $escape = false;
                    continue;
                }
                if ($ch === '\\') {
                    $escape = true;
                    continue;
                }
                if ($ch === "'") {
                    if ($i + 1 < $len && $sql[$i + 1] === "'") {
                        $i++;
                        continue;
                    }
                    $inString = false;
                }
                continue;
            }

            if ($ch === "'") {
                $inString = true;
                continue;
            }

            if ($ch === ';') {
                $end = $i;
                break;
            }
        }

        if ($end === null) {
            throw new RuntimeException('Unterminated INSERT statement for table ' . $table);
        }

        $statements[] = substr($sql, $pos, $end - $pos + 1);
        $offset = $end + 1;
    }

    return $statements;
}

function splitSqlTuples(string $valuesPart): array
{
    $tuples = [];
    $length = strlen($valuesPart);
    $depth = 0;
    $inString = false;
    $escape = false;
    $start = null;

    for ($i = 0; $i < $length; $i++) {
        $ch = $valuesPart[$i];

        if ($inString) {
            if ($escape) {
                $escape = false;
                continue;
            }
            if ($ch === '\\') {
                $escape = true;
                continue;
            }
            if ($ch === "'") {
                if ($i + 1 < $length && $valuesPart[$i + 1] === "'") {
                    $i++;
                    continue;
                }
                $inString = false;
            }
            continue;
        }

        if ($ch === "'") {
            $inString = true;
            continue;
        }

        if ($ch === '(') {
            if ($depth === 0) {
                $start = $i;
            }
            $depth++;
            continue;
        }

        if ($ch === ')') {
            $depth--;
            if ($depth === 0 && $start !== null) {
                $tuples[] = substr($valuesPart, $start, $i - $start + 1);
                $start = null;
            }
        }
    }

    return $tuples;
}

function parseSqlTupleValues(string $tuple): array
{
    $inner = trim($tuple);
    if ($inner[0] !== '(' || $inner[strlen($inner) - 1] !== ')') {
        throw new RuntimeException('Invalid tuple: ' . $tuple);
    }
    $inner = substr($inner, 1, -1);

    $values = [];
    $buffer = '';
    $length = strlen($inner);
    $inString = false;
    $escape = false;

    for ($i = 0; $i < $length; $i++) {
        $ch = $inner[$i];

        if ($inString) {
            if ($escape) {
                $buffer .= sqlUnescapeChar($ch);
                $escape = false;
                continue;
            }

            if ($ch === '\\') {
                $escape = true;
                continue;
            }

            if ($ch === "'") {
                if ($i + 1 < $length && $inner[$i + 1] === "'") {
                    $buffer .= "'";
                    $i++;
                    continue;
                }
                $inString = false;
                continue;
            }

            $buffer .= $ch;
            continue;
        }

        if ($ch === "'") {
            $inString = true;
            continue;
        }

        if ($ch === ',') {
            $values[] = parseRawSqlToken($buffer);
            $buffer = '';
            continue;
        }

        $buffer .= $ch;
    }

    $values[] = parseRawSqlToken($buffer);
    return $values;
}

function parseRawSqlToken(string $token)
{
    $token = trim($token);
    if (strcasecmp($token, 'NULL') === 0) {
        return null;
    }
    if ($token === '') {
        return '';
    }
    return $token;
}

function sqlUnescapeChar(string $ch): string
{
    return match ($ch) {
        'n' => "\n",
        'r' => "\r",
        't' => "\t",
        '0' => "\0",
        'Z' => chr(26),
        default => $ch,
    };
}

function writeSqlInsert(string $path, string $table, array $columns, array $rows): void
{
    $lines = [];
    $lines[] = 'START TRANSACTION;';
    $lines[] = '';

    if ($rows === []) {
        $lines[] = '-- No rows generated for ' . $table . '.';
        $lines[] = 'COMMIT;';
        file_put_contents($path, implode(PHP_EOL, $lines) . PHP_EOL);
        return;
    }

    $chunkSize = 500;
    for ($offset = 0; $offset < count($rows); $offset += $chunkSize) {
        $chunk = array_slice($rows, $offset, $chunkSize);
        $lines[] = 'INSERT INTO `' . $table . '` (`' . implode('`, `', $columns) . '`) VALUES';

        $tupleLines = [];
        foreach ($chunk as $row) {
            $tupleValues = [];
            foreach ($columns as $column) {
                $tupleValues[] = sqlValue($row[$column] ?? null);
            }
            $tupleLines[] = '(' . implode(', ', $tupleValues) . ')';
        }

        $lines[] = implode(',' . PHP_EOL, $tupleLines) . ';';
        $lines[] = '';
    }

    $lines[] = 'COMMIT;';
    file_put_contents($path, implode(PHP_EOL, $lines) . PHP_EOL);
}

function sqlValue($value): string
{
    if ($value === null) {
        return 'NULL';
    }

    if (is_int($value) || is_float($value)) {
        return (string)$value;
    }

    $string = (string)$value;
    $string = str_replace('\\', '\\\\', $string);
    $string = str_replace("'", "\\'", $string);
    return "'" . $string . "'";
}

function writeSectionMapCsv(string $path, array $rows): void
{
    $fp = fopen($path, 'wb');
    if ($fp === false) {
        throw new RuntimeException('Unable to write map file: ' . $path);
    }

    fputcsv($fp, ['old_section_id', 'old_name', 'target_section_id', 'target_name', 'action']);
    foreach ($rows as $row) {
        fputcsv($fp, $row);
    }
    fclose($fp);
}

function normalizeName(string $name): string
{
    $name = trim($name);
    if ($name === '') {
        return '';
    }
    $name = preg_replace('/\s+/u', ' ', $name) ?? $name;
    return mb_strtolower($name, 'UTF-8');
}

function nullIfEmpty(string $value): ?string
{
    return trim($value) === '' ? null : $value;
}

function normalizeInt($value, int $default): int
{
    if ($value === null || $value === '') {
        return $default;
    }
    if (is_numeric($value)) {
        return (int)$value;
    }
    return $default;
}

function normalizeBoolInt($value): int
{
    return normalizeInt($value, 0) > 0 ? 1 : 0;
}

function normalizeDatetime($value): ?string
{
    if ($value === null) {
        return null;
    }

    $s = trim((string)$value);
    if ($s === '' || $s === '0000-00-00 00:00:00' || $s === '0000-00-00') {
        return null;
    }

    return $s;
}

function normalizeRelatedEventId($value, array $eventIds): ?string
{
    $id = nullIfEmpty((string)$value);
    if ($id === null) {
        return null;
    }
    return isset($eventIds[$id]) ? $id : null;
}

function truncateUtf8(string $value, int $maxLen): string
{
    return mb_strlen($value, 'UTF-8') > $maxLen
        ? mb_substr($value, 0, $maxLen, 'UTF-8')
        : $value;
}

function generateUniqueId(array &$used): string
{
    do {
        $id = randomCrockford(26);
    } while (isset($used[$id]));

    $used[$id] = true;
    return $id;
}

function randomCrockford(int $length): string
{
    $alphabet = BASE32_CROCKFORD;
    $max = strlen($alphabet) - 1;
    $out = '';
    for ($i = 0; $i < $length; $i++) {
        $out .= $alphabet[random_int(0, $max)];
    }
    return $out;
}
