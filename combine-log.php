<?php

$moj_logs = new StdClass;
$moj_names = new StdClass;
$pcode_names = new StdClass;
$ly_logs = new StdClass;
$ly_names = new StdClass;
$laws_data = new StdClass;

$output_names = fopen('name.csv', 'w');
fputcsv($output_names, array('name', 'lycode'));
$output_laws = fopen('laws.csv', 'w');
fputcsv($output_laws, array('lycode', 'pcode', 'name', 'status', 'oldname'));

$fp = fopen('moj-history.csv', 'r');
$columns = fgetcsv($fp);
while ($rows = fgetcsv($fp)) {
    if (count($columns) != count($rows)) {
        error_log(json_encode($columns, JSON_UNESCAPED_UNICODE));
        error_log(substr(json_encode($rows, JSON_UNESCAPED_UNICODE), 0, 100));
        exit;
    }
    $values = array_combine($columns, $rows);
    if (!property_exists($moj_logs, $values['pcode'])) {
        $moj_logs->{$values['pcode']} = array();
    }
    $moj_logs->{$values['pcode']}[] = $values;
    $name = $values['title'];
    $name = preg_replace('#（.*）$#u', '', $name);
    if (!property_exists($moj_names, $name)) {
        $moj_names->{$name} = array();
    }
    $moj_names->{$name}[$values['pcode']] = $values['pcode'];

    if (!property_exists($pcode_names, $values['pcode'])) {
        $pcode_names->{$values['pcode']} = array();
    }
    $pcode_names->{$values['pcode']}[$name] = $name;
}
fclose($fp);

$fp = fopen('laws-versions.csv', 'r');
$columns = fgetcsv($fp);
while ($rows = fgetcsv($fp)) {
    $values = array_combine($columns, $rows);

    $name = $values['法條名稱'];
    $name = preg_replace('#（民國.*年）$#u', '', $name);

    if (!property_exists($ly_logs, $values['代碼'])) {
        $ly_logs->{$values['代碼']} = array();
    }
    $ly_logs->{$values['代碼']}[] = $values;

    if (!property_exists($laws_data, $values['代碼'])) {
        $laws_data->{$values['代碼']} = new STdClass;
        $laws_data->{$values['代碼']}->pcode = '';
        $laws_data->{$values['代碼']}->name = $name;
        $laws_data->{$values['代碼']}->status = '';
        $laws_data->{$values['代碼']}->oldname = array();
    }
    $laws_data->{$values['代碼']}->status = explode(';', $values['發布時間'])[0];
    if (!property_exists($ly_names, $name)) {
        $ly_names->{$name} = array();
    }
    $ly_names->{$name}[$values['代碼']] = $values['代碼'];
}
fclose($fp);

foreach ($ly_names as $name => $lycodes) {
    $lycodes = array_Values($lycodes);
    if (property_exists($moj_names, $name)) {
        $pcodes = array_values($moj_names->{$name});
    } else {
        $pcodes = array();
    }
    if (count($lycodes) == 1 and count($pcodes) == 1) {
        foreach ($pcode_names->{$pcodes[0]} as $name) {
            fputcsv($output_names, array($name, $lycodes[0]));
            $laws_data->{$lycodes[0]}->oldname[$name] = $name;
        }
        $laws_data->{$lycodes[0]}->pcode = $pcodes[0];
        error_log("pcode={$pcodes[0]}, lycode={$lycodes[0]}, $name");
        unset($moj_names->{$name}[$pcodes[0]]);
        if (!$moj_names->{$name}) {
            unset($moj_names->{$name});
        }
        continue;
    }
    foreach ($lycodes as $lycode) {
        $logs = $ly_logs->{$lycode};
        $min_date = $max_date = null;
        foreach ($logs as $log) {
            if (preg_match('#中華民國(\d+)年(\d+)月(\d+)日公布#', $log['發布時間'], $matches)) {
            } elseif (preg_match('#中華民國(\d+)年(\d+)月(\d+)日#', $log['發布時間'], $matches)) {
            } else {
                var_dump($log);
                exit;
            }
            $time = intval(sprintf("%03d%02d%02d", $matches[1], $matches[2], $matches[3]));
            if (is_null($min_date)) {
                $min_date = $max_date = $time;
            } else {
                $min_date = min($min_date, $time);
                $max_date = max($max_date, $time);
            }
        }

        $hit = false;
        foreach ($pcodes as $pcode) {
            $logs = $moj_logs->{$pcode};
            $log = $logs[count($logs) - 1];
            $moj_min_date = intval(sprintf("%03d%02d%02d", $log['year'], $log['month'], $log['day']));
            $log = $logs[0];
            $moj_max_date = intval(sprintf("%03d%02d%02d", $log['year'], $log['month'], $log['day']));

            if (abs($min_date - $moj_min_date) < 10000 and abs($max_date - $moj_max_date) < 10000) {
                $hit = true;
                foreach ($pcode_names->{$pcode} as $name) {
                    fputcsv($output_names, array($name, $lycode));
                    $laws_data->{$lycode}->oldname[$name] = $name;
                }
                $laws_data->{$lycode}->pcode = $pcode;
                error_log("pcode={$pcode}, lycode={$lycode}, $name");
                unset($moj_names->{$name}[$pcode]);
                if (!$moj_names->{$name}) {
                    unset($moj_names->{$name});
                }
                break;
            }
        }
        if (!$hit) {
            if (!property_exists($moj_names, $name)) {
                error_log("pcode=null, lycode={$lycode}, $name");
                continue;
            }
        }
    }
}

foreach ($laws_data as $lycode => $data) {
    //fputcsv($output_laws, array('lycode', 'pcode', 'name', 'status', 'oldname'));
    if (array_key_Exists($data->name, $data->oldname)) {
        unset($data->oldname[$data->name]);
    }
    fputcsv($output_laws, array(
        $lycode, $data->pcode, $data->name, $data->status, implode(';', $data->oldname),
    ));
}
