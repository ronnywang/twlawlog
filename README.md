法律歷程記錄
===========

本專案混合法務部法學資料庫和立法院國會圖書館資料，想要整合出法律編修歷程記錄

筆記
----
* 立法院國會圖書館法律系統有每一條法修法的一讀二讀三讀記錄，在立院公報第幾頁連結，但是立法院國會圖書館系統沒有法律的歷史名稱，如果法律更名同一都會顯示最新名稱
* 法務部法學資料庫可以從編修記錄找到法律的歷史名稱

步驟
----
* php crawl-moj.php > moj-history.csv
  * 將法務部法學資料庫的法律編修記錄存到 moj-history.csv
  * 欄位為 法律名稱,代碼,序號,年,月,日,記錄
* curl https://raw.githubusercontent.com/ronnywang/twlaw/master/laws-versions.csv > laws-versions.csv
  * 從 https://github.com/ronnywang/twlaw 抓取下來的資料抓修法版本過來
* php combine-log.php
  * 把 moj-history.csv 和 laws-versions.csv 的資料產生出 name.csv (法律名稱對照) 和 laws.csv (法律記錄)

有問題法律
---------
* 法學資料庫把不同法律併成一條
  * 典試法
  * 公務人員任用法
  * 公務人員俸給法
  * 公務人員考績法
