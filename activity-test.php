#!/usr/bin/php5
<?

$yearStart = "2011";
$yearEnd = "2016";
$authorsFile = dirname(__FILE__) . "/nonum.txt";

function echo2($some) {
    if (is_array($some)) {
        $some = join($some, "\n");
    }
    fwrite(STDERR, $some . "\n");
    return $some;
}

if (!file_exists($authorsFile)) {
    die(echo2("I need you to configure the authors file\n"));
}
$authors = file($authorsFile);
$authorEmail = array();

foreach ($authors as $author) {
    preg_match("/\<(.+)\>/", $author, $matches);
    if (!empty($matches[1])) {
        $authorEmail[] = $matches[1];
    }
}
$authorEmail = array_unique($authorEmail);
foreach ($authorEmail as $auth) {
    $authActivity = array();
    for($currentYear = $yearStart; $currentYear < $yearEnd; $currentYear++) {
        $authActivity[$currentYear] = findCommits($auth, $currentYear);
    }
    echo $auth . " " . join($authActivity, " ") . "\n";
}

function findCommits($auth, $yearCurrent) {
    $dateRange = '--since=01/01/' . $yearCurrent . ' --until=01/01/' . ($yearCurrent+1);
    $list = array();
    exec(echo2('git log --diff-filter=M -U0 --author=' . escapeshellcmd($auth) . ' ' . $dateRange . ' --name-only | grep -o \'module\/\w\+\/\(UITEST\|START\|HELPER\|\S\+unit\.php\|\S\+unit\.js\)\' | sort | uniq -d'), $list);

    $moduleActivity = array();
    foreach ($list as $module) {
        $output = array();
        exec(echo2('git log --diff-filter=M -U0 --word-diff=plain --word-diff-regex=\'[A-z0-9_]+|[^[:space:]]\' --author=' . escapeshellcmd($auth) . ' ' . $dateRange . ' -p -- ' . $module . " | grep -Eo '(\[-.+\+\}|\[-.+-\]|\{+.*\+\})' | awk '!seen[$0]++' | wc -l"), $output);
        $moduleActivity[$module] = $output[0];
    }

    arsort($moduleActivity);
    $sumTotal = 0;
    foreach ($moduleActivity as $module => $lines) {
        $sumTotal += $lines;
    }

    echo2("$auth " . $sumTotal . "\n");
    return $sumTotal;
}
