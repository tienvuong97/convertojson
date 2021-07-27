<html>

<head>
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>
    <script>
        var _validFileExtensions = [".xls", ".xlsx", ".csv"];

        function ValidateSingleInput(oInput) {
            if (oInput.type == "file") {
                var sFileName = oInput.value;
                if (sFileName.length > 0) {
                    var blnValid = false;
                    for (var j = 0; j < _validFileExtensions.length; j++) {
                        var sCurExtension = _validFileExtensions[j];
                        if (sFileName.substr(sFileName.length - sCurExtension.length, sCurExtension.length).toLowerCase() == sCurExtension.toLowerCase()) {
                            blnValid = true;
                            break;
                        }
                    }

                    if (!blnValid) {
                        alert("Sorry, " + sFileName + " is invalid, allowed extensions are: " + _validFileExtensions.join(", "));
                        oInput.value = "";
                        return false;
                    }
                }
            }
            return true;
        }
    </script>
    <style>
        .pass {
            color: green;
            font-weight: bold;
        }

        .fail {
            color: red;
            font-weight: bold;
        }

        thead {
            background-color: #f3f3f3;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>TOOL convert Excel to XML</h1>
        <p class="lead"><a href="template.xlsx" target="_blank">Excel Template available here.</a></p>
        <div class="container">
            <form action="" method="POST" enctype="multipart/form-data">
                <label> Export to XML<input type="text" value="D:/Download/Json/sitemap.xml" name="export-xml" required></label><br>
                <label> Export to Json<input type="text" value="D:/Download/Json/sitemap.json" name="export-json" required></label>
                <input type="file" name="excel" onchange="ValidateSingleInput(this)" />
                <input type="submit" />
            </form>
        </div>
        <?php

        if (isset($_FILES['excel']) && $_FILES['excel']['error'] == 0) {
            error_reporting(E_ERROR | E_PARSE);
            require_once "Classes/PHPExcel.php";
            $tmpfname = $_FILES['excel']['tmp_name'];
            $excelReader = PHPExcel_IOFactory::createReaderForFile($tmpfname);
            $excelObj = $excelReader->load($tmpfname);
            $worksheet = $excelObj->getSheet(0);
            $lastRow = $worksheet->getHighestRow();
            function arrayToXml($lastRow, $worksheet)
            {
                $xml = '<?xml version=' . '"1.0"' . ' encoding="UTF-8"?>';
                $xml .= '<?xml-stylesheet type="text/xsl" href="https://www.logigear.com/sitemap_urlset.xsl"?>
            <urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xhtml="http://www.w3.org/1999/xhtml" xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd http://www.w3.org/1999/xhtml http://www.w3.org/2002/08/xhtml/xhtml1-strict.xsd" xhtml="http://www.w3.org/1999/xhtml">';
                for ($row = 2; $row <= $lastRow; $row++) {
                    $myObj = new stdClass();
                    $page = $worksheet->getCell('A' . $row)->getValue();
                    $xml .= "<url>
                    <loc>" . $page . "</loc>
                    <changefreq>weekly</changefreq>
                    <priority>0.5</priority>
                </url>";
                }
                $xml .= "</urlset>";
                $file = fopen($_POST['export-xml'], "w");
                fwrite($file, $xml);
            }
            arrayToXml($lastRow, $worksheet);
            $myJSON = "{";
            for ($row = 2; $row <= $lastRow; $row++) {
                $page = $worksheet->getCell('A' . $row)->getValue();
                $content = file_get_contents($page);
                $doc = new DOMDocument();
                if ($doc->loadHTML($content)) {
                    $xpath = new DOMXpath($doc);
                    $objs = explode("https://www.logigear.com", $page);
                    $myJSON .= '"';
                    $myJSON .= $objs[1];
                    $myJSON .= '":{';
                    $elementsTitle = $xpath->query('//title');
                    if (!is_null($elementsTitle)) {
                        foreach ($elementsTitle as $element) {
                            $nodes = $element->childNodes;
                            foreach ($nodes as $node) {
                                $tit = str_replace("l LogiGear Corporation", "", strip_tags($node->nodeValue));
                                $tit = str_replace("l LogiGear", "", $tit);
                                $tit = str_replace("| LogiGear", "", $tit);
                                $tit = str_replace("| LogiGear Corporation", "", $tit);
                                $myJSON .= '"type":["COM_JLSITEMAP_TYPES_MENU"],';
                                $myJSON .= '"title": "';
                                $myJSON .= $tit;
                                $myJSON .= '",';
                                $myJSON .= '"level": ';
                                $myJSON .= substr_count($objs[1], '/');
                                $myJSON .= ',';
                                $myJSON .= ' "loc": "';
                                $myJSON .= $page;
                                $myJSON .= '",';
                                $myJSON .= ' "changefreq": "weekly",
                                "changefreqValue": 4,
                                "priority": "0.5",
                                "exclude": false,
                                "alternates": false';

                            }
                        }
                    }
             
                    $myJSON .= '},';
                }
                
            }
            $myJSON = rtrim($myJSON,',');
            $myJSON .= "}";
            $file = fopen($_POST['export-json'], "w");
            fwrite($file, $myJSON);
        }
        ?>
    </div>

</body>