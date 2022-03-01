<?php

include "SpellCorrector.php";
ini_set("memory_limit", -1);

// make sure browsers see this page as utf-8 encoded HTML
header('Content-Type: text/html; charset=utf-8');

$limit = 10;
$query = isset($_REQUEST['q']) ? $_REQUEST['q'] : false;
$results = false;

if ($query) {
    // The Apache Solr Client library should be on the include path
    // which is usually most easily accomplished by placing in the
    // same directory as this script ( . or current directory is a default
    // php include path entry in the php.ini)
    require_once('Apache/Solr/Service.php');

    // create a new solr service instance - host, port, and webapp
    // path (all defaults in this example)
    $solr = new Apache_Solr_Service('localhost', 8983, '/solr/csci572hw5');



    // SPELL CORRECTION
    $query_terms = explode(" ", $query);
    $correct_terms = [];
    foreach ($query_terms as $term)
        $correct_terms[] = SpellCorrector::correct($term);
    echo "<script>console.log('" . array_values($correct_terms)[0] . "')</script>";
    $correct_query = implode(" ", $correct_terms);
    $spellCheck = false;
    if (strtolower($query) != strtolower($correct_query))
        $spellCheck = true;

    // in production code you'll always want to use a try /catch for any
    // possible exceptions emitted  by searchingexternal_pageRankFile (i.e. connection
    // problems or a query parsing error)
    try {
        if ($_GET["algo"] == "pagerank") {
            $algo = "pagerank";
            $additionalParameters = array('sort' => 'pageRankFile desc');
            $results = $solr->search($query, 0, $limit, $additionalParameters);
        } else {
            $algo = "lucene";
            $results = $solr->search($query, 0, $limit);
        }
    } catch (Exception $e) {
        // in production you'd probably log or email this error to an admin
        // and then show a special message to the user but for this example
        // we're going to show the full exception
        die("<html><head><title>SEARCH EXCEPTION</title><body><pre>{$e->__toString()}</pre></body></html>");
    }
}

?>
<html>

<head>
    <title>PHP Solr Client Example</title>
    <link href="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8/themes/base/jquery-ui.css" rel="stylesheet" type="text/css"/>
    <script src="http://ajax.googleapis.com/ajax/libs/jquery/1.5/jquery.min.js"></script>
    <script src="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8/jquery-ui.min.js"></script>
    <link href="http://code.jquery.com/ui/1.10.4/themes/ui-lightness/jquery-ui.css" rel="stylesheet">
    </link>
    <script src="http://code.jquery.com/jquery-1.10.2.js"></script>
    <script src="http://code.jquery.com/ui/1.11.4/jquery-ui.js"></script>
    <script>
        $(document).ready(function(){
            $('#q').focus();
        });

        $(document).ready(function() {
            $('#q').autocomplete({

                source: function( request, response ) {

                    var numWordsRequest = request.term.split(" ").length;
                    if(request.term.split(" ").length > 1){
                        var oldTerm = request.term.split(request.term.split(" ")[numWordsRequest-1])[0];
                        var requestTerm = request.term.split(" ")[numWordsRequest-1];
                    }else{
                        var requestTerm = request.term;
                    }

                    $.ajax({
                        url : 'auto.php',
                        dataType: 'json',
                        data: {
                            name_startsWith: requestTerm,
                        },
                        success: function( data ) {
                            if (numWordsRequest>1){
                                var reformattedResponse = data.map(function(itemString){
                                    return oldTerm + itemString;
                                });
                                response(reformattedResponse);
                            }else{
                                response(data);
                            }
                        }
                    });
                },
                autoFocus: true,
                minLength: 0
            });
        });
    </script>
</head>

<body>
<form accept-charset="utf-8" method="get">
    <center>
        <h1><label for="q">Search</label></h1>
    </center>
    <center>
        <input id="q" name="q" type="text" value="<?php echo htmlspecialchars($query, ENT_QUOTES, 'utf-8'); ?>" />
    </center>
    <br />
    <center>
        <input type="radio" name="algo" value="lucene" <?php if (isset($_REQUEST['algo']) && $_REQUEST['algo'] == 'lucene') {
            echo 'checked="checked"';
        } ?>> Lucene
        <input type="radio" name="algo" value="pagerank" <?php if (isset($_REQUEST['algo']) && $_REQUEST['algo'] == 'pagerank') {
            echo 'checked="checked"';
        } ?>> Page Rank</center>
    <br />
    <center><input type="submit" /></center>
</form>

<?php

// display results
if ($results) {
$total = (int) $results->response->numFound;
$start = min(1, $total);
$end = min($limit, $total);
echo "Showing results for ", $query;
if($spellCheck){
    $link = "http://localhost:63342/solrUI/index.php?q=$correct_query&algo=$algo";
    echo "<br>Did you mean <a href='$link'>$correct_query</a>?";
}

?>
<div>Results <?php echo $start; ?> - <?php echo $end; ?> of <?php echo $total; ?>:</div>
<ol>
    <?php
    // iterate result documents
    $csv = array_map('str_getcsv', file('URLtoHTML_fox_news.csv'));
    foreach ($results->response->docs as $doc) {
        $id = $doc->id;
        $title = $doc->title;
        $url = $doc->og_url;
        $desc = $doc->og_description;

        if ($desc == "" || $desc == null)
            $desc = "N/A";
        if ($title == "" || $title == null)
            $title = "N/A";
        if ($url == "" || $url == null) {
            foreach ($csv as $row) {
                try {
                    $cmp = "/Users/CSCI572_HW4/run/solr-8.10.1/foxnews/" .$row[0];
                    if ($id == $cmp) {
                        $url = $row[1];
                        unset($row);
                        break;
                    }
                }catch (Exception $exception){
                    $url="https://www.foxnews.com/".$row[0];
                }

            }
        }

        echo "Title : <a href = '$url'>$title</a></br>";
        echo "URL : <a href = '$url'>$url</a></br>";
        echo "ID : $id</br>";
        echo "Description : $desc </br></br>";
    }
    }
    ?>
</body>

</html>