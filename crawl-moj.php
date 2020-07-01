<?php

$content = file_get_contents("https://law.moj.gov.tw/Law/LawSearchLaw.aspx?TY=04051008");
$output = fopen('php://output', 'w');
fputcsv($output, array('title', 'pcode', 'seq', 'year', 'month', 'day', 'log'));

$to_num = null;
$to_num = function($str) use (&$to_num) {
    $n = 0;
    if (strpos($str, '一百') === 0) {
        return 100 + $to_num(mb_substr($str, 2, null, 'UTF-8'));
    }
    if (strpos($str, '零') === 0){
        return $to_num(mb_substr($str, 1, null, 'UTF-8'));
    }
    if (strpos($str, '十') === 0) {
        return 10 + $to_num(mb_substr($str, 1, null, 'UTF-8'));
    }

    $map = array(
        '一' => 1,
        '二' => 2,
        '三' => 3,
        '四' => 4,
        '五' => 5,
        '六' => 6,
        '七' => 7,
        '八' => 8,
        '九' => 9,
    );
    if (mb_strlen($str, 'UTF-8') == 1 and array_key_exists($str, $map)) {
        return $map[$str];
    }
    if (mb_substr($str, 1, 1, 'UTF-8') == '十' and array_key_exists(mb_substr($str, 0, 1, 'UTF-8'), $map)) {
        return $map[mb_substr($str, 0, 1, 'UTF-8')] * 10 + $to_num(mb_substr($str, 2, null, 'UTF-8'));
    }
    if (mb_strlen($str, 'UTF-8') == 0) {
        return 0;
    }
    if ($str == '五五') {
        return 55;
    }

    throw new Exception("未知的數字 {$str}");
};

file_put_Contents('error', '');

