<!DOCTYPE html>
<html lang="en">
<head>

</head>
<body>
<?php
ob_start();

require_once('assets/db_connection.php');
require_once('assets/classes/queryProcessor.php');

if (isset($_POST["submit"])) {


    $query = $_POST['query'];
    $qp = new queryProcessor($connection);
    //get query results, perfom rnaking and put them in a list here


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
            <?php
                //display results here
            echo $query;

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

