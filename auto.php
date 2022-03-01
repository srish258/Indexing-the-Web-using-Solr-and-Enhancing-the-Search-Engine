<?php
$file= "http://localhost:8983/solr/csci572hw5/suggest?q=".strtolower($_GET['name_startsWith'])."&wt=json";
$flag = 0;
$json = file_get_contents($file);
$object = json_decode($json);

$SuggestionArray = array();
$prev = array();
foreach ($object->suggest->suggest as $key=>$value){
    foreach ($value->suggestions as $each){
        if ((!preg_match('/[^a-zA-Z\d]/', $each->term)) || (preg_match( '/\d/',  $each->term)) || (preg_match( '/\s/',  $each->term)))
        {
            if ((!preg_match('/network/', $each->term)) && (!preg_match('/you/', $each->term)) && (!preg_match('/dns/', $each->term)) && (!preg_match('/desktop/', $each->term)) && (!preg_match('/description/', $each->term))){
                $suggestion = $each->term;
                $SuggestionArray[] = $suggestion;
                $flag++;
            }
        }
    }
}
echo json_encode($SuggestionArray);
?>