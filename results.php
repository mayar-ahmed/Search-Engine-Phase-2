<!DOCTYPE html>
<html lang="en">
<head>

    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/bootstrap-theme.min.css">
    <link rel="stylesheet" href="css/index.css">
    <link rel="stylesheet" href="css/results.css">


</head>
<body>
<?php
ob_start();
require_once('assets/functions.php');
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
     $rankObject = new relevanceRank();
     $rankObject -> db_connect();
    $rankedResults = $rankObject->rank($results,$qp->countWords ($query),$qp->isPhraseQuery());
    //get query results, perfom ranking and put them in a list here
    //check display results to see how o access rows


    ?>


    <div class="container">
        <div class="row" id="top">
            <div id="logo2" class="col-md-2">
                <h2>ZING</h2>
            </div>
            <form class="form-group form-inline col-md-8" action="results.php" method="post" id="form">

                <div class="form-group">

                    <input name="query" type="text" placeholder="Enter Search query" class="form-control" size="50"/>
                </div>

                <div class="form-group ">
                    <input type="submit" name="submit" value="Search" class="btn btn-lg"/>
                </div>
            </form>

        </div>


        <?php
        //display results here
        if ($rankedResults != null) {
            $urlE;
            //echo (count($rankedResults));
            //echo "<br>";
            //while ($row = mysqli_fetch_assoc($rankedResults)) {
            foreach ($rankedResults as $url => $value) {
                if($qp->isPhraseQuery())
                    $urlE = $value['url'];
                else
                    $urlE = $url;
                ?>

                <div class="row result">
                    <div class="title">
                        <h3><a href="<?php echo $urlE;?>"><?php echo $value['title'];?></a></h3> <!-- page url and title here-->
                        <p class="small"><?php echo $urlE; ?></p>
                    </div>
                    <div class="snippet">
                        <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit,
                            sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.
                            Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut
                            aliquip ex ea commodo consequat..</p>

                    </div>
                </div>

                <?php

            }
        } 
        else {

            echo "<h3> No Results Found For Your Query</h3>";
        }?>

        <!--search results here -->
        <div>
            <br>
            <?php
                //display results here
            /*
            if($qp->isPhraseQuery())
            {
                 foreach($rankedResults as $key => $value)
                {
                    echo "<br>";
                    foreach($value as $k => $v)
                    echo ($v);
                    echo "<br>";

                }

            }

            else{
            foreach ($rankedResults as $url => $info) {
            echo "$url" . "<br>";
            echo "<br>";
            echo ($info['score']);
            echo "<br>" . "-------------------------------- " . "<br>";
        }
    }
    */

            //if($results!=null)

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
                /*
                foreach($rankedResults as $doc=>$rank)
                {
                    echo "<br>" . "$doc : ";
                    echo($rank);
                    echo "<br>";
                    echo "-------------------------------------";
                }
                */
            
       


        ?>

        <!--search results here -->


    </div>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>
    <script src="js/bootstrap.min.js"></script>


    <?php
} else {
    redirect_to('index.php');

}

ob_end_flush();
?>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>
<script src="js/bootstrap.min.js"></script>

</body>
</html>

