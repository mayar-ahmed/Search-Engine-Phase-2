<!DOCTYPE html>
<html lang="en">
<head>
	<meta http-equiv="Content-Type" content="text/html;charset=iso-8859-1">
	
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
require_once('assets/classes/rank.php');
require_once('assets/snippet.php');

if (isset($_POST["submit"])) {

    $query = $_POST['query'];
    $qp = new queryProcessor($connection);
    $results = $qp->process($query);//list of database rows containing : term,stem,df (from stems table) ,tf,location,document url
    $rankObject = new rank();
    $rankObject -> db_connect();
    $rankedResults = $rankObject->rankDocuments($results,$qp->countWords ($query),$qp->isPhraseQuery(),$qp->isStopsOnly()); //Returns the documents ranked
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
            foreach ($rankedResults as $url => $value) {
			$pq=$qp->isPhraseQuery();
			if(!$pq)
			$snippets=snippet($qp->getQueryTokens(),$value['text'],$pq);
			else 
                $snippets=snippet($query,$value['text'],$pq);
                ?>

                <div class="row result">
                    <div class="title">
                        <h3><a href="<?php echo $url;?>"><?php echo $value['title'];?></a></h3> <!-- page url and title here-->
                        <p class="small"><?php echo $url; ?></p>
						<?php /*echo ($value['score']);
						      echo "<br>";
						      echo ($value['rank']);*/?>
                    </div>
                    <div class="snippet">
					<?php
                        foreach($snippets as $s)
						{	$i1 = strpos($s, ' ');
                            $i2 = strrpos($s,' ');
                            $s2 = substr($s, $i1,$i2-$i1);
							echo "<p>...$s2...</p>";
						}
					?>

                    </div>
                </div>

                <?php

            }
        } 
        else {

            echo "<h3> No Results Found For Your Query</h3>";
        }?>
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

