#!/usr/bin/php5
<?

$yearStart = "2007";
$yearEnd = "2016";
$authorsFile = dirname(__FILE__) . "/nonum2.txt";
// Authors file needs to be in the format: <someaddress@somedomain.com> <anotheraliasfromsameuser@somedomain.com>
// One line per author

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
    $found = preg_match_all("/\<(\S+)\>/", $author, $matches);
    $emails = array();
    if ($found) {
        foreach ($matches[0] as $match) {
            $emails[] = $match;
        }
    }
    $authorEmail[] = $emails;
}

foreach ($authorEmail as $auth) {
    $authActivity = array();
    for($currentYear = $yearStart; $currentYear < $yearEnd; $currentYear++) {
        $authActivity[$currentYear] = findCommits($auth, $currentYear);
    }
    echo $auth[0] . " " . join($authActivity, " ") . "\n";
}

function findCommits($auth, $yearCurrent) {
    $dateRange = '--since=01/01/' . $yearCurrent . ' --until=01/01/' . ($yearCurrent+1);
    $list = array();
    $authFilter = "";
    foreach ($auth as $em) {
        $authFilter .= ' --author=' . escapeshellcmd($em) . " ";
    }
    // Use this one to calculate totals
    //exec(echo2('git log --diff-filter=AM -U0 ' . $authFilter . ' ' . $dateRange . ' --name-only | grep -o \'\S\+\(\.php\|\.js\)$\' | sort | uniq -d'), $list);
    // Use this one to calculate test work
    exec(echo2('git log --diff-filter=AM -U0 ' . $authFilter . ' ' . $dateRange . ' --name-only | grep -o \'module\/\w\+\/\(UITEST\|START\|HELPER\|\S\+unit\.php\|\S\+unit\.js\)\' | sort | uniq -d'), $list);

    $moduleActivity = array();
    foreach ($list as $module) {
        $output = array();
        exec(echo2('git log --diff-filter=AM -U0 --word-diff=plain --word-diff-regex=\'[A-z0-9_]+|[^[:space:]]\'' . $authFilter . ' ' . $dateRange . ' -p -- ' . $module . " | grep -Eo '(\[-.+\+\}|\[-.+-\]|\{+.*\+\})' | awk '!seen[$0]++' | wc -l"), $output);
        $moduleActivity[$module] = $output[0];
    }

    arsort($moduleActivity);
    $sumTotal = 0;
    foreach ($moduleActivity as $module => $lines) {
        $sumTotal += $lines;
    }

    echo2("$authFilter " . $sumTotal . "\n");
    return $sumTotal;
}