preg_match_all('#LawSearchLaw.aspx\?TY=(\d+)([^"]*)#', $content, $matches_url);
foreach ($matches_url[1] as $idx => $ty_id) {
    $target = __DIR__ . "/html/{$ty_id}.html";
    $url = html_entity_decode($matches_url[0][$idx]);
    error_log($url);
    if (!file_exists($target)) {
        error_log($ty_id);
        file_put_contents(__DIR__ . "/html/{$ty_id}.html", file_get_contents("https://law.moj.gov.tw/Law/{$url}"));
    }

    $content = file_get_contents($target);
    preg_match_all('#Hot/AddHotLaw.ashx\?PCode=([^"]*)#', $content, $matches);
    foreach ($matches[1] as $pcode) {
        $target = __DIR__ . "/html/history-{$pcode}.html";
        if (!file_exists($target)) {
            error_log($target);
            file_put_contents($target, file_get_contents("https://law.moj.gov.tw/LawClass/LawHistory.aspx?pcode={$pcode}"));
            sleep(1);
        }

        $content = file_get_contents($target);
        $doc = new DOMDocument;
        @$doc->loadHTML($content);
        $title = trim($doc->getElementById('hlLawName')->nodeValue);
        foreach ($doc->getElementsByTagName('div') as $div_dom) {
            if ($div_dom->getAttribute('class') != 'col-data text-pre') {
                continue;
            }
            $body = preg_replace('/\s+/', '', $div_dom->nodeValue);
            if (preg_match('#^(\d+)\.$#', $body)) {
                continue;
            }
            // M0050003 5.中華民國七十二牛十二月二十日經濟部修正發布
            // R0040062 2.中華民國八十三月一月二十六日考試院令修正發布第4條條文
            // F0100001 6.中華民國六十八年十二日二十六日國防部（68）金銓字第4249號令修正發布
            if ($pcode == 'M0050003' and $body == '5.中華民國七十二牛十二月二十日經濟部修正發布') {
                $body = '5.中華民國七十二年十二月二十日經濟部修正發布';
            } elseif ($pcode == 'R0040062' and $body == '2.中華民國八十三月一月二十六日考試院令修正發布第4條條文') {
                $body = '2.中華民國八十三年一月二十六日考試院令修正發布第4條條文';
            } elseif ($pcode == 'F0100001' and $body == '6.中華民國六十八年十二日二十六日國防部（68）金銓字第4249號令修正發布') {
                $body = '6.中華民國六十八年十二月二十六日國防部（68）金銓字第4249號令修正發布';
            } elseif ($pcode == 'D0000032') {
                // PHP Fatal error:  Uncaught exception 'Exception' with message '3.中華民國八十九年七月五日內政部（89）台內 地字第8973042號令修正發布名稱及全文14條；並自發布日起施行（原名稱：地價評議委員會暨標準地價評議委員會組織 規程；新名稱：地價暨標準地價評議委員會組織規程） 地價及標準地價評議委員會組織規程 D0000032 地價及標準地價 評議委員會組織規程 != 地價暨標準地價評議委員會組織規程' in /srv/db1/twlaw2020/crawl.php:92
                $body = str_replace('地價暨標準地價', '地價及標準地價', $body);
            } elseif ($pcode == 'G0380185') {
                // PHP Fatal error:  Uncaught exception 'Exception' with message '2.中華民國九十年一月十七日總統（90）華總一 義字第9000009340號令修正公布全文12條；並自公布日起施行（原名稱：日據時代株式會社台灣銀行海外分支機構特別當座預金處理條例；新名稱：日據時代株式會社臺灣銀行海外分支機構存款及匯款處理條例） 日據時代株式會社台灣銀行 海外分支機構存款及匯款處理條例 G0380185 日據時代株式會社台灣銀行海外分支機構存款及匯款處理條例 != 日據時代株式會社臺灣銀行海外分支機構存款及匯款處理條例' in /srv/db1/twlaw2020/crawl.php:109
                $body = str_replace('臺灣銀行', '台灣銀行', $body);
            } elseif ($pcode == 'G0350064') {
                // PHP Fatal error:  Uncaught exception 'Exception' with message '3.中華民國八十五年十月八日財務部（85）台財 關字第852017686號令修正發布名稱及全文19條（原名稱：收遞貨物進出口通關辦法；新名稱：快遞貨物進出口通關辦法 ） 快遞貨物通關辦法 G0350064 快遞貨物通關辦法 != 快遞貨物進出口通關辦法' in /srv/db1/twlaw2020/crawl.php:112
                $body = str_replace('快遞貨物進出口通關辦法', '快遞貨物通關辦法', $body);
            } elseif ($pcode == 'H0080035') {
                // PHP Fatal error:  Uncaught exception 'Exception' with message '2.中華民國八十八年二月三日教育部（88）台參 字第88010951號令修正發布全文9條及名稱；並自八十八年八月一日起施行（原名稱：特殊教育學生入學年齡修業年限及 保送甄試升學辦法；新名稱：資賦優異學生降底入學年齡縮短修業年限及升學辦法） 資賦優異學生降低入學年齡縮短修 業年限及升學辦法 H0080035 資賦優異學生降低入學年齡縮短修業年限及升學辦法 != 資賦優異學生降底入學年齡縮短修業年限及升學辦法' in /srv/db1/twlaw2020/crawl.php:115
                $body = str_replace('降底入學', '降低入學', $body);
            }

            $act = '';
            if (preg_match('#^(\d+)\.(中華民|民國|中民國|中華華民國|中華民國)?([一二三四五六七八九十百零]+)年([一二三四五六七八九十元]+)?月?([一二三四五六七八九十]*)日?(.*)#u', $body, $matches)) {
                if ($matches[4] == '元') {
                    $matches[4] = '一';
                }
                $old_title = $title;
                $act = trim($matches[6]);
                $act = str_replace('\\', '', $act);
                if (preg_match('#（原名稱：(.*)；新名稱：(.*)）#u', $body, $matches2)) {
                    $old = $matches2[1];
                    $new = $matches2[2];

                    $stack = 0;
                    for ($i = 0; $i < mb_strlen($new, 'UTF-8'); $i ++) {
                        if (mb_substr($new, $i, 1, 'UTF-8') == '）') {
                            $stack --;
                        } elseif (mb_substr($new, $i, 1, 'UTF-8') == '（') {
                            $stack ++;
                        }
                        if ($stack == -1) {
                            $new = mb_substr($new, 0, $i, 'UTF-8');
                        }
                    }
                    if ($title != $new) {
                        file_put_Contents('error', "{$body} {$title} {$pcode} {$title} != {$new}\n", FILE_APPEND);
                    }
                    $old_title = $old;
                }
                fputcsv($output, array($title, $pcode, $matches[1], $to_num($matches[3]), $to_num($matches[4]), $to_num($matches[5]), $act));
                $title = $old_title;
            }
        }
    }
}
