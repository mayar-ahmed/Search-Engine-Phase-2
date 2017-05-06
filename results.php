<!DOCTYPE html>
<html lang="en">
<head>

</head>
<body>
<?php
ob_start();

require_once('assets/db_connection.php');
require_once('assets/classes/queryProcessor.php');
require_once('assets/classes/relevanceRank.php');

if (isset($_POST["submit"])) {
    
    $query = $_POST['query'];
    $qp = new queryProcessor($connection);
    $results = $qp->process($query);//list of database rows containing :
	//term,stem,df (from stems table) ,tf,location,document url

    //to get tokens $qp->getQueryTokens();
    //to get stems $qp->getQueryStems();
     $rankObject = new relevanceRank($connection);
     $temp = $qp->getQueryStems();
     echo "<br> From Processing: <br>";
        foreach($temp as $k => $v){
            echo "$k : $v";
            echo "<br>";
        }
        $words = $qp->countWords ($query);
        echo "<br>" . "QUERY:" . "<br>";
        foreach ($words as $k => $v) {
            if($k == "football")
                echo "YES"."<br>";
        }
    $rankedResults = $rankObject->rank($results,$qp->countWords ($query),$qp->getQueryStems());
	
    //get query results, perfom ranking and put them in a list here
	//check display results to see how o access rows


    ?>


    <div class="container">
        <form class="form-inline" action="results.php" method="post">
            <div class="form-group">

                <input name="query" type="text" placeholder="Enter Search query" class="form-control"/>
            </div>

            <div class="form-group">
                <input type="submit" name="submit" value="Search" class="btn btn-danger"/>
            </div>
        </form>

        <!--search results here -->
        <div>
            <br>
            <?php
                //display results here

            /*
                while ($row =mysqli_fetch_assoc($results))
                {
                    echo $row['url'] . "<br>";
                }
                mysqli_free_result($results); //to free memory after displaying
                */
/*
                foreach ($rankedResults as $key => $value) {
                    echo($value);
                    echo"   =>   ";
                    echo ($key);
                    echo "<br>";
                }
*/
                foreach($rankedResults as $doc=>$data)
                {
                    echo "<br>" . "$doc : " . "<br>";
                    foreach($data as $k => $v)
                    {
                        echo "$k : $v" . "<br>";
                    }
                    echo "-------------------------------------";
                }


            ?>
        </div>

    </div>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>
    <script src="js/bootstrap.min.js"></script>


    <?php
} else {
    redirect_to('index.php');

}

ob_end_flush();
?>

</body>
</html>

