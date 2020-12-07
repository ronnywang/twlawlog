法律歷程記錄
===========

本專案混合法務部法學資料庫和立法院國會圖書館資料，想要整合出法律編修歷程記錄

筆記
----
* 立法院國會圖書館法律系統有每一條法修法的一讀二讀三讀記錄，在立院公報第幾頁連結，但是立法院國會圖書館系統沒有法律的歷史名稱，如果法律更名同一都會顯示最新名稱
* 法務部法學資料庫可以從編修記錄找到法律的歷史名稱
* 議案相關
  * 提案都有固定的 Word / PDF 格式可以解析，從第 7 屆第 6 會期開始才有可解析格式
    * 第一筆：https://lci.ly.gov.tw/LyLCEW/agenda/02/pdf/07/06/01/LCEWA01_070601_00005.pdf
    * 更早的資料都是圖片檔，從國會圖書館可以連結到
      * 國會圖書館的修法記錄的關係文書從第九屆開始才會連到 LCEWA 文件，更早都是連結到圖片檔
    * ~~LCEWA01_070601_00005 可以當作提案的 ID~~
      * LyLCEW/agenda1/02/word/08/03/02/LCEWA01_080302_00005.doc, LyLCEW/agenda1/03/word/08/03/02/01/LCEWA01_080302_00005.doc 是不一樣兩個提案，因為有臨時會的關係
      * LyLCEW/agenda1/02/word/08/03/11/LCEWA01_080311_00009.doc, LyLCEW/agenda1/02/add/08/03/11/LCEWA01_080311_00009.doc 兩個也是不一樣的提案。/add/ 的提案在第 9 屆後就沒再出現了
      * ~~立法院另外有 billNo 欄位，可以跟 LCEWA 一對一對照，Ex: 1010521070200600 => LCEWA01_080114_00024~~ 錯了
  * 審查報告會有 LCEWA01_090411_40005_1 這樣的 ID
  * 一個議案可以在每個會期都重新提一次
  * https://data.ly.gov.tw/getds.action?id=20 有第八屆以後的提案資料，除了法律對照表都找的到
    * 全部下載：http://data.ly.gov.tw/odw/usageFile.action?id=20&type=CSV&fname=20_CSV.csv
  * https://data.ly.gov.tw/getds.action?id=19 有第八屆以後各提案的法律對照表
  * 一個議案可能會符合兩條法律，
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
