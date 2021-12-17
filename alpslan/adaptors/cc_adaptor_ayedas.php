<?php

// To collect data from izsu.com
// file name : cc_adaptor_ayedas.php


class CC_ADAPTOR_AYEDAS {
   
    public function scrapeFiles(){
        $arrFinalValue = array();
        $timezone = date_default_timezone_get();
        date_default_timezone_set('Europe/Istanbul');
        $strScraperTime = date('Ymd H:i:s', strtotime('now'));
        $strFileContent = file_get_contents('https://www.ayedas.com.tr/pages/bilgilendirme/planlibakim/planli-kesinti-listesi-ve-haritasi.aspx');
        $objDomDoc = new DOMDocument();
        @$objDomDoc->loadHTML($strFileContent);
        foreach ($objDomDoc->getElementsByTagName('table') as $objTable) {
            if(trim($objTable->getAttribute('class')) == 'table table-responsive table-striped'){
                foreach ($objTable->getElementsByTagName('tr') as $objTRs) {
                    $strProvince = trim($objTRs->getAttribute('data-il'));
                    $strDistict = trim($objTRs->getAttribute('data-ilce'));
                    $strDate = trim($objTRs->getAttribute('data-tarih'));
                    foreach ($objTRs->getElementsByTagName('td') as $intTdCount => $objTDs) {
                        // To get the start time end time
                        if($intTdCount == 0){
                            $strStartEndTime = trim($objTDs->nodeValue);
                            $strStartTime = $strEndTime = '';
                            if(preg_match('#(\d+:\d+)\s+-\s+(\d+:\d+).*#', $strStartEndTime, $arrStarEndTime)){
                                $strStartTime = $arrStarEndTime[1];
                                $strEndTime = $arrStarEndTime[2];
                            }
                            continue;
                        }
                        $strDeductions = trim($objTDs->nodeValue);
                        preg_match_all('#Belde\/.*:(.*)-\s+Mahalle:(.*)-\s+Sokak:(.*)#', $strDeductions, $arrDeductions);
                        if(count($arrDeductions[2]) > 0){
                            foreach ($arrDeductions[2] as $intPostalCount => $strPostalValue) {
                                if(isset($arrDeductions[3][$intPostalCount])
                                       && trim($arrDeductions[3][$intPostalCount]) != ''){
                                    $arrFile['SOURCE_INSTITUTION'] = 'ayedas';
                                    $arrFile['TAGS'] = 'Cut/Shortage, Water';
                                    $arrFile['PROVINCE'] = "İSTANBUL";
                                    $arrFile['DISTRICT'] = $strDistict;
                                    $arrFile['POSTAL_CODE'] = $strPostalValue;
                                    $arrFile['STREET'] = $arrDeductions[3][$intPostalCount]. ' Sokak';
                                    $arrFile['VENUE'] = '';
                                    $arrFile['COUNTRY'] = 'TURKEY';
                                    $arrFile['RAW_ADDRESS'] = $arrDeductions[0][$intPostalCount];
                                    $arrFile['RAW_START'] = '';
                                    $arrFile['START_DATE'] = $strDate;
                                    $arrFile['START_TIME'] = $strStartTime;
                                    $arrFile['FINISH_DATE'] = $strDate;
                                    $arrFile['FINISH_TIME'] = $strEndTime;
                                    $arrFile['DESCRIPTION'] = '';
                                    $arrFile['LINK'] = 'https://www.ayedas.com.tr/pages/bilgilendirme/planlibakim/planli-kesinti-listesi-ve-haritasi.aspx';
                                    $arrFile['IMAGE_PATH'] = '';
                                    $arrFile['IMAGE_LINK'] = '';
                                    $arrFile['COORDINATE'] = '';
                                    $arrFile['SCRAPE_DATE'] = $strScraperTime;
                                    if(date('Ymd', strtotime($strDate)) >= date('Ymd')){
                                        $arrFinalValue[] = $arrFile;
                                    }
                                }
                            }
                        }
                        unset($arrDeductions);
                    }
                    unset($objTRs);
                }
            }
            unset($objTable);
        }    
        date_default_timezone_set($timezone);
        return $arrFinalValue;
    }
    
    public function createFileInCsv($arrFinalValues) {
        if(count($arrFinalValues) == 0){
            trigger_error("\n Result In Csv Format -- Empty result returned \n ");
        }
        $timezone = date_default_timezone_get();
        date_default_timezone_set('Europe/Istanbul');
        $strScraperDate = date('dmY', strtotime('now'));
        $strScraperTime = date('His', strtotime('now'));
        $strFinalResultFilePath = "\\csvfiles\\";
        $strCsvFileName = str_replace(array('{date}', '{time}'),
                    array($strScraperDate, $strScraperTime), 'cc_adaptor_ayedas_{date}{time}.csv');
        if (!file_exists($strFinalResultFilePath)) {
            mkdir($strFinalResultFilePath, 777, true);
        }
        $fileOpenAndWrite = fopen($strFinalResultFilePath.$strCsvFileName, 'w');
        print 'Saved File Path => '.$strFinalResultFilePath.$strCsvFileName."\n";
        $blnHeaderValue = FALSE;
        foreach ($arrFinalValues as $intKeyCount => $arrEachRowResult) {
            if($intKeyCount == 0 && $blnHeaderValue == FALSE){
                fputcsv($fileOpenAndWrite, array_keys($arrEachRowResult), ';');
                $blnHeaderValue = TRUE;
            }
            fputcsv($fileOpenAndWrite, $arrEachRowResult, ';');
        }
        fclose($fileOpenAndWrite);
    }
}


$objadaptorAyedas = new CC_ADAPTOR_AYEDAS();
$arrFinalValues = $objadaptorAyedas->scrapeFiles();
$objadaptorAyedas->createFileInCsv($arrFinalValues);


